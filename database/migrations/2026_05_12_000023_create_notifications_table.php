<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_Notifications_Table extends CI_Migration {

    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `notifications` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT DEFAULT NULL,
    `is_read` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_user_read` (`user_id`, `is_read`),
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('notifications', TRUE);
    }
}
