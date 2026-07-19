<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog (Multi-Source Inventory)
 * @author   : Mamun Mia Turan
 * @filename : InventorySeeder.php
 *
 * Idempotent bootstrap for multi-source inventory (ported from Bagisto), additive:
 *   php index.php migrate seed InventorySeeder
 *
 * `inventory_sources` = warehouses/locations. `product_inventories` = per-source
 * stock (the NEW source of truth, variant_id=0 for base products). Existing
 * products.stock_quantity becomes a CACHED ROLLUP = SUM of active-source qty, so
 * every current stock check keeps working. A default source is seeded and each
 * product's current stock_quantity is backfilled into it, so on day one the
 * rollup equals the existing totals. `order_stock_allocations` audits what each
 * order drew from which source (for restock on cancel/return).
 */
class InventorySeeder extends Seeder
{
    public function run()
    {
        $this->_create_tables();
        $this->_seed_permission();
        $this->_seed_default_source();
        $this->_backfill_stock();
        $this->_refresh_sidebar();
        echo "InventorySeeder finished." . PHP_EOL;
    }

    private function _create_tables()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `inventory_sources` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(60) NOT NULL,
  `name` varchar(120) NOT NULL,
  `priority` int NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `contact_name` varchar(120) DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `country` varchar(80) DEFAULT NULL,
  `state` varchar(80) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `postcode` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inv_sources_code` (`code`),
  KEY `idx_inv_sources_status` (`status`),
  KEY `idx_inv_sources_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `product_inventories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `variant_id` int NOT NULL DEFAULT 0,
  `inventory_source_id` int NOT NULL,
  `qty` int NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pi_product_variant_source` (`product_id`, `variant_id`, `inventory_source_id`),
  KEY `idx_pi_source` (`inventory_source_id`),
  CONSTRAINT `fk_pi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pi_source` FOREIGN KEY (`inventory_source_id`) REFERENCES `inventory_sources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `order_stock_allocations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `variant_id` int NOT NULL DEFAULT 0,
  `inventory_source_id` int NOT NULL,
  `qty` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_osa_order` (`order_id`),
  KEY `idx_osa_source` (`inventory_source_id`),
  CONSTRAINT `fk_osa_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
        echo "inventory tables ensured." . PHP_EOL;
    }

    private function _seed_permission()
    {
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'catalog'])->row();
        if (!$mod) {
            $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
            $this->db->insert('permission_modules', ['name' => 'Catalog', 'prefix' => 'catalog', 'system' => 1, 'sorted' => $sorted, 'created_at' => date('Y-m-d H:i:s')]);
            $module_id = (int) $this->db->insert_id();
        } else {
            $module_id = (int) $mod->id;
        }
        if (!$this->db->get_where('permission', ['prefix' => 'inventory_source'])->row()) {
            $this->db->insert('permission', ['module_id' => $module_id, 'name' => 'Inventory Sources', 'prefix' => 'inventory_source', 'show_view' => 1, 'show_add' => 1, 'show_edit' => 1, 'show_delete' => 1]);
        }
        $perm = $this->db->get_where('permission', ['prefix' => 'inventory_source'])->row();
        if ($perm) {
            foreach ([1, 2] as $role_id) {
                if (!$this->db->get_where('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id])->num_rows()) {
                    $this->db->insert('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id, 'is_view' => 1, 'is_add' => 1, 'is_edit' => 1, 'is_delete' => 1]);
                }
            }
        }
        echo "inventory_source permission ensured." . PHP_EOL;
    }

    private function _seed_default_source()
    {
        if (!$this->db->get_where('inventory_sources', ['code' => 'default'])->row()) {
            $this->db->insert('inventory_sources', [
                'code' => 'default', 'name' => 'Default Warehouse', 'priority' => 0, 'is_default' => 1,
                'country' => 'Bangladesh', 'city' => 'Dhaka', 'status' => 'Active', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        echo "default inventory source ensured." . PHP_EOL;
    }

    private function _backfill_stock()
    {
        $src = $this->db->get_where('inventory_sources', ['code' => 'default'])->row();
        if (!$src) {
            return;
        }
        $sid = (int) $src->id;
        // For every base product with no per-source rows yet, seed the default
        // source with its current stock_quantity so rollup == existing total.
        $rows = $this->db->query(
            "SELECT p.id, p.stock_quantity FROM products p
             WHERE p.deleted_at IS NULL
               AND NOT EXISTS (SELECT 1 FROM product_inventories pi WHERE pi.product_id = p.id AND pi.variant_id = 0)"
        )->result_array();
        $now = date('Y-m-d H:i:s');
        foreach ($rows as $r) {
            $this->db->insert('product_inventories', [
                'product_id' => (int) $r['id'], 'variant_id' => 0, 'inventory_source_id' => $sid,
                'qty' => (int) $r['stock_quantity'], 'created_at' => $now,
            ]);
        }
        echo "backfilled per-source stock for " . count($rows) . " products (default source)." . PHP_EOL;
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
