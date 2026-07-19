<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Settings_model.php
 */

class Settings_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Update global settings record (ID = 1)
     */
    public function update_global_settings($data)
    {
        $this->db->where('id', 1);
        return $this->db->update('global_settings', $data);
    }

    /**
     * Check if a column exists in a table
     */
    public function column_exists($table, $column)
    {
        return $this->db->field_exists($column, $table);
    }

    /**
     * Self-healing: Check and add column dynamically
     */
    public function ensure_column_exists($table, $column, $definition)
    {
        if (!$this->db->field_exists($column, $table)) {
            $this->load->dbforge();
            $this->dbforge->add_column($table, array($column => $definition));
            return true;
        }
        return false;
    }
}
