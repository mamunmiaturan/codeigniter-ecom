<?php
defined('BASEPATH') or exit('No direct script access allowed');

class UserSeeder extends Seeder
{
    public function run()
    {
        // Fix missing quotes for 'users' table name
        $this->db->empty_table('users');
        $user = [];

        $user_template = [
            'name' => '',
            'email' => '',
            'mobile_no' => '',
            'created_by' => 1
        ];

        // 1. Superman (1)
        $user[] = array_merge($user_template, [
            'id' => 1,
            'user_id' => 'SUP-001',
            'name' => 'Mamun Mia Turan',
            'email' => 'turan.dev.bd@gmail.com',
            'mobile_no' => '01965572363'
        ]);

        // 2. Admin (1)
        $user[] = array_merge($user_template, [
            'id' => 2,
            'user_id' => 'ADM-001',
            'name' => 'System Admin',
            'email' => 'admin@gmail.com',
            'mobile_no' => '01700000002'
        ]);

        $this->db->insert_batch('users', $user);
        echo "userSeeder Finished with " . count($user) . " records." . PHP_EOL;
    }
}
