<!-- Compiled Static Sidebar for admin.php -->
<aside id="sidebar-left" class="sidebar-left">
    <div class="nano">
        <div class="nano-content">
            <nav id="menu" class="nav-main" role="navigation">
                <ul class="nav nav-main">
                    <!-- dashboard -->
                    <li class="<?php if ($main_menu == 'dashboard') echo 'nav-active'; ?>">
                        <a href="<?php echo base_url('dashboard'); ?>">
                            <i class="fas fa-home"></i>
                            <span><?php echo translate('dashboard') ?: 'dashboard'; ?></span>
                        </a>
                    </li>
                    <!-- Audit Logs Section -->
                    <?php $is_active = in_array($sub_page, ['audit/activity/index', 'audit/email/log/index', 'audit/sms/log/index', 'audit/system/log/index']) || $main_menu == 'audit'; ?>
                    <li class="nav-parent <?php if ($is_active) echo 'nav-expanded nav-active'; ?>">
                        <a href="javascript:void(0);">
                            <i class="fas fa-file-invoice"></i>
                            <span><?php echo translate('audit_logs') ?: 'Audit Logs'; ?></span>
                        </a>
                        <ul class="nav nav-children">
                            <li class="<?php if ($sub_page == 'audit/activity/index') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('activity-logs'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('activity_logs') ?: 'Activity Logs'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'audit/email/log/index') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('email-logs'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('email_logs') ?: 'Email Logs'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'audit/sms/log/index') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('sms/log'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('sms_logs') ?: 'SMS Logs'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'audit/system/log/index') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('system-logs'); ?>" target="_blank">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('system_logs') ?: 'System Logs'; ?></span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- Catalog Section -->
                    <?php $is_active = in_array($sub_page, ['catalog/attribute_family/index', 'catalog/attribute_family/form', 'catalog/attribute/index', 'catalog/attribute/form', 'catalog/brand/index', 'catalog/brand/form', 'catalog/category/index', 'catalog/category/form', 'inventory/index', 'inventory/form', 'catalog/product/index', 'catalog/product/form', 'catalog/review/index', 'inventory/movements', 'inventory/transfer', 'inventory/report', 'inventory/low_stock']) || $main_menu == 'catalog'; ?>
                    <li class="nav-parent <?php if ($is_active) echo 'nav-expanded nav-active'; ?>">
                        <a href="javascript:void(0);">
                            <i class="fas fa-store"></i>
                            <span><?php echo translate('catalog') ?: 'Catalog'; ?></span>
                        </a>
                        <ul class="nav nav-children">
                            <li class="<?php if ($sub_page == 'catalog/attribute_family/index' || $sub_page == 'catalog/attribute_family/form') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('attribute_family'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('attribute_families') ?: 'Attribute Families'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'catalog/attribute/index' || $sub_page == 'catalog/attribute/form') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('attribute'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('attributes') ?: 'Attributes'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'catalog/brand/index' || $sub_page == 'catalog/brand/form') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('brand'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('brands') ?: 'Brands'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'catalog/category/index' || $sub_page == 'catalog/category/form') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('category'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('categories') ?: 'Categories'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'inventory/index' || $sub_page == 'inventory/form') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('inventory_source'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('inventory_sources') ?: 'Inventory Sources'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'catalog/product/index' || $sub_page == 'catalog/product/form') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('product'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('products') ?: 'Products'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'catalog/review/index') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('review'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('reviews') ?: 'Reviews'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'inventory/movements' || $sub_page == 'inventory/transfer') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('inventory_source/movements'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('stock_movements') ?: 'Stock Movements'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'inventory/report' || $sub_page == 'inventory/low_stock') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('inventory_source/report'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('warehouse_report') ?: 'Warehouse Report'; ?></span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- Customers Section -->
                    <?php $is_active = in_array($sub_page, ['customer_group/index', 'customer_group/form']) || $main_menu == 'customers'; ?>
                    <li class="nav-parent <?php if ($is_active) echo 'nav-expanded nav-active'; ?>">
                        <a href="javascript:void(0);">
                            <i class="fas fa-users"></i>
                            <span><?php echo translate('customers') ?: 'Customers'; ?></span>
                        </a>
                        <ul class="nav nav-children">
                            <li class="<?php if ($sub_page == 'customer_group/index' || $sub_page == 'customer_group/form') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('customer_group'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('customer_groups') ?: 'Customer Groups'; ?></span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- Imports -->
                    <li class="<?php if ($main_menu == 'imports') echo 'nav-active'; ?>">
                        <a href="<?php echo base_url('import'); ?>">
                            <i class="fas fa-file-import"></i>
                            <span><?php echo translate('imports') ?: 'Imports'; ?></span>
                        </a>
                    </li>
                    <!-- Notifications -->
                    <li class="<?php if ($main_menu == 'notifications') echo 'nav-active'; ?>">
                        <a href="<?php echo base_url('notification'); ?>">
                            <i class="fas fa-bell"></i>
                            <span><?php echo translate('notifications') ?: 'Notifications'; ?></span>
                        </a>
                    </li>
                    <!-- Orders Section -->
                    <?php $is_active = in_array($sub_page, ['order/index', 'order/view', 'rma/index', 'rma/view']) || $main_menu == 'orders'; ?>
                    <li class="nav-parent <?php if ($is_active) echo 'nav-expanded nav-active'; ?>">
                        <a href="javascript:void(0);">
                            <i class="fas fa-shopping-bag"></i>
                            <span><?php echo translate('orders') ?: 'Orders'; ?></span>
                        </a>
                        <ul class="nav nav-children">
                            <li class="<?php if ($sub_page == 'order/index' || $sub_page == 'order/view') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('order'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('orders') ?: 'Orders'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'rma/index' || $sub_page == 'rma/view') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('rma'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('returns') ?: 'Returns'; ?></span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- Promotions Section -->
                    <?php $is_active = in_array($sub_page, ['promotion/cart_rule_index', 'promotion/cart_rule_form', 'promotion/catalog_rule_index', 'promotion/catalog_rule_form', 'catalog/coupon/index', 'catalog/coupon/form']) || $main_menu == 'promotions'; ?>
                    <li class="nav-parent <?php if ($is_active) echo 'nav-expanded nav-active'; ?>">
                        <a href="javascript:void(0);">
                            <i class="fas fa-bullhorn"></i>
                            <span><?php echo translate('promotions') ?: 'Promotions'; ?></span>
                        </a>
                        <ul class="nav nav-children">
                            <li class="<?php if ($sub_page == 'promotion/cart_rule_index' || $sub_page == 'promotion/cart_rule_form') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('cart_rule'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('cart_price_rules') ?: 'Cart Price Rules'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'promotion/catalog_rule_index' || $sub_page == 'promotion/catalog_rule_form') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('catalog_rule'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('catalog_price_rules') ?: 'Catalog Price Rules'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'catalog/coupon/index' || $sub_page == 'catalog/coupon/form') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('coupon'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('coupons') ?: 'Coupons'; ?></span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- Reports -->
                    <li class="<?php if ($main_menu == 'report') echo 'nav-active'; ?>">
                        <a href="<?php echo base_url('report'); ?>">
                            <i class="fas fa-chart-line"></i>
                            <span><?php echo translate('reports') ?: 'Reports'; ?></span>
                        </a>
                    </li>
                    <!-- Settings Section -->
                    <?php $is_active = in_array($sub_page, ['settings/backup/index', 'settings/email/index', 'settings/email/form', 'settings/global/index', 'settings/landing/index', 'settings/language/index', 'settings/language/words/edit', 'settings/language/edit', 'settings/module/index', 'settings/module/edit', 'settings/role/index', 'settings/role/edit', 'settings/role/permission/index', 'settings/sms/index', 'settings/sms/form']) || $main_menu == 'settings'; ?>
                    <li class="nav-parent <?php if ($is_active) echo 'nav-expanded nav-active'; ?>">
                        <a href="javascript:void(0);">
                            <i class="fas fa-cogs"></i>
                            <span><?php echo translate('settings') ?: 'Settings'; ?></span>
                        </a>
                        <ul class="nav nav-children">
                            <li class="<?php if ($sub_page == 'settings/backup/index') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('backup'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('database_backup') ?: 'Database Backup'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'settings/email/index' || $sub_page == 'settings/email/form') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('email'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('email_setting') ?: 'Email Setting'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'settings/global/index') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('settings'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('global_setting') ?: 'Global Setting'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'settings/landing/index') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('landing-setting'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('landing_setting') ?: 'Landing Setting'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'settings/language/index' || $sub_page == 'settings/language/words/edit' || $sub_page == 'settings/language/edit') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('language'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('language_setting') ?: 'Language Setting'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'settings/module/index' || $sub_page == 'settings/module/edit') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('module'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('module_permission') ?: 'Module Permission'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'settings/role/index' || $sub_page == 'settings/role/edit' || $sub_page == 'settings/role/permission/index') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('role'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('role_permission') ?: 'Role Permission'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'settings/sms/index' || $sub_page == 'settings/sms/form') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('sms'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('sms_setting') ?: 'SMS Setting'; ?></span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- Support Section -->
                    <?php $is_active = in_array($sub_page, ['contact/index', 'contact/view']) || $main_menu == 'support'; ?>
                    <li class="nav-parent <?php if ($is_active) echo 'nav-expanded nav-active'; ?>">
                        <a href="javascript:void(0);">
                            <i class="fas fa-headset"></i>
                            <span><?php echo translate('support') ?: 'Support'; ?></span>
                        </a>
                        <ul class="nav nav-children">
                            <li class="<?php if ($sub_page == 'contact/index' || $sub_page == 'contact/view') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('contact'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('contact_messages') ?: 'Contact Messages'; ?></span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- Users Section -->
                    <?php $is_active = in_array($sub_page, ['user/create', 'user/index', 'user/view', 'user/edit', 'user/disable']) || $main_menu == 'user'; ?>
                    <li class="nav-parent <?php if ($is_active) echo 'nav-expanded nav-active'; ?>">
                        <a href="javascript:void(0);">
                            <i class="fas fa-user-tie"></i>
                            <span><?php echo translate('users') ?: 'Users'; ?></span>
                        </a>
                        <ul class="nav nav-children">
                            <li class="<?php if ($sub_page == 'user/create') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('user/' . route_hash('create')); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('create_user') ?: 'Create User'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'user/index' || $sub_page == 'user/view' || $sub_page == 'user/edit' || $sub_page == 'user/disable') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('user'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('user_list') ?: 'User List'; ?></span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- Website Section -->
                    <?php $is_active = in_array($sub_page, ['banner/index', 'banner/form', 'blog/index', 'blog/form', 'cms/index', 'cms/form', 'faq/index', 'faq/form', 'flash_sale/index', 'flash_sale/form', 'marketing/newsletter_index']) || $main_menu == 'website'; ?>
                    <li class="nav-parent <?php if ($is_active) echo 'nav-expanded nav-active'; ?>">
                        <a href="javascript:void(0);">
                            <i class="fas fa-globe"></i>
                            <span><?php echo translate('website') ?: 'Website'; ?></span>
                        </a>
                        <ul class="nav nav-children">
                            <li class="<?php if ($sub_page == 'banner/index' || $sub_page == 'banner/form') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('banner'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('banners') ?: 'Banners'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'blog/index' || $sub_page == 'blog/form') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('blog'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('blog') ?: 'Blog'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'cms/index' || $sub_page == 'cms/form') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('cms'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('cms_pages') ?: 'CMS Pages'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'faq/index' || $sub_page == 'faq/form') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('faq'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('faq') ?: 'FAQ'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'flash_sale/index' || $sub_page == 'flash_sale/form') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('flash_sale'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('flash_sale') ?: 'Flash Sale'; ?></span>
                                </a>
                            </li>
                            <li class="<?php if ($sub_page == 'marketing/newsletter_index') echo 'nav-active'; ?>">
                                <a href="<?php echo base_url('newsletter'); ?>">
                                    <span><i class="fas fa-caret-right" aria-hidden="true"></i> <?php echo translate('newsletter') ?: 'Newsletter'; ?></span>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</aside>
<!-- end sidebar -->
<script>
    $(document).ready(function() {
        $('.nav-parent > a').off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $li = $(this).parent();
            var $children = $li.children('.nav-children');
            if ($li.hasClass('nav-expanded')) {
                $children.slideUp(200, function() { $li.removeClass('nav-expanded'); });
            } else {
                $('.nav-parent.nav-expanded').not($li).each(function() {
                    $(this).removeClass('nav-expanded').children('.nav-children').slideUp(200);
                });
                $li.addClass('nav-expanded');
                $children.slideDown(200);
            }
        });
        $('.nav-parent.nav-active').addClass('nav-expanded').children('.nav-children').show();
    });
</script>
