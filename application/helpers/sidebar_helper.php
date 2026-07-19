<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Sidebar Helper
 *
 * @package    Authentication
 * @author     Mamun Mia Turan
 * @filename   sidebar_helper.php
 *
 * Generates role-specific sidebar files.
 * Rules:
 *   - Superman  : sees all items unconditionally
 *   - Other roles: items shown based on DB permission (user_privileges)
 *   - No developer_mode checks anywhere
 */

if (!function_exists('get_role_db_permission')) {
    function get_role_db_permission($role_id, $permission, $can)
    {
        $permissions = get_user_permissions($role_id);
        foreach ($permissions as $p) {
            if ($p->permission_prefix == $permission && $p->$can == '1') {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('get_sidebar_item_compile_status')) {
    /**
     * Returns 'visible' or 'omit' for a sidebar item.
     * Superman always gets 'visible'. Others depend on DB permission.
     */
    function get_sidebar_item_compile_status($role_id, $perm, $action = 'is_view')
    {
        if ($role_id == ROLE_SUPERMAN_ID) {
            return 'visible';
        }

        return get_role_db_permission($role_id, $perm, $action) ? 'visible' : 'omit';
    }
}

if (!function_exists('get_role_sidebar_filename')) {
    function get_role_sidebar_filename($role_id)
    {
        $ci = &get_instance();
        static $roles_cache = null;
        if ($roles_cache === null) {
            $roles_cache = [];
            if ($ci->db->table_exists('roles')) {
                $roles = $ci->db->get('roles')->result_array();
                foreach ($roles as $r) {
                    $name = strtolower(trim($r['name']));
                    $name = preg_replace('/[^a-z0-9_]/', '_', $name);
                    $roles_cache[$r['id']] = $name;
                }
            }
        }
        $name = isset($roles_cache[$role_id]) ? $roles_cache[$role_id] : 'role_' . $role_id;
        return $name . '.php';
    }
}

if (!function_exists('generate_sidebar_file_for_role')) {
    function generate_sidebar_file_for_role(int $role_id): bool
    {
        $ci = &get_instance();
        if (!$ci->db->table_exists('roles')) {
            return false;
        }

        $role_id = (int) $role_id;
        if ($role_id < 1) {
            return false;
        }

        $exists = $ci->db->limit(1)->get_where('roles', ['id' => $role_id])->num_rows() > 0;
        if (!$exists) {
            return false;
        }

        $dir = APPPATH . 'views/layout/sidebar/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content   = generate_sidebar_content($role_id);
        $file_path = $dir . get_role_sidebar_filename($role_id);
        atomic_file_put_contents($file_path, $content);

        $ci->load->library('redis_lib');
        if ($ci->redis_lib->is_enabled()) {
            $ci->redis_lib->delete('sidebar_html_role_' . $role_id);
        }

        return true;
    }
}

if (!function_exists('generate_sidebar_files')) {
    function generate_sidebar_files()
    {
        $ci = &get_instance();
        if (!$ci->db->table_exists('roles')) {
            return;
        }
        $roles = $ci->db->get('roles')->result_array();

        $dir = APPPATH . 'views/layout/sidebar/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ci->load->library('redis_lib');
        $redis_enabled = $ci->redis_lib->is_enabled();

        foreach ($roles as $role) {
            $role_id   = $role['id'];
            $content   = generate_sidebar_content($role_id);
            $filename  = get_role_sidebar_filename($role_id);
            $file_path = $dir . $filename;

            atomic_file_put_contents($file_path, $content);

            // Invalidate Redis cache for all nodes (SC3)
            if ($redis_enabled) {
                $ci->redis_lib->delete('sidebar_html_role_' . $role_id);
            }
        }
    }
}

if (!function_exists('generate_sidebar_content')) {
    function generate_sidebar_content($role_id)
    {
        // Dashboard is pinned at top; all other items sorted A-Z by name (children also sorted A-Z)
        $dashboard = [
            'type'      => 'link',
            'name'      => 'dashboard',
            'icon'      => 'fas fa-home',
            'url'       => 'dashboard',
            'main_menu' => 'dashboard'
        ];

        $other_items = [
            [
                'type'      => 'parent',
                'name'      => 'Catalog',
                'icon'      => 'fas fa-store',
                'main_menu' => 'catalog',
                'children'  => [
                    [
                        'type'                => 'link',
                        'name'                => 'Categories',
                        'url'                 => 'category',
                        'sub_page'            => 'catalog/category/index',
                        'sub_page_additional' => ['catalog/category/form'],
                        'permission'          => ['category', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Brands',
                        'url'                 => 'brand',
                        'sub_page'            => 'catalog/brand/index',
                        'sub_page_additional' => ['catalog/brand/form'],
                        'permission'          => ['brand', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Products',
                        'url'                 => 'product',
                        'sub_page'            => 'catalog/product/index',
                        'sub_page_additional' => ['catalog/product/form'],
                        'permission'          => ['product', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Attributes',
                        'url'                 => 'attribute',
                        'sub_page'            => 'catalog/attribute/index',
                        'sub_page_additional' => ['catalog/attribute/form'],
                        'permission'          => ['attribute', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Attribute Families',
                        'url'                 => 'attribute_family',
                        'sub_page'            => 'catalog/attribute_family/index',
                        'sub_page_additional' => ['catalog/attribute_family/form'],
                        'permission'          => ['attribute_family', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Inventory Sources',
                        'url'                 => 'inventory_source',
                        'sub_page'            => 'inventory/index',
                        'sub_page_additional' => ['inventory/form'],
                        'permission'          => ['inventory_source', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Stock Movements',
                        'url'                 => 'inventory_source/movements',
                        'sub_page'            => 'inventory/movements',
                        'sub_page_additional' => ['inventory/transfer'],
                        'permission'          => ['inventory_source', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Warehouse Report',
                        'url'                 => 'inventory_source/report',
                        'sub_page'            => 'inventory/report',
                        'sub_page_additional' => ['inventory/low_stock'],
                        'permission'          => ['inventory_source', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Reviews',
                        'url'                 => 'review',
                        'sub_page'            => 'catalog/review/index',
                        'permission'          => ['review', 'is_view']
                    ],
                ]
            ],
            [
                'type'      => 'parent',
                'name'      => 'Orders',
                'icon'      => 'fas fa-shopping-bag',
                'main_menu' => 'orders',
                'children'  => [
                    [
                        'type'                => 'link',
                        'name'                => 'Orders',
                        'url'                 => 'order',
                        'sub_page'            => 'order/index',
                        'sub_page_additional' => ['order/view'],
                        'permission'          => ['order', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Returns',
                        'url'                 => 'rma',
                        'sub_page'            => 'rma/index',
                        'sub_page_additional' => ['rma/view'],
                        'permission'          => ['rma', 'is_view']
                    ],
                ]
            ],
            [
                'type'      => 'parent',
                'name'      => 'Promotions',
                'icon'      => 'fas fa-bullhorn',
                'main_menu' => 'promotions',
                'children'  => [
                    [
                        'type'                => 'link',
                        'name'                => 'Coupons',
                        'url'                 => 'coupon',
                        'sub_page'            => 'catalog/coupon/index',
                        'sub_page_additional' => ['catalog/coupon/form'],
                        'permission'          => ['coupon', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Cart Price Rules',
                        'url'                 => 'cart_rule',
                        'sub_page'            => 'promotion/cart_rule_index',
                        'sub_page_additional' => ['promotion/cart_rule_form'],
                        'permission'          => ['cart_rule', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Catalog Price Rules',
                        'url'                 => 'catalog_rule',
                        'sub_page'            => 'promotion/catalog_rule_index',
                        'sub_page_additional' => ['promotion/catalog_rule_form'],
                        'permission'          => ['catalog_rule', 'is_view']
                    ],
                ]
            ],
            [
                'type'      => 'parent',
                'name'      => 'Shipping',
                'icon'      => 'fas fa-truck',
                'main_menu' => 'shipping',
                'children'  => [
                    [
                        'type'                => 'link',
                        'name'                => 'Shipping Zones',
                        'url'                 => 'shipping/zones',
                        'sub_page'            => 'shipping/zones',
                        'sub_page_additional' => ['shipping/zone_form'],
                        'permission'          => ['shipping_zone', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Shipping Methods',
                        'url'                 => 'shipping/methods',
                        'sub_page'            => 'shipping/methods',
                        'sub_page_additional' => ['shipping/method_form'],
                        'permission'          => ['shipping_method', 'is_view']
                    ],
                ]
            ],
            [
                'type'      => 'parent',
                'name'      => 'Tax',
                'icon'      => 'fas fa-percent',
                'main_menu' => 'tax',
                'children'  => [
                    [
                        'type'                => 'link',
                        'name'                => 'Tax Categories',
                        'url'                 => 'tax/categories',
                        'sub_page'            => 'tax/categories',
                        'sub_page_additional' => ['tax/category_form'],
                        'permission'          => ['tax_category', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Tax Rates',
                        'url'                 => 'tax/rates',
                        'sub_page'            => 'tax/rates',
                        'sub_page_additional' => ['tax/rate_form'],
                        'permission'          => ['tax_rate', 'is_view']
                    ],
                ]
            ],
            [
                'type'      => 'parent',
                'name'      => 'Payment',
                'icon'      => 'fas fa-credit-card',
                'main_menu' => 'payment',
                'children'  => [
                    [
                        'type'       => 'link',
                        'name'       => 'Payment Methods',
                        'url'        => 'payment-settings',
                        'sub_page'   => 'payment/settings',
                        'permission' => ['payment_method', 'is_view']
                    ],
                    [
                        'type'       => 'link',
                        'name'       => 'Transactions',
                        'url'        => 'payment-settings/transactions',
                        'sub_page'   => 'payment/transactions',
                        'permission' => ['payment_transaction', 'is_view']
                    ],
                ]
            ],
            [
                // Storefront/website content management — the landing-page
                // custom pages grouped under one "Website" menu.
                'type'      => 'parent',
                'name'      => 'Website',
                'icon'      => 'fas fa-globe',
                'main_menu' => 'website',
                'children'  => [
                    [
                        'type'                => 'link',
                        'name'                => 'Banners',
                        'url'                 => 'banner',
                        'sub_page'            => 'banner/index',
                        'sub_page_additional' => ['banner/form'],
                        'permission'          => ['banner', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Blog',
                        'url'                 => 'blog',
                        'sub_page'            => 'blog/index',
                        'sub_page_additional' => ['blog/form'],
                        'permission'          => ['blog', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'CMS Pages',
                        'url'                 => 'cms',
                        'sub_page'            => 'cms/index',
                        'sub_page_additional' => ['cms/form'],
                        'permission'          => ['cms', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'FAQ',
                        'url'                 => 'faq',
                        'sub_page'            => 'faq/index',
                        'sub_page_additional' => ['faq/form'],
                        'permission'          => ['faq', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Flash Sale',
                        'url'                 => 'flash_sale',
                        'sub_page'            => 'flash_sale/index',
                        'sub_page_additional' => ['flash_sale/form'],
                        'permission'          => ['flash_sale', 'is_view']
                    ],
                    [
                        'type'       => 'link',
                        'name'       => 'Newsletter',
                        'url'        => 'newsletter',
                        'sub_page'   => 'marketing/newsletter_index',
                        'permission' => ['newsletter', 'is_view']
                    ],
                ]
            ],
            [
                'type'      => 'parent',
                'name'      => 'Support',
                'icon'      => 'fas fa-headset',
                'main_menu' => 'support',
                'children'  => [
                    [
                        'type'                => 'link',
                        'name'                => 'Complaints',
                        'url'                 => 'complaint',
                        'sub_page'            => 'complaint/index',
                        'sub_page_additional' => ['complaint/view'],
                        'permission'          => ['complaint', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Contact Messages',
                        'url'                 => 'contact',
                        'sub_page'            => 'contact/index',
                        'sub_page_additional' => ['contact/view'],
                        'permission'          => ['contact', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Tickets',
                        'url'                 => 'ticket',
                        'sub_page'            => 'ticket/index',
                        'sub_page_additional' => ['ticket/view'],
                        'permission'          => ['ticket', 'is_view']
                    ],
                ]
            ],
            [
                'type'       => 'link',
                'name'       => 'Reports',
                'icon'       => 'fas fa-chart-line',
                'url'        => 'report',
                'main_menu'  => 'report',
                'sub_page'   => 'report/index',
                'permission' => ['report', 'is_view']
            ],
            [
                'type'      => 'parent',
                'name'      => 'Customers',
                'icon'      => 'fas fa-users',
                'main_menu' => 'customers',
                'children'  => [
                    [
                        'type'       => 'link',
                        'name'       => 'Customer List',
                        'url'        => 'customer',
                        'sub_page'   => 'customer/index',
                        'permission' => ['customer', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Customer Groups',
                        'url'                 => 'customer_group',
                        'sub_page'            => 'customer_group/index',
                        'sub_page_additional' => ['customer_group/form'],
                        'permission'          => ['customer_group', 'is_view']
                    ],
                ]
            ],
            [
                'type'      => 'parent',
                'name'      => 'Audit Logs',
                'icon'      => 'fas fa-file-invoice',
                'main_menu' => 'audit',
                'children'  => [
                    [
                        'type'       => 'link',
                        'name'       => 'Activity Logs',
                        'url'        => 'activity-logs',
                        'sub_page'   => 'audit/activity/index',
                        'permission' => ['activity_log', 'is_view']
                    ],
                    [
                        'type'       => 'link',
                        'name'       => 'Email Logs',
                        'url'        => 'email-logs',
                        'sub_page'   => 'audit/email/log/index',
                        'permission' => ['email_log', 'is_view']
                    ],
                    [
                        'type'       => 'link',
                        'name'       => 'SMS Logs',
                        'url'        => 'sms/log',
                        'sub_page'   => 'audit/sms/log/index',
                        'permission' => ['sms_logs', 'is_view']
                    ],
                    [
                        'type'       => 'link',
                        'name'       => 'System Logs',
                        'url'        => 'system-logs',
                        'sub_page'   => 'audit/system/log/index',
                        'permission' => ['system_log', 'is_view'],
                        'target'     => '_blank'
                    ],
                ]
            ],
            [
                'type'       => 'link',
                'name'       => 'Imports',
                'icon'       => 'fas fa-file-import',
                'url'        => 'import',
                'main_menu'  => 'imports',
                'permission' => ['imports', 'is_view']
            ],
            [
                'type'       => 'link',
                'name'       => 'Notifications',
                'icon'       => 'fas fa-bell',
                'url'        => 'notification',
                'main_menu'  => 'notifications',
                'permission' => ['notifications', 'is_view']
            ],
            [
                'type'      => 'parent',
                'name'      => 'Settings',
                'icon'      => 'fas fa-cogs',
                'main_menu' => 'settings',
                'children'  => [
                    [
                        'type'       => 'link',
                        'name'       => 'Database Backup',
                        'url'        => 'backup',
                        'sub_page'   => 'settings/backup/index',
                        'permission' => ['database_backup', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Email Setting',
                        'url'                 => 'email',
                        'sub_page'            => 'settings/email/index',
                        'sub_page_additional' => ['settings/email/form'],
                        'permission'          => ['email_setting', 'is_view']
                    ],
                    [
                        'type'       => 'link',
                        'name'       => 'Global Setting',
                        'url'        => 'settings',
                        'sub_page'   => 'settings/global/index',
                        'permission' => ['global_setting', 'is_view']
                    ],
                    [
                        'type'       => 'link',
                        'name'       => 'Landing Setting',
                        'url'        => 'landing-setting',
                        'sub_page'   => 'settings/landing/index',
                        'permission' => ['global_setting', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Language Setting',
                        'url'                 => 'language',
                        'sub_page'            => 'settings/language/index',
                        'sub_page_additional' => ['settings/language/words/edit', 'settings/language/edit'],
                        'permission'          => ['language', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Module Permission',
                        'url'                 => 'module',
                        'sub_page'            => 'settings/module/index',
                        'sub_page_additional' => ['settings/module/edit'],
                        'permission'          => ['modules', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'Role Permission',
                        'url'                 => 'role',
                        'sub_page'            => 'settings/role/index',
                        'sub_page_additional' => ['settings/role/edit', 'settings/role/permission/index'],
                        'permission'          => ['role_permission', 'is_view']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'SMS Setting',
                        'url'                 => 'sms',
                        'sub_page'            => 'settings/sms/index',
                        'sub_page_additional' => ['settings/sms/form'],
                        'permission'          => ['sms_setting', 'is_view']
                    ],
                ]
            ],
            [
                'type'      => 'parent',
                'name'      => 'Users',
                'icon'      => 'fas fa-user-tie',
                'main_menu' => 'user',
                'children'  => [
                    [
                        'type'       => 'link',
                        'name'       => 'Create User',
                        'url'        => 'user/create',
                        'sub_page'   => 'user/create',
                        'permission' => ['user', 'is_add']
                    ],
                    [
                        'type'                => 'link',
                        'name'                => 'User List',
                        'url'                 => 'user',
                        'sub_page'            => 'user/index',
                        'sub_page_additional' => ['user/view', 'user/edit', 'user/disable'],
                        'permission'          => ['user', 'is_view']
                    ],
                ]
            ],
        ];

        // Sort top-level items A-Z by name
        usort($other_items, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        // Sort children A-Z by name within each parent
        foreach ($other_items as &$item) {
            if ($item['type'] === 'parent' && !empty($item['children'])) {
                usort($item['children'], function ($a, $b) {
                    return strcasecmp($a['name'], $b['name']);
                });
            }
        }
        unset($item);

        $sidebar_structure = array_merge([$dashboard], $other_items);

        // Filter menu structure based on permissions (no developer_mode)
        $filtered_menu = [];
        foreach ($sidebar_structure as $item) {
            if ($item['type'] === 'link') {
                if (isset($item['permission'])) {
                    list($perm, $action) = $item['permission'];
                    if (get_sidebar_item_compile_status($role_id, $perm, $action) === 'omit') {
                        continue;
                    }
                }
                $filtered_menu[] = $item;

            } elseif ($item['type'] === 'parent') {
                $filtered_children = [];
                foreach ($item['children'] as $child) {
                    if (isset($child['permission'])) {
                        list($perm, $action) = $child['permission'];
                        if (get_sidebar_item_compile_status($role_id, $perm, $action) === 'omit') {
                            continue;
                        }
                    }
                    $filtered_children[] = $child;
                }
                if (!empty($filtered_children)) {
                    $item['children'] = $filtered_children;
                    $filtered_menu[]  = $item;
                }
            }
        }

        // ---- Generate PHP / HTML ----
        $code  = '<!-- Compiled Static Sidebar for ' . get_role_sidebar_filename($role_id) . ' -->' . PHP_EOL;
        $code .= '<aside id="sidebar-left" class="sidebar-left">' . PHP_EOL;
        $code .= '    <div class="nano">' . PHP_EOL;
        $code .= '        <div class="nano-content">' . PHP_EOL;
        $code .= '            <nav id="menu" class="nav-main" role="navigation">' . PHP_EOL;
        $code .= '                <ul class="nav nav-main">' . PHP_EOL;

        foreach ($filtered_menu as $item) {
            if ($item['type'] === 'link') {
                $url_expr   = "base_url('{$item['url']}')";
                $target_attr = isset($item['target']) ? ' target="' . $item['target'] . '"' : '';
                $code .= '                    <!-- ' . $item['name'] . ' -->' . PHP_EOL;
                $code .= '                    <li class="<?php if ($main_menu == \'' . $item['main_menu'] . '\') echo \'nav-active\'; ?>">' . PHP_EOL;
                $code .= '                        <a href="<?php echo ' . $url_expr . '; ?>"' . $target_attr . '>' . PHP_EOL;
                $code .= '                            <i class="' . $item['icon'] . '"></i>' . PHP_EOL;
                $translate_key = str_replace(' ', '_', strtolower($item['name']));
                $code .= '                            <span><?php echo translate(\'' . $translate_key . '\') ?: \'' . addslashes($item['name']) . '\'; ?></span>' . PHP_EOL;
                $code .= '                        </a>' . PHP_EOL;
                $code .= '                    </li>' . PHP_EOL;

            } elseif ($item['type'] === 'parent') {
                $code .= '                    <!-- ' . $item['name'] . ' Section -->' . PHP_EOL;

                // Build sub_page list for active detection
                $sub_pages = [];
                foreach ($item['children'] as $child) {
                    $sub_pages[] = "'" . $child['sub_page'] . "'";
                    if (isset($child['sub_page_additional'])) {
                        foreach ($child['sub_page_additional'] as $add) {
                            $sub_pages[] = "'" . $add . "'";
                        }
                    }
                }
                $sub_pages_str = '[' . implode(', ', $sub_pages) . ']';

                $code .= '                    <?php $is_active = in_array($sub_page, ' . $sub_pages_str . ') || $main_menu == \'' . $item['main_menu'] . '\'; ?>' . PHP_EOL;
                $code .= '                    <li class="nav-parent <?php if ($is_active) echo \'nav-expanded nav-active\'; ?>">' . PHP_EOL;
                $code .= '                        <a href="javascript:void(0);">' . PHP_EOL;
                $code .= '                            <i class="' . $item['icon'] . '"></i>' . PHP_EOL;
                $parent_translate_key = str_replace(' ', '_', strtolower($item['name']));
                $code .= '                            <span><?php echo translate(\'' . $parent_translate_key . '\') ?: \'' . addslashes($item['name']) . '\'; ?></span>' . PHP_EOL;
                $code .= '                        </a>' . PHP_EOL;
                $code .= '                        <ul class="nav nav-children">' . PHP_EOL;

                foreach ($item['children'] as $child) {
                    if ($child['url'] === 'user/create') {
                        $url_expr = "base_url('user/' . route_hash('create'))";
                    } else {
                        $url_expr = "base_url('{$child['url']}')";
                    }

                    $child_target_attr = isset($child['target']) ? ' target="' . $child['target'] . '"' : '';

                    $active_conds = ["\$sub_page == '{$child['sub_page']}'"];
                    if (isset($child['sub_page_additional'])) {
                        foreach ($child['sub_page_additional'] as $add) {
                            $active_conds[] = "\$sub_page == '{$add}'";
                        }
                    }
                    $active_cond_str = implode(' || ', $active_conds);

                    $code .= '                            <li class="<?php if (' . $active_cond_str . ') echo \'nav-active\'; ?>">' . PHP_EOL;
                    $code .= '                                <a href="<?php echo ' . $url_expr . '; ?>"' . $child_target_attr . '>' . PHP_EOL;
                    $child_translate_key = str_replace(' ', '_', strtolower($child['name']));
                    $code .= '                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate(\'' . $child_translate_key . '\') ?: \'' . addslashes($child['name']) . '\'; ?></span>' . PHP_EOL;
                    $code .= '                                </a>' . PHP_EOL;
                    $code .= '                            </li>' . PHP_EOL;
                }

                $code .= '                        </ul>' . PHP_EOL;
                $code .= '                    </li>' . PHP_EOL;
            }
        }

        $code .= '                </ul>' . PHP_EOL;
        $code .= '            </nav>' . PHP_EOL;
        $code .= '        </div>' . PHP_EOL;
        $code .= '    </div>' . PHP_EOL;
        $code .= '</aside>' . PHP_EOL;
        $code .= '<!-- end sidebar -->' . PHP_EOL;

        // JS accordion
        $code .= '<script>' . PHP_EOL;
        $code .= '    $(document).ready(function() {' . PHP_EOL;
        $code .= '        $(\'.nav-parent > a\').off(\'click\').on(\'click\', function(e) {' . PHP_EOL;
        $code .= '            e.preventDefault();' . PHP_EOL;
        $code .= '            e.stopPropagation();' . PHP_EOL;
        $code .= '            var $li = $(this).parent();' . PHP_EOL;
        $code .= '            var $children = $li.children(\'.nav-children\');' . PHP_EOL;
        $code .= '            if ($li.hasClass(\'nav-expanded\')) {' . PHP_EOL;
        $code .= '                $children.slideUp(200, function() { $li.removeClass(\'nav-expanded\'); });' . PHP_EOL;
        $code .= '            } else {' . PHP_EOL;
        $code .= '                $(\'.nav-parent.nav-expanded\').not($li).each(function() {' . PHP_EOL;
        $code .= '                    $(this).removeClass(\'nav-expanded\').children(\'.nav-children\').slideUp(200);' . PHP_EOL;
        $code .= '                });' . PHP_EOL;
        $code .= '                $li.addClass(\'nav-expanded\');' . PHP_EOL;
        $code .= '                $children.slideDown(200);' . PHP_EOL;
        $code .= '            }' . PHP_EOL;
        $code .= '        });' . PHP_EOL;
        $code .= '        $(\'.nav-parent.nav-active\').addClass(\'nav-expanded\').children(\'.nav-children\').show();' . PHP_EOL;
        $code .= '    });' . PHP_EOL;
        $code .= '</script>' . PHP_EOL;

        return $code;
    }
}
