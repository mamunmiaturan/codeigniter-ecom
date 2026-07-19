<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PermissionSeeder extends Seeder {
    public function run() {
        $this->db->empty_table('permission');
        
        $permissions = [
            // Authentication Module Permissions (Module 1)
            [
                'id' => 1,
                'module_id' => 1,
                'name' => 'Authentication',
                'prefix' => 'authentication',
                'show_view' => 1,
                'show_add' => 0,
                'show_edit' => 0,
                'show_delete' => 0
            ],
            
            // Backup Module Permissions (Module 2)
            [
                'id' => 2,
                'module_id' => 2,
                'name' => 'Database Backup',
                'prefix' => 'database_backup',
                'show_view' => 1,
                'show_add' => 1,
                'show_edit' => 0,
                'show_delete' => 1
            ],
            [
                'id' => 3,
                'module_id' => 2,
                'name' => 'Database Restore',
                'prefix' => 'database_restore',
                'show_view' => 1,
                'show_add' => 1,
                'show_edit' => 0,
                'show_delete' => 0
            ],
            
            // Language Module Permissions (Module 3)
            [
                'id' => 4,
                'module_id' => 3,
                'name' => 'Language',
                'prefix' => 'language',
                'show_view' => 1,
                'show_add' => 1,
                'show_edit' => 1,
                'show_delete' => 1
            ],
            
            // Module Module Permissions (Module 4)
            [
                'id' => 5,
                'module_id' => 4,
                'name' => 'Modules',
                'prefix' => 'modules',
                'show_view' => 1,
                'show_add' => 1,
                'show_edit' => 1,
                'show_delete' => 1
            ],
            
            // Profile Module Permissions (Module 5)
            [
                'id' => 6,
                'module_id' => 5,
                'name' => 'Profile',
                'prefix' => 'profile',
                'show_view' => 1,
                'show_add' => 0,
                'show_edit' => 1,
                'show_delete' => 0
            ],
            
            // Role Module Permissions (Module 6)
            [
                'id' => 7,
                'module_id' => 6,
                'name' => 'Role Permission',
                'prefix' => 'role_permission',
                'show_view' => 1,
                'show_add' => 1,
                'show_edit' => 1,
                'show_delete' => 1
            ],
            
            // Settings Module Permissions (Module 7)
            [
                'id' => 8,
                'module_id' => 7,
                'name' => 'Email Setting',
                'prefix' => 'email_setting',
                'show_view' => 1,
                'show_add' => 0,
                'show_edit' => 1,
                'show_delete' => 0
            ],
            [
                'id' => 9,
                'module_id' => 7,
                'name' => 'Global Setting',
                'prefix' => 'global_setting',
                'show_view' => 1,
                'show_add' => 1,
                'show_edit' => 1,
                'show_delete' => 1
            ],
            
            // SMS Module Permissions (Module 8)
            [
                'id' => 10,
                'module_id' => 8,
                'name' => 'Send SMS',
                'prefix' => 'send_sms',
                'show_view' => 1,
                'show_add' => 1,
                'show_edit' => 0,
                'show_delete' => 0
            ],
            [
                'id' => 12,
                'module_id' => 8,
                'name' => 'SMS Setting',
                'prefix' => 'sms_setting',
                'show_view' => 1,
                'show_add' => 1,
                'show_edit' => 1,
                'show_delete' => 1
            ],
            
            // User Module Permissions (Module 9)
            [
                'id' => 13,
                'module_id' => 9,
                'name' => 'User',
                'prefix' => 'user',
                'show_view' => 1,
                'show_add' => 1,
                'show_edit' => 1,
                'show_delete' => 1
            ],
            [
                'id' => 14,
                'module_id' => 9,
                'name' => 'User Disable Authentication',
                'prefix' => 'user_disable_authentication',
                'show_view' => 1,
                'show_add' => 0,
                'show_edit' => 0,
                'show_delete' => 0
            ],


            // Audit Logs Module Permissions (Module 11)
            [
                'id' => 11,
                'module_id' => 11,
                'name' => 'SMS Logs',
                'prefix' => 'sms_logs',
                'show_view' => 1,
                'show_add' => 0,
                'show_edit' => 0,
                'show_delete' => 1
            ],
            [
                'id' => 16,
                'module_id' => 11,
                'name' => 'Activity Logs',
                'prefix' => 'activity_log',
                'show_view' => 1,
                'show_add' => 0,
                'show_edit' => 0,
                'show_delete' => 1
            ],
            [
                'id' => 17,
                'module_id' => 11,
                'name' => 'Email Logs',
                'prefix' => 'email_log',
                'show_view' => 1,
                'show_add' => 0,
                'show_edit' => 0,
                'show_delete' => 1
            ],
            [
                'id' => 18,
                'module_id' => 11,
                'name' => 'System Logs',
                'prefix' => 'system_log',
                'show_view' => 1,
                'show_add' => 0,
                'show_edit' => 0,
                'show_delete' => 1
            ],

            // Import Module Permissions (Module 10)
            [
                'id' => 19,
                'module_id' => 10,
                'name' => 'Imports',
                'prefix' => 'imports',
                'show_view' => 1,
                'show_add' => 1,
                'show_edit' => 0,
                'show_delete' => 0
            ],

            // Notification Module Permissions (Module 12)
            [
                'id' => 20,
                'module_id' => 12,
                'name' => 'Notifications',
                'prefix' => 'notifications',
                'show_view' => 1,
                'show_add' => 0,
                'show_edit' => 0,
                'show_delete' => 0
            ],
        ];

        $this->db->insert_batch('permission', $permissions);
        echo "PermissionSeeder Finished with " . count($permissions) . " records." . PHP_EOL;
    }
}
