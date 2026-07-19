<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Promotions
 * @author   : Mamun Mia Turan
 * @filename : CouponSeeder.php
 *
 * Idempotent bootstrap for coupons: ensures schema + the carts.coupon_code
 * column, registers the Coupon permission, seeds a few sample codes, and
 * refreshes sidebars. Run repeatedly:  php index.php migrate seed CouponSeeder
 */
class CouponSeeder extends Seeder {

    public function run()
    {
        $this->_create_tables();
        $module_id = $this->_seed_permissions();
        $this->_seed_privileges();
        $this->_seed_samples();
        $this->_refresh_sidebar();
        echo "CouponSeeder finished (module id {$module_id})." . PHP_EOL;
    }

    private function _create_tables()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `type` enum('percentage','fixed','free_shipping') NOT NULL DEFAULT 'percentage',
  `value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `min_order_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `max_discount_amount` decimal(12,2) DEFAULT NULL,
  `usage_limit` int DEFAULT NULL,
  `usage_limit_per_user` int DEFAULT NULL,
  `used_count` int NOT NULL DEFAULT 0,
  `starts_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_coupons_code` (`code`),
  KEY `idx_coupons_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `coupon_usages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `coupon_id` int NOT NULL,
  `order_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `guest_token` varchar(64) DEFAULT NULL,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cu_coupon` (`coupon_id`),
  KEY `idx_cu_user` (`user_id`),
  CONSTRAINT `fk_cu_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        if ($this->db->table_exists('carts') && !$this->db->field_exists('coupon_code', 'carts')) {
            $this->db->query("ALTER TABLE `carts` ADD `coupon_code` VARCHAR(50) NULL AFTER `guest_token`");
        }
        if ($this->db->table_exists('orders') && !$this->db->field_exists('coupon_code', 'orders')) {
            $this->db->query("ALTER TABLE `orders` ADD `coupon_code` VARCHAR(50) NULL AFTER `discount`");
        }
        echo "Coupon tables ensured." . PHP_EOL;
    }

    private function _seed_permissions()
    {
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'coupons'])->row();
        if (!$mod) {
            $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
            $this->db->insert('permission_modules', [
                'name'       => 'Coupons',
                'prefix'     => 'coupons',
                'system'     => 1,
                'sorted'     => $sorted,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $module_id = (int) $this->db->insert_id();
        } else {
            $module_id = (int) $mod->id;
        }
        if (!$this->db->get_where('permission', ['prefix' => 'coupon'])->row()) {
            $this->db->insert('permission', [
                'module_id'   => $module_id,
                'name'        => 'Coupon',
                'prefix'      => 'coupon',
                'show_view'   => 1,
                'show_add'    => 1,
                'show_edit'   => 1,
                'show_delete' => 1,
            ]);
        }
        echo "Coupon permission ensured." . PHP_EOL;
        return $module_id;
    }

    private function _seed_privileges()
    {
        $perm = $this->db->get_where('permission', ['prefix' => 'coupon'])->row();
        if (!$perm) {
            return;
        }
        foreach ([1, 2] as $role_id) {
            if (!$this->db->get_where('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id])->num_rows()) {
                $this->db->insert('user_privileges', [
                    'role_id'       => $role_id,
                    'permission_id' => $perm->id,
                    'is_view'       => 1,
                    'is_add'        => 1,
                    'is_edit'       => 1,
                    'is_delete'     => 1,
                ]);
            }
        }
        echo "Coupon privileges granted to Superman & Admin." . PHP_EOL;
    }

    private function _seed_samples()
    {
        $now = date('Y-m-d H:i:s');
        $samples = [
            ['code' => 'SAVE10',   'description' => '10% off (max ৳2000)', 'type' => 'percentage',    'value' => 10, 'min_order_amount' => 0,   'max_discount_amount' => 2000],
            ['code' => 'FLAT100',  'description' => '৳100 off over ৳500',   'type' => 'fixed',         'value' => 100, 'min_order_amount' => 500, 'max_discount_amount' => null],
            ['code' => 'FREESHIP', 'description' => 'Free shipping',         'type' => 'free_shipping', 'value' => 0,  'min_order_amount' => 0,   'max_discount_amount' => null],
        ];
        foreach ($samples as $c) {
            if (!$this->db->get_where('coupons', ['code' => $c['code']])->row()) {
                $this->db->insert('coupons', array_merge($c, ['status' => 'Active', 'created_at' => $now]));
            }
        }
        echo "Sample coupons ensured (SAVE10, FLAT100, FREESHIP)." . PHP_EOL;
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
            echo "Sidebar regen deferred to next page load: " . $e->getMessage() . PHP_EOL;
        }
        foreach (glob(APPPATH . 'views/layout/sidebar/*.php') as $file) {
            @unlink($file);
        }
    }
}
