<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Cron.php
 */

class Cron extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('user_model');
    }

    public function clean_logs()
    {
        if (!is_cli()) {
            show_error('CLI only');
        }

        $now = time();
        $retention_days = 30;
        $retention_seconds = 60 * 60 * 24 * $retention_days;
        $deleted_count = 0;

        // Clean Activity Logs
        $activity_log_dir = APPPATH . 'logs/activity/';
        if (is_dir($activity_log_dir)) {
            $files = glob($activity_log_dir . '*.json');
            if ($files !== false) {
                foreach ($files as $file) {
                    if (is_file($file) && ($now - filemtime($file) >= $retention_seconds)) {
                        unlink($file);
                        $deleted_count++;
                    }
                }
            }
        }

        // Clean System Logs
        $sys_log_dir = APPPATH . 'logs/';
        if (is_dir($sys_log_dir)) {
            $files = glob($sys_log_dir . 'log-*.php');
            if ($files !== false) {
                foreach ($files as $file) {
                    if (is_file($file) && ($now - filemtime($file) >= $retention_seconds)) {
                        unlink($file);
                        $deleted_count++;
                    }
                }
            }
        }

        echo "Log rotation completed. Deleted $deleted_count old log files.\n";
    }
}
