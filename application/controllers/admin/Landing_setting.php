<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Landing Setting — dynamic storefront branding (accent/button colour, font,
 * hero title & subtitle). Stored on the global_settings row and applied by the
 * storefront head partial.
 */
class Landing_setting extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('settings_model');
    }

    public function index()
    {
        if (!get_permission('global_setting', 'is_view')) {
            access_denied();
        }

        if ($this->input->post('landing_setting')) {
            if (!get_permission('global_setting', 'is_add')) {
                access_denied();
            }
            $config  = [];
            $allowed = ['landing_accent_color', 'landing_font', 'landing_hero_title', 'landing_hero_subtitle'];
            foreach ($allowed as $field) {
                $value = $this->input->post($field);
                if ($value === null) {
                    continue;
                }
                if ($field === 'landing_accent_color' && !preg_match('/^#[0-9a-fA-F]{3,6}$/', (string) $value)) {
                    $value = '#0d9488';
                }
                $config[$field] = $value;
            }
            $this->settings_model->update_global_settings($config);
            if (function_exists('update_global_settings_cache')) {
                update_global_settings_cache();
            }
            $this->log_activity('update', 'global_settings', get_loggedin_user_id(), 'Updated landing settings');
            set_alert('success', translate('the_configuration_has_been_updated') ?: 'Landing settings updated.');
            redirect(base_url('landing-setting'));
        }

        $this->data['title']     = translate('landing_setting') ?: 'Landing Setting';
        $this->data['sub_page']  = 'settings/landing/index';
        $this->data['main_menu'] = 'landing_setting';
        $this->load->view('layout/index', $this->data);
    }
}
