<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_Ci_Sessions_Table extends CI_Migration {

    public function up()
    {
        $sql = <<<'SQL'
CREATE TABLE `ci_sessions` (
  `id` varchar(128) NOT NULL PRIMARY KEY,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` int UNSIGNED NOT NULL DEFAULT 0,
  `data` blob NOT NULL,
  KEY `ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
        $this->db->query($sql);
    }

    public function down()
    {
        $this->dbforge->drop_table('ci_sessions', TRUE);
    }
}
