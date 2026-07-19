<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_Sms_Templates_Table extends CI_Migration {

    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `sms_templates` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `sms_type` varchar(200) NOT NULL UNIQUE COMMENT 'e.g. booking_confirm, otp_verification',
  `subject` varchar(250) NOT NULL,
  `template_body` LONGTEXT NOT NULL,
  `tags` TEXT DEFAULT NULL COMMENT 'Available placeholder tags',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('sms_templates', TRUE);
    }
}
