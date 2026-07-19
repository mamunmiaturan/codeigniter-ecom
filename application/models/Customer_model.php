<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Customer
 * @author   : Mamun Mia Turan
 * @filename : Customer_model.php
 *
 * Storefront customers reuse the existing users + login_credential tables with
 * role = ROLE_CUSTOMER_ID. This model handles API registration and profile
 * without the admin-panel coupling of User_model::save() (no file upload, no
 * session-derived created_by).
 */
class Customer_model extends MY_Model
{
    protected $table = 'users';

    public function email_exists($email)
    {
        return $this->db
            ->where('email', $email)
            ->where('deleted_at', null)
            ->count_all_results('login_credential') > 0;
    }

    /**
     * Create a customer (users + login_credential) in one transaction.
     * @return array|false ['user_id' => int, 'credential_id' => int]
     */
    public function register($data)
    {
        $role_id = defined('ROLE_CUSTOMER_ID') ? ROLE_CUSTOMER_ID : 6;
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();

        $this->db->insert('users', [
            'user_id'    => substr(app_generate_hash(), 0, 10),
            'name'       => $data['name'],
            'email'      => $data['email'],
            'mobile_no'  => $data['phone'] ?? '',
            'created_at' => $now,
        ]);
        $user_id = (int) $this->db->insert_id();

        // Assign the default customer group when the groups feature is installed.
        if ($this->db->field_exists('customer_group_id', 'users')) {
            $g = $this->db->select('id')->where('is_default', 1)->where('deleted_at', null)->get('customer_groups')->row();
            if ($g) {
                $this->db->where('id', $user_id)->update('users', ['customer_group_id' => (int) $g->id]);
            }
        }

        $this->db->insert('login_credential', [
            'user_id'    => $user_id,
            'email'      => $data['email'],
            'password'   => $data['password_hash'],
            'role'       => $role_id,
            'status'     => 'Active',
            'created_at' => $now,
        ]);
        $credential_id = (int) $this->db->insert_id();

        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            return false;
        }
        return ['user_id' => $user_id, 'credential_id' => $credential_id];
    }

    /**
     * Fetch a customer login row by email (role-scoped to customers) for the
     * storefront web login. Returns credential + name, or null.
     */
    public function get_login_by_email($email)
    {
        $role_id = defined('ROLE_CUSTOMER_ID') ? ROLE_CUSTOMER_ID : 6;
        $this->db->select('lc.id AS credential_id, lc.user_id, lc.email, lc.password, lc.role, lc.status, u.name');
        $this->db->from('login_credential lc');
        $this->db->join('users u', 'u.id = lc.user_id', 'left');
        $this->db->where('lc.email', $email);
        $this->db->where('lc.role', $role_id);
        $this->db->where('lc.deleted_at', null);
        return $this->db->get()->row_array();
    }

    public function touch_last_login($credential_id)
    {
        $this->db->where('id', (int) $credential_id)->update('login_credential', ['last_login' => date('Y-m-d H:i:s')]);
    }

    // ---- email verification ----

    /** Generate + store a fresh verification token (no-op if columns not migrated). */
    public function create_verify_token($user_id)
    {
        if (!$this->db->field_exists('email_verify_token', 'users')) {
            return null;
        }
        $token = bin2hex(random_bytes(24));
        $this->db->where('id', (int) $user_id)->update('users', ['email_verified' => 0, 'email_verify_token' => $token]);
        return $token;
    }

    /** Verify a user by token; returns the user row on success, false otherwise. */
    public function verify_by_token($token)
    {
        if (empty($token) || !$this->db->field_exists('email_verify_token', 'users')) {
            return false;
        }
        $u = $this->db->select('id, name, email')
            ->where('email_verify_token', $token)
            ->where('deleted_at', null)
            ->get('users')->row_array();
        if (!$u) {
            return false;
        }
        $this->db->where('id', (int) $u['id'])->update('users', ['email_verified' => 1, 'email_verify_token' => null]);
        return $u;
    }

    public function is_verified($user_id)
    {
        if (!$this->db->field_exists('email_verified', 'users')) {
            return true; // feature not migrated — treat everyone as verified
        }
        $u = $this->db->select('email_verified')->where('id', (int) $user_id)->get('users')->row();
        return $u ? (int) $u->email_verified === 1 : false;
    }

    public function get_profile($user_id)
    {
        $this->db->select('u.id, u.user_id AS code, u.name, u.email, u.mobile_no, u.gender, u.dob, u.address, lc.status, lc.last_login');
        $this->db->from('users u');
        $this->db->join('login_credential lc', 'lc.user_id = u.id', 'left');
        $this->db->where('u.id', (int) $user_id);
        $this->db->where('u.deleted_at', null);
        return $this->db->get()->row_array();
    }

    /**
     * Update profile fields (whitelisted). Email/role/status are immutable here.
     */
    public function update_profile($user_id, $data)
    {
        $allowed = ['name', 'mobile_no', 'gender', 'dob', 'address'];
        $update = array_intersect_key($data, array_flip($allowed));
        if (empty($update)) {
            return true;
        }
        $update['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', (int) $user_id)->update('users', $update);
        return true;
    }
}
