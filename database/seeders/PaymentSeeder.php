<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Payment
 * @author   : Mamun Mia Turan
 * @filename : PaymentSeeder.php
 *
 * Idempotent bootstrap for the payment layer:
 *   php index.php migrate seed PaymentSeeder
 *
 * Ensures payment_settings/payment_transactions, adds orders.payment_gateway +
 * transaction_id, widens orders/carts.payment_method to varchar, seeds the
 * default method rows (cod + mock active, sslcommerz inactive), registers the
 * `payment` permission, grants it to Superman & Admin, and refreshes sidebars.
 */
class PaymentSeeder extends Seeder
{
    public function run()
    {
        $this->_create_tables();
        $this->_add_columns();
        $this->_seed_permission();
        $this->_seed_privileges();
        $this->_seed_settings();
        $this->_refresh_sidebar();
        echo "PaymentSeeder finished." . PHP_EOL;
    }

    private function _create_tables()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `payment_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(60) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `title` varchar(150) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT 0,
  `config` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payment_settings_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `gateway` varchar(60) NOT NULL,
  `transaction_id` varchar(191) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `status` varchar(30) NOT NULL,
  `payload` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payment_txn_order` (`order_id`),
  KEY `idx_payment_txn_txnid` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
        echo "payment tables ensured." . PHP_EOL;
    }

    private function _add_columns()
    {
        if (!$this->db->field_exists('payment_gateway', 'orders')) {
            $this->db->query("ALTER TABLE `orders` ADD COLUMN `payment_gateway` VARCHAR(60) NULL AFTER `payment_method`");
        }
        if (!$this->db->field_exists('transaction_id', 'orders')) {
            $this->db->query("ALTER TABLE `orders` ADD COLUMN `transaction_id` VARCHAR(191) NULL AFTER `payment_gateway`");
        }
        // Widen payment_method from enum('cod','online') to varchar so the registry
        // can use codes like 'sslcommerz' / 'mock'. MODIFY is safe to re-run.
        $this->db->query("ALTER TABLE `orders` MODIFY `payment_method` VARCHAR(30) NOT NULL DEFAULT 'cod'");
        if (!$this->db->field_exists('payment_method', 'carts')) {
            $this->db->query("ALTER TABLE `carts` ADD COLUMN `payment_method` VARCHAR(30) NULL AFTER `shipping_method`");
        }
        echo "orders/carts payment columns ensured." . PHP_EOL;
    }

    private function _seed_permission()
    {
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'payment'])->row();
        if (!$mod) {
            $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
            $this->db->insert('permission_modules', [
                'name' => 'Payment', 'prefix' => 'payment', 'system' => 1, 'sorted' => $sorted, 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $module_id = (int) $this->db->insert_id();
        } else {
            $module_id = (int) $mod->id;
        }
        // Payment is a parent menu with two independently-gated sub-pages:
        //   Payment Methods (prefix `payment_method`, the dynamic gateway config)
        //   and Transactions (prefix `payment_transaction`, a read-only log).
        $permissions = [
            ['name' => 'Payment Methods', 'prefix' => 'payment_method', 'edit' => 1],
            ['name' => 'Transactions',    'prefix' => 'payment_transaction', 'edit' => 0],
        ];
        foreach ($permissions as $p) {
            if (!$this->db->get_where('permission', ['prefix' => $p['prefix']])->row()) {
                $this->db->insert('permission', [
                    'module_id' => $module_id, 'name' => $p['name'], 'prefix' => $p['prefix'],
                    'show_view' => 1, 'show_add' => 0, 'show_edit' => $p['edit'], 'show_delete' => 0,
                ]);
            }
        }

        // Retire the legacy single `payment` permission (superseded by the two above).
        $legacy = $this->db->get_where('permission', ['prefix' => 'payment'])->row();
        if ($legacy) {
            $this->db->delete('user_privileges', ['permission_id' => $legacy->id]);
            $this->db->delete('permission', ['id' => $legacy->id]);
        }
        echo "payment_method + payment_transaction permissions ensured." . PHP_EOL;
    }

    private function _seed_privileges()
    {
        $perms = $this->db->where_in('prefix', ['payment_method', 'payment_transaction'])->get('permission')->result();
        foreach ($perms as $perm) {
            $is_edit = ($perm->prefix === 'payment_method') ? 1 : 0;
            foreach ([1, 2] as $role_id) {
                if (!$this->db->get_where('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id])->num_rows()) {
                    $this->db->insert('user_privileges', [
                        'role_id' => $role_id, 'permission_id' => $perm->id,
                        'is_view' => 1, 'is_add' => 0, 'is_edit' => $is_edit, 'is_delete' => 0,
                    ]);
                }
            }
        }
        echo "payment privileges granted to Superman & Admin." . PHP_EOL;
    }

    private function _seed_settings()
    {
        $defaults = [
            ['code' => 'cod',        'is_active' => 1, 'title' => 'Cash on Delivery',                    'sort_order' => 1, 'config' => null],
            ['code' => 'sslcommerz', 'is_active' => 0, 'title' => 'Card / Mobile Banking (SSLCommerz)',  'sort_order' => 2, 'config' => json_encode(['sandbox' => true, 'store_id' => '', 'store_passwd' => ''])],
            ['code' => 'mock',       'is_active' => 1, 'title' => 'Test Online Payment (Mock)',          'sort_order' => 9, 'config' => null],
        ];
        foreach ($defaults as $d) {
            if (!$this->db->get_where('payment_settings', ['code' => $d['code']])->row()) {
                $d['created_at'] = date('Y-m-d H:i:s');
                $this->db->insert('payment_settings', $d);
            }
        }
        echo "payment_settings seeded (cod+mock active, sslcommerz inactive)." . PHP_EOL;
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
