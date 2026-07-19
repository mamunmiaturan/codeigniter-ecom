<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Support
 * @author   : Mamun Mia Turan
 * @filename : SupportDeskSeeder.php
 *
 * Bootstraps the customer support desk:
 *   - `complaints`      : customer-filed complaints (single message + status)
 *   - `support_tickets` : a ticket a staff member opens ON a complaint (thread)
 *   - `ticket_replies`  : the ticket conversation (customer <-> admin)
 *
 * Registers the `complaint` and `ticket` permissions under the Support module,
 * grants them to Superman & Admin, and refreshes sidebars. Idempotent.
 *
 *   php index.php system/migrate seed SupportDeskSeeder
 */
class SupportDeskSeeder extends Seeder
{
    public function run()
    {
        $this->_create_tables();
        $this->_seed_permission();
        $this->_refresh_sidebar();
        echo "SupportDeskSeeder finished." . PHP_EOL;
    }

    private function _create_tables()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `complaints` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(191) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `order_id` int DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('New','Under Review','Resolved','Closed') NOT NULL DEFAULT 'New',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_complaints_customer` (`customer_id`),
  KEY `idx_complaints_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(30) NOT NULL,
  `complaint_id` int DEFAULT NULL,
  `customer_id` int DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `priority` enum('Low','Medium','High') NOT NULL DEFAULT 'Medium',
  `status` enum('Open','In Progress','Answered','Closed') NOT NULL DEFAULT 'Open',
  `assigned_to` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ticket_number` (`ticket_number`),
  KEY `idx_tickets_customer` (`customer_id`),
  KEY `idx_tickets_complaint` (`complaint_id`),
  KEY `idx_tickets_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `ticket_replies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `sender_type` enum('customer','admin') NOT NULL,
  `sender_id` int DEFAULT NULL,
  `sender_name` varchar(150) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_replies_ticket` (`ticket_id`),
  CONSTRAINT `fk_ticket_replies_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
        echo "support desk tables ensured." . PHP_EOL;
    }

    private function _seed_permission()
    {
        // Attach both permissions to the Support module (create it if the
        // SupportModuleSeeder hasn't run yet, so ordering doesn't matter).
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'support'])->row();
        if (!$mod) {
            $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
            $this->db->insert('permission_modules', [
                'name' => 'Support', 'prefix' => 'support', 'system' => 1, 'sorted' => $sorted, 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $module_id = (int) $this->db->insert_id();
        } else {
            $module_id = (int) $mod->id;
        }

        $permissions = [
            ['name' => 'Complaints', 'prefix' => 'complaint'],
            ['name' => 'Tickets',    'prefix' => 'ticket'],
        ];
        foreach ($permissions as $p) {
            if (!$this->db->get_where('permission', ['prefix' => $p['prefix']])->row()) {
                $this->db->insert('permission', [
                    'module_id' => $module_id, 'name' => $p['name'], 'prefix' => $p['prefix'],
                    'show_view' => 1, 'show_add' => 1, 'show_edit' => 1, 'show_delete' => 1,
                ]);
            }
            $perm = $this->db->get_where('permission', ['prefix' => $p['prefix']])->row();
            if ($perm) {
                foreach ([1, 2] as $role_id) {
                    if (!$this->db->get_where('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id])->num_rows()) {
                        $this->db->insert('user_privileges', [
                            'role_id' => $role_id, 'permission_id' => $perm->id,
                            'is_view' => 1, 'is_add' => 1, 'is_edit' => 1, 'is_delete' => 1,
                        ]);
                    }
                }
            }
        }
        echo "complaint + ticket permissions ensured." . PHP_EOL;
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
