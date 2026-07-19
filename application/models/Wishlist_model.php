<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Wishlist
 * @author   : Mamun Mia Turan
 * @filename : Wishlist_model.php
 *
 * Customer wishlists (requires a logged-in customer / user_id). Uses raw query
 * builder so wishlist toggles don't spam the activity log.
 */
class Wishlist_model extends MY_Model
{
    protected $table = 'wishlists';

    public function exists($user_id, $product_id)
    {
        return $this->db
            ->where('user_id', (int) $user_id)
            ->where('product_id', (int) $product_id)
            ->count_all_results('wishlists') > 0;
    }

    public function add($user_id, $product_id)
    {
        if ($this->exists($user_id, $product_id)) {
            return true;
        }
        return $this->db->insert('wishlists', [
            'user_id'    => (int) $user_id,
            'product_id' => (int) $product_id,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function remove($user_id, $product_id)
    {
        $this->db->where('user_id', (int) $user_id)->where('product_id', (int) $product_id)->delete('wishlists');
        return $this->db->affected_rows() > 0;
    }

    /**
     * Toggle a product in the wishlist. Returns 'added' or 'removed'.
     */
    public function toggle($user_id, $product_id)
    {
        if ($this->exists($user_id, $product_id)) {
            $this->remove($user_id, $product_id);
            return 'removed';
        }
        $this->add($user_id, $product_id);
        return 'added';
    }

    public function count_for($user_id)
    {
        return (int) $this->db->where('user_id', (int) $user_id)->count_all_results('wishlists');
    }

    /**
     * Product ids on the user's wishlist (for marking hearts across the catalog).
     */
    public function product_ids($user_id)
    {
        $rows = $this->db->select('product_id')->where('user_id', (int) $user_id)->get('wishlists')->result_array();
        return array_map(function ($r) { return (int) $r['product_id']; }, $rows);
    }

    /**
     * Wishlist with product details (Active products only), newest first.
     */
    public function list_detailed($user_id)
    {
        $this->db->select('w.id AS wishlist_id, w.created_at,
            p.id, p.name, p.slug, p.sku, p.price, p.special_price, p.currency, p.thumbnail, p.stock_status, p.status');
        $this->db->from('wishlists w');
        $this->db->join('products p', 'p.id = w.product_id');
        $this->db->where('w.user_id', (int) $user_id);
        $this->db->where('p.deleted_at', null);
        $this->db->order_by('w.id', 'DESC');
        return $this->db->get()->result_array();
    }

    public function clear($user_id)
    {
        $this->db->where('user_id', (int) $user_id)->delete('wishlists');
    }
}
