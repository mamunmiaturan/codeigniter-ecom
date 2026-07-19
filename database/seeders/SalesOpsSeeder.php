<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Sales Ops
 * @author   : Mamun Mia Turan
 * @filename : SalesOpsSeeder.php
 *
 * Idempotent bootstrap for order ops (invoices/shipments/refunds under the
 * existing `order` permission) + returns/RMA (its own `rma` permission):
 *   php index.php migrate seed SalesOpsSeeder
 */
class SalesOpsSeeder extends Seeder
{
    public function run()
    {
        $this->_create_tables();
        $this->_seed_permission();
        $this->_seed_privileges();
        $this->_refresh_sidebar();
        echo "SalesOpsSeeder finished." . PHP_EOL;
    }

    private function _create_tables()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `order_invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `invoice_number` varchar(40) NOT NULL,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_invoice_number` (`invoice_number`),
  KEY `idx_invoice_order` (`order_id`),
  CONSTRAINT `fk_invoice_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `order_shipments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `carrier` varchar(120) NOT NULL,
  `tracking_number` varchar(120) DEFAULT NULL,
  `tracking_url` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `shipped_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_shipment_order` (`order_id`),
  CONSTRAINT `fk_shipment_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `order_refunds` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `refunded_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_refund_order` (`order_id`),
  CONSTRAINT `fk_refund_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `return_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `rma_number` varchar(40) NOT NULL,
  `order_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `guest_token` varchar(64) DEFAULT NULL,
  `reason` varchar(150) DEFAULT NULL,
  `customer_note` text DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `status` enum('requested','approved','rejected','received','refunded','cancelled') NOT NULL DEFAULT 'requested',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rma_number` (`rma_number`),
  KEY `idx_return_order` (`order_id`),
  KEY `idx_return_user` (`user_id`),
  KEY `idx_return_status` (`status`),
  CONSTRAINT `fk_return_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `return_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `return_request_id` int NOT NULL,
  `order_item_id` int DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_return_item_req` (`return_request_id`),
  CONSTRAINT `fk_return_item_req` FOREIGN KEY (`return_request_id`) REFERENCES `return_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
        echo "sales ops tables ensured." . PHP_EOL;
    }

    private function _seed_permission()
    {
        // Returns/RMA gets its own permission under the existing Orders module.
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'orders'])->row();
        if (!$mod) {
            $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
            $this->db->insert('permission_modules', ['name' => 'Orders', 'prefix' => 'orders', 'system' => 1, 'sorted' => $sorted, 'created_at' => date('Y-m-d H:i:s')]);
            $module_id = (int) $this->db->insert_id();
        } else {
            $module_id = (int) $mod->id;
        }
        if (!$this->db->get_where('permission', ['prefix' => 'rma'])->row()) {
            $this->db->insert('permission', [
                'module_id' => $module_id, 'name' => 'Returns / RMA', 'prefix' => 'rma',
                'show_view' => 1, 'show_add' => 0, 'show_edit' => 1, 'show_delete' => 1,
            ]);
        }
        echo "rma permission ensured." . PHP_EOL;
    }

    private function _seed_privileges()
    {
        $perm = $this->db->get_where('permission', ['prefix' => 'rma'])->row();
        if (!$perm) {
            return;
        }
        foreach ([1, 2] as $role_id) {
            if (!$this->db->get_where('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id])->num_rows()) {
                $this->db->insert('user_privileges', [
                    'role_id' => $role_id, 'permission_id' => $perm->id,
                    'is_view' => 1, 'is_add' => 0, 'is_edit' => 1, 'is_delete' => 1,
                ]);
            }
        }
        echo "rma privileges granted to Superman & Admin." . PHP_EOL;
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
