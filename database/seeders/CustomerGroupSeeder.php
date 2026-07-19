<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Customers
 * @author   : Mamun Mia Turan
 * @filename : CustomerGroupSeeder.php
 *
 * Idempotent bootstrap for customer groups:
 *   php index.php migrate seed CustomerGroupSeeder
 *
 * Ensures customer_groups, adds users.customer_group_id (+FK) and
 * cart_rules.customer_group_id (group-scoped rule condition), registers the
 * `customer_group` permission, seeds General/Wholesale/VIP groups + a
 * group-discount cart rule per non-default group, and refreshes sidebars.
 */
class CustomerGroupSeeder extends Seeder
{
    public function run()
    {
        $this->_create_table();
        $this->_add_columns();
        $this->_seed_permission();
        $this->_seed_groups();
        $this->_refresh_sidebar();
        echo "CustomerGroupSeeder finished." . PHP_EOL;
    }

    private function _create_table()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `customer_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_customer_groups_code` (`code`),
  KEY `idx_customer_groups_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
        echo "customer_groups table ensured." . PHP_EOL;
    }

    private function _add_columns()
    {
        if (!$this->db->field_exists('customer_group_id', 'users')) {
            $this->db->query("ALTER TABLE `users` ADD COLUMN `customer_group_id` int NULL");
        }
        if (!$this->_fk_exists('fk_users_customer_group')) {
            $this->db->query("ALTER TABLE `users` ADD CONSTRAINT `fk_users_customer_group` FOREIGN KEY (`customer_group_id`) REFERENCES `customer_groups` (`id`) ON DELETE SET NULL");
        }
        if ($this->db->table_exists('cart_rules') && !$this->db->field_exists('customer_group_id', 'cart_rules')) {
            $this->db->query("ALTER TABLE `cart_rules` ADD COLUMN `customer_group_id` int NULL AFTER `category_id`");
        }
        echo "users/cart_rules customer_group columns ensured." . PHP_EOL;
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
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'customers'])->row();
        if (!$mod) {
            $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
            $this->db->insert('permission_modules', ['name' => 'Customers', 'prefix' => 'customers', 'system' => 1, 'sorted' => $sorted, 'created_at' => date('Y-m-d H:i:s')]);
            $module_id = (int) $this->db->insert_id();
        } else {
            $module_id = (int) $mod->id;
        }
        // Two permissions live under the Customers module: the customer LIST
        // (prefix `customer`) and Customer Groups (prefix `customer_group`).
        $perms = [
            ['name' => 'Customers',       'prefix' => 'customer'],
            ['name' => 'Customer Groups', 'prefix' => 'customer_group'],
        ];
        foreach ($perms as $p) {
            if (!$this->db->get_where('permission', ['prefix' => $p['prefix']])->row()) {
                $this->db->insert('permission', ['module_id' => $module_id, 'name' => $p['name'], 'prefix' => $p['prefix'], 'show_view' => 1, 'show_add' => 1, 'show_edit' => 1, 'show_delete' => 1]);
            } else {
                // Keep an already-existing permission attached to this module.
                $this->db->where('prefix', $p['prefix'])->update('permission', ['module_id' => $module_id]);
            }
            $perm = $this->db->get_where('permission', ['prefix' => $p['prefix']])->row();
            if ($perm) {
                foreach ([1, 2] as $role_id) {
                    if (!$this->db->get_where('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id])->num_rows()) {
                        $this->db->insert('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id, 'is_view' => 1, 'is_add' => 1, 'is_edit' => 1, 'is_delete' => 1]);
                    }
                }
            }
        }
        echo "customer + customer_group permissions ensured." . PHP_EOL;
    }

    private function _seed_groups()
    {
        $now = date('Y-m-d H:i:s');
        $groups = [
            ['name' => 'General', 'code' => 'general', 'discount' => 0, 'default' => 1],
            ['name' => 'Wholesale', 'code' => 'wholesale', 'discount' => 5, 'default' => 0],
            ['name' => 'VIP', 'code' => 'vip', 'discount' => 10, 'default' => 0],
        ];
        foreach ($groups as $g) {
            if (!$this->db->get_where('customer_groups', ['code' => $g['code']])->row()) {
                $this->db->insert('customer_groups', ['name' => $g['name'], 'code' => $g['code'], 'discount_percent' => $g['discount'], 'is_default' => $g['default'], 'status' => 'Active', 'created_at' => $now]);
            }
        }

        // Backfill: customers with no group get the default group.
        $default = $this->db->get_where('customer_groups', ['code' => 'general'])->row();
        if ($default) {
            $role = defined('ROLE_CUSTOMER_ID') ? ROLE_CUSTOMER_ID : 6;
            $this->db->query("UPDATE users u JOIN login_credential lc ON lc.user_id = u.id SET u.customer_group_id = ? WHERE u.customer_group_id IS NULL AND lc.role = ?", [(int) $default->id, $role]);
        }

        // Legacy cleanup: an earlier design created a shadow "<group> group discount"
        // cart rule per group. Group discounts are now applied LIVE from
        // customer_groups.discount_percent by Cart_rule_model::evaluate() (single
        // source of truth), so remove those auto rules to avoid double-applying.
        if ($this->db->table_exists('cart_rules') && $this->db->field_exists('customer_group_id', 'cart_rules')) {
            $this->db->query("DELETE FROM cart_rules WHERE customer_group_id IS NOT NULL AND name LIKE '% group discount'");
        }
        echo "customer groups ensured (live discount_percent; legacy shadow rules removed)." . PHP_EOL;
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
