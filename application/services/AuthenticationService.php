<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Base_Service.php';

/**
 * AuthenticationService
 *
 * Pulls the credential check, 2FA gate, password-reset, and impersonation
 * logic out of the Authentication controller. The controller becomes a thin
 * HTTP adapter: parse input, invoke a service method, render output.
 *
 * Every public method returns a structured result:
 *   ['ok' => bool, 'code' => string, ...]
 *
 * Codes used:
 *   - ok            login succeeded, session/redirect can proceed
 *   - mfa_required  caller must collect a TOTP code
 *   - blocked       rate-limited / lockout
 *   - inactive      credential exists but not Active
 *   - invalid       wrong email/password
 *   - expired       reset token expired/used
 *   - reused        password reuse rejected
 */
class AuthenticationService extends Base_Service
{
    public function __construct()
    {
        parent::__construct();
        $this->ci->load->model('authentication_model');
        $this->ci->load->model('user_2fa_model');
        $this->ci->load->model('login_history_model');
        $this->ci->load->model('password_history_model');
    }

    /**
     * Attempt password login. Records login_history on every branch.
     * Caller is responsible for setting the session on `ok` (so we don't
     * couple this service to HTTP session machinery).
     */
    public function attempt_password_login(string $email, string $password, string $source = 'web'): array
    {
        $cred = $this->ci->authentication_model->login_credential($email, $password);
        if ($cred === 'blocked') {
            $this->ci->login_history_model->record('blocked', null, $email, ['source' => $source]);
            return ['ok' => false, 'code' => 'blocked'];
        }
        if (!$cred) {
            $this->ci->login_history_model->record('failure', null, $email, ['source' => $source]);
            return ['ok' => false, 'code' => 'invalid'];
        }
        if ($cred->status !== 'Active') {
            $this->ci->login_history_model->record('failure', (int) $cred->user_id, $email, [
                'source' => $source, 'reason' => 'inactive',
            ]);
            return ['ok' => false, 'code' => 'inactive', 'credential' => $cred];
        }

        $profile = $this->ci->authentication_model->get_user_profile($cred->user_id);
        if (!$profile) {
            return ['ok' => false, 'code' => 'profile_missing', 'credential' => $cred];
        }

        $tfa = $this->ci->user_2fa_model->get_enabled_by_user_id($cred->user_id);
        if ($tfa) {
            // Caller must collect the TOTP code before we record a 'success'.
            return [
                'ok'      => false,
                'code'    => 'mfa_required',
                'credential' => $cred,
                'profile' => $profile,
                'tfa'     => $tfa,
            ];
        }

        $this->ci->login_history_model->record('success', (int) $cred->user_id, $email, ['source' => $source]);
        return [
            'ok'         => true,
            'code'       => 'ok',
            'credential' => $cred,
            'profile'    => $profile,
        ];
    }

    /**
     * Verify a TOTP code (or one-shot backup code) for the user. Updates the
     * backup-code list on consumption.
     */
    public function verify_totp(int $user_id, string $code, string $source = 'web'): array
    {
        $tfa = $this->ci->user_2fa_model->get_enabled_by_user_id($user_id);
        if (!$tfa) {
            return ['ok' => false, 'code' => 'mfa_not_enabled'];
        }

        $this->ci->load->library('totp');
        $plain_secret = totp_decrypt_secret($tfa->secret);
        $valid = $this->ci->totp->verify($plain_secret, $code);

        if (!$valid && strlen($code) > 6) {
            $decrypted_codes = $this->ci->user_2fa_model->decrypt_backup_codes($tfa->backup_codes ?? null);
            $updated_codes   = $this->ci->totp->verify_backup($decrypted_codes, $code);
            if ($updated_codes !== null) {
                $this->ci->user_2fa_model->update_backup_codes($user_id, $updated_codes);
                $valid = true;
            }
        }

        if (!$valid) {
            $this->ci->login_history_model->record('2fa_failure', $user_id, '', ['source' => $source]);
            return ['ok' => false, 'code' => 'invalid_totp'];
        }
        $this->ci->login_history_model->record('2fa_success', $user_id, '', ['source' => $source]);
        return ['ok' => true, 'code' => 'ok'];
    }

    /**
     * Apply a password reset token. Checks expiry, token reuse, account
     * status, and rejects password reuse from history.
     */
    public function reset_password(string $token, string $new_password): array
    {
        $reset = $this->ci->authentication_model->get_reset_token($token);
        if (!$reset) {
            return ['ok' => false, 'code' => 'expired'];
        }
        $cred = $this->ci->authentication_model->get_login_by_id($reset['login_credential_id']);
        if (!$cred || $cred->status !== 'Active') {
            return ['ok' => false, 'code' => 'inactive'];
        }
        if (time() > strtotime($reset['expires_at'])) {
            $this->ci->authentication_model->delete_reset_token($token);
            return ['ok' => false, 'code' => 'expired'];
        }
        $cred_id = (int) $reset['login_credential_id'];
        if ($this->ci->password_history_model->is_reused($cred_id, $new_password)) {
            return ['ok' => false, 'code' => 'reused'];
        }
        $hash = $this->ci->app_lib->pass_hashed($new_password);
        $this->ci->authentication_model->update_password($cred_id, $hash);
        $this->ci->password_history_model->record($cred_id, $hash);
        $this->ci->authentication_model->mark_token_used($cred_id);
        return ['ok' => true, 'code' => 'ok', 'credential_id' => $cred_id];
    }

    /**
     * Validate that the actor is allowed to impersonate the target user.
     * Returns the resolved credential row on success.
     */
    public function may_impersonate(int $actor_user_id, int $actor_role, string $encrypted_target): array
    {
        if (!can_impersonate_users()) {
            return ['ok' => false, 'code' => 'forbidden'];
        }

        $target_id = decrypt_id($encrypted_target);
        if (!$target_id) {
            return ['ok' => false, 'code' => 'invalid_id'];
        }
        $target_id = (int) $target_id;

        $cred = $this->ci->authentication_model->get_user_login($target_id);
        if (!$cred) {
            return ['ok' => false, 'code' => 'not_found'];
        }

        $user_row = $this->ci->db->select('branch_id')
            ->from('users')
            ->where('id', $target_id)
            ->get()
            ->row();
        $target_branch_id = ($user_row && $user_row->branch_id) ? (int) $user_row->branch_id : null;

        if (!can_impersonate_target($target_id, (int) $cred->role, $target_branch_id)) {
            if ((int) $cred->role === ROLE_SUPERMAN_ID) {
                return ['ok' => false, 'code' => 'super_admin_blocked'];
            }
            if ($target_id === $actor_user_id) {
                return ['ok' => false, 'code' => 'self'];
            }
            return ['ok' => false, 'code' => 'forbidden'];
        }

        if ($cred->status !== 'Active') {
            return ['ok' => false, 'code' => 'inactive'];
        }

        return ['ok' => true, 'code' => 'ok', 'credential' => $cred, 'target_id' => $target_id];
    }
}
