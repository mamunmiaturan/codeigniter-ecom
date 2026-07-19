<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog (EAV Attributes)
 * @author   : Mamun Mia Turan
 * @filename : EavSeeder.php
 *
 * Idempotent bootstrap for the EAV attribute layer (ported from Bagisto's
 * Attribute module, additive — the existing catalog + product_variants are
 * untouched):
 *   php index.php migrate seed EavSeeder
 *
 * Ensures attributes / attribute_options / attribute_families / attribute_groups
 * / attribute_group_mappings / product_attribute_values, adds
 * products.attribute_family_id, registers `attribute` + `attribute_family`
 * permissions (Catalog module), seeds the Default family + groups + the
 * filterable descriptive attributes (color, size, material, warranty) with
 * options, backfills products to the default family, assigns demo values, and
 * refreshes the sidebar. Single-locale/single-channel: values store
 * locale/channel NULL.
 */
class EavSeeder extends Seeder
{
    public function run()
    {
        $this->_create_tables();
        $this->_add_product_column();
        $this->_seed_permission();
        $this->_seed_family_groups();
        $this->_seed_attributes();
        $this->_backfill_family();
        $this->_seed_demo_values();
        $this->_refresh_sidebar();
        echo "EavSeeder finished." . PHP_EOL;
    }

    // ---------------------------------------------------------------- schema

