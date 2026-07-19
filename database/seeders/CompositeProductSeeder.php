<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog (Composite Product Types)
 * @author   : Mamun Mia Turan
 * @filename : CompositeProductSeeder.php
 *
 * Idempotent bootstrap for the composite product types ported from Bagisto —
 * configurable (variant parent), grouped (list of simple products) and bundle
 * (dynamic kit of components):
 *   php index.php migrate seed CompositeProductSeeder
 *
 * Additive: widens products.product_type ENUM, adds the composite relation
 * tables, and cart_items.meta / order_items.meta (bundle component storage).
 * The verified simple/virtual/downloadable + product_variants + effective_price
 * pipeline is untouched. Seeds one demo configurable product with variants.
 */
class CompositeProductSeeder extends Seeder
{
    public function run()
    {
        $this->_widen_enum();
        $this->_create_tables();
        $this->_add_meta_columns();
        $this->_seed_configurable_demo();
        $this->_seed_grouped_demo();
        $this->_seed_bundle_demo();
        echo "CompositeProductSeeder finished." . PHP_EOL;
    }

    private function _widen_enum()
    {
        $col = $this->db->query("SHOW COLUMNS FROM `products` WHERE Field = 'product_type'")->row();
        if ($col && stripos($col->Type, 'configurable') === false) {
            $this->db->query("ALTER TABLE `products` MODIFY COLUMN `product_type` ENUM('simple','virtual','downloadable','configurable','grouped','bundle') NOT NULL DEFAULT 'simple'");
        }
        echo "products.product_type widened (configurable/grouped/bundle)." . PHP_EOL;
    }

