<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Jwt is referenced statically by Api_Controller (extract_bearer, etc.), so it
// must be class-available before the constructor runs. The CI loader's
// $this->load->library('jwt') is also called below to register the instance
// property — both are needed.
require_once APPPATH . 'libraries/Jwt.php';

/**
 * Base controller for JSON REST APIs.
 *
 * Responsibilities:
 *  - Force Content-Type: application/json on every response.
 *  - CORS: env-driven origin allowlist (API_CORS_ORIGINS, comma-separated).
 *    Wildcard "*" is supported but rejects credentialed requests per spec.
 *  - Bearer authentication: validates HS256 JWT, rejects revoked jti, exposes
 *    $this->auth_user = ['id', 'credential_id', 'role', 'scope', 'jti'].
 *  - Per-token rate limit: 120 req / 60s default (overridable via env).
 *
 * Subclasses opt out of auth by setting protected $require_auth = false.
 *
 * Concrete subclasses live under application/controllers/api/.
 */
class Api_Controller extends CI_Controller
{
    /** @var bool Override in subclasses (login / public health) */
    protected $require_auth = true;

    /** @var array|null Populated after auth: id, credential_id, role, scope, jti */
    protected $auth_user = null;

    public function __construct()
    {
        parent::__construct();
        $this->output->set_content_type('application/json');
        $this->_apply_cors();

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $this->output->set_status_header(204)->_display();
            exit;
        }

        $this->_apply_rate_limit();

        if ($this->require_auth) {
            $this->_authenticate();
        }
    }

    // ------------------------------------------------------------------
    // Helpers (response shaping)
    // ------------------------------------------------------------------

    protected function ok($data = [], int $status = 200)
    {
        $this->_emit($status, ['status' => 'success', 'data' => $data]);
    }

    protected function fail(string $message, int $status = 400, array $extra = [])
    {
        $this->_emit($status, array_merge([
            'status'  => 'error',
            'message' => $message,
            'code'    => $status,
        ], $extra));
    }

    protected function require_scope(string $scope): void
    {
        $granted = explode(' ', (string) ($this->auth_user['scope'] ?? ''));
        if (!in_array($scope, $granted, true) && !in_array('*', $granted, true)) {
            $this->fail("Insufficient scope: requires '{$scope}'", 403);
            exit;
        }
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    private function _apply_cors(): void
    {
        $raw = getenv('API_CORS_ORIGINS') ?: '';
        $origins = array_filter(array_map('trim', explode(',', $raw)));
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if ($origins === ['*']) {
            header('Access-Control-Allow-Origin: *');
        } elseif ($origin !== '' && in_array($origin, $origins, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
            header('Access-Control-Allow-Credentials: true');
        }
        // else: no CORS header → browsers block cross-origin. Same-origin still works.

        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Request-Id');
        header('Access-Control-Max-Age: 3600');
    }

    private function _apply_rate_limit(): void
    {
        $limit  = (int) (getenv('API_RATE_LIMIT')  ?: 120);
        $window = (int) (getenv('API_RATE_WINDOW') ?: 60);
        if ($limit <= 0) {
            return;
        }
        $this->load->library('api_rate_limiter');
        $bearer = Jwt::extract_bearer($this->_auth_header());
        $key = 'api:' . ($bearer ? hash('sha256', $bearer) : $this->input->ip_address());
        $res = $this->api_rate_limiter->check($key, $limit, $window);
        header('X-RateLimit-Limit: ' . $limit);
        header('X-RateLimit-Remaining: ' . max(0, $res['remaining'] ?? 0));
        if (!$res['allowed']) {
            header('Retry-After: ' . max(1, (int) ($res['reset_in'] ?? $window)));
            $this->_emit(429, [
                'status'  => 'error',
                'message' => 'Rate limit exceeded',
                'code'    => 429,
            ]);
            $this->output->_display();
            exit;
        }
    }

    private function _authenticate(): void
    {
        $token = Jwt::extract_bearer($this->_auth_header());
        if (!$token) {
            $this->_emit(401, ['status' => 'error', 'message' => 'Missing bearer token', 'code' => 401]);
            $this->output->_display();
            exit;
        }
        try {
            $this->load->library('jwt');
            $claims = $this->jwt->decode($token);
        } catch (Throwable $e) {
            $this->_emit(401, ['status' => 'error', 'message' => 'Invalid token: ' . $e->getMessage(), 'code' => 401]);
            $this->output->_display();
            exit;
        }
        if (($claims['type'] ?? '') !== 'access') {
            $this->_emit(401, ['status' => 'error', 'message' => 'Wrong token type', 'code' => 401]);
            $this->output->_display();
            exit;
        }

        // Check JTI revocation.
        $this->load->database();
        if ($this->db->table_exists('api_revoked_jti')) {
            $jti_hash = hash('sha256', (string) ($claims['jti'] ?? ''));
            $hit = $this->db
                ->where('jti_hash', $jti_hash)
                ->count_all_results('api_revoked_jti');
            if ($hit > 0) {
                $this->_emit(401, ['status' => 'error', 'message' => 'Token revoked', 'code' => 401]);
                $this->output->_display();
                exit;
            }
        }

        $this->auth_user = [
            'id'            => (int) ($claims['sub'] ?? 0),
            'credential_id' => (int) ($claims['cid'] ?? 0),
            'role'          => (int) ($claims['role'] ?? 0),
            'scope'         => (string) ($claims['scope'] ?? ''),
            'jti'           => (string) ($claims['jti'] ?? ''),
        ];
    }

    private function _auth_header(): ?string
    {
        $h = $this->input->get_request_header('Authorization', true);
        if ($h) return $h;
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) return $_SERVER['HTTP_AUTHORIZATION'];
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        return null;
    }

    private function _emit(int $status, array $payload): void
    {
        $this->output
            ->set_status_header($status)
            ->set_output(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
