<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Audit log visibility: Superman/Admin see all; Branch Manager sees own branch scope only.
 */

if (!function_exists('audit_logs_view_unrestricted')) {
    function audit_logs_view_unrestricted(): bool
    {
        $role_id = (int) loggedin_role_id();
        return in_array($role_id, [ROLE_SUPERMAN_ID, ROLE_ADMIN_ID], true);
    }
}

if (!function_exists('audit_logs_can_view_system')) {
    /** System / PHP error logs are for Superman and Admin only. */
    function audit_logs_can_view_system(): bool
    {
        return audit_logs_view_unrestricted();
    }
}

if (!function_exists('audit_logs_branch_scope')) {
    /**
     * @return null|array{branch_id:int,user_ids:int[]}
     */
    function audit_logs_branch_scope(): ?array
    {
        if (audit_logs_view_unrestricted()) {
            return null;
        }

        if ((int) loggedin_role_id() !== ROLE_BRANCH_ID) {
            return ['branch_id' => 0, 'user_ids' => []];
        }

        $branch_id = loggedin_branch_id();
        if (!$branch_id) {
            return ['branch_id' => 0, 'user_ids' => []];
        }

        return [
            'branch_id' => (int) $branch_id,
            'user_ids'  => audit_logs_scoped_user_ids((int) $branch_id),
        ];
    }
}

if (!function_exists('audit_logs_scoped_user_ids')) {
    /**
     * User IDs whose audit trail a branch manager may see: self + kitchen/rider/customer on branch.
     */
    function audit_logs_scoped_user_ids(int $branch_id): array
    {
        static $cache = [];
        if (isset($cache[$branch_id])) {
            return $cache[$branch_id];
        }

        $ci = &get_instance();
        $ids = [];

        if ((int) loggedin_role_id() === ROLE_BRANCH_ID) {
            $manager_id = (int) get_loggedin_user_id();
            if ($manager_id > 0) {
                $ids[] = $manager_id;
            }
        }

        $ci->db->select('u.id');
        $ci->db->from('users u');
        $ci->db->join('login_credential lc', 'lc.user_id = u.id', 'inner');
        $ci->db->where('u.branch_id', $branch_id);
        $ci->db->where_in('lc.role', [ROLE_KITCHEN_ID, ROLE_RIDER_ID, ROLE_CUSTOMER_ID]);
        if ($ci->db->field_exists('deleted_at', 'users')) {
            $ci->db->where('u.deleted_at IS NULL', null, false);
        }
        foreach ($ci->db->get()->result_array() as $row) {
            $ids[] = (int) $row['id'];
        }

        $cache[$branch_id] = array_values(array_unique(array_filter($ids)));
        return $cache[$branch_id];
    }
}

if (!function_exists('audit_activity_log_visible')) {
    function audit_activity_log_visible(array $log): bool
    {
        if (audit_logs_view_unrestricted()) {
            return true;
        }

        $scope = audit_logs_branch_scope();
        if ($scope === null) {
            return true;
        }

        $user_ids  = $scope['user_ids'];
        $branch_id = (int) $scope['branch_id'];

        if ($branch_id < 1 || $user_ids === []) {
            return false;
        }

        if (!empty($log['branch_id']) && (int) $log['branch_id'] === $branch_id) {
            return true;
        }

        $actor = (int) ($log['user_id'] ?? 0);
        if ($actor > 0 && in_array($actor, $user_ids, true)) {
            return true;
        }

        $module = (string) ($log['module_name'] ?? $log['table_name'] ?? '');
        if (in_array($module, ['user', 'customer'], true)) {
            $target = (int) ($log['row_id'] ?? 0);
            if ($target > 0 && in_array($target, $user_ids, true)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('audit_email_log_visible')) {
    function audit_email_log_visible(array $log): bool
    {
        if (audit_logs_view_unrestricted()) {
            return true;
        }

        $scope = audit_logs_branch_scope();
        if ($scope === null) {
            return true;
        }

        $branch_id = (int) ($scope['branch_id'] ?? 0);
        if ($branch_id < 1) {
            return false;
        }

        if (!empty($log['branch_id']) && (int) $log['branch_id'] === $branch_id) {
            return true;
        }

        $recipient = trim((string) ($log['recipient'] ?? ''));
        if ($recipient !== '') {
            $ci = &get_instance();
            $row = $ci->db->select('u.id')
                ->from('users u')
                ->join('login_credential lc', 'lc.user_id = u.id', 'inner')
                ->where('lc.email', $recipient)
                ->where('u.branch_id', $branch_id)
                ->where_in('lc.role', [ROLE_KITCHEN_ID, ROLE_RIDER_ID, ROLE_CUSTOMER_ID, ROLE_BRANCH_ID])
                ->limit(1)
                ->get()
                ->row();
            if ($row && in_array((int) $row->id, $scope['user_ids'], true)) {
                return true;
            }
            if ($row && (int) loggedin_role_id() === ROLE_BRANCH_ID && (int) $row->id === (int) get_loggedin_user_id()) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('audit_filter_activity_logs')) {
    function audit_filter_activity_logs(array $logs): array
    {
        if (audit_logs_view_unrestricted()) {
            return $logs;
        }
        return array_values(array_filter($logs, 'audit_activity_log_visible'));
    }
}

if (!function_exists('audit_filter_email_logs')) {
    function audit_filter_email_logs(array $logs): array
    {
        if (audit_logs_view_unrestricted()) {
            return $logs;
        }
        return array_values(array_filter($logs, 'audit_email_log_visible'));
    }
}
