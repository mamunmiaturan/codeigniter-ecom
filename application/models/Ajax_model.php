<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Ajax_model.php
 */

class Ajax_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Update user's last active timestamp
     */
    public function update_last_active($loggedin_id)
    {
        $this->db->where('id', $loggedin_id);
        return $this->db->update('login_credential', ['last_active' => date('Y-m-d H:i:s')]);
    }

    /**
     * Get active users from database matching criteria
     */
    public function get_active_users($redis_enabled, $redis_active_ids, $loggedin_id, $threshold, $loggedin_role, $loggedin_user_id)
    {
        $this->db->select('login_credential.id as cred_id, login_credential.role, users.name, users.photo, roles.name as role_name');
        $this->db->from('login_credential');
        $this->db->join('users', 'users.id = login_credential.user_id', 'inner');
        $this->db->join('roles', 'roles.id = login_credential.role', 'inner');

        if ($redis_enabled && !empty($redis_active_ids)) {
            $this->db->where_in('login_credential.id', $redis_active_ids);
        } else if ($redis_enabled) {
            $this->db->where('1 = 0', null, false);
        } else {
            $this->db->where('login_credential.last_active >=', $threshold);
            $this->db->where('login_credential.id !=', $loggedin_id);
        }

        if ($loggedin_role == ROLE_SUPERMAN_ID) {
            // Superman: see all
        } else if (in_array((int) $loggedin_role, [ROLE_SUPERADMIN_ID, ROLE_ADMIN_ID], true)) {
            // Manager: only roles strictly below own authority level (level-based).
            // Hides Superman (level 0) and, for Admin, Superadmin too.
            $this->db->where('roles.level >', role_level((int) $loggedin_role));
        } else {
            // Regular user: only see their creator
            $creator_user_id = $this->db->select('created_by')->where('id', $loggedin_user_id)->get('users')->row('created_by');
            if (!empty($creator_user_id)) {
                $this->db->where('login_credential.user_id', $creator_user_id);
            } else {
                $this->db->where('1 = 0', null, false);
            }
        }

        $this->db->group_by('login_credential.id');
        return $this->db->get()->result_array();
    }

    /**
     * Get recipient last active timestamp
     */
    public function get_recipient_last_active($receiver_id)
    {
        return $this->db->select('last_active')->where('id', $receiver_id)->get('login_credential')->row();
    }
}
