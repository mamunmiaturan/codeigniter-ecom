<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : User_model.php
 */

class User_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // MODERATOR EMPLOYEE ALL INFORMATION
    public function save($data)
    {
        $this->db->trans_start();

        $gender = !empty($data["gender"]) ? ucfirst(strtolower($data["gender"])) : null;
        if ($gender !== null && !in_array($gender, ['Male', 'Female', 'Other'])) {
            $gender = null;
        }

        $marital_status = !empty($data["marital_status"]) ? ucfirst(strtolower($data["marital_status"])) : 'Single';
        if (!in_array($marital_status, ['Single', 'Married', 'Divorced', 'Widowed'])) {
            $marital_status = 'Single';
        }

        $inser_data1 = array(
            'name' => $data["name"],
            'email' => $data["email"] ?? '',
            'mobile_no' => $data["mobile_no"] ?? '',
            'dob' => null,
            'gender' => $gender,
            'blood_group' => $data["blood_group"] ?? '',
            'religion' => $data["religion"] ?? '',
            'marital_status' => $marital_status,
            'educational_qualification' => $data["educational_qualification"] ?? '',
            'nid_no' => $data["nid_no"] ?? '',
            'nationality' => $data["nationality"] ?? '',
            'photo' => $this->app_lib->upload_image('users'),
            'address' => $data["address"] ?? '',
        );

        $dob = $data["dob"] ?? $data["birthday"] ?? '';
        if (!empty($dob)) {
            $inser_data1['dob'] = date("Y-m-d", strtotime($dob));
        }

        $inser_data2 = array(
            'email' => $data["email"],
        );

        if (isset($data["user_role"])) {
            $inser_data2['role'] = $data["user_role"];
        }

        if (isset($data["status"])) {
            if ($data["status"] === '1' || $data["status"] === 'Active') {
                $inser_data2['status'] = 'Active';
            } elseif ($data["status"] === '2' || $data["status"] === 'Inactive') {
                $inser_data2['status'] = 'Inactive';
            } elseif ($data["status"] === '3' || $data["status"] === 'Suspended') {
                $inser_data2['status'] = 'Suspended';
            } elseif ($data["status"] === '4' || $data["status"] === 'Blocked') {
                $inser_data2['status'] = 'Blocked';
            } else {
                $inser_data2['status'] = 'Active';
            }
        }

        if (empty($data['user_id'])) {
            $inser_data1['user_id'] = substr(app_generate_hash(), 0, 10);
            // Always derive created_by from the session — never trust client input
            // (prevents POST tampering to forge the audit trail).
            $inser_data1['created_by'] = get_loggedin_user_id() ?: null;
            // SAVE EMPLOYEE INFORMATION IN THE DATABASE
            $this->db->insert('users', $inser_data1);
            $user_id = $this->db->insert_id();

            // SAVE EMPLOYEE LOGIN CREDENTIAL INFORMATION IN THE DATABASE
            if (!isset($inser_data2['status'])) {
                $inser_data2['status'] = 'Active';
            }
            $inser_data2['user_id'] = $user_id;
            $inser_data2['password'] = $this->app_lib->pass_hashed($data["password"]);
            $this->db->insert('login_credential', $inser_data2);

            $this->db->trans_complete();
            return ($this->db->trans_status() === FALSE) ? false : $user_id;
        } else {
            // UPDATE ALL INFORMATION IN THE DATABASE
            $this->db->where('id', $data['user_id']);
            $old_user = $this->db->get('users')->row();

            $new_photo = $this->app_lib->upload_image('users');
            if (!empty($new_photo)) {
                $inser_data1['photo'] = $new_photo;
                if (!empty($old_user->photo)) {
                    $old_path = FCPATH . 'uploads/images/users/' . $old_user->photo;
                    if (file_exists($old_path)) {
                        @unlink($old_path);
                    }
                }
            }

            $inser_data1['updated_by'] = get_loggedin_user_id();

            $this->db->where('id', $data['user_id']);
            $this->db->update('users', $inser_data1);

            // UPDATE LOGIN CREDENTIAL INFORMATION IN THE DATABASE
            $this->db->where('user_id', $data['user_id']);
            $this->db->update('login_credential', $inser_data2);

            $this->db->trans_complete();
            return $this->db->trans_status();
        }
    }

    // GET user ALL DETAILS
    public function get_user_list($role_id, $active = 1)
    {
        $loggedin_role = loggedin_role_id();
        $loggedin_user_id = get_loggedin_user_id();

        $creator_user_id = null;
        if ($loggedin_role != ROLE_SUPERMAN_ID && $loggedin_role != ROLE_SUPERADMIN_ID && $loggedin_role != ROLE_ADMIN_ID) {
            $creator_user_id = $this->db->select('created_by')->where('id', $loggedin_user_id)->get('users')->row('created_by');
        }

        $this->db->select('user.*, login_credential.role as role_id, login_credential.status, (CASE WHEN login_credential.status = \'Active\' THEN 1 ELSE 0 END) as active, roles.name as role, creator.name as creator_name');
        $this->db->from('users as user');
        $this->db->join('login_credential', 'login_credential.user_id = user.id', 'inner');
        $this->db->join('roles', 'roles.id = login_credential.role', 'left');
        $this->db->join('users as creator', 'creator.id = user.created_by', 'left');
        $this->db->where('login_credential.role', $role_id);
        $this->db->where('user.deleted_at', NULL);
        $this->db->where('login_credential.deleted_at', NULL);

        if ($loggedin_role == ROLE_SUPERMAN_ID) {
            // Superman: see all
        } else if (in_array((int) $loggedin_role, [ROLE_SUPERADMIN_ID, ROLE_ADMIN_ID], true)) {
            // Manager: only roles strictly below own authority level (level-based,
            // so Admin never sees Superadmin users). Also hides Superman (level 0).
            if (role_level((int) $role_id) <= role_level((int) $loggedin_role)) {
                $this->db->where('1 = 0', null, false);
            }
        } else {
            // Regular user: only see their creator
            if (!empty($creator_user_id)) {
                $this->db->where('user.id', $creator_user_id);
            } else {
                $this->db->where('1 = 0', null, false);
            }
        }

        if ($active !== '') {
            if ($active == 1) {
                $this->db->where('login_credential.status', 'Active');
            } else {
                $this->db->where('login_credential.status !=', 'Active');
            }
        }
        $this->db->order_by('user.id', 'ASC');
        return $this->db->get()->result();
    }

    // GET SINGLE EMPLOYEE DETAILS
    public function get_single_user($id = null)
    {
        $this->db->select('user.*, login_credential.role as role_id, login_credential.status, (CASE WHEN login_credential.status = \'Active\' THEN 1 ELSE 0 END) as active, login_credential.email, roles.name as role');
        $this->db->from('users as user');
        $this->db->join('login_credential', 'login_credential.user_id = user.id', 'inner');
        $this->db->join('roles', 'roles.id = login_credential.role', 'left');
        $this->db->where('user.id', $id);
        $this->db->where('user.deleted_at', NULL);
        $this->db->where('login_credential.deleted_at', NULL);
        $query = $this->db->get();
        return $query->row_array();
    }

    public function get_all_admin()
    {
        $this->db->select('user.*, login_credential.role');
        $this->db->from('users as user');
        $this->db->join('login_credential', 'login_credential.user_id = user.id');
        $this->db->where_in('login_credential.role', [1, 2]);
        $this->db->where('user.deleted_at', NULL);
        $this->db->where('login_credential.deleted_at', NULL);
        return $this->db->get()->result_array();
    }

    /**
     * Soft delete user and credentials
     */
    public function delete_user($id, $updater_id)
    {
        $this->db->trans_start();
        $this->db->where('id', $id);
        $this->db->update('users', [
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_by' => $updater_id
        ]);
        $this->db->where('user_id', $id);
        $this->db->update('login_credential', [
            'deleted_at' => date('Y-m-d H:i:s')
        ]);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Check if email is unique
     */
    public function unique_email_check($email, $user_id = null)
    {
        if ($user_id) {
            $login_id = $this->app_lib->get_credential_id($user_id);
            if ($login_id) {
                $this->db->where('id !=', $login_id);
            }
        }
        $this->db->where('email', $email);
        return $this->db->get('login_credential')->num_rows() == 0;
    }

    /**
     * Change user password
     */
    public function change_password($user_id, $password_hash)
    {
        $this->db->where('user_id', $user_id);
        return $this->db->update('login_credential', ['password' => $password_hash]);
    }

    /**
     * Update user status
     */
    public function update_status($user_id, $status)
    {
        $this->db->where('user_id', $user_id);
        return $this->db->update('login_credential', ['status' => $status]);
    }

    /**
     * Apply role, attribute filters, and role-hierarchy constraints to the active query.
     */
    private function _apply_user_list_filters($role, $gender, $blood_group, $status, $loggedin_role, $creator_user_id)
    {
        if ($role !== '' && $role !== null && $role !== false && (int) $role > 0) {
            $this->db->where('login_credential.role', (int) $role);
        } else {
            $this->db->where('1 = 0', null, false);
            return;
        }

        if (!empty($gender)) {
            $this->db->where('user.gender', $gender);
        }
        if (!empty($blood_group)) {
            $this->db->where('user.blood_group', $blood_group);
        }
        if (!empty($status)) {
            $this->db->where('login_credential.status', $status);
        }

        $this->db->where('user.deleted_at', NULL);
        $this->db->where('login_credential.deleted_at', NULL);

        if ($loggedin_role == ROLE_SUPERMAN_ID) {
            return;
        }

        // Manager roles (Superadmin, Admin) may only view users whose role sits
        // strictly BELOW their own authority level. This is level-based so it
        // adapts to the dynamic hierarchy — an Admin can never see Superadmin
        // users even though Superadmin's id is numerically higher.
        if (in_array((int) $loggedin_role, [ROLE_SUPERADMIN_ID, ROLE_ADMIN_ID], true)) {
            if ($role !== '' && $role !== null && $role !== false
                && role_level((int) $role) <= role_level((int) $loggedin_role)) {
                $this->db->where('1 = 0', null, false);
            }
            return;
        }

        if (!empty($creator_user_id)) {
            $this->db->where('user.id', $creator_user_id);
        } else {
            $this->db->where('1 = 0', null, false);
        }
    }

    /**
     * Get user count for custom filters
     */
    public function get_users_count($role, $gender, $blood_group, $status, $loggedin_role, $loggedin_user_id)
    {
        $creator_user_id = $this->_get_creator_user_id($loggedin_role, $loggedin_user_id);

        $this->db->from('users as user');
        $this->db->join('login_credential', 'login_credential.user_id = user.id', 'inner');

        $this->_apply_user_list_filters($role, $gender, $blood_group, $status, $loggedin_role, $creator_user_id);
        return $this->db->count_all_results();
    }

    /**
     * Get users data and filtered count for server-side DataTable
     */
    public function get_users_server_side_data($role, $gender, $blood_group, $status, $loggedin_role, $loggedin_user_id, $search, $start, $length, $order_col, $order_dir)
    {
        $creator_user_id = $this->_get_creator_user_id($loggedin_role, $loggedin_user_id);

        $this->db->select('user.*, login_credential.email, login_credential.role as role_id, login_credential.status, (CASE WHEN login_credential.status = \'Active\' THEN 1 ELSE 0 END) as active, roles.name as role, creator.name as creator_name');
        $this->db->from('users as user');
        $this->db->join('login_credential', 'login_credential.user_id = user.id', 'inner');
        $this->db->join('roles', 'roles.id = login_credential.role', 'left');
        $this->db->join('users as creator', 'creator.id = user.created_by', 'left');

        $this->_apply_user_list_filters($role, $gender, $blood_group, $status, $loggedin_role, $creator_user_id);

        // Ghost Superman: hide Superman accounts from everyone except Superman.
        if ((int) $loggedin_role !== ROLE_SUPERMAN_ID) {
            $this->db->where('login_credential.role !=', ROLE_SUPERMAN_ID);
        }

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('user.name', $search);
            $this->db->or_like('user.user_id', $search);
            $this->db->or_like('login_credential.email', $search);
            $this->db->or_like('user.mobile_no', $search);
            $this->db->or_like('creator.name', $search);
            $this->db->group_end();
        }

        // Count before order/limit; do not reset query (CI3 clone breaks WHERE clauses).
        $total_filtered = (int) $this->db->count_all_results('', false);

        $allowed_cols = ['user.id', 'user.name', 'user.user_id', 'login_credential.email', 'user.mobile_no', 'creator.name'];
        if (!in_array($order_col, $allowed_cols)) {
            $order_col = 'user.id';
        }
        if (!in_array(strtolower($order_dir), ['asc', 'desc'])) {
            $order_dir = 'asc';
        }
        $this->db->order_by($order_col, $order_dir);

        if ($length != -1) {
            $this->db->limit($length, $start);
        }

        $query = $this->db->get();
        return [
            'data' => $query->result(),
            'total_filtered' => $total_filtered
        ];
    }

    /**
     * Resolve creator scope for non-admin roles without polluting the main query builder.
     */
    private function _get_creator_user_id($loggedin_role, $loggedin_user_id)
    {
        if ($loggedin_role == ROLE_SUPERMAN_ID || $loggedin_role == ROLE_SUPERADMIN_ID || $loggedin_role == ROLE_ADMIN_ID) {
            return null;
        }

        $row = $this->db->select('created_by')
            ->from('users')
            ->where('id', $loggedin_user_id)
            ->get()
            ->row();

        return $row ? $row->created_by : null;
    }

    /**
     * Get dashboard count statistics
     */
    public function get_dashboard_stats()
    {
        // Ghost Superman: exclude the Superman account from counts for everyone
        // except Superman itself.
        $hide = !is_superman_loggedin();

        // total_users
        $this->db->from('users u')->where('u.deleted_at', NULL);
        if ($hide) {
            $this->db->join('login_credential lc', 'lc.user_id = u.id', 'inner')
                     ->where('lc.role !=', ROLE_SUPERMAN_ID);
        }
        $total_users = (int) $this->db->count_all_results();

        // active_users
        $this->db->from('login_credential')->where('status', 'Active')->where('deleted_at', NULL);
        if ($hide) { $this->db->where('role !=', ROLE_SUPERMAN_ID); }
        $active_users = (int) $this->db->count_all_results();

        // inactive_users
        $this->db->from('login_credential')->where('status', 'Inactive')->where('deleted_at', NULL);
        if ($hide) { $this->db->where('role !=', ROLE_SUPERMAN_ID); }
        $inactive_users = (int) $this->db->count_all_results();

        // this_month_new_users
        $this->db->from('users u')->where('u.deleted_at', NULL)->where('u.created_at >=', date('Y-m-01 00:00:00'));
        if ($hide) {
            $this->db->join('login_credential lc', 'lc.user_id = u.id', 'inner')
                     ->where('lc.role !=', ROLE_SUPERMAN_ID);
        }
        $this_month_new_users = (int) $this->db->count_all_results();

        return [
            'total_users' => $total_users,
            'active_users' => $active_users,
            'inactive_users' => $inactive_users,
            'this_month_new_users' => $this_month_new_users,
        ];
    }

    /**
     * Get user count breakdown by role
     */
    public function get_role_distribution()
    {
        return $this->db->select('roles.name, COUNT(login_credential.id) as user_count')
            ->from('roles')
            ->join('login_credential', 'login_credential.role = roles.id AND login_credential.deleted_at IS NULL', 'left')
            ->group_by('roles.id')
            ->get()
            ->result_array();
    }

    /**
     * Update password using credential ID
     */
    public function update_password_by_credential_id($login_id, $password_hash)
    {
        $this->db->where('id', $login_id);
        return $this->db->update('login_credential', array('password' => $password_hash));
    }

    /**
     * Get password using credential ID
     */
    public function get_password_by_credential_id($login_id)
    {
        $row = $this->db->select('password')
            ->where('id', $login_id)
            ->get('login_credential')->row();
        return $row ? $row->password : '';
    }
}
