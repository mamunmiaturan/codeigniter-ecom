<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Adds the dynamic-hierarchy columns to `roles`:
 *   - level     : authority order (lower number = higher authority)
 *   - parent_id : organisational parent role (for the organogram)
 *
 * Idempotent (field_exists guards) so it is safe on partially-migrated DBs and
 * reproducible under `migrate:fresh`.
 */
class Migration_Add_Hierarchy_To_Roles extends CI_Migration
{
    public function up()
    {
        $this->load->dbforge();

        if (!$this->db->field_exists('level', 'roles')) {
            $this->dbforge->add_column('roles', [
                'level' => [
                    'type'       => 'INT',
                    'null'       => false,
                    'default'    => 50,
                    'after'      => 'short_form',
                ],
            ]);
        }

        if (!$this->db->field_exists('parent_id', 'roles')) {
            $this->dbforge->add_column('roles', [
                'parent_id' => [
                    'type'    => 'INT',
                    'null'    => true,
                    'default' => null,
                    'after'   => 'level',
                ],
            ]);
        }
    }

    public function down()
    {
        $this->load->dbforge();
        if ($this->db->field_exists('parent_id', 'roles')) {
            $this->dbforge->drop_column('roles', 'parent_id');
        }
        if ($this->db->field_exists('level', 'roles')) {
            $this->dbforge->drop_column('roles', 'level');
        }
    }
}