    private function _create_tables()
    {
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `admin_name` varchar(150) NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'text',
  `swatch_type` varchar(20) DEFAULT NULL,
  `validation` varchar(20) DEFAULT NULL,
  `regex` varchar(255) DEFAULT NULL,
  `position` int NOT NULL DEFAULT 0,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `is_unique` tinyint(1) NOT NULL DEFAULT 0,
  `is_filterable` tinyint(1) NOT NULL DEFAULT 0,
  `is_comparable` tinyint(1) NOT NULL DEFAULT 0,
  `is_configurable` tinyint(1) NOT NULL DEFAULT 0,
  `is_visible_on_front` tinyint(1) NOT NULL DEFAULT 1,
  `is_user_defined` tinyint(1) NOT NULL DEFAULT 1,
  `default_value` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_attributes_code` (`code`),
  KEY `idx_attributes_filterable` (`is_filterable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `attribute_options` (
  `id` int NOT NULL AUTO_INCREMENT,
  `attribute_id` int NOT NULL,
  `admin_name` varchar(150) DEFAULT NULL,
  `label` varchar(150) NOT NULL,
  `swatch_value` varchar(150) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attr_options_attr` (`attribute_id`),
  CONSTRAINT `fk_attr_options_attr` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `attribute_families` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `name` varchar(150) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `is_user_defined` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_attr_families_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `attribute_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(100) DEFAULT NULL,
  `attribute_family_id` int NOT NULL,
  `name` varchar(150) NOT NULL,
  `column` tinyint NOT NULL DEFAULT 1,
  `position` int NOT NULL DEFAULT 0,
  `is_user_defined` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_attr_group_family_name` (`attribute_family_id`, `name`),
  KEY `idx_attr_groups_family` (`attribute_family_id`),
  CONSTRAINT `fk_attr_groups_family` FOREIGN KEY (`attribute_family_id`) REFERENCES `attribute_families` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `attribute_group_mappings` (
  `attribute_id` int NOT NULL,
  `attribute_group_id` int NOT NULL,
  `position` int DEFAULT NULL,
  PRIMARY KEY (`attribute_id`, `attribute_group_id`),
  KEY `idx_agm_group` (`attribute_group_id`),
  CONSTRAINT `fk_agm_attr` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_agm_group` FOREIGN KEY (`attribute_group_id`) REFERENCES `attribute_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `product_attribute_values` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `attribute_id` int NOT NULL,
  `locale` varchar(20) DEFAULT NULL,
  `channel` varchar(50) DEFAULT NULL,
  `text_value` text,
  `boolean_value` tinyint(1) DEFAULT NULL,
  `integer_value` int DEFAULT NULL,
  `float_value` decimal(14,4) DEFAULT NULL,
  `datetime_value` datetime DEFAULT NULL,
  `date_value` date DEFAULT NULL,
  `json_value` longtext,
  `unique_id` varchar(150) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pav_unique` (`unique_id`),
  UNIQUE KEY `uk_pav_scope` (`channel`, `locale`, `attribute_id`, `product_id`),
  KEY `idx_pav_attr_int` (`attribute_id`, `integer_value`),
  KEY `idx_pav_product` (`product_id`),
  CONSTRAINT `fk_pav_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pav_attr` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
        echo "EAV tables ensured." . PHP_EOL;
    }

    private function _add_product_column()
    {
        if (!$this->db->field_exists('attribute_family_id', 'products')) {
            $this->db->query("ALTER TABLE `products` ADD COLUMN `attribute_family_id` int NULL AFTER `product_type`");
        }
        if (!$this->_fk_exists('fk_products_attr_family')) {
            // RESTRICT: a family in use by products cannot be deleted.
            $this->db->query("ALTER TABLE `products` ADD CONSTRAINT `fk_products_attr_family` FOREIGN KEY (`attribute_family_id`) REFERENCES `attribute_families` (`id`) ON DELETE RESTRICT");
        }
        echo "products.attribute_family_id ensured." . PHP_EOL;
    }

    private function _fk_exists($name)
    {
        return $this->db->query(
            "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$name]
        )->num_rows() > 0;
    }

    // ---------------------------------------------------------------- permissions

    private function _seed_permission()
    {
        $mod = $this->db->get_where('permission_modules', ['prefix' => 'catalog'])->row();
        if (!$mod) {
            $sorted = (int) $this->db->select_max('sorted')->get('permission_modules')->row()->sorted + 1;
            $this->db->insert('permission_modules', ['name' => 'Catalog', 'prefix' => 'catalog', 'system' => 1, 'sorted' => $sorted, 'created_at' => date('Y-m-d H:i:s')]);
            $module_id = (int) $this->db->insert_id();
        } else {
            $module_id = (int) $mod->id;
        }
        $perms = [
            ['name' => 'Attributes', 'prefix' => 'attribute'],
            ['name' => 'Attribute Families', 'prefix' => 'attribute_family'],
        ];
        foreach ($perms as $p) {
            if (!$this->db->get_where('permission', ['prefix' => $p['prefix']])->row()) {
                $this->db->insert('permission', ['module_id' => $module_id, 'name' => $p['name'], 'prefix' => $p['prefix'], 'show_view' => 1, 'show_add' => 1, 'show_edit' => 1, 'show_delete' => 1]);
            }
            $perm = $this->db->get_where('permission', ['prefix' => $p['prefix']])->row();
            if ($perm) {
                foreach ([1, 2] as $role_id) {
                    if (!$this->db->get_where('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id])->num_rows()) {
                        $this->db->insert('user_privileges', ['role_id' => $role_id, 'permission_id' => $perm->id, 'is_view' => 1, 'is_add' => 1, 'is_edit' => 1, 'is_delete' => 1]);
                    }
                }
            }
        }
        echo "attribute + attribute_family permissions ensured." . PHP_EOL;
    }

    // ---------------------------------------------------------------- family/groups

    private function _seed_family_groups()
    {
        if (!$this->db->get_where('attribute_families', ['code' => 'default'])->row()) {
            $this->db->insert('attribute_families', ['code' => 'default', 'name' => 'Default', 'status' => 1, 'is_user_defined' => 0, 'created_at' => date('Y-m-d H:i:s')]);
        }
        $family = $this->db->get_where('attribute_families', ['code' => 'default'])->row();
        $fid = (int) $family->id;

        // Groups for custom (EAV) attributes — native columns already cover
        // name/price/etc, so these buckets host the descriptive extras.
        $groups = [
            ['code' => 'general',        'name' => 'General',        'column' => 1, 'position' => 1],
            ['code' => 'specifications', 'name' => 'Specifications', 'column' => 1, 'position' => 2],
            ['code' => 'settings',       'name' => 'Settings',       'column' => 2, 'position' => 1],
        ];
        foreach ($groups as $g) {
            if (!$this->db->get_where('attribute_groups', ['attribute_family_id' => $fid, 'name' => $g['name']])->row()) {
                $this->db->insert('attribute_groups', [
                    'code' => $g['code'], 'attribute_family_id' => $fid, 'name' => $g['name'],
                    'column' => $g['column'], 'position' => $g['position'], 'is_user_defined' => 0,
                ]);
            }
        }
        echo "default family + groups ensured." . PHP_EOL;
    }

    private function _seed_attributes()
    {
        $family = $this->db->get_where('attribute_families', ['code' => 'default'])->row();
        $spec_group = $this->db->get_where('attribute_groups', ['attribute_family_id' => (int) $family->id, 'name' => 'Specifications'])->row();
        $spec_gid = (int) $spec_group->id;
        $now = date('Y-m-d H:i:s');

        // code => [name, type, is_filterable, is_configurable, is_visible_on_front, options[]]
        $defs = [
            ['code' => 'color',    'name' => 'Color',    'type' => 'select', 'filt' => 1, 'conf' => 1, 'front' => 1, 'options' => ['Red' => '#e53935', 'Green' => '#43a047', 'Blue' => '#1e88e5', 'Black' => '#000000', 'White' => '#ffffff']],
            ['code' => 'size',     'name' => 'Size',     'type' => 'select', 'filt' => 1, 'conf' => 1, 'front' => 1, 'options' => ['S' => null, 'M' => null, 'L' => null, 'XL' => null]],
            ['code' => 'material', 'name' => 'Material', 'type' => 'select', 'filt' => 1, 'conf' => 0, 'front' => 1, 'options' => ['Cotton' => null, 'Leather' => null, 'Plastic' => null, 'Metal' => null, 'Wood' => null]],
            ['code' => 'warranty', 'name' => 'Warranty', 'type' => 'text',   'filt' => 0, 'conf' => 0, 'front' => 1, 'options' => []],
        ];
        $pos = 1;
        foreach ($defs as $d) {
            if (!$this->db->get_where('attributes', ['code' => $d['code']])->row()) {
                $this->db->insert('attributes', [
                    'code' => $d['code'], 'admin_name' => $d['name'], 'name' => $d['name'], 'type' => $d['type'],
                    'swatch_type' => ($d['code'] === 'color' ? 'color' : ($d['type'] === 'select' ? 'dropdown' : null)),
                    'position' => $pos, 'is_filterable' => $d['filt'], 'is_configurable' => $d['conf'],
                    'is_visible_on_front' => $d['front'], 'is_user_defined' => 1, 'status' => 'Active', 'created_at' => $now,
                ]);
            }
            $attr = $this->db->get_where('attributes', ['code' => $d['code']])->row();
            $aid = (int) $attr->id;

            // Options
            $so = 1;
            foreach ($d['options'] as $label => $swatch) {
                if (!$this->db->get_where('attribute_options', ['attribute_id' => $aid, 'label' => $label])->row()) {
                    $this->db->insert('attribute_options', [
                        'attribute_id' => $aid, 'admin_name' => $label, 'label' => $label,
                        'swatch_value' => $swatch, 'sort_order' => $so, 'created_at' => $now,
                    ]);
                }
                $so++;
            }

            // Map into the Specifications group
            if (!$this->db->get_where('attribute_group_mappings', ['attribute_id' => $aid, 'attribute_group_id' => $spec_gid])->row()) {
                $this->db->insert('attribute_group_mappings', ['attribute_id' => $aid, 'attribute_group_id' => $spec_gid, 'position' => $pos]);
            }
            $pos++;
        }
        echo "descriptive attributes (color/size/material/warranty) + options ensured." . PHP_EOL;
    }

    private function _backfill_family()
    {
        $family = $this->db->get_where('attribute_families', ['code' => 'default'])->row();
        if ($family) {
            $this->db->query("UPDATE `products` SET `attribute_family_id` = ? WHERE `attribute_family_id` IS NULL", [(int) $family->id]);
        }
        echo "products backfilled to default family." . PHP_EOL;
    }

    private function _seed_demo_values()
    {
        // Assign color/size/material to a few existing simple products so the
        // storefront facets have data. Only runs where the product has no value yet.
        $this->ci->load->helper('eav');
        $color = $this->db->get_where('attributes', ['code' => 'color'])->row();
        $size  = $this->db->get_where('attributes', ['code' => 'size'])->row();
        $mat   = $this->db->get_where('attributes', ['code' => 'material'])->row();
        if (!$color || !$size || !$mat) {
            return;
        }
        $color_opts = $this->_options_by_label((int) $color->id);
        $size_opts  = $this->_options_by_label((int) $size->id);
        $mat_opts   = $this->_options_by_label((int) $mat->id);

        $products = $this->db->select('id')->where('deleted_at', null)
            ->where("(product_type IS NULL OR product_type = 'simple')", null, false)
            ->order_by('id', 'ASC')->limit(6)->get('products')->result_array();

        $combos = [
            ['Red', 'M', 'Cotton'], ['Blue', 'L', 'Leather'], ['Black', 'S', 'Plastic'],
            ['White', 'XL', 'Metal'], ['Green', 'M', 'Wood'], ['Red', 'L', 'Cotton'],
        ];
        $i = 0;
        foreach ($products as $p) {
            $c = $combos[$i % count($combos)];
            $this->_set_select_value((int) $p['id'], (int) $color->id, $color_opts[$c[0]] ?? null);
            $this->_set_select_value((int) $p['id'], (int) $size->id, $size_opts[$c[1]] ?? null);
            $this->_set_select_value((int) $p['id'], (int) $mat->id, $mat_opts[$c[2]] ?? null);
            $i++;
        }
        echo "demo attribute values assigned to " . count($products) . " products." . PHP_EOL;
    }

    private function _options_by_label($attribute_id)
    {
        $out = [];
        foreach ($this->db->where('attribute_id', $attribute_id)->get('attribute_options')->result_array() as $o) {
            $out[$o['label']] = (int) $o['id'];
        }
        return $out;
    }

    private function _set_select_value($product_id, $attribute_id, $option_id)
    {
        if (!$option_id) {
            return;
        }
        $uid = eav_unique_id(null, null, $product_id, $attribute_id);
        if ($this->db->get_where('product_attribute_values', ['unique_id' => $uid])->row()) {
            return; // already set
        }
        $this->db->insert('product_attribute_values', [
            'product_id'    => $product_id,
            'attribute_id'  => $attribute_id,
            'integer_value' => $option_id,
            'unique_id'     => $uid,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    private function _refresh_sidebar()
    {
        try {
            require_once APPPATH . 'helpers/sidebar_helper.php';
            $this->ci->load->helper(['url', 'general', 'permission', 'translation']);
            if (function_exists('generate_sidebar_files')) {
                generate_sidebar_files();
                echo "Sidebar files regenerated." . PHP_EOL;
                return;
            }
        } catch (Throwable $e) {
            echo "Sidebar regen deferred: " . $e->getMessage() . PHP_EOL;
        }
        foreach (glob(APPPATH . 'views/layout/sidebar/*.php') as $file) {
            @unlink($file);
        }
    }
}
