<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : general_helper.php
 */

if (!function_exists('time_ago')) {
    /**
     * Relative "time ago" label with a hover tooltip showing the actual date.
     *   < 60 min  -> "x minutes ago" (or "just now" under a minute)
     *   < 24 hr   -> "x hours ago"
     *   < 30 days -> "x days ago"
     *   < 12 mo   -> "x months ago"
     *   else      -> "x years ago"
     *
     * Returns a <span data-toggle="tooltip" title="<full date>">…</span> so a
     * Bootstrap tooltip reveals the exact timestamp on hover.
     *
     * @param string|int $datetime  datetime string or unix timestamp
     * @param bool       $tooltip   wrap with the hover tooltip span (default true)
     */
    function time_ago($datetime, $tooltip = true)
    {
        if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
            return '';
        }
        $ts = is_numeric($datetime) ? (int) $datetime : strtotime((string) $datetime);
        if (!$ts) {
            return html_escape((string) $datetime);
        }

        // created_at / updated_at are always historical, so always read as "ago".
        // (A timestamp resolving slightly in the future is a timezone/clock skew,
        // not a real future event — never show "from now".)
        $diff   = abs(time() - $ts);
        $suffix = ' ago';

        if ($diff < 60) {
            $rel = 'just now';
        } elseif ($diff < 3600) {
            $n = (int) floor($diff / 60);
            $rel = $n . ' minute' . ($n > 1 ? 's' : '') . $suffix;
        } elseif ($diff < 86400) {
            $n = (int) floor($diff / 3600);
            $rel = $n . ' hour' . ($n > 1 ? 's' : '') . $suffix;
        } elseif ($diff < 604800) { // < 7 days
            $n = (int) floor($diff / 86400);
            $rel = $n . ' day' . ($n > 1 ? 's' : '') . $suffix;
        } elseif ($diff < 2592000) { // < ~30 days -> weeks
            $n = (int) floor($diff / 604800);
            $rel = $n . ' week' . ($n > 1 ? 's' : '') . $suffix;
        } elseif ($diff < 31536000) { // < 12 months
            $n = (int) floor($diff / 2592000);
            $rel = $n . ' month' . ($n > 1 ? 's' : '') . $suffix;
        } else {
            $n = (int) floor($diff / 31536000);
            $rel = $n . ' year' . ($n > 1 ? 's' : '') . $suffix;
        }

        if (!$tooltip) {
            return html_escape($rel);
        }
        $actual = date('d M Y, h:i A', $ts);
        return '<span class="time-ago" data-toggle="tooltip" data-original-title="' . html_escape($actual) . '" title="' . html_escape($actual) . '">' . html_escape($rel) . '</span>';
    }
}

// Translation functions: translate(), update_language_cache(), rebuild_all_languages_cache()
require_once APPPATH . 'helpers/translation_helper.php';

// return translation
function is_secure($url)
{
    $is_secure = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) === 'on');

    return ($is_secure ? 'https://' : 'http://') . $url;
}

function sanitizeString($var = '')
{
    if (empty($var)) {
        return $var;
    }
    if (is_array($var)) {
        foreach (array_keys($var) as $key) {
            $var[$key] = sanitizeString($var[$key]);
        }

        return $var;
    }

    $var = trim($var);
    $var = strtr($var, array_flip(get_html_translation_table(HTML_ENTITIES)));
    $var = strip_tags($var);
    $var = htmlspecialchars($var, ENT_QUOTES, config_item('charset'), true);
    return $var;
}

function get_global_setting($name = '')
{
    $name = trim($name);
    // Sanitize column name
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
    if (empty($name)) return '';

    static $settings_cache = null;
    $cache_file = APPPATH . 'logs/global/global_settings.json';

    if ($settings_cache === null) {
        if (file_exists($cache_file)) {
            $settings_cache = json_decode(file_get_contents($cache_file), true);
        } else {
            $settings_cache = update_global_settings_cache();
        }
    }

    return isset($settings_cache[$name]) ? $settings_cache[$name] : '';
}

