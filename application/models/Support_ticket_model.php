<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Support
 * @author   : Mamun Mia Turan
 * @filename : Support_ticket_model.php
 *
 * Support tickets + their reply thread. A staff member opens a ticket ON a
 * customer complaint (or standalone); the customer and admins then converse via
 * ticket_replies until it is Closed.
 */
class Support_ticket_model extends MY_Model
{
    protected $table = 'support_tickets';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDelete = false;
    protected $allowedFields = [
        'ticket_number', 'complaint_id', 'customer_id', 'subject',
        'priority', 'status', 'assigned_to', 'created_by',
    ];

    const STATUSES   = ['Open', 'In Progress', 'Answered', 'Closed'];
    const PRIORITIES = ['Low', 'Medium', 'High'];

    /** A short, unique, human-friendly ticket number (e.g. TCK-8FA31C). */
    public function generate_number()
    {
        do {
            $num = 'TCK-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 6));
        } while ($this->db->where('ticket_number', $num)->count_all_results($this->table) > 0);
        return $num;
    }

    /**
     * Open a ticket on a complaint. Seeds the thread with the complaint text as
     * the customer's first message and moves the complaint to "Under Review".
     * Returns the new ticket id (or an existing one if already opened).
     */
    public function open_from_complaint(array $complaint, $created_by)
    {
        $existing = $this->db->where('complaint_id', (int) $complaint['id'])->get($this->table)->row_array();
        if ($existing) {
            return (int) $existing['id'];
        }
        $id = $this->insert([
            'ticket_number' => $this->generate_number(),
            'complaint_id'  => (int) $complaint['id'],
            'customer_id'   => $complaint['customer_id'] !== null ? (int) $complaint['customer_id'] : null,
            'subject'       => $complaint['subject'],
            'priority'      => 'Medium',
            'status'        => 'Open',
            'created_by'    => $created_by ? (int) $created_by : null,
        ]);
        if ($id) {
            // Seed the thread with the original complaint message.
            $this->add_reply($id, 'customer', $complaint['customer_id'], $complaint['name'], $complaint['message']);
        }
        return $id;
    }

    /** Append a message to the ticket thread and bump the ticket status. */
    public function add_reply($ticket_id, $sender_type, $sender_id, $sender_name, $message)
    {
        $this->db->insert('ticket_replies', [
            'ticket_id'   => (int) $ticket_id,
            'sender_type' => $sender_type === 'admin' ? 'admin' : 'customer',
            'sender_id'   => $sender_id ? (int) $sender_id : null,
            'sender_name' => $sender_name ?: null,
            'message'     => $message,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        // Admin reply -> Answered; customer reply -> Open (awaiting staff).
        $new_status = ($sender_type === 'admin') ? 'Answered' : 'Open';
        $this->update($ticket_id, ['status' => $new_status]);
        return (int) $this->db->insert_id();
    }

    /** Full thread for a ticket, oldest first. */
    public function replies($ticket_id)
    {
        return $this->db->where('ticket_id', (int) $ticket_id)
            ->order_by('id', 'asc')->get('ticket_replies')->result_array();
    }

    public function set_status($id, $status)
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }
        return $this->update($id, ['status' => $status]);
    }

    public function count_open()
    {
        return (int) $this->db->where_in('status', ['Open', 'In Progress'])->count_all_results($this->table);
    }

    // ---- Admin server-side DataTable ------------------------------------
    public function count_all()
    {
        return (int) $this->db->count_all($this->table);
    }

    public function datatable($search, $start, $length, $order_col, $order_dir)
    {
        $build = function () use ($search) {
            $this->db->from($this->table . ' t');
            $this->db->join('users u', 'u.id = t.customer_id', 'left');
            if ($search !== '') {
                $this->db->group_start()
                    ->like('t.ticket_number', $search)->or_like('t.subject', $search)
                    ->or_like('t.status', $search)->or_like('u.name', $search)
                    ->group_end();
            }
        };
        $build();
        $filtered = $this->db->count_all_results('', false);
        $this->db->select('t.*, u.name AS customer_name', false);
        $build();
        $this->db->order_by('t.' . $order_col, $order_dir)->limit($length, $start);
        $data = $this->db->get()->result();
        return ['data' => $data, 'filtered' => $filtered];
    }

    // ---- Storefront ------------------------------------------------------
    public function for_customer($customer_id)
    {
        return $this->db->where('customer_id', (int) $customer_id)
            ->order_by('id', 'desc')->get($this->table)->result_array();
    }

    public function find_for_customer($id, $customer_id)
    {
        return $this->db->where('id', (int) $id)
            ->where('customer_id', (int) $customer_id)
            ->get($this->table)->row_array();
    }
}
