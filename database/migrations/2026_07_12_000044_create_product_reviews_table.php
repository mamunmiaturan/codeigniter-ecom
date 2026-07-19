<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Reviews
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : 2026_07_12_000044_create_product_reviews_table.php
 *
 * Product reviews & ratings. Uses CREATE TABLE IF NOT EXISTS so it is safe to
 * run against a database bootstrapped from the SQL dump (migration version 0).
 */
class Migration_Create_Product_Reviews_Table extends CI_Migration
{
  public function up()
  {
    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `product_reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `order_id` int DEFAULT NULL,
  `author_name` varchar(150) NOT NULL,
  `author_email` varchar(190) DEFAULT NULL,
  `rating` tinyint NOT NULL DEFAULT 5,
  `title` varchar(200) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `is_verified_purchase` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_reply` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reviews_product` (`product_id`),
  KEY `idx_reviews_status` (`status`),
  KEY `idx_reviews_user` (`user_id`),
  CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
  }

  public function down()
  {
    $this->dbforge->drop_table('product_reviews', TRUE);
  }
}
