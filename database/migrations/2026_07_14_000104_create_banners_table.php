<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Banners
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : 2026_07_14_000104_create_banners_table.php
 *
 * Storefront promotional banners: homepage sliders, promo blocks, popup modals
 * and the top announcement bar. Scheduled via optional starts_at / ends_at.
 */
class Migration_Create_Banners_Table extends CI_Migration
{
  public function up()
  {
    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `banners` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) DEFAULT NULL,
  `subtitle` varchar(300) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `button_text` varchar(80) DEFAULT NULL,
  `type` enum('slider','promo','popup','announcement') NOT NULL DEFAULT 'slider',
  `position` int NOT NULL DEFAULT 0,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_banner_type_status` (`type`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
  }

  public function down()
  {
    $this->dbforge->drop_table('banners', TRUE);
  }
}
