<?php
defined('BASEPATH') or exit('No direct script access allowed');

class EmailTemplatesSeeder extends Seeder
{
    public function run()
    {
        $this->db->empty_table('email_templates');
        $this->db->insert_batch('email_templates', [
            ['template_key' => 'order_confirmation', 'subject' => 'Auth Order Confirmed!', 'template_body' => 'Dear User, your order has been received. Thanks for staying with us.'],
            ['template_key' => 'password_reset', 'subject' => 'Password Reset Request', 'template_body' => 'Please click the link below to reset your password.']
        ]);
        echo "EmailTemplatesSeeder Finished." . PHP_EOL;
    }
}
