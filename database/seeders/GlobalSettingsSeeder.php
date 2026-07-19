<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class GlobalSettingsSeeder extends Seeder
{
    public function run()
    {
        $this->db->empty_table('global_settings');

        $this->db->insert('global_settings', [
            'id' => 1,
            'site_name' => 'Auth',
            'site_email' => 'contact@auth.com.bd',

            'currency' => 'BDT',
            'currency_symbol' => '৳',
            'default_language' => 'english',
            'timezone' => 'Asia/Dhaka',
            'date_format' => 'Y-m-d',

            'logo' => NULL,

            'footer_text' => '© ' . date('Y') . ' Auth Bangladesh Ltd. All Rights Reserved.',

            'address' => 'House 45, Road 12, Sector 7, Uttara, Dhaka-1230',
            'mobile_no' => '+8801700000000',
            'translation' => 'english',

            'facebook_url' => 'https://facebook.com',
            'twitter_url' => 'https://twitter.com',
            'youtube_url' => 'https://youtube.com',
            'linkedin_url' => 'https://linkedin.com/company',
            'instagram_url' => 'https://instagram.com',

            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        echo "GlobalSettingsSeeder Finished." . PHP_EOL;
    }
}
?>