<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog (Multi-Source Inventory)
 * @author   : Mamun Mia Turan
 * @filename : Inventory_model.php
 *
 * Inventory sources (warehouses) + per-source product stock. product_inventories
 * is the source of truth; products.stock_quantity is a cached rollup = SUM of the
 * active sources' qty (recompute() keeps it in sync). Orders allocate across
 * sources by priority (allocate()) and refresh the rollup. Single-channel: MSI
 * covers base products (variant_id = 0); variants keep their own single stock.
 */
class Inventory_model extends MY_Model
{
    protected $table = 'inventory_sources';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDelete = true;
    protected $allowedFields = [
        'code', 'name', 'priority', 'is_default', 'contact_name', 'contact_email', 'contact_number',
        'country', 'state', 'city', 'street', 'postcode', 'status', 'created_by', 'updated_by',
    ];

    // ================= Sources =================

    public function active_sources()
    {
        return $this->db->where('deleted_at', null)->where('status', 'Active')
            ->order_by('priority', 'ASC')->order_by('id', 'ASC')
            ->get('inventory_sources')->result_array();
    }

    public function default_source_id()
    {
        $row = $this->db->select('id')->where('is_default', 1)->where('deleted_at', null)->get('inventory_sources')->row_array();
        if ($row) {
            return (int) $row['id'];
        }
        $any = $this->db->select('id')->where('deleted_at', null)->order_by('id', 'ASC')->get('inventory_sources')->row_array();
        return $any ? (int) $any['id'] : null;
    }

    public function get_dropdown()
    {
        $out = [];
        foreach ($this->db->where('deleted_at', null)->order_by('priority', 'ASC')->get('inventory_sources')->result_array() as $r) {
            $out[$r['id']] = $r['name'];
        }
        return $out;
    }

    public function unique_code($code, $ignore_id = null)
    {
        $this->db->where('code', trim((string) $code))->where('deleted_at', null);
        if ($ignore_id) {
            $this->db->where('id !=', (int) $ignore_id);
        }
        return $this->db->get('inventory_sources')->num_rows() === 0;
    }

    public function toggle_status($id)
    {
        $row = $this->find($id);
        if (empty($row)) {
            return false;
        }
        $new = ($row['status'] === 'Active') ? 'Inactive' : 'Active';
        if (!$this->update($id, ['status' => $new])) {
            return false;
        }
        // A source going active/inactive changes every rollup it participates in.
        $this->recompute_source($id);
        return $new;
    }

    public function clear_other_defaults($keep_id)
    {
        $this->db->where('id !=', (int) $keep_id)->update('inventory_sources', ['is_default' => 0]);
    }

    public function count_all($status = '')
    {
        $this->db->where('deleted_at', null);
        if ($status !== '' && $status !== null) {
            $this->db->where('status', $status);
        }
        return $this->db->count_all_results($this->table);
    }

    public function datatable($search, $start, $length, $order_col, $order_dir, $status = '')
    {
        $apply = function () use ($search, $status) {
            $this->db->from('inventory_sources')->where('deleted_at', null);
            if ($status !== '' && $status !== null) {
                $this->db->where('status', $status);
            }
            if ($search !== '') {
                $this->db->group_start()->like('name', $search)->or_like('code', $search)->or_like('city', $search)->group_end();
            }
        };
        $apply();
        $filtered = $this->db->count_all_results();
        $apply();
        $this->db->order_by($order_col, $order_dir)->limit($length, $start);
        return ['filtered' => $filtered, 'data' => $this->db->get()->result()];
    }

    // ================= Per-source stock =================

