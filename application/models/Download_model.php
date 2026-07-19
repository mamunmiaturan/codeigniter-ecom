<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog (Downloads)
 * @author   : Mamun Mia Turan
 * @filename : Download_model.php
 *
 * product_downloads = files an admin attaches to a downloadable product.
 * customer_downloads = the secure, per-order links granted to a buyer once the
 * order is paid. Files live in uploads/downloads/ and are streamed by token via
 * the Download controller (never linked directly).
 */
class Download_model extends MY_Model
{
    protected $table = 'product_downloads';

    // ================= Admin: product downloads =================

    public function get_for_product($product_id)
    {
        return $this->db->where('product_id', (int) $product_id)->order_by('is_sample', 'ASC')->order_by('sort_order', 'ASC')->order_by('id', 'ASC')
            ->get('product_downloads')->result_array();
    }

    public function samples($product_id)
    {
        return $this->db->where('product_id', (int) $product_id)->where('is_sample', 1)->order_by('sort_order', 'ASC')
            ->get('product_downloads')->result_array();
    }

    public function has_downloads($product_id)
    {
        return $this->db->where('product_id', (int) $product_id)->where('is_sample', 0)->count_all_results('product_downloads') > 0;
    }

    public function add_download($product_id, $name, $file_path, $is_sample = 0, $download_limit = null, $sort_order = 0)
    {
        $this->db->insert('product_downloads', [
            'product_id'     => (int) $product_id,
            'name'           => $name,
            'file_path'      => $file_path,
            'is_sample'      => $is_sample ? 1 : 0,
            'download_limit' => $download_limit !== null && $download_limit !== '' ? (int) $download_limit : null,
            'sort_order'     => (int) $sort_order,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insert_id();
    }

    public function get_download($id, $product_id = null)
    {
        $this->db->where('id', (int) $id);
        if ($product_id !== null) {
            $this->db->where('product_id', (int) $product_id);
        }
        return $this->db->get('product_downloads')->row_array();
    }

    /** Delete a product download; returns its file path so the caller can unlink. */
    public function delete_download($id, $product_id)
    {
        $row = $this->get_download($id, $product_id);
        if (!$row) {
            return null;
        }
        $this->db->where('id', (int) $id)->where('product_id', (int) $product_id)->delete('product_downloads');
        return $row['file_path'];
    }

    // ================= Grant on paid order =================

    /**
     * Grant download links for every downloadable item in a paid order.
     * Idempotent — skips items already granted. Returns count granted.
     */
    public function grant_for_order($order_id)
    {
        $order = $this->db->select('id, user_id')->where('id', (int) $order_id)->get('orders')->row_array();
        if (!$order) {
            return 0;
        }
        $items = $this->db->where('order_id', (int) $order_id)->get('order_items')->result_array();
        $granted = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($items as $it) {
            if (empty($it['product_id'])) {
                continue;
            }
            // Already granted for this order line?
            if ((int) $this->db->where('order_id', (int) $order_id)->where('order_item_id', (int) $it['id'])->count_all_results('customer_downloads') > 0) {
                continue;
            }
            $files = $this->db->where('product_id', (int) $it['product_id'])->where('is_sample', 0)->get('product_downloads')->result_array();
            foreach ($files as $f) {
                $this->db->insert('customer_downloads', [
                    'user_id'             => $order['user_id'] ? (int) $order['user_id'] : null,
                    'order_id'            => (int) $order_id,
                    'order_item_id'       => (int) $it['id'],
                    'product_id'          => (int) $it['product_id'],
                    'product_download_id' => (int) $f['id'],
                    'name'                => $f['name'],
                    'file_path'           => $f['file_path'],
                    'token'               => bin2hex(random_bytes(24)),
                    'download_limit'      => $f['download_limit'] !== null ? (int) $f['download_limit'] : null,
                    'downloads_used'      => 0,
                    'expires_at'          => null,
                    'created_at'          => $now,
                ]);
                $granted++;
            }
        }
        return $granted;
    }

    // ================= Customer: serve =================

    public function list_for_user($user_id)
    {
        $this->db->select('cd.*, o.order_number, p.slug AS product_slug');
        $this->db->from('customer_downloads cd');
        $this->db->join('orders o', 'o.id = cd.order_id', 'left');
        $this->db->join('products p', 'p.id = cd.product_id', 'left');
        $this->db->where('cd.user_id', (int) $user_id);
        $this->db->order_by('cd.id', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_by_token($token)
    {
        return $this->db->where('token', $token)->get('customer_downloads')->row_array();
    }

    /** True when a customer_downloads row can still be downloaded. */
    public function is_available($row)
    {
        if (!$row) {
            return false;
        }
        if ($row['expires_at'] !== null && $row['expires_at'] < date('Y-m-d H:i:s')) {
            return false;
        }
        if ($row['download_limit'] !== null && (int) $row['downloads_used'] >= (int) $row['download_limit']) {
            return false;
        }
        return true;
    }

    public function record_download($id)
    {
        $this->db->set('downloads_used', 'downloads_used + 1', false)->where('id', (int) $id)->update('customer_downloads');
    }
}
