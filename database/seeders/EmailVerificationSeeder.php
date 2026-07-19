<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Idempotent: adds email-verification columns to `users`.
 *   php index.php migrate seed EmailVerificationSeeder
 */
class EmailVerificationSeeder extends Seeder
{
    public function run()
    {
        if (!$this->db->field_exists('email_verified', 'users')) {
            $this->db->query("ALTER TABLE `users` ADD COLUMN `email_verified` tinyint(1) NOT NULL DEFAULT 0");
            echo "users.email_verified added." . PHP_EOL;
        } else {
            echo "users.email_verified already present." . PHP_EOL;
        }
        if (!$this->db->field_exists('email_verify_token', 'users')) {
            $this->db->query("ALTER TABLE `users` ADD COLUMN `email_verify_token` varchar(64) DEFAULT NULL");
            echo "users.email_verify_token added." . PHP_EOL;
        } else {
            echo "users.email_verify_token already present." . PHP_EOL;
        }
        echo "EmailVerificationSeeder finished." . PHP_EOL;
    }
}