    /**
     * Per-source qty rows for a base product (variant_id 0), including sources
     * with no row yet (qty 0). Only non-deleted sources.
     */
    public function per_source($product_id, $variant_id = 0)
    {
        $sources = $this->db->where('deleted_at', null)->order_by('priority', 'ASC')->order_by('id', 'ASC')
            ->get('inventory_sources')->result_array();
        $have = [];
        foreach ($this->db->where('product_id', (int) $product_id)->where('variant_id', (int) $variant_id)->get('product_inventories')->result_array() as $pi) {
            $have[(int) $pi['inventory_source_id']] = (int) $pi['qty'];
        }
        $out = [];
        foreach ($sources as $s) {
            $out[] = [
                'inventory_source_id' => (int) $s['id'],
                'source_name'         => $s['name'],
                'source_status'       => $s['status'],
                'qty'                 => $have[(int) $s['id']] ?? 0,
            ];
        }
        return $out;
    }

    /**
     * Upsert per-source qty for a base product from a source_id => qty map, then
     * refresh the cached rollup. Raw query builder (no activity-log spam).
     */
    public function save_stock($product_id, $source_qty, $variant_id = 0)
    {
        $product_id = (int) $product_id;
        $variant_id = (int) $variant_id;
        $now = date('Y-m-d H:i:s');
        $uid = function_exists('get_loggedin_user_id') ? get_loggedin_user_id() : null;
        foreach ((array) $source_qty as $sid => $qty) {
            $sid = (int) $sid;
            $qty = max(0, (int) $qty);
            if ($sid <= 0) {
                continue;
            }
            $existing = $this->db->where('product_id', $product_id)->where('variant_id', $variant_id)->where('inventory_source_id', $sid)->get('product_inventories')->row();
            $old = $existing ? (int) $existing->qty : 0;
            if ($existing) {
                $this->db->where('id', $existing->id)->update('product_inventories', ['qty' => $qty, 'updated_at' => $now]);
            } else {
                $this->db->insert('product_inventories', ['product_id' => $product_id, 'variant_id' => $variant_id, 'inventory_source_id' => $sid, 'qty' => $qty, 'created_at' => $now]);
            }
            // Additive ledger entry: record the net change as a manual adjustment.
            if ($qty !== $old) {
                $this->log_movement([
                    'product_id'          => $product_id,
                    'variant_id'          => $variant_id,
                    'inventory_source_id' => $sid,
                    'type'                => 'adjust',
                    'qty'                 => $qty - $old,
                    'reason'              => 'Manual stock edit',
                    'created_by'          => $uid,
                ]);
            }
        }
        $this->recompute($product_id, $variant_id);
    }

    /**
     * Recompute the cached rollup: products.stock_quantity (variant_id 0) =
     * SUM(qty) over ACTIVE, non-deleted sources. Variants (variant_id>0) are not
     * multi-source in v1, so they are left untouched.
     */
    public function recompute($product_id, $variant_id = 0)
    {
        if ((int) $variant_id !== 0) {
            return; // variants keep their own single stock
        }
        $row = $this->db->select('COALESCE(SUM(pi.qty),0) AS total', false)
            ->from('product_inventories pi')
            ->join('inventory_sources s', 's.id = pi.inventory_source_id')
            ->where('pi.product_id', (int) $product_id)
            ->where('pi.variant_id', 0)
            ->where('s.deleted_at', null)
            ->where('s.status', 'Active')
            ->get()->row_array();
        $total = (int) ($row['total'] ?? 0);
        $this->db->where('id', (int) $product_id)->update('products', ['stock_quantity' => $total]);
        return $total;
    }

    /** Recompute the rollup for every base product that stocks a given source. */
    public function recompute_source($source_id)
    {
        $pids = $this->db->select('DISTINCT product_id', false)->where('inventory_source_id', (int) $source_id)->where('variant_id', 0)->get('product_inventories')->result_array();
        foreach ($pids as $p) {
            $this->recompute((int) $p['product_id'], 0);
        }
    }

    // ================= Order allocation =================

