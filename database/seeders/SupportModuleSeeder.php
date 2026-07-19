<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Support
 * @author   : Mamun Mia Turan
 * @filename : SupportModuleSeeder.php
 *
 * Groups customer-support features under a "Support" permission module so the
 * Module & Permission page mirrors the sidebar's Support menu. Currently that
 * is Contact Messages; more support tools can be added here later.
 *
 * ContactSeeder registers a standalone one-permission "Contact Messages"
 * module; this seeder runs after it: it creates the Support module, re-parents
 * the `contact` permission to it, then removes the now-empty Contact Messages
 * module. Idempotent.
 *
 *   php index.php system/migrate seed SupportModuleSeeder
 */
class SupportModuleSeeder extends Seeder
{
    public function run()
    {
        $module_id = $this->_ensure_support_module();
        // Re-parent the Contact Messages permission to the Support module.
        $this->db->where('prefix', 'contact')->update('permission', ['module_id' => $module_id]);
        $this->_drop_empty_module('contact');
        echo "Contact Messages grouped under the Support module." . PHP_EOL;
    }

    private function _ensure_support_module()
    {
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'support'])->row();
        if ($mod) {
            return (int) $mod->id;
        }
        $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
        $this->db->insert('permission_modules', [
            'name' => 'Support', 'prefix' => 'support', 'system' => 1,
            'sorted' => $sorted, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insert_id();
    }

    private function _drop_empty_module($prefix)
    {
        $m = $this->db->get_where('permission_modules', ['prefix' => $prefix])->row();
        if (!$m) {
            return;
        }
        $remaining = $this->db->where('module_id', $m->id)->count_all_results('permission');
        if ($remaining == 0) {
            $this->db->delete('permission_modules', ['id' => $m->id]);
        }
    }
}
