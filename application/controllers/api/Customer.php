<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Api_Controller.php';

/**
 * Storefront customer accounts API.
 *
 *  POST /api/v1/customer/register  {name, email, phone, password} -> tokens
 *  GET  /api/v1/customer/profile   (Bearer)                       -> profile
 *  POST /api/v1/customer/profile   (Bearer) {name, phone, ...}    -> updated profile
 *
 * Login/refresh/logout/me are served by the existing /api/v1/auth/* endpoints
 * (a customer is an Active role-6 login_credential).
 */
class Customer extends Api_Controller
{
    protected $require_auth = false;
    private $_body_cache = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['customer_model', 'api_refresh_token_model']);
        $this->load->library(['jwt', 'app_lib']);
    }

    public function register()
    {
        $b = $this->_json_body();
        $name     = trim((string) ($b['name'] ?? ''));
        $email    = trim((string) ($b['email'] ?? ''));
        $phone    = trim((string) ($b['phone'] ?? ''));
        $password = (string) ($b['password'] ?? '');

        if ($name === '' || $email === '' || $password === '') {
            $this->fail('name, email and password are required', 422);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->fail('Invalid email format', 422);
            return;
        }
        $valid = $this->app_lib->validate_password($password);
        if ($valid !== true) {
            $this->fail(is_string($valid) ? $valid : 'Password does not meet complexity requirements.', 422);
            return;
        }
        if ($this->customer_model->email_exists($email)) {
            $this->fail('Email is already registered', 409);
            return;
        }

        $res = $this->customer_model->register([
            'name'          => $name,
            'email'         => $email,
            'phone'         => $phone,
            'password_hash' => $this->app_lib->pass_hashed($password),
        ]);
        if (!$res) {
            $this->fail('Registration failed', 500);
            return;
        }

        $role   = defined('ROLE_CUSTOMER_ID') ? ROLE_CUSTOMER_ID : 6;
        $tokens = $this->_issue_tokens($res['user_id'], $res['credential_id'], $role, 'read:self write:self');

        $this->ok([
            'customer' => ['id' => $res['user_id'], 'name' => $name, 'email' => $email, 'phone' => $phone],
            'auth'     => $tokens,
        ], 201);
    }

    public function profile()
    {
        $claims = $this->_auth();
        if (!$claims) {
            return;
        }
        $p = $this->customer_model->get_profile((int) $claims['sub']);
        if (!$p) {
            $this->fail('Profile not found', 404);
            return;
        }
        $this->ok($this->_shape_profile($p));
    }

    public function update_profile()
    {
        $claims = $this->_auth();
        if (!$claims) {
            return;
        }
        $b = $this->_json_body();
        $update = [];
        foreach (['name', 'mobile_no', 'gender', 'dob', 'address'] as $f) {
            if (array_key_exists($f, $b)) {
                $update[$f] = $b[$f];
            }
        }
        if (array_key_exists('phone', $b)) {
            $update['mobile_no'] = $b['phone'];
        }
        $this->customer_model->update_profile((int) $claims['sub'], $update);
        $this->ok($this->_shape_profile($this->customer_model->get_profile((int) $claims['sub'])));
    }

    /**
     * GET /api/v1/customer/downloads (Bearer) -> the customer's digital library.
     * Each item carries a tokenised URL; the mobile client fetches it with the
     * same Authorization header to stream the file.
     */
    public function downloads()
    {
        $claims = $this->_auth();
        if (!$claims) {
            return;
        }
        $this->load->model('download_model');
        $rows = $this->download_model->list_for_user((int) $claims['sub']);
        $now  = date('Y-m-d H:i:s');
        $items = array_map(function ($d) use ($now) {
            $limit     = $d['download_limit'] !== null ? (int) $d['download_limit'] : null;
            $used      = (int) $d['downloads_used'];
            $expired   = $d['expires_at'] !== null && $d['expires_at'] < $now;
            $exhausted = $limit !== null && $used >= $limit;
            return [
                'id'             => (int) $d['id'],
                'name'           => $d['name'],
                'order_number'   => $d['order_number'] ?? null,
                'product_slug'   => $d['product_slug'] ?? null,
                'downloads_used' => $used,
                'download_limit' => $limit,
                'expires_at'     => $d['expires_at'],
                'available'      => !$expired && !$exhausted,
                'url'            => base_url('download/file/' . rawurlencode($d['token'])),
            ];
        }, $rows);
        $this->ok(['items' => $items]);
    }

    // ------------------------------------------------------------------

    private function _shape_profile($p)
    {
        return [
            'id'         => (int) $p['id'],
            'code'       => $p['code'],
            'name'       => $p['name'],
            'email'      => $p['email'],
            'phone'      => $p['mobile_no'],
            'gender'     => $p['gender'],
            'dob'        => $p['dob'],
            'address'    => $p['address'],
            'status'     => $p['status'],
            'last_login' => $p['last_login'],
        ];
    }

    private function _auth()
    {
        $token = Jwt::extract_bearer($this->input->get_request_header('Authorization', true) ?: ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
        if (!$token) {
            $this->fail('Missing bearer token', 401);
            return null;
        }
        try {
            $claims = $this->jwt->decode($token);
        } catch (Throwable $e) {
            $this->fail('Invalid token: ' . $e->getMessage(), 401);
            return null;
        }
        if (($claims['type'] ?? '') !== 'access') {
            $this->fail('Wrong token type', 401);
            return null;
        }
        return $claims;
    }

    private function _issue_tokens($user_id, $credential_id, $role, $scope)
    {
        $access_ttl  = (int) (getenv('JWT_ACCESS_TTL')  ?: 900);
        $refresh_ttl = (int) (getenv('JWT_REFRESH_TTL') ?: 1209600);

        $access  = $this->jwt->encode([
            'sub'   => $user_id,
            'cid'   => $credential_id,
            'role'  => $role,
            'scope' => $scope,
        ], $access_ttl, 'access');

        $refresh = $this->api_refresh_token_model->issue($user_id, $credential_id, $scope, $refresh_ttl);

        return [
            'token_type'         => 'Bearer',
            'access_token'       => $access,
            'expires_in'         => $access_ttl,
            'refresh_token'      => $refresh['token'],
            'refresh_expires_at' => $refresh['exp'],
            'scope'              => $scope,
        ];
    }

    private function _json_body()
    {
        if ($this->_body_cache !== null) {
            return $this->_body_cache;
        }
        $raw = file_get_contents('php://input') ?: '';
        $decoded = json_decode($raw, true);
        $this->_body_cache = is_array($decoded) ? $decoded : ($this->input->post() ?: []);
        return $this->_body_cache;
    }
}
