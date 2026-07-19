<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : 2026_07_11_000039_create_catalog_tables.php
 *
 * Catalog foundation schema: categories, brands, products, product_variants,
 * product_images. Uses CREATE TABLE IF NOT EXISTS so it is safe to run against
 * a database that was bootstrapped from the SQL dump (migration version 0).
 */
class Migration_Create_Catalog_Tables extends CI_Migration
{

  public function up()
  {
    // 1. Categories (self-nesting via parent_id)
    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `parent_id` int DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int NOT NULL DEFAULT 0,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categories_slug` (`slug`),
  KEY `idx_categories_parent` (`parent_id`),
  KEY `idx_categories_status` (`status`),
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

    // 2. Brands
    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `brands` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int NOT NULL DEFAULT 0,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_brands_slug` (`slug`),
  KEY `idx_brands_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

    // 3. Products
    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(280) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `category_id` int DEFAULT NULL,
  `brand_id` int DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `special_price` decimal(12,2) DEFAULT NULL,
  `cost_price` decimal(12,2) DEFAULT NULL,
  `currency` char(3) NOT NULL DEFAULT 'BDT',
  `stock_quantity` int NOT NULL DEFAULT 0,
  `stock_status` enum('in_stock','out_of_stock','pre_order') NOT NULL DEFAULT 'in_stock',
  `unit` varchar(50) DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT NULL,
  `has_variants` tinyint(1) NOT NULL DEFAULT 0,
  `thumbnail` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Draft','Active','Inactive') NOT NULL DEFAULT 'Draft',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_products_slug` (`slug`),
  KEY `idx_products_sku` (`sku`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_brand` (`brand_id`),
  KEY `idx_products_status` (`status`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_products_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

    // 4. Product variants
    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `product_variants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `name` varchar(150) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `special_price` decimal(12,2) DEFAULT NULL,
  `stock_quantity` int NOT NULL DEFAULT 0,
  `attributes` json DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_variants_product` (`product_id`),
  CONSTRAINT `fk_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

    // 5. Product images (gallery)
    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT 0,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_images_product` (`product_id`),
  CONSTRAINT `fk_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
  }

  public function down()
  {
    $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['product_images', 'product_variants', 'products', 'brands', 'categories'] as $table) {
      $this->dbforge->drop_table($table, TRUE);
    }
    $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
  }
}
