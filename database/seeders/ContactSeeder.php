<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Support
 * @author   : Mamun Mia Turan
 * @filename : ContactSeeder.php
 *
 * Idempotent bootstrap for the storefront contact inbox:
 *   php index.php migrate seed ContactSeeder
 */
class ContactSeeder extends Seeder
{
    public function run()
    {
        $this->_create_table();
        $this->_seed_permission();
        $this->_seed_privileges();
        $this->_seed_messages();
        $this->_refresh_sidebar();
        echo "ContactSeeder finished." . PHP_EOL;
    }

    private function _create_table()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('New','Read','Replied','Closed') NOT NULL DEFAULT 'New',
  `admin_reply` text DEFAULT NULL,
  `replied_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_contact_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
        echo "contact_messages table ensured." . PHP_EOL;
    }

    private function _seed_permission()
    {
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'contact'])->row();
        if (!$mod) {
            $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
            $this->db->insert('permission_modules', ['name' => 'Contact Messages', 'prefix' => 'contact', 'system' => 1, 'sorted' => $sorted, 'created_at' => date('Y-m-d H:i:s')]);
            $module_id = (int) $this->db->insert_id();
        } else {
            $module_id = (int) $mod->id;
        }
        if (!$this->db->get_where('permission', ['prefix' => 'contact'])->row()) {
            $this->db->insert('permission', [
                'module_id' => $module_id, 'name' => 'Contact Messages', 'prefix' => 'contact',
                'show_view' => 1, 'show_add' => 0, 'show_edit' => 1, 'show_delete' => 1,
            ]);
        }
        echo "contact permission ensured." . PHP_EOL;
    }

    private function _seed_privileges()
    {
        $perm = $this->db->get_where('permission', ['prefix' => 'contact'])->row();
        if (!$perm) {
            return;
        }
        foreach ([1, 2] as $role_id) {
            if (!$this->db->get_where('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id])->num_rows()) {
                $this->db->insert('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id, 'is_view' => 1, 'is_add' => 0, 'is_edit' => 1, 'is_delete' => 1]);
            }
        }
        echo "contact privileges granted to Superman & Admin." . PHP_EOL;
    }

    private function _seed_messages()
    {
        $now = date('Y-m-d H:i:s');
        $messages = [
            [
                'name' => 'Rahim Uddin', 'email' => 'rahim.uddin@example.com', 'phone' => '01710000001',
                'subject' => 'Delivery time to Chattogram', 'message' => 'Hello, I placed an order yesterday. How many days does delivery to Chattogram usually take?', 'status' => 'New',
            ],
            [
                'name' => 'Fatema Akter', 'email' => 'fatema.akter@example.com', 'phone' => '01810000002',
                'subject' => 'Wrong size received', 'message' => 'I received a different size than what I ordered. Please help me arrange a return.', 'status' => 'New',
            ],
        ];
        $added = 0;
        foreach ($messages as $m) {
            if (!$this->db->get_where('contact_messages', ['email' => $m['email'], 'subject' => $m['subject']])->row()) {
                $this->db->insert('contact_messages', array_merge($m, ['created_at' => $now]));
                $added++;
            }
        }
        echo "contact messages ensured ({$added} added)." . PHP_EOL;
    }

    private function _refresh_sidebar()
    {
        try {
            require_once APPPATH . 'helpers/sidebar_helper.php';
            $this->ci->load->helper(['url', 'general', 'permission', 'translation']);
            if (function_exists('generate_sidebar_files')) {
                generate_sidebar_files();
                echo "Sidebar files regenerated." . PHP_EOL;
                return;
            }
        } catch (Throwable $e) {
            echo "Sidebar regen deferred: " . $e->getMessage() . PHP_EOL;
        }
        foreach (glob(APPPATH . 'views/layout/sidebar/*.php') as $file) {
            @unlink($file);
        }
    }
}
