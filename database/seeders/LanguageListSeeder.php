<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LanguageListSeeder extends Seeder {
    public function run() {
        $this->db->empty_table('language_list');
        $this->db->insert_batch('language_list', [
            ['name' => 'English', 'code' => 'english', 'status' => 'Active'],
            ['name' => 'Bengali', 'code' => 'bengali', 'status' => 'Active']
        ]);
        echo "LanguageListSeeder Finished." . PHP_EOL;
    }
}
