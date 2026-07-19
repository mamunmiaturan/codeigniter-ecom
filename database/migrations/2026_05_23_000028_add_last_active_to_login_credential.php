<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Add_Last_Active_To_Login_Credential extends CI_Migration
{
    public function up()
    {
        if (!$this->db->field_exists('last_active', 'login_credential')) {
            $this->db->query("ALTER TABLE `login_credential` ADD COLUMN `last_active` DATETIME DEFAULT NULL AFTER `last_login`");
            $this->db->query("ALTER TABLE `login_credential` ADD INDEX `idx_last_active` (`last_active`)");
        }
    }

    public function down()
    {
        if ($this->db->field_exists('last_active', 'login_credential')) {
            $this->db->query("ALTER TABLE `login_credential` DROP INDEX `idx_last_active`");
            $this->db->query("ALTER TABLE `login_credential` DROP COLUMN `last_active`");
        }
    }
}
