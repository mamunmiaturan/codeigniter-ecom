<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Absorbs the legacy `Email::__construct` DDL that ran on every request:
 *  - Renames legacy column names (from_email→email, protocol→email_protocol, encryption→smtp_encryption)
 *  - Ensures all SMTP columns exist with their defaults
 *  - Seeds a single default row if the table is empty
 *
 * Idempotent — safe to re-run.
 */
class Migration_Normalize_Email_Config extends CI_Migration
{
    public function up()
    {
        if (!$this->db->table_exists('email_config')) {
            // Nothing to normalize — the original create migration must have
            // failed. Skip rather than half-create the table here.
            return;
        }
        $this->load->dbforge();

        $column_defs = [
            'email'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE],
            'email_protocol'  => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => 'smtp', 'null' => FALSE],
            'smtp_encryption' => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => 'tls',  'null' => TRUE],
            'smtp_host'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE],
            'smtp_user'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE],
            'smtp_pass'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => TRUE],
            'smtp_port'       => ['type' => 'VARCHAR', 'constraint' => 10,  'null' => TRUE],
        ];

        // Step 1 — rename legacy columns if present.
        $renames = [
            'from_email' => 'email',
            'protocol'   => 'email_protocol',
            'encryption' => 'smtp_encryption',
        ];
        foreach ($renames as $old => $new) {
            if ($this->db->field_exists($old, 'email_config') && !$this->db->field_exists($new, 'email_config')) {
                $this->dbforge->modify_column('email_config', [
                    $old => array_merge(['name' => $new], $column_defs[$new]),
                ]);
            }
        }

        // Step 2 — add any missing columns.
        foreach ($column_defs as $col => $def) {
            if (!$this->db->field_exists($col, 'email_config')) {
                $this->dbforge->add_column('email_config', [$col => $def]);
            }
        }

        // Step 3 — seed a default row if empty (uses literal placeholders;
        // operators must change these via the Email Settings UI before sending).
        $count = (int) $this->db->count_all('email_config');
        if ($count === 0) {
            $this->db->insert('email_config', [
                'id'              => 1,
                'email'           => 'noreply@example.com',
                'email_protocol'  => 'smtp',
                'smtp_host'       => 'smtp.example.com',
                'smtp_user'       => 'noreply@example.com',
                'smtp_pass'       => '',
                'smtp_port'       => '587',
                'smtp_encryption' => 'tls',
            ]);
        }
    }

    public function down()
    {
        // Non-destructive — leave columns in place. Operators can revert via
        // a fresh DROP+CREATE if needed.
    }
}
