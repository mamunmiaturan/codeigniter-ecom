<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PermissionModulesSeeder extends Seeder {
    public function run() {
        $this->db->empty_table('permission_modules');
        
        $modules = [
            [
                'id' => 1,
                'name' => 'Authentication',
                'prefix' => 'authentication',
                'system' => 1,
                'sorted' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'name' => 'Backup',
                'prefix' => 'backup',
                'system' => 1,
                'sorted' => 2,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 3,
                'name' => 'Language',
                'prefix' => 'language',
                'system' => 1,
                'sorted' => 3,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 4,
                'name' => 'Module',
                'prefix' => 'module',
                'system' => 1,
                'sorted' => 4,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 5,
                'name' => 'Profile',
                'prefix' => 'profile',
                'system' => 1,
                'sorted' => 5,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 6,
                'name' => 'Role',
                'prefix' => 'role',
                'system' => 1,
                'sorted' => 6,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 7,
                'name' => 'Settings',
                'prefix' => 'settings',
                'system' => 1,
                'sorted' => 7,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 8,
                'name' => 'SMS',
                'prefix' => 'sms',
                'system' => 1,
                'sorted' => 8,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 9,
                'name' => 'User',
                'prefix' => 'user',
                'system' => 1,
                'sorted' => 9,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 10,
                'name' => 'Import',
                'prefix' => 'import',
                'system' => 1,
                'sorted' => 10,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 11,
                'name' => 'Audit Logs',
                'prefix' => 'audit_logs',
                'system' => 1,
                'sorted' => 11,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 12,
                'name' => 'Notification',
                'prefix' => 'notification',
                'system' => 1,
                'sorted' => 12,
                'created_at' => date('Y-m-d H:i:s')
            ],
        ];

        $this->db->insert_batch('permission_modules', $modules);
        echo "PermissionModulesSeeder Finished with " . count($modules) . " records." . PHP_EOL;
    }
}
