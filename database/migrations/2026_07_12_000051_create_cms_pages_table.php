<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / CMS
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : 2026_07_12_000051_create_cms_pages_table.php
 *
 * Static content pages (About, Privacy, Terms, ...) rendered on the storefront.
 */
class Migration_Create_Cms_Pages_Table extends CI_Migration
{
  public function up()
  {
    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `cms_pages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `content` longtext DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `show_in_footer` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int NOT NULL DEFAULT 0,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cms_pages_slug` (`slug`),
  KEY `idx_cms_pages_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
  }

  public function down()
  {
    $this->dbforge->drop_table('cms_pages', TRUE);
  }
}
