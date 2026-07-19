<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_Global_Settings_Table extends CI_Migration
{
    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `global_settings` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `site_name` VARCHAR(255) NOT NULL,
    `site_email` VARCHAR(100) DEFAULT NULL,
    `currency` VARCHAR(10) DEFAULT 'BDT',
    `currency_symbol` VARCHAR(5) DEFAULT '৳',
    `default_language` VARCHAR(10) DEFAULT 'english',
    `timezone` VARCHAR(50) DEFAULT 'Asia/Dhaka',
    `date_format` VARCHAR(50) DEFAULT 'Y-m-d',
    `logo` VARCHAR(255) DEFAULT NULL,
    `footer_text` TEXT DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `mobile_no` VARCHAR(60) DEFAULT NULL,
    `translation` VARCHAR(50) DEFAULT 'english',
    `facebook_url` VARCHAR(255) DEFAULT NULL,
    `twitter_url` VARCHAR(255) DEFAULT NULL,
    `youtube_url` VARCHAR(255) DEFAULT NULL,
    `linkedin_url` VARCHAR(255) DEFAULT NULL,
    `instagram_url` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('global_settings', TRUE);
    }
}
