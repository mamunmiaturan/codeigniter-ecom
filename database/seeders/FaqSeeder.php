<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / FAQ
 * @author   : Mamun Mia Turan
 * @filename : FaqSeeder.php
 *
 * Idempotent bootstrap for FAQs:
 *   php index.php migrate seed FaqSeeder
 */
class FaqSeeder extends Seeder
{
    public function run()
    {
        $this->_create_table();
        $this->_seed_permission();
        $this->_seed_privileges();
        $this->_seed_faqs();
        $this->_refresh_sidebar();
        echo "FaqSeeder finished." . PHP_EOL;
    }

    private function _create_table()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `faqs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `question` varchar(255) NOT NULL,
  `answer` longtext DEFAULT NULL,
  `category` varchar(120) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `sort_order` int NOT NULL DEFAULT 0,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_faqs_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
        echo "faqs table ensured." . PHP_EOL;
    }

    private function _seed_permission()
    {
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'faq'])->row();
        if (!$mod) {
            $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
            $this->db->insert('permission_modules', ['name' => 'FAQ', 'prefix' => 'faq', 'system' => 1, 'sorted' => $sorted, 'created_at' => date('Y-m-d H:i:s')]);
            $module_id = (int) $this->db->insert_id();
        } else {
            $module_id = (int) $mod->id;
        }
        if (!$this->db->get_where('permission', ['prefix' => 'faq'])->row()) {
            $this->db->insert('permission', [
                'module_id' => $module_id, 'name' => 'FAQ', 'prefix' => 'faq',
                'show_view' => 1, 'show_add' => 1, 'show_edit' => 1, 'show_delete' => 1,
            ]);
        }
        echo "faq permission ensured." . PHP_EOL;
    }

    private function _seed_privileges()
    {
        $perm = $this->db->get_where('permission', ['prefix' => 'faq'])->row();
        if (!$perm) {
            return;
        }
        foreach ([1, 2] as $role_id) {
            if (!$this->db->get_where('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id])->num_rows()) {
                $this->db->insert('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id, 'is_view' => 1, 'is_add' => 1, 'is_edit' => 1, 'is_delete' => 1]);
            }
        }
        echo "faq privileges granted to Superman & Admin." . PHP_EOL;
    }

    private function _seed_faqs()
    {
        $now = date('Y-m-d H:i:s');
        $faqs = [
            ['question' => 'How do I place an order?', 'category' => 'Ordering', 'sort' => 1, 'answer' => '<p>Browse the shop, add the items you want to your cart, then proceed to checkout and confirm your delivery details.</p>'],
            ['question' => 'Can I order without creating an account?', 'category' => 'Ordering', 'sort' => 2, 'answer' => '<p>Yes. Guest checkout with cash on delivery is fully supported — no account is required to place an order.</p>'],
            ['question' => 'What payment methods do you accept?', 'category' => 'Payment & Delivery', 'sort' => 3, 'answer' => '<p>We accept cash on delivery nationwide, plus supported online payment gateways at checkout.</p>'],
            ['question' => 'How long does delivery take?', 'category' => 'Payment & Delivery', 'sort' => 4, 'answer' => '<p>Orders inside Dhaka are usually delivered within 1-2 business days; outside Dhaka typically takes 3-5 business days.</p>'],
        ];
        $added = 0;
        foreach ($faqs as $f) {
            if (!$this->db->get_where('faqs', ['question' => $f['question']])->row()) {
                $this->db->insert('faqs', [
                    'question' => $f['question'], 'answer' => $f['answer'], 'category' => $f['category'],
                    'status' => 'Active', 'sort_order' => $f['sort'], 'created_at' => $now,
                ]);
                $added++;
            }
        }
        echo "faqs ensured ({$added} added)." . PHP_EOL;
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
