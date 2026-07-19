<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_User_Privileges_Table extends CI_Migration
{
    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `user_privileges` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  `is_add` tinyint(1) NOT NULL DEFAULT 0,
  `is_edit` tinyint(1) NOT NULL DEFAULT 0,
  `is_view` tinyint(1) NOT NULL DEFAULT 0,
  `is_delete` tinyint(1) NOT NULL DEFAULT 0,
  UNIQUE KEY `uk_role_permission` (`role_id`, `permission_id`),
  INDEX `idx_role` (`role_id`),
  CONSTRAINT `fk_user_privileges_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_privileges_permission` FOREIGN KEY (`permission_id`) REFERENCES `permission` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('user_privileges', TRUE);
    }
}
