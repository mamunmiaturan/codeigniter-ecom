<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class OtpHistorySeeder extends Seeder {
    public function run() {
        $this->db->empty_table('otp_history');
        // Securely hash OTP codes to comply with new hardened schema
        $this->db->insert_batch('otp_history', [
            [
                'phone' => '01711223344', 
                'otp_code_hash' => password_hash('456123', PASSWORD_BCRYPT), 
                'is_verified' => 1, 
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'phone' => '01811223344', 
                'otp_code_hash' => password_hash('789456', PASSWORD_BCRYPT), 
                'is_verified' => 0, 
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
        echo "OtpHistorySeeder Finished." . PHP_EOL;
    }
}
