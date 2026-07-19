<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Support
 * @author   : Mamun Mia Turan
 * @filename : Contact_message_model.php
 *
 * Storefront contact submissions. Admins read/reply/close; there is no admin
 * create path, so this model exposes list + transition helpers only.
 */
class Contact_message_model extends MY_Model
{
    protected $table = 'contact_messages';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDelete = false;
    protected $allowedFields = [
        'name', 'email', 'phone', 'subject', 'message',
        'status', 'admin_reply', 'replied_by',
    ];

    /** Valid workflow states, in order. */
    const STATUSES = ['New', 'Read', 'Replied', 'Closed'];

    /**
     * Mark a still-unread message as Read. Never downgrades a message that has
     * already progressed (Replied/Closed) back to Read.
     */
    public function mark_read($id)
    {
        $row = $this->find($id);
        if (empty($row) || $row['status'] !== 'New') {
            return false;
        }
        return $this->update($id, ['status' => 'Read']);
    }

    /**
     * Save the admin reply and move the message to Replied.
     */
    public function reply($id, $reply, $by)
    {
        $row = $this->find($id);
        if (empty($row)) {
            return false;
        }
        return $this->update($id, [
            'admin_reply' => $reply,
            'status'      => 'Replied',
            'replied_by'  => $by ? (int) $by : null,
        ]);
    }

    /**
     * Force a specific workflow status.
     */
    public function set_status($id, $status)
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }
        return $this->update($id, ['status' => $status]);
    }

    /** Count of unread (New) messages — used for the sidebar/inbox badge. */
    public function count_new()
    {
        return (int) $this->db->where('status', 'New')->count_all_results($this->table);
    }

    public function count_all($status = '')
    {
        if ($status !== '' && $status !== null) {
            $this->db->where('status', $status);
        }
        return $this->db->count_all_results($this->table);
    }

    public function datatable($search, $start, $length, $order_col, $order_dir, $status = '')
    {
        $apply = function () use ($search, $status) {
            $this->db->from('contact_messages');
            if ($status !== '' && $status !== null) {
                $this->db->where('status', $status);
            }
            if ($search !== '') {
                $this->db->group_start()
                    ->like('name', $search)
                    ->or_like('email', $search)
                    ->or_like('subject', $search)
                    ->or_like('message', $search)
                    ->group_end();
            }
        };
        $apply();
        $filtered = $this->db->count_all_results();
        $apply();
        $this->db->order_by($order_col, $order_dir)->limit($length, $start);
        return ['filtered' => $filtered, 'data' => $this->db->get()->result()];
    }
}
