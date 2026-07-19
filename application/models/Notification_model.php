<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Notification Model
 * Handles database operations for notifications.
 */
class Notification_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get notifications list
     */
    public function get_notifications($user_id, $role_id)
    {
        $this->db->select('notifications.*, users.name as user_name');
        $this->db->from('notifications');
        $this->db->join('users', 'users.id = notifications.user_id', 'left');
        if ($role_id != ROLE_SUPERMAN_ID) {
            $this->db->where('notifications.user_id', $user_id);
        }
        $this->db->order_by('notifications.id', 'DESC');
        return $this->db->get()->result_array();
    }

    /**
     * Get unread notifications
     */
    public function get_unread_notifications($user_id)
    {
        $this->db->where('user_id', $user_id);
        $this->db->where('is_read', 0);
        $this->db->order_by('id', 'DESC');
        return $this->db->get('notifications')->result_array();
    }

    /**
     * Mark all notifications as read
     */
    public function mark_all_as_read($user_id)
    {
        $this->db->where('user_id', $user_id);
        return $this->db->update('notifications', ['is_read' => 1]);
    }

    /**
     * Mark single notification as read
     */
    public function mark_single_as_read($id, $user_id)
    {
        $this->db->where('id', $id);
        $this->db->where('user_id', $user_id);
        return $this->db->update('notifications', ['is_read' => 1]);
    }
}
