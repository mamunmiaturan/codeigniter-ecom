<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_Imports_Table extends CI_Migration {

    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `imports` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255) NOT NULL,
    `original_filename` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `user_id` INT NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
    `total_rows` INT NOT NULL DEFAULT 0,
    `success_rows` INT NOT NULL DEFAULT 0,
    `failed_rows` INT NOT NULL DEFAULT 0,
    `error_message` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT NULL,
    INDEX `idx_user_id` (`user_id`),
    CONSTRAINT `fk_imports_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('imports', TRUE);
    }
}
