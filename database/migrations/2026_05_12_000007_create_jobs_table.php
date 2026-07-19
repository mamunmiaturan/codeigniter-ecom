<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_Jobs_Table extends CI_Migration {

    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `jobs` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `queue` VARCHAR(255) NOT NULL DEFAULT 'default',
    `payload` LONGTEXT NOT NULL,
    `attempts` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
    `reserved_at` DATETIME DEFAULT NULL,
    `available_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT NULL,
    INDEX `idx_queue_reserved_available` (`queue`, `reserved_at`, `available_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('jobs', TRUE);
    }
}
