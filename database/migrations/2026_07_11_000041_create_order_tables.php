<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Orders (OMS)
 * @author   : Mamun Mia Turan
 * @filename : 2026_07_11_000041_create_order_tables.php
 *
 * Order schema: orders (with snapshotted shipping address + money totals),
 * order_items (product snapshots), and order_status_history (audit of
 * status transitions). IF NOT EXISTS keeps it safe on the dump-bootstrapped DB.
 */
class Migration_Create_Order_Tables extends CI_Migration {

    public function up()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_number` varchar(32) NOT NULL,
  `user_id` int DEFAULT NULL,
  `guest_token` varchar(64) DEFAULT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_phone` varchar(30) NOT NULL,
  `customer_email` varchar(150) DEFAULT NULL,
  `shipping_division` varchar(100) DEFAULT NULL,
  `shipping_district` varchar(100) DEFAULT NULL,
  `shipping_area` varchar(150) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `shipping_landmark` varchar(255) DEFAULT NULL,
  `shipping_postcode` varchar(20) DEFAULT NULL,
  `payment_method` enum('cod','online') NOT NULL DEFAULT 'cod',
  `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `status` enum('pending','confirmed','processing','shipped','delivered','completed','cancelled','returned') NOT NULL DEFAULT 'pending',
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `shipping_charge` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency` char(3) NOT NULL DEFAULT 'BDT',
  `item_count` int NOT NULL DEFAULT 0,
  `note` text DEFAULT NULL,
  `placed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_orders_number` (`order_number`),
  KEY `idx_orders_user` (`user_id`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `variant_id` int DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_slug` varchar(280) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `variant_name` varchar(150) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `quantity` int NOT NULL DEFAULT 1,
  `line_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_product` (`product_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `order_status_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `status` varchar(30) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `changed_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_osh_order` (`order_id`),
  CONSTRAINT `fk_osh_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
    }

    public function down()
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['order_status_history', 'order_items', 'orders'] as $table) {
            $this->dbforge->drop_table($table, TRUE);
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }
}
