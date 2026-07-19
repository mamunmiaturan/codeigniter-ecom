<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Shipping
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : 2026_07_12_000046_create_shipping_tables.php
 *
 * Shipping zones + methods (replaces the flat Order_model::FLAT_SHIPPING = 60).
 * A zone matches by division list ('*' = all divisions, fallback). Each method
 * computes a rate (flat / per-unit / free), with an optional free-over threshold.
 * CREATE TABLE IF NOT EXISTS keeps it safe on the dump-bootstrapped DB.
 */
class Migration_Create_Shipping_Tables extends CI_Migration
{
  public function up()
  {
    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `shipping_zones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `divisions` varchar(500) NOT NULL DEFAULT '*',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `sort_order` int NOT NULL DEFAULT 0,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_shipping_zones_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `shipping_methods` (
  `id` int NOT NULL AUTO_INCREMENT,
  `zone_id` int NOT NULL,
  `code` varchar(60) NOT NULL,
  `title` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `type` enum('flat','per_unit','free') NOT NULL DEFAULT 'flat',
  `base_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `per_unit_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `free_over` decimal(12,2) DEFAULT NULL,
  `min_days` int DEFAULT NULL,
  `max_days` int DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `sort_order` int NOT NULL DEFAULT 0,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_shipping_methods_zone` (`zone_id`),
  KEY `idx_shipping_methods_status` (`status`),
  CONSTRAINT `fk_shipping_methods_zone` FOREIGN KEY (`zone_id`) REFERENCES `shipping_zones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
  }

  public function down()
  {
    $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['shipping_methods', 'shipping_zones'] as $t) {
      $this->dbforge->drop_table($t, TRUE);
    }
    $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
  }
}
