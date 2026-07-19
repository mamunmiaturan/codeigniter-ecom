<?php
defined('BASEPATH') or exit('No direct script access allowed');

class SmsTemplatesSeeder extends Seeder
{
    public function run()
    {
        $this->db->empty_table('sms_templates');
        $this->db->insert_batch('sms_templates', [
            ['sms_type' => 'otp_login', 'subject' => 'OTP Login', 'template_body' => 'Apnar Auth login OTP holo {otp}. Do not share.', 'tags' => '{otp}'],
            ['sms_type' => 'booking_success', 'subject' => 'Booking Success', 'template_body' => 'Apnar {date} tarikh-er meal booking confirm hoyeche. Thanks!', 'tags' => '{date}']
        ]);
        echo "SmsTemplatesSeeder Finished." . PHP_EOL;
    }
}
