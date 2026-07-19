<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_Reset_Password_Table extends CI_Migration {

    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `reset_password` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `token` VARCHAR(255) NOT NULL COMMENT 'Hashed reset token',
  `login_credential_id` INT NOT NULL COMMENT 'FK to login_credential.id',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NOT NULL COMMENT 'Token expiry — reject if past this time',
  `is_used` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = token already consumed',
  UNIQUE KEY `uk_token` (`token`),
  INDEX `idx_credential` (`login_credential_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('reset_password', TRUE);
    }
}
