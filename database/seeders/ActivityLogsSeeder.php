<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ActivityLogsSeeder extends Seeder
{
    public function run()
    {
        $this->db->empty_table('activity_logs');
        $this->db->insert_batch('activity_logs', [
            [
                'user_id' => 1,
                'module_name' => 'Meals',
                'table_name' => 'meals',
                'row_id' => 1,
                'action' => 'update',
                'description' => 'Kacchi price changed from 250 to 280',
                'ip_address' => '103.1.2.3',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'user_id' => 2,
                'module_name' => 'Meal Booking',
                'table_name' => 'meal_booking',
                'row_id' => 10,
                'action' => 'delete',
                'description' => 'Meal cancelled for user ID 10',
                'ip_address' => '103.1.2.4',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
        echo "ActivityLogsSeeder Finished." . PHP_EOL;
    }
}
