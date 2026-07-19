<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Banners
 * @author   : Mamun Mia Turan
 * @filename : BannerSeeder.php
 *
 * Idempotent bootstrap for storefront banners:
 *   php index.php migrate seed BannerSeeder
 */
class BannerSeeder extends Seeder
{
    public function run()
    {
        $this->_create_table();
        $this->_ensure_upload_dir();
        $this->_seed_permission();
        $this->_seed_privileges();
        $this->_seed_banners();
        $this->_refresh_sidebar();
        echo "BannerSeeder finished." . PHP_EOL;
    }

    private function _create_table()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `banners` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) DEFAULT NULL,
  `subtitle` varchar(300) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `button_text` varchar(80) DEFAULT NULL,
  `type` enum('slider','promo','popup','announcement') NOT NULL DEFAULT 'slider',
  `position` int NOT NULL DEFAULT 0,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_banner_type_status` (`type`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
        echo "banners table ensured." . PHP_EOL;
    }

    private function _ensure_upload_dir()
    {
        $dir = FCPATH . 'uploads/banner/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    private function _seed_permission()
    {
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'banner'])->row();
        if (!$mod) {
            $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
            $this->db->insert('permission_modules', ['name' => 'Banners', 'prefix' => 'banner', 'system' => 1, 'sorted' => $sorted, 'created_at' => date('Y-m-d H:i:s')]);
            $module_id = (int) $this->db->insert_id();
        } else {
            $module_id = (int) $mod->id;
        }
        if (!$this->db->get_where('permission', ['prefix' => 'banner'])->row()) {
            $this->db->insert('permission', [
                'module_id' => $module_id, 'name' => 'Banners', 'prefix' => 'banner',
                'show_view' => 1, 'show_add' => 1, 'show_edit' => 1, 'show_delete' => 1,
            ]);
        }
        echo "banner permission ensured." . PHP_EOL;
    }

    private function _seed_privileges()
    {
        $perm = $this->db->get_where('permission', ['prefix' => 'banner'])->row();
        if (!$perm) {
            return;
        }
        foreach ([1, 2] as $role_id) {
            if (!$this->db->get_where('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id])->num_rows()) {
                $this->db->insert('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id, 'is_view' => 1, 'is_add' => 1, 'is_edit' => 1, 'is_delete' => 1]);
            }
        }
        echo "banner privileges granted to Superman & Admin." . PHP_EOL;
    }

    private function _seed_banners()
    {
        $now = date('Y-m-d H:i:s');
        $banners = [
            [
                'title'       => 'Everything you need, delivered to your door',
                'subtitle'    => 'Browse real products at real prices. Cash on delivery all over Bangladesh.',
                'button_text' => 'Start shopping',
                'link_url'    => 'landing/shop',
                'type'        => 'slider',
                'position'    => 1,
            ],
            [
                'title'       => 'Save 10% with code SAVE10',
                'subtitle'    => 'Apply the coupon at checkout on your first order and enjoy instant savings.',
                'button_text' => 'Shop deals',
                'link_url'    => 'landing/shop',
                'type'        => 'slider',
                'position'    => 2,
            ],
            [
                'title'       => 'Free delivery on orders over ৳3000',
                'subtitle'    => null,
                'button_text' => null,
                'link_url'    => 'landing/shop',
                'type'        => 'announcement',
                'position'    => 1,
            ],
            [
                'title'       => 'Welcome to our store!',
                'subtitle'    => 'Subscribe and get 10% off your first order with coupon code SAVE10.',
                'button_text' => 'Start shopping',
                'link_url'    => 'landing/shop',
                'type'        => 'popup',
                'position'    => 1,
            ],
        ];
        $added = 0;
        foreach ($banners as $b) {
            if (!$this->db->get_where('banners', ['title' => $b['title'], 'type' => $b['type']])->row()) {
                $this->db->insert('banners', [
                    'title'       => $b['title'],
                    'subtitle'    => $b['subtitle'],
                    'button_text' => $b['button_text'],
                    'link_url'    => $b['link_url'],
                    'type'        => $b['type'],
                    'position'    => $b['position'],
                    'status'      => 'Active',
                    'created_at'  => $now,
                ]);
                $added++;
            }
        }
        echo "banners ensured ({$added} added)." . PHP_EOL;
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
