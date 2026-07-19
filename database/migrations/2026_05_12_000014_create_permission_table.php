<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_Permission_Table extends CI_Migration {

    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `permission` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `module_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `prefix` varchar(100) NOT NULL,
  `show_view` tinyint DEFAULT '1',
  `show_add` tinyint DEFAULT '1',
  `show_edit` tinyint DEFAULT '1',
  `show_delete` tinyint DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_module` (`module_id`),
  CONSTRAINT `fk_permission_module` FOREIGN KEY (`module_id`) REFERENCES `permission_modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('permission', TRUE);
    }
}
