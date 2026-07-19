<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Tax
 * @author   : Mamun Mia Turan
 * @filename : Tax_model.php
 *
 * Tax categories + rates + the matching engine. Tax is EXCLUSIVE (added on top
 * of the discounted line total). A product's tax_category_id selects a category;
 * the category's rates are matched against the shipping address
 * (country/state=division/postcode), first match wins by priority DESC, rate DESC.
 * Category CRUD uses MY_Model; rates use raw query builder.
 */
class Tax_model extends MY_Model
{
    protected $table = 'tax_categories';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDelete = true;
    protected $allowedFields = ['code', 'name', 'description', 'is_default', 'status', 'created_by', 'updated_by'];

    private $_rate_cache = [];

    // ================= Matching engine =================

    public function default_category_id()
    {
        $row = $this->db->select('id')->where('is_default', 1)->where('status', 'Active')->where('deleted_at', null)->get('tax_categories')->row_array();
        return $row ? (int) $row['id'] : null;
    }

    /**
     * The applicable tax rate (percent) for a category at an address.
     * @param array $address ['country'=>?, 'state'=>?(division), 'postcode'=>?]
     * @return float percent (0.0 when nothing matches)
     */
    public function rate_for_category($tax_category_id, $address)
    {
        $tax_category_id = (int) $tax_category_id;
        if ($tax_category_id <= 0) {
            return 0.0;
        }
        $country  = strtoupper(trim((string) ($address['country'] ?? 'BD'))) ?: 'BD';
        $state    = trim((string) ($address['state'] ?? ''));
        $postcode = trim((string) ($address['postcode'] ?? ''));

        $ckey = $tax_category_id . '|' . $country . '|' . strtolower($state) . '|' . $postcode;
        if (isset($this->_rate_cache[$ckey])) {
            return $this->_rate_cache[$ckey];
        }

        $rates = $this->db
            ->select('r.*')
            ->from('tax_category_rates cr')
            ->join('tax_rates r', 'r.id = cr.tax_rate_id')
            ->where('cr.tax_category_id', $tax_category_id)
            ->where('r.status', 'Active')
            ->where('r.deleted_at', null)
            ->where('r.country', $country)
            ->order_by('r.priority', 'DESC')
            ->order_by('r.rate', 'DESC')
            ->get()->result_array();

        $matched = 0.0;
        foreach ($rates as $r) {
            $rstate = trim((string) $r['state']);
            if ($rstate !== '' && $rstate !== '*' && strcasecmp($rstate, $state) !== 0) {
                continue;
            }
            $rzip = trim((string) $r['postcode']);
            if ($rzip !== '' && $rzip !== '*' && $rzip !== $postcode) {
                continue;
            }
            $matched = (float) $r['rate'];
            break; // first match wins
        }

        $this->_rate_cache[$ckey] = $matched;
        return $matched;
    }

    /**
     * Total tax for a set of taxable lines at an address (tax-exclusive).
     * @param array $lines each ['taxable'=>float, 'tax_category_id'=>?int]
     * @return array ['tax'=>float, 'lines'=>[['rate'=>float,'amount'=>float], ...]]
     */
    public function compute_for_lines($lines, $address)
    {
        $default = $this->default_category_id();
        $tax = 0.0;
        $out = [];
        foreach ($lines as $ln) {
            $cat = !empty($ln['tax_category_id']) ? (int) $ln['tax_category_id'] : $default;
            $rate = $cat ? $this->rate_for_category($cat, $address) : 0.0;
            $amount = round(((float) $ln['taxable']) * $rate / 100, 2);
            $tax += $amount;
            $out[] = ['rate' => $rate, 'amount' => $amount];
        }
        return ['tax' => round($tax, 2), 'lines' => $out];
    }

    // ================= Category CRUD (MY_Model) =================

    public function get_dropdown($include_none = true)
    {
        $rows = $this->db->where('deleted_at', null)->order_by('name', 'ASC')->get('tax_categories')->result_array();
        $out = [];
        foreach ($rows as $r) {
            $out[$r['id']] = $r['name'] . ($r['is_default'] ? ' (default)' : '');
        }
        return $out;
    }

