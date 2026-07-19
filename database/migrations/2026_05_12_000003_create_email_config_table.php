<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_Email_Config_Table extends CI_Migration
{

    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `email_config` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,

    `from_email` VARCHAR(255) DEFAULT NULL,

    `protocol` ENUM('smtp','mail','sendmail') NOT NULL DEFAULT 'smtp',

    `smtp_host` VARCHAR(255) NOT NULL,
    `smtp_user` VARCHAR(255) DEFAULT NULL,
    `smtp_pass` TEXT DEFAULT NULL COMMENT 'Store encrypted — use app-level AES encryption',
    `is_encrypted` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = smtp_pass is AES encrypted',

    `smtp_port` INT NOT NULL DEFAULT 587,

    `encryption` ENUM('none','ssl','tls') DEFAULT 'tls',

    `status` ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',

    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('email_config', TRUE);
    }
}