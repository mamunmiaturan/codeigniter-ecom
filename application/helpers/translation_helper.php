<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Translation Helper
 *
 * @package    Authentication
 * @author     Mamun Mia Turan
 * @filename   translation_helper.php
 *
 * Functions:
 *   - translate()
 *   - update_language_cache()
 *   - rebuild_all_languages_cache()
 */

// Return translation from static JSON cache or database fallback
function translate($word = '')
{
    static $translations = [];
    $ci = &get_instance();
    $set_lang = $ci->session->userdata('set_lang');
    if (empty($set_lang)) {
        $set_lang = get_global_setting('translation') ?: 'english';
    }

    // Resolve legacy ID-based session values to clean name codes
    if (strpos($set_lang, 'lang_') === 0) {
        $lang_id = str_replace('lang_', '', $set_lang);
        $lang_row = $ci->db->select('code')->where('id', $lang_id)->get('language_list')->row();
        if ($lang_row && !empty($lang_row->code)) {
            $set_lang = $lang_row->code;
            $ci->session->set_userdata('set_lang', $set_lang);
        }
    }

    // Sanitize word key to prevent injection or invalid chars
    $word = preg_replace('/[^a-zA-Z0-9__-]/', '', $word);
    if (empty($word)) return '';

    // Load entire language into static cache on first call
    if (!isset($translations[$set_lang])) {
        $lang_folder = strtolower($set_lang);

        // Resolve actual language name from database using code
        $lang_row = $ci->db->select('name')->where('code', $set_lang)->get('language_list')->row();
        if ($lang_row) {
            $lang_folder = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $lang_row->name));
        } else {
            // Fallback for legacy ID-based checks
            if (strpos($set_lang, 'lang_') === 0) {
                $lang_id = str_replace('lang_', '', $set_lang);
                $lang_row = $ci->db->select('name')->where('id', $lang_id)->get('language_list')->row();
                if ($lang_row) {
                    $lang_folder = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $lang_row->name));
                }
            }
        }

        $cache_file = APPPATH . 'language/' . $lang_folder . '/' . $lang_folder . '.json';
        if (file_exists($cache_file)) {
            $translations[$set_lang] = json_decode(file_get_contents($cache_file), true);
        } else {
            $translations[$set_lang] = update_language_cache($set_lang);
        }
    }

    if (isset($translations[$set_lang][$word])) {
        return $translations[$set_lang][$word];
    }

    // Fallback: If word key doesn't exist in cache, check database
    $query = $ci->db->get_where('languages', array('word_key' => $word));
    if ($query->num_rows() > 0) {
        $row = $query->row();
        $translated = (isset($row->$set_lang) && $row->$set_lang != '') ? $row->$set_lang : ucwords(str_replace('_', ' ', $row->word_key));
        $translations[$set_lang][$word] = $translated;
        update_language_cache($set_lang);
        return $translated;
    } else {
        $arrayData = array(
            'word_key'   => $word,
            'created_at' => date('Y-m-d H:i:s'),
        );
        // Fill other language columns with empty strings
        $fields = $ci->db->list_fields('languages');
        foreach ($fields as $field) {
            if ($field === 'id' || $field === 'updated_at') {
                continue;
            }
            if (!isset($arrayData[$field])) {
                $arrayData[$field] = '';
            }
        }
        $ci->db->insert('languages', $arrayData);
        $translated = ucwords(str_replace('_', ' ', $word));
        $translations[$set_lang][$word] = $translated;

        // Rebuild all language caches because a new key has been added
        rebuild_all_languages_cache();

        return $translated;
    }
}

// Write language database records into a static JSON cache file under application/language/{lang}/{lang}.json
function update_language_cache($lang)
{
    $ci = &get_instance();

    // Resolve legacy ID-based values to clean name codes
    if (strpos($lang, 'lang_') === 0) {
        $lang_id = str_replace('lang_', '', $lang);
        $lang_row = $ci->db->select('code')->where('id', $lang_id)->get('language_list')->row();
        if ($lang_row && !empty($lang_row->code)) {
            $lang = $lang_row->code;
        }
    }

    $lang = preg_replace('/[^a-zA-Z0-9_-]/', '', $lang);
    if (empty($lang)) return [];

    // Self-healing: Ensure updated_at column exists in languages table
    if (!$ci->db->field_exists('updated_at', 'languages')) {
        $ci->load->dbforge();
        $ci->dbforge->add_column('languages', array(
            'updated_at' => array(
                'type'  => 'DATETIME',
                'null'  => TRUE,
                'after' => 'created_at'
            )
        ));
    }

    // Self-healing: If column is missing in 'languages' table, dynamically create it
    if (!$ci->db->field_exists($lang, 'languages')) {
        $ci->load->dbforge();
        $fields = array(
            $lang => array(
                'type'      => 'VARCHAR',
                'constraint'=> '100',
                'collation' => 'utf8_unicode_ci',
                'null'      => true,
                'default'   => '',
                'after'     => 'word_key'
            ),
        );
        $ci->dbforge->add_column('languages', $fields);
    }

    $ci->db->select("word_key, " . $ci->db->escape_identifiers($lang));
    $query = $ci->db->get('languages');
    $translations = [];
    if ($query->num_rows() > 0) {
        foreach ($query->result() as $row) {
            $translated = (isset($row->$lang) && $row->$lang != '') ? $row->$lang : ucwords(str_replace('_', ' ', $row->word_key));
            $translations[$row->word_key] = $translated;
        }
    }

    $lang_folder = strtolower($lang);

    // Resolve actual language name from database using code
    $lang_row = $ci->db->select('name')->where('code', $lang)->get('language_list')->row();
    if ($lang_row) {
        $lang_folder = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $lang_row->name));
    } else {
        if (strpos($lang, 'lang_') === 0) {
            $lang_id = str_replace('lang_', '', $lang);
            $lang_row = $ci->db->select('name')->where('id', $lang_id)->get('language_list')->row();
            if ($lang_row) {
                $lang_folder = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $lang_row->name));
            }
        }
    }

    $dir = APPPATH . 'language/' . $lang_folder . '/';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        // Language cache directory is not writable (e.g. read-only / restricted
        // deploy). Translations still resolve from the DB, so skip persisting the
        // JSON cache instead of emitting mkdir() permission warnings on every
        // translate() call. Caching is best-effort, mirroring atomic_file_put_contents().
        return $translations;
    }

    atomic_file_put_contents($dir . $lang_folder . '.json', json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    return $translations;
}

// Rebuild JSON cache for all registered languages inside application/language/{lang}/{lang}.json
function rebuild_all_languages_cache()
{
    $ci = &get_instance();
    $langs = $ci->db->get('language_list')->result();
    foreach ($langs as $lang) {
        $lang_field = empty($lang->lang_field) ? 'lang_' . $lang->id : $lang->lang_field;
        update_language_cache($lang_field);
    }
    update_language_cache('english');
}
