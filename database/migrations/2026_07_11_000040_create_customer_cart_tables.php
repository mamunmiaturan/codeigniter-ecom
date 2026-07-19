<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Customer + Cart
 * @author   : Mamun Mia Turan
 * @filename : 2026_07_11_000040_create_customer_cart_tables.php
 *
 * Shopping cart schema. Customers reuse the existing users/login_credential
 * tables (role = ROLE_CUSTOMER_ID). A cart belongs to either a logged-in
 * user_id or an anonymous guest_token. IF NOT EXISTS keeps it safe on the
 * dump-bootstrapped (migration version 0) database.
 */
class Migration_Create_Customer_Cart_Tables extends CI_Migration {

    public function up()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `carts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `guest_token` varchar(64) DEFAULT NULL,
  `status` enum('active','ordered','abandoned') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_carts_user` (`user_id`),
  KEY `idx_carts_guest` (`guest_token`),
  KEY `idx_carts_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `cart_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cart_id` int NOT NULL,
  `product_id` int NOT NULL,
  `variant_id` int DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cart_items_cart` (`cart_id`),
  KEY `idx_cart_items_product` (`product_id`),
  CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
    }

    public function down()
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['cart_items', 'carts'] as $table) {
            $this->dbforge->drop_table($table, TRUE);
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }
}
