<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog (EAV)
 * @author   : Mamun Mia Turan
 * @filename : Attribute_family_model.php
 *
 * Attribute families + their groups + the group<->attribute pivot. A product
 * belongs to one family (products.attribute_family_id); the family's groups are
 * the buckets the product edit form renders, ordered across two columns.
 */
class Attribute_family_model extends MY_Model
{
    protected $table = 'attribute_families';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $useSoftDelete = false;
    protected $allowedFields = ['code', 'name', 'status', 'is_user_defined'];

    // ---- families ----

    public function default_family_id()
    {
        $row = $this->db->select('id')->where('code', 'default')->get('attribute_families')->row_array();
        if ($row) {
            return (int) $row['id'];
        }
        $any = $this->db->select('id')->order_by('id', 'ASC')->get('attribute_families')->row_array();
        return $any ? (int) $any['id'] : null;
    }

    public function get_dropdown()
    {
        $rows = $this->db->order_by('name', 'ASC')->get('attribute_families')->result_array();
        $out = [];
        foreach ($rows as $r) {
            $out[$r['id']] = $r['name'];
        }
        return $out;
    }

    public function unique_code($code, $ignore_id = null)
    {
        $this->db->where('code', trim((string) $code));
        if ($ignore_id) {
            $this->db->where('id !=', (int) $ignore_id);
        }
        return $this->db->get('attribute_families')->num_rows() === 0;
    }

    public function toggle_status($id)
    {
        $row = $this->find($id);
        if (empty($row)) {
            return false;
        }
        $new = ((int) $row['status'] === 1) ? 0 : 1;
        return $this->update($id, ['status' => $new]) ? $new : false;
    }

    public function product_count($family_id)
    {
        return (int) $this->db->where('attribute_family_id', (int) $family_id)->where('deleted_at', null)->count_all_results('products');
    }

    // ---- groups ----

    public function get_groups($family_id)
    {
        return $this->db->where('attribute_family_id', (int) $family_id)
            ->order_by('`column`', 'ASC', false)->order_by('position', 'ASC')->order_by('id', 'ASC')
            ->get('attribute_groups')->result_array();
    }

    /**
     * A family's groups, each with its ordered attributes (incl. options for
     * option-typed attributes). This is the structure the product edit form and
     * the storefront render from.
     * @return array [ ['group'=>row, 'attributes'=>[ attr + 'options'=>[] ] ], ... ]
     */
    public function grouped_attributes($family_id)
    {
        $out = [];
        foreach ($this->get_groups($family_id) as $g) {
            $attrs = $this->db->select('a.*, m.position AS pivot_position')
                ->from('attribute_group_mappings m')
                ->join('attributes a', 'a.id = m.attribute_id')
                ->where('m.attribute_group_id', (int) $g['id'])
                ->where('a.deleted_at', null)
                ->where('a.status', 'Active')
                ->order_by('m.position', 'ASC')->order_by('a.position', 'ASC')
                ->get()->result_array();
            foreach ($attrs as &$a) {
                $a['options'] = in_array($a['type'], ['select', 'multiselect', 'checkbox'], true)
                    ? $this->db->where('attribute_id', (int) $a['id'])->order_by('sort_order', 'ASC')->get('attribute_options')->result_array()
                    : [];
            }
            unset($a);
            $out[] = ['group' => $g, 'attributes' => $attrs];
        }
        return $out;
    }

    /** Flat ordered list of a family's attributes (no group nesting). */
    public function family_attributes($family_id)
    {
        return $this->db->select('a.*, m.position AS pivot_position')
            ->from('attribute_group_mappings m')
            ->join('attributes a', 'a.id = m.attribute_id')
            ->join('attribute_groups g', 'g.id = m.attribute_group_id')
            ->where('g.attribute_family_id', (int) $family_id)
            ->where('a.deleted_at', null)
            ->where('a.status', 'Active')
            ->order_by('a.id', 'ASC')
            ->get()->result_array();
    }

