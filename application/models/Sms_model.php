<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Sms_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        // Self-healing: Ensure mobile_no column exists in sms_logs table
        $this->ensure_column_exists('sms_logs', 'mobile_no', [
            'type' => 'VARCHAR',
            'constraint' => 20,
            'null' => TRUE,
            'default' => NULL
        ]);
    }

    // Save SMS log into the database
    public function save_sms_log($data)
    {
        $creator_id = get_loggedin_user_id() ?: null;

        // Redact plaintext passwords from SMS body logs
        $sms_text = $data['sms_text'];
        $sms_text = preg_replace('/(password\s*[:=]\s*)\S+/i', '$1[REDACTED]', $sms_text);

        $insert_data = array(
            'user_id' => $data['user_id'],
            'mobile_no' => $data['mobile_no'] ?? null,
            'sms_text' => $sms_text,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => $data['status'] ?? 'Pending',
            'created_by' => $creator_id,
            'remarks' => $data['remarks'] ?? 'Web'
        );

        $this->db->insert('sms_logs', $insert_data);
        return $this->db->insert_id();
    }

    // Get SMS logs joined with User data
    public function get_sms_logs($user_id = null)
    {
        $this->db->select('sms_logs.*, users.name as user_name, COALESCE(sms_logs.mobile_no, users.mobile_no) as mobile_no, creator.name as creator_name');
        $this->db->from('sms_logs');
        $this->_join_sms_log_users_for_scope();
        $this->_apply_audit_log_branch_scope_to_sms_query();
        if ($user_id) {
            $this->db->where('sms_logs.user_id', $user_id);
        }
        $this->db->order_by('sms_logs.id', 'DESC');
        return $this->db->get()->result_array();
    }

    /**
     * Get active users
     */
    public function get_active_users()
    {
        // `status` lives on login_credential, not users.
        return $this->db->select('users.id, users.name, users.mobile_no, users.user_id')
            ->join('login_credential', 'login_credential.user_id = users.id', 'inner')
            ->where('login_credential.status', 'Active')
            ->get('users')
            ->result_array();
    }

    /**
     * Get total sms logs count
     */
    public function get_sms_logs_count($date_from = null, $date_to = null, $status = null)
    {
        $this->db->from('sms_logs');
        $this->_join_sms_log_users_for_scope();
        $this->_apply_audit_log_branch_scope_to_sms_query();
        if ($date_from) $this->db->where('DATE(sms_logs.created_at) >=', $date_from);
        if ($date_to)   $this->db->where('DATE(sms_logs.created_at) <=', $date_to);
        if ($status)    $this->db->where('sms_logs.status', $status);
        return $this->db->count_all_results();
    }

    /**
     * Get sms logs server side data with search, pagination, and sorting
     */
    public function get_sms_logs_server_side_data($search, $start, $length, $order_col, $order_dir, $date_from = null, $date_to = null, $status = null)
    {
        $this->db->select('sms_logs.*, users.name as user_name, COALESCE(sms_logs.mobile_no, users.mobile_no) as mobile_no, creator.name as creator_name');
        $this->db->from('sms_logs');
        $this->_join_sms_log_users_for_scope();
        $this->_apply_audit_log_branch_scope_to_sms_query();

        if ($date_from) {
            $this->db->where('DATE(sms_logs.created_at) >=', $date_from);
        }
        if ($date_to) {
            $this->db->where('DATE(sms_logs.created_at) <=', $date_to);
        }
        if ($status) {
            $this->db->where('sms_logs.status', $status);
        }

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('users.name', $search);
            $this->db->or_like('users.mobile_no', $search);
            $this->db->or_like('sms_logs.mobile_no', $search);
            $this->db->or_like('sms_logs.sms_text', $search);
            $this->db->or_like('sms_logs.status', $search);
            $this->db->or_like('creator.name', $search);
            $this->db->group_end();
        }

        // Count before order/limit; do not clone $this->db (breaks CI3 WHERE clauses).
        $total_filtered = (int) $this->db->count_all_results('', false);

        $allowed_cols = ['sms_logs.id', 'users.name', 'users.mobile_no', 'sms_logs.mobile_no', 'sms_logs.sms_text', 'sms_logs.status', 'sms_logs.created_at', 'creator.name'];
        if (!in_array($order_col, $allowed_cols)) {
            $order_col = 'sms_logs.id';
        }
        if (!in_array(strtolower($order_dir), ['asc', 'desc'])) {
            $order_dir = 'desc';
        }
        $this->db->order_by($order_col, $order_dir);

        if ($length != -1) {
            $this->db->limit($length, $start);
        }

        $query = $this->db->get();
        return [
            'data' => $query->result_array(),
            'total_filtered' => $total_filtered
        ];
    }

    /**
     * Self-healing: Check and add column dynamically
     */
    /**
     * Joins required when branch scope references users.branch_id / creator.branch_id.
     */
    protected function _join_sms_log_users_for_scope(): void
    {
        $this->db->join('users', 'users.id = sms_logs.user_id', 'left');
        $this->db->join('users as creator', 'creator.id = sms_logs.created_by', 'left');
    }

    /**
     * Branch managers: SMS to/from their branch staff & customers only.
     */
    protected function _apply_audit_log_branch_scope_to_sms_query(): void
    {
        if (!function_exists('audit_logs_view_unrestricted') || audit_logs_view_unrestricted()) {
            return;
        }

        $scope = audit_logs_branch_scope();
        if ($scope === null) {
            return;
        }

        $branch_id = (int) ($scope['branch_id'] ?? 0);
        $user_ids  = $scope['user_ids'] ?? [];
        if ($branch_id < 1 || $user_ids === []) {
            $this->db->where('1 = 0', null, false);
            return;
        }

        $this->db->group_start();
        $this->db->where('users.branch_id', $branch_id);
        $this->db->or_where('creator.branch_id', $branch_id);
        $this->db->or_where_in('sms_logs.created_by', $user_ids);
        $this->db->or_where_in('sms_logs.user_id', $user_ids);
        $this->db->group_end();
    }

    public function ensure_column_exists($table, $column, $definition)
    {
        if (!$this->db->field_exists($column, $table)) {
            $this->load->dbforge();
            $this->dbforge->add_column($table, [$column => $definition]);
            return true;
        }
        return false;
    }

    /**
     * Get gateway config by gateway name
     */
    public function get_sms_config($gateway_name)
    {
        return $this->db->get_where('sms_config', array('gateway_name' => $gateway_name))->row();
    }

    /**
     * Insert new sms config entry
     */
    public function insert_sms_config($data)
    {
        return $this->db->insert('sms_config', $data);
    }

    /**
     * Update active sms gateway status
     */
    public function update_active_gateway($selected_gateway)
    {
        $this->db->update('sms_config', array('is_active' => 0));
        if ($selected_gateway !== 'disabled') {
            $this->db->where('gateway_name', $selected_gateway);
            return $this->db->update('sms_config', array('is_active' => 1));
        }
        return true;
    }

    /**
     * Update gateway credentials json
     */
    public function update_gateway_credentials($gateway_name, $credentials)
    {
        $this->db->where('gateway_name', $gateway_name);
        return $this->db->update('sms_config', array('credentials' => json_encode($credentials)));
    }

    /**
     * Update sms template body/subject/notify
     */
    public function update_sms_template($template_id, $data)
    {
        $this->db->where('id', $template_id);
        return $this->db->update('sms_templates', $data);
    }

    /**
     * Send SMS using branch gateway (when enabled) or global gateway.
     */
    public function send($mobile, $message, $user_id = null, $remarks = 'System', ?int $branch_id = null)
    {
        // No per-branch SMS gateways in this storefront build: a null branch makes
        // resolve_sms_gateway() fall back to the global gateway.
        $active_gateway_row = $this->resolve_sms_gateway($branch_id);
        if (!$active_gateway_row) {
            log_message('error', 'SMS Sending failed: No active gateway configured.');
            return false;
        }

        return $this->dispatch_sms($mobile, $message, $active_gateway_row, $user_id, $remarks, $branch_id);
    }

    /**
     * @return object|null Row with gateway_name + credentials (json string)
     */
    public function resolve_sms_gateway(?int $branch_id = null)
    {
        if ($branch_id) {
            $this->load->model('branch_messaging_model');
            $branch_row = $this->branch_messaging_model->get_sms_config((int) $branch_id);
            if ($branch_row
                && (int) ($branch_row->is_enabled ?? 0) === 1
                && !empty($branch_row->credentials)
            ) {
                return $branch_row;
            }
        }

        return $this->db->get_where('sms_config', ['is_active' => 1])->row();
    }

    protected function dispatch_sms($mobile, $message, $active_gateway_row, $user_id, $remarks, ?int $branch_id)
    {
        $gateway_name = $active_gateway_row->gateway_name;
        $credentials = json_decode($active_gateway_row->credentials, true);
        $status = false;
        $error_msg = '';

        if ($gateway_name === 'custom_sms') {
            $endpoint = $credentials['endpoint'] ?? '';
            $method = strtoupper($credentials['method'] ?? 'GET');
            $mobile_prefix = $credentials['mobile_prefix'] ?? '';
            $mobile_key = $credentials['mobile_key'] ?? 'MobileNumbers';
            $message_key = $credentials['message_key'] ?? 'Message';
            $headers = $credentials['headers'] ?? [];
            $params = $credentials['params'] ?? [];

            if (empty($endpoint)) {
                log_message('error', 'Custom SMS failed: Endpoint missing.');
                return false;
            }

            // Prefix formatting if necessary
            $formatted_mobile = $mobile;
            if (!empty($mobile_prefix)) {
                $prefix_clean = ltrim($mobile_prefix, '+');
                $mobile_clean = ltrim($mobile, '+');
                if (strpos($mobile_clean, $prefix_clean) !== 0) {
                    $formatted_mobile = $prefix_clean . $mobile_clean;
                } else {
                    $formatted_mobile = $mobile_clean;
                }
                if (strpos($mobile_prefix, '+') === 0) {
                    $formatted_mobile = '+' . $formatted_mobile;
                }
            }

            // Setup parameters
            $params[$mobile_key] = $formatted_mobile;
            $params[$message_key] = $message;

            // Setup headers
            $curl_headers = [];
            foreach ($headers as $h_key => $h_val) {
                $curl_headers[] = "{$h_key}: {$h_val}";
            }

            $ch = curl_init();
            
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_URL, $endpoint);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            } else {
                $query_string = http_build_query($params);
                $separator = (strpos($endpoint, '?') === false) ? '?' : '&';
                curl_setopt($ch, CURLOPT_URL, $endpoint . $separator . $query_string);
            }

            if (!empty($curl_headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_err = curl_error($ch);
            curl_close($ch);

            if ($http_code >= 200 && $http_code < 300) {
                $status = true;
            } else {
                $error_msg = "Custom SMS Error: HTTP Code {$http_code} - " . ($response ?: $curl_err);
            }
        }

        if (!empty($error_msg) && !empty($credentials)) {
            foreach ($credentials as $cred_val) {
                if (is_string($cred_val) && strlen($cred_val) > 3) {
                    $error_msg = str_ireplace($cred_val, '[REDACTED]', $error_msg);
                }
            }
        }

        // Log to JSON file
        $this->log_communication_log([
            'type'      => 'sms',
            'recipient' => $mobile,
            'subject'   => 'SMS Notification',
            'status'    => $status ? 'success' : 'failed',
            'error'     => $status ? '' : $error_msg,
            'branch_id' => $branch_id,
            'time'      => date('Y-m-d H:i:s')
        ]);

        // Log to DB
        $this->save_sms_log([
            'user_id'   => $user_id,
            'mobile_no' => $mobile,
            'sms_text'  => $message,
            'status'    => $status ? 'Success' : 'Failed',
            'remarks'   => $remarks,
        ]);

        return $status;
    }

    public function sentRegisteredAccount($data)
    {
        $smsTemplate = $this->app_lib->get_table('sms_templates', 1, true);
        if ($smsTemplate['notified'] == 1) {
            $message = $smsTemplate['template_body'];
            $message = str_replace('{institute_name}', get_global_setting('institute_name'), $message);
            $message = str_replace('{name}', $data['name'], $message);
            $message = str_replace('{username}', $data['username'], $message);
            $message = str_replace('{password}', $data['password'], $message);
            $message = str_replace('{user_role}', 'Client', $message);
            $message = str_replace('{login_url}', base_url(), $message);
            $recipient = $data['mobile_no'];
            $this->send($recipient, $message, (int) ($data['user_id'] ?? 0), 'Registration');
        }
    }

    private function log_communication_log($data)
    {
        $log_dir = APPPATH . 'logs/sms/';
        if (!is_dir($log_dir)) {
            if (!mkdir($log_dir, 0755, true)) {
                log_message('error', 'Failed to create sms log directory.');
                return false;
            }
        }
        $file_name = $log_dir . date('Y-m-d') . '.json';
        if (file_put_contents($file_name, json_encode($data) . PHP_EOL, FILE_APPEND) === false) {
            log_message('error', 'Failed to write to sms log file.');
            return false;
        }
        return true;
    }
}
