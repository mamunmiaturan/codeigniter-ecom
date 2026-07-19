<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Flash Sale
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Flash_sale_model.php
 *
 * Time-boxed flash sales. A sale is a scheduled window (starts_at..ends_at) with
 * a set of products, each carrying a special sale_price for the duration. The
 * storefront reuses Landing_model::shape() so flash-sale cards render exactly
 * like the rest of the catalog, with the effective price forced to sale_price.
 */
class Flash_sale_model extends MY_Model
{
    protected $table = 'flash_sales';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDelete = true;
    protected $allowedFields = [
        'title', 'starts_at', 'ends_at', 'status', 'created_by', 'updated_by',
    ];

    /**
     * Total non-deleted rows (optionally scoped by status) — DataTables recordsTotal.
     */
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
     * Each row carries an `items_count` scalar (correlated subquery).
     */
    public function datatable($search, $start, $length, $order_col, $order_dir, $status = '')
    {
        $apply = function () use ($search, $status) {
            $this->db->from('flash_sales fs')->where('fs.deleted_at', null);
            if ($status !== '' && $status !== null) {
                $this->db->where('fs.status', $status);
            }
            if ($search !== '') {
                $this->db->like('fs.title', $search);
            }
        };
        $apply();
        $filtered = $this->db->count_all_results();
        $apply();
        $this->db->select('fs.*, (SELECT COUNT(*) FROM flash_sale_items fsi WHERE fsi.flash_sale_id = fs.id) AS items_count', false);
        $this->db->order_by($order_col, $order_dir)->limit($length, $start);
        return ['filtered' => $filtered, 'data' => $this->db->get()->result()];
    }

    /**
     * Toggle Active/Inactive; returns the new status string or false.
     */
    public function toggle_status($id)
    {
        $row = $this->find($id);
        if (empty($row)) {
            return false;
        }
        $new = ($row['status'] === 'Active') ? 'Inactive' : 'Active';
        return $this->update($id, ['status' => $new]) ? $new : false;
    }

    /**
     * Items for a sale joined with their product (name + original price), for the
     * admin edit form prefill.
     */
    public function get_items($flash_sale_id)
    {
        return $this->db->select('fsi.*, p.name AS product_name, p.sku AS product_sku, p.price AS product_price')
            ->from('flash_sale_items fsi')
            ->join('products p', 'p.id = fsi.product_id', 'left')
            ->where('fsi.flash_sale_id', (int) $flash_sale_id)
            ->order_by('fsi.id', 'ASC')
            ->get()->result_array();
    }

    /**
     * Replace-all persist of a sale's items: wipe the existing set then insert the
     * submitted product_id[] / sale_price[] pairs (blank/invalid rows and duplicate
     * product ids are skipped to honour the uq_fs_item unique key).
     *
     * @return int number of items persisted
     */
    public function save_items($flash_sale_id, array $product_ids, array $sale_prices)
    {
        $flash_sale_id = (int) $flash_sale_id;
        $this->db->where('flash_sale_id', $flash_sale_id)->delete('flash_sale_items');

        $now  = date('Y-m-d H:i:s');
        $seen = [];
        $rows = [];
        foreach ($product_ids as $i => $pid) {
            $pid   = (int) $pid;
            $price = $sale_prices[$i] ?? '';
            if ($pid <= 0 || $price === '' || !is_numeric($price) || (float) $price < 0) {
                continue;
            }
            if (isset($seen[$pid])) {
                continue; // unique (flash_sale_id, product_id)
            }
            $seen[$pid] = true;
            $rows[] = [
                'flash_sale_id' => $flash_sale_id,
                'product_id'    => $pid,
                'sale_price'    => (float) $price,
                'created_at'    => $now,
            ];
        }
        if (!empty($rows)) {
            $this->db->insert_batch('flash_sale_items', $rows);
        }
        return count($rows);
    }

    /**
     * The single flash sale that is live right now: Active, within its window,
     * most recent first. Returns the row array or null.
     */
    public function active()
    {
        $now = date('Y-m-d H:i:s');
        return $this->db->from('flash_sales')
            ->where('deleted_at', null)
            ->where('status', 'Active')
            ->where('starts_at <=', $now)
            ->where('ends_at >=', $now)
            ->order_by('starts_at', 'DESC')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()->row_array();
    }

    /**
     * Shaped product rows for the currently-active sale, priced at their sale_price.
     * Reuses Landing_model::shape() then forces the effective price to the flash
     * sale price, flags on_sale and recomputes the discount vs. the original price.
     * Returns [] when there is no active sale.
     */
    public function active_items()
    {
        $sale = $this->active();
        if (!$sale) {
            return [];
        }
        $this->load->model('landing_model');

        $rows = $this->db
            ->select('p.*, c.name AS category_name, c.slug AS category_slug, b.name AS brand_name, fsi.sale_price AS fs_sale_price')
            ->from('flash_sale_items fsi')
            ->join('products p', 'p.id = fsi.product_id', 'inner')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->join('brands b', 'b.id = p.brand_id', 'left')
            ->where('fsi.flash_sale_id', (int) $sale['id'])
            ->where('p.deleted_at', null)
            ->where('p.status', 'Active')
            ->order_by('fsi.id', 'ASC')
            ->get()->result_array();

        $items = [];
        foreach ($rows as $r) {
            $sale_price = (float) $r['fs_sale_price'];
            $shaped     = $this->landing_model->shape($r);
            $orig       = (float) $shaped['price'];

            $shaped['effective_price'] = $sale_price;
            $shaped['on_sale']         = true;
            $shaped['discount_pct']    = ($orig > 0 && $sale_price < $orig)
                ? (int) round((1 - $sale_price / $orig) * 100) : 0;
            $items[] = $shaped;
        }
        return $items;
    }
}
