<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_Login_Credential_Table extends CI_Migration
{

    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `login_credential` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` INT NOT NULL COMMENT 'FK to roles.id',
    `status` ENUM('Active','Inactive','Blocked','Suspended') NOT NULL DEFAULT 'Active',
    `device_token` TEXT DEFAULT NULL COMMENT 'For mobile push notifications',
    `last_login` DATETIME DEFAULT NULL,
    `last_active` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL COMMENT 'For soft delete',
    INDEX `idx_role` (`role`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_last_active` (`last_active`),
    CONSTRAINT `fk_login_credential_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_login_credential_role` FOREIGN KEY (`role`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;

        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('login_credential', TRUE);
    }
}
