<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_Activity_Logs_Table extends CI_Migration
{

    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `activity_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `module_name` VARCHAR(100) DEFAULT NULL COMMENT 'e.g. Meal Booking, Finance',
    `table_name` VARCHAR(150) NOT NULL,
    `row_id` INT NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `payload` TEXT NULL DEFAULT NULL,
    `description` TEXT DEFAULT NULL COMMENT 'Human readable summary',
    `old_data` LONGTEXT DEFAULT NULL COMMENT 'JSON string of previous state',
    `new_data` LONGTEXT DEFAULT NULL COMMENT 'JSON string of new state',
    `route_path` VARCHAR(255) DEFAULT NULL COMMENT 'URL that triggered the action',
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_user_date` (`user_id`, `created_at`),
    INDEX `idx_table` (`table_name`),
    INDEX `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('activity_logs', TRUE);
    }
}
