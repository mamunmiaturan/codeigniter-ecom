<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SidebarPermissionsSeeder extends Seeder
{
    public function run()
    {
        $source = APPPATH . 'helpers/sidebar_helper.php';
        if (!is_file($source)) {
            if (is_cli()) {
                echo "SidebarPermissionsSeeder skipped: sidebar helper not found." . PHP_EOL;
            }
            return;
        }

        require_once APPPATH . 'helpers/sidebar_helper.php';
        if (!function_exists('get_raw_sidebar_structure')) {
            if (is_cli()) {
                echo "SidebarPermissionsSeeder skipped: get_raw_sidebar_structure function not found." . PHP_EOL;
            }
            return;
        }

        $sidebar_structure = get_raw_sidebar_structure();

        $modules = [];
        $permission_to_module = [];
        $permission_names = [];

        // Helper function to map sidebar parents to database modules
        $get_module_info = function ($raw_name) {
            $clean_name = str_replace('_', ' ', $raw_name);
            $lower = strtolower($raw_name);
            switch ($lower) {
                case 'audit_logs':
                    return ['audit_log', 'Audit Logs'];
                case 'branch_setup':
                    return ['branches', 'Branch Setup'];
                case 'meal_setup':
                    return ['meal_setup', 'Meal Setup'];
                case 'meal_bookings':
                    return ['meal_bookings', 'Meal Bookings'];
                case 'kitchen':
                    return ['kitchen', 'Kitchen'];
                case 'deliveries':
                    return ['deliveries', 'Deliveries'];
                case 'finance':
                    return ['finance', 'Finance'];
                case 'marketing':
                    return ['marketing', 'Marketing'];
                case 'referrals':
                    return ['referrals', 'Referrals'];
                case 'food_donations':
                    return ['food_donations', 'Food Donations'];
                case 'support':
                    return ['support', 'Support'];
                case 'settings':
                    return ['settings', 'Settings'];
                case 'inventory':
                    return ['inventory', 'Inventory'];
                case 'customers':
                    return ['customer', 'Customers'];
                case 'users':
                    return ['user', 'Users'];
                case 'appearance_settings':
                    return ['appearance', 'Appearance'];
                default:
                    return [strtolower($raw_name), $clean_name];
            }
        };

        // 1. Process parent items first
        foreach ($sidebar_structure as $item) {
            if (isset($item['type']) && $item['type'] === 'parent') {
                list($module_prefix, $module_name) = $get_module_info($item['name']);
                $modules[$module_prefix] = $module_name;

                if (isset($item['children']) && is_array($item['children'])) {
                    foreach ($item['children'] as $child) {
                        if (isset($child['permission']) && is_array($child['permission'])) {
                            $perm_prefix = $child['permission'][0];
                            $permission_to_module[$perm_prefix] = $module_prefix;
                            $permission_names[$perm_prefix] = ucwords(str_replace('_', ' ', $child['name']));
                        }
                    }
                }
            }
        }

        // 2. Process top-level link items
        foreach ($sidebar_structure as $item) {
            if (isset($item['type']) && $item['type'] === 'link') {
                if (isset($item['permission']) && is_array($item['permission'])) {
                    $perm_prefix = $item['permission'][0];
                    // Skip if already mapped under a parent module
                    if (isset($permission_to_module[$perm_prefix])) {
                        continue;
                    }
                    $module_prefix = $perm_prefix;
                    $module_name = ucwords(str_replace('_', ' ', $item['name']));
                    $modules[$module_prefix] = $module_name;
                    $permission_to_module[$perm_prefix] = $module_prefix;
                    $permission_names[$perm_prefix] = $module_name;
                }
            }
        }

        $module_sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted;
        $created_modules = 0;
        $created_permissions = 0;
        $updated_permissions = 0;

        // Keep track of module IDs we use/create
        $module_ids = [];

        foreach ($modules as $prefix => $name) {
            $module = $this->db->get_where('permission_modules', ['prefix' => $prefix])->row();
            if (!$module) {
                $module_sorted++;
                $this->db->insert('permission_modules', [
                    'name'       => $name,
                    'prefix'     => $prefix,
                    'system'     => 1,
                    'sorted'     => $module_sorted,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $module_id = (int) $this->db->insert_id();
                $created_modules++;
            } else {
                $module_id = (int) $module->id;
                // Update module name to match sidebar grouping
                $this->db->where('id', $module_id)->update('permission_modules', ['name' => $name]);
            }
            $module_ids[$prefix] = $module_id;
        }

        foreach ($permission_to_module as $perm_prefix => $module_prefix) {
            $module_id = $module_ids[$module_prefix];
            $perm_name = $permission_names[$perm_prefix];

            $permission = $this->db->get_where('permission', ['prefix' => $perm_prefix])->row();
            $payload = [
                'module_id' => $module_id,
                'name'      => $perm_name,
                'prefix'    => $perm_prefix,
            ];

            if (!$permission) {
                $payload['show_view']   = 1;
                $payload['show_add']    = 1;
                $payload['show_edit']   = 1;
                $payload['show_delete'] = 1;
                $this->db->insert('permission', $payload);
                $created_permissions++;
            } else {
                $this->db->where('id', (int) $permission->id)->update('permission', $payload);
                $updated_permissions++;
            }
        }

        // Garbage collect: Delete empty permission modules that are no longer used
        $this->db->query("DELETE FROM permission_modules WHERE id NOT IN (SELECT DISTINCT module_id FROM permission)");

        if (function_exists('invalidate_permission_cache')) {
            invalidate_permission_cache();
        }
        if (function_exists('generate_sidebar_files')) {
            generate_sidebar_files();
        }

        if (is_cli()) {
            echo "SidebarPermissionsSeeder Finished. Modules created {$created_modules}, permissions created {$created_permissions}, updated {$updated_permissions}." . PHP_EOL;
        }
    }
}