    /**
     * Allocate $qty of a base product across its active sources by priority ASC,
     * decrement each source's qty, record the allocation for $order_id, and
     * refresh the rollup. Returns the qty actually allocated (may be < qty if
     * oversold — the check stage is the real guard). Idempotent per call only.
     */
    public function allocate($order_id, $product_id, $qty, $variant_id = 0)
    {
        $product_id = (int) $product_id;
        $qty = (int) $qty;
        if ($qty <= 0) {
            return 0;
        }
        $now = date('Y-m-d H:i:s');
        // Active sources holding this product, cheapest-priority first.
        $rows = $this->db->select('pi.id, pi.inventory_source_id, pi.qty')
            ->from('product_inventories pi')
            ->join('inventory_sources s', 's.id = pi.inventory_source_id')
            ->where('pi.product_id', $product_id)->where('pi.variant_id', (int) $variant_id)
            ->where('s.deleted_at', null)->where('s.status', 'Active')
            ->where('pi.qty >', 0)
            ->order_by('s.priority', 'ASC')->order_by('s.id', 'ASC')
            ->get()->result_array();

        $remaining = $qty;
        foreach ($rows as $pi) {
            if ($remaining <= 0) {
                break;
            }
            $take = min($remaining, (int) $pi['qty']);
            if ($take <= 0) {
                continue;
            }
            $this->db->where('id', (int) $pi['id'])->update('product_inventories', ['qty' => (int) $pi['qty'] - $take, 'updated_at' => $now]);
            $this->db->insert('order_stock_allocations', [
                'order_id' => (int) $order_id, 'product_id' => $product_id, 'variant_id' => (int) $variant_id,
                'inventory_source_id' => (int) $pi['inventory_source_id'], 'qty' => $take, 'created_at' => $now,
            ]);
            // Additive ledger entry: a source-level decrement for this order.
            $this->log_movement([
                'product_id'          => $product_id,
                'variant_id'          => (int) $variant_id,
                'inventory_source_id' => (int) $pi['inventory_source_id'],
                'type'                => 'allocation',
                'qty'                 => -$take,
                'reason'              => 'Order allocation',
                'reference'           => 'order#' . (int) $order_id,
            ]);
            $remaining -= $take;
        }
        $this->recompute($product_id, (int) $variant_id);
        return $qty - $remaining;
    }

    // ================= Stock movement ledger =================

    /**
     * Append a row to the stock_movements ledger. Best-effort: a logging failure
     * (e.g. the table not yet migrated) must NEVER break a stock write or checkout,
     * so it guards on table existence first.
     */
    public function log_movement(array $m)
    {
        if (!$this->db->table_exists('stock_movements')) {
            return;
        }
        $this->db->insert('stock_movements', [
            'product_id'          => (int) ($m['product_id'] ?? 0),
            'variant_id'          => (int) ($m['variant_id'] ?? 0),
            'inventory_source_id' => (int) ($m['inventory_source_id'] ?? 0),
            'type'                => $m['type'] ?? 'adjust',
            'qty'                 => (int) ($m['qty'] ?? 0),
            'reason'              => $m['reason'] ?? null,
            'reference'           => $m['reference'] ?? null,
            'created_by'          => $m['created_by'] ?? (function_exists('get_loggedin_user_id') ? get_loggedin_user_id() : null),
            'created_at'          => date('Y-m-d H:i:s'),
        ]);
    }

    /** Available qty of a base product/variant at a given source. */
    public function source_qty($product_id, $source_id, $variant_id = 0)
    {
        $r = $this->db->select('qty')
            ->where(['product_id' => (int) $product_id, 'variant_id' => (int) $variant_id, 'inventory_source_id' => (int) $source_id])
            ->get('product_inventories')->row();
        return $r ? (int) $r->qty : 0;
    }

