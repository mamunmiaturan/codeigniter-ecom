<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Blog
 * @author   : Mamun Mia Turan
 * @filename : BlogSeeder.php
 *
 * Idempotent bootstrap for the Blog module:
 *   php index.php migrate seed BlogSeeder
 */
class BlogSeeder extends Seeder
{
    public function run()
    {
        $this->_create_table();
        $this->_seed_permission();
        $this->_seed_privileges();
        $this->_seed_posts();
        $this->_refresh_sidebar();
        echo "BlogSeeder finished." . PHP_EOL;
    }

    private function _create_table()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `excerpt` varchar(500) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `category` varchar(120) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `status` enum('Draft','Active','Inactive') NOT NULL DEFAULT 'Draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` varchar(300) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_blog_slug` (`slug`),
  KEY `idx_blog_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
        echo "blog_posts table ensured." . PHP_EOL;
    }

    private function _seed_permission()
    {
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'blog'])->row();
        if (!$mod) {
            $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
            $this->db->insert('permission_modules', ['name' => 'Blog', 'prefix' => 'blog', 'system' => 1, 'sorted' => $sorted, 'created_at' => date('Y-m-d H:i:s')]);
            $module_id = (int) $this->db->insert_id();
        } else {
            $module_id = (int) $mod->id;
        }
        if (!$this->db->get_where('permission', ['prefix' => 'blog'])->row()) {
            $this->db->insert('permission', [
                'module_id' => $module_id, 'name' => 'Blog', 'prefix' => 'blog',
                'show_view' => 1, 'show_add' => 1, 'show_edit' => 1, 'show_delete' => 1,
            ]);
        }
        echo "blog permission ensured." . PHP_EOL;
    }

    private function _seed_privileges()
    {
        $perm = $this->db->get_where('permission', ['prefix' => 'blog'])->row();
        if (!$perm) {
            return;
        }
        foreach ([1, 2] as $role_id) {
            if (!$this->db->get_where('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id])->num_rows()) {
                $this->db->insert('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id, 'is_view' => 1, 'is_add' => 1, 'is_edit' => 1, 'is_delete' => 1]);
            }
        }
        echo "blog privileges granted to Superman & Admin." . PHP_EOL;
    }

    private function _seed_posts()
    {
        $now = date('Y-m-d H:i:s');
        $posts = [
            [
                'title'    => 'Welcome to Our Store Blog',
                'slug'     => 'welcome-to-our-store-blog',
                'excerpt'  => 'News, product highlights and shopping tips from our team.',
                'content'  => '<p>Welcome to our blog! Here we share the latest news, product launches and handy tips to help you shop smarter. Stay tuned for regular updates.</p>',
                'category' => 'News',
                'tags'     => 'welcome,news,store',
                'featured' => 1,
            ],
            [
                'title'    => 'Top 5 Products This Season',
                'slug'     => 'top-5-products-this-season',
                'excerpt'  => 'Our best-selling picks that customers love right now.',
                'content'  => '<p>From everyday essentials to seasonal favourites, here are the five products flying off our shelves this season. Grab yours before they sell out.</p>',
                'category' => 'Shopping',
                'tags'     => 'products,trending,bestsellers',
                'featured' => 0,
            ],
            [
                'title'    => 'How Cash on Delivery Works',
                'slug'     => 'how-cash-on-delivery-works',
                'excerpt'  => 'A simple guide to paying for your order at your doorstep.',
                'content'  => '<p>Cash on delivery lets you pay only when your order arrives. In this guide we walk you through how it works, where it is available and what to expect at delivery.</p>',
                'category' => 'Guides',
                'tags'     => 'cod,payment,delivery',
                'featured' => 0,
            ],
        ];
        $added = 0;
        foreach ($posts as $p) {
            if (!$this->db->get_where('blog_posts', ['slug' => $p['slug']])->row()) {
                $this->db->insert('blog_posts', [
                    'title'        => $p['title'],
                    'slug'         => $p['slug'],
                    'excerpt'      => $p['excerpt'],
                    'content'      => $p['content'],
                    'category'     => $p['category'],
                    'tags'         => $p['tags'],
                    'status'       => 'Active',
                    'is_featured'  => $p['featured'],
                    'published_at' => $now,
                    'meta_title'   => $p['title'],
                    'created_at'   => $now,
                ]);
                $added++;
            }
        }
        echo "blog posts ensured ({$added} added)." . PHP_EOL;
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
