<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Promotions
 * @author   : Mamun Mia Turan
 * @filename : 2026_07_11_000042_create_coupon_tables.php
 *
 * Coupons + coupon usage tracking, plus a `coupon_code` column on carts so an
 * applied code follows the cart into checkout. IF NOT EXISTS / field_exists keep
 * it safe on the dump-bootstrapped DB.
 */
class Migration_Create_Coupon_Tables extends CI_Migration {

    public function up()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `type` enum('percentage','fixed','free_shipping') NOT NULL DEFAULT 'percentage',
  `value` decimal(12,2) NOT NULL DEFAULT 0.00,
  `min_order_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `max_discount_amount` decimal(12,2) DEFAULT NULL,
  `usage_limit` int DEFAULT NULL,
  `usage_limit_per_user` int DEFAULT NULL,
  `used_count` int NOT NULL DEFAULT 0,
  `starts_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_coupons_code` (`code`),
  KEY `idx_coupons_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `coupon_usages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `coupon_id` int NOT NULL,
  `order_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `guest_token` varchar(64) DEFAULT NULL,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cu_coupon` (`coupon_id`),
  KEY `idx_cu_user` (`user_id`),
  CONSTRAINT `fk_cu_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        if ($this->db->table_exists('carts') && !$this->db->field_exists('coupon_code', 'carts')) {
            $this->db->query("ALTER TABLE `carts` ADD `coupon_code` VARCHAR(50) NULL AFTER `guest_token`");
        }
        if ($this->db->table_exists('orders') && !$this->db->field_exists('coupon_code', 'orders')) {
            $this->db->query("ALTER TABLE `orders` ADD `coupon_code` VARCHAR(50) NULL AFTER `discount`");
        }
    }

    public function down()
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['coupon_usages', 'coupons'] as $table) {
            $this->dbforge->drop_table($table, TRUE);
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
        if ($this->db->field_exists('coupon_code', 'carts')) {
            $this->dbforge->drop_column('carts', 'coupon_code');
        }
        if ($this->db->field_exists('coupon_code', 'orders')) {
            $this->dbforge->drop_column('orders', 'coupon_code');
        }
    }
}
