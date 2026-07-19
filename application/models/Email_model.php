<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Email_model.php
 */

class Email_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function sentRegisteredAccount($data)
    {
        $emailTemplate = $this->app_lib->get_table('email_templates', 1, true);
        if ($emailTemplate['notified'] == 1) {
            $role_name = get_type_name_by_id('roles', $data['user_role']);
            $message = $emailTemplate['template_body'];
            $message = str_replace("{institute_name}", get_global_setting('institute_name'), $message);
            $message = str_replace("{name}", $data['name'], $message);
            $message = str_replace("{username}", $data['username'], $message);
            $message = str_replace("{password}", $data['password'], $message);
            $message = str_replace("{user_role}", $role_name, $message);
            $message = str_replace("{login_url}", base_url(), $message);
            // No per-branch SMTP in this storefront build: send via the global
            // email_config (resolve_email_config() falls back to it when branch is null).
            $this->sendMail($data['email'], $emailTemplate['subject'], $message);
        }
    }

    public function sendMail($recipient, $subject, $message, ?int $branch_id = null)
    {
        $emailsetting = $this->resolve_email_config($branch_id);
        if (!$emailsetting) {
            log_message('error', 'Email send failed: no email configuration.');
            return false;
        }

        $email_protocol = getenv('EMAIL_PROTOCOL') ?: $emailsetting->email_protocol;

        $config = [
            'protocol' => $email_protocol,
            'mailtype' => 'html',
            'newline'  => "\r\n",
            'charset'  => 'utf-8',
        ];

        if ($email_protocol == 'smtp') {
            $config['smtp_host']   = getenv('SMTP_HOST')   ?: $emailsetting->smtp_host;
            $config['smtp_port']   = getenv('SMTP_PORT')   ?: $emailsetting->smtp_port;
            $config['smtp_user']   = getenv('SMTP_USER')   ?: $emailsetting->smtp_user;
            $config['smtp_pass']   = getenv('SMTP_PASS')   ?: $emailsetting->smtp_pass;
            $config['smtp_crypto'] = getenv('SMTP_CRYPTO') ?: $emailsetting->smtp_encryption;
        }

        $this->load->library('email');
        $this->email->initialize($config);
        $this->email->clear(true);

        $from_email = getenv('SMTP_FROM_EMAIL') ?: $emailsetting->email;
        $from_name  = !empty($emailsetting->sender_name)
            ? $emailsetting->sender_name
            : (get_global_setting('institute_name') ?: get_global_setting('site_name'));
        $this->email->from($from_email, $from_name);
        $this->email->to($recipient);
        $this->email->subject($subject);
        $this->email->message($message);

        $status    = $this->email->send();
        $error_msg = $status ? '' : $this->email->print_debugger(['headers']);

        if (!empty($error_msg)) {
            if (!empty($config['smtp_pass'])) {
                $error_msg = str_ireplace($config['smtp_pass'], '[REDACTED]', $error_msg);
            }
            if (!empty($config['smtp_user'])) {
                $error_msg = str_ireplace($config['smtp_user'], '[REDACTED]', $error_msg);
            }
        }

        $this->log_communication([
            'type'      => 'email',
            'recipient' => $recipient,
            'subject'   => $subject,
            'status'    => $status ? 'success' : 'failed',
            'error'     => $error_msg,
            'branch_id' => $branch_id,
            'time'      => date('Y-m-d H:i:s')
        ]);

        return $status;
    }

    /**
     * Branch SMTP when enabled; otherwise global email_config (id=1).
     */
    protected function resolve_email_config(?int $branch_id = null)
    {
        if ($branch_id) {
            $this->load->model('branch_messaging_model');
            $branch_cfg = $this->branch_messaging_model->get_email_config((int) $branch_id);
            if ($branch_cfg
                && (int) ($branch_cfg['is_enabled'] ?? 0) === 1
                && ($branch_cfg['status'] ?? 'Active') === 'Active'
                && !empty($branch_cfg['email'])
            ) {
                return (object) $branch_cfg;
            }
        }

        return $this->db->get_where('email_config', ['id' => 1])->row();
    }

    private function log_communication($data)
    {
        $log_dir = APPPATH . 'logs/email/';
        if (!is_dir($log_dir)) {
            if (!mkdir($log_dir, 0755, true)) {
                log_message('error', 'Failed to create email log directory.');
                return false;
            }
        }
        $file_name = $log_dir . date('Y-m-d') . '.json';
        if (file_put_contents($file_name, json_encode($data) . PHP_EOL, FILE_APPEND) === false) {
            log_message('error', 'Failed to write to email log file.');
            return false;
        }
        return true;
    }

    /**
     * Self-healing: Check and add column dynamically
     */
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
     * Self-healing: Rename database column dynamically
     */
    public function rename_column($table, $old_name, $new_name, $definition)
    {
        if ($this->db->field_exists($old_name, $table) && !$this->db->field_exists($new_name, $table)) {
            $this->load->dbforge();
            $this->dbforge->modify_column($table, [
                $old_name => array_merge(['name' => $new_name], $definition)
            ]);
            return true;
        }
        return false;
    }

    /**
     * Get email config record count
     */
    public function get_email_config_count()
    {
        $this->db->where('id', 1);
        return $this->db->get('email_config')->num_rows();
    }

    /**
     * Insert new email config settings
     */
    public function insert_email_config($data)
    {
        return $this->db->insert('email_config', $data);
    }

    /**
     * Update existing email config settings
     */
    public function update_email_config($data)
    {
        $this->db->where('id', 1);
        return $this->db->update('email_config', $data);
    }

    /**
     * Update email template subject/body/notify
     */
    public function update_email_template($template_id, $data)
    {
        $this->db->where('id', $template_id);
        return $this->db->update('email_templates', $data);
    }
}
