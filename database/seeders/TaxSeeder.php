<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Tax
 * @author   : Mamun Mia Turan
 * @filename : TaxSeeder.php
 *
 * Idempotent bootstrap for the tax engine:
 *   php index.php migrate seed TaxSeeder
 *
 * Ensures tax tables, adds products.tax_category_id (+FK), registers the `tax`
 * permission, grants it to Superman & Admin, seeds a default Standard category
 * with a BD VAT 15% rate, tags the sample products, and refreshes sidebars.
 */
class TaxSeeder extends Seeder
{
    public function run()
    {
        $this->_create_tables();
        $this->_add_product_column();
        $this->_seed_permission();
        $this->_seed_privileges();
        $this->_seed_sample_data();
        $this->_refresh_sidebar();
        echo "TaxSeeder finished." . PHP_EOL;
    }

    private function _create_tables()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `tax_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tax_categories_code` (`code`),
  KEY `idx_tax_categories_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `tax_rates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `identifier` varchar(80) NOT NULL,
  `name` varchar(120) NOT NULL,
  `country` char(2) NOT NULL DEFAULT 'BD',
  `state` varchar(100) NOT NULL DEFAULT '*',
  `postcode` varchar(30) NOT NULL DEFAULT '*',
  `rate` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `priority` int NOT NULL DEFAULT 0,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tax_rates_identifier` (`identifier`),
  KEY `idx_tax_rates_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `tax_category_rates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tax_category_id` int NOT NULL,
  `tax_rate_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tax_cat_rate` (`tax_category_id`,`tax_rate_id`),
  CONSTRAINT `fk_tcr_category` FOREIGN KEY (`tax_category_id`) REFERENCES `tax_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tcr_rate` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
        echo "tax tables ensured." . PHP_EOL;
    }

    private function _add_product_column()
    {
        if (!$this->db->field_exists('tax_category_id', 'products')) {
            $this->db->query("ALTER TABLE `products` ADD COLUMN `tax_category_id` int NULL AFTER `brand_id`");
        }
        if (!$this->_fk_exists('fk_products_tax_category')) {
            $this->db->query("ALTER TABLE `products` ADD CONSTRAINT `fk_products_tax_category` FOREIGN KEY (`tax_category_id`) REFERENCES `tax_categories` (`id`) ON DELETE SET NULL");
        }
        echo "products.tax_category_id ensured." . PHP_EOL;
    }

    private function _fk_exists($name)
    {
        return $this->db->query(
            "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$name]
        )->num_rows() > 0;
    }

    private function _seed_permission()
    {
        // Tax is a parent menu with two independently-gated sub-pages:
        //   Tax Categories (prefix `tax_category`) and Tax Rates (prefix `tax_rate`).
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'tax'])->row();
        if (!$mod) {
            $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
            $this->db->insert('permission_modules', [
                'name' => 'Tax', 'prefix' => 'tax', 'system' => 1, 'sorted' => $sorted, 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $module_id = (int) $this->db->insert_id();
        } else {
            $module_id = (int) $mod->id;
        }

        $permissions = [
            ['name' => 'Tax Categories', 'prefix' => 'tax_category'],
            ['name' => 'Tax Rates',      'prefix' => 'tax_rate'],
        ];
        foreach ($permissions as $p) {
            if (!$this->db->get_where('permission', ['prefix' => $p['prefix']])->row()) {
                $this->db->insert('permission', [
                    'module_id' => $module_id, 'name' => $p['name'], 'prefix' => $p['prefix'],
                    'show_view' => 1, 'show_add' => 1, 'show_edit' => 1, 'show_delete' => 1,
                ]);
            }
        }

        // Retire the legacy single `tax` permission (superseded by the two above).
        $legacy = $this->db->get_where('permission', ['prefix' => 'tax'])->row();
        if ($legacy) {
            $this->db->delete('user_privileges', ['permission_id' => $legacy->id]);
            $this->db->delete('permission', ['id' => $legacy->id]);
        }
        echo "tax_category + tax_rate permissions ensured." . PHP_EOL;
    }

    private function _seed_privileges()
    {
        $perms = $this->db->where_in('prefix', ['tax_category', 'tax_rate'])->get('permission')->result();
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
        echo "tax privileges granted to Superman & Admin." . PHP_EOL;
    }

    private function _seed_sample_data()
    {
        $now = date('Y-m-d H:i:s');

        // Default "Standard" category + a "Zero" category.
        if (!$this->db->get_where('tax_categories', ['code' => 'standard'])->row()) {
            $this->db->insert('tax_categories', ['code' => 'standard', 'name' => 'Standard', 'description' => 'Standard VAT-rated goods.', 'is_default' => 1, 'status' => 'Active', 'created_at' => $now]);
        }
        if (!$this->db->get_where('tax_categories', ['code' => 'zero'])->row()) {
            $this->db->insert('tax_categories', ['code' => 'zero', 'name' => 'Zero / Exempt', 'description' => 'VAT-exempt goods.', 'is_default' => 0, 'status' => 'Active', 'created_at' => $now]);
        }
        $standard = $this->db->get_where('tax_categories', ['code' => 'standard'])->row();

        // BD VAT 15% rate (nationwide) mapped to Standard.
        if (!$this->db->get_where('tax_rates', ['identifier' => 'bd-vat-15'])->row()) {
            $this->db->insert('tax_rates', ['identifier' => 'bd-vat-15', 'name' => 'Bangladesh VAT 15%', 'country' => 'BD', 'state' => '*', 'postcode' => '*', 'rate' => 15.0000, 'priority' => 0, 'status' => 'Active', 'created_at' => $now]);
        }
        $rate = $this->db->get_where('tax_rates', ['identifier' => 'bd-vat-15'])->row();

        if ($standard && $rate) {
            if (!$this->db->get_where('tax_category_rates', ['tax_category_id' => $standard->id, 'tax_rate_id' => $rate->id])->row()) {
                $this->db->insert('tax_category_rates', ['tax_category_id' => $standard->id, 'tax_rate_id' => $rate->id]);
            }
            // Tag sample products (those with no category yet) as Standard so tax is demonstrable.
            $this->db->where('tax_category_id', null)->update('products', ['tax_category_id' => $standard->id]);
        }
        echo "tax sample data ensured (Standard 15% VAT; sample products tagged)." . PHP_EOL;
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
