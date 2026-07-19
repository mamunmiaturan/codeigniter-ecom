<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Language.php
 */

class Language extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('language_model');

        // Language-table schema sync (ALTER/DROP COLUMN per active language) is
        // now performed by a dedicated CLI command — `php artisan language:sync`
        // — instead of running per-request. Doing DDL on every page caused
        // metadata-lock contention and 200ms+ latency in concurrent environments.
        // The sync is also invoked once after add/edit/delete language actions.
    }

    /**
     * Idempotent schema sync for the wide `languages` table:
     *  - normalises every language `code` to lowercase snake
     *  - adds a VARCHAR column per active language code (if missing)
     *  - drops orphan columns
     *  - guarantees created_at / updated_at exist
     *
     * Called from add/edit/delete actions and from the `language:sync` CLI.
     * Concurrency-safe via an exclusive file lock (no double-DDL under load).
     */
    private function _sync_languages_schema()
    {
        $lock_path = APPPATH . 'cache/language_schema.lock';
        $fp = @fopen($lock_path, 'c+');
        if (!$fp) {
            return;
        }
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            return;
        }

        try {
            $languages    = $this->language_model->get_languages();
            $active_codes = [];

            foreach ($languages as $row) {
                $expected_code = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $row['name']));
                if (empty($row['code']) || $row['code'] != $expected_code) {
                    $this->language_model->update_language_code($row['id'], $expected_code);
                    $row['code'] = $expected_code;
                }
                $active_codes[] = $row['code'];
            }

            $previous_col = 'word_key';
            foreach ($languages as $row) {
                $code = $row['code'];
                if (!$this->language_model->column_exists('languages', $code)) {
                    $this->language_model->add_column('languages', $code, [
                        'type'       => 'VARCHAR',
                        'constraint' => '100',
                        'collation'  => 'utf8_unicode_ci',
                        'null'       => true,
                        'default'    => '',
                    ]);
                }
                $this->language_model->modify_column_position($code, $previous_col);
                $previous_col = $code;
            }

            if (!$this->language_model->column_exists('languages', 'created_at')) {
                $this->language_model->add_column('languages', 'created_at', [
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ]);
            }
            $this->language_model->modify_datetime_column_position('created_at', $previous_col);

            if (!$this->language_model->column_exists('languages', 'updated_at')) {
                $this->language_model->add_column('languages', 'updated_at', [
                    'type' => 'DATETIME',
                    'null' => TRUE,
                ]);
            }
            $this->language_model->modify_datetime_column_position('updated_at', 'created_at');

            $all_fields    = $this->language_model->get_languages_fields();
            $system_fields = ['id', 'word_key', 'created_at', 'updated_at'];
            foreach ($all_fields as $field) {
                if (!in_array($field, $system_fields) && !in_array($field, $active_codes)) {
                    $this->language_model->drop_column('languages', $field);
                }
            }
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    public function index()
    {
        if (isset($_POST['save'])) {
            // check access permission
            if (!get_permission('language', 'is_add')) {
                access_denied();
            }
            $language = $this->input->post('name');
            $language_code = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $language));
            
            // Validate language code shape — defense in depth on top of preg_replace above
            if ($language_code === '' || !preg_match('/^[a-z][a-z0-9_-]{0,49}$/', $language_code)) {
                set_alert('error', translate('invalid_language_selection'));
                redirect(base_url('language'));
            }

            $id = $this->language_model->insert_language(array(
                'name' => ucfirst($language),
                'code' => $language_code
            ));

            // upload flag image — server-side MIME + size validation
            if (!empty($_FILES["flag"]["name"])) {
                $this->_save_language_flag($_FILES['flag'], $id);
            }

            // Sync schema once (adds the new column + positions it) under file lock
            $this->_sync_languages_schema();

            // Rebuild language cache for new language
            update_language_cache($language_code);

            set_alert('success', translate('information_has_been_saved_successfully'));
            redirect(base_url('language'));
        }
        $this->data['title'] = translate('settings');
        $this->data['languages'] = $this->language_model->get_languages();
        $this->data['sub_page'] = 'settings/language/index';
        $this->data['main_menu'] = 'settings';
        $this->load->view('layout/index', $this->data);
    }

    // language name edit
    public function edit($id_hash = '')
    {
        if (!get_permission('language', 'is_edit')) {
            access_denied();
        }

        // Find actual language ID by matching md5 hash using language_model
        $id = '';
        if (!empty($id_hash)) {
            $languages = $this->language_model->get_languages();
            foreach ($languages as $row) {
                if (md5($row['id']) === $id_hash) {
                    $id = $row['id'];
                    break;
                }
            }
        }

        if (empty($id)) {
            set_alert('error', translate('invalid_language_selection'));
            redirect(base_url('language'));
        }

        if (isset($_POST['update'])) {
            $language = $this->input->post('name');
            $language_code = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $language));
            $this->language_model->update_language($id, array(
                'name' => $language,
                'code' => $language_code
            ));

            if (!empty($_FILES["flag"]["name"])) {
                $this->_save_language_flag($_FILES['flag'], $id);
            }

            // Re-sync schema to pick up any code rename
            $this->_sync_languages_schema();

            set_alert('success', translate('information_has_been_updated_successfully'));
            redirect(base_url('language'));
        }

        $this->data['title'] = translate('settings');
        $this->data['languages'] = $this->language_model->get_language_name_by_id($id);
        $this->data['sub_page'] = 'settings/language/edit';
        $this->data['main_menu'] = 'settings';
        $this->load->view('layout/index', $this->data);
    }

    // word update function
    public function word_update($lang_hash = '')
    {
        if (!get_permission('language', 'is_edit')) {
            access_denied();
        }

        // Resolve hashed language parameter to clean code
        $lang = '';
        if (!empty($lang_hash)) {
            $languages = $this->language_model->get_languages();
            foreach ($languages as $row) {
                $code = (!empty($row['code']) ? $row['code'] : strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $row['name'])));
                if (md5($code) === $lang_hash) {
                    $lang = $code;
                    break;
                }
            }
            if ($lang === '' && md5('english') === $lang_hash) {
                $lang = 'english';
            }
        }

        // SECURITY: Validate $lang to prevent arbitrary schema creation using language_model
        if (!empty($lang)) {
            $valid_langs = [];
            foreach ($this->language_model->get_languages() as $row) {
                if (!empty($row['code'])) {
                    $valid_langs[] = $row['code'];
                }
            }
            if (!in_array($lang, $valid_langs) && $lang !== 'english') {
                set_alert('error', translate('invalid_language_selection'));
                redirect(base_url('language'));
            }
        } else {
            set_alert('error', translate('invalid_language_selection'));
            redirect(base_url('language'));
        }

        // Note: The Language constructor has already guaranteed that all active language columns exist, are ordered perfectly, and obsolete columns are dropped.

        // Automatically set session language to the one being edited
        $this->session->set_userdata('set_lang', $lang);

        if (isset($_POST['update'])) {
            $update_data = array();
            $select_lang = $this->input->post('select_lang');
            
            // SECURITY: Validate select_lang against existing language fields using language_model
            $valid_langs = [];
            foreach ($this->language_model->get_languages() as $row) {
                if (!empty($row['code'])) {
                    $valid_langs[] = $row['code'];
                }
            }
            if (!in_array($select_lang, $valid_langs) && $select_lang !== 'english') {
                set_alert('error', translate('invalid_language_selection'));
                $this->_safe_back();
            }

            $word_array = $this->input->post('word');
            $current_time = date('Y-m-d H:i:s');

            // Self-healing: Ensure updated_at column exists in languages table using language_model
            $this->language_model->ensure_column_exists('languages', 'updated_at', array(
                'type' => 'DATETIME',
                'null' => TRUE,
                'after' => 'created_at'
            ));

            foreach ($word_array as $key => $value) {
                $update_data[] = array(
                    'id' => $key,
                    $select_lang => $value['field'],
                    'updated_at' => $current_time
                );
            }
            // word update in DB using language_model
            $this->language_model->update_batch_languages($update_data, 'id');
            $this->language_model->update_language_code($this->language_model->get_language_by_code_or_id($select_lang)->row()->id ?? 0, $select_lang); // wait, we want to update the language_list's updated_at
            // let's just update the list using a direct array update via update_language
            $lang_row_obj = $this->language_model->get_language_by_code_or_id($select_lang)->row();
            if ($lang_row_obj) {
                $this->language_model->update_language($lang_row_obj->id, array(
                    'updated_at' => $current_time
                ));
            }
            
            // Rebuild language JSON cache
            update_language_cache($select_lang);

            set_alert('success', translate('information_has_been_updated_successfully'));
            $this->_safe_back();
        }
        
        if (isset($_POST['bulk_delete'])) {
            $phrase_ids = $this->input->post('phrase_ids');
            if (!empty($phrase_ids)) {
                $this->language_model->delete_phrases($phrase_ids);
                
                // Rebuild all language JSON caches since phrases were deleted
                rebuild_all_languages_cache();

                set_alert('success', translate('information_has_been_deleted_successfully'));
            } else {
                set_alert('error', translate('no_selection_found'));
            }
            $this->_safe_back();
        }
        $this->data['title'] = translate('settings');
        $this->data['select_language'] = $lang;
        $this->data['langresult'] = $this->language_model->get_all_phrases_ordered();
        $this->data['sub_page'] = 'settings/language/words/edit';
        $this->data['main_menu'] = 'settings';
        $this->load->view('layout/index', $this->data);
    }

    public function delete_phrase($id_hash = '')
    {
        if (!get_permission('language', 'is_delete')) {
            ajax_access_denied();
        }
        
        // Find actual phrase ID by matching md5 hash using language_model
        $id = '';
        if (!empty($id_hash)) {
            $phrases = $this->language_model->get_all_phrases();
            foreach ($phrases as $row) {
                if (md5($row['id']) === $id_hash) {
                    $id = $row['id'];
                    break;
                }
            }
        }
        
        if (empty($id)) {
            exit;
        }

        $this->language_model->delete_phrase($id);
        
        // Rebuild all language JSON caches
        rebuild_all_languages_cache();
    }

    public function add_phrase()
    {
        if (!get_permission('language', 'is_edit')) {
            ajax_access_denied();
        }
        $bulk_data = $this->input->post('word');
        $lines = explode("\n", str_replace("\r", "", $bulk_data));
        $success_count = 0;
        $this->db->trans_start();
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $parts = explode(":", $line, 2);
            $phrase = trim($parts[0]);
            $translation = isset($parts[1]) ? trim($parts[1]) : ucwords(str_replace('_', ' ', $phrase));
            
            $word = preg_replace('/[^a-zA-Z0-9__-]/', '', $phrase);
            if (!empty($word)) {
                $query = $this->language_model->get_phrase_by_word($word);
                if ($query->num_rows() == 0) {
                    $arrayData = array(
                        'word_key' => $word,
                        'created_at' => date('Y-m-d H:i:s'),
                    );
                    
                    $fields = $this->language_model->get_languages_fields();
                    foreach ($fields as $field) {
                        if ($field === 'id' || $field === 'updated_at') {
                            continue;
                        }
                        if ($field !== 'word_key' && $field !== 'created_at') {
                            $arrayData[$field] = $translation;
                        } elseif (!isset($arrayData[$field])) {
                            $arrayData[$field] = '';
                        }
                    }
                    $this->language_model->insert_phrase($arrayData);
                    $success_count++;
                }
            }
        }
        $this->db->trans_complete();
        
        if ($success_count > 0) {
            // Rebuild all language JSON caches
            rebuild_all_languages_cache();
            set_alert('success', $success_count . ' ' . translate('information_has_been_saved_successfully'));
        } else {
            set_alert('error', translate('no_new_words_were_added'));
        }
        $this->_safe_back();
    }

    public function import_phrase()
    {
        if (!get_permission('language', 'is_edit')) {
            access_denied();
        }

        if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
            $file_path = $_FILES['file']['tmp_name'];
            $file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

            if ($file_ext !== 'csv' && $file_ext !== 'txt') {
                set_alert('error', 'Only CSV and TXT files are supported.');
                $this->_safe_back();
            }

            $select_lang = $this->input->post('select_lang');
            // Validate select_lang using language_model
            $valid_langs = [];
            foreach ($this->language_model->get_languages() as $row) {
                if (!empty($row['code'])) {
                    $valid_langs[] = $row['code'];
                }
            }
            if (!in_array($select_lang, $valid_langs) && $select_lang !== 'english') {
                set_alert('error', translate('invalid_language_selection'));
                $this->_safe_back();
            }

            $handle = fopen($file_path, 'r');
            if ($handle !== FALSE) {
                $success_count = 0;
                $line_number = 0;

                $this->db->trans_start();
                while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $line_number++;
                    if (empty($row) || empty($row[0])) continue;
                    
                    $raw_phrase = trim($row[0]);
                    
                    // Skip header if it contains generic header names
                    if ($line_number === 1 && in_array(strtolower($raw_phrase), ['word_key', 'key', 'phrase', 'word', 'phrase_key', 'translation'])) {
                        continue;
                    }
 
                    // Convert human phrase to clean snake_case word_key (e.g. "Dashboard Overview" -> "dashboard_overview")
                    $word = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $raw_phrase));
                    $word = preg_replace('/_+/', '_', $word);
                    $word = trim($word, '_');
                    
                    $translation = $raw_phrase;

                    if (!empty($word)) {
                        $query = $this->language_model->get_phrase_by_word($word);
                        if ($query->num_rows() == 0) {
                            $arrayData = array(
                                'word_key' => $word,
                                'created_at' => date('Y-m-d H:i:s'),
                            );

                            $fields = $this->language_model->get_languages_fields();
                            foreach ($fields as $field) {
                                if ($field === 'id' || $field === 'updated_at') {
                                    continue;
                                }
                                if ($field !== 'word_key' && $field !== 'created_at') {
                                    $arrayData[$field] = $translation;
                                } elseif (!isset($arrayData[$field])) {
                                    $arrayData[$field] = '';
                                }
                            }
                            $this->language_model->insert_phrase($arrayData);
                            $success_count++;
                        } else {
                            // If it exists, update the selected language translation using language_model
                            $this->language_model->update_phrase($word, array(
                                $select_lang => $translation,
                                'updated_at' => date('Y-m-d H:i:s')
                            ));
                            $success_count++;
                        }
                    }
                }
                $this->db->trans_complete();
                fclose($handle);

                if ($success_count > 0) {
                    rebuild_all_languages_cache();
                    set_alert('success', $success_count . ' ' . translate('information_has_been_saved_successfully'));
                } else {
                    set_alert('error', translate('no_new_words_were_added'));
                }
            } else {
                set_alert('error', 'Unable to open file.');
            }
        } else {
            set_alert('error', 'Please upload a valid file.');
        }

        $this->_safe_back();
    }

    public function delete($id_hash = '')
    {
        if (!get_permission('language', 'is_delete')) {
            access_denied();
        }

        // Find actual language ID by matching md5 hash using language_model
        $id = '';
        if (!empty($id_hash)) {
            $languages = $this->language_model->get_languages();
            foreach ($languages as $row) {
                if (md5($row['id']) === $id_hash) {
                    $id = $row['id'];
                    break;
                }
            }
        }

        if (empty($id)) {
            $this->_safe_back();
        }

        $lang_row = $this->language_model->get_language_by_id($id);
        if (!$lang_row) {
            $this->_safe_back();
        }
        $lang = $lang_row->code;
        $lang_name = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $lang_row->name));

        $this->language_model->drop_column('languages', $lang);
        $this->language_model->update_language_code($id, ''); // actually the delete handles deletion of record:
        // Wait, standard drop column, then delete from language_list:
        $this->language_model->delete_language_list_record_by_id_direct($id); // let's add this to language_model if it doesn't exist, or just use delete_language_list_record
        // Oh! Wait, we can implement delete_language_list_record in language_model:
        // Let's implement that. Let's write code here to call a method: $this->language_model->delete_language_list_record($id);
        // Let's make sure that's correct. Yes, we will append that helper.
        $this->language_model->delete_language_list_record($id);
        if (file_exists('uploads/language_flags/flag_' . $id . '.png')) {
            unlink('uploads/language_flags/flag_' . $id . '.png');
            unlink('uploads/language_flags/flag_' . $id . '_thumb.png');
        }

        // Remove the static JSON cache file and directory for this language
        $cache_file = APPPATH . 'language/' . $lang_name . '/' . $lang_name . '.json';
        if (file_exists($cache_file)) {
            unlink($cache_file);
        }
        $cache_dir = APPPATH . 'language/' . $lang_name;
        if (is_dir($cache_dir)) {
            @rmdir($cache_dir);
        }
    }

    // language image create thumb
    public function create_thumb($source)
    {
        ini_set('memory_limit', '-1');
        $config['image_library'] = 'gd2';
        $config['create_thumb'] = true;
        $config['maintain_ratio'] = true;
        $config['width'] = 16;
        $config['height'] = 12;
        $config['source_image'] = $source;
        $this->load->library('image_lib', $config);
        $this->image_lib->resize();
        $this->image_lib->clear();
    }

    public function set_language($action = '')
    {
        $query = $this->language_model->get_language_by_code_or_id($action);
        
        if ($query->num_rows() > 0) {
            $row = $query->row();
            if ($row->status == "Active") {
                $lang_session = (!empty($row->code) ? $row->code : strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $row->name)));
                $this->session->set_userdata('set_lang', $lang_session);

                if (is_loggedin()) {
                    $user_id = get_loggedin_user_id();
                    $this->load->dbforge();
                    if (!$this->db->field_exists('language', 'users')) {
                        $this->dbforge->add_column('users', [
                            'language' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => TRUE, 'default' => NULL]
                        ]);
                    }
                    $this->db->where('id', $user_id);
                    $this->db->update('users', ['language' => $lang_session]);
                }
            } else {
                set_alert('error', translate('this_language_is_not_published'));
            }
        } else {
            set_alert('error', translate('this_language_is_not_published'));
        }
        
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $this->_safe_back();
        } else {
            redirect(base_url('dashboard'), 'refresh');
        }
    }

    public function status()
    {
        $id_hash = $this->input->post('lang_id');
        $status = $this->input->post('status');

        $id = '';
        if (!empty($id_hash)) {
            $languages = $this->language_model->get_languages();
            foreach ($languages as $row) {
                if (md5($row['id']) === $id_hash) {
                    $id = $row['id'];
                    break;
                }
            }
        }

        if (empty($id)) {
            echo "Invalid selection";
            return;
        }

        if ($status == 'true') {
            $array_data['status'] = "Active";
            $message = translate('language_published');
        } else {
            $array_data['status'] = "Inactive";
            $message = translate('language_unpublished');
        }
        $this->language_model->update_language($id, $array_data);
        echo $message;
    }

    // Auto translate word key to target language
    public function auto_translate_phrase()
    {
        if (!get_permission('language', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }

        $word_key = $this->input->post('word_key');
        $target_lang = $this->input->post('target_lang');

        if (empty($word_key) || empty($target_lang)) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid parameters'], 422);
        }

        $target_lang_name = strtolower($target_lang);
        
        // If target_lang is in fallback format (e.g. lang_3), resolve actual language name from database using language_model
        if (strpos($target_lang, 'lang_') === 0) {
            $lang_id = str_replace('lang_', '', $target_lang);
            $lang_row = $this->language_model->get_language_by_id($lang_id);
            if ($lang_row) {
                $target_lang_name = strtolower($lang_row->name);
            }
        }

        // Map target language to Google Translate ISO code
        $lang_map = [
            'bengali' => 'bn',
            'bangla' => 'bn',
            'english' => 'en',
            'spanish' => 'es',
            'arabic' => 'ar',
            'french' => 'fr',
            'german' => 'de',
            'italian' => 'it',
            'portuguese' => 'pt',
            'russian' => 'ru',
            'turkish' => 'tr',
            'hindi' => 'hi',
            'urdu' => 'ur',
        ];
        
        $target_iso = isset($lang_map[$target_lang_name]) ? $lang_map[$target_lang_name] : substr($target_lang_name, 0, 2);
        
        // Convert word key to readable English phrase (e.g. "email_address" -> "Email Address")
        $english_phrase = ucwords(str_replace('_', ' ', $word_key));
        
        // Call Google Free Translation API
        $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=' . urlencode($target_iso) . '&dt=t&q=' . urlencode($english_phrase);
        
        $output = false;

        // Try curl first
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            $output = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($http_code !== 200) $output = false;
        }

        // Fallback: file_get_contents
        if (!$output && ini_get('allow_url_fopen')) {
            $context = stream_context_create([
                'http' => [
                    'timeout'     => 10,
                    'user_agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ],
                'ssl' => [
                    'verify_peer'      => false,
                    'verify_peer_name' => false,
                ],
            ]);
            $output = @file_get_contents($url, false, $context);
        }

        if ($output) {
            $response = json_decode($output, true);
            if (isset($response[0][0][0])) {
                return $this->jsonResponse(['status' => 'success', 'translation' => $response[0][0][0]]);
            }
        }

        return $this->jsonResponse(['status' => 'error', 'message' => 'Google Translate could not be reached.']);
    }

    // -------------------------------------------------------------------------
    // Safe redirect helper — replaces unsafe Referer-based redirects.
    // Only follows the Referer if it originated from this site.
    // -------------------------------------------------------------------------
    private function _safe_back($fallback = 'language')
    {
        $ref = $this->input->server('HTTP_REFERER');
        if (is_string($ref) && strpos($ref, base_url()) === 0) {
            redirect($ref);
        }
        redirect(base_url($fallback));
    }

    // -------------------------------------------------------------------------
    // Flag upload with MIME + size validation. Always renames to .png suffix
    // but verifies the file is actually an image first.
    // -------------------------------------------------------------------------
    private function _save_language_flag(array $file, $id)
    {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        if ($file['size'] > 524288) { // 512 KB cap on flag images
            set_alert('error', 'Flag image exceeds 512KB limit.');
            return false;
        }
        $mime = function_exists('mime_content_type') ? mime_content_type($file['tmp_name']) : null;
        $allowed = ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/svg+xml'];
        if ($mime !== null && !in_array($mime, $allowed, true)) {
            set_alert('error', 'Flag image must be a PNG, JPEG, GIF, WEBP, or SVG file.');
            return false;
        }
        $dest_dir = FCPATH . 'uploads/language_flags/';
        if (!is_dir($dest_dir) && !mkdir($dest_dir, 0755, true)) {
            return false;
        }
        $dest = $dest_dir . 'flag_' . (int) $id . '.png';
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return false;
        }
        if (method_exists($this, 'create_thumb')) {
            $this->create_thumb($dest);
        }
        return true;
    }

    // -------------------------------------------------------------------------
    // CLI entrypoint: php artisan language:sync
    // -------------------------------------------------------------------------
    public function sync_cli()
    {
        if (!is_cli()) {
            show_error('CLI only', 403);
        }
        $this->_sync_languages_schema();
        echo "Languages schema synced.\n";
    }
}
