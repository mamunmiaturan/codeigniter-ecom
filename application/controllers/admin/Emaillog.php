<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @author : Mamun Mia Turan
 * @filename : Emaillog.php
 */

class Emaillog extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!get_permission('email_log', 'is_view')) {
            access_denied();
        }
    }

    public function index()
    {
        $date_from = trim($this->input->get('date_from') ?: '');
        $date_to   = trim($this->input->get('date_to')   ?: '');
        $status    = trim($this->input->get('status')    ?: '');

        $logs = [];
        $log_dir = APPPATH . 'logs/email/';
        if (is_dir($log_dir)) {
            $files = glob($log_dir . '*.json');
            rsort($files);
            foreach ($files as $file) {
                // Skip files outside date range early (filename is YYYY-MM-DD.json)
                $file_date = basename($file, '.json');
                if ($date_from && $file_date < $date_from) continue;
                if ($date_to   && $file_date > $date_to)   continue;

                foreach (file($file) as $line) {
                    $log_data = json_decode(trim($line), true);
                    if ($log_data && isset($log_data['type']) && $log_data['type'] === 'email') {
                        $logs[] = $log_data;
                    }
                }
            }
        }

        usort($logs, fn($a, $b) => strtotime($b['time']) <=> strtotime($a['time']));

        $logs = audit_filter_email_logs($logs);

        // Apply date_from / date_to on the time field (precise filtering)
        if ($date_from) {
            $logs = array_values(array_filter($logs, fn($l) => substr($l['time'] ?? '', 0, 10) >= $date_from));
        }
        if ($date_to) {
            $logs = array_values(array_filter($logs, fn($l) => substr($l['time'] ?? '', 0, 10) <= $date_to));
        }
        if ($status) {
            $logs = array_values(array_filter($logs, fn($l) => strcasecmp($l['status'] ?? '', $status) === 0));
        }

        $this->data['logs']      = array_slice($logs, 0, 500);
        $this->data['date_from'] = $date_from;
        $this->data['date_to']   = $date_to;
        $this->data['status']    = $status;
        $this->data['title']     = translate('Email Logs');
        $this->data['sub_page']  = 'audit/email/log/index';
        $this->data['main_menu'] = 'email_log';
        $this->load->view('layout/index', $this->data);
    }

    public function clear()
    {
        if (!get_permission('email_log', 'is_delete') || !audit_logs_view_unrestricted()) {
            access_denied();
        }
        $log_dir = APPPATH . 'logs/email/';
        if (is_dir($log_dir)) {
            $files = glob($log_dir . '*.json');
            foreach ($files as $file) {
                $lines = file($file);
                $remaining = [];
                foreach ($lines as $line) {
                    $data = json_decode(trim($line), true);
                    if ($data && isset($data['type']) && $data['type'] !== 'email') {
                        $remaining[] = trim($line);
                    }
                }
                if (empty($remaining)) {
                    unlink($file);
                } else {
                    file_put_contents($file, implode(PHP_EOL, $remaining) . PHP_EOL);
                }
            }
        }
        set_alert('success', translate('logs_cleared'));
        redirect(base_url('email-logs'));
    }
}
