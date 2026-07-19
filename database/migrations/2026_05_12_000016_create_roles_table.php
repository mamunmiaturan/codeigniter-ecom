<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_Roles_Table extends CI_Migration {

    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(50) NOT NULL,
  `prefix` varchar(50) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = system role, cannot be deleted',
  `short_form` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('roles', TRUE);
    }
}
