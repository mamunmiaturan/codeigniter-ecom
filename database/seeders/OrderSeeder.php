<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Orders (OMS)
 * @author   : Mamun Mia Turan
 * @filename : OrderSeeder.php
 *
 * Idempotent bootstrap for the order module: ensures schema, registers the
 * Order permission module + `order` permission, grants Superman & Admin, and
 * refreshes the compiled sidebars. Safe to run repeatedly:
 *   php index.php migrate seed OrderSeeder
 */
class OrderSeeder extends Seeder {

    public function run()
    {
        $this->_create_tables();
        $module_id = $this->_seed_permissions();
        $this->_seed_privileges();
        $this->_refresh_sidebar();
        echo "OrderSeeder finished (module id {$module_id})." . PHP_EOL;
    }

    private function _create_tables()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_number` varchar(32) NOT NULL,
  `user_id` int DEFAULT NULL,
  `guest_token` varchar(64) DEFAULT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_phone` varchar(30) NOT NULL,
  `customer_email` varchar(150) DEFAULT NULL,
  `shipping_division` varchar(100) DEFAULT NULL,
  `shipping_district` varchar(100) DEFAULT NULL,
  `shipping_area` varchar(150) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `shipping_landmark` varchar(255) DEFAULT NULL,
  `shipping_postcode` varchar(20) DEFAULT NULL,
  `payment_method` enum('cod','online') NOT NULL DEFAULT 'cod',
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `status` enum('pending','confirmed','processing','shipped','delivered','completed','cancelled','returned') NOT NULL DEFAULT 'pending',
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `shipping_charge` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency` char(3) NOT NULL DEFAULT 'BDT',
  `item_count` int NOT NULL DEFAULT 0,
  `note` text DEFAULT NULL,
  `placed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_orders_number` (`order_number`),
  KEY `idx_orders_user` (`user_id`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `variant_id` int DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_slug` varchar(280) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `variant_name` varchar(150) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `quantity` int NOT NULL DEFAULT 1,
  `line_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_product` (`product_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `order_status_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `status` varchar(30) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `changed_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_osh_order` (`order_id`),
  CONSTRAINT `fk_osh_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        echo "Order tables ensured." . PHP_EOL;
    }

    private function _seed_permissions()
    {
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'orders'])->row();
        if (!$mod) {
            $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
            $this->db->insert('permission_modules', [
                'name'       => 'Orders',
                'prefix'     => 'orders',
                'system'     => 1,
                'sorted'     => $sorted,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $module_id = (int) $this->db->insert_id();
        } else {
            $module_id = (int) $mod->id;
        }

        if (!$this->db->get_where('permission', ['prefix' => 'order'])->row()) {
            $this->db->insert('permission', [
                'module_id'   => $module_id,
                'name'        => 'Order',
                'prefix'      => 'order',
                'show_view'   => 1,
                'show_add'    => 0,
                'show_edit'   => 1,
                'show_delete' => 1,
            ]);
        }
        echo "Order permission ensured." . PHP_EOL;
        return $module_id;
    }

    private function _seed_privileges()
    {
        $perm = $this->db->get_where('permission', ['prefix' => 'order'])->row();
        if (!$perm) {
            return;
        }
        foreach ([1, 2] as $role_id) {
            $exists = $this->db->get_where('user_privileges', [
                'role_id'       => $role_id,
                'permission_id' => $perm->id,
            ])->num_rows();
            if (!$exists) {
                $this->db->insert('user_privileges', [
                    'role_id'       => $role_id,
                    'permission_id' => $perm->id,
                    'is_view'       => 1,
                    'is_add'        => 0,
                    'is_edit'       => 1,
                    'is_delete'     => 1,
                ]);
            }
        }
        echo "Order privileges granted to Superman & Admin." . PHP_EOL;
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
