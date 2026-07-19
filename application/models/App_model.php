<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : App_model.php
 */

class App_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get_users_list_by_role_exclude($excluded_roles)
    {
        $this->db->select('users.id,users.name,users.user_id,login_credential.role');
        $this->db->from('users');
        $this->db->join('login_credential', 'login_credential.user_id = users.id AND login_credential.role != 4', 'inner');
        $this->db->where_not_in('login_credential.role', $excluded_roles);
        $this->db->order_by('users.id', 'ASC');
        return $this->db->get()->result();
    }

    public function get_users_list_by_role($role_id = null)
    {
        $this->db->select('users.id, users.name');
        $this->db->from('users');
        if ($role_id) {
            $this->db->join('login_credential', 'login_credential.user_id = users.id', 'inner');
            $this->db->where('login_credential.role', $role_id);
        }
        return $this->db->get()->result();
    }

    public function get_table_records($table, $where = [])
    {
        if (!empty($where)) {
            $this->db->where($where);
        }
        return $this->db->get($table)->result();
    }

    public function get_table_records_ordered($table, $id = NULL, $single = FALSE)
    {
        if ($single == TRUE) {
            $method = 'row_array';
        } else {
            $this->db->order_by('id', 'ASC');
            $method = 'result_array';
        }
        if ($id != NULL) {
            $this->db->where('id', $id);
        }
        return $this->db->get($table)->$method();
    }

    public function get_roles_excluding($exclude_ids)
    {
        $this->db->where_not_in('id', $exclude_ids);
        return $this->db->get('roles')->result();
    }

    public function get_roles_greater_than($role_id)
    {
        $this->db->where('id >', $role_id);
        return $this->db->get('roles')->result();
    }

    public function get_credential_by_user($user_id, $exclude_role_7 = true)
    {
        $this->db->select('id');
        if ($exclude_role_7) {
            $this->db->where_not_in('role', 7);
        }
        $this->db->where('user_id', $user_id);
        return $this->db->get('login_credential')->row_array();
    }
}
