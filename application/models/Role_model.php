<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Role_model.php
 */

class Role_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // role save and update function
    public function save_roles($data)
    {
        $insertData = array(
            'name' => $data['role'],
            'prefix' => strtolower(str_replace(' ', '', $data['role'])),
        );

        // Dynamic hierarchy fields (guarded so the model still works if the
        // columns have not been added yet).
        if ($this->db->field_exists('level', 'roles') && isset($data['level']) && $data['level'] !== '') {
            $insertData['level'] = max(0, (int) $data['level']);
        }
        if ($this->db->field_exists('parent_id', 'roles')) {
            // Empty / 0 parent means "no parent" (top-level).
            $parent = isset($data['parent_id']) ? (int) $data['parent_id'] : 0;
            $insertData['parent_id'] = $parent > 0 ? $parent : null;
        }

        if (!isset($data['id']) || empty($data['id'])) {
            $insertData['is_system'] = 0;
            $this->db->insert('roles', $insertData);
            return $this->db->insert_id();
        }

        $this->db->where('id', $data['id']);
        // A role can never be its own parent.
        if (isset($insertData['parent_id']) && (int) $insertData['parent_id'] === (int) $data['id']) {
            $insertData['parent_id'] = null;
        }
        $this->db->update('roles', $insertData);
        return (int) $data['id'];
    }

    /**
     * Authority level for a role id (lower number = more authority).
     * Falls back to the role id itself when the level column is absent, which
     * preserves the legacy id-based ordering.
     */
    public function get_role_level(int $role_id): int
    {
        if ($role_id === ROLE_SUPERMAN_ID) {
            return 0;
        }
        if (!$this->db->field_exists('level', 'roles')) {
            return $role_id;
        }
        $row = $this->db->select('level')->where('id', $role_id)->get('roles')->row();
        return $row ? (int) $row->level : PHP_INT_MAX;
    }

    /**
     * Flat list of all (non-deleted) roles ordered by level — used to populate
     * the "Parent Role" dropdown.
     * @return array<int, array<string,mixed>>
     */
    public function get_all_roles(): array
    {
        $order_col = $this->db->field_exists('level', 'roles') ? 'level' : 'id';
        $this->db->order_by($order_col, 'ASC')->order_by('id', 'ASC');
        if ($this->db->field_exists('deleted_at', 'roles')) {
            $this->db->where('deleted_at IS NULL', null, false);
        }
        $roles = $this->db->get('roles')->result_array();

        // Hierarchical visibility: hide every role ABOVE the viewer's own level
        // (lower level number = higher authority; Superman is level 0 and sees
        // all). This also keeps Superman a ghost for everyone below it.
        if ($this->db->field_exists('level', 'roles')) {
            $viewer_level = role_level((int) loggedin_role_id());
            $roles = array_values(array_filter($roles, static function (array $r) use ($viewer_level) {
                return (int) $r['level'] >= $viewer_level;
            }));
        } elseif (!is_superman_loggedin()) {
            $roles = array_values(array_filter($roles, static function (array $r) {
                return (int) $r['id'] !== ROLE_SUPERMAN_ID;
            }));
        }
        return $roles;
    }

    /**
     * Full role list as a parent/child tree for the organogram, ordered by level.
     * @return array<int, array<string,mixed>> top-level roles, each with a 'children' key
     */
    public function get_role_tree(): array
    {
        $order_col = $this->db->field_exists('level', 'roles') ? 'level' : 'id';
        $this->db->order_by($order_col, 'ASC')->order_by('id', 'ASC');
        if ($this->db->field_exists('deleted_at', 'roles')) {
            $this->db->where('deleted_at IS NULL', null, false);
        }
        $roles = $this->db->get('roles')->result_array();

        // Hierarchical visibility: hide every role ABOVE the viewer's own level.
        // Removing them promotes the viewer's own role to a top-level root when
        // the tree is built (its now-hidden parent no longer resolves).
        if ($this->db->field_exists('level', 'roles')) {
            $viewer_level = role_level((int) loggedin_role_id());
            $roles = array_values(array_filter($roles, static function (array $r) use ($viewer_level) {
                return (int) $r['level'] >= $viewer_level;
            }));
        } elseif (!is_superman_loggedin()) {
            $roles = array_values(array_filter($roles, static function (array $r) {
                return (int) $r['id'] !== ROLE_SUPERMAN_ID;
            }));
        }

        $has_parent = $this->db->field_exists('parent_id', 'roles');
        $by_id = [];
        foreach ($roles as $r) {
            $r['children'] = [];
            $by_id[(int) $r['id']] = $r;
        }
        $tree = [];
        foreach ($by_id as $id => &$node) {
            $pid = $has_parent ? (int) ($node['parent_id'] ?? 0) : 0;
            if ($pid > 0 && isset($by_id[$pid]) && $pid !== $id) {
                $by_id[$pid]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }
        unset($node);
        return $tree;
    }

    // check permissions function
    public function check_permissions($module_id = '', $role_id = '')
    {
        $this->db->select('permission.*, user_privileges.id as user_privileges_id, user_privileges.is_add, user_privileges.is_edit, user_privileges.is_view, user_privileges.is_delete');
        $this->db->from('permission');
        $this->db->join('user_privileges', 'user_privileges.permission_id = permission.id AND user_privileges.role_id = ' . $this->db->escape($role_id), 'left');
        $this->db->where('permission.module_id', $module_id);
        $this->db->order_by('permission.id', 'ASC');
        return $this->db->get()->result_array();
    }

    /**
     * Check if a role name is unique
     */
    public function check_unique_name($name, $id = null)
    {
        if ($id) {
            $where = array('name' => $name, 'id != ' => $id);
        } else {
            $where = array('name' => $name);
        }
        return $this->db->get_where('roles', $where)->num_rows() > 0;
    }

    /**
     * Check if any user is assigned to a specific role
     */
    public function is_role_assigned($role_id)
    {
        $this->db->where('role', $role_id);
        return $this->db->get('login_credential')->num_rows() > 0;
    }

    /**
     * Delete a role
     */
    public function delete_role($role_id)
    {
        $this->db->where('id', $role_id);
        return $this->db->delete('roles');
    }

    /**
     * Get list of permission modules ordered alphabetically
     */
    public function get_permission_modules()
    {
        return $this->db->order_by('name', 'ASC')->get('permission_modules')->result_array();
    }

    /**
     * Roles the logged-in user may manage (strictly lower privilege = higher numeric id).
     * Hides own role and every role above (id <= logged-in role id).
     */
    public function get_roles_for_manager(int $loggedin_role_id): array
    {
        $loggedin_role_id = (int) $loggedin_role_id;

        $has_level = $this->db->field_exists('level', 'roles');
        $this->db->order_by($has_level ? 'level' : 'id', 'ASC')->order_by('id', 'ASC');
        if ($this->db->field_exists('deleted_at', 'roles')) {
            $this->db->where('deleted_at IS NULL', null, false);
        }
        $roles = $this->db->get('roles')->result_array();

        // A manager sees roles strictly below their own authority level (higher
        // level number = lower authority). Superman (level 0) sees everyone else.
        $manager_level = $has_level ? $this->get_role_level($loggedin_role_id) : $loggedin_role_id;

        return array_values(array_filter($roles, static function (array $role) use ($manager_level, $has_level, $loggedin_role_id) {
            if ((int) $role['id'] === ROLE_SUPERMAN_ID) {
                // Ghost Superman: only Superman itself may see the Superman role.
                return $loggedin_role_id === ROLE_SUPERMAN_ID;
            }
            $role_level = $has_level ? (int) $role['level'] : (int) $role['id'];
            return $role_level > $manager_level;
        }));
    }

    /**
     * Whether a manager may edit/delete/assign permissions for a target role.
     */
    public function can_manage_role(int $loggedin_role_id, int $target_role_id): bool
    {
        $target_role_id   = (int) $target_role_id;
        $loggedin_role_id = (int) $loggedin_role_id;

        if ($target_role_id === ROLE_SUPERMAN_ID) {
            return false;
        }

        if ($loggedin_role_id === ROLE_SUPERMAN_ID) {
            return true;
        }

        // Level-based hierarchy: a role may manage any role with a strictly
        // higher level number (= lower authority). Falls back to id comparison
        // when the level column is absent.
        if ($this->db->field_exists('level', 'roles')) {
            return $this->get_role_level($target_role_id) > $this->get_role_level($loggedin_role_id);
        }

        return $target_role_id > $loggedin_role_id;
    }

    /**
     * @return array<int, array{is_view:int,is_add:int,is_edit:int,is_delete:int,has_any:bool}>
     */
    public function get_granter_privilege_map(int $granter_role_id): array
    {
        if ((int) $granter_role_id === ROLE_SUPERMAN_ID) {
            return [];
        }

        $this->db->select('permission_id, is_view, is_add, is_edit, is_delete');
        $this->db->from('user_privileges');
        $this->db->where('role_id', (int) $granter_role_id);
        $rows = $this->db->get()->result_array();

        $map = [];
        foreach ($rows as $row) {
            $is_view   = (int) $row['is_view'];
            $is_add    = (int) $row['is_add'];
            $is_edit   = (int) $row['is_edit'];
            $is_delete = (int) $row['is_delete'];
            if ($is_view + $is_add + $is_edit + $is_delete < 1) {
                continue;
            }
            $map[(int) $row['permission_id']] = [
                'is_view'   => $is_view,
                'is_add'    => $is_add,
                'is_edit'   => $is_edit,
                'is_delete' => $is_delete,
                'has_any'   => true,
            ];
        }

        return $map;
    }

    /**
     * Modules that contain at least one permission the granter holds.
     */
    public function get_permission_modules_for_granter(int $granter_role_id): array
    {
        if ((int) $granter_role_id === ROLE_SUPERMAN_ID) {
            return $this->get_permission_modules();
        }

        $grantable_ids = array_keys($this->get_granter_privilege_map($granter_role_id));
        if ($grantable_ids === []) {
            return [];
        }

        $this->db->distinct();
        $this->db->select('permission_modules.*');
        $this->db->from('permission_modules');
        $this->db->join('permission', 'permission.module_id = permission_modules.id');
        $this->db->where_in('permission.id', $grantable_ids);
        $this->db->order_by('permission_modules.name', 'ASC');

        return $this->db->get()->result_array();
    }

    /**
     * Target role permissions limited to what the granter may assign.
     */
    public function check_permissions_for_granter(int $module_id, int $target_role_id, int $granter_role_id): array
    {
        $permissions = $this->check_permissions($module_id, $target_role_id);

        if ((int) $granter_role_id === ROLE_SUPERMAN_ID) {
            return $permissions;
        }

        $granter_map = $this->get_granter_privilege_map($granter_role_id);
        $filtered    = [];

        foreach ($permissions as $permission) {
            $pid = (int) $permission['id'];
            if (!isset($granter_map[$pid])) {
                continue;
            }

            $granter = $granter_map[$pid];
            $permission['show_view']   = !empty($permission['show_view']) && $granter['is_view'];
            $permission['show_add']    = !empty($permission['show_add']) && $granter['is_add'];
            $permission['show_edit']   = !empty($permission['show_edit']) && $granter['is_edit'];
            $permission['show_delete'] = !empty($permission['show_delete']) && $granter['is_delete'];

            if (!$permission['show_view'] && !$permission['show_add'] && !$permission['show_edit'] && !$permission['show_delete']) {
                continue;
            }

            $filtered[] = $permission;
        }

        return $filtered;
    }

    /**
     * Save privileges; non-superman may only grant permissions/actions they already have.
     */
    public function save_privileges($role_id, $privileges, $granter_role_id = null)
    {
        $role_id = (int) $role_id;
        $privileges = is_array($privileges) ? $privileges : [];
        $granter_role_id = $granter_role_id !== null ? (int) $granter_role_id : ROLE_SUPERMAN_ID;

        if ($granter_role_id !== ROLE_SUPERMAN_ID) {
            $granter_map = $this->get_granter_privilege_map($granter_role_id);
            $sanitized   = [];

            foreach ($privileges as $permission_id => $value) {
                $permission_id = (int) $permission_id;
                if (!isset($granter_map[$permission_id]) || !is_array($value)) {
                    continue;
                }

                $granter = $granter_map[$permission_id];
                $sanitized[$permission_id] = [
                    'view'   => (isset($value['view']) && $granter['is_view']) ? 1 : 0,
                    'add'    => (isset($value['add']) && $granter['is_add']) ? 1 : 0,
                    'edit'   => (isset($value['edit']) && $granter['is_edit']) ? 1 : 0,
                    'delete' => (isset($value['delete']) && $granter['is_delete']) ? 1 : 0,
                ];
            }

            $privileges = $sanitized;
        }

        $modules = ($granter_role_id === ROLE_SUPERMAN_ID)
            ? $this->get_permission_modules()
            : $this->get_permission_modules_for_granter($granter_role_id);

        $permission_rows = [];
        foreach ($modules as $module) {
            $rows = $this->check_permissions_for_granter((int) $module['id'], $role_id, $granter_role_id);
            foreach ($rows as $permission) {
                $permission_rows[(int) $permission['id']] = $permission;
            }
        }

        $this->db->trans_start();
        foreach ($permission_rows as $permission_id => $permission) {
            $value = $privileges[$permission_id] ?? [];
            $is_add    = !empty($value['add']) ? 1 : 0;
            $is_edit   = !empty($value['edit']) ? 1 : 0;
            $is_view   = !empty($value['view']) ? 1 : 0;
            $is_delete = !empty($value['delete']) ? 1 : 0;
            $arrayData = [
                'role_id'         => $role_id,
                'permission_id'   => $permission_id,
                'is_add'          => $is_add,
                'is_edit'         => $is_edit,
                'is_view'         => $is_view,
                'is_delete'       => $is_delete,
            ];
            $exist_privileges = $this->db->select('id')->limit(1)
                ->where(['role_id' => $role_id, 'permission_id' => $permission_id])
                ->get('user_privileges')->num_rows();
            if ($exist_privileges > 0) {
                $this->db->update('user_privileges', $arrayData, ['role_id' => $role_id, 'permission_id' => $permission_id]);
            } else {
                $this->db->insert('user_privileges', $arrayData);
            }
        }
        $this->db->trans_complete();
        if ($this->db->trans_status() !== FALSE) {
            $this->session->unset_userdata('role_permissions_' . $role_id);
            if (function_exists('invalidate_permission_cache')) {
                invalidate_permission_cache();
            }
        }
        return $this->db->trans_status();
    }
}
