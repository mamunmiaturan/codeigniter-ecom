<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_Sms_Config_Table extends CI_Migration {

    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `sms_config` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `gateway_name` varchar(100) NOT NULL COMMENT 'e.g. ssl_wireless, boomcast, adn, alphanet, mim, banglalink_biz',
  `display_name` varchar(150) NOT NULL COMMENT 'Human readable label',
  `credentials` JSON NOT NULL COMMENT 'Provider-specific config: api_key, sender_id, username, password etc.',
  `is_active` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Only ONE should be 1 at a time',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_gateway` (`gateway_name`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('sms_config', TRUE);
    }
}
