<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_fix_ci_session_table extends CI_Migration {

    public function up()
    {
        $table = 'ci_sessions';
        
        // If table doesn't exist, create it properly
        // Check if table exists properly (avoiding CI's internal cache during migrations)
        $exists_query = $this->db->query("SHOW TABLES LIKE '" . $this->db->dbprefix($table) . "'");
        if ($exists_query->num_rows() === 0) {
            $sql = "CREATE TABLE `ci_sessions` (
                `id` varchar(128) NOT NULL PRIMARY KEY,
                `ip_address` varchar(45) NOT NULL,
                `timestamp` int(10) unsigned DEFAULT 0 NOT NULL,
                `data` blob NOT NULL,
                KEY `ci_sessions_timestamp` (`timestamp`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
            $this->db->query($sql);
            echo "Created ci_sessions table." . PHP_EOL;
        } else {
            // Table exists, check for 'id' column
            if (!$this->db->field_exists('id', $table)) {
                // If it has 'session_id', rename it
                if ($this->db->field_exists('session_id', $table)) {
                    $this->db->query("ALTER TABLE `ci_sessions` CHANGE `session_id` `id` VARCHAR(128) NOT NULL;");
                    echo "Renamed session_id to id in ci_sessions table." . PHP_EOL;
                } else {
                    $this->db->query("ALTER TABLE `ci_sessions` ADD `id` VARCHAR(128) NOT NULL FIRST;");
                    echo "Added id column to ci_sessions table." . PHP_EOL;
                }
            } else {
                // 'id' exists, ensure it's varchar(128)
                $this->db->query("ALTER TABLE `ci_sessions` MODIFY `id` VARCHAR(128) NOT NULL;");
                echo "Updated id column to VARCHAR(128) in ci_sessions table." . PHP_EOL;
            }
            
            // Ensure primary key or index exists on id if possible, but at least ensure the schema is CI3 compatible
            // CI3 database session driver expects 'id' and 'ip_address' (if match_ip is TRUE)
            if (!$this->db->field_exists('timestamp', $table)) {
                $this->db->query("ALTER TABLE `ci_sessions` ADD `timestamp` int(10) unsigned DEFAULT 0 NOT NULL;");
            }
            if (!$this->db->field_exists('data', $table)) {
                $this->db->query("ALTER TABLE `ci_sessions` ADD `data` blob NOT NULL;");
            }
        }
    }

    public function down()
    {
        // No down migration needed for a fix
    }
}
