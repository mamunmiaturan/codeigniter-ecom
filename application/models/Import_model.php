<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Import Model
 * Handles database operations for user/CSV imports.
 */
class Import_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Create a new import record
     */
    public function create_import($data)
    {
        $this->db->insert('imports', $data);
        return $this->db->insert_id();
    }

    /**
     * Get all import records
     */
    public function get_imports($role_id, $user_id)
    {
        $this->db->select('imports.*, users.name as importer_name');
        $this->db->from('imports');
        $this->db->join('users', 'users.id = imports.user_id', 'left');
        if ($role_id != ROLE_SUPERMAN_ID) {
            $this->db->where('imports.user_id', $user_id);
        }
        $this->db->order_by('imports.id', 'DESC');
        return $this->db->get()->result_array();
    }

    /**
     * Get a specific import record by ID with role-based restriction
     */
    public function get_import_by_id($id, $role_id, $user_id)
    {
        $this->db->where('id', $id);
        if ($role_id != ROLE_SUPERMAN_ID) {
            $this->db->where('user_id', $user_id);
        }
        return $this->db->get('imports')->row_array();
    }

    /**
     * Update an import record by ID
     */
    public function update_import($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('imports', $data);
    }

    /**
     * Get user's role ID by user_id
     */
    public function get_user_role($user_id)
    {
        $uploader = $this->db->select('role')
            ->where('user_id', $user_id)
            ->get('login_credential')
            ->row();
        return $uploader ? intval($uploader->role) : 0;
    }

    /**
     * Check if email already exists in login_credentials
     */
    public function email_exists($email)
    {
        return $this->db->where('email', $email)
            ->get('login_credential')
            ->num_rows() > 0;
    }

    /**
     * Get all existing emails as a lowercase lookup map
     */
    public function get_all_emails_map()
    {
        $results = $this->db->select('email')
            ->get('login_credential')
            ->result_array();
        $map = [];
        foreach ($results as $row) {
            if (!empty($row['email'])) {
                $map[strtolower(trim($row['email']))] = true;
            }
        }
        return $map;
    }

    /**
     * Insert notification record
     */
    public function create_notification($data)
    {
        return $this->db->insert('notifications', $data);
    }
}
