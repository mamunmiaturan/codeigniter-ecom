<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog (Product Enhancements)
 * @author   : Mamun Mia Turan
 * @filename : ProductEnhancementsSeeder.php
 *
 * Idempotent bootstrap for the product-catalog enhancement columns:
 *   php index.php migrate seed ProductEnhancementsSeeder
 *
 * Adds three marketing columns to the existing `products` table via a
 * column-existence check then ALTER TABLE (mirrors ProductTypeSeeder):
 *   - tags      varchar(500) NULL  (comma-separated tag list)
 *   - video_url varchar(255) NULL  (YouTube/Vimeo/mp4 product video)
 *   - label     varchar(40)  NULL  (New, Hot, Sale, Best Seller, Limited…)
 */
class ProductEnhancementsSeeder extends Seeder
{
    public function run()
    {
        $this->_add_columns();
        echo "ProductEnhancementsSeeder finished." . PHP_EOL;
    }

    private function _add_columns()
    {
        if (!$this->db->field_exists('tags', 'products')) {
            $this->db->query("ALTER TABLE `products` ADD COLUMN `tags` VARCHAR(500) DEFAULT NULL AFTER `meta_description`");
        }
        echo "products.tags ensured." . PHP_EOL;

        if (!$this->db->field_exists('video_url', 'products')) {
            $this->db->query("ALTER TABLE `products` ADD COLUMN `video_url` VARCHAR(255) DEFAULT NULL AFTER `tags`");
        }
        echo "products.video_url ensured." . PHP_EOL;

        if (!$this->db->field_exists('label', 'products')) {
            $this->db->query("ALTER TABLE `products` ADD COLUMN `label` VARCHAR(40) DEFAULT NULL AFTER `video_url`");
        }
        echo "products.label ensured." . PHP_EOL;
    }
}
