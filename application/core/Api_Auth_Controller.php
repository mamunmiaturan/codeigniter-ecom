<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Api_Controller.php';

/**
 * Base for resource APIs (bookings, wallet, etc.).
 * Accepts Bearer JWT (access) from /api/v1/auth/login or legacy api_tokens.
 */
class Api_Auth_Controller extends Api_Controller
{
    protected ?array $api_user = null;

    public function __construct()
    {
        $this->require_auth = false;
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
    }

    protected function json_body(): array
    {
        $raw = $this->input->raw_input_stream;
        if (!$raw) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function success(string $message, array $data = [], int $status = 200): void
    {
        $this->_emit($status, [
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ]);
    }

    protected function error(string $message, int $status = 400): void
    {
        $this->_emit($status, [
            'status'  => 'error',
            'message' => $message,
            'code'    => $status,
        ]);
    }

    protected function require_auth(): void
    {
        $header = $this->input->get_request_header('Authorization', true)
            ?: ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if (!$header || !preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
            $this->error('Unauthorized', 401);
            exit;
        }

        $bearer = $m[1];

        if ($this->_auth_via_jwt($bearer)) {
            return;
        }

        $this->load->model('api_token_model');
        $resolved = $this->api_token_model->resolve($bearer);
        if (!$resolved) {
            $this->error('Invalid or expired token', 401);
            exit;
        }

        $profile = $resolved['profile'];
        $this->api_user = $profile;
        $this->_bind_session($profile, (int) $resolved['token_row']['credential_id']);
    }

    protected function apply_rate_limit(string $key, int $max, int $window): void
    {
        $this->load->library('api_rate_limiter');
        $result = $this->api_rate_limiter->check($key, $max, $window);
        if (!$result['allowed']) {
            $this->error('Too many requests', 429);
            exit;
        }
    }

    protected function require_role(int ...$role_ids): void
    {
        if (!in_array((int) ($this->api_user['role'] ?? 0), $role_ids, true)) {
            $this->error('Forbidden', 403);
            exit;
        }
    }

    private function _auth_via_jwt(string $bearer): bool
    {
        try {
            $this->load->library('jwt');
            $claims = $this->jwt->decode($bearer);
        } catch (Throwable $e) {
            return false;
        }

        if (($claims['type'] ?? '') !== 'access') {
            return false;
        }

        if ($this->db->table_exists('api_revoked_jti')) {
            $jti_hash = hash('sha256', (string) ($claims['jti'] ?? ''));
            if ($this->db->where('jti_hash', $jti_hash)->count_all_results('api_revoked_jti') > 0) {
                return false;
            }
        }

        $user_id = (int) ($claims['sub'] ?? 0);
        $credential_id = (int) ($claims['cid'] ?? 0);
        if ($user_id <= 0) {
            return false;
        }

        $profile = $this->db->select('u.id, u.name, u.mobile_no, u.branch_id, u.photo, lc.email, lc.role')
            ->from('users u')
            ->join('login_credential lc', 'lc.user_id = u.id AND lc.id = ' . $credential_id, 'inner')
            ->where('u.id', $user_id)
            ->where('u.deleted_at IS NULL', null, false)
            ->get()
            ->row_array();

        if (!$profile) {
            return false;
        }

        $this->api_user = $profile;
        $this->_bind_session($profile, $credential_id);
        return true;
    }

    private function _bind_session(array $profile, int $credential_id): void
    {
        $this->session->set_userdata([
            'name'             => $profile['name'],
            'logger_photo'     => $profile['photo'] ?? null,
            'loggedin_id'      => $credential_id,
            'loggedin_role_id' => (int) $profile['role'],
            'loggedin_userid'  => (int) $profile['id'],
            'loggedin'         => true,
        ]);
    }

    private function _emit(int $status, array $payload): void
    {
        $this->output
            ->set_status_header($status)
            ->set_output(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
