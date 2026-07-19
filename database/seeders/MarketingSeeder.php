<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Marketing
 * @author   : Mamun Mia Turan
 * @filename : MarketingSeeder.php
 *
 * Idempotent bootstrap for newsletter subscribers:
 *   php index.php migrate seed MarketingSeeder
 */
class MarketingSeeder extends Seeder
{
    public function run()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(190) NOT NULL,
  `status` enum('subscribed','unsubscribed') NOT NULL DEFAULT 'subscribed',
  `source` varchar(50) DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_newsletter_email` (`email`),
  KEY `idx_newsletter_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
        echo "newsletter_subscribers table ensured." . PHP_EOL;

        // Permission under a Marketing module.
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'marketing'])->row();
        if (!$mod) {
            $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
            $this->db->insert('permission_modules', ['name' => 'Marketing', 'prefix' => 'marketing', 'system' => 1, 'sorted' => $sorted, 'created_at' => date('Y-m-d H:i:s')]);
            $module_id = (int) $this->db->insert_id();
        } else {
            $module_id = (int) $mod->id;
        }
        if (!$this->db->get_where('permission', ['prefix' => 'newsletter'])->row()) {
            $this->db->insert('permission', ['module_id' => $module_id, 'name' => 'Newsletter', 'prefix' => 'newsletter', 'show_view' => 1, 'show_add' => 0, 'show_edit' => 1, 'show_delete' => 1]);
        }
        $perm = $this->db->get_where('permission', ['prefix' => 'newsletter'])->row();
        if ($perm) {
            foreach ([1, 2] as $role_id) {
                if (!$this->db->get_where('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id])->num_rows()) {
                    $this->db->insert('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id, 'is_view' => 1, 'is_add' => 0, 'is_edit' => 1, 'is_delete' => 1]);
                }
            }
        }
        echo "newsletter permission ensured." . PHP_EOL;

        $this->_refresh_sidebar();
        echo "MarketingSeeder finished." . PHP_EOL;
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
