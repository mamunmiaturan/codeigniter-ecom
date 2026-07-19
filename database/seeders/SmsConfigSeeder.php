<?php
defined('BASEPATH') or exit('No direct script access allowed');

class SmsConfigSeeder extends Seeder
{
    public function run()
    {
        $this->db->empty_table('sms_config');
        $this->db->insert_batch('sms_config', [
            [
                'gateway_name' => 'ssl_wireless',
                'display_name' => 'SSL Wireless SMS Gateway',
                'credentials' => json_encode([
                    'api_token' => 'SSL-API-TOKEN-123456',
                    'sid' => 'Auth',
                    'sms_type' => 'text'
                ]),
                'is_active' => 1
            ],
            [
                'gateway_name' => 'twilio',
                'display_name' => 'Twilio SMS Gateway',
                'credentials' => json_encode([
                    'account_sid' => 'TW-SID-123456',
                    'auth_token' => 'TW-TOKEN-abcdef',
                    'twilio_number' => '+1234567890'
                ]),
                'is_active' => 0
            ]
        ]);
        echo "SmsConfigSeeder Finished." . PHP_EOL;
    }
}
