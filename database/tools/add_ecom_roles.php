<?php
/**
 * Add e-commerce staff roles + their permissions to the role/permission system.
 *
 * Adds the roles a real store needs (beyond Superman/Admin/Customer):
 *   - Store Manager      : runs day-to-day operations across all store modules
 *   - Catalog Manager    : products, categories, brands, attributes, flash sale
 *   - Order Manager      : orders, returns, shipping, fulfillment
 *   - Customer Support   : orders (assist), returns, contact messages, FAQ, reviews
 *   - Marketing Manager  : coupons, promotions, banners, blog, CMS, newsletter
 *   - Accountant         : payments, tax, reports
 *
 * Idempotent: a role that already exists (by name) is skipped. CRUD flags are
 * capped by each permission's show_view/add/edit/delete so nothing impossible is
 * granted. Run once from the project root:
 *   & "C:\Users\MamunMiaTuran\.config\herd\bin\php84\php.exe" database/tools/add_ecom_roles.php
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

// CRUD presets -> [view, add, edit, delete]
$P = [
    'F'   => [1, 1, 1, 1], // full
    'VAE' => [1, 1, 1, 0], // view + add + edit
    'VED' => [1, 0, 1, 1], // view + edit + delete
    'VE'  => [1, 0, 1, 0], // view + edit
    'V'   => [1, 0, 0, 0], // view only
];

// permission ids (from `permission`): Catalog 21-Category 22-Brand 23-Product 26-Reviews
// 36-Attributes 37-AttrFamilies 38-InventorySources | 24-Order 32-Returns | 25-Coupon
// 27-Shipping 28-Tax 29-Payment | 30-CartRules 31-CatalogRules | 33-CMS | 34-Newsletter
// 35-CustomerGroups | 39-FAQ | 40-Contact | 41-Blog | 42-Banners | 43-Reports | 44-FlashSale
$roles = [
    'Store Manager' => ['SM', [
        21 => 'F', 22 => 'F', 23 => 'F', 26 => 'F', 36 => 'F', 37 => 'F', 38 => 'F',
        24 => 'F', 32 => 'F', 25 => 'F', 27 => 'F', 28 => 'F', 29 => 'F', 30 => 'F', 31 => 'F',
        33 => 'F', 34 => 'F', 35 => 'F', 39 => 'F', 40 => 'F', 41 => 'F', 42 => 'F', 44 => 'F',
        43 => 'V',
    ]],
    'Catalog Manager' => ['CM', [
        21 => 'F', 22 => 'F', 23 => 'F', 26 => 'F', 36 => 'F', 37 => 'F', 38 => 'F',
        44 => 'F', 31 => 'F', 24 => 'V',
    ]],
    'Order Manager' => ['OM', [
        24 => 'F', 32 => 'F', 27 => 'VE', 29 => 'V', 35 => 'V', 43 => 'V',
    ]],
    'Customer Support' => ['CS', [
        24 => 'VE', 32 => 'VAE', 40 => 'F', 39 => 'F', 26 => 'VED', 35 => 'V', 41 => 'V',
    ]],
    'Marketing Manager' => ['MM', [
        25 => 'F', 30 => 'F', 34 => 'F', 42 => 'F', 41 => 'F', 33 => 'F', 44 => 'F', 39 => 'F',
    ]],
    'Accountant' => ['AC', [
        29 => 'VE', 28 => 'VE', 43 => 'V', 24 => 'V',
    ]],
];

// Cache each permission's show_* caps.
$show = [];
$r = $m->query('SELECT id, show_view, show_add, show_edit, show_delete FROM permission');
while ($o = $r->fetch_object()) {
    $show[(int) $o->id] = [(int) $o->show_view, (int) $o->show_add, (int) $o->show_edit, (int) $o->show_delete];
}

$m->begin_transaction();
try {
    $added = 0; $skipped = 0;
    foreach ($roles as $name => [$short, $perms]) {
        $exists = $m->query("SELECT id FROM roles WHERE name = '" . $m->real_escape_string($name) . "'")->fetch_object();
        if ($exists) { echo "skip (exists): $name\n"; $skipped++; continue; }

        $prefix = strtolower(str_replace(' ', '_', $name));
        $m->query("INSERT INTO roles (name, prefix, is_system, short_form) VALUES ("
            . "'" . $m->real_escape_string($name) . "', '" . $m->real_escape_string($prefix) . "', 0, '" . $m->real_escape_string($short) . "')");
        $rid = (int) $m->insert_id;

        $granted = 0;
        foreach ($perms as $pid => $code) {
            $pid = (int) $pid;
            if (!isset($show[$pid])) { continue; }
            [$v, $a, $e, $d] = $P[$code];
            [$sv, $sa, $se, $sd] = $show[$pid];
            $v &= $sv; $a &= $sa; $e &= $se; $d &= $sd; // cap by what the permission supports
            if (!($v || $a || $e || $d)) { continue; }
            $m->query("INSERT INTO user_privileges (role_id, permission_id, is_view, is_add, is_edit, is_delete) VALUES ($rid, $pid, $v, $a, $e, $d)");
            $granted++;
        }
        echo sprintf("added role #%d %-18s (%d permissions)\n", $rid, $name, $granted);
        $added++;
    }
    $m->commit();
    echo "\nDONE: $added role(s) added, $skipped skipped.\n";
    echo "Roles now:\n";
    $r = $m->query('SELECT id, name, is_system FROM roles ORDER BY id');
    while ($o = $r->fetch_object()) { echo "  #{$o->id} {$o->name}" . ($o->is_system ? ' (system)' : '') . "\n"; }
} catch (Throwable $ex) {
    $m->rollback();
    fwrite(STDERR, 'ROLLBACK: ' . $ex->getMessage() . "\n");
    exit(1);
}
