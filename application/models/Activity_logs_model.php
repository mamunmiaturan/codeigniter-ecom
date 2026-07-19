<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Activity_logs_model.php
 */

class Activity_logs_model extends MY_Model
{

    protected $table = 'activity_logs';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get logs with user information
     */
    public function get_logs($limit = 100)
    {
        $this->db->select('activity_logs.*, users.name as user_name');
        $this->db->from('activity_logs');
        $this->db->join('users', 'users.id = activity_logs.user_id', 'left');
        $this->db->order_by('activity_logs.id', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result_array();
    }

    /**
     * Get recent activity for the dashboard — lightweight, no JOIN needed
     * because user_name is already stored in the log row.
     */
    public function get_recent(int $limit = 15): array
    {
        return $this->db
            ->select('activity_logs.action, activity_logs.description, activity_logs.created_at, users.name as user_name')
            ->from($this->table)
            ->join('users', 'users.id = activity_logs.user_id', 'left')
            ->order_by('activity_logs.id', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * Count total log entries (for dashboard stats).
     */
    public function count_all(): int
    {
        return (int) $this->db->count_all($this->table);
    }
}
