<?php
/**
 * One-off: reorganise the storefront catalog into type-based women's groups and
 * gender-separated kids groups.
 *
 * Result (top-level order):
 *   Women's Clothing  (parent) -> Sarees, Salwar Kameez, Kurtis & Tops, Winterwear
 *   Women's Footwear  (leaf)
 *   Women's Accessories (parent) -> Handbags & Purses, Jewelry
 *   Girls  (leaf)   Boys  (leaf)   Baby  (leaf)
 *   Kids' Footwear (leaf)   Kids' Accessories (leaf)
 *
 * Run once from the project root:
 *   & "C:\Users\MamunMiaTuran\.config\herd\bin\php84\php.exe" database/tools/restructure_categories.php
 */
$host = getenv('DB_HOSTNAME') ?: '127.0.0.1';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD');
$db   = getenv('DB_DATABASE') ?: 'codeigniter_ecom';
if ($pass === false) {
    foreach (@file(__DIR__ . '/../../.env') ?: [] as $line) {
        if (preg_match('/^\s*DB_PASSWORD\s*=\s*(.+)\s*$/', $line, $mm)) { $pass = trim($mm[1]); }
    }
}
$m = new mysqli($host, $user, $pass, $db);
if ($m->connect_errno) { fwrite(STDERR, "DB connect failed: {$m->connect_error}\n"); exit(1); }

$now = date('Y-m-d H:i:s');
$m->begin_transaction();
try {
    // Helper: fetch a category id by slug.
    $idOf = function ($slug) use ($m) {
        $r = $m->query("SELECT id FROM categories WHERE slug = '" . $m->real_escape_string($slug) . "' LIMIT 1");
        $row = $r->fetch_object();
        return $row ? (int) $row->id : 0;
    };
    // Helper: create a top-level parent category if missing, return its id.
    $ensureParent = function ($name, $slug, $sort, $icon) use ($m, $now, $idOf) {
        $id = $idOf($slug);
        if ($id) {
            $m->query("UPDATE categories SET name='" . $m->real_escape_string($name) . "', parent_id=NULL, sort_order=$sort, status='Active', deleted_at=NULL WHERE id=$id");
            return $id;
        }
        $m->query("INSERT INTO categories (name, slug, parent_id, icon, is_featured, sort_order, status, created_at) VALUES ("
            . "'" . $m->real_escape_string($name) . "', '" . $m->real_escape_string($slug) . "', NULL, "
            . ($icon ? "'" . $m->real_escape_string($icon) . "'" : "NULL") . ", 1, $sort, 'Active', '$now')");
        return (int) $m->insert_id;
    };
    $reparent = function ($slug, $parentId, $sort) use ($m, $idOf) {
        $id = $idOf($slug);
        if ($id) { $m->query("UPDATE categories SET parent_id=$parentId, sort_order=$sort WHERE id=$id"); }
        return $id;
    };
    $promote = function ($slug, $newName, $sort, $icon) use ($m, $idOf) {
        $id = $idOf($slug);
        if (!$id) { return 0; }
        $set = "parent_id=NULL, sort_order=$sort";
        if ($newName !== null) { $set .= ", name='" . $m->real_escape_string($newName) . "'"; }
        if ($icon !== null)    { $set .= ", icon='" . $m->real_escape_string($icon) . "'"; }
        $m->query("UPDATE categories SET $set WHERE id=$id");
        return $id;
    };

    // 1) New type-based women's parents.
    $wClothing = $ensureParent("Women's Clothing", 'womens-clothing', 1, 'bag');
    $wAcc      = $ensureParent("Women's Accessories", 'womens-accessories', 3, 'handbag');

    // 2) Re-parent women's sub-categories.
    $reparent('sarees',           $wClothing, 1);
    $reparent('salwar-kameez',    $wClothing, 2);
    $reparent('kurtis-tops',      $wClothing, 3);
    $reparent('women-winterwear', $wClothing, 4);
    $reparent('handbags-purses',  $wAcc, 1);
    $reparent('jewelry',          $wAcc, 2);

    // 3) Promote the remaining categories to top level (boys/girls/baby separated).
    $promote('women-footwear', "Women's Footwear", 2, 'bag-check');
    $girls = $promote('girls-clothing',  'Girls', 4, 'gender-female');
    $boys  = $promote('boys-clothing',   'Boys', 5, 'gender-male');
    $baby  = $promote('baby-clothing',   'Baby', 6, 'emoji-smile');
    $promote('kids-footwear',    "Kids' Footwear", 7, 'bag-check');
    $promote('kids-accessories', "Kids' Accessories", 8, 'backpack');

    // 4) Move the clearly-gendered kids products into Girls / Boys / Baby.
    $mv = function ($productSlug, $catId) use ($m) {
        if ($catId) { $m->query("UPDATE products SET category_id=$catId WHERE slug='" . $m->real_escape_string($productSlug) . "'"); }
    };
    $mv('girls-party-shoes',   $girls);
    $mv('hair-band-clip-set',  $girls);
    $mv('boys-sandals',        $boys);
    $mv('baby-soft-booties',   $baby);

    // 5) Remove the now-empty old parents.
    foreach (['womens-fashion', 'kids-baby'] as $slug) {
        $r = $m->query("SELECT id FROM categories WHERE slug='" . $m->real_escape_string($slug) . "'");
        if ($row = $r->fetch_object()) {
            $id = (int) $row->id;
            // safety: only delete if it has no remaining children
            $c = $m->query("SELECT COUNT(*) c FROM categories WHERE parent_id=$id")->fetch_object()->c;
            if ((int) $c === 0) { $m->query("DELETE FROM categories WHERE id=$id"); }
        }
    }

    $m->commit();
    echo "COMMIT OK\n\n";
    // Report the new tree.
    $r = $m->query("SELECT c.id,c.name,c.slug,c.parent_id,c.sort_order,(SELECT COUNT(*) FROM products WHERE category_id=c.id) pc FROM categories c ORDER BY c.parent_id IS NOT NULL, COALESCE(c.parent_id,c.id), c.sort_order, c.id");
    while ($o = $r->fetch_object()) {
        $prefix = $o->parent_id ? '    - ' : '> ';
        printf("%s%-24s (slug=%s, products=%d)\n", $prefix, $o->name, $o->slug, $o->pc);
    }
} catch (Throwable $e) {
    $m->rollback();
    fwrite(STDERR, 'ROLLBACK: ' . $e->getMessage() . "\n");
    exit(1);
}
