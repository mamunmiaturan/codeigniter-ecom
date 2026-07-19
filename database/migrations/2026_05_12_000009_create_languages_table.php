<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_Languages_Table extends CI_Migration
{

    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `languages` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `word_key` VARCHAR(255) NOT NULL UNIQUE,
    `english` TEXT DEFAULT NULL,
    `bengali` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('languages', TRUE);
    }
}
