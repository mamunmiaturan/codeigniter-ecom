<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / CMS
 * @author   : Mamun Mia Turan
 * @filename : CmsSeeder.php
 *
 * Idempotent bootstrap for CMS pages:
 *   php index.php migrate seed CmsSeeder
 */
class CmsSeeder extends Seeder
{
    public function run()
    {
        $this->_create_table();
        $this->_seed_permission();
        $this->_seed_privileges();
        $this->_seed_pages();
        $this->_refresh_sidebar();
        echo "CmsSeeder finished." . PHP_EOL;
    }

    private function _create_table()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `cms_pages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `content` longtext DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `show_in_footer` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int NOT NULL DEFAULT 0,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cms_pages_slug` (`slug`),
  KEY `idx_cms_pages_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
        echo "cms_pages table ensured." . PHP_EOL;
    }

    private function _seed_permission()
    {
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'cms'])->row();
        if (!$mod) {
            $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
            $this->db->insert('permission_modules', ['name' => 'CMS', 'prefix' => 'cms', 'system' => 1, 'sorted' => $sorted, 'created_at' => date('Y-m-d H:i:s')]);
            $module_id = (int) $this->db->insert_id();
        } else {
            $module_id = (int) $mod->id;
        }
        if (!$this->db->get_where('permission', ['prefix' => 'cms'])->row()) {
            $this->db->insert('permission', [
                'module_id' => $module_id, 'name' => 'CMS Pages', 'prefix' => 'cms',
                'show_view' => 1, 'show_add' => 1, 'show_edit' => 1, 'show_delete' => 1,
            ]);
        }
        echo "cms permission ensured." . PHP_EOL;
    }

    private function _seed_privileges()
    {
        $perm = $this->db->get_where('permission', ['prefix' => 'cms'])->row();
        if (!$perm) {
            return;
        }
        foreach ([1, 2] as $role_id) {
            if (!$this->db->get_where('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id])->num_rows()) {
                $this->db->insert('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id, 'is_view' => 1, 'is_add' => 1, 'is_edit' => 1, 'is_delete' => 1]);
            }
        }
        echo "cms privileges granted to Superman & Admin." . PHP_EOL;
    }

    private function _seed_pages()
    {
        $now = date('Y-m-d H:i:s');
        $pages = [
            ['title' => 'About Us', 'slug' => 'about-us', 'sort' => 1, 'content' => '<p>We are a Bangladesh-based online store delivering quality products nationwide with cash on delivery.</p>'],
            ['title' => 'Privacy Policy', 'slug' => 'privacy-policy', 'sort' => 2, 'content' => '<p>We respect your privacy. Your personal information is used only to process and deliver your orders.</p>'],
            ['title' => 'Terms & Conditions', 'slug' => 'terms-conditions', 'sort' => 3, 'content' => '<p>By using this site you agree to our terms of service, pricing, delivery and returns policy.</p>'],
            ['title' => 'Return & Refund Policy', 'slug' => 'return-refund-policy', 'sort' => 4, 'content' => '<p>Items can be returned within 7 days of delivery if damaged or incorrect. Refunds are processed after inspection.</p>'],
            ['title' => 'Contact Us', 'slug' => 'contact-us', 'sort' => 5, 'content' => '<p>Questions? Reach our support team — we usually reply within one business day.</p>'],
        ];
        $added = 0;
        foreach ($pages as $p) {
            if (!$this->db->get_where('cms_pages', ['slug' => $p['slug']])->row()) {
                $this->db->insert('cms_pages', [
                    'title' => $p['title'], 'slug' => $p['slug'], 'content' => $p['content'],
                    'meta_title' => $p['title'], 'status' => 'Active', 'show_in_footer' => 1, 'sort_order' => $p['sort'], 'created_at' => $now,
                ]);
                $added++;
            }
        }
        echo "cms pages ensured ({$added} added)." . PHP_EOL;
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
