<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @author : Mamun Mia Turan
 * @filename : MY_Controller.php
 */

class MY_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->_self_heal_schema();
        $this->_check_installation();
        $this->_enforce_route_guard();
        $this->_shield_session();
        $this->_load_runtime_config();
    }

    // -------------------------------------------------------------------------
    // Schema Self-Healing (runs at most once per 24 hours, race-condition safe)
    // -------------------------------------------------------------------------
    private function _self_heal_schema()
    {
        $flag_file = APPPATH . 'cache/db_self_healed.flag';

        // Atomic exclusive lock — only one concurrent request performs healing.
        // LOCK_NB means every other request skips immediately instead of waiting.
        $fp = @fopen($flag_file, 'c+');
        if (!$fp) {
            log_message('error', 'MY_Controller: Cannot open self-heal flag: ' . $flag_file);
            return;
        }

        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            return;
        }

        $last_healed = (int) fread($fp, 20);
        $vital_tables = ['user_2fa', 'api_rate_limits'];
        $all_exist = true;
        foreach ($vital_tables as $table) {
            if (!$this->db->table_exists($table)) {
                $all_exist = false;
                break;
            }
        }
        if ($all_exist && $last_healed > 0 && (time() - $last_healed) < 86400) {
            flock($fp, LOCK_UN);
            fclose($fp);
            return;
        }

        // --- Ensure user_2fa table exists ---
        if (!$this->db->table_exists('user_2fa')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `user_2fa` (
                `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` int(11) UNSIGNED NOT NULL,
                `secret` varchar(64) NOT NULL,
                `enabled` tinyint(1) NOT NULL DEFAULT 0,
                `backup_codes` text DEFAULT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        }

        // --- Ensure api_rate_limits table exists ---
        if (!$this->db->table_exists('api_rate_limits')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `api_rate_limits` (
                `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `rate_key` varchar(128) NOT NULL,
                `hits` int(11) UNSIGNED NOT NULL DEFAULT 1,
                `window_start` int(11) UNSIGNED NOT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_rate_key` (`rate_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        }

        // --- Ensure ABAC hour-window columns exist in global_settings ---
        if ($this->db->table_exists('global_settings')) {
            foreach (['abac_hour_start', 'abac_hour_end'] as $col) {
                if (!$this->db->field_exists($col, 'global_settings')) {
                    $this->load->dbforge();
                    $default = ($col === 'abac_hour_start') ? 6 : 23;
                    $this->dbforge->add_column('global_settings', [
                        $col => ['type' => 'TINYINT', 'unsigned' => TRUE, 'default' => $default, 'null' => FALSE],
                    ]);
                }
            }
        }

        // --- Ensure login_credential.last_active column + index ---
        if ($this->db->table_exists('login_credential')) {
            if (!$this->db->field_exists('last_active', 'login_credential')) {
                $this->load->dbforge();
                $this->dbforge->add_column('login_credential', [
                    'last_active' => ['type' => 'DATETIME', 'null' => TRUE],
                ]);
            }
            $idx = $this->db->query("SHOW INDEX FROM `login_credential` WHERE Key_name = 'idx_last_active'")->num_rows();
            if (!$idx) {
                $this->db->query("ALTER TABLE `login_credential` ADD INDEX `idx_last_active` (`last_active`)");
            }
        }

        // --- Ensure jobs.priority column exists ---
        if ($this->db->table_exists('jobs')) {
            if (!$this->db->field_exists('priority', 'jobs')) {
                $this->load->dbforge();
                $this->dbforge->add_column('jobs', [
                    'priority' => ['type' => 'TINYINT', 'unsigned' => FALSE, 'default' => 0],
                ]);
            }
        }

        // --- Ensure theme_settings.font_family + font_size columns exist ---
        if ($this->db->table_exists('theme_settings')) {
            if (!$this->db->field_exists('font_family', 'theme_settings')) {
                $this->load->dbforge();
                $this->dbforge->add_column('theme_settings', [
                    'font_family' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'System Default', 'null' => FALSE],
                ]);
            }
            if (!$this->db->field_exists('font_size', 'theme_settings')) {
                $this->load->dbforge();
                $this->dbforge->add_column('theme_settings', [
                    'font_size' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'medium', 'null' => FALSE],
                ]);
            }
        }

        // --- Seed permission modules / privileges ---
        if ($this->db->table_exists('permission_modules') && $this->db->table_exists('permission')) {
            $seeder_changed   = false;
            $audit_mod_id     = 0;

            $modules_to_seed = [
                ['prefix' => 'imports',       'name' => 'Imports',    'sorted' => 10],
                ['prefix' => 'notifications', 'name' => 'Notifications', 'sorted' => 11],
                ['prefix' => 'audit_log',     'name' => 'Audit Logs', 'sorted' => 12],
            ];

            $mod_ids = [];
            foreach ($modules_to_seed as $mod) {
                $row = $this->db->get_where('permission_modules', ['prefix' => $mod['prefix']])->row();
                if (!$row) {
                    $this->db->insert('permission_modules', [
                        'name'       => $mod['name'],
                        'prefix'     => $mod['prefix'],
                        'system'     => 1,
                        'sorted'     => $mod['sorted'],
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    $mod_ids[$mod['prefix']] = $this->db->insert_id();
                    $seeder_changed = true;
                } else {
                    $mod_ids[$mod['prefix']] = $row->id;
                }
            }
            $audit_mod_id = (int) ($mod_ids['audit_log'] ?? 0);

            $permissions_to_seed = [
                ['module_key' => 'imports',       'prefix' => 'imports',       'name' => 'Imports',       'show_view' => 1, 'show_add' => 1, 'show_edit' => 1, 'show_delete' => 1],
                ['module_key' => 'notifications', 'prefix' => 'notifications', 'name' => 'Notifications', 'show_view' => 1, 'show_add' => 1, 'show_edit' => 1, 'show_delete' => 1],
                ['module_key' => 'audit_log',     'prefix' => 'activity_log',  'name' => 'Activity Log',  'show_view' => 1, 'show_add' => 0, 'show_edit' => 0, 'show_delete' => 0],
                ['module_key' => 'audit_log',     'prefix' => 'system_log',    'name' => 'System Log',    'show_view' => 1, 'show_add' => 0, 'show_edit' => 0, 'show_delete' => 0],
                ['module_key' => 'audit_log',     'prefix' => 'email_log',     'name' => 'Email Log',     'show_view' => 1, 'show_add' => 0, 'show_edit' => 0, 'show_delete' => 1],
            ];

            foreach ($permissions_to_seed as $perm) {
                if (!$this->db->get_where('permission', ['prefix' => $perm['prefix']])->row()) {
                    $this->db->insert('permission', [
                        'module_id'   => $mod_ids[$perm['module_key']] ?? 0,
                        'name'        => $perm['name'],
                        'prefix'      => $perm['prefix'],
                        'show_view'   => $perm['show_view'],
                        'show_add'    => $perm['show_add'],
                        'show_edit'   => $perm['show_edit'],
                        'show_delete' => $perm['show_delete'],
                    ]);
                    $seeder_changed = true;
                }
            }

            // Move settings-related permissions to Settings module (ID 7)
            $settings_prefixes = ['modules', 'role_permission', 'sms_setting', 'language', 'database_backup', 'database_restore'];
            if ($this->db->where_in('prefix', $settings_prefixes)->where('module_id !=', 7)->get('permission')->num_rows() > 0) {
                $this->db->where_in('prefix', $settings_prefixes)->update('permission', ['module_id' => 7]);
                $seeder_changed = true;
            }

            // Move sms_logs permission to Audit Logs module
            if ($audit_mod_id > 0) {
                if ($this->db->where('prefix', 'sms_logs')->where('module_id !=', $audit_mod_id)->get('permission')->num_rows() > 0) {
                    $this->db->where('prefix', 'sms_logs')->update('permission', ['module_id' => $audit_mod_id]);
                    $seeder_changed = true;
                }
            }

            // Seed Admin (role 2) user_privileges
            if ($this->db->table_exists('user_privileges')) {
                $privilege_prefixes = ['imports', 'notifications', 'activity_log', 'system_log', 'email_log', 'sms_logs'];
                foreach ($privilege_prefixes as $prefix) {
                    $perm_row = $this->db->get_where('permission', ['prefix' => $prefix])->row();
                    if (!$perm_row) {
                        continue;
                    }
                    $exists = $this->db->get_where('user_privileges', ['role_id' => 2, 'permission_id' => $perm_row->id])->num_rows();
                    if (!$exists) {
                        $is_delete = in_array($prefix, ['imports', 'sms_logs', 'email_log']) ? 1 : 0;
                        $is_add    = in_array($prefix, ['imports', 'notifications']) ? 1 : 0;
                        $is_edit   = in_array($prefix, ['imports', 'notifications']) ? 1 : 0;
                        $this->db->insert('user_privileges', [
                            'role_id'       => 2,
                            'permission_id' => $perm_row->id,
                            'is_view'       => 1,
                            'is_add'        => $is_add,
                            'is_edit'       => $is_edit,
                            'is_delete'     => $is_delete,
                        ]);
                        $seeder_changed = true;
                    }
                }
            }

            if ($seeder_changed) {
                generate_sidebar_files();
            }
        }

        // Write updated timestamp atomically
        ftruncate($fp, 0);
        rewind($fp);
        if (fwrite($fp, (string) time()) === false) {
            log_message('error', 'MY_Controller: Self-heal flag write failed: ' . $flag_file);
        }
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    // -------------------------------------------------------------------------
    // Installation check
    // -------------------------------------------------------------------------
    private function _check_installation()
    {
        if ($this->config->item('installed') == FALSE) {
            redirect(site_url('install'));
        }
    }

    // -------------------------------------------------------------------------
    // Strict route enforcement — blocks CI3's default controller/method bypass
    // -------------------------------------------------------------------------
    private function _enforce_route_guard()
    {
        if (is_cli()) {
            return;
        }

        $uri_string = trim($this->uri->uri_string(), '/');

        if ($uri_string === 'install' || strpos($uri_string, 'install/') === 0) {
            return;
        }

        if ($uri_string === '' || in_array($uri_string, ['landing', 'landing/show_404'])) {
            return;
        }

        $verb = strtolower($this->input->method());
        if ($verb === 'head') {
            $verb = 'get'; // HEAD is a GET without a body
        }

        // Resolve what CI will actually dispatch. On a 404_override CI rewrites
        // $URI->rsegments but leaves $router->class holding the *requested*
        // non-existent class, so read rsegments first — otherwise the 404 handler
        // itself fails the guard and unknown URLs redirect instead of rendering
        // the 404 page.
        $rsegments        = $this->uri->rsegments;
        $controller_class = ucfirst($rsegments[1] ?? $this->router->class);
        $method_name      = $rsegments[2] ?? ($this->router->method ?: 'index');

        // 1. A GET must never reach an endpoint that is declared without a GET
        //    route. CI3's router silently SKIPS a route whose verb does not match
        //    and falls through to controller/method auto-routing, so a POST-only
        //    declaration does not, by itself, stop `GET /faq/delete/{id}` from
        //    deleting — nor `GET /activitylog/clear` (whose canonical URI,
        //    activity-logs/clear, differs from the auto-routed one).
        //
        //    Deliberately scoped to GET only. Declared verbs are NOT enforced in
        //    general: methods legitimately serve verbs no single route declares —
        //    e.g. the login form POSTs to the auto-routed /authentication while
        //    Authentication@index is declared GET-only via the `login` route.
        if ($verb === 'get') {
            $directory = trim((string) ($this->router->directory ?? ''), '/');
            $target    = strtolower(($directory ? $directory . '/' : '') . $controller_class . '/' . $method_name);
            $declared  = [];
            foreach ($this->router->routes as $route_key => $t) {
                if (in_array($route_key, ['default_controller', '404_override', 'translate_uri_dashes'], true) || !is_array($t)) {
                    continue;
                }
                foreach ($t as $route_verb => $action) {
                    if (is_string($action) && strtolower(preg_replace('#/\$\d+#', '', $action)) === $target) {
                        $declared[$route_verb] = true;
                    }
                }
            }
            if ($declared && !isset($declared['get'])) {
                show_error('Method Not Allowed', 405, 'Method Not Allowed');
                return;
            }
        }

        // 2. Trust CI's own resolution (declared route or auto-routing).
        if (class_exists($controller_class) && method_exists($controller_class, $method_name)) {
            return;
        }

        // 3. Fallback: wildcard route match.
        foreach (array_keys($this->router->routes) as $route_key) {
            if (in_array($route_key, ['default_controller', '404_override', 'translate_uri_dashes'], true)) {
                continue;
            }
            $regex = str_replace(['(:any)', '(:num)'], ['[^/]+', '[0-9]+'], trim($route_key, '/'));
            $regex = str_replace('?', '\?', $regex);
            if (preg_match('#^' . $regex . '$#i', $uri_string)) {
                return;
            }
        }

        // No match found — fail closed
        if (is_loggedin()) {
            set_alert('error', translate('access_denied_invalid_route'));
            redirect(base_url('dashboard'));
        } else {
            redirect(base_url('login'));
        }
    }

    // -------------------------------------------------------------------------
    // Session Hijacking Shield
    // -------------------------------------------------------------------------
    private function _shield_session()
    {
        if (!is_loggedin()) {
            return;
        }

        $current_ip = $this->input->ip_address();
        $user_agent = $this->input->user_agent();

        // Subnet-level comparison to tolerate dynamic ISP IP shifts
        if (strpos($current_ip, ':') !== false) {
            $ip_parts  = explode(':', $current_ip);
            $ip_subnet = (count($ip_parts) >= 4) ? implode(':', array_slice($ip_parts, 0, 4)) : $current_ip;
        } else {
            $ip_parts  = explode('.', $current_ip);
            $ip_subnet = (count($ip_parts) >= 3) ? ($ip_parts[0] . '.' . $ip_parts[1] . '.' . $ip_parts[2]) : $current_ip;
        }

        // HMAC-SHA256 with server secret — stronger than plain MD5
        $fingerprint        = hash_hmac('sha256', $ip_subnet . '|' . $user_agent, config_item('encryption_key'));
        $stored_fingerprint = $this->session->userdata('session_fingerprint');

        if (empty($stored_fingerprint)) {
            $this->session->set_userdata('session_fingerprint', $fingerprint);
            return;
        }

        if (!hash_equals($stored_fingerprint, $fingerprint)) {
            $this->log_activity(
                'SECURITY_BREACH',
                'authentication',
                get_loggedin_user_id(),
                'CRITICAL: Session hijacking attempt blocked. IP: ' . $current_ip . ', UA: ' . $user_agent
            );

            $this->session->unset_userdata('loggedin');
            $this->session->unset_userdata('session_fingerprint');
            $this->session->sess_destroy();

            // Use flashdata — never leak security events in the URL
            $this->session->set_flashdata('alert-message-warning', translate('session_expired_please_login'));
            redirect(base_url('login'));
        }
    }

    // -------------------------------------------------------------------------
    // Global runtime configuration (config, maintenance, theme, timezone)
    // -------------------------------------------------------------------------
    private function _load_runtime_config()
    {
        $get_config = get_global_setting_row();
        $this->data['global_config'] = $get_config;

        // Maintenance mode
        $is_maintenance = $get_config['maintenance_mode'] ?? 0;
        if ($is_maintenance == 1 && loggedin_role_id() != 1 && $this->uri->segment(1) !== 'authentication') {
            show_error('System is under maintenance. Please try again later.', 503, 'Maintenance Mode');
        }

        $this->data['main_menu'] = '';

        // Theme config with sane default
        $theme_config = get_theme_setting_row();
        if (empty($theme_config)) {
            $theme_config = [
                'id'                 => 1,
                'primary_color'      => '#5956ea',
                'secondary_color'    => '#6c757d',
                'sidebar_color'      => '#ffffff',
                'sidebar_text_color' => '#6b7280',
                'navbar_color'       => '#ffffff',
                'navbar_text_color'  => '#6b7280',
                'dark_mode'          => 0,
                'dark_skin'          => 'false',
            ];
            $this->db->replace('theme_settings', $theme_config);
        }
        $this->data['theme_config'] = $theme_config;

        $tz = (isset($get_config['timezone']) && $get_config['timezone'] !== '') ? $get_config['timezone'] : 'UTC';
        date_default_timezone_set($tz);

        // Profiler
        if (getenv('ENABLE_PROFILER') === 'true' && !is_cli() && !$this->input->is_ajax_request()) {
            $this->output->enable_profiler(TRUE);
        }
    }

    // -------------------------------------------------------------------------
    // Activity Logging
    // Fast path: atomic append to date-partitioned JSON flat file (synchronous).
    // Slow path: DB insert + Pusher notification offloaded to queue (async).
    // -------------------------------------------------------------------------
    public function log_activity(string $action, string $module, $record_id = 0, string $description = '', $old_data = null, $new_data = null)
    {
        if (!is_loggedin()) {
            return;
        }

        $data = [
            'user_id'     => get_loggedin_user_id(),
            'user_name'   => get_loggedin_name(),
            'module_name' => $module,
            'table_name'  => $module,
            'row_id'      => (int) $record_id,
            'action'      => $action,
            'description' => $description,
            'ip_address'  => $this->input->ip_address(),
            'user_agent'  => $this->input->user_agent(),
            'created_at'  => date('Y-m-d H:i:s'),
            'old_data'    => $old_data,
            'new_data'    => $new_data,
        ];

        // --- Synchronous: write to flat file for immediate audit trail ---
        $log_dir = APPPATH . 'logs/activity/';
        if (!is_dir($log_dir)) {
            // @ suppresses the warning so a permission problem degrades to an
            // error log entry instead of breaking the response (headers already sent).
            if (!@mkdir($log_dir, 0755, true) && !is_dir($log_dir)) {
                log_message('error', 'Failed to create activity log directory: ' . $log_dir);
                return;
            }
        }
        if (@file_put_contents($log_dir . date('Y-m-d') . '.json', json_encode($data) . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            log_message('error', 'Failed to write activity log.');
        }

        // --- Tamper-evident append to the hash chain (best-effort, never blocks) ---
        try {
            $this->load->library('audit_chain');
            $this->audit_chain->append($data);
        } catch (Throwable $e) {
            log_message('error', 'audit_chain append failed: ' . $e->getMessage());
        }

        // --- Async: push DB insert + Pusher notification to queue worker ---
        $this->load->library('queue');
        $this->queue->push('queue/persist_activity_log', $data);
        $this->queue->push('queue/trigger_pusher', [
            'channel' => 'activity-channel',
            'event'   => 'new-log',
            'data'    => [
                'user'        => $data['user_name'],
                'action'      => $action,
                'description' => $description,
                'time'        => date('H:i:s'),
            ]
        ]);
    }

    // -------------------------------------------------------------------------
    // Standardised JSON response helper
    // -------------------------------------------------------------------------
    public function jsonResponse($data, int $status = 200)
    {
        if (is_array($data) && !isset($data['csrf'])) {
            $data['csrf'] = [$this->security->get_csrf_token_name() => $this->security->get_csrf_hash()];
        }
        return $this->output
            ->set_status_header($status)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    // -------------------------------------------------------------------------
    // Global HTML output interceptor — injects CSRF meta tag and form tokens
    // -------------------------------------------------------------------------
    public function _output(string $output)
    {
        if ($this->input->is_ajax_request() || is_cli() || $this->output->get_content_type() !== 'text/html') {
            echo $output;
            return;
        }

        $csrf_name = $this->security->get_csrf_token_name();
        $csrf_hash = $this->security->get_csrf_hash();

        // 1. Inject CSRF meta tag inside <head>
        if (strpos($output, '<head>') !== false) {
            $meta = PHP_EOL
                . '    <meta name="csrf-token" content="' . $csrf_hash . '">' . PHP_EOL
                . '    <meta name="csrf-name" content="' . $csrf_name . '">';
            $output = str_replace('<head>', '<head>' . $meta, $output);
        }

        // 2. Inject CSRF hidden input into POST forms that don't already have one.
        //    Capture the full form (open tag + body + close tag) so the deduplication
        //    check searches the actual form body, not just the opening tag.
        $csrf_field_pattern = '#name=["\']' . preg_quote($csrf_name, '#') . '["\']\s*#i';
        $output = preg_replace_callback(
            '#<form(\s[^>]*?)?\bmethod=["\']post["\']([^>]*)>(.*?)</form>#is',
            function ($matches) use ($csrf_name, $csrf_hash, $csrf_field_pattern) {
                // Already contains a CSRF input inside the form body — skip
                if (preg_match($csrf_field_pattern, $matches[3])) {
                    return $matches[0];
                }
                $hidden = '<input type="hidden" name="' . $csrf_name . '" value="' . $csrf_hash . '">';
                return '<form' . $matches[1] . 'method="post"' . $matches[2] . '>'
                    . PHP_EOL . $hidden
                    . $matches[3]
                    . '</form>';
            },
            $output
        );

        echo $output;
    }
}

// -----------------------------------------------------------------------------

class Authentication_Controller extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('authentication_model');
    }
}

// -----------------------------------------------------------------------------

class Admin_Controller extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (is_cli()) {
            return;
        }

        if (!is_loggedin()) {
            if ($this->input->is_ajax_request()) {
                $this->jsonResponse(['error' => 'Session expired. Please log in again.', 'redirect' => base_url('authentication')], 401);
                $this->output->_display();
                exit;
            }
            $this->session->set_userdata('redirect_url', current_url());
            redirect(base_url('authentication'), 'refresh');
        }

        if (!$this->session->userdata('login_time')) {
            $this->session->set_userdata('login_time', time());
        }

        $this->_check_access();
    }

    private function _check_access()
    {
        $class  = strtolower($this->router->fetch_class());
        $method = strtolower($this->router->fetch_method());

        // Core endpoints accessible to every authenticated user
        if (in_array($class, ['dashboard', 'profile', 'authentication', 'ajax'])) {
            return;
        }

        // Language switching is a UX action — no permission required
        if ($class === 'language' && $method === 'set_language') {
            return;
        }

        $permission_map = [
            'category'    => 'category',
            'brand'       => 'brand',
            'product'     => 'product',
            'attributes'  => 'attribute',
            'attribute_family' => 'attribute_family',
            'inventory_source' => 'inventory_source',
            'review'      => 'review',
            'order'       => 'order',
            'rma'         => 'rma',
            'coupon'      => 'coupon',
            'shipping'    => (strpos($method, 'method') !== false ? 'shipping_method' : 'shipping_zone'),
            'tax'         => (strpos($method, 'rate') !== false ? 'tax_rate' : 'tax_category'),
            'cms'         => 'cms',
            'faq'         => 'faq',
            'contact'     => 'contact',
            'complaint'   => 'complaint',
            'ticket'      => 'ticket',
            'blog'        => 'blog',
            'banner'      => 'banner',
            'report'      => 'report',
            'flash_sale'  => 'flash_sale',
            'newsletter'  => 'newsletter',
            'customer_group' => 'customer_group',
            'cart_rule'   => 'cart_rule',
            'catalog_rule'=> 'catalog_rule',
            'paymentsetting' => (strpos($method, 'transaction') !== false ? 'payment_transaction' : 'payment_method'),
            'user'        => 'user',
            'customer'    => 'customer',
            'wishlist'    => 'user',
            'import'      => 'imports',
            'notification'=> 'notifications',
            'settings'    => 'global_setting',
            'landing_setting' => 'global_setting',
            'email'       => 'email_setting',
            'language'    => 'language',
            'role'        => 'role_permission',
            'module'      => 'modules',
            'smssettings' => 'sms_setting',
            'sms'         => in_array($method, ['index', 'template']) ? 'sms_setting' : 'send_sms',
            'smslog'      => 'sms_logs',
            'backup'      => $method === 'restore' ? 'database_restore' : 'database_backup',
            'dbtool'      => 'database_backup',
            'activitylog' => 'activity_log',
            'systemlog'   => 'system_log',
            'emaillog'    => 'email_log',
            'queuedashboard'=> 'system_log',
        ];

        $prefix = $permission_map[$class] ?? null;

        if ($prefix === null) {
            access_denied(); // Fail-closed: unknown controller is always denied
            return;
        }

        if (!get_permission($prefix, 'is_view')) {
            access_denied();
        }
    }
}

// -----------------------------------------------------------------------------

class Frontend_Controller extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->data['cms_setting'] = get_global_setting_row() ?: [];
    }
}

// -----------------------------------------------------------------------------

class Cron_Controller extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }
}
