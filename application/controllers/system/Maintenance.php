<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Maintenance Controller - CLI Tools
 */
class Maintenance extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->input->is_cli_request()) {
            exit("This controller can only be accessed via CLI." . PHP_EOL);
        }
        $this->load->model('settings_model');
    }

    /**
     * Put the system into maintenance mode
     */
    public function down()
    {
        echo "Putting system into maintenance mode... ";
        $this->settings_model->update_global_settings(['maintenance_mode' => 1]);
        update_global_settings_cache();
        
        // Create a maintenance file for the index.php firewall
        $data = [
            'secret' => bin2hex(random_bytes(16)),
            'allowed_ips' => ['127.0.0.1', '::1'],
            'message' => 'System is under maintenance.'
        ];
        file_put_contents(FCPATH . '.maintenance', json_encode($data));
        
        echo "Done. System is now DOWN." . PHP_EOL;
    }

    /**
     * Bring the system back up
     */
    public function up()
    {
        echo "Bringing system back online... ";
        $this->settings_model->update_global_settings(['maintenance_mode' => 0]);
        update_global_settings_cache();
        
        if (file_exists(FCPATH . '.maintenance')) {
            unlink(FCPATH . '.maintenance');
        }
        
        echo "Done. System is now UP." . PHP_EOL;
    }

    /**
     * Clear application logs
     */
    public function clear_logs()
    {
        echo "Clearing application logs... ";
        $log_path = APPPATH . 'logs/';
        $files = glob($log_path . '*.php');
        foreach ($files as $file) {
            if (basename($file) != 'index.html') {
                unlink($file);
            }
        }
        echo "Done. All log files removed." . PHP_EOL;
    }

    /**
     * Clear application cache
     */
    public function clear_cache()
    {
        echo "Clearing application cache... ";
        $this->load->driver('cache');
        $this->cache->clean();
        
        $cache_path = APPPATH . 'cache/';
        $files = glob($cache_path . '*');
        foreach ($files as $file) {
            if (basename($file) != 'index.html' && basename($file) != '.htaccess') {
                if (is_file($file)) unlink($file);
            }
        }
        echo "Done. Cache cleared." . PHP_EOL;
    }

    /**
     * Basic Security Scan
     */
    public function security_scan()
    {
        echo "--- Starting Security Scan ---" . PHP_EOL;
        
        // 1. Check for writable config files
        $config_files = ['application/config/config.php', 'application/config/database.php'];
        foreach ($config_files as $file) {
            if (is_writable(FCPATH . $file)) {
                echo "[WARNING] Config file is writable: $file" . PHP_EOL;
            }
        }

        // 2. Check for development environment
        if (ENVIRONMENT === 'development') {
            echo "[NOTICE] Environment is set to 'development'. Ensure this is not production." . PHP_EOL;
        }

        // 3. Check for default encryption key
        $key = $this->config->item('encryption_key');
        if ($key === '6b4d326162383563643765343239303135363738' || empty($key)) {
            echo "[CRITICAL] Default or empty encryption key detected! Please update SECURITY_KEY in .env" . PHP_EOL;
        }

        echo "--- Scan Completed ---" . PHP_EOL;
    }

    /**
     * Bump the permission-cache version and prune on-disk cache files.
     * Run this after migrations that change permission/permission_modules rows.
     */
    public function clear_permission_cache()
    {
        echo "Invalidating permission cache... ";
        $this->load->helper('permission');
        invalidate_permission_cache();
        echo "Done." . PHP_EOL;
    }

    /**
     * List all routes
     */
    public function routes()
    {
        echo "--- Application Routes ---" . PHP_EOL;
        require_once APPPATH . 'config/routes.php';
        foreach ($route as $uri => $target) {
            echo str_pad($uri, 30) . " -> " . $target . PHP_EOL;
        }
    }
}
