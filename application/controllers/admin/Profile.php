<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Profile.php
 */

class Profile extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('user_model');
        $this->load->model('email_model');
        $this->load->model('sms_model');
        $this->load->model('user_2fa_model');
        $this->load->model('password_history_model');
    }

    // when user edit his profile


    public function index($id = '')
    {
        $userID = get_loggedin_user_id();

        if ($this->input->post('submit') == 'update') {
            $this->form_validation->set_rules('name', 'Name', 'trim|required');
            $this->form_validation->set_rules('mobile_no', 'Mobile No', 'trim|required');
            $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|callback_unique_email', array('unique_email' => translate('email_has_already_been_used')));
            if ($this->form_validation->run() !== false) {
                $data = $this->input->post();
                // Security Fix: Prevent IDOR and Privilege Escalation via parameter injection
                $data['user_id'] = $userID;
                unset($data['user_role']);
                unset($data['role']);
                unset($data['status']);
                
                $this->user_model->save($data);
                set_alert('success', translate('information_has_been_updated_successfully'));
                $this->session->set_flashdata('profile_tab', 1);
                redirect(base_url('profile/' . route_hash('index') . '/' . $data['user_id']));
            } else {
                $this->session->set_flashdata('profile_tab', 1);
            }
        }

        $user_data = $this->user_model->get_single_user($userID);

        if (empty($user_data)) {
            log_message('error', 'Profile: user data not found for UserID: ' . $userID . ' RoleID: ' . loggedin_role_id());
            set_alert('error', translate('user_not_found'));
            redirect(base_url('dashboard'));
        }
        $this->data['user'] = $user_data;

        $this->data['all_admins'] = $this->user_model->get_all_admin();

        $this->data['maritalStatus'] = array(
            '' => translate('select'),
            '1' => translate('single'),
            '2' => translate('married')
        );

        $this->data['sub_page'] = 'profile/profile';
        $this->data['title'] = translate('profile');
        $this->data['main_menu'] = 'profile';
        $this->load->view('layout/index', $this->data);
    }

    // when user change his password
    public function password()
    {
        if ($this->input->post('save')) {
            $this->form_validation->set_rules('current_password', 'Current Password', 'trim|required|min_length[4]|callback_check_validate_password');
            $this->form_validation->set_rules('new_password', 'New Password', 'trim|required|min_length[8]|password_complexity|callback_check_password_not_reused');
            $this->form_validation->set_rules('confirm_password', 'Confirm Password', 'trim|required|min_length[8]|matches[new_password]');
            if ($this->form_validation->run() !== false) {
                $new_password = $this->input->post('new_password');
                $cred_id = get_loggedin_id();
                $hash    = $this->app_lib->pass_hashed($new_password);
                $this->user_model->update_password_by_credential_id($cred_id, $hash);
                $this->password_history_model->record($cred_id, $hash);
                $this->log_activity('change_password', 'profile', get_loggedin_user_id(), 'Changed own password');
                set_alert('success', translate('password_has_been_changed'));
                redirect(base_url('profile/' . route_hash('password')));
            }
        }

        $this->data['sub_page'] = 'profile/password_change';
        $this->data['main_menu'] = 'profile';
        $this->data['title'] = translate('profile');
        $this->load->view('layout/index', $this->data);
    }

    // AJAX user own password change
    public function change_password()
    {
        $this->form_validation->set_rules('current_password', 'Current Password', 'trim|required|min_length[4]|callback_check_validate_password');
        $this->form_validation->set_rules('new_password', 'New Password', 'trim|required|min_length[8]|password_complexity|callback_check_password_not_reused');
        $this->form_validation->set_rules('confirm_password', 'Confirm Password', 'trim|required|min_length[8]|matches[new_password]');

        if ($this->form_validation->run() !== false) {
            $new_password = $this->input->post('new_password');
            $cred_id = get_loggedin_id();
            $hash    = $this->app_lib->pass_hashed($new_password);
            $this->user_model->update_password_by_credential_id($cred_id, $hash);
            $this->password_history_model->record($cred_id, $hash);

            // Log activity
            $this->log_activity('change_password', 'profile', get_loggedin_user_id(), 'Changed own password');
            
            $response = array(
                'status' => 'success',
                'msg' => translate('password_has_been_changed')
            );
            
            set_alert('success', translate('password_has_been_changed'));
        } else {
            $response = array(
                'status' => 'fail',
                'errors' => array(
                    'current_password' => form_error('current_password'),
                    'new_password' => form_error('new_password'),
                    'confirm_password' => form_error('confirm_password')
                )
            );
        }

        return $this->jsonResponse($response);
    }

    // current password verification is done here
    public function check_validate_password($password)
    {
        $getPassword = $this->user_model->get_password_by_credential_id(get_loggedin_id());
        $getVerify = $this->app_lib->verify_password($password, $getPassword);
        if ($getVerify) {
            return true;
        } else {
            $this->form_validation->set_message("check_validate_password", translate('current_password_is_invalid'));
            return false;
        }
    }

    /**
     * Validation callback: reject new passwords that have been used in the
     * last N change cycles (configurable via PASSWORD_HISTORY_KEEP env).
     */
    public function check_password_not_reused($password)
    {
        if (!isset($this->password_history_model)) {
            $this->load->model('password_history_model');
        }
        $reused = $this->password_history_model->is_reused(get_loggedin_id(), (string) $password);
        if ($reused) {
            $keep = (int) (getenv('PASSWORD_HISTORY_KEEP') ?: 10);
            $this->form_validation->set_message(
                'check_password_not_reused',
                'You cannot reuse one of your last ' . $keep . ' passwords. Please choose a different password.'
            );
            return false;
        }
        return true;
    }

    // unique valid email verification is done here
    public function unique_email($email)
    {
        $user_id = decrypt_id($this->input->post('user_id'));
        return $this->user_model->unique_email_check($email, $user_id);
    }

    // -------------------------------------------------------------------------
    // Two-Factor Authentication (TOTP)
    // -------------------------------------------------------------------------

    public function security()
    {
        $user_id = get_loggedin_user_id();
        $this->load->library('totp');

        $tfa_row = $this->user_2fa_model->get_by_user_id($user_id);
        $this->data['tfa_enabled']  = $tfa_row && $tfa_row->enabled;
        $this->data['tfa_has_row']  = (bool) $tfa_row;
        $this->data['backup_count'] = $this->user_2fa_model->backup_code_count($user_id);

        // Generate a fresh plaintext secret if 2FA is not yet set up; the
        // session holds the plaintext until the user confirms it with a code.
        if (!$tfa_row) {
            $plain_secret = $this->totp->generate_secret();
            $this->session->set_userdata('2fa_setup_secret', $plain_secret);
        } else {
            $plain_secret = $this->session->userdata('2fa_setup_secret')
                ?: ($tfa_row->enabled ? '' : totp_decrypt_secret($tfa_row->secret));
        }

        $user_data = $this->user_model->get_single_user($user_id);
        $label     = $user_data['email'] ?? 'user';
        $issuer    = get_global_setting('app_name') ?: 'Auth';

        $this->data['tfa_secret'] = $plain_secret;
        $this->data['tfa_qr_url'] = $plain_secret
            ? $this->totp->get_qr_url($plain_secret, $label, $issuer)
            : '';
        $this->data['sub_page']  = 'profile/security';
        $this->data['main_menu'] = 'profile';
        $this->data['title']     = translate('security_settings');
        $this->load->view('layout/index', $this->data);
    }

    public function enable_2fa()
    {
        if ($this->input->method() !== 'post') {
            show_error('Method Not Allowed', 405);
        }
        $user_id     = get_loggedin_user_id();
        $code        = trim($this->input->post('totp_code'));
        $plain_secret = $this->session->userdata('2fa_setup_secret');

        if (!$plain_secret) {
            set_alert('error', 'Setup session expired. Please try again.');
            redirect(base_url('profile/' . route_hash('security')));
        }

        $this->load->library('totp');
        if (!$this->totp->verify($plain_secret, $code)) {
            set_alert('error', 'Invalid code. Please scan the QR code again and enter the 6-digit code from your authenticator app.');
            redirect(base_url('profile/' . route_hash('security')));
        }

        $backup_codes = $this->totp->generate_backup_codes();
        // Encrypt the secret before persisting — protects against raw DB dump exposure
        $this->user_2fa_model->upsert($user_id, [
            'secret'       => totp_encrypt_secret($plain_secret),
            'enabled'      => 1,
            'backup_codes' => json_encode($backup_codes),
        ]);
        $this->session->unset_userdata('2fa_setup_secret');
        $this->log_activity('ENABLE_2FA', 'profile', $user_id, 'Two-factor authentication enabled');

        $this->session->set_flashdata('2fa_backup_codes', $backup_codes);
        set_alert('success', 'Two-factor authentication has been enabled.');
        redirect(base_url('profile/' . route_hash('security')));
    }

    public function disable_2fa()
    {
        if ($this->input->method() !== 'post') {
            show_error('Method Not Allowed', 405);
        }
        $user_id  = get_loggedin_user_id();
        $password = $this->input->post('current_password');
        $totp_code = trim($this->input->post('totp_code') ?? '');

        // Require current password
        $stored_pw = $this->user_model->get_password_by_credential_id(get_loggedin_id());
        if (!$this->app_lib->verify_password($password, $stored_pw)) {
            set_alert('error', 'Incorrect password. 2FA was not disabled.');
            redirect(base_url('profile/' . route_hash('security')));
        }

        // Also require a valid TOTP code — a stolen password alone cannot disable 2FA
        $tfa_row = $this->user_2fa_model->get_enabled_by_user_id($user_id);
        if ($tfa_row) {
            $this->load->library('totp');
            $plain_secret = totp_decrypt_secret($tfa_row->secret);
            if (!$this->totp->verify($plain_secret, $totp_code)) {
                set_alert('error', 'Invalid authenticator code. 2FA was not disabled.');
                redirect(base_url('profile/' . route_hash('security')));
            }
        }

        $this->user_2fa_model->set_enabled($user_id, false);
        $this->log_activity('DISABLE_2FA', 'profile', $user_id, 'Two-factor authentication disabled');
        set_alert('success', 'Two-factor authentication has been disabled.');
        redirect(base_url('profile/' . route_hash('security')));
    }

    public function regenerate_backup_codes()
    {
        if ($this->input->method() !== 'post') {
            show_error('Method Not Allowed', 405);
        }
        $user_id = get_loggedin_user_id();
        $tfa_row = $this->user_2fa_model->get_enabled_by_user_id($user_id);
        if (!$tfa_row) {
            set_alert('error', '2FA is not enabled on this account.');
            redirect(base_url('profile/' . route_hash('security')));
        }

        $this->load->library('totp');
        $new_codes = $this->totp->generate_backup_codes();
        $this->user_2fa_model->update_backup_codes($user_id, json_encode($new_codes));
        $this->log_activity('REGEN_2FA_BACKUP', 'profile', $user_id, 'Regenerated 2FA backup codes');
        $this->session->set_flashdata('2fa_backup_codes', $new_codes);
        set_alert('success', 'New backup codes generated. Store them safely.');
        redirect(base_url('profile/' . route_hash('security')));
    }
}
