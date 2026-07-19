<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Settings.php
 */

class Settings extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('settings_model');
    }

    // global settings controller
    public function index()
    {
        // check access permission
        if (!get_permission('global_setting', 'is_view')) {
            access_denied();
        }
        $config = array();
        // general setting update in database
        // app setting update in database
        if ($this->input->post('app_setting')) {
            if (!get_permission('global_setting', 'is_add')) {
                access_denied();
            }
            $allowed_fields = [
                'currency', 'currency_symbol', 'translation', 'timezone',
                'date_format', 'footer_text',
                'abac_hour_start', 'abac_hour_end',
                'recaptcha_site_key', 'recaptcha_secret_key',
            ];
            foreach ($allowed_fields as $field) {
                if ($this->input->post($field) !== null) {
                    $config[$field] = $this->input->post($field);
                }
            }
            $this->settings_model->update_global_settings($config);
            update_global_settings_cache();
            $this->session->set_flashdata('active', 4);
            set_alert('success', translate('the_configuration_has_been_updated'));
            redirect(base_url('settings'));
        }

        if ($this->input->post('ops_setting')) {
            if (!get_permission('global_setting', 'is_add')) {
                access_denied();
            }
            $toggle_fields = [
                'customer_self_registration',
                'booking_reminders_enabled',
                'weekly_report_email_enabled',
                'delivery_eta_notifications_enabled',
                'kitchen_stock_alerts_enabled',
                'wallet_auto_topup_enabled',
            ];
            foreach ($toggle_fields as $field) {
                $config[$field] = $this->input->post($field) ? 1 : 0;
            }
            $this->settings_model->update_global_settings($config);
            update_global_settings_cache();
            $this->session->set_flashdata('active', 7);
            set_alert('success', translate('the_configuration_has_been_updated'));
            redirect(base_url('settings'));
        }

        // general setting update in database
        if ($this->input->post('general_setting')) {
            if (!get_permission('global_setting', 'is_add')) {
                access_denied();
            }
            $allowed_fields = [
                'site_name', 'site_email', 'mobile_no', 'address'
            ];
            foreach ($allowed_fields as $field) {
                if ($this->input->post($field) !== null) {
                    $config[$field] = $this->input->post($field);
                }
            }
            $this->settings_model->update_global_settings($config);
            update_global_settings_cache();
            $this->session->set_flashdata('active', 1);
            set_alert('success', translate('the_configuration_has_been_updated'));
            redirect(base_url('settings'));
        }

        if ($this->input->post('social_setting')) {
            if (!get_permission('global_setting', 'isf_add')) {
                access_denied();
            }
            $allowed_fields = [
                'facebook_url', 'twitter_url', 'linkedin_url', 
                'youtube_url', 'instagram_url'
            ];
            foreach ($allowed_fields as $field) {
                if ($this->input->post($field) !== null) {
                    $config[$field] = $this->input->post($field);
                }
            }
            $this->settings_model->update_global_settings($config);
            update_global_settings_cache();
            $this->session->set_flashdata('active', 3);
            set_alert('success', translate('the_configuration_has_been_updated'));
            redirect(base_url('settings'));
        }
        // theme setting update (admin global settings page — saves logged-in user's theme)
        if ($this->input->post('theme')) {
            if (!get_permission('global_setting', 'is_add')) {
                access_denied();
            }
            $this->load->helper('theme');
            $user_id = get_loggedin_user_id();
            $result = save_user_theme_from_request($user_id);
            if (!$result['ok']) {
                set_alert('error', $result['error'] ?? translate('invalid_color_value'));
                redirect(base_url('settings'));
            }
            $this->log_activity('update', 'theme_settings', $user_id, 'Updated user theme settings');
            set_alert('success', translate('the_configuration_has_been_updated'));
            $this->session->set_flashdata('active', 6);
            redirect(base_url('settings'));
        }

        if ($this->input->post('reset_theme')) {
            if (!get_permission('global_setting', 'is_add')) {
                access_denied();
            }
            $this->load->helper('theme');
            $user_id = get_loggedin_user_id();
            reset_user_theme_to_default($user_id);
            set_alert('success', translate('the_configuration_has_been_updated'));
            $this->session->set_flashdata('active', 6);
            redirect(base_url('settings'));
        }
        // logo setting update in database
        if ($this->input->post('logo')) {
            if (!get_permission('global_setting', 'is_add')) {
                access_denied();
            }

            $allowed_logo_exts  = ['png', 'jpg', 'jpeg'];
            $allowed_logo_mimes = ['image/png', 'image/jpeg', 'image/pjpeg', 'image/x-png'];
            $allowed_fav_exts   = ['ico', 'png', 'gif'];
            $allowed_fav_mimes  = ['image/x-icon', 'image/png', 'image/gif', 'image/vnd.microsoft.icon'];
            $max_size           = 2097152; // 2 MB

            // --- Validate ALL files first before touching any disk or DB ---
            $uploads = [
                'logo_file'    => ['dest' => 'uploads/app_image/logo.png',          'exts' => $allowed_logo_exts, 'mimes' => $allowed_logo_mimes, 'label' => 'Logo'],
                'text_logo'    => ['dest' => 'uploads/app_image/logo-small.png',     'exts' => $allowed_logo_exts, 'mimes' => $allowed_logo_mimes, 'label' => 'Text Logo'],
                'print_file'   => ['dest' => 'uploads/app_image/printing-logo.png',  'exts' => $allowed_logo_exts, 'mimes' => $allowed_logo_mimes, 'label' => 'Print Logo'],
                'favicon_file' => ['dest' => null, /* dynamic */                     'exts' => $allowed_fav_exts,  'mimes' => $allowed_fav_mimes,  'label' => 'Favicon'],
            ];

            foreach ($uploads as $field => $meta) {
                if (empty($_FILES[$field]['name'])) continue;
                $file = $_FILES[$field];
                $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $mime = mime_content_type($file['tmp_name']);
                if (!in_array($ext, $meta['exts']) || !in_array($mime, $meta['mimes']) || $file['size'] > $max_size) {
                    set_alert('error', "Invalid {$meta['label']} file. Allowed: " . implode('/', $meta['exts']) . " up to 2 MB.");
                    redirect(base_url('settings'));
                }
            }

            // --- All files valid — ensure favicon column, then move + update DB in one transaction ---
            if (!empty($_FILES['favicon_file']['name'])) {
                $this->settings_model->ensure_column_exists('global_settings', 'favicon', [
                    'type' => 'VARCHAR', 'constraint' => '255', 'null' => TRUE,
                ]);
            }

            $db_updates = [];

            $this->db->trans_start();

            $file_map = [
                'logo_file'  => ['dest' => 'uploads/app_image/logo.png',         'db_key' => 'logo',  'db_val' => 'logo.png'],
                'text_logo'  => ['dest' => 'uploads/app_image/logo-small.png',   'db_key' => null,    'db_val' => null],
                'print_file' => ['dest' => 'uploads/app_image/printing-logo.png','db_key' => null,    'db_val' => null],
            ];
            foreach ($file_map as $field => $meta) {
                if (empty($_FILES[$field]['name'])) continue;
                if (!move_uploaded_file($_FILES[$field]['tmp_name'], $meta['dest'])) {
                    $this->db->trans_rollback();
                    set_alert('error', "Failed to save file. No changes were applied.");
                    redirect(base_url('settings'));
                }
                if ($meta['db_key']) {
                    $db_updates[$meta['db_key']] = $meta['db_val'];
                }
            }

            if (!empty($_FILES['favicon_file']['name'])) {
                $fav_ext  = strtolower(pathinfo($_FILES['favicon_file']['name'], PATHINFO_EXTENSION));
                $fav_dest = 'uploads/app_image/favicon.' . $fav_ext;
                if (!move_uploaded_file($_FILES['favicon_file']['tmp_name'], $fav_dest)) {
                    $this->db->trans_rollback();
                    set_alert('error', "Failed to save Favicon. No changes were applied.");
                    redirect(base_url('settings'));
                }
                $db_updates['favicon'] = 'favicon.' . $fav_ext;
            }

            if (!empty($db_updates)) {
                $this->settings_model->update_global_settings($db_updates);
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === false) {
                set_alert('error', "Database update failed. Uploaded files may need manual cleanup.");
                redirect(base_url('settings'));
            }

            $this->log_activity('update', 'global_settings', 1, 'Updated system logos/branding');
            update_global_settings_cache();
            set_alert('success', translate('the_configuration_has_been_updated'));
            $this->session->set_flashdata('active', 2);
            redirect(base_url('settings'));
        }

        $this->data['title'] = translate('settings');
        $this->data['sub_page'] = 'settings/global/index';
        $this->data['main_menu'] = 'settings';
        $this->load->view('layout/index', $this->data);
    }

    public function toggle_theme()
    {
        if (!is_loggedin()) {
            exit;
        }
        $this->load->helper('theme');
        $theme = get_theme_setting_row();
        $current_skin = $theme['dark_skin'] ?? 'false';
        $new_theme = ($current_skin == 'true' ? 'false' : 'true');
        $new_mode = ($new_theme == 'true' ? 1 : 0);
        save_user_theme(get_loggedin_user_id(), array(
            'dark_skin' => $new_theme,
            'dark_mode' => $new_mode
        ));
        echo $new_theme;
    }

    public function rebuild_system_cache()
    {
        if (!get_permission('global_setting', 'is_edit')) {
            echo json_encode(['status' => 'error', 'message' => translate('access_denied')]);
            exit;
        }

        $this->load->helper('general_helper');
        
        // Rebuild all language caches
        rebuild_all_languages_cache();
        
        // Rebuild global settings cache
        update_global_settings_cache();
        
        // Rebuild theme settings cache
        if (function_exists('update_theme_settings_cache')) {
            update_theme_settings_cache();
        }

        // Set active tab to 5 so that page reload remains on System Health
        $this->session->set_flashdata('active', 5);

        echo json_encode(['status' => 'success', 'message' => 'All system JSON caches have been successfully rebuilt and warmed up!']);
        exit;
    }

    public function run_integrity_scan()
    {
        if (!get_permission('global_setting', 'is_view')) {
            echo json_encode(['status' => 'error', 'message' => translate('access_denied')]);
            exit;
        }

        $issues = [];
        $files = [
            'Language (English)' => APPPATH . 'language/english/english.json',
            'Language (Bengali)' => APPPATH . 'language/bengali/bengali.json',
            'Global Settings' => APPPATH . 'logs/global/global_settings.json',
            'Theme Config' => APPPATH . 'logs/theme/theme_settings.json'
        ];

        foreach ($files as $name => $path) {
            if (!file_exists($path)) {
                $issues[] = "$name cache file is missing.";
            } else {
                $content = file_get_contents($path);
                json_decode($content);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $issues[] = "$name cache contains corrupted or invalid JSON.";
                }
            }
        }

        if (empty($issues)) {
            echo json_encode(['status' => 'success', 'message' => 'Integrity Scan complete! All static JSON caches are completely healthy and active.']);
        } else {
            echo json_encode(['status' => 'warning', 'message' => 'Integrity Scan complete. Identified issues: ' . implode(' ', $issues)]);
        }
        exit;
    }
}
