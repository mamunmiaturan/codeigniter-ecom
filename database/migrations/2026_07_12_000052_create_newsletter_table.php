<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Marketing
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : 2026_07_12_000052_create_newsletter_table.php
 *
 * Newsletter subscribers (storefront signup + admin management).
 */
class Migration_Create_Newsletter_Table extends CI_Migration
{
  public function up()
  {
    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(190) NOT NULL,
  `status` enum('subscribed','unsubscribed') NOT NULL DEFAULT 'subscribed',
  `source` varchar(50) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_newsletter_email` (`email`),
  KEY `idx_newsletter_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
  }

  public function down()
  {
    $this->dbforge->drop_table('newsletter_subscribers', TRUE);
  }
}
