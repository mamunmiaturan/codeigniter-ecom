<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Website
 * @author   : Mamun Mia Turan
 * @filename : WebsiteModuleSeeder.php
 *
 * Consolidates the storefront/content permissions under a single "Website"
 * permission module so the Module & Permission page reflects the same grouping
 * as the sidebar's "Website" menu (Banners, Blog, CMS Pages, FAQ, Flash Sale,
 * Newsletter).
 *
 * Each of those features is seeded by its own content seeder, which registers a
 * one-permission module (Banners / Blog / CMS / FAQ / Flash Sale / Marketing).
 * This seeder runs LAST: it creates the Website module, re-parents those six
 * permissions to it, then removes the now-empty legacy modules. Idempotent.
 *
 *   php index.php system/migrate seed WebsiteModuleSeeder
 */
class WebsiteModuleSeeder extends Seeder
{
    /** Permissions that belong under the Website module. */
    private $perm_prefixes = ['banner', 'blog', 'cms', 'faq', 'flash_sale', 'newsletter'];

    /** Legacy single-permission modules to retire once their permission moves. */
    private $legacy_module_prefixes = ['banner', 'blog', 'cms', 'faq', 'flash_sale', 'marketing'];

    public function run()
    {
        $module_id = $this->_ensure_website_module();
        $this->_move_permissions($module_id);
        $this->_drop_empty_legacy_modules();
        echo "Website module consolidated (" . implode(', ', $this->perm_prefixes) . ")." . PHP_EOL;
    }

    private function _ensure_website_module()
    {
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'website'])->row();
        if ($mod) {
            return (int) $mod->id;
        }
        $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
        $this->db->insert('permission_modules', [
            'name' => 'Website', 'prefix' => 'website', 'system' => 1,
            'sorted' => $sorted, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insert_id();
    }

    private function _move_permissions($module_id)
    {
        $this->db->where_in('prefix', $this->perm_prefixes)
                 ->update('permission', ['module_id' => $module_id]);
    }

    private function _drop_empty_legacy_modules()
    {
        foreach ($this->legacy_module_prefixes as $prefix) {
            $m = $this->db->get_where('permission_modules', ['prefix' => $prefix])->row();
            if (!$m) {
                continue;
            }
            $remaining = $this->db->where('module_id', $m->id)->count_all_results('permission');
            if ($remaining == 0) {
                $this->db->delete('permission_modules', ['id' => $m->id]);
            }
        }
    }
}
