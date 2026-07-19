<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LanguagesSeeder extends Seeder {
    public function run() {
        $this->db->empty_table('languages');
        $this->db->insert_batch('languages', [
            ['word_key' => 'login', 'english' => 'Login', 'bengali' => 'লগইন'],
            ['word_key' => 'email', 'english' => 'Email', 'bengali' => 'ইমেল'],
            ['word_key' => 'password', 'english' => 'Password', 'bengali' => 'পাসওয়ার্ড'],
            ['word_key' => 'site_name', 'english' => 'Site Name', 'bengali' => 'সাইটের নাম']
        ]);
        echo "LanguagesSeeder Finished." . PHP_EOL;
    }
}
