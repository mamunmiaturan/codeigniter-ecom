<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Seeds the system roles and their dynamic hierarchy.
 *
 * Hierarchy (lower level = higher authority):
 *   Superman (0, ghost) -> Superadmin (1) -> Admin (2) -> ... -> Customer (10)
 *
 * Uses INSERT ... ON DUPLICATE KEY UPDATE (upsert) instead of empty_table():
 * `roles` is referenced by FKs from login_credential and user_privileges, so
 * emptying it fails on any non-fresh database. Upsert is FK-safe and idempotent.
 */
class RolesSeeder extends Seeder {

    public function run() {
        if (!$this->db->table_exists('roles')) {
            echo "Error: 'roles' table does not exist. Skipping seeding." . PHP_EOL;
            return;
        }

        $superman   = defined('ROLE_SUPERMAN_ID')   ? ROLE_SUPERMAN_ID   : 1;
        $superadmin = defined('ROLE_SUPERADMIN_ID') ? ROLE_SUPERADMIN_ID : 7;
        $admin      = defined('ROLE_ADMIN_ID')      ? ROLE_ADMIN_ID      : 2;
        $customer   = defined('ROLE_CUSTOMER_ID')   ? ROLE_CUSTOMER_ID   : 6;

        // id, name, level, parent_id  (lower level = higher authority)
        $roles = [
            [$superman,   'Superman',   0,  null],        // ghost, top
            [$superadmin, 'Superadmin', 1,  $superman],
            [$admin,      'Admin',      2,  $superadmin],
            [8,           'Manager',    3,  $admin],       // ecom staff roles under Admin
            [9,           'Sales',      4,  $admin],
            [10,          'Delivery',   5,  $admin],
            [11,          'Support',    6,  $admin],
            [$customer,   'Customer',   7,  $admin],
        ];

        $has_level  = $this->db->field_exists('level', 'roles');
        $has_parent = $this->db->field_exists('parent_id', 'roles');

        foreach ($roles as [$id, $name, $level, $parent]) {
            $cols = ['id' => (int) $id, 'name' => $name, 'is_system' => 1];
            if ($has_level)  { $cols['level'] = (int) $level; }
            if ($has_parent) { $cols['parent_id'] = $parent === null ? null : (int) $parent; }

            $fields = implode(', ', array_map(fn($c) => "`$c`", array_keys($cols)));
            $values = implode(', ', array_map(fn($v) => $v === null ? 'NULL' : $this->db->escape($v), array_values($cols)));
            // On conflict (same id) refresh the hierarchy fields; never touch is_system=0 downgrades.
            $updates = [];
            foreach (array_keys($cols) as $c) {
                if ($c === 'id') { continue; }
                $updates[] = "`$c` = VALUES(`$c`)";
            }
            $sql = "INSERT INTO `roles` ($fields) VALUES ($values) "
                 . "ON DUPLICATE KEY UPDATE " . implode(', ', $updates);
            $this->db->query($sql);
        }

        echo "RolesSeeder Finished (Superman/Superadmin/Admin/Customer)." . PHP_EOL;
    }
}
