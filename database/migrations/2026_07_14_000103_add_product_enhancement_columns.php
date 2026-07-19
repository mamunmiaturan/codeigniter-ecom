<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog (Product Enhancements)
 * @author   : Mamun Mia Turan
 * @filename : 2026_07_14_000103_add_product_enhancement_columns.php
 *
 * Adds three marketing columns to `products`:
 *   - tags      varchar(500) NULL  (comma-separated tag list)
 *   - video_url varchar(255) NULL  (YouTube/Vimeo/mp4 product video)
 *   - label     varchar(40)  NULL  (New, Hot, Sale, Best Seller, Limited…)
 *
 * Each ALTER is guarded by a column-existence check so the migration is
 * idempotent and safe on the dump-bootstrapped DB.
 */
class Migration_Add_Product_Enhancement_Columns extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('tags', 'products')) {
            $this->db->query("ALTER TABLE `products` ADD COLUMN `tags` VARCHAR(500) DEFAULT NULL AFTER `meta_description`");
        }
        if (!$this->db->field_exists('video_url', 'products')) {
            $this->db->query("ALTER TABLE `products` ADD COLUMN `video_url` VARCHAR(255) DEFAULT NULL AFTER `tags`");
        }
        if (!$this->db->field_exists('label', 'products')) {
            $this->db->query("ALTER TABLE `products` ADD COLUMN `label` VARCHAR(40) DEFAULT NULL AFTER `video_url`");
        }
    }

    public function down()
    {
        foreach (['label', 'video_url', 'tags'] as $col) {
            if ($this->db->field_exists($col, 'products')) {
                $this->db->query("ALTER TABLE `products` DROP COLUMN `{$col}`");
            }
        }
    }
}
