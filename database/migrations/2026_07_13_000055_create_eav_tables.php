<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog (EAV Attributes)
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : 2026_07_13_000055_create_eav_tables.php
 *
 * EAV attribute layer ported from Bagisto (additive — the existing catalog +
 * product_variants are untouched). Schema is ensured idempotently by EavSeeder;
 * products.attribute_family_id is added there too. This migration documents the
 * canonical schema.
 */
class Migration_Create_Eav_Tables extends CI_Migration
{
    public function up()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `admin_name` varchar(150) NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'text',
  `swatch_type` varchar(20) DEFAULT NULL,
  `validation` varchar(20) DEFAULT NULL,
  `regex` varchar(255) DEFAULT NULL,
  `position` int NOT NULL DEFAULT 0,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `is_unique` tinyint(1) NOT NULL DEFAULT 0,
  `is_filterable` tinyint(1) NOT NULL DEFAULT 0,
  `is_comparable` tinyint(1) NOT NULL DEFAULT 0,
  `is_configurable` tinyint(1) NOT NULL DEFAULT 0,
  `is_visible_on_front` tinyint(1) NOT NULL DEFAULT 1,
  `is_user_defined` tinyint(1) NOT NULL DEFAULT 1,
  `default_value` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_attributes_code` (`code`),
  KEY `idx_attributes_filterable` (`is_filterable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `attribute_options` (
  `id` int NOT NULL AUTO_INCREMENT,
  `attribute_id` int NOT NULL,
  `admin_name` varchar(150) DEFAULT NULL,
  `label` varchar(150) NOT NULL,
  `swatch_value` varchar(150) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attr_options_attr` (`attribute_id`),
  CONSTRAINT `fk_attr_options_attr` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `attribute_families` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `name` varchar(150) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `is_user_defined` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_attr_families_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `attribute_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(100) DEFAULT NULL,
  `attribute_family_id` int NOT NULL,
  `name` varchar(150) NOT NULL,
  `column` tinyint NOT NULL DEFAULT 1,
  `position` int NOT NULL DEFAULT 0,
  `is_user_defined` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_attr_group_family_name` (`attribute_family_id`, `name`),
  KEY `idx_attr_groups_family` (`attribute_family_id`),
  CONSTRAINT `fk_attr_groups_family` FOREIGN KEY (`attribute_family_id`) REFERENCES `attribute_families` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `attribute_group_mappings` (
  `attribute_id` int NOT NULL,
  `attribute_group_id` int NOT NULL,
  `position` int DEFAULT NULL,
  PRIMARY KEY (`attribute_id`, `attribute_group_id`),
  KEY `idx_agm_group` (`attribute_group_id`),
  CONSTRAINT `fk_agm_attr` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_agm_group` FOREIGN KEY (`attribute_group_id`) REFERENCES `attribute_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `product_attribute_values` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `attribute_id` int NOT NULL,
  `locale` varchar(20) DEFAULT NULL,
  `channel` varchar(50) DEFAULT NULL,
  `text_value` text,
  `boolean_value` tinyint(1) DEFAULT NULL,
  `integer_value` int DEFAULT NULL,
  `float_value` decimal(14,4) DEFAULT NULL,
  `datetime_value` datetime DEFAULT NULL,
  `date_value` date DEFAULT NULL,
  `json_value` longtext,
  `unique_id` varchar(150) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pav_unique` (`unique_id`),
  UNIQUE KEY `uk_pav_scope` (`channel`, `locale`, `attribute_id`, `product_id`),
  KEY `idx_pav_attr_int` (`attribute_id`, `integer_value`),
  KEY `idx_pav_product` (`product_id`),
  CONSTRAINT `fk_pav_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pav_attr` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        // product_type is normally added by ProductTypeSeeder; ensure it here so
        // this migration also works on a fresh DB where seeders haven't run yet.
        if (!$this->db->field_exists('product_type', 'products')) {
            $this->db->query("ALTER TABLE `products` ADD COLUMN `product_type` ENUM('simple','virtual','downloadable') NOT NULL DEFAULT 'simple' AFTER `slug`");
        }

        if (!$this->db->field_exists('attribute_family_id', 'products')) {
            $this->db->query("ALTER TABLE `products` ADD COLUMN `attribute_family_id` int NULL AFTER `product_type`");
        }
    }

    public function down()
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['product_attribute_values', 'attribute_group_mappings', 'attribute_groups', 'attribute_options', 'attribute_families', 'attributes'] as $t) {
            $this->dbforge->drop_table($t, TRUE);
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }
}
