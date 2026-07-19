<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @filename : 2026_07_14_000105_create_stock_movements_table.php
 * Stock movement ledger: every stock change (adjust / allocation / transfer).
 */
class Migration_Create_Stock_Movements_Table extends CI_Migration
{
    public function up()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `stock_movements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `variant_id` int NOT NULL DEFAULT 0,
  `inventory_source_id` int DEFAULT NULL,
  `type` enum('in','out','adjust','transfer_in','transfer_out','allocation') NOT NULL DEFAULT 'adjust',
  `qty` int NOT NULL DEFAULT 0,
  `reason` varchar(255) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sm_product` (`product_id`),
  KEY `idx_sm_source` (`inventory_source_id`),
  KEY `idx_sm_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
    }

    public function down()
    {
        $this->dbforge->drop_table('stock_movements', TRUE);
    }
}
