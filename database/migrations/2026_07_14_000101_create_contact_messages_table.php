<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Support
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : 2026_07_14_000101_create_contact_messages_table.php
 *
 * Storefront "Contact Us" submissions delivered to an admin inbox
 * (read / view / reply / delete). Customers submit; admins never create.
 */
class Migration_Create_Contact_Messages_Table extends CI_Migration
{
  public function up()
  {
    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('New','Read','Replied','Closed') NOT NULL DEFAULT 'New',
  `admin_reply` text DEFAULT NULL,
  `replied_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_contact_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
  }

  public function down()
  {
    $this->dbforge->drop_table('contact_messages', TRUE);
  }
}
