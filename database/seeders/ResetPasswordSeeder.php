<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ResetPasswordSeeder extends Seeder {
    public function run() {
        $this->db->empty_table('reset_password');
        // Align seeder with new security-hardened token architecture
        $this->db->insert_batch('reset_password', [
            [
                'token' => hash('sha256', 'RESET-KEY-ABC-123'), 
                'login_credential_id' => 1, 
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
                'is_used' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
        echo "ResetPasswordSeeder Finished." . PHP_EOL;
    }
}