    private function _create_tables()
    {
        // Which is_configurable attributes are a configurable product's axes.
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `product_super_attributes` (
  `product_id` int NOT NULL,
  `attribute_id` int NOT NULL,
  PRIMARY KEY (`product_id`, `attribute_id`),
  KEY `idx_psa_attr` (`attribute_id`),
  CONSTRAINT `fk_psa_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_psa_attr` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        // Grouped: a curated list of associated simple products.
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `product_grouped_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `associated_product_id` int NOT NULL,
  `qty` int NOT NULL DEFAULT 1,
  `sort_order` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_grouped_pair` (`product_id`, `associated_product_id`),
  KEY `idx_grouped_assoc` (`associated_product_id`),
  CONSTRAINT `fk_grouped_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grouped_assoc` FOREIGN KEY (`associated_product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        // Bundle: option groups (select/radio/checkbox/multiselect).
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `product_bundle_options` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `label` varchar(150) NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'select',
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_bundle_opt_product` (`product_id`),
  CONSTRAINT `fk_bundle_opt_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

        // Bundle: the simple products offered within each option group.
        $this->db->query(<<<'SQL'
CREATE TABLE IF NOT EXISTS `product_bundle_option_products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bundle_option_id` int NOT NULL,
  `product_id` int NOT NULL,
  `qty` int NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_bop_option` (`bundle_option_id`),
  KEY `idx_bop_product` (`product_id`),
  CONSTRAINT `fk_bop_option` FOREIGN KEY (`bundle_option_id`) REFERENCES `product_bundle_options` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bop_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
        echo "composite relation tables ensured." . PHP_EOL;
    }

    private function _add_meta_columns()
    {
        // Bundle components selected for a cart/order line (JSON list of
        // {product_id, variant_id, qty}). NULL for every non-bundle line.
        if (!$this->db->field_exists('meta', 'cart_items')) {
            $this->db->query("ALTER TABLE `cart_items` ADD COLUMN `meta` longtext NULL AFTER `unit_price`");
        }
        if (!$this->db->field_exists('meta', 'order_items')) {
            $this->db->query("ALTER TABLE `order_items` ADD COLUMN `meta` longtext NULL AFTER `line_total`");
        }
        echo "cart_items.meta / order_items.meta ensured." . PHP_EOL;
    }

    private function _seed_configurable_demo()
    {
        $slug = 'classic-t-shirt';
        if ($this->db->get_where('products', ['slug' => $slug])->row()) {
            echo "demo configurable product already present." . PHP_EOL;
            return;
        }
        $now = date('Y-m-d H:i:s');
        $cat = $this->db->get_where('categories', ['slug' => 'electronics'])->row();
        $fam = $this->db->get_where('attribute_families', ['code' => 'default'])->row();

        $this->db->insert('products', [
            'name'                => 'Classic T-Shirt (Demo)',
            'slug'                => $slug,
            'product_type'        => 'configurable',
            'sku'                 => 'TSHIRT-CFG',
            'category_id'         => $cat ? (int) $cat->id : null,
            'attribute_family_id' => $fam ? (int) $fam->id : null,
            'short_description'   => 'A configurable t-shirt — pick your colour and size.',
            'price'               => 0,
            'currency'            => 'BDT',
            'stock_quantity'      => 0,
            'stock_status'        => 'in_stock',
            'has_variants'        => 1,
            'is_featured'         => 0,
            'status'              => 'Active',
            'created_at'          => $now,
        ]);
        $pid = (int) $this->db->insert_id();

        // Variant axes: link the is_configurable color + size EAV attributes.
        foreach (['color', 'size'] as $code) {
            $a = $this->db->get_where('attributes', ['code' => $code])->row();
            if ($a) {
                $this->db->query("INSERT IGNORE INTO `product_super_attributes` (product_id, attribute_id) VALUES (?, ?)", [$pid, (int) $a->id]);
            }
        }

        // Four variants (Color x Size); attributes JSON keyed by attribute name.
        $variants = [
            ['Red',  'S', 'TSHIRT-R-S', 500, 10],
            ['Red',  'M', 'TSHIRT-R-M', 550, 8],
            ['Blue', 'S', 'TSHIRT-B-S', 500, 5],
            ['Blue', 'M', 'TSHIRT-B-M', 550, 0],
        ];
        foreach ($variants as $v) {
            $this->db->insert('product_variants', [
                'product_id'     => $pid,
                'name'           => $v[0] . ' / ' . $v[1],
                'sku'            => $v[2],
                'price'          => $v[3],
                'stock_quantity' => $v[4],
                'attributes'     => json_encode(['Color' => $v[0], 'Size' => $v[1]]),
                'status'         => 'Active',
                'created_at'     => $now,
            ]);
        }
        echo "demo configurable product 'classic-t-shirt' + 4 variants seeded." . PHP_EOL;
    }

    private function _seed_grouped_demo()
    {
        $slug = 'starter-bundle-group';
        if ($this->db->get_where('products', ['slug' => $slug])->row()) {
            echo "demo grouped product already present." . PHP_EOL;
            return;
        }
        // Pick 3 existing simple products with a price to associate.
        $simples = $this->db->select('id')->where('deleted_at', null)
            ->where("(product_type IS NULL OR product_type = 'simple')", null, false)
            ->where('status', 'Active')->where('price >', 0)
            ->order_by('id', 'ASC')->limit(3)->get('products')->result_array();
        if (count($simples) < 2) {
            echo "not enough simple products for a grouped demo; skipped." . PHP_EOL;
            return;
        }
        $now = date('Y-m-d H:i:s');
        $cat = $this->db->get_where('categories', ['slug' => 'electronics'])->row();
        $this->db->insert('products', [
            'name'              => 'Essentials Collection (Demo)',
            'slug'              => $slug,
            'product_type'      => 'grouped',
            'sku'               => 'GRP-0001',
            'category_id'       => $cat ? (int) $cat->id : null,
            'short_description' => 'A hand-picked collection — add any items you like to your cart.',
            'price'             => 0,
            'currency'          => 'BDT',
            'stock_quantity'    => 0,
            'stock_status'      => 'in_stock',
            'status'            => 'Active',
            'created_at'        => $now,
        ]);
        $pid = (int) $this->db->insert_id();
        $sort = 1;
        foreach ($simples as $s) {
            $this->db->insert('product_grouped_items', [
                'product_id'            => $pid,
                'associated_product_id' => (int) $s['id'],
                'qty'                   => 1,
                'sort_order'            => $sort++,
            ]);
        }
        echo "demo grouped product 'essentials-collection' + " . count($simples) . " items seeded." . PHP_EOL;
    }

    private function _seed_bundle_demo()
    {
        $slug = 'home-office-kit';
        if ($this->db->get_where('products', ['slug' => $slug])->row()) {
            echo "demo bundle product already present." . PHP_EOL;
            return;
        }
        // Need at least 4 simple priced products for two option groups.
        $simples = $this->db->select('id, price')->where('deleted_at', null)
            ->where("(product_type IS NULL OR product_type = 'simple')", null, false)
            ->where('status', 'Active')->where('price >', 0)
            ->order_by('id', 'ASC')->limit(4)->get('products')->result_array();
        if (count($simples) < 4) {
            echo "not enough simple products for a bundle demo; skipped." . PHP_EOL;
            return;
        }
        $now = date('Y-m-d H:i:s');
        $cat = $this->db->get_where('categories', ['slug' => 'electronics'])->row();
        $this->db->insert('products', [
            'name'              => 'Home Office Starter Kit (Demo)',
            'slug'              => $slug,
            'product_type'      => 'bundle',
            'sku'               => 'BND-0001',
            'category_id'       => $cat ? (int) $cat->id : null,
            'short_description' => 'Build your kit — pick a device and add accessories. Priced from the components you choose.',
            'price'             => 0,
            'currency'          => 'BDT',
            'stock_quantity'    => 0,
            'stock_status'      => 'in_stock',
            'status'            => 'Active',
            'created_at'        => $now,
        ]);
        $pid = (int) $this->db->insert_id();

        // Option 1 (required, radio): pick one device.
        $this->db->insert('product_bundle_options', ['product_id' => $pid, 'label' => 'Choose a device', 'type' => 'radio', 'is_required' => 1, 'sort_order' => 1]);
        $opt1 = (int) $this->db->insert_id();
        $this->db->insert('product_bundle_option_products', ['bundle_option_id' => $opt1, 'product_id' => (int) $simples[0]['id'], 'qty' => 1, 'is_default' => 1, 'sort_order' => 1]);
        $this->db->insert('product_bundle_option_products', ['bundle_option_id' => $opt1, 'product_id' => (int) $simples[1]['id'], 'qty' => 1, 'is_default' => 0, 'sort_order' => 2]);

        // Option 2 (optional, checkbox): add accessories.
        $this->db->insert('product_bundle_options', ['product_id' => $pid, 'label' => 'Add accessories', 'type' => 'checkbox', 'is_required' => 0, 'sort_order' => 2]);
        $opt2 = (int) $this->db->insert_id();
        $this->db->insert('product_bundle_option_products', ['bundle_option_id' => $opt2, 'product_id' => (int) $simples[2]['id'], 'qty' => 1, 'is_default' => 0, 'sort_order' => 1]);
        $this->db->insert('product_bundle_option_products', ['bundle_option_id' => $opt2, 'product_id' => (int) $simples[3]['id'], 'qty' => 2, 'is_default' => 0, 'sort_order' => 2]);

        echo "demo bundle product 'home-office-kit' + 2 options seeded." . PHP_EOL;
    }
}
