<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Reviews
 * @author   : Mamun Mia Turan
 * @filename : Review_model.php
 *
 * Product reviews & ratings. Admin moderation reads/writes via MY_Model; the
 * public rating summary and storefront listing use raw query builder.
 */
class Review_model extends MY_Model
{
    protected $table = 'product_reviews';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDelete = true;
    protected $allowedFields = [
        'product_id', 'user_id', 'order_id', 'author_name', 'author_email',
        'rating', 'title', 'comment', 'is_verified_purchase', 'status', 'admin_reply',
    ];

    // ---- Admin moderation ----

    public function count_all($status = '')
    {
        $this->db->where('deleted_at', null);
        if ($status !== '' && $status !== null) {
            $this->db->where('status', $status);
        }
        return $this->db->count_all_results($this->table);
    }

    /**
     * Server-side DataTables payload. Returns ['filtered' => int, 'data' => object[]].
     */
    public function datatable($search, $start, $length, $order_col, $order_dir, $status = '')
    {
        $apply = function () use ($search, $status) {
            $this->db->from('product_reviews r');
            $this->db->join('products p', 'p.id = r.product_id', 'left');
            $this->db->where('r.deleted_at', null);
            if ($status !== '' && $status !== null) {
                $this->db->where('r.status', $status);
            }
            if ($search !== '') {
                $this->db->group_start();
                $this->db->like('r.author_name', $search);
                $this->db->or_like('r.title', $search);
                $this->db->or_like('r.comment', $search);
                $this->db->or_like('p.name', $search);
                $this->db->group_end();
            }
        };

        $apply();
        $filtered = $this->db->count_all_results();

        $this->db->select('r.*, p.name AS product_name, p.slug AS product_slug');
        $apply();
        $this->db->order_by($order_col, $order_dir);
        $this->db->limit($length, $start);
        $data = $this->db->get()->result();

        return ['filtered' => $filtered, 'data' => $data];
    }

    public function set_status($id, $status)
    {
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            return false;
        }
        return $this->update($id, ['status' => $status]);
    }

    public function reply($id, $text)
    {
        return $this->update($id, ['admin_reply' => ($text === '' ? null : $text)]);
    }

    // ---- Public storefront / API ----

    /**
     * Aggregate rating for a product: average, count and per-star breakdown
     * (approved reviews only).
     */
    public function rating_summary($product_id)
    {
        $row = $this->db
            ->select('COUNT(*) AS cnt, AVG(rating) AS avg_rating', false)
            ->where('product_id', (int) $product_id)
            ->where('status', 'approved')
            ->where('deleted_at', null)
            ->get('product_reviews')
            ->row_array();

        $breakdown = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        $rows = $this->db
            ->select('rating, COUNT(*) AS c', false)
            ->where('product_id', (int) $product_id)
            ->where('status', 'approved')
            ->where('deleted_at', null)
            ->group_by('rating')
            ->get('product_reviews')
            ->result_array();
        foreach ($rows as $r) {
            $star = (int) $r['rating'];
            if (isset($breakdown[$star])) {
                $breakdown[$star] = (int) $r['c'];
            }
        }

        $count = (int) ($row['cnt'] ?? 0);
        $avg   = $count > 0 ? round((float) $row['avg_rating'], 2) : 0.0;
        return ['count' => $count, 'average' => $avg, 'breakdown' => $breakdown];
    }

    public function approved_for_product($product_id, $limit = 20, $offset = 0)
    {
        $limit  = min(100, max(1, (int) $limit));
        $offset = max(0, (int) $offset);
        return $this->db
            ->where('product_id', (int) $product_id)
            ->where('status', 'approved')
            ->where('deleted_at', null)
            ->order_by('is_verified_purchase', 'DESC')
            ->order_by('id', 'DESC')
            ->limit($limit, $offset)
            ->get('product_reviews')
            ->result_array();
    }

    public function create($data)
    {
        $clean = [
            'product_id'           => (int) $data['product_id'],
            'user_id'              => !empty($data['user_id']) ? (int) $data['user_id'] : null,
            'order_id'             => !empty($data['order_id']) ? (int) $data['order_id'] : null,
            'author_name'          => trim((string) $data['author_name']),
            'author_email'         => !empty($data['author_email']) ? trim((string) $data['author_email']) : null,
            'rating'               => max(1, min(5, (int) $data['rating'])),
            'title'                => !empty($data['title']) ? trim((string) $data['title']) : null,
            'comment'              => !empty($data['comment']) ? trim((string) $data['comment']) : null,
            'is_verified_purchase' => !empty($data['is_verified_purchase']) ? 1 : 0,
            'status'               => 'pending',
        ];
        return $this->insert($clean);
    }

    /**
     * Has this user already reviewed this product (any status, not deleted)?
     */
    public function user_already_reviewed($user_id, $product_id)
    {
        return $this->db
            ->where('user_id', (int) $user_id)
            ->where('product_id', (int) $product_id)
            ->where('deleted_at', null)
            ->count_all_results('product_reviews') > 0;
    }

    /**
     * Did this user buy this product (used to flag verified purchases)? Matches a
     * non-cancelled order containing the product for the user.
     */
    public function user_purchased($user_id, $product_id)
    {
        if (!$this->db->table_exists('order_items') || !$this->db->table_exists('orders')) {
            return false;
        }
        return $this->db
            ->from('order_items oi')
            ->join('orders o', 'o.id = oi.order_id')
            ->where('o.user_id', (int) $user_id)
            ->where('oi.product_id', (int) $product_id)
            ->where('o.status !=', 'cancelled')
            ->count_all_results() > 0;
    }
}
