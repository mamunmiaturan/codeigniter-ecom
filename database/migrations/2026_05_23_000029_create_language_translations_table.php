<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Create_language_translations_table extends CI_Migration
{
    public function up()
    {
        // Create the EAV table
        $this->dbforge->add_field([
            'id' => ['type' => 'INT', 'unsigned' => TRUE, 'auto_increment' => TRUE],
            'language_id' => ['type' => 'INT', 'null' => FALSE],
            'word_key' => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => FALSE],
            'translation' => ['type' => 'LONGTEXT', 'null' => TRUE],
            'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => ['type' => 'DATETIME', 'null' => TRUE],
        ]);
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('language_translations', TRUE);

        // Add unique constraint and indexes
        $this->db->query('ALTER TABLE language_translations ADD UNIQUE KEY uk_lang_word (language_id, word_key)');
        $this->db->query('ALTER TABLE language_translations ADD INDEX idx_language_id (language_id)');
        $this->db->query('ALTER TABLE language_translations ADD CONSTRAINT fk_lt_language FOREIGN KEY (language_id) REFERENCES language_list(id) ON DELETE CASCADE');
        $this->db->query('ALTER TABLE language_translations MODIFY updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');

        // Migrate data from dynamic columns to EAV
        $languages = $this->db->get('language_list')->result_array();
        $fields = $this->db->list_fields('languages');

        foreach ($languages as $lang) {
            $code = strtolower($lang['code']); // e.g. 'english', 'bengali'
            // Try exact code match, then try name match
            $col = null;
            if (in_array($code, $fields)) {
                $col = $code;
            } else {
                $name = strtolower($lang['name']);
                if (in_array($name, $fields)) {
                    $col = $name;
                }
            }
            if (!$col) continue;

            // Fetch all phrases for this language column
            $phrases = $this->db->select("word_key, `$col` as translation")->get('languages')->result_array();
            $insert_batch = [];
            foreach ($phrases as $phrase) {
                if ($phrase['translation'] !== null && $phrase['translation'] !== '') {
                    $insert_batch[] = [
                        'language_id' => $lang['id'],
                        'word_key' => $phrase['word_key'],
                        'translation' => $phrase['translation'],
                    ];
                }
            }
            if (!empty($insert_batch)) {
                // Insert in chunks to avoid memory issues
                foreach (array_chunk($insert_batch, 500) as $chunk) {
                    $this->db->insert_batch('language_translations', $chunk);
                }
            }
        }
    }

    public function down()
    {
        $this->db->query('ALTER TABLE language_translations DROP FOREIGN KEY fk_lt_language');
        $this->dbforge->drop_table('language_translations', TRUE);
    }
}
