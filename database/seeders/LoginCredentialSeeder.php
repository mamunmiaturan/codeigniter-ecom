<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LoginCredentialSeeder extends Seeder {
    public function run() {
        $this->db->empty_table('login_credential');
        $logins = [];
        $branches = [101, 102];
        $password = password_hash('123456', PASSWORD_DEFAULT);

        // 1. Superman
        $logins[] = ['email' => 'turan.dev.bd@gmail.com', 'password' => $password, 'user_id' => 1, 'role' => 1, 'status' => 'Active'];
        $logins[] = ['email' => 'admin@gmail.com', 'password' => $password, 'user_id' => 2, 'role' => 2, 'status' => 'Active'];

        $this->db->insert_batch('login_credential', $logins);
        echo "LoginCredentialSeeder Finished with " . count($logins) . " records." . PHP_EOL;
    }
}
