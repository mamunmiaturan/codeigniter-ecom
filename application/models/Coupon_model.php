<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Promotions
 * @author   : Mamun Mia Turan
 * @filename : Coupon_model.php
 *
 * Coupon persistence + the validation / discount engine used by the cart and
 * checkout, plus usage tracking (total + per-user) and admin CRUD helpers.
 */
class Coupon_model extends MY_Model
{
    protected $table = 'coupons';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDelete = true;
    protected $allowedFields = [
        'code', 'description', 'type', 'value', 'min_order_amount', 'max_discount_amount',
        'usage_limit', 'usage_limit_per_user', 'starts_at', 'expires_at', 'status',
        'created_by', 'updated_by',
    ];

    public function get_active_by_code($code)
    {
        return $this->db->where('code', strtoupper(trim((string) $code)))
            ->where('deleted_at', null)
            ->get('coupons')->row_array();
    }

    /**
     * Validate a coupon for a given subtotal + caller identity.
     * @return array ['ok'=>bool, 'code'=>string, 'message'=>?string, 'coupon'=>?array, 'discount'=>float, 'free_shipping'=>bool]
     */
    public function validate($code, $subtotal, $identity)
    {
        $c = $this->get_active_by_code($code);
        if (!$c) {
            return ['ok' => false, 'code' => 'not_found', 'message' => 'Invalid coupon code'];
        }
        if ($c['status'] !== 'Active') {
            return ['ok' => false, 'code' => 'inactive', 'message' => 'This coupon is not active'];
        }
        $now = date('Y-m-d H:i:s');
        if (!empty($c['starts_at']) && $c['starts_at'] > $now) {
            return ['ok' => false, 'code' => 'not_started', 'message' => 'This coupon is not yet active'];
        }
        if (!empty($c['expires_at']) && $c['expires_at'] < $now) {
            return ['ok' => false, 'code' => 'expired', 'message' => 'This coupon has expired'];
        }
        if ((float) $subtotal < (float) $c['min_order_amount']) {
            return ['ok' => false, 'code' => 'min_order',
                    'message' => 'Minimum order of ' . number_format((float) $c['min_order_amount'], 2) . ' required for this coupon'];
        }
        if ($c['usage_limit'] !== null && (int) $c['used_count'] >= (int) $c['usage_limit']) {
            return ['ok' => false, 'code' => 'limit_reached', 'message' => 'This coupon has reached its usage limit'];
        }
        if ($c['usage_limit_per_user'] !== null) {
            if ($this->_user_usage_count((int) $c['id'], $identity) >= (int) $c['usage_limit_per_user']) {
                return ['ok' => false, 'code' => 'user_limit', 'message' => 'You have already used this coupon'];
            }
        }

        $calc = $this->compute_discount($c, $subtotal);
        return ['ok' => true, 'code' => 'valid', 'coupon' => $c,
                'discount' => $calc['discount'], 'free_shipping' => $calc['free_shipping']];
    }

    public function compute_discount($c, $subtotal)
    {
        $subtotal = (float) $subtotal;
        $discount = 0.0;
        $free_shipping = false;

        if ($c['type'] === 'percentage') {
            $discount = $subtotal * ((float) $c['value'] / 100);
            if ($c['max_discount_amount'] !== null && $discount > (float) $c['max_discount_amount']) {
                $discount = (float) $c['max_discount_amount'];
            }
        } elseif ($c['type'] === 'fixed') {
            $discount = min((float) $c['value'], $subtotal);
        } elseif ($c['type'] === 'free_shipping') {
            $free_shipping = true;
        }

        if ($discount > $subtotal) {
            $discount = $subtotal;
        }
        return ['discount' => round($discount, 2), 'free_shipping' => $free_shipping];
    }

    private function _user_usage_count($coupon_id, $identity)
    {
        $this->db->where('coupon_id', $coupon_id);
        if (!empty($identity['user_id'])) {
            $this->db->where('user_id', (int) $identity['user_id']);
        } elseif (!empty($identity['guest_token'])) {
            $this->db->where('guest_token', $identity['guest_token']);
        } else {
            return 0;
        }
        return (int) $this->db->count_all_results('coupon_usages');
    }

    public function record_usage($coupon_id, $order_id, $identity, $discount)
    {
        $this->db->insert('coupon_usages', [
            'coupon_id'       => (int) $coupon_id,
            'order_id'        => $order_id ? (int) $order_id : null,
            'user_id'         => !empty($identity['user_id']) ? (int) $identity['user_id'] : null,
            'guest_token'     => empty($identity['user_id']) ? ($identity['guest_token'] ?? null) : null,
            'discount_amount' => $discount,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
        $this->db->set('used_count', 'used_count + 1', false)->where('id', (int) $coupon_id)->update('coupons');
    }

    // ---- admin ----

    public function unique_code($code, $ignore_id = null)
    {
        $this->db->where('code', strtoupper(trim((string) $code)));
        if ($ignore_id) {
            $this->db->where('id !=', (int) $ignore_id);
        }
        return $this->db->get('coupons')->num_rows() === 0;
    }

    public function toggle_status($id)
    {
        $row = $this->find($id);
        if (empty($row)) {
            return false;
        }
        $new = ($row['status'] === 'Active') ? 'Inactive' : 'Active';
        return $this->update($id, ['status' => $new]) ? $new : false;
    }

    public function count_all($status = '')
    {
        $this->db->where('deleted_at', null);
        if ($status !== '' && $status !== null) {
            $this->db->where('status', $status);
        }
        return $this->db->count_all_results($this->table);
    }

    public function datatable($search, $start, $length, $order_col, $order_dir, $status = '')
    {
        $apply = function () use ($search, $status) {
            $this->db->where('deleted_at', null);
            if ($status !== '' && $status !== null) {
                $this->db->where('status', $status);
            }
            if ($search !== '') {
                $this->db->group_start();
                $this->db->like('code', $search);
                $this->db->or_like('description', $search);
                $this->db->group_end();
            }
        };

        $this->db->from($this->table);
        $apply();
        $filtered = $this->db->count_all_results();

        $this->db->from($this->table);
        $apply();
        $this->db->order_by($order_col, $order_dir);
        $this->db->limit($length, $start);
        $data = $this->db->get()->result();

        return ['filtered' => $filtered, 'data' => $data];
    }
}
