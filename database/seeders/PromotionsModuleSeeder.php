<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Promotions
 * @author   : Mamun Mia Turan
 * @filename : PromotionsModuleSeeder.php
 *
 * Groups Coupons under the "Promotions" permission module so the Module &
 * Permission page mirrors the sidebar's Promotions menu (Coupons, Cart Price
 * Rules, Catalog Price Rules).
 *
 * CouponSeeder registers a standalone one-permission "Coupons" module; this
 * seeder runs after it (and after PromotionSeeder): it re-parents the `coupon`
 * permission to the Promotions module, then removes the now-empty Coupons
 * module. Idempotent.
 *
 *   php index.php system/migrate seed PromotionsModuleSeeder
 */
class PromotionsModuleSeeder extends Seeder
{
    public function run()
    {
        $module_id = $this->_ensure_promotions_module();
        // Re-parent the Coupons permission to the Promotions module.
        $this->db->where('prefix', 'coupon')->update('permission', ['module_id' => $module_id]);
        $this->_drop_empty_module('coupons');
        echo "Coupons grouped under the Promotions module." . PHP_EOL;
    }

    private function _ensure_promotions_module()
    {
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'promotions'])->row();
        if ($mod) {
            return (int) $mod->id;
        }
        $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
        $this->db->insert('permission_modules', [
            'name' => 'Promotions', 'prefix' => 'promotions', 'system' => 1,
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
