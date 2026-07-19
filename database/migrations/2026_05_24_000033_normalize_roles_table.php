<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Adds timestamps + soft-delete + UNIQUE(name) to the `roles` table.
 * Previously the table had no created_at/updated_at/deleted_at and `name`
 * could be duplicated. See AUDIT_REPORT MED-A4.
 */
class Migration_Normalize_Roles_Table extends CI_Migration
{
    public function up()
    {
        $this->load->dbforge();

        if (!$this->db->field_exists('created_at', 'roles')) {
            // Raw query — dbforge quotes 'CURRENT_TIMESTAMP' as a string literal.
            $this->db->query("ALTER TABLE `roles` ADD COLUMN `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        }
        if (!$this->db->field_exists('updated_at', 'roles')) {
            // Use raw query so we can attach ON UPDATE clause.
            $this->db->query("ALTER TABLE `roles` ADD COLUMN `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP");
        }
        if (!$this->db->field_exists('deleted_at', 'roles')) {
            $this->dbforge->add_column('roles', [
                'deleted_at' => ['type' => 'DATETIME', 'null' => TRUE],
            ]);
        }

        // De-duplicate before adding UNIQUE.
        $dupes = $this->db
            ->select('name, COUNT(*) AS c', false)
            ->from('roles')
            ->group_by('name')
            ->having('c >', 1)
            ->get()
            ->result_array();
        foreach ($dupes as $dupe) {
            $rows = $this->db->where('name', $dupe['name'])->order_by('id', 'ASC')->get('roles')->result_array();
            // Keep the first row; rename the rest with -dup-<id> suffix to preserve data.
            foreach (array_slice($rows, 1) as $row) {
                $this->db->where('id', $row['id'])->update('roles', [
                    'name' => substr($row['name'], 0, 40) . '-dup-' . $row['id'],
                ]);
            }
        }

        $has_unique = $this->db
            ->query("SHOW INDEX FROM `roles` WHERE Key_name = 'uk_role_name'")
            ->num_rows();
        if (!$has_unique) {
            $this->db->query("ALTER TABLE `roles` ADD UNIQUE KEY `uk_role_name` (`name`)");
        }

        // Indexes for soft-delete-filtered queries on hot tables.
        foreach (['users', 'login_credential', 'imports', 'notifications'] as $tbl) {
            if (!$this->db->table_exists($tbl)) {
                continue;
            }
            if (!$this->db->field_exists('deleted_at', $tbl)) {
                continue;
            }
            $idx_name = 'idx_' . $tbl . '_deleted_at';
            $has = $this->db
                ->query("SHOW INDEX FROM `{$tbl}` WHERE Key_name = '{$idx_name}'")
                ->num_rows();
            if (!$has) {
                $this->db->query("ALTER TABLE `{$tbl}` ADD INDEX `{$idx_name}` (`deleted_at`)");
            }
        }

        // Composite index for the very common (status, role) lookup in login_credential.
        if ($this->db->table_exists('login_credential')) {
            $has = $this->db
                ->query("SHOW INDEX FROM `login_credential` WHERE Key_name = 'idx_status_role'")
                ->num_rows();
            if (!$has) {
                $this->db->query("ALTER TABLE `login_credential` ADD INDEX `idx_status_role` (`status`, `role`)");
            }
        }
    }

    public function down()
    {
        // Non-destructive — keep timestamps/indexes; only drop UNIQUE to allow rollback.
        $this->db->query("ALTER TABLE `roles` DROP INDEX `uk_role_name`");
    }
}
