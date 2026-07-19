<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Permission Helper
 *
 * @package    Authentication
 * @author     Mamun Mia Turan
 * @filename   permission_helper.php
 *
 * Rules:
 *   - Superman (ROLE_SUPERMAN_ID) : always has access to everything
 *   - All other roles             : permissions based purely on DB (user_privileges table)
 *   - No developer_mode concept anywhere
 */

function is_superman_loggedin(): bool
{
    return (int) loggedin_role_id() === ROLE_SUPERMAN_ID;
}

/**
 * Authority level for a role id (lower number = more authority).
 * Superman is always 0. Falls back to the role id itself when the `level`
 * column is absent, preserving the legacy id-based ordering. Result is cached
 * per-request.
 */
function role_level($role_id): int
{
    static $cache = [];
    $role_id = (int) $role_id;
    if ($role_id === ROLE_SUPERMAN_ID) {
        return 0;
    }
    if (isset($cache[$role_id])) {
        return $cache[$role_id];
    }
    $ci = &get_instance();
    if (!$ci->db->field_exists('level', 'roles')) {
        return $cache[$role_id] = $role_id;
    }
    // Raw query (NOT the query builder): this helper is often called while a
    // caller is mid-way assembling another query on the shared $this->db, and
    // builder methods like ->get() would reset that in-progress query.
    $row = $ci->db->query('SELECT level FROM roles WHERE id = ? LIMIT 1', [$role_id])->row();
    return $cache[$role_id] = ($row ? (int) $row->level : PHP_INT_MAX);
}

/**
 * Roles that may use "Login As User" (impersonation).
 */
function can_impersonate_users(): bool
{
    return in_array((int) loggedin_role_id(), [ROLE_SUPERMAN_ID, ROLE_ADMIN_ID, ROLE_BRANCH_ID], true);
}

/**
 * Whether the logged-in actor may impersonate a specific target user.
 * Nobody may impersonate themselves or a Superman. Superman may impersonate
 * anyone else; Admin only roles below Admin; Branch only lower roles in its
 * own branch.
 */
function can_impersonate_target(int $target_user_id, int $target_role_id, ?int $target_branch_id = null): bool
{
    if (!can_impersonate_users()) {
        return false;
    }

    $actor_user_id = (int) get_loggedin_user_id();
    $actor_role    = (int) loggedin_role_id();

    if ($target_user_id === $actor_user_id || $target_role_id === ROLE_SUPERMAN_ID) {
        return false;
    }

    if ($actor_role === ROLE_SUPERMAN_ID) {
        return true;
    }

    if ($actor_role === ROLE_ADMIN_ID) {
        return $target_role_id > ROLE_ADMIN_ID;
    }

    if ($actor_role === ROLE_BRANCH_ID) {
        if ($target_role_id <= ROLE_BRANCH_ID) {
            return false;
        }
        $own_branch = loggedin_branch_id();
        return $own_branch !== null
            && $target_branch_id !== null
            && $own_branch === (int) $target_branch_id;
    }

    return false;
}

