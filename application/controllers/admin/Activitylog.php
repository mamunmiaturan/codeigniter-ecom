<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @author : Mamun Mia Turan
 * @filename : Activitylog.php
 */

class Activitylog extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('activity_logs_model');
    }

    public function index()
    {
        if (!get_permission('activity_log', 'is_view')) {
            access_denied();
        }

        $f_date_from = $this->input->get('date_from') ?: '';
        $f_date_to   = $this->input->get('date_to')   ?: '';
        $f_action    = $this->input->get('action')     ?: '';
        $f_user      = $this->input->get('user_name')  ?: '';

        $logs    = [];
        $log_dir = APPPATH . 'logs/activity/';
        if (is_dir($log_dir)) {
            $files = glob($log_dir . '*.json');
            rsort($files);
            foreach ($files as $file) {
                $lines = file($file);
                foreach ($lines as $line) {
                    $log_data = json_decode($line, true);
                    if ($log_data) {
                        $logs[] = $log_data;
                    }
                }
            }
        }

        usort($logs, function ($a, $b) {
            return strtotime($b['created_at']) <=> strtotime($a['created_at']);
        });

        $logs = audit_filter_activity_logs($logs);

        // Build unique user list before filtering
        $all_users = array_values(array_unique(array_filter(array_column($logs, 'user_name'))));
        sort($all_users);

        // Apply GET filters
        if ($f_date_from) {
            $logs = array_filter($logs, function ($r) use ($f_date_from) {
                return date('Y-m-d', strtotime($r['created_at'])) >= $f_date_from;
            });
        }
        if ($f_date_to) {
            $logs = array_filter($logs, function ($r) use ($f_date_to) {
                return date('Y-m-d', strtotime($r['created_at'])) <= $f_date_to;
            });
        }
        if ($f_action) {
            $logs = array_filter($logs, function ($r) use ($f_action) {
                return strtolower($r['action'] ?? '') === strtolower($f_action);
            });
        }
        if ($f_user) {
            $logs = array_filter($logs, function ($r) use ($f_user) {
                return ($r['user_name'] ?? '') === $f_user;
            });
        }

        $this->data['filters']   = compact('f_date_from', 'f_date_to', 'f_action', 'f_user');
        $this->data['all_users'] = $all_users;
        $this->data['logs']      = array_slice(array_values($logs), 0, 500);
        $this->data['title']     = translate('activity_logs');
        $this->data['sub_page']  = 'audit/activity/index';
        $this->data['main_menu'] = 'audit';
        $this->load->view('layout/index', $this->data);
    }

    public function clear()
    {
        if (!is_superadmin_loggedin()) {
            access_denied();
        }

        $log_dir = APPPATH . 'logs/activity/';
        if (is_dir($log_dir)) {
            foreach (glob($log_dir . '*.json') as $file) {
                unlink($file);
            }
        }

        set_alert('success', translate('information_has_been_deleted_successfully'));
        redirect(base_url('activity-logs'));
    }
}
