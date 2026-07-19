<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Customer + Cart
 * @author   : Mamun Mia Turan
 * @filename : CustomerCartSeeder.php
 *
 * Idempotent bootstrap for storefront customers + cart. Ensures the cart tables
 * exist and that the Customer role (ROLE_CUSTOMER_ID = 6) is present so
 * customer login_credential rows satisfy the roles FK. Safe to run repeatedly:
 *   php index.php migrate seed CustomerCartSeeder
 */
class CustomerCartSeeder extends Seeder {

    public function run()
    {
        $this->_create_tables();
        $this->_ensure_customer_role();
        echo "CustomerCartSeeder finished." . PHP_EOL;
    }

    private function _create_tables()
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

        // JWT auth-infra tables (from migration 000037). Absent on the
        // dump-bootstrapped DB, so token issuance (login + customer register)
        // would 500 without them. Ensured here idempotently.
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `api_refresh_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` BIGINT UNSIGNED NULL,
  `user_id` INT NOT NULL,
  `login_credential_id` INT NOT NULL,
  `refresh_token_hash` CHAR(64) NOT NULL,
  `scope` VARCHAR(255) NOT NULL DEFAULT '',
  `device_hash` CHAR(64) NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(512) NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME NULL,
  `revoked_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_refresh_token_hash` (`refresh_token_hash`),
  INDEX `idx_user_expires` (`user_id`, `expires_at`),
  INDEX `idx_credential` (`login_credential_id`),
  INDEX `idx_parent` (`parent_id`),
  CONSTRAINT `fk_refresh_token_credential` FOREIGN KEY (`login_credential_id`) REFERENCES `login_credential` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `api_revoked_jti` (
  `jti_hash` CHAR(64) NOT NULL,
  `revoked_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NOT NULL,
  PRIMARY KEY (`jti_hash`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `customer_addresses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `label` varchar(50) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `division` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `area` varchar(150) DEFAULT NULL,
  `address` text NOT NULL,
  `landmark` varchar(255) DEFAULT NULL,
  `postcode` varchar(20) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_addr_user` (`user_id`),
  KEY `idx_addr_default` (`user_id`, `is_default`),
  CONSTRAINT `fk_addr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        echo "Cart + API auth + address tables ensured." . PHP_EOL;
    }

    private function _ensure_customer_role()
    {
        $role_id = defined('ROLE_CUSTOMER_ID') ? ROLE_CUSTOMER_ID : 6;
        if (!$this->db->get_where('roles', ['id' => $role_id])->row()) {
            $this->db->insert('roles', [
                'id'        => $role_id,
                'name'      => 'Customer',
                'is_system' => 1,
            ]);
            echo "Customer role (id {$role_id}) created." . PHP_EOL;
        } else {
            echo "Customer role already present." . PHP_EOL;
        }
    }
}
