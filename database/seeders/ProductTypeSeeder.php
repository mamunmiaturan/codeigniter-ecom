<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog (Product Types)
 * @author   : Mamun Mia Turan
 * @filename : ProductTypeSeeder.php
 *
 * Idempotent bootstrap for virtual + downloadable product types:
 *   php index.php migrate seed ProductTypeSeeder
 *
 * Ensures product_downloads/customer_downloads, adds products.product_type,
 * prepares the protected uploads/downloads dir, and seeds one demo downloadable
 * product with a sample file. Managed under the existing `product` permission.
 */
class ProductTypeSeeder extends Seeder
{
    public function run()
    {
        $this->_create_tables();
        $this->_add_column();
        $this->_ensure_dir();
        $this->_seed_demo();
        echo "ProductTypeSeeder finished." . PHP_EOL;
    }

    private function _create_tables()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `product_downloads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `name` varchar(200) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `is_sample` tinyint(1) NOT NULL DEFAULT 0,
  `download_limit` int DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pdownloads_product` (`product_id`),
  CONSTRAINT `fk_pdownloads_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `customer_downloads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `order_id` int NOT NULL,
  `order_item_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `product_download_id` int DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `download_limit` int DEFAULT NULL,
  `downloads_used` int NOT NULL DEFAULT 0,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cdownloads_token` (`token`),
  KEY `idx_cdownloads_user` (`user_id`),
  KEY `idx_cdownloads_order` (`order_id`),
  CONSTRAINT `fk_cdownloads_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
        echo "product download tables ensured." . PHP_EOL;
    }

    private function _add_column()
    {
        if (!$this->db->field_exists('product_type', 'products')) {
            $this->db->query("ALTER TABLE `products` ADD COLUMN `product_type` ENUM('simple','virtual','downloadable') NOT NULL DEFAULT 'simple' AFTER `slug`");
        }
        echo "products.product_type ensured." . PHP_EOL;
    }

    private function _ensure_dir()
    {
        $dir = FCPATH . 'uploads/downloads/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        // Block direct web access (files are streamed by token via the controller).
        $ht = $dir . '.htaccess';
        if (!is_file($ht)) {
            @file_put_contents($ht, "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
        }
        echo "uploads/downloads dir ready (direct access denied)." . PHP_EOL;
    }

    private function _seed_demo()
    {
        $now = date('Y-m-d H:i:s');
        $slug = 'digital-wallpaper-pack';
        $existing = $this->db->get_where('products', ['slug' => $slug])->row();
        if (!$existing) {
            $cat = $this->db->get_where('categories', ['slug' => 'electronics'])->row();
            $this->db->insert('products', [
                'name'              => 'Digital Wallpaper Pack (Demo)',
                'slug'              => $slug,
                'product_type'      => 'downloadable',
                'sku'               => 'DL-0001',
                'category_id'       => $cat ? (int) $cat->id : null,
                'short_description' => 'A downloadable pack of high-resolution wallpapers. Delivered instantly after payment.',
                'price'             => 199,
                'currency'          => 'BDT',
                'stock_quantity'    => 0,
                'stock_status'      => 'in_stock',
                'is_featured'       => 0,
                'status'            => 'Active',
                'created_at'        => $now,
            ]);
            $pid = (int) $this->db->insert_id();

            // Write a small demo file + a sample file.
            $dir = FCPATH . 'uploads/downloads/';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $main = 'demo-wallpaper-pack.txt';
            @file_put_contents($dir . $main, "Thank you for your purchase!\nThis is a demo downloadable file for the Digital Wallpaper Pack.\n");
            $sample = 'demo-wallpaper-sample.txt';
            @file_put_contents($dir . $sample, "This is a free sample preview.\n");

            $this->db->insert('product_downloads', ['product_id' => $pid, 'name' => 'Wallpaper Pack (full)', 'file_path' => $main, 'is_sample' => 0, 'sort_order' => 1, 'created_at' => $now]);
            $this->db->insert('product_downloads', ['product_id' => $pid, 'name' => 'Free sample', 'file_path' => $sample, 'is_sample' => 1, 'sort_order' => 2, 'created_at' => $now]);
            echo "demo downloadable product seeded." . PHP_EOL;
        } else {
            echo "demo downloadable product already present." . PHP_EOL;
        }
    }
}
