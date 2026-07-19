<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Reviews
 * @author   : Mamun Mia Turan
 * @filename : ReviewSeeder.php
 *
 * Idempotent bootstrap for product reviews. Safe to run repeatedly:
 *   php index.php migrate seed ReviewSeeder
 *
 * Ensures the product_reviews table, registers the `review` permission under the
 * Catalog module, grants it to Superman & Admin, seeds a few sample reviews, and
 * refreshes the compiled sidebars.
 */
class ReviewSeeder extends Seeder
{
    public function run()
    {
        $this->_create_table();
        $this->_seed_permission();
        $this->_seed_privileges();
        $this->_seed_sample_data();
        $this->_refresh_sidebar();
        echo "ReviewSeeder finished." . PHP_EOL;
    }

    private function _create_table()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `product_reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `order_id` int DEFAULT NULL,
  `author_name` varchar(150) NOT NULL,
  `author_email` varchar(190) DEFAULT NULL,
  `rating` tinyint NOT NULL DEFAULT 5,
  `title` varchar(200) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `is_verified_purchase` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_reply` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reviews_product` (`product_id`),
  KEY `idx_reviews_status` (`status`),
  KEY `idx_reviews_user` (`user_id`),
  CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
        echo "product_reviews table ensured." . PHP_EOL;
    }

    private function _seed_permission()
    {
        // Attach the review permission to the existing Catalog module (fallback: create it).
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'catalog'])->row();
        if (!$mod) {
            $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
            $this->db->insert('permission_modules', [
                'name' => 'Catalog', 'prefix' => 'catalog', 'system' => 1, 'sorted' => $sorted, 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $module_id = (int) $this->db->insert_id();
        } else {
            $module_id = (int) $mod->id;
        }

        if (!$this->db->get_where('permission', ['prefix' => 'review'])->row()) {
            $this->db->insert('permission', [
                'module_id' => $module_id,
                'name' => 'Reviews',
                'prefix' => 'review',
                'show_view' => 1, 'show_add' => 0, 'show_edit' => 1, 'show_delete' => 1,
            ]);
        }
        echo "Review permission ensured." . PHP_EOL;
    }

    private function _seed_privileges()
    {
        $perm = $this->db->get_where('permission', ['prefix' => 'review'])->row();
        if (!$perm) {
            return;
        }
        foreach ([1, 2] as $role_id) { // Superman + Admin
            if (!$this->db->get_where('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id])->num_rows()) {
                $this->db->insert('user_privileges', [
                    'role_id' => $role_id, 'permission_id' => $perm->id,
                    'is_view' => 1, 'is_add' => 0, 'is_edit' => 1, 'is_delete' => 1,
                ]);
            }
        }
        echo "Review privileges granted to Superman & Admin." . PHP_EOL;
    }

    private function _seed_sample_data()
    {
        if ((int) $this->db->count_all('product_reviews') > 0) {
            echo "Reviews already present — skipping samples." . PHP_EOL;
            return;
        }
        $now = date('Y-m-d H:i:s');
        $samples = [
            ['slug' => 'sample-smartphone', 'author' => 'Rahim Uddin',  'rating' => 5, 'title' => 'Excellent phone', 'comment' => 'Great value for money, camera is superb.', 'verified' => 1],
            ['slug' => 'sample-smartphone', 'author' => 'Karim Ali',    'rating' => 4, 'title' => 'Good but battery', 'comment' => 'Performance is solid, battery could be better.', 'verified' => 0],
            ['slug' => 'wireless-earbuds',  'author' => 'Nusrat Jahan', 'rating' => 5, 'title' => 'Crystal clear',    'comment' => 'Sound quality is amazing for the price.', 'verified' => 1],
        ];
        $added = 0;
        foreach ($samples as $s) {
            $p = $this->db->get_where('products', ['slug' => $s['slug']])->row();
            if (!$p) { continue; }
            $this->db->insert('product_reviews', [
                'product_id' => (int) $p->id,
                'author_name' => $s['author'],
                'rating' => $s['rating'],
                'title' => $s['title'],
                'comment' => $s['comment'],
                'is_verified_purchase' => $s['verified'],
                'status' => 'approved',
                'created_at' => $now,
            ]);
            $added++;
        }
        echo "Review sample data ensured ({$added} added)." . PHP_EOL;
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
