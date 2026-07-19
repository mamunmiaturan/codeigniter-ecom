<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Api_Controller.php';

/**
 * Product reviews API.
 *
 *  GET  /api/v1/products/{slug}/reviews            -> approved reviews + summary
 *  POST /api/v1/products/{slug}/reviews  (Bearer)  -> submit a review (pending)
 *      body: {rating(1-5), title?, comment?}
 *
 * Submitted reviews start as `pending` and appear on the storefront only after
 * an admin approves them. A review is flagged verified when the customer has an
 * order containing the product.
 */
class Review extends Api_Controller
{
    protected $require_auth = false;
    private $_body_cache = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['product_model', 'review_model', 'customer_model']);
        $this->load->library('jwt');
    }

    public function index($slug = '')
    {
        $product = $this->product_model->get_active_by_slug(rawurldecode($slug));
        if (!$product) {
            $this->fail('Product not found', 404);
            return;
        }
        $page = max(1, (int) ($this->input->get('page') ?: 1));
        $per  = min(50, max(1, (int) ($this->input->get('per_page') ?: 10)));

        $reviews = $this->review_model->approved_for_product($product['id'], $per, ($page - 1) * $per);
        $summary = $this->review_model->rating_summary($product['id']);

        $this->ok([
            'summary'    => $summary,
            'reviews'    => array_map([$this, '_shape'], $reviews),
            'pagination' => [
                'page'     => $page,
                'per_page' => $per,
                'total'    => $summary['count'],
            ],
        ]);
    }

    public function create($slug = '')
    {
        $claims = $this->_auth();
        if (!$claims) {
            return;
        }
        $product = $this->product_model->get_active_by_slug(rawurldecode($slug));
        if (!$product) {
            $this->fail('Product not found', 404);
            return;
        }
        $user_id = (int) $claims['sub'];

        if ($this->review_model->user_already_reviewed($user_id, $product['id'])) {
            $this->fail('You have already reviewed this product', 409);
            return;
        }

        $b      = $this->_json_body();
        $rating = (int) ($b['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) {
            $this->fail('A rating between 1 and 5 is required', 422);
            return;
        }

        $profile  = $this->customer_model->get_profile($user_id);
        $verified = $this->review_model->user_purchased($user_id, $product['id']);

        $id = $this->review_model->create([
            'product_id'           => $product['id'],
            'user_id'              => $user_id,
            'author_name'          => $profile['name'] ?? 'Customer',
            'author_email'         => $profile['email'] ?? null,
            'rating'               => $rating,
            'title'                => $b['title'] ?? null,
            'comment'              => $b['comment'] ?? null,
            'is_verified_purchase' => $verified ? 1 : 0,
        ]);

        if (!$id) {
            $this->fail('Could not submit review', 500);
            return;
        }
        $this->ok([
            'id'      => (int) $id,
            'status'  => 'pending',
            'message' => 'Review submitted and awaiting moderation',
        ], 201);
    }

    // ------------------------------------------------------------------

    private function _shape($r)
    {
        return [
            'id'                => (int) $r['id'],
            'author'            => $r['author_name'],
            'rating'            => (int) $r['rating'],
            'title'             => $r['title'],
            'comment'           => $r['comment'],
            'verified_purchase' => (bool) $r['is_verified_purchase'],
            'admin_reply'       => $r['admin_reply'],
            'created_at'        => $r['created_at'],
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
            $this->fail('Invalid token', 401);
            return null;
        }
        if (($claims['type'] ?? '') !== 'access') {
            $this->fail('Wrong token type', 401);
            return null;
        }
        return $claims;
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
