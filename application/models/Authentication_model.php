<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Authentication_model.php
 */
class Authentication_model extends MY_Model
{
    // checking login credential
    public function login_credential($email, $password)
    {
        $ip = $this->input->ip_address();

        // 1. Check if IP is blocked
        if ($this->is_blocked($ip)) {
            return 'blocked';
        }

        $this->db->select('*');
        $this->db->from('login_credential');
        $this->db->where('email', $email);
        $this->db->where('deleted_at', NULL);
        $this->db->limit(1);
        $query = $this->db->get();

        if ($query->num_rows() == 1) {
            $row = $query->row();
            $verify_password = $this->app_lib->verify_password($password, $row->password);
            if ($verify_password) {
                // Clear attempts on success
                $this->clear_login_attempts($ip, $email);
                return $row;
            }
        }

        // 2. Record failed attempt
        $this->record_login_attempt($ip, $email);
        return false;
    }

    /**
     * Check if an IP address is temporarily blocked
     */
    public function is_blocked($ip)
    {
        $this->load->library('redis_lib');
        if ($this->redis_lib->is_enabled()) {
            $attempts = intval($this->redis_lib->get('login_attempts:' . $ip) ?? 0);
            return ($attempts >= 5);
        }

        $limit = 5; // Max 5 attempts
        $time_window = time() - (15 * 60); // Within last 15 minutes

        $this->db->where('ip_address', $ip);
        $this->db->where('timestamp >', $time_window);
        $attempts = $this->db->count_all_results('login_attempts');

        return ($attempts >= $limit);
    }

    /**
     * Record a failed login attempt
     */
    public function record_login_attempt($ip, $email)
    {
        $this->load->library('redis_lib');
        if ($this->redis_lib->is_enabled()) {
            $this->redis_lib->incr('login_attempts:' . $ip, 900); // 15 mins block window
        }

        $this->db->insert('login_attempts', [
            'ip_address' => $ip,
            'email' => $email,
            'timestamp' => time()
        ]);
    }

    /**
     * Clear login attempts for an IP/Username after successful login
     */
    public function clear_login_attempts($ip, $email)
    {
        $this->load->library('redis_lib');
        if ($this->redis_lib->is_enabled()) {
            $this->redis_lib->delete('login_attempts:' . $ip);
        }

        $this->db->where('ip_address', $ip);
        $this->db->delete('login_attempts');
    }
    public function get_user_login($user_id)
    {
        $this->db->select('*');
        $this->db->from('login_credential');
        $this->db->where('user_id', $user_id);
        $this->db->where('deleted_at', NULL);
        $this->db->limit(1);
        $query = $this->db->get();

        if ($query->num_rows() == 1) {
            return $query->row();
        }
        return false;
    }
    // password forgotten
    public function lose_password($email)
    {
        if (!empty($email)) {
            $this->db->select('*');
            $this->db->from('login_credential');
            $this->db->where('email', $email);
            $this->db->limit(1);
            $query = $this->db->get();

            if ($query->num_rows() > 0) {
                $login_credential = $query->row();

                $getUser = $this->get_list('users', array('id' => $login_credential->user_id), true, 'name,email');

                $key = hash('sha512', $login_credential->role . $login_credential->email . app_generate_hash());

                // Remove previous reset requests
                $this->db->where('login_credential_id', $login_credential->id);
                $this->db->delete('reset_password');

                // Insert new reset request using correct migration columns (token, expires_at, is_used)
                $arrayReset = array(
                    'token' => $key,
                    'login_credential_id' => $login_credential->id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'expires_at' => date('Y-m-d H:i:s', time() + 3600), // expires in 1 hour
                    'is_used' => 0
                );
                $this->db->insert('reset_password', $arrayReset);

                // Build email content
                $resetUrl = base_url('authentication/password-reset?key=' . $key);
                $subject = 'Password Reset Request';
                $message = "
                Dear {$getUser['name']},<br><br>
                We received a request to reset your password.<br><br>
                Please click the link below to set a new password:<br><br>
                <a href='{$resetUrl}' target='_blank'>{$resetUrl}</a><br><br>
                If you did not request this, please ignore this email.<br><br>
                Best regards,<br>
                " . get_global_setting('site_name');

                // Push email to background job queue to prevent synchronous HTTP request blocking
                $this->load->library('queue');
                $this->queue->push('queue/send_email', array(
                    'email' => $getUser['email'],
                    'subject' => $subject,
                    'message' => $message
                ));
                return true;
            }
        }
        return false;
    }

    /**
     * Get user profile details
     */
    public function get_user_profile($user_id)
    {
        $this->load->dbforge();
        if (!$this->db->field_exists('language', 'users')) {
            $this->dbforge->add_column('users', [
                'language' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => TRUE, 'default' => NULL]
            ]);
        }
        return $this->db->select('name, photo, user_id as uniqueid, language')
            ->where('id', $user_id)
            ->get('users')
            ->row();
    }

    /**
     * Update last login timestamp
     */
    public function update_last_login($login_id)
    {
        $this->db->where('id', $login_id);
        return $this->db->update('login_credential', ['last_login' => date('Y-m-d H:i:s')]);
    }

    /**
     * Insert impersonation hijack security alert to activity log
     */
    public function log_security_alert($user_id, $payload)
    {
        return $this->db->insert('activity_logs', [
            'user_id' => $user_id,
            'table_name' => 'security_alert',
            'row_id' => 0,
            'action' => 'impersonation_hijack_attempt',
            'payload' => $payload,
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent()
        ]);
    }

    /**
     * Get login credential by email address
     */
    public function get_login_by_email($email)
    {
        return $this->db->get_where('login_credential', array('email' => $email))->row();
    }

    /**
     * Get password reset entry by token
     */
    public function get_reset_token($key)
    {
        return $this->db->get_where('reset_password', array('token' => $key, 'is_used' => 0))->row_array();
    }

    /**
     * Get login credential by login ID
     */
    public function get_login_by_id($id)
    {
        return $this->db->get_where('login_credential', array('id' => $id))->row();
    }

    /**
     * Delete expired/invalid reset token
     */
    public function delete_reset_token($key)
    {
        $this->db->where('token', $key);
        return $this->db->delete('reset_password');
    }

    /**
     * Update user login password
     */
    public function update_password($login_id, $password_hash)
    {
        $this->db->where('id', $login_id);
        return $this->db->update('login_credential', array('password' => $password_hash));
    }

    /**
     * Mark reset token as used
     */
    public function mark_token_used($login_id)
    {
        $this->db->where('login_credential_id', $login_id);
        return $this->db->update('reset_password', array('is_used' => 1));
    }
}
