<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SmsLogsSeeder extends Seeder {
    public function run() {
        $this->db->empty_table('sms_logs');
        $this->db->insert_batch('sms_logs', [
            [
                'user_id' => 1, 'sms_text' => 'Your OTP is 456123', 'created_at' => date('Y-m-d H:i:s'), 
                'status' => 'Delivered', 'created_by' => 1
            ],
            [
                'user_id' => 1, 'sms_text' => 'Booking Confirmed!', 'created_at' => date('Y-m-d H:i:s'), 
                'status' => 'Failed', 'created_by' => 1
            ]
        ]);
        echo "SmsLogsSeeder Finished." . PHP_EOL;
    }
}
