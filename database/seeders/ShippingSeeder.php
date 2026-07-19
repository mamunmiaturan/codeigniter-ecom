<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Shipping
 * @author   : Mamun Mia Turan
 * @filename : ShippingSeeder.php
 *
 * Idempotent bootstrap for the shipping engine:
 *   php index.php migrate seed ShippingSeeder
 *
 * Ensures shipping_zones/shipping_methods, adds orders.shipping_method(+label)
 * and carts.shipping_method, registers the `shipping` permission, grants it to
 * Superman & Admin, seeds sample BD zones/methods, and refreshes the sidebars.
 */
class ShippingSeeder extends Seeder
{
    public function run()
    {
        $this->_create_tables();
        $this->_add_columns();
        $this->_seed_permission();
        $this->_seed_privileges();
        $this->_seed_sample_data();
        $this->_refresh_sidebar();
        echo "ShippingSeeder finished." . PHP_EOL;
    }

    private function _create_tables()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `shipping_zones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `divisions` varchar(500) NOT NULL DEFAULT '*',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `sort_order` int NOT NULL DEFAULT 0,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_shipping_zones_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `shipping_methods` (
  `id` int NOT NULL AUTO_INCREMENT,
  `zone_id` int NOT NULL,
  `code` varchar(60) NOT NULL,
  `title` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `type` enum('flat','per_unit','free') NOT NULL DEFAULT 'flat',
  `base_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `per_unit_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `free_over` decimal(12,2) DEFAULT NULL,
  `min_days` int DEFAULT NULL,
  `max_days` int DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `sort_order` int NOT NULL DEFAULT 0,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_shipping_methods_zone` (`zone_id`),
  KEY `idx_shipping_methods_status` (`status`),
  CONSTRAINT `fk_shipping_methods_zone` FOREIGN KEY (`zone_id`) REFERENCES `shipping_zones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
        echo "shipping tables ensured." . PHP_EOL;
    }

    private function _add_columns()
    {
        if (!$this->db->field_exists('shipping_method', 'orders')) {
            $this->db->query("ALTER TABLE `orders` ADD COLUMN `shipping_method` VARCHAR(60) NULL AFTER `shipping_charge`");
        }
        if (!$this->db->field_exists('shipping_method_label', 'orders')) {
            $this->db->query("ALTER TABLE `orders` ADD COLUMN `shipping_method_label` VARCHAR(150) NULL AFTER `shipping_method`");
        }
        if (!$this->db->field_exists('shipping_method', 'carts')) {
            // coupon_code is added by CouponSeeder — anchor to it only if present.
            $after = $this->db->field_exists('coupon_code', 'carts') ? ' AFTER `coupon_code`' : '';
            $this->db->query("ALTER TABLE `carts` ADD COLUMN `shipping_method` VARCHAR(60) NULL{$after}");
        }
        echo "orders/carts shipping columns ensured." . PHP_EOL;
    }

    private function _seed_permission()
    {
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'shipping'])->row();
        if (!$mod) {
            $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
            $this->db->insert('permission_modules', [
                'name' => 'Shipping', 'prefix' => 'shipping', 'system' => 1, 'sorted' => $sorted, 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $module_id = (int) $this->db->insert_id();
        } else {
            $module_id = (int) $mod->id;
        }
        // Shipping is a parent menu with two independently-gated sub-pages:
        //   Shipping Zones (prefix `shipping_zone`) and Shipping Methods
        //   (prefix `shipping_method`).
        $permissions = [
            ['name' => 'Shipping Zones',   'prefix' => 'shipping_zone'],
            ['name' => 'Shipping Methods', 'prefix' => 'shipping_method'],
        ];
        foreach ($permissions as $p) {
            if (!$this->db->get_where('permission', ['prefix' => $p['prefix']])->row()) {
                $this->db->insert('permission', [
                    'module_id' => $module_id, 'name' => $p['name'], 'prefix' => $p['prefix'],
                    'show_view' => 1, 'show_add' => 1, 'show_edit' => 1, 'show_delete' => 1,
                ]);
            }
        }

        // Retire the legacy single `shipping` permission (superseded by the two above).
        $legacy = $this->db->get_where('permission', ['prefix' => 'shipping'])->row();
        if ($legacy) {
            $this->db->delete('user_privileges', ['permission_id' => $legacy->id]);
            $this->db->delete('permission', ['id' => $legacy->id]);
        }
        echo "shipping_zone + shipping_method permissions ensured." . PHP_EOL;
    }

    private function _seed_privileges()
    {
        $perms = $this->db->where_in('prefix', ['shipping_zone', 'shipping_method'])->get('permission')->result();
        foreach ($perms as $perm) {
            foreach ([1, 2] as $role_id) {
                if (!$this->db->get_where('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id])->num_rows()) {
                    $this->db->insert('user_privileges', [
                        'role_id' => $role_id, 'permission_id' => $perm->id,
                        'is_view' => 1, 'is_add' => 1, 'is_edit' => 1, 'is_delete' => 1,
                    ]);
                }
            }
        }
        echo "shipping privileges granted to Superman & Admin." . PHP_EOL;
    }

    private function _seed_sample_data()
    {
        if ((int) $this->db->count_all('shipping_zones') > 0) {
            echo "shipping zones already present — skipping samples." . PHP_EOL;
            return;
        }
        $now = date('Y-m-d H:i:s');

        // Inside Dhaka (specific)
        $this->db->insert('shipping_zones', ['name' => 'Inside Dhaka', 'divisions' => 'Dhaka', 'status' => 'Active', 'sort_order' => 1, 'created_at' => $now]);
        $dhaka = (int) $this->db->insert_id();
        // Rest of Bangladesh (fallback)
        $this->db->insert('shipping_zones', ['name' => 'Outside Dhaka', 'divisions' => '*', 'status' => 'Active', 'sort_order' => 10, 'created_at' => $now]);
        $rest = (int) $this->db->insert_id();

        $methods = [
            ['zone_id' => $dhaka, 'code' => 'dhaka_standard', 'title' => 'Standard Delivery', 'description' => 'Delivered in 1-2 days inside Dhaka.', 'type' => 'flat', 'base_rate' => 60, 'free_over' => 1500, 'min_days' => 1, 'max_days' => 2, 'sort_order' => 1],
            ['zone_id' => $dhaka, 'code' => 'dhaka_express', 'title' => 'Express Delivery', 'description' => 'Same-day / next-day inside Dhaka.', 'type' => 'flat', 'base_rate' => 120, 'free_over' => null, 'min_days' => 0, 'max_days' => 1, 'sort_order' => 2],
            ['zone_id' => $rest, 'code' => 'bd_standard', 'title' => 'Standard Delivery', 'description' => 'Delivered in 3-5 days nationwide.', 'type' => 'flat', 'base_rate' => 120, 'free_over' => 3000, 'min_days' => 3, 'max_days' => 5, 'sort_order' => 1],
        ];
        foreach ($methods as $m) {
            $this->db->insert('shipping_methods', array_merge($m, ['status' => 'Active', 'created_at' => $now]));
        }
        echo "shipping sample zones/methods ensured." . PHP_EOL;
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
