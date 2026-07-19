<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Flash Sale
 * @author   : Mamun Mia Turan
 * @filename : FlashSaleSeeder.php
 *
 * Idempotent bootstrap for time-boxed flash sales:
 *   php index.php migrate seed FlashSaleSeeder
 */
class FlashSaleSeeder extends Seeder
{
    public function run()
    {
        $this->_create_tables();
        $this->_seed_permission();
        $this->_seed_privileges();
        $this->_seed_sample();
        $this->_refresh_sidebar();
        echo "FlashSaleSeeder finished." . PHP_EOL;
    }

    private function _create_tables()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `flash_sales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fs_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `flash_sale_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `flash_sale_id` int NOT NULL,
  `product_id` int NOT NULL,
  `sale_price` decimal(12,2) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fs_item` (`flash_sale_id`,`product_id`),
  KEY `idx_fs_item_sale` (`flash_sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
        echo "flash_sales + flash_sale_items tables ensured." . PHP_EOL;
    }

    private function _seed_permission()
    {
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'flash_sale'])->row();
        if (!$mod) {
            $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
            $this->db->insert('permission_modules', ['name' => 'Flash Sale', 'prefix' => 'flash_sale', 'system' => 1, 'sorted' => $sorted, 'created_at' => date('Y-m-d H:i:s')]);
            $module_id = (int) $this->db->insert_id();
        } else {
            $module_id = (int) $mod->id;
        }
        if (!$this->db->get_where('permission', ['prefix' => 'flash_sale'])->row()) {
            $this->db->insert('permission', [
                'module_id' => $module_id, 'name' => 'Flash Sale', 'prefix' => 'flash_sale',
                'show_view' => 1, 'show_add' => 1, 'show_edit' => 1, 'show_delete' => 1,
            ]);
        }
        echo "flash_sale permission ensured." . PHP_EOL;
    }

    private function _seed_privileges()
    {
        $perm = $this->db->get_where('permission', ['prefix' => 'flash_sale'])->row();
        if (!$perm) {
            return;
        }
        foreach ([1, 2] as $role_id) {
            if (!$this->db->get_where('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id])->num_rows()) {
                $this->db->insert('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id, 'is_view' => 1, 'is_add' => 1, 'is_edit' => 1, 'is_delete' => 1]);
            }
        }
        echo "flash_sale privileges granted to Superman & Admin." . PHP_EOL;
    }

    private function _seed_sample()
    {
        $title = 'Weekend Flash Sale';
        $sale = $this->db->get_where('flash_sales', ['title' => $title])->row();
        if (!$sale) {
            $this->db->insert('flash_sales', [
                'title'      => $title,
                'starts_at'  => '2026-07-14 00:00:00',
                'ends_at'    => '2026-07-21 23:59:59',
                'status'     => 'Active',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $sale_id = (int) $this->db->insert_id();
        } else {
            $sale_id = (int) $sale->id;
        }

        $now   = date('Y-m-d H:i:s');
        $added = 0;
        foreach ([1, 2, 3] as $pid) {
            $product = $this->db->select('price')->get_where('products', ['id' => $pid])->row();
            if (!$product) {
                continue; // product not seeded yet — skip
            }
            if ($this->db->get_where('flash_sale_items', ['flash_sale_id' => $sale_id, 'product_id' => $pid])->row()) {
                continue; // already present (idempotent)
            }
            $price      = (float) $product->price;
            $sale_price = $price > 0 ? round($price * 0.8, 2) : 0.00; // 20% off, below original
            $this->db->insert('flash_sale_items', [
                'flash_sale_id' => $sale_id,
                'product_id'    => $pid,
                'sale_price'    => $sale_price,
                'created_at'    => $now,
            ]);
            $added++;
        }
        echo "flash sale sample ensured ({$added} items added)." . PHP_EOL;
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