    public function unique_code($code, $ignore_id = null)
    {
        $this->db->where('code', trim((string) $code))->where('deleted_at', null);
        if ($ignore_id) {
            $this->db->where('id !=', (int) $ignore_id);
        }
        return $this->db->get('tax_categories')->num_rows() === 0;
    }

    /**
     * When a category is marked default, clear the flag on every other category.
     */
    public function clear_other_defaults($keep_id)
    {
        $this->db->where('id !=', (int) $keep_id)->update('tax_categories', ['is_default' => 0]);
    }

    public function count_categories()
    {
        return (int) $this->db->where('deleted_at', null)->count_all_results('tax_categories');
    }

    public function categories_datatable($search, $start, $length, $order_col, $order_dir)
    {
        $apply = function () use ($search) {
            $this->db->from('tax_categories')->where('deleted_at', null);
            if ($search !== '') {
                $this->db->group_start()->like('name', $search)->or_like('code', $search)->group_end();
            }
        };
        $apply();
        $filtered = $this->db->count_all_results();
        $apply();
        $this->db->order_by($order_col, $order_dir)->limit($length, $start);
        return ['filtered' => $filtered, 'data' => $this->db->get()->result()];
    }

    public function toggle_category_status($id)
    {
        $row = $this->find($id);
        if (empty($row)) {
            return false;
        }
        $new = ($row['status'] === 'Active') ? 'Inactive' : 'Active';
        return $this->update($id, ['status' => $new]) ? $new : false;
    }

    // ---- category ↔ rate mapping ----

    public function rate_ids_for_category($category_id)
    {
        $rows = $this->db->select('tax_rate_id')->where('tax_category_id', (int) $category_id)->get('tax_category_rates')->result_array();
        return array_map(function ($r) { return (int) $r['tax_rate_id']; }, $rows);
    }

    public function set_category_rates($category_id, $rate_ids)
    {
        $this->db->where('tax_category_id', (int) $category_id)->delete('tax_category_rates');
        foreach (array_unique(array_map('intval', (array) $rate_ids)) as $rid) {
            if ($rid > 0) {
                $this->db->insert('tax_category_rates', ['tax_category_id' => (int) $category_id, 'tax_rate_id' => $rid]);
            }
        }
    }

    // ================= Rate CRUD (raw) =================

    public function get_rate($id)
    {
        return $this->db->where('id', (int) $id)->where('deleted_at', null)->get('tax_rates')->row_array();
    }

    public function get_rates_all()
    {
        return $this->db->where('deleted_at', null)->order_by('name', 'ASC')->get('tax_rates')->result_array();
    }

    public function unique_identifier($identifier, $ignore_id = null)
    {
        $this->db->where('identifier', trim((string) $identifier))->where('deleted_at', null);
        if ($ignore_id) {
            $this->db->where('id !=', (int) $ignore_id);
        }
        return $this->db->get('tax_rates')->num_rows() === 0;
    }

    public function count_rates()
    {
        return (int) $this->db->where('deleted_at', null)->count_all_results('tax_rates');
    }

    public function rates_datatable($search, $start, $length, $order_col, $order_dir)
    {
        $apply = function () use ($search) {
            $this->db->from('tax_rates')->where('deleted_at', null);
            if ($search !== '') {
                $this->db->group_start()->like('name', $search)->or_like('identifier', $search)->or_like('state', $search)->group_end();
            }
        };
        $apply();
        $filtered = $this->db->count_all_results();
        $apply();
        $this->db->order_by($order_col, $order_dir)->limit($length, $start);
        return ['filtered' => $filtered, 'data' => $this->db->get()->result()];
    }

    public function insert_rate($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('tax_rates', $data);
        return (int) $this->db->insert_id();
    }

    public function update_rate($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', (int) $id)->update('tax_rates', $data);
        return true;
    }

    public function delete_rate($id)
    {
        $this->db->where('id', (int) $id)->update('tax_rates', ['deleted_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    public function toggle_rate_status($id)
    {
        $row = $this->get_rate($id);
        if (empty($row)) {
            return false;
        }
        $new = ($row['status'] === 'Active') ? 'Inactive' : 'Active';
        $this->update_rate($id, ['status' => $new]);
        return $new;
    }
}
