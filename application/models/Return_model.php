<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Returns (RMA)
 * @author   : Mamun Mia Turan
 * @filename : Return_model.php
 *
 * Return / RMA requests (customer-initiated) + admin moderation.
 */
class Return_model extends MY_Model
{
    protected $table = 'return_requests';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    const STATUSES = ['requested', 'approved', 'rejected', 'received', 'refunded', 'cancelled'];

    public function generate_rma_number()
    {
        do {
            $num = 'RMA' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while ($this->db->where('rma_number', $num)->get('return_requests')->num_rows() > 0);
        return $num;
    }

    /**
     * @param array $identity ['user_id'=>?, 'guest_token'=>?]
     * @param array $items    each ['order_item_id'=>?int, 'product_name'=>str, 'quantity'=>int]
     * @return string|false the RMA number
     */
    public function create_request($order_id, $identity, $reason, $customer_note, $items, $type = 'return')
    {
        $items = array_values(array_filter($items, function ($i) { return (int) ($i['quantity'] ?? 0) > 0; }));
        if (empty($items)) {
            return false;
        }
        $rma = $this->generate_rma_number();
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();
        $row = [
            'rma_number'    => $rma,
            'order_id'      => (int) $order_id,
            'user_id'       => !empty($identity['user_id']) ? (int) $identity['user_id'] : null,
            'guest_token'   => empty($identity['user_id']) ? ($identity['guest_token'] ?? null) : null,
            'reason'        => trim((string) $reason) ?: null,
            'customer_note' => trim((string) $customer_note) ?: null,
            'status'        => 'requested',
            'created_at'    => $now,
        ];
        if ($this->db->field_exists('type', 'return_requests')) {
            $row['type'] = in_array($type, ['return', 'exchange'], true) ? $type : 'return';
        }
        $this->db->insert('return_requests', $row);
        $req_id = (int) $this->db->insert_id();
        foreach ($items as $it) {
            $this->db->insert('return_items', [
                'return_request_id' => $req_id,
                'order_item_id'     => !empty($it['order_item_id']) ? (int) $it['order_item_id'] : null,
                'product_name'      => (string) $it['product_name'],
                'quantity'          => (int) $it['quantity'],
                'created_at'        => $now,
            ]);
        }
        $this->db->trans_complete();
        return $this->db->trans_status() !== false ? $rma : false;
    }

    public function get_items($request_id)
    {
        return $this->db->where('return_request_id', (int) $request_id)->get('return_items')->result_array();
    }

    public function get_for_order($order_id)
    {
        return $this->db->where('order_id', (int) $order_id)->order_by('id', 'DESC')->get('return_requests')->result_array();
    }

    public function get_for_customer($user_id)
    {
        $this->db->select('r.*, o.order_number');
        $this->db->from('return_requests r');
        $this->db->join('orders o', 'o.id = r.order_id', 'left');
        $this->db->where('r.user_id', (int) $user_id);
        $this->db->order_by('r.id', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get($id)
    {
        return $this->db->where('id', (int) $id)->get('return_requests')->row_array();
    }

    public function get_by_rma($rma_number)
    {
        return $this->db->where('rma_number', $rma_number)->get('return_requests')->row_array();
    }

    public function set_status($id, $status, $admin_note = null)
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }
        $data = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        if ($admin_note !== null && $admin_note !== '') {
            $data['admin_note'] = $admin_note;
        }
        $this->db->where('id', (int) $id)->update('return_requests', $data);
        return true;
    }

    /** Has this order already got an open/active return? */
    public function has_open_return($order_id)
    {
        return $this->db
            ->where('order_id', (int) $order_id)
            ->where_not_in('status', ['rejected', 'cancelled'])
            ->count_all_results('return_requests') > 0;
    }

    // ---- admin datatable ----

    public function count_all($status = '')
    {
        if ($status !== '' && $status !== null) {
            $this->db->where('status', $status);
        }
        return $this->db->count_all_results('return_requests');
    }

    public function datatable($search, $start, $length, $order_col, $order_dir, $status = '')
    {
        $apply = function () use ($search, $status) {
            $this->db->from('return_requests r');
            $this->db->join('orders o', 'o.id = r.order_id', 'left');
            if ($status !== '' && $status !== null) {
                $this->db->where('r.status', $status);
            }
            if ($search !== '') {
                $this->db->group_start()
                    ->like('r.rma_number', $search)
                    ->or_like('o.order_number', $search)
                    ->or_like('r.reason', $search)
                    ->group_end();
            }
        };
        $apply();
        $filtered = $this->db->count_all_results();
        $this->db->select('r.*, o.order_number, o.customer_name');
        $apply();
        $this->db->order_by($order_col, $order_dir)->limit($length, $start);
        return ['filtered' => $filtered, 'data' => $this->db->get()->result()];
    }
}
