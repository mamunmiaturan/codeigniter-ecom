<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_otp_history_table extends CI_Migration {

    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `otp_history` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `phone` VARCHAR(20) NOT NULL,
    `otp_code_hash` VARCHAR(255) NOT NULL,
    `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT NULL,
    INDEX `idx_phone_verified` (`phone`, `is_verified`),
    INDEX `idx_phone_created` (`phone`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $this->db->query($sql);
        echo "Created otp_history table." . PHP_EOL;
    }

    public function down()
    {
        $this->dbforge->drop_table('otp_history', TRUE);
    }
}
