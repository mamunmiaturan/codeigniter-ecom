<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Wishlist
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : 2026_07_12_000045_create_wishlist_table.php
 *
 * Customer wishlists. CREATE TABLE IF NOT EXISTS so it is safe to run against a
 * database bootstrapped from the SQL dump (migration version 0).
 */
class Migration_Create_Wishlist_Table extends CI_Migration
{
  public function up()
  {
    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `wishlists` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_wishlist_user_product` (`user_id`,`product_id`),
  KEY `idx_wishlist_user` (`user_id`),
  CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
  }

  public function down()
  {
    $this->dbforge->drop_table('wishlists', TRUE);
  }
}
