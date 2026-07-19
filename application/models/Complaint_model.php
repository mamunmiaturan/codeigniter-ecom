<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Support
 * @author   : Mamun Mia Turan
 * @filename : Complaint_model.php
 *
 * Customer-filed complaints. Customers create them from the storefront account
 * area; staff review them and (optionally) open a support ticket ON a complaint
 * to work it. Single message + workflow status — the back-and-forth lives on
 * the linked ticket, not here.
 */
class Complaint_model extends MY_Model
{
    protected $table = 'complaints';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDelete = false;
    protected $allowedFields = [
        'customer_id', 'name', 'email', 'phone', 'order_id',
        'subject', 'message', 'status',
    ];

    const STATUSES = ['New', 'Under Review', 'Resolved', 'Closed'];

    /** Force a workflow status. */
    public function set_status($id, $status)
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }
        return $this->update($id, ['status' => $status]);
    }

    /** Count of still-New complaints (sidebar/inbox badge). */
    public function count_new()
    {
        return (int) $this->db->where('status', 'New')->count_all_results($this->table);
    }

    // ---- Admin server-side DataTable ------------------------------------
    public function count_all()
    {
        return (int) $this->db->count_all($this->table);
    }

    public function datatable($search, $start, $length, $order_col, $order_dir)
    {
        $this->db->from($this->table);
        if ($search !== '') {
            $this->db->group_start()
                ->like('name', $search)->or_like('email', $search)
                ->or_like('subject', $search)->or_like('status', $search)
                ->group_end();
        }
        $filtered = $this->db->count_all_results('', false);
        $this->db->select('c.*, t.ticket_number', false);
        $this->db->from($this->table . ' c');
        $this->db->join('support_tickets t', 't.complaint_id = c.id', 'left');
        if ($search !== '') {
            $this->db->group_start()
                ->like('c.name', $search)->or_like('c.email', $search)
                ->or_like('c.subject', $search)->or_like('c.status', $search)
                ->group_end();
        }
        $this->db->order_by('c.' . $order_col, $order_dir)->limit($length, $start);
        $data = $this->db->get()->result();
        return ['data' => $data, 'filtered' => $filtered];
    }

    // ---- Storefront ------------------------------------------------------
    /** A customer's own complaints, newest first. */
    public function for_customer($customer_id)
    {
        return $this->db->where('customer_id', (int) $customer_id)
            ->order_by('id', 'desc')->get($this->table)->result_array();
    }

    /** A single complaint scoped to its owner (null if not theirs). */
    public function find_for_customer($id, $customer_id)
    {
        return $this->db->where('id', (int) $id)
            ->where('customer_id', (int) $customer_id)
            ->get($this->table)->row_array();
    }
}
