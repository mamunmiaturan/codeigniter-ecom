<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @author : Mamun Mia Turan
 * @filename : Email.php
 */

class Email extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('email_model');
        // NOTE: Email config schema is created normalized in the base migration.
        // Default-row seeding lives in EmailConfigSeeder.
    }

    public function index()
    {
        if (!get_permission('email_setting', 'is_view')) {
            access_denied();
        }
        if (isset($_POST['save'])) {
            if (!get_permission('email_setting', 'is_edit')) {
                access_denied();
            }

            $allowed_fields = ['email_protocol', 'smtp_host', 'smtp_user', 'smtp_pass', 'smtp_port', 'smtp_encryption', 'email'];
            $data = [];
            foreach ($allowed_fields as $field) {
                if ($this->input->post($field) !== null) {
                    $data[$field] = $this->input->post($field);
                }
            }
            if (!empty($data)) {
                $this->email_model->update_email_config($data);
                set_alert('success', translate('the_configuration_has_been_updated'));
            }
            redirect(base_url('email'));
        }

        if (isset($_POST['test_email'])) {
            if (!get_permission('email_setting', 'is_edit')) {
                access_denied();
            }
            $recipient = $this->input->post('test_recipient');
            $subject   = $this->input->post('test_subject');
            $message   = $this->input->post('test_message');
            if (empty($recipient) || empty($subject) || empty($message)) {
                set_alert('error', 'Recipient, Subject, and Message cannot be empty.');
            } else {
                $status = $this->email_model->sendMail($recipient, $subject, $message);
                if ($status) {
                    set_alert('success', 'Test email sent successfully!');
                } else {
                    set_alert('error', 'Failed to send email. Check SMTP configuration or Email Logs.');
                }
            }
            $this->session->set_flashdata('active_tab', 'email_test');
            redirect(base_url('email'));
        }

        $this->data['config']    = $this->app_lib->get_table('email_config', 1, true);
        $this->data['title']     = translate('settings');
        $this->data['sub_page']  = 'settings/email/index';
        $this->data['main_menu'] = 'settings';
        $this->load->view('layout/index', $this->data);
    }

    public function template($id = '')
    {
        if (!get_permission('email_setting', 'is_view')) {
            access_denied();
        }
        $this->load->model('email_template_model');

        if (isset($_POST['save'])) {
            if (!get_permission('email_setting', 'is_edit')) {
                access_denied();
            }
            $template_id = (int) $this->input->post('template_id');
            if ($template_id <= 0) {
                set_alert('error', translate('invalid_request'));
                redirect(base_url('email/template'));
            }
            $this->email_template_model->update($template_id, [
                'subject'       => $this->input->post('subject'),
                'template_body' => $this->input->post('template_body'),
                'is_active'     => $this->input->post('is_active') ? 1 : 0,
            ]);
            $this->session->set_flashdata('emailt_active', $template_id);
            set_alert('success', translate('the_configuration_has_been_updated'));
            redirect(base_url('email/template'));
        }

        $this->data['headerelements'] = [
            'js' => ['vendor/ckeditor/ckeditor.js', 'vendor/ckeditor/adapters/jquery.js'],
        ];
        $this->data['title']     = translate('settings');
        $this->data['template']  = $this->email_template_model->list_all();
        $this->data['sub_page']  = 'settings/email/form';
        $this->data['main_menu'] = 'settings';
        $this->load->view('layout/index', $this->data);
    }
}
