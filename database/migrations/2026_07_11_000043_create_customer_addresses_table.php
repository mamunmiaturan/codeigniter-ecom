<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Customer
 * @author   : Mamun Mia Turan
 * @filename : 2026_07_11_000043_create_customer_addresses_table.php
 *
 * Saved shipping addresses per customer (role-6 user). A customer can keep
 * several labelled addresses and mark one as default; checkout can reference
 * one by id. IF NOT EXISTS keeps it safe on the dump-bootstrapped DB.
 */
class Migration_Create_Customer_Addresses_Table extends CI_Migration {

    public function up()
    {
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
    }

    public function down()
    {
        $this->dbforge->drop_table('customer_addresses', TRUE);
    }
}
