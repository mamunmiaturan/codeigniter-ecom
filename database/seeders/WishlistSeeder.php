<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Wishlist
 * @author   : Mamun Mia Turan
 * @filename : WishlistSeeder.php
 *
 * Idempotent bootstrap for the customer wishlist table. Safe to run repeatedly:
 *   php index.php migrate seed WishlistSeeder
 *
 * Wishlists are a customer-facing feature (no admin permission/menu), so the
 * seeder only ensures the schema exists.
 */
class WishlistSeeder extends Seeder
{
    public function run()
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
        echo "wishlists table ensured." . PHP_EOL;
    }
}