// Getting user access permission — main entry point
function get_permission($permission, $can = '')
{
    $ci      = &get_instance();
    $role_id = $ci->session->userdata('loggedin_role_id');

    // --- ABAC Guards: time-window & WAN IP check for high-security operations ---
    if (in_array($permission, ['database_backup', 'database_restore', 'global_setting'])) {
        $client_ip    = $ci->input->ip_address();
        $current_hour = (int) date('H');

        // Read configurable hour window from global_settings (defaults: start=6, end=23)
        $hour_start = (int) (get_global_setting('abac_hour_start') ?: 6);
        $hour_end   = (int) (get_global_setting('abac_hour_end')   ?: 23);

        if ($current_hour < $hour_start || $current_hour > $hour_end) {
            $ci->db->insert('activity_logs', [
                'user_id'     => $ci->session->userdata('loggedin_userid') ?: null,
                'module_name' => 'security_alert',
                'table_name'  => 'security_alert',
                'row_id'      => 0,
                'action'      => 'other',
                'description' => "Blocked administrative access outside allowed hours ({$hour_start}:00–{$hour_end}:59).",
                'new_data'    => json_encode([
                    'permission' => $permission,
                    'action'     => $can,
                    'alert_type' => 'time_lockout',
                ]),
                'ip_address'  => $client_ip,
                'user_agent'  => $ci->input->user_agent(),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
            return false;
        }

        // Log WAN access as warning (does not block)
        // Private ranges: 127.x, ::1, 10.x, 172.16-31.x, 192.168.x
        $is_local = (
            in_array($client_ip, ['127.0.0.1', '::1']) ||
            strpos($client_ip, '10.')       === 0 ||
            strpos($client_ip, '192.168.')  === 0 ||
            preg_match('/^172\.(1[6-9]|2\d|3[01])\./', $client_ip)
        );
        if (!$is_local) {
            $ci->db->insert('activity_logs', [
                'user_id'     => $ci->session->userdata('loggedin_userid') ?: null,
                'module_name' => 'security_alert',
                'table_name'  => 'security_alert',
                'row_id'      => 0,
                'action'      => 'other',
                'description' => 'Administrative request originated from external WAN network.',
                'new_data'    => json_encode([
                    'permission' => $permission,
                    'action'     => $can,
                    'alert_type' => 'wan_access_warning',
                ]),
                'ip_address'  => $client_ip,
                'user_agent'  => $ci->input->user_agent(),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }
    }

    // Superman always has full access — no developer_mode needed
    if ($role_id == ROLE_SUPERMAN_ID) {
        return true;
    }

    if (empty($role_id)) {
        return false;
    }

    // All other roles: pure DB permission check
    return get_db_permission($role_id, $permission, $can);
}

/**
 * DB-based permission check for any role.
 * Checks user_privileges table directly — no developer_mode.
 */
function get_db_permission($role_id, $permission, $can = '')
{
    static $perm_cache = [];
    $cache_key = $role_id . '_' . $permission . '_' . $can;
    if (isset($perm_cache[$cache_key])) {
        return $perm_cache[$cache_key];
    }

    if (empty($role_id)) {
        return false;
    }

    $permissions = get_user_permissions($role_id);
    foreach ($permissions as $p) {
        if ($p->permission_prefix == $permission) {
            if (empty($can) || $p->$can == '1') {
                $perm_cache[$cache_key] = true;
                return true;
            }
        }
    }

    $perm_cache[$cache_key] = false;
    return false;
}

function get_user_permissions($id)
{
    static $user_perms = [];
    if (isset($user_perms[$id])) {
        return $user_perms[$id];
    }

    $ci      = &get_instance();
    $version = (int) get_global_setting('permission_cache_version');
    $cache_file = APPPATH . 'cache/permissions/role_' . (int) $id . '_v' . $version . '.json';

    // Multi-request cache: a single JSON read replaces the JOIN-based DB query
    // for every page load. Version-bumped via invalidate_permission_cache() when
    // permissions are edited so stale data is impossible.
    if ($version > 0 && is_file($cache_file)) {
        $payload = @file_get_contents($cache_file);
        if ($payload !== false) {
            $decoded = json_decode($payload);
            if (is_array($decoded)) {
                $user_perms[$id] = $decoded;
                return $user_perms[$id];
            }
        }
    }

    $ci->db->select('user_privileges.*, permission.id as permission_id, permission.prefix as permission_prefix');
    $ci->db->from('user_privileges');
    $ci->db->join('permission', 'permission.id = user_privileges.permission_id');
    $ci->db->where('user_privileges.role_id', $id);
    $result = $ci->db->get()->result();

    $user_perms[$id] = $result;

    if ($version > 0) {
        $dir = dirname($cache_file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($cache_file, json_encode($result), LOCK_EX);
    }

    return $user_perms[$id];
}

/**
 * Bump the permission-cache version so every cached role file becomes stale on
 * the next request. Call this whenever user_privileges rows change (role
 * permission save, role delete, module/permission add).
 */
function invalidate_permission_cache()
{
    $ci = &get_instance();
    if (!$ci->db->table_exists('global_settings')) {
        return;
    }
    // Ensure the column exists BEFORE selecting it (self-heals older DBs).
    if (!$ci->db->field_exists('permission_cache_version', 'global_settings')) {
        $ci->load->dbforge();
        $ci->dbforge->add_column('global_settings', [
            'permission_cache_version' => [
                'type' => 'BIGINT', 'unsigned' => TRUE, 'default' => 0, 'null' => FALSE,
            ],
        ]);
    }
    $row = $ci->db->select('id, permission_cache_version')
        ->from('global_settings')
        ->limit(1)
        ->get()
        ->row();
    if (!$row) {
        return;
    }
    $next = (int) ($row->permission_cache_version ?? 0) + 1;
    $ci->db->where('id', $row->id)->update('global_settings', [
        'permission_cache_version' => $next,
    ]);
    // Refresh the global-settings JSON cache so the bumped version is visible
    // to subsequent requests (get_global_setting reads from that file).
    if (function_exists('update_global_settings_cache')) {
        update_global_settings_cache();
    }
    // Best-effort prune of on-disk cache files.
    $dir = APPPATH . 'cache/permissions/';
    if (is_dir($dir)) {
        foreach ((array) glob($dir . '*.json') as $f) {
            @unlink($f);
        }
    }
}
