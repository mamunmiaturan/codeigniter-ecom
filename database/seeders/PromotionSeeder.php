<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Promotions
 * @author   : Mamun Mia Turan
 * @filename : PromotionSeeder.php
 *
 * Idempotent bootstrap for cart price rules + catalog price rules:
 *   php index.php migrate seed PromotionSeeder
 *
 * Ensures the rule tables, registers the `cart_rule` + `catalog_rule`
 * permissions under a Promotions module, grants them to Superman & Admin, seeds
 * sample rules, builds the catalog price index, and refreshes sidebars.
 */
class PromotionSeeder extends Seeder
{
    public function run()
    {
        $this->_create_tables();
        $module_id = $this->_seed_module();
        $this->_seed_permissions($module_id);
        $this->_seed_privileges();
        $this->_seed_sample_data();
        $this->_reindex();
        $this->_refresh_sidebar();
        echo "PromotionSeeder finished." . PHP_EOL;
    }

    private function _create_tables()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `cart_rules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `sort_order` int NOT NULL DEFAULT 0,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `min_subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `category_id` int DEFAULT NULL,
  `action_type` enum('percentage','fixed','free_shipping') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `max_discount` decimal(12,2) DEFAULT NULL,
  `free_shipping` tinyint(1) NOT NULL DEFAULT 0,
  `usage_limit` int DEFAULT NULL,
  `usage_limit_per_user` int DEFAULT NULL,
  `used_count` int NOT NULL DEFAULT 0,
  `end_other_rules` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cart_rules_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `cart_rule_usages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cart_rule_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `guest_token` varchar(64) DEFAULT NULL,
  `order_id` int DEFAULT NULL,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cru_rule` (`cart_rule_id`),
  KEY `idx_cru_user` (`user_id`),
  CONSTRAINT `fk_cru_rule` FOREIGN KEY (`cart_rule_id`) REFERENCES `cart_rules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `catalog_rules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `sort_order` int NOT NULL DEFAULT 0,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `scope` enum('all','category','product') NOT NULL DEFAULT 'all',
  `category_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `action_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `end_other_rules` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_catalog_rules_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `catalog_rule_product_prices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `catalog_rule_id` int NOT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `computed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_crpp_product` (`product_id`),
  KEY `idx_crpp_rule` (`catalog_rule_id`),
  CONSTRAINT `fk_crpp_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
        echo "promotion tables ensured." . PHP_EOL;
    }

    private function _seed_module()
    {
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'promotions'])->row();
        if (!$mod) {
            $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
            $this->db->insert('permission_modules', [
                'name' => 'Promotions', 'prefix' => 'promotions', 'system' => 1, 'sorted' => $sorted, 'created_at' => date('Y-m-d H:i:s'),
            ]);
            return (int) $this->db->insert_id();
        }
        return (int) $mod->id;
    }

    private function _seed_permissions($module_id)
    {
        $perms = [
            ['prefix' => 'cart_rule', 'name' => 'Cart Rules'],
            ['prefix' => 'catalog_rule', 'name' => 'Catalog Rules'],
        ];
        foreach ($perms as $p) {
            if (!$this->db->get_where('permission', ['prefix' => $p['prefix']])->row()) {
                $this->db->insert('permission', [
                    'module_id' => $module_id, 'name' => $p['name'], 'prefix' => $p['prefix'],
                    'show_view' => 1, 'show_add' => 1, 'show_edit' => 1, 'show_delete' => 1,
                ]);
            }
        }
        echo "promotion permissions ensured." . PHP_EOL;
    }

    private function _seed_privileges()
    {
        foreach (['cart_rule', 'catalog_rule'] as $prefix) {
            $perm = $this->db->get_where('permission', ['prefix' => $prefix])->row();
            if (!$perm) {
                continue;
            }
            foreach ([1, 2] as $role_id) {
                if (!$this->db->get_where('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id])->num_rows()) {
                    $this->db->insert('user_privileges', [
                        'role_id' => $role_id, 'permission_id' => $perm->id,
                        'is_view' => 1, 'is_add' => 1, 'is_edit' => 1, 'is_delete' => 1,
                    ]);
                }
            }
        }
        echo "promotion privileges granted to Superman & Admin." . PHP_EOL;
    }

    private function _seed_sample_data()
    {
        $now = date('Y-m-d H:i:s');
        if ((int) $this->db->count_all('cart_rules') === 0) {
            $this->db->insert('cart_rules', ['name' => '10% off orders over ৳2000', 'description' => 'Automatic 10% discount, capped at ৳500.', 'status' => 'Active', 'sort_order' => 1, 'min_subtotal' => 2000, 'action_type' => 'percentage', 'discount_value' => 10, 'max_discount' => 500, 'created_at' => $now]);
            $this->db->insert('cart_rules', ['name' => 'Free shipping over ৳3000', 'description' => 'Free delivery on orders above ৳3000.', 'status' => 'Active', 'sort_order' => 2, 'min_subtotal' => 3000, 'action_type' => 'free_shipping', 'free_shipping' => 1, 'created_at' => $now]);
        }
        if ((int) $this->db->count_all('catalog_rules') === 0) {
            $elec = $this->db->get_where('categories', ['slug' => 'electronics'])->row();
            $this->db->insert('catalog_rules', ['name' => 'Electronics 5% off', 'description' => 'Catalog-wide 5% off electronics.', 'status' => 'Active', 'sort_order' => 1, 'scope' => $elec ? 'category' : 'all', 'category_id' => $elec ? (int) $elec->id : null, 'action_type' => 'percentage', 'discount_value' => 5, 'created_at' => $now]);
        }
        echo "promotion sample rules ensured." . PHP_EOL;
    }

    private function _reindex()
    {
        $this->ci->load->model('catalog_rule_model');
        $n = $this->ci->catalog_rule_model->reindex();
        echo "catalog price index rebuilt ({$n} products)." . PHP_EOL;
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
