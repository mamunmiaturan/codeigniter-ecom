<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Reports & Analytics
 * @author   : Mamun Mia Turan
 * @filename : ReportSeeder.php
 *
 * Idempotent bootstrap for the Reports module. It is a read-only surface, so
 * there is NO table and NO sample data — only the permission module, a
 * view-only permission, role privileges (Superman & Admin) and a sidebar
 * refresh:
 *   php index.php migrate seed ReportSeeder
 */
class ReportSeeder extends Seeder
{
    public function run()
    {
        $this->_seed_permission();
        $this->_seed_privileges();
        $this->_refresh_sidebar();
        echo "ReportSeeder finished." . PHP_EOL;
    }

    private function _seed_permission()
    {
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'report'])->row();
        if (!$mod) {
            $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
            $this->db->insert('permission_modules', ['name' => 'Reports', 'prefix' => 'report', 'system' => 1, 'sorted' => $sorted, 'created_at' => date('Y-m-d H:i:s')]);
            $module_id = (int) $this->db->insert_id();
        } else {
            $module_id = (int) $mod->id;
        }
        // View-only module: no add / edit / delete actions.
        if (!$this->db->get_where('permission', ['prefix' => 'report'])->row()) {
            $this->db->insert('permission', [
                'module_id' => $module_id, 'name' => 'Reports & Analytics', 'prefix' => 'report',
                'show_view' => 1, 'show_add' => 0, 'show_edit' => 0, 'show_delete' => 0,
            ]);
        }
        echo "report permission ensured." . PHP_EOL;
    }

    private function _seed_privileges()
    {
        $perm = $this->db->get_where('permission', ['prefix' => 'report'])->row();
        if (!$perm) {
            return;
        }
        foreach ([1, 2] as $role_id) {
            if (!$this->db->get_where('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id])->num_rows()) {
                $this->db->insert('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id, 'is_view' => 1, 'is_add' => 0, 'is_edit' => 0, 'is_delete' => 0]);
            }
        }
        echo "report privileges granted to Superman & Admin." . PHP_EOL;
    }

    private function _refresh_sidebar()
    {
        try {
            require_once APPPATH . 'helpers/sidebar_helper.php';
            $this->ci->load->helper(['url', 'general', 'permission', 'translation']);
            if (function_exists('generate_sidebar_files')) {
                generate_sidebar_files();
                echo "Sidebar files regenerated." . PHP_EOL;
                return;
            }
        } catch (Throwable $e) {
            echo "Sidebar regen deferred: " . $e->getMessage() . PHP_EOL;
        }
        foreach (glob(APPPATH . 'views/layout/sidebar/*.php') as $file) {
            @unlink($file);
        }
    }
}
