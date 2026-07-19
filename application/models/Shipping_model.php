<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Shipping
 * @author   : Mamun Mia Turan
 * @filename : Shipping_model.php
 *
 * Shipping zones + methods and the rate engine. A zone matches by division
 * ('*' = fallback for all divisions). Each method computes a rate:
 *   - flat     : base_rate
 *   - per_unit : base_rate + per_unit_rate * total_qty
 *   - free     : 0
 * Any method also becomes free when free_over is set and the (discounted)
 * subtotal reaches it. Zone CRUD uses MY_Model; methods use raw query builder.
 */
class Shipping_model extends MY_Model
{
    protected $table = 'shipping_zones';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDelete = true;
    protected $allowedFields = ['name', 'divisions', 'status', 'sort_order', 'created_by', 'updated_by'];

    // ================= Rate engine =================

    /**
     * Resolve the zone that serves a division. Prefers a zone that explicitly
     * lists the division; otherwise the first Active '*' fallback zone.
     */
    public function match_zone($division)
    {
        $division = trim((string) $division);
        $zones = $this->db->where('status', 'Active')->where('deleted_at', null)
            ->order_by('sort_order', 'ASC')->order_by('id', 'ASC')
            ->get('shipping_zones')->result_array();

        $fallback = null;
        foreach ($zones as $z) {
            $divs = array_map('trim', explode(',', (string) $z['divisions']));
            if (in_array('*', $divs, true)) {
                if ($fallback === null) {
                    $fallback = $z;
                }
                continue;
            }
            if ($division !== '') {
                foreach ($divs as $d) {
                    if ($d !== '' && strcasecmp($d, $division) === 0) {
                        return $z;
                    }
                }
            }
        }
        return $fallback;
    }

    public function compute_rate($method, $disc_subtotal, $total_qty)
    {
        if ($method['type'] === 'free') {
            return 0.0;
        }
        if ($method['free_over'] !== null && $method['free_over'] !== '' && (float) $disc_subtotal >= (float) $method['free_over']) {
            return 0.0;
        }
        if ($method['type'] === 'per_unit') {
            return round((float) $method['base_rate'] + (float) $method['per_unit_rate'] * max(1, (int) $total_qty), 2);
        }
        return round((float) $method['base_rate'], 2); // flat
    }

    /**
     * Active methods for the division's zone, each with a computed_rate + is_free.
     * @return array ['zone'=>?array, 'methods'=>array]
     */
    public function available_methods($division, $disc_subtotal, $total_qty)
    {
        $zone = $this->match_zone($division);
        if (!$zone) {
            return ['zone' => null, 'methods' => []];
        }
        $methods = $this->db->where('zone_id', (int) $zone['id'])->where('status', 'Active')->where('deleted_at', null)
            ->order_by('sort_order', 'ASC')->order_by('id', 'ASC')
            ->get('shipping_methods')->result_array();
        foreach ($methods as &$m) {
            $m['computed_rate'] = $this->compute_rate($m, $disc_subtotal, $total_qty);
            $m['is_free'] = ($m['computed_rate'] <= 0.0);
        }
        unset($m);
        return ['zone' => $zone, 'methods' => $methods];
    }

    /**
     * Pick the chosen method (validated to belong to the served zone) or default
     * to the first available. Returns the method row (with computed_rate) or null
     * when no method serves the address.
     */
    public function resolve($division, $disc_subtotal, $total_qty, $method_id = null)
    {
        $avail = $this->available_methods($division, $disc_subtotal, $total_qty);
        if (empty($avail['methods'])) {
            return null;
        }
        if ($method_id) {
            foreach ($avail['methods'] as $m) {
                if ((int) $m['id'] === (int) $method_id) {
                    return $m;
                }
            }
        }
        return $avail['methods'][0];
    }

    // ================= Zone CRUD (MY_Model) =================

    public function get_zones_dropdown()
    {
        $rows = $this->db->where('deleted_at', null)->order_by('sort_order', 'ASC')->order_by('name', 'ASC')->get('shipping_zones')->result_array();
        $out = [];
        foreach ($rows as $r) {
            $out[$r['id']] = $r['name'];
        }
        return $out;
    }

    public function count_zones()
    {
        return (int) $this->db->where('deleted_at', null)->count_all_results('shipping_zones');
    }

    public function zones_datatable($search, $start, $length, $order_col, $order_dir)
    {
        $apply = function () use ($search) {
            $this->db->from('shipping_zones')->where('deleted_at', null);
            if ($search !== '') {
                $this->db->group_start()->like('name', $search)->or_like('divisions', $search)->group_end();
            }
        };
        $apply();
        $filtered = $this->db->count_all_results();
        $apply();
        $this->db->order_by($order_col, $order_dir)->limit($length, $start);
        return ['filtered' => $filtered, 'data' => $this->db->get()->result()];
    }

    // ================= Method CRUD (raw) =================

    public function get_method($id)
    {
        return $this->db->where('id', (int) $id)->where('deleted_at', null)->get('shipping_methods')->row_array();
    }

    public function count_methods()
    {
        return (int) $this->db->where('deleted_at', null)->count_all_results('shipping_methods');
    }

    public function methods_datatable($search, $start, $length, $order_col, $order_dir, $zone_id = '')
    {
        $apply = function () use ($search, $zone_id) {
            $this->db->from('shipping_methods m')->join('shipping_zones z', 'z.id = m.zone_id', 'left')->where('m.deleted_at', null);
            if ($zone_id !== '' && $zone_id !== null) {
                $this->db->where('m.zone_id', (int) $zone_id);
            }
            if ($search !== '') {
                $this->db->group_start()->like('m.title', $search)->or_like('m.code', $search)->or_like('z.name', $search)->group_end();
            }
        };
        $apply();
        $filtered = $this->db->count_all_results();
        $this->db->select('m.*, z.name AS zone_name');
        $apply();
        $this->db->order_by($order_col, $order_dir)->limit($length, $start);
        return ['filtered' => $filtered, 'data' => $this->db->get()->result()];
    }

    public function insert_method($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('shipping_methods', $data);
        return (int) $this->db->insert_id();
    }

    public function update_method($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', (int) $id)->update('shipping_methods', $data);
        return true;
    }

    public function delete_method($id)
    {
        $this->db->where('id', (int) $id)->update('shipping_methods', ['deleted_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    public function toggle_zone_status($id)
    {
        $row = $this->find($id);
        if (empty($row)) {
            return false;
        }
        $new = ($row['status'] === 'Active') ? 'Inactive' : 'Active';
        return $this->update($id, ['status' => $new]) ? $new : false;
    }

    public function toggle_method_status($id)
    {
        $row = $this->get_method($id);
        if (empty($row)) {
            return false;
        }
        $new = ($row['status'] === 'Active') ? 'Inactive' : 'Active';
        $this->update_method($id, ['status' => $new]);
        return $new;
    }
}
