<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_user_2fa_and_rate_limits_tables extends CI_Migration
{
    public function up()
    {
        // user_2fa — TOTP secrets and backup codes per user
        $this->dbforge->add_field([
            'id'           => ['type' => 'INT', 'unsigned' => TRUE, 'auto_increment' => TRUE],
            'user_id'      => ['type' => 'INT', 'unsigned' => TRUE, 'null' => FALSE],
            'secret'       => ['type' => 'VARCHAR', 'constraint' => '64', 'null' => FALSE],
            'enabled'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'backup_codes' => ['type' => 'TEXT', 'null' => TRUE],
            'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at'   => ['type' => 'DATETIME', 'null' => TRUE],
        ]);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('user_2fa', TRUE);
        $this->db->query('ALTER TABLE user_2fa ADD UNIQUE KEY uk_user_id (user_id)');
        $this->db->query('ALTER TABLE user_2fa MODIFY updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');

        // api_rate_limits — sliding-window rate limiting (DB fallback when Redis absent)
        $this->dbforge->add_field([
            'id'           => ['type' => 'INT', 'unsigned' => TRUE, 'auto_increment' => TRUE],
            'rate_key'     => ['type' => 'VARCHAR', 'constraint' => '128', 'null' => FALSE],
            'hits'         => ['type' => 'INT', 'unsigned' => TRUE, 'default' => 1],
            'window_start' => ['type' => 'INT', 'unsigned' => TRUE, 'null' => FALSE],
            'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        ]);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('api_rate_limits', TRUE);
        $this->db->query('ALTER TABLE api_rate_limits ADD UNIQUE KEY uk_rate_key (rate_key)');
        $this->db->query('ALTER TABLE api_rate_limits ADD INDEX idx_window_start (window_start)');
    }

    public function down()
    {
        $this->dbforge->drop_table('user_2fa', TRUE);
        $this->dbforge->drop_table('api_rate_limits', TRUE);
    }
}
