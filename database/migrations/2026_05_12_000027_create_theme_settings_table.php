<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_Theme_Settings_Table extends CI_Migration {

    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `theme_settings` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL COMMENT 'NULL for global default',
    `primary_color` VARCHAR(20) DEFAULT '#007bff',
    `secondary_color` VARCHAR(20) DEFAULT '#6c757d',
    `sidebar_color` VARCHAR(20) DEFAULT '#343a40',
    `sidebar_text_color` VARCHAR(20) DEFAULT '#ffffff',
    `navbar_color` VARCHAR(20) DEFAULT '#ffffff',
    `navbar_text_color` VARCHAR(20) DEFAULT '#343a40',
    `dark_mode` TINYINT(1) NOT NULL DEFAULT 0,
    `dark_skin` VARCHAR(10) NOT NULL DEFAULT 'false',
    `border_mode` ENUM('Rounded','Square') DEFAULT 'Rounded',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('theme_settings', TRUE);
    }
}
