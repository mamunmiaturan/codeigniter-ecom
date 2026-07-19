<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Payment
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : 2026_07_12_000048_create_payment_tables.php
 *
 * Payment settings (admin overrides of the config registry) + payment
 * transactions (gateway callback/IPN audit + idempotency).
 */
class Migration_Create_Payment_Tables extends CI_Migration
{
  public function up()
  {
    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `payment_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(60) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `title` varchar(150) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT 0,
  `config` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payment_settings_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `gateway` varchar(60) NOT NULL,
  `transaction_id` varchar(191) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `status` varchar(30) NOT NULL,
  `payload` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payment_txn_order` (`order_id`),
  KEY `idx_payment_txn_txnid` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
  }

  public function down()
  {
    $this->dbforge->drop_table('payment_transactions', TRUE);
    $this->dbforge->drop_table('payment_settings', TRUE);
  }
}