    /**
     * Persist a family's group tree from the builder payload. Upserts groups,
     * syncs the attribute mappings, deletes removed groups/mappings. System
     * groups (is_user_defined=0) are preserved. Raw query builder.
     *
     * $tree: [ ['id'=>?, 'code'=>, 'name'=>, 'column'=>1|2, 'position'=>,
     *           'attributes'=>[ ['id'=>attr_id,'position'=>], ... ] ], ... ]
     */
    public function save_tree($family_id, $tree)
    {
        $family_id = (int) $family_id;
        $keep_groups = [];
        $used_names  = []; // lowercased group names already used this pass (unique per family)
        $gpos = 1;
        $ok = true;

        $this->db->trans_start();
        foreach ($tree as $g) {
            $name = trim((string) ($g['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            // The (family, name) unique constraint would make a duplicate group
            // name a silent failing INSERT — skip duplicates rather than corrupt.
            $key = strtolower($name);
            if (isset($used_names[$key])) {
                continue;
            }
            $used_names[$key] = true;

            $col = ((int) ($g['column'] ?? 1) === 2) ? 2 : 1;
            $grow = [
                'attribute_family_id' => $family_id,
                'code'                => trim((string) ($g['code'] ?? '')) ?: strtolower(preg_replace('/[^a-z0-9]+/i', '_', $name)),
                'name'                => $name,
                'column'              => $col,
                'position'            => isset($g['position']) && $g['position'] !== '' ? (int) $g['position'] : $gpos,
            ];
            $gid = (int) ($g['id'] ?? 0);
            $existing = $gid > 0 ? $this->db->where('id', $gid)->where('attribute_family_id', $family_id)->get('attribute_groups')->row_array() : null;
            if ($existing) {
                $this->db->where('id', $gid)->update('attribute_groups', $grow);
            } else {
                $grow['is_user_defined'] = 1;
                $this->db->insert('attribute_groups', $grow);
                $gid = (int) $this->db->insert_id();
                if ($gid <= 0) {
                    // Insert failed (e.g. a name collision with an existing group
                    // not in this tree). Skip its mappings so we never write rows
                    // with attribute_group_id = 0.
                    $ok = false;
                    continue;
                }
            }
            $keep_groups[] = $gid;

            // sync this group's attribute mappings
            $keep_attr = [];
            $apos = 1;
            foreach ((array) ($g['attributes'] ?? []) as $a) {
                $aid = (int) ($a['id'] ?? 0);
                if ($aid <= 0) {
                    continue;
                }
                $pos = isset($a['position']) && $a['position'] !== '' ? (int) $a['position'] : $apos;
                if ($this->db->where('attribute_id', $aid)->where('attribute_group_id', $gid)->get('attribute_group_mappings')->row()) {
                    $this->db->where('attribute_id', $aid)->where('attribute_group_id', $gid)->update('attribute_group_mappings', ['position' => $pos]);
                } else {
                    $this->db->insert('attribute_group_mappings', ['attribute_id' => $aid, 'attribute_group_id' => $gid, 'position' => $pos]);
                }
                $keep_attr[] = $aid;
                $apos++;
            }
            // Reconcile ONLY the mappings the builder can actually render — Active,
            // non-deleted attributes. Mappings for Inactive/soft-deleted attributes
            // are preserved (the builder omits them, so a blind sweep would drop
            // membership that the admin never intended to remove).
            $this->db->where('attribute_group_id', $gid);
            if (!empty($keep_attr)) {
                $this->db->where_not_in('attribute_id', $keep_attr);
            }
            $this->db->where('attribute_id IN (SELECT id FROM attributes WHERE status = "Active" AND deleted_at IS NULL)', null, false);
            $this->db->delete('attribute_group_mappings');
            $gpos++;
        }

        // Delete user-defined groups removed from the tree (mappings cascade via
        // FK). System groups (is_user_defined=0) are never swept, so seeded
        // groups + their attribute mappings survive even if the builder omits them.
        $this->db->where('attribute_family_id', $family_id)->where('is_user_defined', 1);
        if (!empty($keep_groups)) {
            $this->db->where_not_in('id', $keep_groups);
        }
        $this->db->delete('attribute_groups');

        $this->db->trans_complete();
        return $ok && $this->db->trans_status() !== false;
    }

    // ---- datatable ----

    public function datatable($search, $start, $length, $order_col, $order_dir)
    {
        $apply = function () use ($search) {
            $this->db->from('attribute_families');
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

    public function count_all()
    {
        return $this->db->count_all_results('attribute_families');
    }
}
