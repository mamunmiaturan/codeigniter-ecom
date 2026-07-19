<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_Language_List_Table extends CI_Migration
{

    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `language_list` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(10) NOT NULL UNIQUE COMMENT 'en, bn, etc',
    `status` ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('language_list', TRUE);
    }
}
