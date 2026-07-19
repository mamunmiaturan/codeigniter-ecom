<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Promotions
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : 2026_07_12_000049_create_rule_tables.php
 *
 * Cart price rules (auto-applied cart-level discounts, beyond coded coupons) and
 * catalog price rules (adjust product prices, materialised into a price index).
 */
class Migration_Create_Rule_Tables extends CI_Migration
{
  public function up()
  {
    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `cart_rules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `sort_order` int NOT NULL DEFAULT 0,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `min_subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `category_id` int DEFAULT NULL,
  `action_type` enum('percentage','fixed','free_shipping') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `max_discount` decimal(12,2) DEFAULT NULL,
  `free_shipping` tinyint(1) NOT NULL DEFAULT 0,
  `usage_limit` int DEFAULT NULL,
  `usage_limit_per_user` int DEFAULT NULL,
  `used_count` int NOT NULL DEFAULT 0,
  `end_other_rules` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cart_rules_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `cart_rule_usages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cart_rule_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `guest_token` varchar(64) DEFAULT NULL,
  `order_id` int DEFAULT NULL,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cru_rule` (`cart_rule_id`),
  KEY `idx_cru_user` (`user_id`),
  CONSTRAINT `fk_cru_rule` FOREIGN KEY (`cart_rule_id`) REFERENCES `cart_rules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `catalog_rules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `sort_order` int NOT NULL DEFAULT 0,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `scope` enum('all','category','product') NOT NULL DEFAULT 'all',
  `category_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `action_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `end_other_rules` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_catalog_rules_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `catalog_rule_product_prices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `catalog_rule_id` int NOT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `computed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_crpp_product` (`product_id`),
  KEY `idx_crpp_rule` (`catalog_rule_id`),
  CONSTRAINT `fk_crpp_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
  }

  public function down()
  {
    $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['catalog_rule_product_prices', 'catalog_rules', 'cart_rule_usages', 'cart_rules'] as $t) {
      $this->dbforge->drop_table($t, TRUE);
    }
    $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
  }
}
