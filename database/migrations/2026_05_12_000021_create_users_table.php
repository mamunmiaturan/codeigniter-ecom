<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_Users_Table extends CI_Migration
{
    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `users` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` VARCHAR(50) DEFAULT NULL UNIQUE COMMENT 'Human-readable ID',
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `mobile_no` VARCHAR(20) DEFAULT NULL,
    `photo` VARCHAR(255) DEFAULT NULL,
    `gender` ENUM('Male','Female','Other') DEFAULT NULL,
    `dob` DATE DEFAULT NULL,
    `blood_group` VARCHAR(10) DEFAULT NULL,
    `religion` VARCHAR(50) DEFAULT NULL,
    `marital_status` ENUM('Single','Married','Divorced','Widowed') DEFAULT 'Single',
    `nid_no` VARCHAR(50) DEFAULT NULL,
    `nationality` VARCHAR(50) DEFAULT NULL,
    `educational_qualification` VARCHAR(255) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `created_by` bigint DEFAULT NULL,
    `updated_by` bigint DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL COMMENT 'For soft delete',
    INDEX `idx_email` (`email`),
    INDEX `idx_mobile_no` (`mobile_no`),
    INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('users', TRUE);
    }
}
