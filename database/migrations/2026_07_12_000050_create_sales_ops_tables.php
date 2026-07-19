<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Sales Ops
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : 2026_07_12_000050_create_sales_ops_tables.php
 *
 * Order operations: invoices, shipments (courier + tracking), refunds, and
 * returns/RMA (request + items). All hang off orders with ON DELETE CASCADE.
 */
class Migration_Create_Sales_Ops_Tables extends CI_Migration
{
  public function up()
  {
    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `order_invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `invoice_number` varchar(40) NOT NULL,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_invoice_number` (`invoice_number`),
  KEY `idx_invoice_order` (`order_id`),
  CONSTRAINT `fk_invoice_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `order_shipments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `carrier` varchar(120) NOT NULL,
  `tracking_number` varchar(120) DEFAULT NULL,
  `tracking_url` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `shipped_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_shipment_order` (`order_id`),
  CONSTRAINT `fk_shipment_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `order_refunds` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `refunded_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_refund_order` (`order_id`),
  CONSTRAINT `fk_refund_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `return_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `rma_number` varchar(40) NOT NULL,
  `order_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `guest_token` varchar(64) DEFAULT NULL,
  `reason` varchar(150) DEFAULT NULL,
  `customer_note` text DEFAULT NULL,
  `admin_note` text DEFAULT NULL,
  `status` enum('requested','approved','rejected','received','refunded','cancelled') NOT NULL DEFAULT 'requested',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_rma_number` (`rma_number`),
  KEY `idx_return_order` (`order_id`),
  KEY `idx_return_user` (`user_id`),
  KEY `idx_return_status` (`status`),
  CONSTRAINT `fk_return_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `return_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `return_request_id` int NOT NULL,
  `order_item_id` int DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_return_item_req` (`return_request_id`),
  CONSTRAINT `fk_return_item_req` FOREIGN KEY (`return_request_id`) REFERENCES `return_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
  }

  public function down()
  {
    $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['return_items', 'return_requests', 'order_refunds', 'order_shipments', 'order_invoices'] as $t) {
      $this->dbforge->drop_table($t, TRUE);
    }
    $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
  }
}
