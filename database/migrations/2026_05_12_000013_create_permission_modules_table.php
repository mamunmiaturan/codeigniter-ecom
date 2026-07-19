<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_Permission_Modules_Table extends CI_Migration {

    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `permission_modules` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(50) NOT NULL,
  `prefix` varchar(50) NOT NULL,
  `system` tinyint(1) NOT NULL DEFAULT 0,
  `sorted` tinyint NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('permission_modules', TRUE);
    }
}