    /**
     * Move qty of a product from one source to another in one transaction, writing
     * transfer_out/transfer_in ledger entries. Returns true on success.
     */
    public function transfer_stock($product_id, $from_source, $to_source, $qty, $variant_id = 0, $note = null, $uid = null)
    {
        $product_id = (int) $product_id;
        $from_source = (int) $from_source;
        $to_source = (int) $to_source;
        $qty = (int) $qty;
        $variant_id = (int) $variant_id;
        if ($qty <= 0 || $from_source <= 0 || $to_source <= 0 || $from_source === $to_source) {
            return false;
        }
        $from = $this->db->where(['product_id' => $product_id, 'variant_id' => $variant_id, 'inventory_source_id' => $from_source])
            ->get('product_inventories')->row();
        if (!$from || (int) $from->qty < $qty) {
            return false; // insufficient stock at the source
        }
        $now = date('Y-m-d H:i:s');
        $this->db->trans_start();
        $this->db->where('id', (int) $from->id)->update('product_inventories', ['qty' => (int) $from->qty - $qty, 'updated_at' => $now]);
        $to = $this->db->where(['product_id' => $product_id, 'variant_id' => $variant_id, 'inventory_source_id' => $to_source])
            ->get('product_inventories')->row();
        if ($to) {
            $this->db->where('id', (int) $to->id)->update('product_inventories', ['qty' => (int) $to->qty + $qty, 'updated_at' => $now]);
        } else {
            $this->db->insert('product_inventories', ['product_id' => $product_id, 'variant_id' => $variant_id, 'inventory_source_id' => $to_source, 'qty' => $qty, 'created_at' => $now]);
        }
        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            return false;
        }
        $this->log_movement(['product_id' => $product_id, 'variant_id' => $variant_id, 'inventory_source_id' => $from_source, 'type' => 'transfer_out', 'qty' => -$qty, 'reason' => $note ?: 'Stock transfer', 'reference' => 'to#' . $to_source, 'created_by' => $uid]);
        $this->log_movement(['product_id' => $product_id, 'variant_id' => $variant_id, 'inventory_source_id' => $to_source, 'type' => 'transfer_in', 'qty' => $qty, 'reason' => $note ?: 'Stock transfer', 'reference' => 'from#' . $from_source, 'created_by' => $uid]);
        $this->recompute($product_id, $variant_id);
        return true;
    }

    /** On-hand totals grouped by source (all non-deleted sources). */
    public function stock_by_source()
    {
        return $this->db->select('s.id, s.name, s.code, s.status, COALESCE(SUM(pi.qty),0) AS on_hand', false)
            ->from('inventory_sources s')
            ->join('product_inventories pi', 'pi.inventory_source_id = s.id', 'left')
            ->where('s.deleted_at', null)
            ->group_by('s.id')
            ->order_by('s.priority', 'ASC')
            ->get()->result_array();
    }

    /** Active products at/under a low-stock threshold, with rollup on-hand. */
    public function low_stock($threshold = 5, $limit = 200)
    {
        return $this->db->select('id, name, sku, stock_quantity, stock_status', false)
            ->where('deleted_at', null)
            ->where('status', 'Active')
            ->where('stock_quantity <=', (int) $threshold)
            ->order_by('stock_quantity', 'ASC')
            ->limit((int) $limit)
            ->get('products')->result_array();
    }

    /** Server-side datatable of the movement ledger. */
    public function movements_datatable($search, $start, $length, $type = '')
    {
        $apply = function () use ($search, $type) {
            $this->db->from('stock_movements m')
                ->join('products p', 'p.id = m.product_id', 'left')
                ->join('inventory_sources s', 's.id = m.inventory_source_id', 'left');
            if ($type !== '' && $type !== null) {
                $this->db->where('m.type', $type);
            }
            if ($search !== '') {
                $this->db->group_start()->like('p.name', $search)->or_like('m.reference', $search)->or_like('m.reason', $search)->group_end();
            }
        };
        $apply();
        $filtered = $this->db->count_all_results();
        $apply();
        $this->db->select('m.*, p.name AS product_name, s.name AS source_name', false)->order_by('m.id', 'DESC')->limit($length, $start);
        return ['filtered' => $filtered, 'data' => $this->db->get()->result()];
    }

    public function movements_count($type = '')
    {
        if ($type !== '' && $type !== null) {
            $this->db->where('type', $type);
        }
        return (int) $this->db->count_all_results('stock_movements');
    }
}
