<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog (Women & Kids demo catalog)
 * @author   : Mamun Mia Turan
 * @filename : WomenChildCatalogSeeder.php
 *
 * Idempotent bootstrap that fills the catalog with a realistic Women & Children
 * storefront: 2 parent categories + 15 sub-categories, ~19 brands and 100+
 * products carrying real names, prices (BDT), descriptions and tags.
 *
 *   php index.php migrate seed WomenChildCatalogSeeder
 *
 * "Real" imagery: for every category and product the seeder downloads a real,
 * keyword-matched photo from LoremFlickr (https://loremflickr.com) into
 * uploads/catalog/{category,product}/ and stores the local filename in the
 * `image` / `thumbnail` column — so every existing render path (which resolves
 * base_url('uploads/catalog/...')) works with zero code changes.
 *
 * Idempotency:
 *   - categories/brands/products are inserted only when their slug is absent;
 *   - images are downloaded only when the target file does not already exist;
 *   - if a download fails (offline), a bundled product-*.webp is used as a
 *     fallback so the thumbnail is never broken.
 *
 * Requires the `products` table (CatalogSeeder) and — for the optional
 * tags/label/product_type columns — ProductEnhancementsSeeder / ProductTypeSeeder;
 * every optional column write is guarded by field_exists().
 */
class WomenChildCatalogSeeder extends Seeder
{
    private $img_ok = 0;
    private $img_fail = 0;
    private $fallbacks = ['product-1.webp', 'product-2.webp', 'product-3.webp', 'product-4.webp',
        'product-5.webp', 'product-6.webp', 'product-7.webp', 'product-8.webp',
        'product-9.webp', 'product-10.webp', 'product-11.webp', 'product-12.webp'];
    private $fb_i = 0;
    private $product_cols = null;

    public function run()
    {
        $this->_ensure_tables();
        $this->_ensure_dirs();
        $parents = $this->_seed_parent_categories();
        $catmap  = $this->_seed_categories($parents);
        $brands  = $this->_seed_brands();
        $this->_seed_products($catmap, $brands);
        echo "WomenChildCatalogSeeder finished — images: {$this->img_ok} downloaded, "
            . "{$this->img_fail} fell back to bundled art." . PHP_EOL;
    }

    /* --------------------------------------------------------------------- */
    /*  Schema (defensive — CatalogSeeder normally already ran)              */
    /* --------------------------------------------------------------------- */

    private function _ensure_tables()
    {
        if ($this->db->table_exists('products') && $this->db->table_exists('categories')
            && $this->db->table_exists('brands')) {
            return;
        }
        // Minimal DDL mirroring CatalogSeeder so this seeder can run stand-alone.
        $this->db->query("CREATE TABLE IF NOT EXISTS `categories` (
          `id` int NOT NULL AUTO_INCREMENT, `name` varchar(150) NOT NULL, `slug` varchar(180) NOT NULL,
          `parent_id` int DEFAULT NULL, `description` text DEFAULT NULL, `image` varchar(255) DEFAULT NULL,
          `icon` varchar(100) DEFAULT NULL, `is_featured` tinyint(1) NOT NULL DEFAULT 0,
          `sort_order` int NOT NULL DEFAULT 0, `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
          `meta_title` varchar(255) DEFAULT NULL, `meta_description` varchar(500) DEFAULT NULL,
          `created_by` int DEFAULT NULL, `updated_by` int DEFAULT NULL,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` datetime DEFAULT NULL,
          `deleted_at` datetime DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `uk_categories_slug` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $this->db->query("CREATE TABLE IF NOT EXISTS `brands` (
          `id` int NOT NULL AUTO_INCREMENT, `name` varchar(150) NOT NULL, `slug` varchar(180) NOT NULL,
          `logo` varchar(255) DEFAULT NULL, `description` text DEFAULT NULL, `website` varchar(255) DEFAULT NULL,
          `is_featured` tinyint(1) NOT NULL DEFAULT 0, `sort_order` int NOT NULL DEFAULT 0,
          `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active', `meta_title` varchar(255) DEFAULT NULL,
          `meta_description` varchar(500) DEFAULT NULL, `created_by` int DEFAULT NULL, `updated_by` int DEFAULT NULL,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` datetime DEFAULT NULL,
          `deleted_at` datetime DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `uk_brands_slug` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $this->db->query("CREATE TABLE IF NOT EXISTS `products` (
          `id` int NOT NULL AUTO_INCREMENT, `name` varchar(255) NOT NULL, `slug` varchar(280) NOT NULL,
          `sku` varchar(100) DEFAULT NULL, `category_id` int DEFAULT NULL, `brand_id` int DEFAULT NULL,
          `short_description` varchar(500) DEFAULT NULL, `description` longtext DEFAULT NULL,
          `price` decimal(12,2) NOT NULL DEFAULT 0.00, `special_price` decimal(12,2) DEFAULT NULL,
          `cost_price` decimal(12,2) DEFAULT NULL, `currency` char(3) NOT NULL DEFAULT 'BDT',
          `stock_quantity` int NOT NULL DEFAULT 0,
          `stock_status` enum('in_stock','out_of_stock','pre_order') NOT NULL DEFAULT 'in_stock',
          `unit` varchar(50) DEFAULT NULL, `weight` decimal(10,2) DEFAULT NULL,
          `has_variants` tinyint(1) NOT NULL DEFAULT 0, `thumbnail` varchar(255) DEFAULT NULL,
          `is_featured` tinyint(1) NOT NULL DEFAULT 0, `status` enum('Draft','Active','Inactive') NOT NULL DEFAULT 'Draft',
          `meta_title` varchar(255) DEFAULT NULL, `meta_description` varchar(500) DEFAULT NULL,
          `created_by` int DEFAULT NULL, `updated_by` int DEFAULT NULL,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` datetime DEFAULT NULL,
          `deleted_at` datetime DEFAULT NULL, PRIMARY KEY (`id`), UNIQUE KEY `uk_products_slug` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    private function _ensure_dirs()
    {
        foreach (['category', 'brand', 'product'] as $sub) {
            $dir = FCPATH . 'uploads/catalog/' . $sub . '/';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }
    }

    /* --------------------------------------------------------------------- */
    /*  Categories                                                            */
    /* --------------------------------------------------------------------- */

    private function _seed_parent_categories()
    {
        $now = date('Y-m-d H:i:s');
        $parents = [
            ['name' => "Women's Fashion", 'slug' => 'womens-fashion', 'kw' => 'women,fashion,clothing', 'sort' => 1],
            ['name' => 'Kids & Baby',     'slug' => 'kids-baby',      'kw' => 'kids,children,clothing', 'sort' => 2],
        ];
        $map = [];
        foreach ($parents as $p) {
            $row = $this->db->get_where('categories', ['slug' => $p['slug']])->row();
            if (!$row) {
                $this->db->insert('categories', [
                    'name' => $p['name'], 'slug' => $p['slug'], 'parent_id' => null,
                    'description' => $p['name'] . ' — curated collection.',
                    'is_featured' => 1, 'sort_order' => $p['sort'], 'status' => 'Active',
                    'image' => $this->_image('category', $p['slug'], $p['kw']),
                    'created_at' => $now,
                ]);
                $id = (int) $this->db->insert_id();
            } else {
                $id = (int) $row->id;
                if (empty($row->image)) {
                    $this->db->where('id', $id)->update('categories',
                        ['image' => $this->_image('category', $p['slug'], $p['kw'])]);
                }
            }
            $map[$p['slug']] = $id;
        }
        return $map;
    }

    private function _seed_categories($parents)
    {
        $now = date('Y-m-d H:i:s');
        $map = [];
        $sort = 10;
        foreach ($this->_catalog() as $g) {
            $slug = $g['slug'];
            $row = $this->db->get_where('categories', ['slug' => $slug])->row();
            if (!$row) {
                $this->db->insert('categories', [
                    'name'        => $g['name'],
                    'slug'        => $slug,
                    'parent_id'   => $parents[$g['parent']] ?? null,
                    'description' => $g['desc'],
                    'is_featured' => !empty($g['featured']) ? 1 : 0,
                    'sort_order'  => $sort++,
                    'status'      => 'Active',
                    'image'       => $this->_image('category', $slug, $g['kw']),
                    'meta_title'  => $g['name'],
                    'created_at'  => $now,
                ]);
                $map[$slug] = (int) $this->db->insert_id();
            } else {
                $map[$slug] = (int) $row->id;
                if (empty($row->image)) {
                    $this->db->where('id', $row->id)->update('categories',
                        ['image' => $this->_image('category', $slug, $g['kw'])]);
                }
            }
        }
        echo "Categories ensured (" . count($map) . " sub-categories)." . PHP_EOL;
        return $map;
    }

    /* --------------------------------------------------------------------- */
    /*  Brands                                                                */
    /* --------------------------------------------------------------------- */

    private function _seed_brands()
    {
        $now = date('Y-m-d H:i:s');
        $brands = [
            ['Aarong', 'aarong', 'Bangladesh’s leading lifestyle & ethnic fashion house.'],
            ['Sailor', 'sailor', 'Contemporary fashion by Epyllion Group.'],
            ['Yellow', 'yellow', 'Trendy western & fusion wear from Beximco.'],
            ['Le Reve', 'le-reve', 'Fashion-forward clothing & accessories brand.'],
            ["Cat's Eye", 'cats-eye', 'Premium apparel & accessories.'],
            ['Ecstasy', 'ecstasy', 'Urban fashion label for men & women.'],
            ['Rang Bangladesh', 'rang-bangladesh', 'Traditional Bengali handloom & fusion wear.'],
            ['Kay Kraft', 'kay-kraft', 'Heritage-inspired ethnic fashion.'],
            ["Anjan's", 'anjans', 'Boutique ethnic wear for the whole family.'],
            ['Infinity', 'infinity', 'Everyday fashion megastore brand.'],
            ['Deshidosh', 'deshidosh', 'Deshi handcrafted apparel collective.'],
            ['Mothercare', 'mothercare', 'Global maternity, baby & kids essentials.'],
            ['Chicco', 'chicco', 'Italian baby care & feeding specialist.'],
            ["Johnson's Baby", 'johnsons-baby', 'Trusted baby toiletries & skincare.'],
            ['LEGO', 'lego', 'Iconic building-block construction toys.'],
            ['Funskool', 'funskool', 'Educational & fun toys for children.'],
            ['Pampers', 'pampers', 'Baby diapers & wipes.'],
            ['Baby Zone', 'baby-zone', 'Affordable baby clothing & gear.'],
            ['Lulu Kids', 'lulu-kids', 'Playful footwear & accessories for kids.'],
        ];
        $map = [];
        $sort = 1;
        foreach ($brands as $b) {
            list($name, $slug, $desc) = $b;
            $row = $this->db->get_where('brands', ['slug' => $slug])->row();
            if (!$row) {
                $this->db->insert('brands', [
                    'name' => $name, 'slug' => $slug, 'description' => $desc,
                    'is_featured' => $sort <= 6 ? 1 : 0, 'sort_order' => $sort,
                    'status' => 'Active', 'created_at' => $now,
                ]);
                $map[$slug] = (int) $this->db->insert_id();
            } else {
                $map[$slug] = (int) $row->id;
            }
            $sort++;
        }
        echo "Brands ensured (" . count($map) . ")." . PHP_EOL;
        return $map;
    }

    /* --------------------------------------------------------------------- */
    /*  Products                                                              */
    /* --------------------------------------------------------------------- */

    private function _seed_products($catmap, $brands)
    {
        $now = date('Y-m-d H:i:s');
        $added = 0;
        $skipped = 0;

        foreach ($this->_catalog() as $g) {
            $cat_id = $catmap[$g['slug']] ?? null;
            $idx = 0;
            foreach ($g['items'] as $it) {
                $idx++;
                // [name, price, special|null, stock, brand_slug|null, feat, label|'', short, desc, tags]
                list($name, $price, $special, $stock, $brand_slug, $feat, $label, $sd, $desc, $tags) = $it;
                $slug = $this->_unique_slug($name);
                $existing = $this->db->get_where('products', ['slug' => $slug])->row();
                if ($existing) {
                    $skipped++;
                    continue;
                }
                $sku = strtoupper($g['prefix']) . '-' . str_pad((string) $idx, 3, '0', STR_PAD_LEFT);
                $thumb = $this->_image('product', $slug, $g['kw']);

                $data = [
                    'name'              => $name,
                    'slug'              => $slug,
                    'sku'               => $sku,
                    'category_id'       => $cat_id,
                    'brand_id'          => $brand_slug ? ($brands[$brand_slug] ?? null) : null,
                    'short_description' => $sd,
                    'description'       => $desc,
                    'price'             => $price,
                    'special_price'     => $special,
                    'cost_price'        => round($price * 0.6, 2),
                    'currency'          => 'BDT',
                    'stock_quantity'    => $stock,
                    'stock_status'      => $stock > 0 ? 'in_stock' : 'out_of_stock',
                    'unit'              => $g['unit'],
                    'weight'            => $g['weight'],
                    'thumbnail'         => $thumb,
                    'is_featured'       => $feat ? 1 : 0,
                    'status'            => 'Active',
                    'meta_title'        => $name,
                    'meta_description'  => $sd,
                    'created_by'        => 1,
                    'created_at'        => $now,
                ];
                // Optional columns (present after ProductEnhancements / ProductType seeders).
                if ($this->_has_col('tags'))         { $data['tags'] = $tags; }
                if ($this->_has_col('label') && $label !== '') { $data['label'] = $label; }
                if ($this->_has_col('product_type')) { $data['product_type'] = 'simple'; }

                $this->db->insert('products', $data);
                $added++;
            }
        }
        echo "Products ensured ({$added} new, {$skipped} already present)." . PHP_EOL;
    }

    /* --------------------------------------------------------------------- */
    /*  Helpers                                                               */
    /* --------------------------------------------------------------------- */

    private function _has_col($col)
    {
        if ($this->product_cols === null) {
            $this->product_cols = array_flip($this->db->list_fields('products'));
        }
        return isset($this->product_cols[$col]);
    }

    private function _unique_slug($name)
    {
        $this->ci->load->helper('url');
        $base = url_title($name, 'dash', true);
        if ($base === '') {
            $base = 'product';
        }
        $slug = $base;
        $i = 1;
        while ($this->db->get_where('products', ['slug' => $slug])->row()) {
            // Same product re-seeded → reuse its slug so the run stays idempotent.
            $row = $this->db->get_where('products', ['slug' => $slug])->row();
            if ($row && strcasecmp($row->name, $name) === 0) {
                return $slug;
            }
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    /**
     * Ensure a real, keyword-matched image exists for a category/product and
     * return the local filename to store. Downloads from LoremFlickr once;
     * falls back to a bundled product-*.webp when offline.
     */
    private function _image($kind, $slug, $keywords)
    {
        $dir  = FCPATH . 'uploads/catalog/' . $kind . '/';
        $file = $slug . '.jpg';
        $dest = $dir . $file;

        if (is_file($dest) && filesize($dest) > 1500) {
            return $file; // already downloaded on a previous run
        }
        // Openverse (https://openverse.org) is a Creative-Commons image *search*
        // engine, so a keyword query returns photos that actually match the
        // product name — far better than a random-tag placeholder service.
        if ($this->_download_openverse($keywords, $dest)) {
            $this->img_ok++;
            return $file;
        }
        // Offline fallback — cycle the bundled demo art so nothing renders broken.
        $this->img_fail++;
        $fb = $this->fallbacks[$this->fb_i % count($this->fallbacks)];
        $this->fb_i++;
        return $fb;
    }

    /**
     * Search Openverse for the keywords, download the first result that decodes
     * as an image, centre-crop it to 600x600 and save it to $dest.
     */
    private function _download_openverse($keywords, $dest)
    {
        if (!function_exists('curl_init') || !function_exists('imagecreatefromstring')) {
            return false;
        }
        $api = 'https://api.openverse.org/v1/images/?q=' . rawurlencode($keywords)
             . '&page_size=6&license_type=commercial&mature=false';
        $json = $this->_http_get($api);
        usleep(3300000); // stay under Openverse's 20-requests/minute anon burst limit
        if ($json === null) {
            return false;
        }
        $data = json_decode($json, true);
        foreach (($data['results'] ?? []) as $r) {
            $url = $r['url'] ?? '';
            if (!$url) {
                continue;
            }
            $img = $this->_http_get($url, 'image');
            if ($img === null || strlen($img) < 3000) {
                continue;
            }
            $src = @imagecreatefromstring($img);
            if (!$src) {
                continue;
            }
            $w = imagesx($src); $h = imagesy($src); $s = min($w, $h);
            $out = imagecreatetruecolor(600, 600);
            imagecopyresampled($out, $src, 0, 0, (int)(($w - $s) / 2), (int)(($h - $s) / 2), 600, 600, $s, $s);
            $saved = imagejpeg($out, $dest, 88);
            imagedestroy($src); imagedestroy($out);
            if ($saved) {
                return true;
            }
        }
        return false;
    }

    private function _http_get($url, $expect = null)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'EcomSeeder/1.0 (product demo images)',
        ]);
        $data  = curl_exec($ch);
        $code  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ctype = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        if ($data === false || $data === '' || $code !== 200) {
            return null;
        }
        if ($expect === 'image' && strpos($ctype, 'image') === false) {
            return null;
        }
        return $data;
    }

    /* --------------------------------------------------------------------- */
    /*  Catalog data — 15 sub-categories, 105 women & children products       */
    /* --------------------------------------------------------------------- */

    private function _catalog()
    {
        // Each product: [name, price, special|null, stock, brand, feat, label, short_desc, description, tags]
        return [
            [
                'name' => 'Sarees', 'slug' => 'sarees', 'parent' => 'womens-fashion', 'featured' => 1,
                'kw' => 'saree,fashion', 'prefix' => 'SAR', 'unit' => 'pcs', 'weight' => 0.80,
                'desc' => 'Handloom, silk and cotton sarees for every occasion.',
                'items' => [
                    ['Jamdani Handloom Saree', 5500, 4990, 18, 'aarong', 1, 'Best Seller', 'Handwoven Dhakai Jamdani with intricate motifs.', 'A genuine handwoven Dhakai Jamdani saree crafted by artisans of Rupganj, featuring traditional geometric and floral motifs on fine cotton. Comes with matching blouse piece.', 'saree,jamdani,handloom,cotton'],
                    ['Katan Silk Banarasi Saree', 7800, 6990, 12, 'rang-bangladesh', 1, 'Hot', 'Rich Katan silk with golden zari border.', 'Luxurious Katan silk saree with an elaborate golden zari anchal and border, perfect for weddings and festive evenings. 5.5 metres with blouse piece.', 'saree,silk,katan,banarasi,party'],
                    ['Half Silk Cotton Saree', 2400, 1990, 30, 'kay-kraft', 0, 'Sale', 'Lightweight half-silk for daily elegance.', 'A comfortable half-silk saree blending the sheen of silk with the breathability of cotton — ideal for office and daytime wear.', 'saree,half-silk,cotton,casual'],
                    ['Tant Cotton Saree', 1650, null, 40, 'aarong', 0, '', 'Crisp Bengali tant weave in vibrant colours.', 'Classic Bengali tant saree woven on traditional handlooms, known for its crisp texture and bold contrast borders. A summer staple.', 'saree,tant,cotton,handloom'],
                    ['Muslin Jamdani Saree', 9500, 8500, 8, 'aarong', 1, 'Premium', 'Featherlight muslin revived from heritage looms.', 'An exquisitely fine muslin Jamdani saree, reviving Bengal’s legendary weaving heritage. Ultra-soft, translucent and hand-detailed.', 'saree,muslin,jamdani,premium'],
                    ['Georgette Party Saree', 3200, 2790, 22, 'sailor', 0, 'New', 'Flowy georgette with sequin embellishment.', 'A flowing georgette saree with delicate sequin and stone work along the border and anchal — made for parties and celebrations.', 'saree,georgette,party,sequin'],
                    ['Block Print Cotton Saree', 1850, 1590, 35, 'rang-bangladesh', 0, '', 'Hand block-printed cotton in earthy tones.', 'Soft cotton saree featuring hand block-printed motifs in natural dyes. Breathable and elegant for everyday styling.', 'saree,block-print,cotton,handmade'],
                ],
            ],
            [
                'name' => 'Salwar Kameez & Three Piece', 'slug' => 'salwar-kameez', 'parent' => 'womens-fashion', 'featured' => 1,
                'kw' => 'salwar,kameez,women', 'prefix' => 'SLK', 'unit' => 'set', 'weight' => 0.70,
                'desc' => 'Stitched & unstitched three-piece sets and salwar kameez.',
                'items' => [
                    ['Unstitched Cotton Three Piece', 1750, 1490, 45, 'yellow', 0, 'Sale', 'Printed cotton kameez, dupatta & bottom.', 'A versatile unstitched three-piece set in soft printed cotton — includes kameez fabric, matching dupatta and salwar bottom, ready to tailor to your fit.', 'three-piece,cotton,unstitched,salwar'],
                    ['Embroidered Georgette Three Piece', 3600, 3190, 20, 'sailor', 1, 'Hot', 'Chest-embroidered georgette with inner lining.', 'An elegant stitched georgette three-piece with fine chest embroidery, comfortable inner lining and a chiffon dupatta — festive ready.', 'three-piece,georgette,embroidered,party'],
                    ['Printed Lawn Salwar Kameez', 2200, null, 30, 'le-reve', 0, 'New', 'Breathable lawn print for summer comfort.', 'Lightweight lawn fabric salwar kameez with all-over summer prints, keeping you cool and stylish through the day.', 'salwar,lawn,printed,summer'],
                    ['Silk Party Three Piece', 4900, 4290, 15, 'kay-kraft', 1, 'Premium', 'Lustrous silk set with karchupi work.', 'A rich silk three-piece featuring traditional karchupi hand-embroidery on the yoke, ideal for Eid and wedding festivities.', 'three-piece,silk,karchupi,eid'],
                    ['Chikankari Kurti Set', 2850, 2490, 25, 'anjans', 0, '', 'Lucknowi chikankari embroidery set.', 'Delicate Lucknowi chikankari embroidery on soft cotton, paired with matching bottom and dupatta for an understated ethnic look.', 'kurti-set,chikankari,cotton,ethnic'],
                    ['Linen Casual Three Piece', 2600, 2290, 28, 'ecstasy', 0, 'New', 'Minimal linen set for work & outings.', 'A minimalist linen three-piece with subtle prints, breathable and wrinkle-friendly for office and casual outings.', 'three-piece,linen,casual,office'],
                    ['Karchupi Eid Three Piece', 5200, 4590, 14, 'rang-bangladesh', 1, 'Festive', 'Heavy karchupi work for Eid celebrations.', 'A statement Eid three-piece with heavy karchupi and stone embellishment across the kameez, complete with churidar and dupatta.', 'three-piece,karchupi,eid,festive'],
                ],
            ],
            [
                'name' => 'Kurtis & Tops', 'slug' => 'kurtis-tops', 'parent' => 'womens-fashion', 'featured' => 1,
                'kw' => 'kurti,tunic,women', 'prefix' => 'KUR', 'unit' => 'pcs', 'weight' => 0.35,
                'desc' => 'Everyday kurtis, tunics and tops.',
                'items' => [
                    ['Printed Cotton Kurti', 1150, 990, 50, 'yellow', 0, 'Sale', 'All-over print straight-cut cotton kurti.', 'A comfortable straight-cut cotton kurti with vibrant all-over prints — a wardrobe essential for daily and casual wear.', 'kurti,cotton,printed,casual'],
                    ['Embroidered A-line Kurti', 1650, 1390, 35, 'sailor', 1, 'Hot', 'Flared A-line with neckline embroidery.', 'An A-line kurti with detailed neckline embroidery and a flattering flared hem, easy to pair with leggings or jeans.', 'kurti,a-line,embroidered'],
                    ['Rayon Straight Kurti', 990, null, 60, 'le-reve', 0, '', 'Soft rayon in solid seasonal shades.', 'A soft-drape rayon kurti in solid seasonal colours, breathable and versatile for both office and outings.', 'kurti,rayon,solid,office'],
                    ['Denim Tunic Top', 1450, 1250, 30, 'ecstasy', 0, 'New', 'Casual denim tunic with button placket.', 'A relaxed denim tunic top with a button placket and roll-up sleeves — perfect for a smart-casual everyday look.', 'top,denim,tunic,casual'],
                    ['Floral Chiffon Top', 1350, 1150, 28, 'yellow', 0, 'New', 'Flowy chiffon top with floral print.', 'A lightweight floral chiffon top with a gentle flow and feminine cut, great for parties and evening outings.', 'top,chiffon,floral,party'],
                    ['Anarkali Long Kurti', 2100, 1790, 22, 'kay-kraft', 1, '', 'Floor-grazing Anarkali silhouette.', 'A graceful floor-length Anarkali kurti with a fitted bodice and flared skirt, embellished for festive occasions.', 'kurti,anarkali,long,festive'],
                    ['Handloom Khadi Kurti', 1550, 1390, 26, 'aarong', 0, '', 'Textured khadi with wooden buttons.', 'A textured handloom khadi kurti with rustic wooden buttons — earthy, sustainable and effortlessly chic.', 'kurti,khadi,handloom,sustainable'],
                ],
            ],
            [
                'name' => "Women's Footwear", 'slug' => 'women-footwear', 'parent' => 'womens-fashion', 'featured' => 0,
                'kw' => 'women,shoes,heels', 'prefix' => 'WFW', 'unit' => 'pair', 'weight' => 0.50,
                'desc' => 'Sandals, heels and flats for women.',
                'items' => [
                    ['Leather Block Heel Sandal', 1990, 1690, 24, 'cats-eye', 1, 'Hot', 'Genuine leather with stable block heel.', 'A genuine leather block-heel sandal with cushioned footbed and adjustable ankle strap — comfort for all-day wear.', 'footwear,sandal,heel,leather'],
                    ['Casual Flat Slipper', 850, 720, 55, 'infinity', 0, 'Sale', 'Lightweight everyday flat slipper.', 'A lightweight, flexible flat slipper with a soft strap and anti-slip sole for comfortable daily use.', 'footwear,slipper,flat,casual'],
                    ['Embroidered Khussa', 1250, null, 30, 'anjans', 0, '', 'Traditional mojari with thread work.', 'A handcrafted embroidered khussa (mojari) with intricate thread work, pairing beautifully with ethnic outfits.', 'footwear,khussa,ethnic,handmade'],
                    ['Wedge Heel Pump', 1750, 1490, 20, 'cats-eye', 0, 'New', 'Comfortable wedge for extra height.', 'A closed-toe wedge pump offering height with stability, finished in a versatile matte tone.', 'footwear,wedge,pump,formal'],
                    ['Ankle Strap Sandal', 1550, 1290, 26, 'ecstasy', 0, '', 'Strappy sandal with kitten heel.', 'An elegant ankle-strap sandal on a low kitten heel — dressy yet walkable for events and dinners.', 'footwear,sandal,strap,party'],
                    ['Ballet Flat Shoe', 1100, 950, 40, 'infinity', 0, '', 'Slip-on ballerina flats.', 'Classic slip-on ballerina flats with a padded insole and bow detail, easy to dress up or down.', 'footwear,ballet,flat,women'],
                    ['Sports Walking Shoe', 2200, 1890, 32, 'infinity', 1, 'New', 'Cushioned mesh trainer for women.', 'A breathable mesh sports shoe with cushioned midsole and grippy outsole — made for walking and light workouts.', 'footwear,sports,shoe,sneaker'],
                ],
            ],
            [
                'name' => 'Handbags & Purses', 'slug' => 'handbags-purses', 'parent' => 'womens-fashion', 'featured' => 0,
                'kw' => 'handbag,purse,fashion', 'prefix' => 'HBG', 'unit' => 'pcs', 'weight' => 0.55,
                'desc' => 'Handbags, clutches and sling bags.',
                'items' => [
                    ['Leather Tote Handbag', 2900, 2490, 18, 'cats-eye', 1, 'Hot', 'Spacious leather tote for work.', 'A spacious genuine-leather tote with a laptop sleeve and multiple compartments — the perfect everyday work companion.', 'handbag,tote,leather,work'],
                    ['Embroidered Clutch Purse', 1350, 1150, 25, 'aarong', 0, '', 'Ethnic clutch with chain strap.', 'A hand-embroidered ethnic clutch with a detachable chain strap and secure magnetic closure, ideal for festive nights.', 'purse,clutch,embroidered,party'],
                    ['Crossbody Sling Bag', 1650, 1390, 30, 'sailor', 0, 'New', 'Compact sling with adjustable strap.', 'A compact crossbody sling with an adjustable strap and zip pockets — hands-free convenience for daily errands.', 'bag,sling,crossbody,casual'],
                    ['Jute Shopper Bag', 750, null, 45, 'deshidosh', 0, 'Eco', 'Eco-friendly jute market bag.', 'A sturdy eco-friendly jute shopper bag with reinforced handles — a stylish, sustainable alternative for shopping.', 'bag,jute,eco,shopper'],
                    ['Mini Backpack Purse', 1950, 1690, 22, 'infinity', 0, '', 'Trendy mini backpack for outings.', 'A trendy mini backpack purse with front zip pockets and comfortable straps — fashion meets function.', 'bag,backpack,mini,fashion'],
                    ['Formal Shoulder Bag', 2400, 1990, 16, 'cats-eye', 1, '', 'Structured shoulder bag in matte finish.', 'A structured shoulder bag with a matte pebbled finish and gold-tone hardware, refined for office and formal use.', 'handbag,shoulder,formal,leather'],
                    ['Fabric Wristlet', 650, 550, 38, 'deshidosh', 0, 'Sale', 'Handloom fabric wristlet wallet.', 'A handloom-fabric wristlet with a zip pocket and card slots — a lightweight carry for essentials.', 'purse,wristlet,fabric,handmade'],
                ],
            ],
            [
                'name' => 'Jewelry', 'slug' => 'jewelry', 'parent' => 'womens-fashion', 'featured' => 1,
                'kw' => 'jewelry,necklace,fashion', 'prefix' => 'JWL', 'unit' => 'pcs', 'weight' => 0.15,
                'desc' => 'Fashion jewelry — necklaces, earrings and more.',
                'items' => [
                    ['Kundan Necklace Set', 2800, 2390, 20, 'anjans', 1, 'Hot', 'Kundan choker with matching earrings.', 'A regal gold-plated Kundan necklace set with a choker and matching jhumka earrings — a bridal favourite.', 'jewelry,kundan,necklace,bridal'],
                    ['Gold Plated Jhumka Earrings', 950, 790, 40, 'anjans', 0, 'Sale', 'Classic dome jhumkas with bead drops.', 'Classic gold-plated jhumka earrings with delicate bead drops, lightweight for all-day comfort.', 'jewelry,earrings,jhumka,gold-plated'],
                    ['Pearl Choker Necklace', 1450, 1250, 28, 'cats-eye', 0, 'New', 'Faux-pearl choker for elegant looks.', 'A sophisticated faux-pearl choker necklace with an adjustable clasp — timeless with both western and ethnic wear.', 'jewelry,pearl,choker,necklace'],
                    ['Silver Anklet Pair', 780, null, 35, 'deshidosh', 0, '', 'Oxidised silver payel with ghungroo.', 'A pair of oxidised silver anklets (payel) with tiny ghungroo bells that chime softly as you walk.', 'jewelry,anklet,silver,ethnic'],
                    ['Bridal Tikli & Nose Pin', 1100, 890, 22, 'anjans', 0, '', 'Matching maang tikka & nath set.', 'A coordinated bridal set featuring a maang tikka (tikli) and a nose pin (nath) with fine stone detailing.', 'jewelry,tikli,nath,bridal'],
                    ['Beaded Bangle Set', 690, 590, 44, 'rang-bangladesh', 0, 'Sale', 'Colourful handmade beaded bangles.', 'A vibrant set of handmade beaded bangles that add a playful pop of colour to any ethnic outfit.', 'jewelry,bangle,beaded,handmade'],
                    ['Antique Finish Ring', 450, null, 50, 'cats-eye', 0, '', 'Adjustable oxidised statement ring.', 'An adjustable antique-finish statement ring with intricate carving — a subtle finishing touch.', 'jewelry,ring,antique,oxidised'],
                ],
            ],
            [
                'name' => "Women's Winterwear", 'slug' => 'women-winterwear', 'parent' => 'womens-fashion', 'featured' => 0,
                'kw' => 'shawl,sweater,women', 'prefix' => 'WWR', 'unit' => 'pcs', 'weight' => 0.60,
                'desc' => 'Shawls, cardigans and sweaters.',
                'items' => [
                    ['Pashmina Shawl', 2200, 1890, 25, 'aarong', 1, 'Hot', 'Soft woven pashmina wrap.', 'A luxuriously soft pashmina-blend shawl with woven detailing — warmth and elegance for the cool season.', 'winterwear,shawl,pashmina,women'],
                    ['Woolen Cardigan', 1950, 1690, 22, 'ecstasy', 0, 'New', 'Button-front knit cardigan.', 'A cosy button-front woolen cardigan with ribbed cuffs — a layering staple for chilly days.', 'winterwear,cardigan,wool,knit'],
                    ['Fleece Hoodie', 1650, 1390, 30, 'infinity', 0, 'Sale', 'Warm fleece-lined pullover hoodie.', 'A warm fleece-lined pullover hoodie with a kangaroo pocket and drawstring hood — casual winter comfort.', 'winterwear,hoodie,fleece,casual'],
                    ['Printed Blazer', 2600, 2290, 16, 'sailor', 0, '', 'Smart printed blazer for layering.', 'A smart single-button blazer in a subtle print, tailored for a sharp layered look over kurtis and tops.', 'winterwear,blazer,formal,women'],
                    ['Knitted Sweater', 1450, 1250, 28, 'ecstasy', 0, '', 'Full-sleeve knit crew sweater.', 'A soft full-sleeve knitted crew-neck sweater in solid colours — versatile everyday warmth.', 'winterwear,sweater,knit,women'],
                    ['Muffler Scarf', 550, 460, 45, 'infinity', 0, 'Sale', 'Chunky knit muffler scarf.', 'A chunky knit muffler scarf that wraps you in softness and adds a cosy accent to winter outfits.', 'winterwear,muffler,scarf,knit'],
                    ['Long Woolen Coat', 3800, 3290, 12, 'sailor', 1, 'Premium', 'Full-length tailored winter coat.', 'A full-length tailored woolen coat with a belted waist and notch lapels — elegant protection against the cold.', 'winterwear,coat,wool,premium'],
                ],
            ],
            [
                'name' => "Girls' Clothing", 'slug' => 'girls-clothing', 'parent' => 'kids-baby', 'featured' => 1,
                'kw' => 'girl,kids,dress', 'prefix' => 'GCL', 'unit' => 'pcs', 'weight' => 0.30,
                'desc' => 'Dresses and outfits for girls.',
                'items' => [
                    ['Party Frock Dress', 1450, 1250, 30, 'anjans', 1, 'Hot', 'Layered tulle party frock for girls.', 'A charming layered tulle party frock with satin bow and sequin bodice — made for birthdays and celebrations.', 'girls,frock,party,dress'],
                    ['Cotton Leggings & Top Set', 950, 820, 45, 'baby-zone', 0, 'Sale', 'Comfy printed top with leggings.', 'A soft cotton two-piece with a printed top and stretch leggings — comfy playwear for active girls.', 'girls,leggings,set,casual'],
                    ['Denim Skirt', 780, null, 38, 'sailor', 0, '', 'Adjustable-waist denim skirt.', 'A cute denim skirt with an adjustable elastic waist and front pockets — easy to mix and match.', 'girls,skirt,denim,casual'],
                    ['Printed Cotton T-shirt', 450, 390, 60, 'baby-zone', 0, '', 'Round-neck graphic tee for girls.', 'A breathable round-neck cotton t-shirt with playful graphic prints, perfect for everyday wear.', 'girls,tshirt,cotton,casual'],
                    ['Ethnic Lehenga Set', 2200, 1890, 18, 'anjans', 1, 'Festive', 'Mini lehenga choli with dupatta.', 'A festive mini lehenga choli set with embroidered blouse, flared skirt and matching dupatta for little ones.', 'girls,lehenga,ethnic,festive'],
                    ['Casual Jumpsuit', 1150, 990, 26, 'sailor', 0, 'New', 'One-piece printed jumpsuit.', 'A trendy one-piece printed jumpsuit with tie straps — a fuss-free outfit for outings and play.', 'girls,jumpsuit,casual,kids'],
                    ['Winter Sweater Dress', 1350, 1150, 22, 'mothercare', 0, '', 'Knitted long-sleeve sweater dress.', 'A cosy knitted long-sleeve sweater dress that keeps girls warm and stylish through the winter.', 'girls,sweater,dress,winter'],
                ],
            ],
            [
                'name' => "Boys' Clothing", 'slug' => 'boys-clothing', 'parent' => 'kids-baby', 'featured' => 1,
                'kw' => 'boy,kids,clothing', 'prefix' => 'BCL', 'unit' => 'pcs', 'weight' => 0.30,
                'desc' => 'Shirts, jeans and sets for boys.',
                'items' => [
                    ['Cotton Polo T-shirt', 550, 470, 55, 'baby-zone', 0, 'Sale', 'Classic collared polo for boys.', 'A classic collared cotton polo t-shirt with ribbed cuffs — smart-casual comfort for school and play.', 'boys,polo,tshirt,cotton'],
                    ['Denim Jeans', 1100, 950, 40, 'sailor', 0, '', 'Slim-fit stretch denim jeans.', 'Durable slim-fit stretch denim jeans with an adjustable waistband for a comfortable growing fit.', 'boys,jeans,denim,casual'],
                    ['Casual Check Shirt', 850, 720, 42, 'ecstasy', 0, 'New', 'Half-sleeve checked cotton shirt.', 'A breezy half-sleeve checked cotton shirt, easy to dress up or pair with shorts for a relaxed look.', 'boys,shirt,check,casual'],
                    ['Panjabi for Boys', 1250, 1090, 30, 'aarong', 1, 'Festive', 'Cotton panjabi for festive days.', 'A soft cotton panjabi with subtle embroidery on the placket — festive-ready for Eid and family occasions.', 'boys,panjabi,ethnic,eid'],
                    ['Track Suit Set', 1450, 1250, 28, 'infinity', 0, 'Hot', 'Jacket & jogger active set.', 'A comfortable two-piece track suit with a zip jacket and joggers — great for sports and casual days.', 'boys,tracksuit,active,set'],
                    ['Hooded Sweatshirt', 1150, 990, 32, 'infinity', 0, '', 'Fleece pullover hoodie for boys.', 'A warm fleece pullover hoodie with a front pocket and drawstring hood — cosy for cooler weather.', 'boys,hoodie,sweatshirt,winter'],
                    ['Half Pant & Shirt Set', 950, 820, 36, 'baby-zone', 0, 'New', 'Coordinated summer casual set.', 'A coordinated summer set with a printed shirt and matching half pants — light and playful for warm days.', 'boys,set,shirt,summer'],
                ],
            ],
            [
                'name' => 'Baby Clothing (0-2 yrs)', 'slug' => 'baby-clothing', 'parent' => 'kids-baby', 'featured' => 1,
                'kw' => 'baby,clothing,infant', 'prefix' => 'BBC', 'unit' => 'pcs', 'weight' => 0.20,
                'desc' => 'Rompers, bodysuits and sets for babies.',
                'items' => [
                    ['Cotton Romper', 650, 550, 50, 'mothercare', 1, 'Hot', 'Snap-button cotton romper.', 'A soft breathable cotton romper with easy snap buttons for quick changes — gentle on delicate baby skin.', 'baby,romper,cotton,infant'],
                    ['Baby Bodysuit Pack of 3', 950, 820, 45, 'mothercare', 0, 'Sale', 'Set of three envelope-neck bodysuits.', 'A value pack of three envelope-neck cotton bodysuits in coordinating prints — everyday newborn essentials.', 'baby,bodysuit,pack,cotton'],
                    ['Newborn Gift Set', 1650, 1390, 22, 'chicco', 1, 'New', '7-piece welcome-baby gift set.', 'A thoughtful 7-piece newborn gift set with romper, cap, mittens, booties and bib — a lovely baby-shower present.', 'baby,giftset,newborn,chicco'],
                    ['Sleep Suit', 720, null, 40, 'baby-zone', 0, '', 'Full-length footed sleep suit.', 'A cosy full-length footed sleep suit with front zip, keeping babies warm and snug through the night.', 'baby,sleepsuit,footed,cotton'],
                    ['Bib & Cap Set', 380, 320, 60, 'baby-zone', 0, 'Sale', 'Absorbent bib with matching cap.', 'A soft absorbent bib paired with a matching cotton cap — practical and adorable for feeding time.', 'baby,bib,cap,set'],
                    ['Frock for Baby Girl', 780, 690, 34, 'mothercare', 0, '', 'Frilled cotton baby frock.', 'A sweet frilled cotton frock with bloomers, gentle on skin and perfect for little-girl outings.', 'baby,frock,girl,cotton'],
                    ['Winter Baby Jacket', 990, 850, 28, 'chicco', 0, 'New', 'Padded hooded baby jacket.', 'A lightly padded hooded jacket that keeps babies warm without bulk — soft lining for extra comfort.', 'baby,jacket,winter,hooded'],
                ],
            ],
            [
                'name' => "Kids' Footwear", 'slug' => 'kids-footwear', 'parent' => 'kids-baby', 'featured' => 0,
                'kw' => 'kids,shoes,children', 'prefix' => 'KFW', 'unit' => 'pair', 'weight' => 0.35,
                'desc' => 'Shoes, sandals and booties for kids.',
                'items' => [
                    ['Kids Sneakers', 1250, 1090, 34, 'lulu-kids', 1, 'Hot', 'Velcro-strap cushioned sneakers.', 'Lightweight velcro-strap sneakers with cushioned soles and breathable uppers — easy on, easy play.', 'kids,sneakers,shoes,footwear'],
                    ['Girls Party Shoes', 950, 820, 28, 'lulu-kids', 0, 'New', 'Glittery buckle party shoes.', 'Adorable glittery party shoes with a secure buckle strap — the perfect finish for festive outfits.', 'kids,party,shoes,girls'],
                    ['Baby Soft Booties', 420, 360, 50, 'mothercare', 0, 'Sale', 'Anti-slip newborn booties.', 'Ultra-soft anti-slip booties for newborns, keeping tiny feet warm and cosy indoors.', 'baby,booties,soft,footwear'],
                    ['Boys Sandals', 780, null, 40, 'lulu-kids', 0, '', 'Durable velcro sport sandals.', 'Durable velcro sport sandals with a grippy sole and adjustable straps — built for active little feet.', 'kids,sandals,boys,footwear'],
                    ['LED Light-up Shoes', 1550, 1290, 24, 'lulu-kids', 1, 'Hot', 'Rechargeable light-up sneakers.', 'Fun rechargeable light-up sneakers with LED soles that flash with every step — a kids’ favourite.', 'kids,led,shoes,sneakers'],
                    ['School Shoes', 1150, 990, 36, 'lulu-kids', 0, '', 'Black lace-up school shoes.', 'Sturdy black lace-up school shoes with reinforced toes and cushioned insoles for all-day comfort.', 'kids,school,shoes,footwear'],
                    ['Rain Boots', 850, 720, 30, 'lulu-kids', 0, 'New', 'Waterproof printed gumboots.', 'Colourful waterproof printed gumboots that keep little feet dry through puddles and monsoon days.', 'kids,rainboots,waterproof,footwear'],
                ],
            ],
            [
                'name' => "Kids' Accessories & School", 'slug' => 'kids-accessories', 'parent' => 'kids-baby', 'featured' => 0,
                'kw' => 'kids,school,backpack', 'prefix' => 'KAC', 'unit' => 'pcs', 'weight' => 0.45,
                'desc' => 'Backpacks, bottles and school essentials.',
                'items' => [
                    ['Character School Backpack', 1250, 1090, 40, 'infinity', 1, 'Hot', 'Padded cartoon-print backpack.', 'A lightweight padded school backpack with fun cartoon prints, multiple compartments and comfy straps.', 'kids,backpack,school,bag'],
                    ['Insulated Water Bottle', 550, 470, 55, 'infinity', 0, 'Sale', 'Leak-proof 500ml kids bottle.', 'A leak-proof 500ml insulated water bottle with a flip-up straw and easy-carry loop for school and play.', 'kids,bottle,water,school'],
                    ['Steel Lunch Box', 650, 550, 45, 'infinity', 0, '', 'Compartment steel tiffin box.', 'A durable stainless-steel lunch box with separate compartments to keep meals fresh and neatly packed.', 'kids,lunchbox,tiffin,school'],
                    ['Hair Band & Clip Set', 320, 270, 60, 'baby-zone', 0, 'New', 'Colourful bows and clips set.', 'A cheerful set of soft hair bands, bows and clips to style little hair for school and parties.', 'kids,hairband,clips,girls'],
                    ['Kids Sunglasses', 380, 320, 48, 'infinity', 0, '', 'UV-protection fun-frame shades.', 'Playful UV-protection sunglasses with flexible frames — cool and safe for sunny outings.', 'kids,sunglasses,uv,accessories'],
                    ['Character Umbrella', 480, 410, 38, 'infinity', 0, 'Sale', 'Kid-size cartoon umbrella.', 'A kid-size cartoon umbrella with a safe rounded tip and easy-grip handle for rainy school days.', 'kids,umbrella,rain,accessories'],
                    ['Pencil Box Set', 420, 360, 52, 'infinity', 0, '', 'Stationery-filled pencil case.', 'A handy pencil box set filled with pencils, eraser, sharpener and scale — back-to-school ready.', 'kids,pencilbox,stationery,school'],
                ],
            ],
        ];
    }
}
