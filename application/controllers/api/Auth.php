<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Api_Controller.php';

/**
 * POST /api/v1/auth/login    {email, password}            -> access + refresh
 * POST /api/v1/auth/refresh  {refresh_token}              -> new access + refresh
 * POST /api/v1/auth/logout   (Bearer)                     -> revoke jti + refresh
 * GET  /api/v1/auth/me       (Bearer)                     -> current identity
 *
 * Notes:
 *  - 2FA is honored: if the account has 2FA enabled, the password+credentials
 *    endpoint refuses login until a TOTP code is supplied via {totp_code}.
 *  - Access token TTL defaults to 900s; refresh TTL defaults to 14 days.
 *    Override via JWT_ACCESS_TTL / JWT_REFRESH_TTL env vars.
 *  - The same per-IP rate limiter that protects browser login also protects
 *    this endpoint (5/15min). Verified login resets the counter.
 */
class Auth extends Api_Controller
{
    protected $require_auth = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('authentication_model');
        $this->load->model('user_2fa_model');
        $this->load->model('login_history_model');
        $this->load->model('api_refresh_token_model');
        $this->load->library(['jwt', 'app_lib', 'api_rate_limiter']);
    }

    public function login()
    {
        $body = $this->_json_body();
        $email    = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');
        $totp     = trim((string) ($body['totp_code'] ?? ''));

        if ($email === '' || $password === '') {
            $this->fail('email and password are required', 422);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->fail('Invalid email format', 422);
            return;
        }

        // Same throttling envelope as the browser login.
        $ip   = $this->input->ip_address();
        $rate = $this->api_rate_limiter->check('login_api:' . $email . ':' . $ip, 5, 900);
        if (!$rate['allowed']) {
            $this->login_history_model->record('blocked', null, $email, ['reason' => 'rate_limit']);
            $this->fail('Too many login attempts. Try again in ' . max(1, (int) ceil($rate['reset_in'] / 60)) . ' minute(s).', 429);
            return;
        }

        $cred = $this->authentication_model->login_credential($email, $password);
        if ($cred === 'blocked') {
            $this->login_history_model->record('blocked', null, $email, ['source' => 'api']);
            $this->fail('Account temporarily blocked.', 423);
            return;
        }
        if (!$cred) {
            $this->login_history_model->record('failure', null, $email, ['source' => 'api']);
            $this->fail('Invalid credentials.', 401);
            return;
        }
        if ($cred->status !== 'Active') {
            $this->login_history_model->record('failure', (int) $cred->user_id, $email, [
                'source' => 'api',
                'reason' => 'inactive',
            ]);
            $this->fail('Account inactive.', 403);
            return;
        }

        // 2FA gate.
        $tfa = $this->user_2fa_model->get_enabled_by_user_id($cred->user_id);
        if ($tfa) {
            if ($totp === '') {
                // Not a hard error — return a structured signal so SPAs can show the TOTP UI.
                $this->ok(['mfa_required' => true], 200);
                return;
            }
            $this->load->library('totp');
            $plain_secret = totp_decrypt_secret($tfa->secret);
            if (!$this->totp->verify($plain_secret, $totp)) {
                $this->login_history_model->record('2fa_failure', (int) $cred->user_id, $email, ['source' => 'api']);
                $this->fail('Invalid TOTP code.', 401);
                return;
            }
            $this->login_history_model->record('2fa_success', (int) $cred->user_id, $email, ['source' => 'api']);
        }

        // Issue tokens.
        $this->api_rate_limiter->reset('login_api:' . $email . ':' . $ip);
        $this->authentication_model->update_last_login($cred->id);
        $this->login_history_model->record('success', (int) $cred->user_id, $email, ['source' => 'api']);

        $this->_issue_tokens((int) $cred->user_id, (int) $cred->id, (int) $cred->role, 'read:self write:self');
    }

    public function refresh()
    {
        $body = $this->_json_body();
        $refresh = trim((string) ($body['refresh_token'] ?? ''));
        if ($refresh === '') {
            $this->fail('refresh_token is required', 422);
            return;
        }
        $rotated = $this->api_refresh_token_model->rotate($refresh);
        if (!$rotated) {
            $this->fail('Invalid or expired refresh token. Re-authenticate.', 401);
            return;
        }
        // Resolve the role at refresh time so role changes propagate instantly.
        $cred = $this->authentication_model->get_login_by_id($rotated['credential_id']);
        if (!$cred || $cred->status !== 'Active') {
            $this->fail('Credential disabled.', 401);
            return;
        }

        $this->_issue_tokens(
            (int) $rotated['user_id'],
            (int) $rotated['credential_id'],
            (int) $cred->role,
            $rotated['scope'],
            $rotated['parent_id']
        );
    }

    public function logout()
    {
        // Forces auth: subclass override required, do it manually.
        $this->require_auth = true;
        // Re-invoke parent constructor's auth step by calling decode manually.
        $token = Jwt::extract_bearer($this->input->get_request_header('Authorization', true) ?: ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        if (!$token) {
            $this->fail('Missing bearer token', 401);
            return;
        }
        try {
            $claims = $this->jwt->decode($token);
        } catch (Throwable $e) {
            $this->fail('Invalid token', 401);
            return;
        }
        $jti_hash = hash('sha256', (string) ($claims['jti'] ?? ''));
        // Revoke this access token's jti.
        $this->db->replace('api_revoked_jti', [
            'jti_hash'   => $jti_hash,
            'revoked_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', (int) ($claims['exp'] ?? time() + 900)),
        ]);
        // Revoke all refresh tokens for this credential.
        $this->api_refresh_token_model->revoke_by_credential((int) ($claims['cid'] ?? 0));
        $this->login_history_model->record('logout', (int) ($claims['sub'] ?? 0), (string) ($claims['email'] ?? ''));
        $this->ok(['revoked' => true]);
    }

    public function me()
    {
        $this->require_auth = true;
        $token = Jwt::extract_bearer($this->input->get_request_header('Authorization', true) ?: ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        if (!$token) {
            $this->fail('Missing bearer token', 401);
            return;
        }
        try {
            $claims = $this->jwt->decode($token);
        } catch (Throwable $e) {
            $this->fail('Invalid token', 401);
            return;
        }
        $this->ok([
            'id'    => (int) $claims['sub'],
            'role'  => (int) ($claims['role'] ?? 0),
            'scope' => (string) ($claims['scope'] ?? ''),
            'exp'   => (int) $claims['exp'],
        ]);
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    private function _issue_tokens(int $user_id, int $credential_id, int $role, string $scope, ?int $parent_refresh_id = null): void
    {
        $access_ttl  = (int) (getenv('JWT_ACCESS_TTL')  ?: 900);     // 15 min
        $refresh_ttl = (int) (getenv('JWT_REFRESH_TTL') ?: 1209600); // 14 days

        $access = $this->jwt->encode([
            'sub'   => $user_id,
            'cid'   => $credential_id,
            'role'  => $role,
            'scope' => $scope,
        ], $access_ttl, 'access');

        $refresh = $this->api_refresh_token_model->issue(
            $user_id, $credential_id, $scope, $refresh_ttl, $parent_refresh_id
        );

        $this->ok([
            'token_type'    => 'Bearer',
            'access_token'  => $access,
            'expires_in'    => $access_ttl,
            'refresh_token' => $refresh['token'],
            'refresh_expires_at' => $refresh['exp'],
            'scope'         => $scope,
        ]);
    }

    private function _json_body(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        // Fall back to form-encoded for clients that don't send JSON.
        return $this->input->post() ?: [];
    }
}