function update_global_settings_cache()
{
    $ci = &get_instance();
    $query = $ci->db->get_where('global_settings', array('id' => 1));
    if ($query->num_rows() > 0) {
        $settings = $query->row_array();
        $dir = APPPATH . 'logs/global/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $cache_file = $dir . 'global_settings.json';
        atomic_file_put_contents($cache_file, json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $settings;
    }
    return [];
}

// Retrieve full global settings row from static JSON cache
function get_global_setting_row()
{
    static $settings_cache = null;
    $cache_file = APPPATH . 'logs/global/global_settings.json';

    if ($settings_cache === null) {
        if (file_exists($cache_file)) {
            $settings_cache = json_decode(file_get_contents($cache_file), true);
        } else {
            $settings_cache = update_global_settings_cache();
        }
    }
    return $settings_cache;
}

// Retrieve single theme setting from static JSON cache
function get_theme_setting($name = '')
{
    $name = trim($name);
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
    if (empty($name)) return '';

    $theme_cache = get_theme_setting_row();
    return isset($theme_cache[$name]) ? $theme_cache[$name] : '';
}

// Retrieve full theme settings row (supports user-based themes with fallback to global default)
function get_theme_setting_row()
{
    static $theme_cache = [];
    $ci = &get_instance();
    $user_id = is_loggedin() ? get_loggedin_user_id() : null;
    $cache_key = $user_id ? 'user_' . $user_id : 'global';

    if (!isset($theme_cache[$cache_key])) {
        // Try fetching user-specific settings first
        if ($user_id) {
            $query = $ci->db->get_where('theme_settings', array('user_id' => $user_id));
            if ($query->num_rows() > 0) {
                $theme_cache[$cache_key] = $query->row_array();
                return $theme_cache[$cache_key];
            }
        }
        
        // Fallback to global settings (id = 1)
        $query = $ci->db->get_where('theme_settings', array('id' => 1));
        if ($query->num_rows() > 0) {
            $theme_cache[$cache_key] = $query->row_array();
        } else {
            // Default settings array in case table is empty
            $theme_cache[$cache_key] = [
                'id' => 1,
                'user_id' => null,
                'primary_color' => '#5956ea',
                'secondary_color' => '#6c757d',
                'sidebar_color' => '#ffffff',
                'sidebar_text_color' => '#6b7280',
                'navbar_color' => '#ffffff',
                'navbar_text_color' => '#6b7280',
                'dark_mode' => 0,
                'dark_skin' => 'false',
                'font_family' => 'System Default'
            ];
        }
    }
    return $theme_cache[$cache_key];
}

// Save or update user-specific theme settings
function save_user_theme($user_id, $data)
{
    $ci = &get_instance();

    // Schema-drift guard: some deploys have a theme_settings table missing the
    // font columns (the migration/self-heal didn't run), which made theme save
    // fail with "Unknown column 'font_family'". Ensure they exist before writing.
    if ($ci->db->table_exists('theme_settings')) {
        $font_cols = [
            'font_family' => "VARCHAR(50) NOT NULL DEFAULT 'System Default'",
            'font_size'   => "VARCHAR(20) NOT NULL DEFAULT 'medium'",
        ];
        foreach ($font_cols as $col => $type) {
            if (!$ci->db->field_exists($col, 'theme_settings')) {
                $ci->db->query("ALTER TABLE `theme_settings` ADD COLUMN `{$col}` {$type}");
            }
        }
    }

    // Check if user row exists
    $exists = $ci->db->get_where('theme_settings', array('user_id' => $user_id))->num_rows() > 0;

    if ($exists) {
        $ci->db->where('user_id', $user_id);
        $ci->db->update('theme_settings', $data);
    } else {
        // Fetch default row (id = 1) to clone settings
        $default = $ci->db->get_where('theme_settings', array('id' => 1))->row_array();
        if (!$default) {
            $default = [
                'primary_color'      => '#5956ea',
                'secondary_color'    => '#6c757d',
                'sidebar_color'      => '#ffffff',
                'sidebar_text_color' => '#6b7280',
                'navbar_color'       => '#ffffff',
                'navbar_text_color'  => '#6b7280',
                'dark_mode'          => 0,
                'dark_skin'          => 'false',
                'font_family'        => 'System Default'
            ];
        }
        unset($default['id']);
        $insert_data = array_merge($default, $data);
        $insert_data['user_id'] = $user_id;
        $ci->db->insert('theme_settings', $insert_data);
    }

    // Sync global row (id=1) with color/theme fields so the login page
    // and any unauthenticated view always reflects the latest saved theme.
    $global_fields = array_intersect_key($data, array_flip([
        'primary_color', 'secondary_color', 'sidebar_color',
        'sidebar_text_color', 'navbar_color', 'navbar_text_color',
        'dark_skin', 'dark_mode', 'font_family', 'font_size',
    ]));
    if (!empty($global_fields)) {
        $ci->db->where('id', 1)->where('user_id IS NULL', null, false);
        $ci->db->update('theme_settings', $global_fields);
    }
}

// No-op for backward compatibility (system cache rebuilds)
function update_theme_settings_cache()
{
    return get_theme_setting_row();
}

function get_logo_url()
{
    $logo = get_global_setting('logo');
    if (empty($logo)) {
        $logo = 'logo.png';
    }
    $file_path = 'uploads/app_image/' . $logo;
    if (!file_exists(FCPATH . $file_path)) {
        $file_path = 'uploads/app_image/defualt.png';
    }
    $v = file_exists(FCPATH . $file_path) ? filemtime(FCPATH . $file_path) : time();
    return base_url($file_path . '?v=' . $v);
}

function get_site_name()
{
    return get_global_setting('site_name');
}

// Permission functions: get_permission(), get_role_permission(), get_user_permissions()
require_once APPPATH . 'helpers/permission_helper.php';

// get session loggedin
function is_loggedin()
{
    $ci = &get_instance();
    if ($ci->session->has_userdata('loggedin')) {
        return true;
    }
    return false;
}

// get loggedin role name
function loggedin_role_name()
{
    $ci = &get_instance();
    $role_id = $ci->session->userdata('loggedin_role_id');
    $row = $ci->db->select('name')->where('id', $role_id)->get('roles')->row();
    return $row ? $row->name : '';
}

function loggedin_role_id()
{
    $ci = &get_instance();
    return $ci->session->userdata('loggedin_role_id');
}

// Branch concept not used in this project — always returns null
function loggedin_branch_id(): ?int
{
    return null;
}

// get logged in user id
function get_loggedin_id()
{
    $ci = &get_instance();
    return $ci->session->userdata('loggedin_id');
}

// get user db id
function get_loggedin_user_id()
{
    $ci = &get_instance();
    return $ci->session->userdata('loggedin_userid');
}

function is_superadmin_loggedin()
{
    $ci = &get_instance();
    return $ci->session->userdata('loggedin_role_id') == 1;
}

// get logged in user name
function get_loggedin_name()
{
    $ci = &get_instance();
    return $ci->session->userdata('name');
}

// get table name by type and id
function get_type_name_by_id($type, $type_id = '', $field = 'name')
{
    $ci = &get_instance();
    // Sanitize table and field names
    $type  = preg_replace('/[^a-zA-Z0-9_-]/', '', $type);
    $field = preg_replace('/[^a-zA-Z0-9_-]/', '', $field);

    if (empty($type) || empty($field)) return '';

    $result = $ci->db->select($field)->from($type)->where('id', $type_id)->limit(1)->get()->row_array();
    return $result ? $result[$field] : '';
}

// set session alert / flashdata
function set_alert($type, $message)
{
    $ci = &get_instance();
    $ci->session->set_flashdata('alert-message-' . $type, $message);
}

// generate secure random hash
function app_generate_hash()
{
    try {
        return bin2hex(random_bytes(16));
    } catch (Exception $e) {
        // OpenSSL fallback — still cryptographically strong on all major platforms
        $bytes = openssl_random_pseudo_bytes(16, $strong);
        if ($bytes !== false && $strong) {
            return bin2hex($bytes);
        }
        // Last-resort: combine multiple entropy sources and hash them
        return hash('sha256', uniqid('', true) . microtime(true) . getmypid());
    }
}

// generate encryption key
function generate_encryption_key()
{
    $ci = &get_instance();
    // In case accessed from my_functions_helper.php
    $ci->load->library('encryption');
    $key = bin2hex($ci->encryption->create_key(16));
    return $key;
}

function _d($date)
{
    if ($date == '' || is_null($date) || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
        return '';
    }
    $time = strtotime($date);
    if ($time === false) {
        return '';
    }
    $ci = &get_instance();
    $format = $ci->session->userdata('date_format');
    if (empty($format)) {
        $format = 'Y-m-d';
    }

    // Polyfill for deprecated strftime formats
    if (strpos($format, '%') !== false) {
        $format = str_replace(
            ['%Y', '%y', '%m', '%d', '%b', '%B', '%H', '%M', '%S', '%p'],
            ['Y',  'y',  'm',  'd',  'M',  'F',  'H',  'i',  's',  'A'],
            $format
        );
    }

    return date($format, $time);
}

// delete url
function btn_delete($uri)
{
    $parts = explode('/', $uri);
    // Automatically encrypt any numeric parameters in the URL segments
    for ($i = 2; $i < count($parts); $i++) {
        if (is_numeric($parts[$i])) {
            $parts[$i] = encrypt_id($parts[$i]);
        }
    }
    $uri = implode('/', $parts);
    return "<button type='button' class='btn btn-danger btn-circle icon' onclick=confirm_modal('" . site_url($uri) . "') ><i class='fas fa-trash-alt'></i></button>";
}

// csrf jquery token
function csrf_jquery_token()
{
    $csrf = [get_instance()->security->get_csrf_token_name() => get_instance()->security->get_csrf_hash()];
    return $csrf;
}

function check_hash_restrictions($table, $id, $hash)
{
    $ci = &get_instance();
    if (!$table || !$id || !$hash) {
        show_404();
    }
    $query = $ci->db->select('hash')->where('id', $id)->get($table);
    if ($query->num_rows() > 0) {
        $get_hash = $query->row()->hash;
    } else {
        $get_hash = '';
    }
    if (empty($hash) || ($get_hash != $hash)) {
        show_404();
    }
}

function get_nicetime($date)
{
    $get_format = get_global_setting('date_format') ?: 'Y-m-d';
    if (empty($date)) {
        return "Unknown";
    }

    $ptime = strtotime($date);
    if ($ptime === false) {
        return "Unknown";
    }
    $ctime = time();
    $diff = $ctime - $ptime;

    if ($diff < 60) {
        return "Just now";
    }

    $minutes = round($diff / 60);
    if ($minutes < 60) {
        return $minutes == 1 ? "1 minute ago" : $minutes . " minutes ago";
    }

    $hours = round($diff / 3600);
    if ($hours < 24) {
        return $hours == 1 ? "1 hour ago" : $hours . " hours ago";
    }

    $days = round($diff / 86400);
    if ($days < 2) {
        return "1 day ago";
    }
    if ($days < 3) {
        return "2 days ago";
    }

    return date($get_format, $ptime);
}

function bytesToSize($path, $filesize = '')
{
    if (!is_numeric($filesize)) {
        $bytes = sprintf('%u', filesize($path));
    } else {
        $bytes = $filesize;
    }
    if ($bytes > 0) {
        $unit  = intval(log($bytes, 1024));
        $units = ['B', 'KB', 'MB', 'GB'];
        if (array_key_exists($unit, $units) === true) {
            return sprintf('%d %s', $bytes / pow(1024, $unit), $units[$unit]);
        }
    }
    return $bytes;
}

function array_to_object($array)
{
    if (!is_array($array) && !is_object($array)) {
        return new stdClass();
    }
    return json_decode(json_encode((object)$array));
}

function access_denied()
{
    $ci = &get_instance();
    if ($ci->input->is_ajax_request()) {
        $ci->output
            ->set_status_header(403)
            ->set_content_type('application/json')
            ->set_output(json_encode(['error' => translate('access_denied')]))
            ->_display();
        exit;
    }
    set_alert('error', translate('access_denied'));
    redirect(site_url('dashboard'));
}

/**
 * Status → coloured badge. Success = green, warning = yellow, danger = red,
 * info = blue, unknown = default/grey.
 *
 * Usage: <?= status_badge($row['status']); ?>
 */
if (!function_exists('asset_ver')) {
    /**
     * Cache-busted asset URL. Appends the file's last-modified time as ?v=,
     * so editing a CSS/JS file automatically invalidates the browser cache —
     * no more manually bumping a hardcoded ?v=. Falls back to '1' if the file
     * is missing (e.g. path typo) so the link still renders.
     */
    function asset_ver($relative_path)
    {
        $rel  = ltrim($relative_path, '/');
        $full = FCPATH . $rel;
        $v    = is_file($full) ? filemtime($full) : '1';
        return base_url($rel) . '?v=' . $v;
    }
}

if (!function_exists('status_badge')) {
    function status_badge(string $status, ?string $label = null): string
    {
        $map = [
            // success (green)
            'active'             => 'success',
            'completed'          => 'success',
            'approved'           => 'success',
            'delivered'          => 'success',
            'paid'               => 'success',
            'verified'           => 'success',
            'replied'            => 'success',
            'available'          => 'success',
            'success'            => 'success',
            'sent'               => 'success',
            'open'               => 'success',
            'received'           => 'success',
            // warning (yellow)
            'pending'            => 'warning',
            'confirmed'          => 'warning',
            'processing'         => 'warning',
            'queued'             => 'warning',
            'partially_refunded' => 'warning',
            'unpaid'             => 'warning',
            'unread'             => 'warning',
            'review'             => 'warning',
            'on_hold'            => 'warning',
            // danger (red)
            'cancelled'          => 'danger',
            'rejected'           => 'danger',
            'inactive'           => 'danger',
            'failed'             => 'danger',
            'suspended'          => 'danger',
            'deleted'            => 'danger',
            'closed'             => 'danger',
            'expired'            => 'danger',
            'sold_out'           => 'danger',
            // info (blue)
            'read'               => 'info',
            'draft'              => 'info',
            'scheduled'          => 'info',
            'new'                => 'info',
            'other'              => 'info',
        ];

        $key   = strtolower(trim($status));
        $class = $map[$key] ?? 'default';
        $text  = html_escape($label ?? ucwords(str_replace(['_', '-'], ' ', $status)));
        return '<span class="badge badge-' . $class . '">' . $text . '</span>';
    }
}

if (!function_exists('ajax_access_denied')) {
    /**
     * JSON-only variant of access_denied() for XHR endpoints that must never
     * emit an HTML redirect. Superman is exempt.
     */
    function ajax_access_denied()
    {
        if (is_superman_loggedin()) {
            return;
        }

        $ci = &get_instance();
        $ci->output
            ->set_status_header(403)
            ->set_content_type('application/json')
            ->set_output(json_encode(['error' => translate('access_denied')]))
            ->_display();
        exit;
    }
}

if (!function_exists('d')) {
    /**
     * Dump variable in a readable format
     * Show the file name and line where it is used.
     * With Details and summary tag
     *
     * @param mixed ...$vars
     * @example d($var1, $var2, $var3, ...)
     *
     * @author Sajib Adhikary <tosajibadhi@gmail.com>
     */
    function d(...$vars)
    {
        echo '<details style="background: #f5f5f5; padding: 10px; margin: 10px; border: 1px solid #ccc; border-radius: 5px;">';
        echo '<summary style="background: #ddd; padding: 5px; border-radius: 5px; cursor: pointer;">'
            . '<p style="color: #666; font-size: 12px;">' . debug_backtrace()[0]['file'] . ' at line ' . debug_backtrace()[0]['line'] . '</p>'
            . '</summary>';
        echo '<pre>';
        foreach ($vars as $var => $value) {
            var_dump($value);
        }
        echo '</pre>';
        echo '</details>';
    }
}

// Usage: dd($vars);
if (!function_exists('dd')) {
    function dd(...$vars)
    {
        echo '<details style="background: #f5f5f5; padding: 10px; margin: 10px; border: 1px solid #ccc; border-radius: 5px;">';
        echo '<summary style="background: #ddd; padding: 5px; border-radius: 5px; cursor: pointer;">'
            . '<p style="color: #666; font-size: 12px;">' . debug_backtrace()[0]['file'] . ' at line ' . debug_backtrace()[0]['line'] . '</p>'
            . '</summary>';
        echo '<pre>';
        foreach ($vars as $var => $value) {
            var_dump($value);
        }
        echo '</pre>';
        echo '</details>';
        die();
    }
}

if (!function_exists('_app_id_key')) {
    function _app_id_key()
    {
        $key = getenv('SECURITY_KEY') ?: config_item('encryption_key') ?: 'my_secure_salt_auth_key';
        if ($key === 'my_secure_salt_auth_key') {
            log_message('error', 'SECURITY: SECURITY_KEY is not set — encrypt_id/decrypt_id are using the hardcoded default key. All encrypted resource IDs are forgeable. Set SECURITY_KEY in .env immediately.');
        }
        return $key;
    }
}

if (!function_exists('encrypt_id')) {
    function encrypt_id($id)
    {
        if (empty($id) && $id !== 0 && $id !== '0') return '';
        $key     = _app_id_key();
        $encoded = base64_encode($id . '|' . hash_hmac('sha256', $id, $key));
        return str_replace(['+', '/', '='], ['-', '_', ''], $encoded);
    }
}

if (!function_exists('decrypt_id')) {
    function decrypt_id($hash)
    {
        if (empty($hash)) return '';

        $base64  = str_replace(['-', '_'], ['+', '/'], $hash);
        $padding = strlen($base64) % 4;
        if ($padding > 0) {
            $base64 .= str_repeat('=', 4 - $padding);
        }
        $decoded = base64_decode($base64);
        if ($decoded === false) return '';

        $parts = explode('|', $decoded);
        if (count($parts) !== 2) return '';

        list($id, $md5) = $parts;
        $key = _app_id_key();
        if (hash_equals(hash_hmac('sha256', $id, $key), $md5)) {
            return $id;
        }
        return '';
    }
}

if (!function_exists('route_hash')) {
    function route_hash($method)
    {
        return $method;
    }
}

function atomic_file_put_contents($filename, $data)
{
    $dir = dirname($filename);

    // Bail out quietly if the destination is not writable (e.g. bad deployment
    // permissions). Callers treat cache writes as best-effort, so log instead
    // of emitting warnings that break output/redirects.
    if (!is_writable($dir) || (file_exists($filename) && !is_writable($filename))) {
        log_message('error', 'atomic_file_put_contents: destination not writable: ' . $filename);
        return false;
    }

    $temp = @tempnam($dir, 'temp');

    // tempnam() falls back to the system temp dir when $dir is unusable;
    // renaming across directories/filesystems is not atomic (and may fail),
    // so only use the temp file if it actually landed in $dir.
    if ($temp === false || dirname($temp) !== rtrim($dir, '/\\')) {
        if ($temp !== false) {
            @unlink($temp);
        }
        return @file_put_contents($filename, $data, LOCK_EX) !== false;
    }

    if (file_put_contents($temp, $data, LOCK_EX) !== false) {
        chmod($temp, 0666);
        if (@rename($temp, $filename)) {
            return true;
        }
        @unlink($temp);
    }
    return @file_put_contents($filename, $data, LOCK_EX) !== false;
}

// ── TOTP Secret Encryption Helpers ───────────────────────────────────────────
// Encrypts/decrypts TOTP secrets stored in the database using AES-256-CBC
// so a raw DB dump does not expose all second-factor secrets.

if (!function_exists('totp_encrypt_secret')) {
    function totp_encrypt_secret(string $plain): string
    {
        $key = hash('sha256', _app_id_key(), true); // 32-byte key
        $iv  = random_bytes(16);
        $ct  = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $ct);
    }
}

if (!function_exists('totp_decrypt_secret')) {
    function totp_decrypt_secret(string $stored): string
    {
        $raw = base64_decode($stored, true);
        if ($raw === false || strlen($raw) < 17) {
            return $stored; // fallback: return as-is for legacy plaintext secrets
        }
        $key = hash('sha256', _app_id_key(), true);
        $iv  = substr($raw, 0, 16);
        $ct  = substr($raw, 16);
        $plain = openssl_decrypt($ct, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return ($plain !== false) ? $plain : $stored;
    }
}

// Sidebar functions: get_role_db_permission(), get_sidebar_item_compile_status(), get_role_sidebar_filename(), generate_sidebar_files(), generate_sidebar_content()
require_once APPPATH . 'helpers/sidebar_helper.php';
