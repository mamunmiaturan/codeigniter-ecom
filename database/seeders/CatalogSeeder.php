<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog
 * @author   : Mamun Mia Turan
 * @filename : CatalogSeeder.php
 *
 * Fully idempotent bootstrap for the catalog module. Safe to run repeatedly and
 * against the live (dump-bootstrapped, migration-version-0) database:
 *   php index.php migrate seed CatalogSeeder
 *
 * Ensures schema (CREATE TABLE IF NOT EXISTS), registers the Catalog permission
 * module + category/brand/product permissions, grants them to Superman & Admin,
 * seeds sample data, prepares upload dirs, and refreshes the compiled sidebars.
 */
class CatalogSeeder extends Seeder
{

    public function run()
    {
        $this->_create_tables();
        $module_id = $this->_seed_permissions();
        $this->_seed_privileges();
        $this->_ensure_upload_dirs();
        // The old sample demo catalog (electronics / home & kitchen / groceries)
        // is no longer seeded — this install is a women & children clothing /
        // fashion store, whose catalog is seeded by WomenChildCatalogSeeder.
        $this->_refresh_sidebar();
        echo "CatalogSeeder finished (module id {$module_id})." . PHP_EOL;
    }

    private function _create_tables()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `parent_id` int DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int NOT NULL DEFAULT 0,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categories_slug` (`slug`),
  KEY `idx_categories_parent` (`parent_id`),
  KEY `idx_categories_status` (`status`),
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `brands` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int NOT NULL DEFAULT 0,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_brands_slug` (`slug`),
  KEY `idx_brands_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(280) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `category_id` int DEFAULT NULL,
  `brand_id` int DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `special_price` decimal(12,2) DEFAULT NULL,
  `cost_price` decimal(12,2) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'BDT',
  `stock_quantity` int NOT NULL DEFAULT 0,
  `stock_status` enum('in_stock','out_of_stock','pre_order') NOT NULL DEFAULT 'in_stock',
  `unit` varchar(50) DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT NULL,
  `has_variants` tinyint(1) NOT NULL DEFAULT 0,
  `thumbnail` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Draft','Active','Inactive') NOT NULL DEFAULT 'Draft',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_products_slug` (`slug`),
  KEY `idx_products_sku` (`sku`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_brand` (`brand_id`),
  KEY `idx_products_status` (`status`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_products_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `product_variants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `name` varchar(150) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `special_price` decimal(12,2) DEFAULT NULL,
  `stock_quantity` int NOT NULL DEFAULT 0,
  `attributes` json DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_variants_product` (`product_id`),
  CONSTRAINT `fk_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT 0,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_images_product` (`product_id`),
  CONSTRAINT `fk_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        echo "Catalog tables ensured." . PHP_EOL;
    }

    private function _seed_permissions()
    {
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'catalog'])->row();
        if (!$mod) {
            $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
            $this->db->insert('permission_modules', [
                'name' => 'Catalog',
                'prefix' => 'catalog',
                'system' => 1,
                'sorted' => $sorted,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $module_id = (int) $this->db->insert_id();
        } else {
            $module_id = (int) $mod->id;
        }

        $perms = [
            ['prefix' => 'category', 'name' => 'Category'],
            ['prefix' => 'brand', 'name' => 'Brand'],
            ['prefix' => 'product', 'name' => 'Product'],
        ];
        foreach ($perms as $p) {
            if (!$this->db->get_where('permission', ['prefix' => $p['prefix']])->row()) {
                $this->db->insert('permission', [
                    'module_id' => $module_id,
                    'name' => $p['name'],
                    'prefix' => $p['prefix'],
                    'show_view' => 1,
                    'show_add' => 1,
                    'show_edit' => 1,
                    'show_delete' => 1,
                ]);
            }
        }
        echo "Catalog permissions ensured." . PHP_EOL;
        return $module_id;
    }

    private function _seed_privileges()
    {
        foreach (['category', 'brand', 'product'] as $prefix) {
            $perm = $this->db->get_where('permission', ['prefix' => $prefix])->row();
            if (!$perm) {
                continue;
            }
            foreach ([1, 2] as $role_id) { // Superman + Admin
                $exists = $this->db->get_where('user_privileges', [
                    'role_id' => $role_id,
                    'permission_id' => $perm->id,
                ])->num_rows();
                if (!$exists) {
                    $this->db->insert('user_privileges', [
                        'role_id' => $role_id,
                        'permission_id' => $perm->id,
                        'is_view' => 1,
                        'is_add' => 1,
                        'is_edit' => 1,
                        'is_delete' => 1,
                    ]);
                }
            }
        }
        echo "Catalog privileges granted to Superman & Admin." . PHP_EOL;
    }

    private function _ensure_upload_dirs()
    {
        foreach (['category', 'brand', 'product'] as $sub) {
            $dir = FCPATH . 'uploads/catalog/' . $sub . '/';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * Rebuild the compiled per-role sidebars so the new Catalog menu appears.
     * Best-effort in CLI; on any failure the stale compiled files are removed so
     * layout/sidebar.php regenerates them on the next web request.
     */
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
