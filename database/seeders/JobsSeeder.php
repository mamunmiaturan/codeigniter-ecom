<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class JobsSeeder extends Seeder {
    public function run() {
        $this->db->empty_table('jobs');
        $this->db->insert_batch('jobs', [
            ['queue' => 'default', 'payload' => '{"job":"SendEmail"}', 'attempts' => 0, 'created_at' => date('Y-m-d H:i:s')]
        ]);
        echo "JobsSeeder Finished." . PHP_EOL;
    }
}
