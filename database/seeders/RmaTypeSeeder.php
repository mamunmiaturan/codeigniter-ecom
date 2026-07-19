<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Idempotent: adds a `type` (return|exchange) column to return_requests.
 *   php index.php migrate seed RmaTypeSeeder
 */
class RmaTypeSeeder extends Seeder
{
    public function run()
    {
        if (!$this->db->field_exists('type', 'return_requests')) {
            $this->db->query("ALTER TABLE `return_requests` ADD COLUMN `type` enum('return','exchange') NOT NULL DEFAULT 'return' AFTER `order_id`");
            echo "return_requests.type added." . PHP_EOL;
        } else {
            echo "return_requests.type already present." . PHP_EOL;
        }
        echo "RmaTypeSeeder finished." . PHP_EOL;
    }
}
