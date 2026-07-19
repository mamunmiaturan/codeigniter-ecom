<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Add_composite_index_user_privileges extends CI_Migration
{
    public function up()
    {
        // Composite index accelerates every permission check (get_db_permission queries role_id + prefix)
        $idx_check = $this->db->query(
            "SHOW INDEX FROM `user_privileges` WHERE Key_name = 'idx_role_permission'"
        )->num_rows();

        if (!$idx_check) {
            $this->db->query(
                'ALTER TABLE `user_privileges` ADD INDEX `idx_role_permission` (`role_id`, `permission_id`)'
            );
        }
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `user_privileges` DROP INDEX IF EXISTS `idx_role_permission`');
    }
}
