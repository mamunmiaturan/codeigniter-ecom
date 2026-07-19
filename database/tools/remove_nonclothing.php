<?php
/**
 * One-off cleanup: convert the demo catalog into a women & children CLOTHING /
 * FASHION store. Permanently deletes the non-clothing products & categories
 * (electronics, home & kitchen, groceries, old demo "Fashion", beauty &
 * cosmetics, toys & games, baby care) and wipes the demo orders/carts that
 * referenced them.
 *
 * A full backup of every affected table was written to
 *   uploads/db_backup/backup_before_clothing_YYYYMMDD.sql
 * before this was produced — restore from there if needed.
 *
 * Run once from the project root:
 *   & "C:\Users\MamunMiaTuran\.config\herd\bin\php84\php.exe" database/tools/remove_nonclothing.php
 */

$host = getenv('DB_HOSTNAME') ?: '127.0.0.1';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD');
$db   = getenv('DB_DATABASE') ?: 'codeigniter_ecom';
if ($pass === false) {
    // fall back to .env when not run through the framework
    foreach (@file(__DIR__ . '/../../.env') ?: [] as $line) {
        if (preg_match('/^\s*DB_PASSWORD\s*=\s*(.+)\s*$/', $line, $mm)) { $pass = trim($mm[1]); }
    }
}

$m = new mysqli($host, $user, $pass, $db);
if ($m->connect_errno) { fwrite(STDERR, "DB connect failed: {$m->connect_error}\n"); exit(1); }

$removeCats = [1, 2, 3, 4, 14, 19, 20]; // Electronics, Fashion(demo), Home&Kitchen, Groceries, Beauty, Toys, Baby Care
$inCats = implode(',', $removeCats);

$m->query('SET FOREIGN_KEY_CHECKS=0');
$m->begin_transaction();
try {
    // Products belonging to the removed categories (plus any orphan/null-category rows).
    $rem = [];
    $r = $m->query("SELECT id FROM products WHERE category_id IN ($inCats) OR category_id IS NULL");
    while ($o = $r->fetch_object()) { $rem[] = (int) $o->id; }
    $inRem = implode(',', $rem ?: [0]);
    echo 'Removing ' . count($rem) . " products in categories [$inCats]\n";

    // 1) Wipe demo transactional data (every order/cart references the old catalog).
    $trans = ['order_items', 'order_invoices', 'order_refunds', 'order_shipments',
        'order_status_history', 'order_stock_allocations', 'customer_downloads',
        'return_items', 'return_requests', 'orders', 'cart_items', 'carts', 'wishlists'];
    foreach ($trans as $t) {
        if ($m->query("SHOW TABLES LIKE '$t'")->num_rows) {
            $m->query("DELETE FROM `$t`");
            echo "  cleared $t ({$m->affected_rows})\n";
        }
    }

    // 2) Catalog child rows for the removed products (FK-constrained + a few extras).
    $childP = ['catalog_rule_product_prices', 'product_attribute_values', 'product_super_attributes',
        'product_variants', 'product_inventories', 'product_reviews', 'product_images',
        'product_downloads', 'product_bundle_option_products', 'product_bundle_options', 'flash_sale_items'];
    foreach ($childP as $t) {
        if ($m->query("SHOW TABLES LIKE '$t'")->num_rows) {
            $m->query("DELETE FROM `$t` WHERE product_id IN ($inRem)");
            echo "  $t -{$m->affected_rows}\n";
        }
    }
    $m->query("DELETE FROM product_grouped_items WHERE product_id IN ($inRem) OR associated_product_id IN ($inRem)");
    echo "  product_grouped_items -{$m->affected_rows}\n";

    // 3) The products themselves, then the empty categories.
    $m->query("DELETE FROM products WHERE id IN ($inRem)");
    echo "Deleted products: {$m->affected_rows}\n";
    $m->query("DELETE FROM categories WHERE id IN ($inCats)");
    echo "Deleted categories: {$m->affected_rows}\n";

    $m->commit();
    $m->query('SET FOREIGN_KEY_CHECKS=1');
    echo "\nCOMMIT OK\n";
    echo 'Products left:   ' . $m->query('SELECT COUNT(*) c FROM products')->fetch_object()->c . "\n";
    echo 'Categories left: ' . $m->query('SELECT COUNT(*) c FROM categories')->fetch_object()->c . "\n";
    $orph = $m->query('SELECT COUNT(*) c FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE c.id IS NULL')->fetch_object()->c;
    echo "Orphan products (bad category): $orph\n";
} catch (Throwable $e) {
    $m->rollback();
    $m->query('SET FOREIGN_KEY_CHECKS=1');
    fwrite(STDERR, 'ROLLBACK: ' . $e->getMessage() . "\n");
    exit(1);
}
