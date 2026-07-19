<?php
defined('BASEPATH') or exit('No direct script access allowed');

class UserPrivilegesSeeder extends Seeder
{
    public function run()
    {
        $this->db->empty_table('user_privileges');
        $privileges = [];

        // Fetch all permissions currently seeded in the database
        $permissions = $this->db->get('permission')->result_array();

        foreach ($permissions as $perm) {
            // 1. Superman (Role 1) - All privileges
            $privileges[] = [
                'role_id' => 1, 
                'permission_id' => $perm['id'], 
                'is_view' => 1, 
                'is_add' => 1, 
                'is_edit' => 1, 
                'is_delete' => 1
            ];

            // 2. Admin (Role 2) - All privileges
            $privileges[] = [
                'role_id' => 2, 
                'permission_id' => $perm['id'], 
                'is_view' => 1, 
                'is_add' => 1, 
                'is_edit' => 1, 
                'is_delete' => 1
            ];
        }

        $this->db->insert_batch('user_privileges', $privileges);
        echo "userPrivilegesSeeder Finished with " . count($privileges) . " records." . PHP_EOL;
    }
}
