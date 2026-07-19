<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog (Product Types)
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : 2026_07_12_000054_create_product_type_tables.php
 *
 * Virtual + downloadable product types. products.product_type is added
 * idempotently by ProductTypeSeeder. product_downloads = the files an admin
 * attaches to a downloadable product; customer_downloads = the time/limit-bound
 * secure links granted to a buyer once their order is paid.
 */
class Migration_Create_Product_Type_Tables extends CI_Migration
{
  public function up()
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
  }

  public function down()
  {
    $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['customer_downloads', 'product_downloads'] as $t) {
      $this->dbforge->drop_table($t, TRUE);
    }
    $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
  }
}
