<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_Sms_Logs_Table extends CI_Migration {

    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `sms_logs` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` int DEFAULT NULL COMMENT 'FK to users.id',
  `mobile_no` varchar(20) DEFAULT NULL,
  `sms_text` varchar(1000) NOT NULL,
  `template_id` int DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_by` int DEFAULT NULL COMMENT 'FK to users.id',
  `updated_by` int DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  INDEX `idx_user_date` (`user_id`, `created_at`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('sms_logs', TRUE);
    }
}
