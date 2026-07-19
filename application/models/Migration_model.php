<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration Model - Handles migration table setup, database schema resetting, and version checking.
 */
class Migration_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Check if table exists
     */
    public function table_exists($table)
    {
        return $this->db->table_exists($table);
    }

    /**
     * Create migration table and seed version 0
     */
    public function create_migration_table($table)
    {
        $this->load->dbforge();
        $this->dbforge->add_field(array(
            'version' => array(
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => TRUE,
            ),
        ));
        $this->dbforge->create_table($table, TRUE);
        return $this->db->insert($table, array('version' => 0));
    }

    /**
     * Reset version to 0
     */
    public function reset_migration_version($table)
    {
        return $this->db->update($table, array('version' => 0));
    }

    /**
     * Disable foreign key checks
     */
    public function disable_foreign_key_checks()
    {
        return $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
    }

    /**
     * Enable foreign key checks
     */
    public function enable_foreign_key_checks()
    {
        return $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * List all database tables
     */
    public function list_tables()
    {
        return $this->db->list_tables();
    }

    /**
     * Drop table dynamically
     */
    public function drop_table($table)
    {
        $this->load->dbforge();
        return $this->dbforge->drop_table($table, TRUE);
    }

    /**
     * Get version of migrations table
     */
    public function get_migration_version($table)
    {
        $query = $this->db->get($table);
        if ($query->num_rows() > 0) {
            return $query->row()->version;
        }
        return null;
    }
}
