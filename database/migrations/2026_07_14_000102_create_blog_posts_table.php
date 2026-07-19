<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Blog
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : 2026_07_14_000102_create_blog_posts_table.php
 *
 * Blog / news posts rendered on the storefront.
 */
class Migration_Create_Blog_Posts_Table extends CI_Migration
{
  public function up()
  {
    $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `excerpt` varchar(500) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `category` varchar(120) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `status` enum('Draft','Active','Inactive') NOT NULL DEFAULT 'Draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` varchar(300) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_blog_slug` (`slug`),
  KEY `idx_blog_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
  }

  public function down()
  {
    $this->dbforge->drop_table('blog_posts', TRUE);
  }
}
