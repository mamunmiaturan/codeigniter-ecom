<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Payment
 * @author   : Mamun Mia Turan
 * @filename : Payment_model.php
 *
 * Resolves the payment registry (config/payment_methods.php) merged with
 * admin overrides in `payment_settings`, instantiates gateway classes, and
 * performs payment_status transitions + transaction logging (idempotent).
 */
class Payment_model extends MY_Model
{
    protected $table = 'payment_settings';

    private $_methods = null;

    // ================= Registry =================

    public function methods()
    {
        if ($this->_methods !== null) {
            return $this->_methods;
        }
        $this->config->load('payment_methods', false, true);
        $reg = $this->config->item('payment_methods') ?: [];

        $overrides = [];
        if ($this->db->table_exists('payment_settings')) {
            foreach ($this->db->get('payment_settings')->result_array() as $row) {
                $overrides[$row['code']] = $row;
            }
        }

        $out = [];
        foreach ($reg as $code => $meta) {
            if (isset($overrides[$code])) {
                $o = $overrides[$code];
                $meta['is_active'] = ((int) $o['is_active'] === 1);
                if (!empty($o['title'])) {
                    $meta['title'] = $o['title'];
                }
                if ($o['sort_order'] !== null && $o['sort_order'] !== '') {
                    $meta['sort'] = (int) $o['sort_order'];
                }
                $cfg = !empty($o['config']) ? json_decode($o['config'], true) : null;
                if (is_array($cfg)) {
                    $meta['config'] = array_merge($meta['config'] ?? [], $cfg);
                }
            }
            $out[$code] = $meta;
        }
        $this->_methods = $out;
        return $out;
    }

    public function gateway($code)
    {
        $methods = $this->methods();
        if (!isset($methods[$code])) {
            return null;
        }
        require_once APPPATH . 'libraries/Payment_gateways.php';
        $cls = $methods[$code]['class'] ?? null;
        if (!$cls || !class_exists($cls)) {
            return null;
        }
        return new $cls($code, $methods[$code]);
    }

    /**
     * Active, usable methods for the checkout selector, sorted.
     */
    public function available_for_checkout()
    {
        $out = [];
        foreach ($this->methods() as $code => $m) {
            if (empty($m['is_active'])) {
                continue;
            }
            if (($m['class'] ?? '') === 'Sslcommerz_gateway'
                && (empty($m['config']['store_id']) || empty($m['config']['store_passwd']))) {
                continue; // not configured yet
            }
            $out[] = [
                'code'        => $code,
                'title'       => $m['title'] ?? ucfirst($code),
                'description' => $m['description'] ?? '',
                'is_online'   => !empty($m['is_online']),
                'sort'        => (int) ($m['sort'] ?? 0),
            ];
        }
        usort($out, function ($a, $b) { return $a['sort'] <=> $b['sort']; });
        return $out;
    }

    public function is_valid_method($code)
    {
        foreach ($this->available_for_checkout() as $m) {
            if ($m['code'] === $code) {
                return true;
            }
        }
        return false;
    }

    // ================= Order payment transitions =================

    public function order_by_number($order_number)
    {
        return $this->db->where('order_number', $order_number)->get('orders')->row_array();
    }

    public function is_paid($order_id)
    {
        $o = $this->db->select('payment_status')->where('id', (int) $order_id)->get('orders')->row();
        return $o && $o->payment_status === 'paid';
    }

    public function mark_paid($order_id, $gateway, $txn_id, $amount = null, $payload = null)
    {
        if ($this->is_paid($order_id)) {
            return true; // idempotent — IPN + return may both fire
        }
        $now = date('Y-m-d H:i:s');
        $this->db->where('id', (int) $order_id)->update('orders', [
            'payment_status'  => 'paid',
            'payment_gateway' => $gateway,
            'transaction_id'  => $txn_id,
            'status'          => 'confirmed',
            'updated_at'      => $now,
        ]);
        $this->db->insert('order_status_history', [
            'order_id'   => (int) $order_id,
            'status'     => 'confirmed',
            'note'       => 'Payment received via ' . $gateway,
            'created_at' => $now,
        ]);
        $this->log_transaction($order_id, $gateway, $txn_id, $amount, 'paid', $payload);

        // Deliver any downloadable products on this order (idempotent).
        $this->load->model('download_model');
        $this->download_model->grant_for_order($order_id);
        return true;
    }

    public function mark_failed($order_id, $gateway, $payload = null)
    {
        if ($this->is_paid($order_id)) {
            return true;
        }
        $this->db->where('id', (int) $order_id)->update('orders', [
            'payment_status' => 'failed',
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        $this->log_transaction($order_id, $gateway, null, null, 'failed', $payload);
        return true;
    }

    public function log_transaction($order_id, $gateway, $txn_id, $amount, $status, $payload)
    {
        $this->db->insert('payment_transactions', [
            'order_id'       => (int) $order_id,
            'gateway'        => $gateway,
            'transaction_id' => $txn_id,
            'amount'         => $amount,
            'status'         => $status,
            'payload'        => $payload ? json_encode($payload) : null,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    // ================= Admin settings =================

    public function save_setting($code, $data)
    {
        $row = [
            'code'       => $code,
            'is_active'  => !empty($data['is_active']) ? 1 : 0,
            'title'      => $data['title'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'config'     => isset($data['config']) ? json_encode($data['config']) : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($this->db->get_where('payment_settings', ['code' => $code])->row()) {
            $this->db->where('code', $code)->update('payment_settings', $row);
        } else {
            $row['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('payment_settings', $row);
        }
    }

    // ---- transaction history (audit of every gateway transition) ----

    public function transactions_datatable($search, $start, $length, $status = '')
    {
        $apply = function () use ($search, $status) {
            $this->db->from('payment_transactions pt')->join('orders o', 'o.id = pt.order_id', 'left');
            if ($status !== '' && $status !== null) {
                $this->db->where('pt.status', $status);
            }
            if ($search !== '') {
                $this->db->group_start()
                    ->like('o.order_number', $search)
                    ->or_like('pt.transaction_id', $search)
                    ->or_like('pt.gateway', $search)
                    ->or_like('o.customer_name', $search)
                    ->group_end();
            }
        };
        $apply();
        $filtered = $this->db->count_all_results();
        $apply();
        $this->db->select('pt.*, o.order_number, o.customer_name', false)->order_by('pt.id', 'DESC')->limit($length, $start);
        return ['filtered' => $filtered, 'data' => $this->db->get()->result()];
    }

    public function transactions_count($status = '')
    {
        if ($status !== '' && $status !== null) {
            $this->db->where('status', $status);
        }
        return (int) $this->db->count_all_results('payment_transactions');
    }
}
