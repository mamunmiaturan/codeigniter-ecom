<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Sms.php
 */

class Sms extends Admin_Controller
{
    private $main_menu = 'sms';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('sms_model');
        $this->load->model('user_model');
        
        // Self-healing: Ensure 'notified' column exists in 'sms_templates' using sms_model
        $this->sms_model->ensure_column_exists('sms_templates', 'notified', [
            'type' => 'TINYINT',
            'constraint' => 1,
            'default' => 1,
            'null' => FALSE
        ]);

        // Self-healing: Clean up database from ssl_wireless and twilio
        $this->db->where_in('gateway_name', ['ssl_wireless', 'twilio'])->delete('sms_config');

        // Ensure custom_sms exists and is active by default
        $check = $this->sms_model->get_sms_config('custom_sms');
        if (!$check) {
            $this->sms_model->insert_sms_config([
                'gateway_name' => 'custom_sms',
                'display_name' => 'SMS',
                'credentials' => json_encode([]),
                'is_active' => 1
            ]);
        } else {
            $this->db->where('gateway_name', 'custom_sms')->update('sms_config', ['is_active' => 1]);
        }
    }

    //--------------- CONFIGURATION & SETTINGS -------------------->
    public function index()
    {
        if (!get_permission('sms_setting', 'is_view')) {
            access_denied();
        }

        // Handle Template Save (inline from index page)
        if ($this->input->post('save') == 'template') {
            if (!get_permission('sms_setting', 'is_edit')) {
                access_denied();
            }
            $template_id = $this->input->post('template_id');
            $array_template = [
                'notified'      => ($this->input->post('notify_enable') ? 1 : 0),
                'template_body' => $this->input->post('template_body'),
            ];
            $this->sms_model->update_sms_template($template_id, $array_template);
            $this->session->set_flashdata('sms_active_tab', 'sms_template');
            $this->session->set_flashdata('active_template', $template_id);
            set_alert('success', translate('information_has_been_updated_successfully'));
            redirect(base_url('sms'));
        }

        // Handle Active Gateway Selection Save
        if ($this->input->post('save') == 'gateway') {
            if (!get_permission('sms_setting', 'is_add')) {
                access_denied();
            }
            $selected_gateway = $this->input->post('sms_gateway');
            $this->sms_model->update_active_gateway($selected_gateway);
            $this->session->set_flashdata('sms_active_tab', 'sms_config');
            set_alert('success', translate('the_configuration_has_been_updated'));
            redirect(base_url('sms'));
        }

        // Handle Custom SMS Settings Save
        if ($this->input->post('save') == 'custom_sms') {
            if (!get_permission('sms_setting', 'is_add')) {
                access_denied();
            }
            $header_keys   = $this->input->post('header_keys') ?: [];
            $header_values = $this->input->post('header_values') ?: [];
            $headers = [];
            for ($i = 0; $i < count($header_keys); $i++) {
                if (!empty($header_keys[$i])) {
                    $headers[$header_keys[$i]] = $header_values[$i] ?? '';
                }
            }
            $param_keys   = $this->input->post('param_keys') ?: [];
            $param_values = $this->input->post('param_values') ?: [];
            $params = [];
            for ($i = 0; $i < count($param_keys); $i++) {
                if (!empty($param_keys[$i])) {
                    $params[$param_keys[$i]] = $param_values[$i] ?? '';
                }
            }
            $credentials = [
                'type'           => $this->input->post('custom_type'),
                'endpoint'       => $this->input->post('custom_endpoint'),
                'method'         => $this->input->post('custom_method'),
                'mobile_prefix'  => $this->input->post('custom_mobile_prefix'),
                'mobile_key'     => $this->input->post('custom_mobile_key'),
                'message_key'    => $this->input->post('custom_message_key'),
                'headers'        => $headers,
                'params'         => $params,
            ];
            $this->sms_model->update_gateway_credentials('custom_sms', $credentials);
            $this->session->set_flashdata('sms_active_tab', 'sms_config');
            set_alert('success', translate('the_configuration_has_been_updated'));
            redirect(base_url('sms'));
        }

        // Handle Test SMS sending
        if ($this->input->post('save') == 'test_sms') {
            if (!get_permission('sms_setting', 'is_edit')) {
                access_denied();
            }
            $recipient_no = $this->input->post('recipient_no');
            $message      = $this->input->post('message');
            if (empty($recipient_no) || empty($message)) {
                set_alert('error', 'Recipient number and message cannot be empty.');
            } else {
                $status = $this->sms_model->send($recipient_no, $message);
                if ($status) {
                    set_alert('success', 'Test SMS has been sent successfully.');
                } else {
                    set_alert('error', 'Failed to send test SMS. Please check logs/communication reports.');
                }
            }
            $this->session->set_flashdata('sms_active_tab', 'test_sms');
            redirect(base_url('sms'));
        }

        // Build the virtual $sms_api object expected by the view
        $sms_api = new stdClass();
        $sms_api->active_gateway = 'disabled';
        
        // Load Custom SMS using sms_model
        $custom_row = $this->sms_model->get_sms_config('custom_sms');
        $custom_creds = json_decode($custom_row->credentials ?? '{}', true);
        $sms_api->custom_type = $custom_creds['type'] ?? 'SMS';
        $sms_api->custom_endpoint = $custom_creds['endpoint'] ?? '';
        $sms_api->custom_method = $custom_creds['method'] ?? 'GET';
        $sms_api->custom_mobile_prefix = $custom_creds['mobile_prefix'] ?? '';
        $sms_api->custom_mobile_key = $custom_creds['mobile_key'] ?? 'MobileNumbers';
        $sms_api->custom_message_key = $custom_creds['message_key'] ?? 'Message';
        $sms_api->custom_headers = $custom_creds['headers'] ?? [];
        $sms_api->custom_params = $custom_creds['params'] ?? [];
        if ($custom_row && $custom_row->is_active == 1) {
            $sms_api->active_gateway = 'custom_sms';
        }

        $this->data['title'] = translate('sms') . ' ' . translate('settings');
        $this->data['sms_api'] = $sms_api;
        $this->data['template'] = $this->app_lib->get_table('sms_templates');
        $this->data['sub_page'] = 'settings/sms/index';
        $this->data['main_menu'] = 'settings';
        $this->load->view('layout/index', $this->data);
    }

    public function template($id = '')
    {
        if (isset($_POST['save'])) {
            if (!get_permission('sms_setting', 'is_add')) {
                access_denied();
            }
            $notified = (isset($_POST['notify_enable']) ? 1 : 0);
            $template_id = $this->input->post('template_id');
            $array_template = array(
                'subject' => $this->input->post('subject', true),
                'template_body' => $this->input->post('template_body'),
                'notified' => $notified,
            );
            $this->sms_model->update_sms_template($template_id, $array_template);
            $this->session->set_flashdata('active_template', $template_id);
            set_alert('success', translate('the_configuration_has_been_updated'));
            redirect(base_url('sms/' . route_hash('template')));
        }
        $this->data['title'] = translate('settings');
        $this->data['template'] = $this->app_lib->get_table('sms_templates');
        $this->data['sub_page'] = 'settings/sms/form';
        $this->data['main_menu'] = 'settings';
        $this->load->view('layout/index', $this->data);
    }

    //--------------- SEND SMS -------------------->
    public function send_sms()
    {
        if (!get_permission('send_sms', 'is_view')) {
            access_denied();
        }

        if ($_POST) {
            if (!get_permission('send_sms', 'is_add')) {
                access_denied();
            }

            $raw_ids  = (array) $this->input->post('user_ids');
            $sms_text = trim((string) $this->input->post('message'));

            // Decrypt every posted ID; drop any that fail HMAC verification.
            $user_ids = array_values(array_filter(array_map(function ($v) {
                $id = decrypt_id($v);
                return ctype_digit((string) $id) && (int) $id > 0 ? (int) $id : null;
            }, $raw_ids)));

            // Hard caps prevent SMS-blasting abuse if rate-limit is bypassed.
            $max_recipients = 500;
            $max_length     = 1600;
            if (count($user_ids) > $max_recipients) {
                set_alert('error', "Too many recipients selected. Maximum is {$max_recipients} per send.");
                redirect(base_url('sms/' . route_hash('send_sms')));
            }
            if (strlen($sms_text) > $max_length) {
                set_alert('error', "Message exceeds {$max_length} characters.");
                redirect(base_url('sms/' . route_hash('send_sms')));
            }

            if (!empty($user_ids) && $sms_text !== '') {
                $sent_count = 0;
                foreach ($user_ids as $user_id) {
                    $user_data = $this->user_model->get_single_user($user_id);

                    if (!empty($user_data)) {
                        $mobile_no = $user_data['mobile_no'] ?? '';

                        if (empty($mobile_no)) {
                            $this->sms_model->save_sms_log([
                                'user_id'  => $user_id,
                                'sms_text' => $sms_text,
                                'status'   => 'Failed - No Mobile Number',
                                'remarks'  => 'Web Console',
                            ]);
                        } else {
                            $this->sms_model->send($mobile_no, $sms_text, $user_id, 'Web Console');
                        }
                        $sent_count++;
                    }
                }

                $this->log_activity('send_sms', 'sms', 0, "Bulk SMS sent to {$sent_count} users");
                set_alert('success', "SMS sending initiated for {$sent_count} users.");
            } else {
                set_alert('error', 'Please select at least one User and enter a message.');
            }

            redirect(base_url('sms/' . route_hash('send_sms')));
        }

        $this->data['title'] = translate('Send SMS');
        $this->data['sub_page'] = 'settings/sms/send/index';
        $this->data['main_menu'] = $this->main_menu;
        
        // Fetch all active users using sms_model
        $this->data['user_list'] = $this->sms_model->get_active_users();
        
        $this->load->view('layout/index', $this->data);
    }
}
