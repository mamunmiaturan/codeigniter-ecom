<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_Email_Templates_Table extends CI_Migration
{

    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `email_templates` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,

    `template_key` VARCHAR(150) NOT NULL UNIQUE COMMENT 'Unique identifier like invoice_mail, reset_password',

    `email_type` ENUM('System','Marketing','Notification') NOT NULL DEFAULT 'System',

    `subject` VARCHAR(255) NOT NULL,

    `template_body` LONGTEXT NOT NULL,

    `available_tags` LONGTEXT DEFAULT NULL COMMENT 'JSON or comma separated tags',

    `is_active` TINYINT(1) NOT NULL DEFAULT 1,

    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('email_templates', TRUE);
    }
}