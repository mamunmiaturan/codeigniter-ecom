<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class EmailConfigSeeder extends Seeder {
    public function run() {
        // Self-normalize columns to the app's expected names. The
        // normalize_email_config migration does this too, but it may not have run
        // in every environment (e.g. seeder-only bootstraps), so guard here as
        // well — otherwise the insert below fails with "Unknown column 'email'".
        $renames = [
            // old            => [new,               column definition]
            'from_email'      => ['email',           "VARCHAR(255) NULL"],
            'protocol'        => ['email_protocol',  "VARCHAR(20) NOT NULL DEFAULT 'smtp'"],
            'encryption'      => ['smtp_encryption', "VARCHAR(10) NULL DEFAULT 'tls'"],
        ];
        foreach ($renames as $old => $meta) {
            list($new, $type) = $meta;
            if ($this->db->field_exists($old, 'email_config') && !$this->db->field_exists($new, 'email_config')) {
                $this->db->query("ALTER TABLE `email_config` CHANGE `{$old}` `{$new}` {$type}");
            }
        }
        // Ensure the expected columns exist even when there was nothing to rename.
        $ensure = [
            'email'           => "VARCHAR(255) NULL",
            'email_protocol'  => "VARCHAR(20) NOT NULL DEFAULT 'smtp'",
            'smtp_encryption' => "VARCHAR(10) NULL DEFAULT 'tls'",
            'smtp_host'       => "VARCHAR(255) NULL",
            'smtp_user'       => "VARCHAR(255) NULL",
            'smtp_pass'       => "VARCHAR(255) NULL",
            'smtp_port'       => "VARCHAR(10) NULL",
        ];
        foreach ($ensure as $col => $type) {
            if (!$this->db->field_exists($col, 'email_config')) {
                $this->db->query("ALTER TABLE `email_config` ADD COLUMN `{$col}` {$type}");
            }
        }

        $this->db->empty_table('email_config');
        // Column names must match the app + Email_model ($emailsetting->email,
        // ->email_protocol, ->smtp_encryption).
        $this->db->insert('email_config', [
            'email'           => 'noreply@auth.com.bd',
            'email_protocol'  => 'smtp',
            'smtp_host'       => 'smtp.gmail.com',
            'smtp_user'       => 'noreply@auth.com.bd',
            'smtp_pass'       => 'encrypted_password',
            'smtp_port'       => 587,
            'smtp_encryption' => 'tls',
        ]);
        echo "EmailConfigSeeder Finished." . PHP_EOL;
    }
}
