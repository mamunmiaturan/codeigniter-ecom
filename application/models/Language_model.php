<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Language Model - Handles database operations for translations and languages management
 */
class Language_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all languages
     */
    public function get_languages()
    {
        return $this->db->order_by('id', 'ASC')->get('language_list')->result_array();
    }

    /**
     * Update language code
     */
    public function update_language_code($id, $code)
    {
        $this->db->where('id', $id);
        return $this->db->update('language_list', array('code' => $code));
    }

    /**
     * Check if column exists
     */
    public function column_exists($table, $column)
    {
        return $this->db->field_exists($column, $table);
    }

    /**
     * Get fields of languages table
     */
    public function get_languages_fields()
    {
        return $this->db->list_fields('languages');
    }

    /**
     * Add column dynamically
     */
    public function add_column($table, $column, $definition)
    {
        if (!$this->db->field_exists($column, $table)) {
            $this->load->dbforge();
            $this->dbforge->add_column($table, array($column => $definition));
            return true;
        }
        return false;
    }

    /**
     * Whitelist identifier shape: must look like [a-zA-Z_][a-zA-Z0-9_-]{0,63}.
     * Defense-in-depth — callers also validate, but the model never trusts.
     */
    private function _safe_ident($name)
    {
        return is_string($name)
            && $name !== ''
            && preg_match('/^[a-zA-Z_][a-zA-Z0-9_-]{0,63}$/', $name) === 1;
    }

    /**
     * Modify column position in languages table.
     * Both $column and $after are validated as safe identifiers; otherwise
     * the call is a no-op (returns false) rather than emitting SQL.
     */
    public function modify_column_position($column, $after)
    {
        if (!$this->_safe_ident($column) || !$this->_safe_ident($after)) {
            log_message('error', "Language_model::modify_column_position rejected unsafe identifier: column='$column', after='$after'");
            return false;
        }
        return $this->db->query("ALTER TABLE `languages` MODIFY COLUMN `{$column}` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT '' AFTER `{$after}`");
    }

    /**
     * Modify datetime column position in languages table.
     */
    public function modify_datetime_column_position($column, $after)
    {
        if (!$this->_safe_ident($column) || !$this->_safe_ident($after)) {
            log_message('error', "Language_model::modify_datetime_column_position rejected unsafe identifier: column='$column', after='$after'");
            return false;
        }
        return $this->db->query("ALTER TABLE `languages` MODIFY COLUMN `{$column}` DATETIME NULL AFTER `{$after}`");
    }

    /**
     * Drop column from a table. System columns are protected.
     */
    public function drop_column($table, $column)
    {
        if (!$this->_safe_ident($table) || !$this->_safe_ident($column)) {
            log_message('error', "Language_model::drop_column rejected unsafe identifier: table='$table', column='$column'");
            return false;
        }
        $protected = ['id', 'word_key', 'created_at', 'updated_at'];
        if (in_array($column, $protected, true)) {
            return false;
        }
        return $this->db->query("ALTER TABLE `{$table}` DROP COLUMN `{$column}`");
    }

    /**
     * Insert language list entry
     */
    public function insert_language($data)
    {
        $this->db->insert('language_list', $data);
        return $this->db->insert_id();
    }

    /**
     * Update language list entry
     */
    public function update_language($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('language_list', $data);
    }

    /**
     * Get language by ID
     */
    public function get_language_by_id($id)
    {
        $this->db->where('id', $id);
        return $this->db->get('language_list')->row();
    }

    /**
     * Get language name by ID
     */
    public function get_language_name_by_id($id)
    {
        $this->db->select('name');
        $this->db->where('id', $id);
        return $this->db->get('language_list')->row_array();
    }

    /**
     * Get language by code or ID
     */
    public function get_language_by_code_or_id($code_or_id)
    {
        $this->db->where('code', $code_or_id);
        $this->db->or_where('id', $code_or_id);
        return $this->db->get('language_list');
    }

    /**
     * Update phrases in batch
     */
    public function update_batch_languages($data, $index)
    {
        return $this->db->update_batch('languages', $data, $index);
    }

    /**
     * Delete multiple phrases
     */
    public function delete_phrases($phrase_ids)
    {
        $this->db->where_in('id', $phrase_ids);
        return $this->db->delete('languages');
    }

    /**
     * Delete a single phrase
     */
    public function delete_phrase($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete('languages');
    }

    /**
     * Get all phrases
     */
    public function get_all_phrases()
    {
        return $this->db->select('id')->get('languages')->result_array();
    }

    /**
     * Get all phrases ordered
     */
    public function get_all_phrases_ordered()
    {
        return $this->db->order_by('word_key', 'ASC')->get('languages')->result_array();
    }

    /**
     * Get phrase by word_key
     */
    public function get_phrase_by_word($word)
    {
        return $this->db->get_where('languages', array('word_key' => $word));
    }

    /**
     * Insert new phrase
     */
    public function insert_phrase($data)
    {
        return $this->db->insert('languages', $data);
    }

    /**
     * Update existing phrase
     */
    public function update_phrase($word, $data)
    {
        $this->db->where('word_key', $word);
        return $this->db->update('languages', $data);
    }

    /**
     * Ensure column exists in a table (self-healing)
     */
    public function ensure_column_exists($table, $column, $definition)
    {
        if (!$this->db->field_exists($column, $table)) {
            $this->load->dbforge();
            return $this->dbforge->add_column($table, array($column => $definition));
        }
        return true;
    }

    /**
     * Delete language list record by ID
     */
    public function delete_language_list_record($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete('language_list');
    }
}
