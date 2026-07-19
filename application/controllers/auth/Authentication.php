<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Authentication
 * @version : 1.0.0
 * @developed by : Turan
 * @support : [EMAIL_ADDRESS]
 * @author : Mamun Mia Turan
 * @filename : Authentication.php
 */

class Authentication extends Authentication_Controller
{

    /** @var AuthenticationService */
    private $auth_service;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('user_2fa_model');
        $this->load->model('login_history_model');
        $this->load->model('password_history_model');
        require_once APPPATH . 'services/AuthenticationService.php';
        $this->auth_service = new AuthenticationService();
    }

    public function index()
    {
        // check auth
        if (is_loggedin()) {
            redirect('dashboard');
        }
        if ($this->session->userdata('store_customer')) {
            redirect(base_url('account'));
        }

        if ($_POST) {
            $config = array(
                array(
                    'field' => 'email',
                    'label' => 'Email Address',
                    'rules' => 'trim|required|valid_email',
                ),
                array(
                    'field' => 'password',
                    'label' => 'Password',
                    'rules' => 'trim|required',
                ),
            );
            $this->form_validation->set_rules($config);
            if ($this->form_validation->run() !== false) {
                // reCAPTCHA — enforced only when site + secret keys are configured
                // in settings; otherwise login proceeds normally (non-breaking).
                $this->load->library('recaptcha');
                if ($this->recaptcha->is_configured()) {
                    $rc = $this->recaptcha->verifyResponse((string) $this->input->post('g-recaptcha-response'));
                    if (empty($rc['success'])) {
                        set_alert('error', translate('please_complete_the_recaptcha') ?: 'Please complete the reCAPTCHA challenge.');
                        redirect(base_url('authentication'));
                    }
                }
                $email = (string) $this->input->post('email');
                $password = (string) $this->input->post('password');

                $result = $this->auth_service->attempt_password_login($email, $password, 'web');

                if ($result['code'] === 'blocked') {
                    set_alert('error', 'Too many login attempts. Please try again after 15 minutes.');
                    redirect(base_url('authentication'));
                }
                if ($result['code'] === 'profile_missing') {
                    set_alert('error', translate('user_profile_not_found'));
                    redirect(base_url('authentication'));
                }
                if ($result['code'] === 'mfa_required') {
                    $login_credential = $result['credential'];
                    $profile          = $result['profile'];
                    $this->session->sess_regenerate(true);
                    $this->session->set_userdata([
                        '2fa_pending'        => true,
                        '2fa_user_id'        => $login_credential->user_id,
                        '2fa_login_id'       => $login_credential->id,
                        '2fa_role'           => $login_credential->role,
                        '2fa_profile_name'   => $profile->name,
                        '2fa_profile_uid'    => $profile->uniqueid,
                        '2fa_profile_photo'  => $profile->photo,
                        '2fa_date_format'    => $this->data['global_config']['date_format'] ?? 'Y-m-d',
                        '2fa_set_lang'       => $profile->language ?? ($this->data['global_config']['translation'] ?? 'english'),
                    ]);
                    redirect(base_url('authentication/verify_2fa/form'));
                }

                if ($result['ok']) {
                    $login_credential = $result['credential'];
                    $profile          = $result['profile'];

                    // Storefront customers share the login_credential table but must
                    // never receive the admin 'loggedin' session — route them to the
                    // separate customer session and the storefront instead.
                    if ((int) $login_credential->role === ROLE_CUSTOMER_ID) {
                        $this->_login_customer($login_credential, $profile);
                        return;
                    }

                    $sessionData = [
                        'name'             => $profile->name,
                        'uniqueid'         => $profile->uniqueid,
                        'logger_photo'     => $profile->photo,
                        'loggedin_id'      => $login_credential->id,
                        'loggedin_role_id' => $login_credential->role,
                        'loggedin_userid'  => $login_credential->user_id,
                        'date_format'      => $this->data['global_config']['date_format'] ?? 'Y-m-d',
                        'set_lang'         => $profile->language ?? ($this->data['global_config']['translation'] ?? 'english'),
                        'loggedin'         => true,
                        'login_time'       => time(),
                    ];

                    // Prevent Session Fixation: Regenerate session ID on successful login
                    $this->session->sess_regenerate(true);
                    $this->session->set_userdata($sessionData);
                    $this->authentication_model->update_last_login($login_credential->id);
                    $this->log_activity('login', 'authentication', $login_credential->user_id, 'User logged in successfully');

                    // Post-login redirect: same-origin absolute URLs or single-leading-
                    // slash relative paths only, else /dashboard. Shared with the
                    // customer branch via _safe_post_login_redirect().
                    redirect($this->_safe_post_login_redirect(base_url('dashboard')));
                }

                if ($result['code'] === 'inactive') {
                    set_alert('error', translate('your_account_has_been_inactive_please_contact_with_system_administrator'));
                    redirect(base_url('authentication'));
                }

                // 'invalid' or anything else falls through here.
                set_alert('error', translate('email_password_incorrect'));
                redirect(base_url('authentication'));
            }
        }
        $this->data['title'] = translate('login');
        $this->data['sub_page'] = 'authentication/login/form';
        $this->load->view('layout/index', $this->data);
    }

    /**
     * Establish a storefront customer session for a credential belonging to a
     * customer (ROLE_CUSTOMER_ID). Kept under the separate `store_customer` key
     * so it never collides with the admin session. Called from index().
     */
    private function _login_customer($cred, $profile)
    {
        // Prevent session fixation, then set the storefront session key only.
        $this->session->sess_regenerate(true);
        $this->session->set_userdata('store_customer', [
            'id'    => (int) $cred->user_id,
            'name'  => $profile->name,
            'email' => $cred->email,
        ]);
        $this->authentication_model->update_last_login($cred->id);
        $this->log_activity('login', 'authentication', $cred->user_id, 'Customer logged in successfully');

        // Merge any guest cart into the now-identified customer.
        $this->load->model('cart_model');
        $guest = $this->session->userdata('store_cart_token');
        if ($guest) {
            $this->cart_model->merge_guest_into_user($guest, (int) $cred->user_id);
        }

        set_alert('success', 'Welcome back, ' . $profile->name . '!');
        redirect($this->_safe_post_login_redirect(base_url('account')));
    }

    /**
     * Resolve a safe post-login redirect from the pending redirect_url set by the
     * auth guard, else $fallback. Allows only same-origin absolute URLs or single-
     * leading-slash relative paths; the auth page itself is rejected to avoid loops.
     */
    private function _safe_post_login_redirect(string $fallback): string
    {
        if (!$this->session->has_userdata('redirect_url')) {
            return $fallback;
        }
        $redirect_url = trim((string) $this->session->userdata('redirect_url'));
        $this->session->unset_userdata('redirect_url');
        if ($redirect_url === '') {
            return $fallback;
        }
        $auth_url = rtrim(base_url('authentication'), '/');
        $is_auth_page = rtrim($redirect_url, '/') === $auth_url
            || strpos($redirect_url, $auth_url . '/') === 0;
        if ($is_auth_page) {
            return $fallback;
        }
        if (strpos($redirect_url, base_url()) === 0 && preg_match('#^https?://#i', $redirect_url)) {
            return $redirect_url;
        }
        if (preg_match('#^/[^/\\\\]#', $redirect_url) === 1) {
            return $redirect_url;
        }
        return $fallback;
    }

    /**
     * Customer self-registration page (admin auth theme). The form posts to the
     * storefront Account::do_register handler, which creates the customer and
     * establishes the store_customer session.
     */
    public function register()
    {
        if ($this->session->userdata('store_customer')) {
            redirect(base_url('account'));
            return;
        }
        if (is_loggedin()) {
            redirect(base_url('dashboard'));
            return;
        }
        $this->data['title']    = 'Create Account';
        $this->data['sub_page'] = 'authentication/register/form';
        $this->load->view('layout/index', $this->data);
    }

    public function autoLogin($userId)
    {
        if (!can_impersonate_users()) {
            access_denied();
        }

        $self_id = (int) get_loggedin_user_id();
        $actor_role = (int) loggedin_role_id();

        // Rate-limit impersonation: 10 attempts / 15 minutes per actor
        $this->load->library('api_rate_limiter');
        $rate = $this->api_rate_limiter->check('impersonate:' . $self_id, 10, 900);
        if (!$rate['allowed']) {
            $this->authentication_model->log_security_alert($self_id, json_encode([
                'event'  => 'impersonation_rate_limit',
                'target' => decrypt_id($userId),
            ]));
            set_alert('error', 'Too many impersonation attempts. Locked for ' . ceil($rate['reset_in'] / 60) . ' minute(s).');
            redirect(base_url('user'));
        }

        $check = $this->auth_service->may_impersonate($self_id, $actor_role, $userId);
        if (!$check['ok']) {
            if ($check['code'] === 'invalid_id') {
                show_404();
                return;
            }
            if ($check['code'] === 'super_admin_blocked') {
                $this->authentication_model->log_security_alert($self_id, json_encode([
                    'event'       => 'impersonation_blocked_super_admin',
                    'target_user' => decrypt_id($userId),
                ]));
            }
            if ($check['code'] === 'not_found') {
                set_alert('error', translate('employee_not_found'));
                redirect(base_url('authentication'));
                return;
            }
            if ($check['code'] === 'inactive') {
                set_alert('error', translate('your_account_has_been_inactive_please_contact_with_system_administrator'));
                redirect(base_url('authentication'));
                return;
            }
            set_alert('error', translate('access_denied'));
            redirect(base_url('user'));
            return;
        }

        $userId = (int) $check['target_id'];
        $login_credential = $check['credential'];

        if ($login_credential) {
            // Fetch the user profile using authentication_model
            $profile = $this->authentication_model->get_user_profile($login_credential->user_id);

            if (!$profile) {
                set_alert('error', translate('user_profile_not_found'));
                redirect(base_url('authentication'));
            }

            // Retrieve current session data
            $current_session = $this->session->userdata();

            // Save current session with secure fingerprint binding
            if (!empty($current_session) && isset($current_session['loggedin']) && $current_session['loggedin']) {
                $current_session['impersonator_ip'] = $this->input->ip_address();
                $current_session['impersonator_ua'] = $this->input->user_agent();
                $this->session->set_userdata('previous_session', $current_session);
                $this->session->set_userdata('previous_session_id', $current_session['loggedin_userid']);
            }

            // Prepare new session data for the current user
            $sessionData = array(
                'name'                 => $profile->name,
                'uniqueid'             => $profile->uniqueid,
                'logger_photo'         => $profile->photo,
                'loggedin_id'          => $login_credential->id,
                'loggedin_role_id'     => $login_credential->role,
                'loggedin_userid'      => $login_credential->user_id,
                'date_format'          => $this->data['global_config']['date_format'] ?? 'Y-m-d',
                'set_lang'             => $profile->language ?? ($this->data['global_config']['translation'] ?? 'english'),
                'loggedin'             => true,
                'previous_session_id'  => $this->session->userdata('previous_session_id') ?? null,
                'login_time'           => time(),
            );

            // Regenerate session to protect session boundaries during impersonation
            $this->session->sess_regenerate(true);
            $this->session->set_userdata($sessionData);

            // Update last login using authentication_model
            $this->authentication_model->update_last_login($login_credential->id);
            $actor_label = 'Branch Manager';
            if ($actor_role === ROLE_SUPERMAN_ID) {
                $actor_label = 'Super-admin';
            } elseif ($actor_role === ROLE_ADMIN_ID) {
                $actor_label = 'Admin';
            }
            $this->log_activity('impersonate', 'authentication', $userId,
                $actor_label . ' impersonated user ' . $userId);

            redirect(base_url('dashboard'));
        } else {
            set_alert('error', translate('employee_not_found'));
            redirect(base_url('authentication'));
        }
    }
    public function restore_previous_session()
    {
        // Retrieve the previous session data
        $previous_session = $this->session->userdata('previous_session');

        if (!empty($previous_session)) {
            $current_ip = $this->input->ip_address();
            $current_ua = $this->input->user_agent();

            // Validate secure fingerprint binding to prevent impersonation session hijacking
            if (($previous_session['impersonator_ip'] ?? '') !== $current_ip || ($previous_session['impersonator_ua'] ?? '') !== $current_ua) {
                // Log severe fingerprint mismatch to activity logs using authentication_model
                $payload_json = json_encode([
                    'expected_ip' => $previous_session['impersonator_ip'] ?? '',
                    'actual_ip' => $current_ip,
                    'expected_ua' => $previous_session['impersonator_ua'] ?? '',
                    'actual_ua' => $current_ua
                ]);
                $this->authentication_model->log_security_alert($previous_session['loggedin_userid'] ?? null, $payload_json);

                $this->session->sess_destroy();
                set_alert('error', 'Security Violation: Impersonation fingerprint mismatch. Access denied.');
                redirect(base_url('authentication'));
            }

            // Remove internal session metadata and previous_session keys from the restoration array to avoid nesting or pollution
            unset($previous_session['previous_session']);
            unset($previous_session['previous_session_id']);
            unset($previous_session['impersonator_ip']);
            unset($previous_session['impersonator_ua']);
            unset($previous_session['__ci_last_regenerate']);

            // Clear current user keys
            $current_keys = array_keys($this->session->userdata());
            foreach ($current_keys as $key) {
                $this->session->unset_userdata($key);
            }

            // Regenerate session ID safely before restoring previous admin data to align with login flow
            // (PHP migrates current $_SESSION to the new ID; we immediately overwrite with $previous_session.)
            $this->session->sess_regenerate(true);

            // Restore the previous admin session data
            $this->session->set_userdata($previous_session);

            redirect(base_url('dashboard'));
        } else {
            set_alert('error', translate('no_previous_session_found'));
            redirect(base_url('dashboard'));
        }
    }

    // forgot password
    public function forgot()
    {
        if (is_loggedin()) {
            redirect(base_url('dashboard'), 'refresh');
        }

        if ($_POST) {
            // Rate-limit forgot-password to 5 requests per 15 minutes per IP
            $this->load->library('api_rate_limiter');
            $rate = $this->api_rate_limiter->check('forgot:' . $this->input->ip_address(), 5, 900);
            if (!$rate['allowed']) {
                set_alert('error', 'Too many requests. Please try again in ' . ceil($rate['reset_in'] / 60) . ' minute(s).');
                redirect(base_url('authentication/' . route_hash('forgot')));
            }

            $config = array(
                array(
                    'field' => 'email',
                    'label' => 'Email Address',
                    'rules' => 'trim|required|valid_email',
                ),
            );
            $this->form_validation->set_rules($config);
            if ($this->form_validation->run() !== false) {
                $email = $this->input->post('email');
                $login_credential = $this->authentication_model->get_login_by_email($email);
                // Always show the same success message to prevent email enumeration.
                // Silently skip inactive / unknown accounts.
                if ($login_credential && $login_credential->status === 'Active') {
                    $this->authentication_model->lose_password($email);
                }
                $this->session->set_flashdata('reset_res', 'TRUE');
                set_alert('success', translate('password_reset_email_sent_successfully'));
                redirect(base_url('authentication/' . route_hash('forgot')));
            }
        }
        $this->data['title'] = translate('Password Restoration');
        $this->data['sub_page'] = 'authentication/forgot/form';
        $this->load->view('layout/index', $this->data);
    }

    // password reset
    public function password_reset()
    {
        if (is_loggedin()) {
            redirect(base_url('dashboard'), 'refresh');
        }

        $key = $this->input->post('reset_key') ?: $this->input->get('key');

        if (!empty($key)) {
            // Using correct migration column 'token' instead of 'key' and check 'is_used' using authentication_model
            $reset = $this->authentication_model->get_reset_token($key);
            if ($reset) {
                $login_credential = $this->authentication_model->get_login_by_id($reset['login_credential_id']);
                if (!$login_credential || $login_credential->status !== 'Active') {
                    set_alert('error', translate('your_account_has_been_inactive_please_contact_with_system_administrator'));
                    redirect(base_url('authentication'));
                }

                // Check for expiry using migration's expires_at column
                if (time() > strtotime($reset['expires_at'])) {
                    $this->authentication_model->delete_reset_token($key);
                    set_alert('error', 'Token Has Expired');
                    redirect(base_url('authentication'));
                }

                if ($this->input->post()) {
                    $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[8]|password_complexity|matches[c_password]');
                    $this->form_validation->set_rules('c_password', 'Confirm Password', 'trim|required|min_length[8]');
                    if ($this->form_validation->run() !== false) {
                        $plain_password = (string) $this->input->post('password');
                        $cred_id = (int) $reset['login_credential_id'];

                        // Reject reuse of any of the last N passwords for this credential.
                        if ($this->password_history_model->is_reused($cred_id, $plain_password)) {
                            $keep = (int) (getenv('PASSWORD_HISTORY_KEEP') ?: 10);
                            set_alert('error', 'You cannot reuse one of your last ' . $keep . ' passwords. Please choose a different password.');
                            redirect(current_url());
                        }

                        $password_hash = $this->app_lib->pass_hashed($plain_password);

                        // Update the user's password using authentication_model
                        $this->authentication_model->update_password($cred_id, $password_hash);
                        $this->password_history_model->record($cred_id, $password_hash);

                        // Mark reset token as used (migration has is_used column) using authentication_model
                        $this->authentication_model->mark_token_used($cred_id);

                        set_alert('success', 'Password Reset Successfully');
                        redirect(base_url('authentication'));
                    }
                }
                $this->data['title'] = translate('reset_password');
                $this->data['sub_page'] = 'authentication/reset/form';
                $this->load->view('layout/index', $this->data);
            } else {
                set_alert('error', 'Token Has Expired');
                redirect(base_url('authentication'));
            }
        } else {
            set_alert('error', 'Token Has Expired');
            redirect(base_url('authentication'));
        }
    }

    // 2FA verification after successful password check
    public function verify_2fa()
    {
        if (is_loggedin()) {
            redirect('dashboard');
        }
        if (!$this->session->userdata('2fa_pending')) {
            redirect(base_url('authentication'));
        }

        if ($_POST) {
            $ip   = $this->input->ip_address();
            $this->load->library('api_rate_limiter');
            $rate = $this->api_rate_limiter->check('2fa_verify:' . $ip, 5, 300);
            if (!$rate['allowed']) {
                set_alert('error', 'Too many verification attempts. Please wait ' . ceil($rate['reset_in'] / 60) . ' minute(s).');
                redirect(base_url('authentication/verify_2fa/form'));
            }

            $code    = trim($this->input->post('totp_code'));
            $user_id = (int) $this->session->userdata('2fa_user_id');

            $tfa_row = $this->user_2fa_model->get_enabled_by_user_id($user_id);
            if (!$tfa_row) {
                $this->session->sess_destroy();
                redirect(base_url('authentication'));
            }

            $this->load->library('totp');
            $plain_secret = totp_decrypt_secret($tfa_row->secret);
            $valid = $this->totp->verify($plain_secret, $code);

            // Try backup codes if TOTP fails
            if (!$valid && strlen($code) > 6) {
                $backup_rate = $this->api_rate_limiter->check('backup_verify:' . $user_id, 5, 900);
                if (!$backup_rate['allowed']) {
                    set_alert('error', 'Too many backup code attempts. Locked out for ' . ceil($backup_rate['reset_in'] / 60) . ' minute(s).');
                    redirect(base_url('authentication/verify_2fa/form'));
                }

                $decrypted_codes = $this->user_2fa_model->decrypt_backup_codes($tfa_row->backup_codes ?? null);
                $updated_codes   = $this->totp->verify_backup($decrypted_codes, $code);
                if ($updated_codes !== null) {
                    $this->user_2fa_model->update_backup_codes($user_id, $updated_codes);
                    $valid = true;
                    $this->api_rate_limiter->reset('backup_verify:' . $user_id);
                }
            }

            if (!$valid) {
                $this->login_history_model->record('2fa_failure', (int) $user_id,
                    (string) $this->session->userdata('2fa_profile_uid'), ['source' => 'web']);
                set_alert('error', 'Invalid verification code. Please try again.');
                redirect(base_url('authentication/verify_2fa/form'));
            }

            // Code is correct — complete login
            $this->api_rate_limiter->reset('2fa_verify:' . $ip);
            $this->login_history_model->record('2fa_success', (int) $user_id,
                (string) $this->session->userdata('2fa_profile_uid'), ['source' => 'web']);
            $sessionData = [
                'name'             => $this->session->userdata('2fa_profile_name'),
                'uniqueid'         => $this->session->userdata('2fa_profile_uid'),
                'logger_photo'     => $this->session->userdata('2fa_profile_photo'),
                'loggedin_id'      => $this->session->userdata('2fa_login_id'),
                'loggedin_role_id' => $this->session->userdata('2fa_role'),
                'loggedin_userid'  => $user_id,
                'date_format'      => $this->session->userdata('2fa_date_format'),
                'set_lang'         => $this->session->userdata('2fa_set_lang'),
                'loggedin'         => true,
                'login_time'       => time(),
            ];
            // Clear the pending 2FA keys before setting full session
            foreach (['2fa_pending','2fa_user_id','2fa_login_id','2fa_role','2fa_profile_name',
                      '2fa_profile_uid','2fa_profile_photo','2fa_date_format','2fa_set_lang'] as $k) {
                $this->session->unset_userdata($k);
            }
            $this->session->set_userdata($sessionData);
            $this->authentication_model->update_last_login($sessionData['loggedin_id']);
            $this->log_activity('login', 'authentication', $user_id, 'User logged in with 2FA');
            redirect(base_url('dashboard'));
        }

        $this->data['title'] = translate('two_factor_authentication');
        $this->data['sub_page'] = 'authentication/verify_2fa/form';
        $this->load->view('layout/index', $this->data);
    }

    // Serve browser default favicon request without hitting CI's Faviconico controller.
    public function favicon()
    {
        $favicon = get_global_setting('favicon');
        if (empty($favicon)) {
            $favicon = 'favicon.png';
        }

        $candidates = [
            FCPATH . 'uploads/app_image/' . $favicon,
            FCPATH . 'uploads/app_image/logo.png',
            FCPATH . 'uploads/app_image/defualt.png',
        ];

        $path = null;
        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                $path = $candidate;
                break;
            }
        }

        if ($path === null) {
            $this->output->set_status_header(204)->set_output('');
            return;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $types = [
            'png' => 'image/png',
            'ico' => 'image/x-icon',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];

        $this->output
            ->set_content_type($types[$ext] ?? 'image/png')
            ->set_header('Cache-Control: public, max-age=86400')
            ->set_output(file_get_contents($path));
    }

    // session logout
    public function logout()
    {
        $uid = (int) ($this->session->userdata('loggedin_userid') ?: 0);
        $name = (string) ($this->session->userdata('name') ?: '');
        if ($uid > 0) {
            $this->login_history_model->record('logout', $uid, $name, ['source' => 'web']);
        }
        $this->session->unset_userdata('name');
        $this->session->unset_userdata('logger_photo');
        $this->session->unset_userdata('loggedin_id');
        $this->session->unset_userdata('loggedin_role_id');
        $this->session->unset_userdata('loggedin_userid');
        $this->session->unset_userdata('date_format');
        $this->session->unset_userdata('set_lang');
        $this->session->unset_userdata('loggedin');
        $this->session->sess_destroy();
        redirect(base_url(), 'refresh');
    }
}
