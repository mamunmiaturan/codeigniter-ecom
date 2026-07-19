<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Customer
 * @author   : Mamun Mia Turan
 * @filename : Customer_address_model.php
 *
 * Saved customer shipping addresses. EVERY method is scoped by user_id so a
 * customer can only read/modify their own addresses (IDOR-safe). Maintains a
 * single default address per customer.
 */
class Customer_address_model extends MY_Model
{
    protected $table = 'customer_addresses';

    public function get_all($user_id)
    {
        return $this->db
            ->where('user_id', (int) $user_id)
            ->where('deleted_at', null)
            ->order_by('is_default', 'DESC')
            ->order_by('id', 'DESC')
            ->get('customer_addresses')->result_array();
    }

    public function get($id, $user_id)
    {
        return $this->db
            ->where('id', (int) $id)
            ->where('user_id', (int) $user_id)
            ->where('deleted_at', null)
            ->get('customer_addresses')->row_array();
    }

    public function create($user_id, $data)
    {
        $user_id = (int) $user_id;
        $count = $this->db->where('user_id', $user_id)->where('deleted_at', null)->count_all_results('customer_addresses');
        $make_default = !empty($data['is_default']) || $count === 0;

        $data['user_id']    = $user_id;
        $data['is_default'] = $make_default ? 1 : 0;
        $data['created_at'] = date('Y-m-d H:i:s');

        $this->db->trans_start();
        if ($make_default) {
            $this->db->where('user_id', $user_id)->update('customer_addresses', ['is_default' => 0]);
        }
        $this->db->insert('customer_addresses', $data);
        $id = (int) $this->db->insert_id();
        $this->db->trans_complete();

        return $this->db->trans_status() !== false ? $id : false;
    }

    public function update_address($id, $user_id, $data)
    {
        $user_id = (int) $user_id;
        $existing = $this->get($id, $user_id);
        if (!$existing) {
            return false;
        }
        $make_default = !empty($data['is_default']);
        unset($data['user_id'], $data['id']); // never reassign owner / pk
        $data['is_default'] = $make_default ? 1 : (int) $existing['is_default'];
        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->trans_start();
        if ($make_default) {
            $this->db->where('user_id', $user_id)->update('customer_addresses', ['is_default' => 0]);
        }
        $this->db->where('id', (int) $id)->where('user_id', $user_id)->update('customer_addresses', $data);
        $this->db->trans_complete();

        return $this->db->trans_status() !== false;
    }

    public function delete_address($id, $user_id)
    {
        $user_id = (int) $user_id;
        $existing = $this->get($id, $user_id);
        if (!$existing) {
            return false;
        }
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();
        $this->db->where('id', (int) $id)->where('user_id', $user_id)->update('customer_addresses', ['deleted_at' => $now]);
        if ((int) $existing['is_default'] === 1) {
            // Promote the most-recent remaining address to default.
            $next = $this->db->where('user_id', $user_id)->where('deleted_at', null)
                ->order_by('id', 'DESC')->limit(1)->get('customer_addresses')->row();
            if ($next) {
                $this->db->where('id', (int) $next->id)->update('customer_addresses', ['is_default' => 1]);
            }
        }
        $this->db->trans_complete();

        return $this->db->trans_status() !== false;
    }

    public function set_default($id, $user_id)
    {
        $user_id = (int) $user_id;
        if (!$this->get($id, $user_id)) {
            return false;
        }
        $this->db->trans_start();
        $this->db->where('user_id', $user_id)->update('customer_addresses', ['is_default' => 0]);
        $this->db->where('id', (int) $id)->where('user_id', $user_id)->update('customer_addresses', ['is_default' => 1]);
        $this->db->trans_complete();

        return $this->db->trans_status() !== false;
    }
}
