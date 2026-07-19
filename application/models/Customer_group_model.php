<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Customers
 * @author   : Mamun Mia Turan
 * @filename : Customer_group_model.php
 *
 * Customer groups + membership. A group's discount_percent is applied to its
 * members through a group-scoped cart rule (Cart_rule_model consults
 * group_for_user() during evaluation).
 */
class Customer_group_model extends MY_Model
{
    protected $table = 'customer_groups';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDelete = true;
    protected $allowedFields = ['name', 'code', 'discount_percent', 'is_default', 'status', 'created_by', 'updated_by'];

    public function default_group_id()
    {
        $row = $this->db->select('id')->where('is_default', 1)->where('deleted_at', null)->get('customer_groups')->row_array();
        return $row ? (int) $row['id'] : null;
    }

    /** The group_id a user belongs to (null if none / guest). */
    public function group_for_user($user_id)
    {
        if (!$user_id) {
            return null;
        }
        $row = $this->db->select('customer_group_id')->where('id', (int) $user_id)->get('users')->row_array();
        return ($row && $row['customer_group_id']) ? (int) $row['customer_group_id'] : null;
    }

    public function assign_user($user_id, $group_id)
    {
        $this->db->where('id', (int) $user_id)->update('users', ['customer_group_id' => $group_id ? (int) $group_id : null]);
        return true;
    }

    /**
     * The live automatic discount for a user's group — read straight from
     * customer_groups so admin edits take effect immediately (single source of
     * truth). Returns null unless the group is Active, not deleted, and > 0%.
     * @return array|null ['id','name','discount_percent']
     */
    public function active_group_discount($user_id)
    {
        if (!$user_id) {
            return null;
        }
        $row = $this->db->select('g.id, g.name, g.discount_percent')
            ->from('users u')
            ->join('customer_groups g', 'g.id = u.customer_group_id')
            ->where('u.id', (int) $user_id)
            ->where('g.status', 'Active')
            ->where('g.deleted_at', null)
            ->get()->row_array();
        if (!$row || (float) $row['discount_percent'] <= 0) {
            return null;
        }
        return ['id' => (int) $row['id'], 'name' => $row['name'], 'discount_percent' => (float) $row['discount_percent']];
    }

    public function get_dropdown($include_none = true)
    {
        $rows = $this->db->where('deleted_at', null)->order_by('name', 'ASC')->get('customer_groups')->result_array();
        $out = [];
        foreach ($rows as $r) {
            $out[$r['id']] = $r['name'] . ((float) $r['discount_percent'] > 0 ? ' (' . rtrim(rtrim(number_format($r['discount_percent'], 2), '0'), '.') . '%)' : '');
        }
        return $out;
    }

    public function unique_code($code, $ignore_id = null)
    {
        $this->db->where('code', trim((string) $code))->where('deleted_at', null);
        if ($ignore_id) {
            $this->db->where('id !=', (int) $ignore_id);
        }
        return $this->db->get('customer_groups')->num_rows() === 0;
    }

    public function clear_other_defaults($keep_id)
    {
        $this->db->where('id !=', (int) $keep_id)->update('customer_groups', ['is_default' => 0]);
    }

    public function toggle_status($id)
    {
        $row = $this->find($id);
        if (empty($row)) {
            return false;
        }
        $new = ($row['status'] === 'Active') ? 'Inactive' : 'Active';
        return $this->update($id, ['status' => $new]) ? $new : false;
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
            $this->db->from('customer_groups')->where('deleted_at', null);
            if ($status !== '' && $status !== null) {
                $this->db->where('status', $status);
            }
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
}
