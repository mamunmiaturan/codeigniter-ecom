<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Admin — Wishlist demand report. Aggregates the storefront `wishlists` table
 * into a "most wishlisted products" view (a demand signal for restocking /
 * promotions). Permission module prefix: `product`.
 */
class Wishlist extends Admin_Controller
{
    public function index()
    {
        // Gated behind the `user` (customer management) permission — the wishlist
        // exposes customer PII (name/phone/email), so it follows customer-data
        // access, not catalog/product access.
        if (!get_permission('user', 'is_view')) {
            access_denied();
        }

        // Individual wishlist entries with the customer (name/phone/email) and the
        // product they saved — so admins can reach out about wanted items.
        $rows = $this->db
            ->select('w.id, w.created_at,
                      u.id AS user_id, u.name AS customer_name, u.mobile_no AS phone,
                      lc.email AS email,
                      p.id AS product_id, p.name AS product_name, p.slug, p.thumbnail, p.price, p.stock_quantity, p.stock_status', false)
            ->from('wishlists w')
            ->join('users u', 'u.id = w.user_id', 'inner')
            ->join('login_credential lc', 'lc.user_id = u.id', 'inner')
            ->join('products p', 'p.id = w.product_id', 'inner')
            ->order_by('w.created_at', 'DESC')
            ->order_by('w.id', 'DESC')
            ->get()
            ->result();

        $this->data['items']     = $rows;
        $this->data['title']     = translate('wishlist') ?: 'Wishlist';
        $this->data['sub_page']  = 'wishlist/index';
        $this->data['main_menu'] = 'wishlist';
        $this->load->view('layout/index', $this->data);
    }
}
