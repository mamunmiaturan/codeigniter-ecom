<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_Login_Attempts_Table extends CI_Migration {

    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `login_attempts` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `timestamp` INT NOT NULL,
    INDEX `idx_email_ip_time` (`email`, `ip_address`, `timestamp`),
    INDEX `idx_ip_timestamp` (`ip_address`, `timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('login_attempts', TRUE);
    }
}
