<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LoginAttemptsSeeder extends Seeder {
    public function run() {
        $this->db->empty_table('login_attempts');
        $this->db->insert_batch('login_attempts', [
            ['ip_address' => '103.1.2.3', 'email' => 'turan.dev.bd@gmail.com', 'timestamp' => time()]
        ]);
        echo "LoginAttemptsSeeder Finished." . PHP_EOL;
    }
}
