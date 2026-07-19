<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Flash Sale
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : 2026_07_14_000106_create_flash_sale_tables.php
 *
 * Time-boxed flash sales: a scheduled window (starts_at..ends_at) plus a set of
 * products each priced at a special sale_price for the duration of the window.
 */
class Migration_Create_Flash_Sale_Tables extends CI_Migration
{
    public function up()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `flash_sales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fs_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `flash_sale_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `flash_sale_id` int NOT NULL,
  `product_id` int NOT NULL,
  `sale_price` decimal(12,2) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fs_item` (`flash_sale_id`,`product_id`),
  KEY `idx_fs_item_sale` (`flash_sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
    }

    public function down()
    {
        $this->dbforge->drop_table('flash_sale_items', TRUE);
        $this->dbforge->drop_table('flash_sales', TRUE);
    }
}
