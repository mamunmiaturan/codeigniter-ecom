<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Tax
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : 2026_07_12_000047_create_tax_tables.php
 *
 * Tax categories + rates + the category↔rate map (replaces the hardcoded
 * tax = 0). A product carries a tax_category_id; a rate matches by
 * country/state(division)/postcode. Tax is applied EXCLUSIVE (added on top).
 */
class Migration_Create_Tax_Tables extends CI_Migration
{
  public function up()
  {
    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `tax_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tax_categories_code` (`code`),
  KEY `idx_tax_categories_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `tax_rates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `identifier` varchar(80) NOT NULL,
  `name` varchar(120) NOT NULL,
  `country` char(2) NOT NULL DEFAULT 'BD',
  `state` varchar(100) NOT NULL DEFAULT '*',
  `postcode` varchar(30) NOT NULL DEFAULT '*',
  `rate` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `priority` int NOT NULL DEFAULT 0,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tax_rates_identifier` (`identifier`),
  KEY `idx_tax_rates_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `tax_category_rates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tax_category_id` int NOT NULL,
  `tax_rate_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tax_cat_rate` (`tax_category_id`,`tax_rate_id`),
  CONSTRAINT `fk_tcr_category` FOREIGN KEY (`tax_category_id`) REFERENCES `tax_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tcr_rate` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
  }

  public function down()
  {
    $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['tax_category_rates', 'tax_rates', 'tax_categories'] as $t) {
      $this->dbforge->drop_table($t, TRUE);
    }
    $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
  }
}
