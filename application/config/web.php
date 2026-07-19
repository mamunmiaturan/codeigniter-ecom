<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Web Routes (Laravel Style for CI3)
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| The Route class maps these directly to CodeIgniter's internal routing array.
|
*/

// Authentication Routes
Route::group(['prefix' => 'login'], function () {
    Route::get('/', 'auth/Authentication@index');
    Route::get('register', 'auth/Authentication@register');
});
Route::get('logout', 'auth/Authentication@logout');

// Public XML sitemap
Route::get('sitemap.xml', 'system/Sitemap@index');

// Health-check probes (system/Health, public monitoring endpoints)
Route::group(['prefix' => 'health'], function () {
    Route::get('/', 'system/Health@index');
    Route::get('ready', 'system/Health@ready');
    Route::get('details', 'system/Health@details');
});

// Queue dashboard (admin) — the failed-jobs UI links to these
Route::group(['prefix' => 'queuedashboard'], function () {
    Route::get('/', 'system/Queuedashboard@index');
    Route::get('retry_failed/{id}', 'system/Queuedashboard@retry_failed');
    Route::get('clear_failed', 'system/Queuedashboard@clear_failed');
});

// Password Reset + 2FA Routes
Route::group(['prefix' => 'authentication'], function () {
    // Login form (GET) + submit (POST). The login view posts to /authentication
    // and every post-login redirect targets it, so it must map to the index method.
    Route::any('/', 'auth/Authentication@index');
    // Logout — the navbar button and script.php link to authentication/logout.
    Route::get('logout', 'auth/Authentication@logout');
    // "Switch back" from an impersonated session (navbar link).
    Route::get('restore_previous_session', 'auth/Authentication@restore_previous_session');
    Route::any('forgot', 'auth/Authentication@forgot');
    Route::any('password-reset', 'auth/Authentication@password_reset');
    Route::any('verify_2fa', 'auth/Authentication@verify_2fa');
});

// Dashboard Route
Route::group(['prefix' => 'dashboard'], function () {
    Route::get('/', 'admin/Dashboard@index');
    Route::get('get_live_logs', 'admin/Dashboard@get_live_logs');
    Route::post('get_live_logs', 'admin/Dashboard@get_live_logs');
});

// User Management Routes
// Customers directory (storefront customers = role-6 users)
Route::get('customer', 'admin/Customer@index');
// Wishlist demand report (most-wishlisted products)
Route::get('wishlist', 'admin/Wishlist@index');

Route::group(['prefix' => 'user'], function () {
    Route::get('/', 'admin/User@index');
    Route::post('/', 'admin/User@index');
    Route::post('get_users_server_side', 'admin/User@get_users_server_side');
    Route::get('create', 'admin/User@create');
    Route::post('store', 'admin/User@store');
    Route::get('edit/{id}', 'admin/User@edit');
    Route::post('edit/{id}', 'admin/User@edit');
    Route::post('update', 'admin/User@update');
    Route::get('profile/{id}', 'admin/User@profile');
    Route::post('status', 'admin/User@status');
    Route::post('change_password', 'admin/User@change_password');
    Route::get('disable_authentication', 'admin/User@disable_authentication');
    Route::post('disable_authentication', 'admin/User@disable_authentication');
    Route::post('delete/{id}', 'admin/User@delete');
});

// Profile Routes
Route::group(['prefix' => 'profile'], function () {
    Route::get('/', 'admin/Profile@index');
    Route::post('/', 'admin/Profile@index');
    // The navbar links to profile/index/{user_id}; the controller's index($id).
    Route::get('index', 'admin/Profile@index');
    Route::get('index/{id}', 'admin/Profile@index');
    Route::post('index', 'admin/Profile@index');
    Route::post('index/{id}', 'admin/Profile@index');
    Route::post('update', 'admin/Profile@index');
    Route::get('security', 'admin/Profile@security');
    Route::get('password', 'admin/Profile@password');
    Route::post('password', 'admin/Profile@password');
    Route::post('change_password', 'admin/Profile@change_password');
    Route::post('enable_2fa', 'admin/Profile@enable_2fa');
    Route::post('disable_2fa', 'admin/Profile@disable_2fa');
    Route::post('regenerate_backup_codes', 'admin/Profile@regenerate_backup_codes');
});

// System Settings
Route::group(['prefix' => 'settings'], function () {
    Route::get('/', 'admin/Settings@index');
    Route::post('/', 'admin/Settings@index');
    Route::post('toggle_theme', 'admin/Settings@toggle_theme');
    Route::post('rebuild_system_cache', 'admin/Settings@rebuild_system_cache');
    Route::post('run_integrity_scan', 'admin/Settings@run_integrity_scan');
});

// Landing (storefront) branding settings
Route::group(['prefix' => 'landing-setting'], function () {
    Route::get('/', 'admin/Landing_setting@index');
    Route::post('/', 'admin/Landing_setting@index');
});

// Email Routes
Route::group(['prefix' => 'email'], function () {
    Route::get('/', 'admin/Email@index');
    Route::post('/', 'admin/Email@index');
    Route::get('template', 'admin/Email@template');
    Route::post('template', 'admin/Email@template');
});

// Sms Routes
Route::group(['prefix' => 'sms'], function () {
    Route::get('/', 'admin/Sms@index');
    Route::post('/', 'admin/Sms@index');
    Route::get('template', 'admin/Sms@template');
    Route::post('template', 'admin/Sms@template');
});

// Role & Permission Routes
Route::group(['prefix' => 'role'], function () {
    Route::get('/', 'admin/Role@index');
    Route::post('/', 'admin/Role@index');
    Route::get('edit/{id}', 'admin/Role@edit');
    Route::post('edit/{id}', 'admin/Role@edit');
    Route::post('delete/{id}', 'admin/Role@delete');
    Route::get('permission/{id}', 'admin/Role@permission');
    Route::post('permission/{id}', 'admin/Role@permission');
    Route::get('regenerate_sidebar/{id}', 'admin/Role@regenerate_sidebar');
    Route::post('regenerate_sidebar/{id}', 'admin/Role@regenerate_sidebar');
});

// Module Routes
Route::group(['prefix' => 'module'], function () {
    Route::get('/', 'admin/Module@index');
    Route::post('/', 'admin/Module@index');
    Route::get('edit/{module_id}/{permission_id}', 'admin/Module@edit');
    Route::post('edit/{module_id}/{permission_id}', 'admin/Module@edit');
    Route::post('delete/{module_id}/{permission_id}', 'admin/Module@delete');
});

// Database Backup
// Note: Dbtool is intentionally not routed — it is CLI-only (`php index.php dbtool/backup`)
// and its constructor rejects any HTTP request.
Route::group(['prefix' => 'backup'], function () {
    Route::get('/', 'admin/Backup@index');
    Route::post('/', 'admin/Backup@index');
    Route::get('download', 'admin/Backup@download');
    Route::post('delete/{file}', 'admin/Backup@delete');
    Route::post('restore_file', 'admin/Backup@restore_file');
});

// Logs & Audit Routes
Route::group(['prefix' => 'activity-logs'], function () {
    Route::get('/', 'admin/Activitylog@index');
    // POST: clearing wipes the log. As a GET it was triggerable by any crawler.
    Route::post('clear', 'admin/Activitylog@clear');
});

Route::group(['prefix' => 'system-logs'], function () {
    Route::get('/', 'admin/Systemlog@index');
    Route::get('load-file', 'admin/Systemlog@load_file');
    Route::post('delete-file', 'admin/Systemlog@delete_file');
});

// SMS Sending & Logs
Route::group(['prefix' => 'sms'], function () {
    Route::get('send-sms', 'admin/Sms@send_sms');
    Route::post('send-sms', 'admin/Sms@send_sms');
    Route::get('send_sms', 'admin/Sms@send_sms');
    Route::post('send_sms', 'admin/Sms@send_sms');
    Route::get('log', 'admin/Smslog@index');
    Route::post('get_sms_logs_server_side', 'admin/Smslog@get_sms_logs_server_side');
});

// Language Routes
Route::group(['prefix' => 'language'], function () {
    Route::get('/', 'admin/Language@index');
    Route::post('/', 'admin/Language@index');
    Route::get('edit/{id}', 'admin/Language@edit');
    Route::post('edit/{id}', 'admin/Language@edit');
    Route::get('word_update/{lang_hash}', 'admin/Language@word_update');
    Route::post('word_update/{lang_hash}', 'admin/Language@word_update');
    Route::post('add_phrase', 'admin/Language@add_phrase');
    Route::post('import_phrase', 'admin/Language@import_phrase');
    Route::post('delete_phrase/{id_hash}', 'admin/Language@delete_phrase');
    Route::post('delete/{id_hash}', 'admin/Language@delete');
    Route::post('status', 'admin/Language@status');
    Route::post('auto_translate_phrase', 'admin/Language@auto_translate_phrase');
    Route::get('set_language/{action}', 'admin/Language@set_language');
});

// Import Queue Routes
Route::group(['prefix' => 'import'], function () {
    Route::get('/', 'admin/Import@index');
    Route::get('download_file/{id}', 'admin/Import@download_file');
    Route::post('approve/{id}', 'admin/Import@approve');
    Route::post('retry/{id}', 'admin/Import@retry');
    Route::get('download_sample_csv', 'admin/Import@download_sample_csv');
    Route::post('import_csv', 'admin/Import@import_csv');
});

// Notifications
Route::group(['prefix' => 'notification'], function () {
    Route::get('/', 'admin/Notification@index');
    Route::get('get_unread_notifications', 'admin/Notification@get_unread_notifications');
    Route::post('mark_notifications_as_read', 'admin/Notification@mark_notifications_as_read');
    Route::get('mark_single_as_read/{id}', 'admin/Notification@mark_single_as_read');
    Route::post('mark_single_as_read/{id}', 'admin/Notification@mark_single_as_read');
});


// Email Logs
Route::group(['prefix' => 'email-logs'], function () {
    Route::get('/', 'admin/Emaillog@index');
    // POST: clearing wipes the log. As a GET it was triggerable by any crawler.
    Route::post('clear', 'admin/Emaillog@clear');
});

// Ajax Utility Routes
Route::group(['prefix' => 'ajax'], function () {
    Route::post('toggle_developer_mode', 'system/Ajax@toggle_developer_mode');
    Route::post('heartbeat', 'system/Ajax@heartbeat');
});

// -------------------------------------------------------------------------
// E-Commerce Catalog Routes
// -------------------------------------------------------------------------
Route::group(['prefix' => 'category'], function () {
    Route::get('/', 'admin/Category@index');
    Route::post('/', 'admin/Category@index');
    Route::get('create', 'admin/Category@create');
    Route::post('store', 'admin/Category@store');
    Route::get('edit/{id}', 'admin/Category@edit');
    Route::post('update', 'admin/Category@update');
    Route::post('delete/{id}', 'admin/Category@delete');
    Route::post('status', 'admin/Category@status');
    Route::post('get_categories_server_side', 'admin/Category@get_categories_server_side');
});

Route::group(['prefix' => 'brand'], function () {
    Route::get('/', 'admin/Brand@index');
    Route::post('/', 'admin/Brand@index');
    Route::get('create', 'admin/Brand@create');
    Route::post('store', 'admin/Brand@store');
    Route::get('edit/{id}', 'admin/Brand@edit');
    Route::post('update', 'admin/Brand@update');
    Route::post('delete/{id}', 'admin/Brand@delete');
    Route::post('status', 'admin/Brand@status');
    Route::post('get_brands_server_side', 'admin/Brand@get_brands_server_side');
});

Route::group(['prefix' => 'product'], function () {
    Route::get('/', 'admin/Product@index');
    Route::post('/', 'admin/Product@index');
    Route::get('export', 'admin/Product@export');
    Route::get('import', 'admin/Product@import');
    Route::get('import_sample', 'admin/Product@import_sample');
    Route::post('import_csv', 'admin/Product@import_csv');
    Route::get('create', 'admin/Product@create');
    Route::post('store', 'admin/Product@store');
    Route::get('edit/{id}', 'admin/Product@edit');
    Route::post('update', 'admin/Product@update');
    Route::post('delete/{id}', 'admin/Product@delete');
    Route::post('status', 'admin/Product@status');
    Route::post('get_products_server_side', 'admin/Product@get_products_server_side');
});

// EAV attributes (admin) — controller is `Attributes` (plural) because
// `Attribute` collides with PHP 8's built-in class; URLs stay singular.
Route::group(['prefix' => 'attribute'], function () {
    Route::get('/', 'admin/Attributes@index');
    Route::post('/', 'admin/Attributes@index');
    Route::post('get_attributes_server_side', 'admin/Attributes@get_attributes_server_side');
    Route::get('create', 'admin/Attributes@create');
    Route::post('store', 'admin/Attributes@store');
    Route::get('edit/{id}', 'admin/Attributes@edit');
    Route::post('update', 'admin/Attributes@update');
    Route::post('delete/{id}', 'admin/Attributes@delete');
    Route::post('status', 'admin/Attributes@status');
});

// Inventory sources (admin)
Route::group(['prefix' => 'inventory_source'], function () {
    Route::get('/', 'admin/Inventory_source@index');
    Route::post('/', 'admin/Inventory_source@index');
    Route::post('get_inventory_sources_server_side', 'admin/Inventory_source@get_inventory_sources_server_side');
    Route::get('create', 'admin/Inventory_source@create');
    Route::post('store', 'admin/Inventory_source@store');
    Route::get('edit/{id}', 'admin/Inventory_source@edit');
    Route::post('update', 'admin/Inventory_source@update');
    Route::post('delete/{id}', 'admin/Inventory_source@delete');
    Route::post('status', 'admin/Inventory_source@status');
    Route::get('movements', 'admin/Inventory_source@movements');
    Route::post('get_movements_server_side', 'admin/Inventory_source@get_movements_server_side');
    Route::get('transfer', 'admin/Inventory_source@transfer');
    Route::post('transfer_save', 'admin/Inventory_source@transfer_save');
    Route::get('report', 'admin/Inventory_source@report');
    Route::get('low_stock', 'admin/Inventory_source@low_stock');
});

// EAV attribute families (admin)
Route::group(['prefix' => 'attribute_family'], function () {
    Route::get('/', 'admin/Attribute_family@index');
    Route::post('/', 'admin/Attribute_family@index');
    Route::post('get_families_server_side', 'admin/Attribute_family@get_families_server_side');
    Route::get('create', 'admin/Attribute_family@create');
    Route::post('store', 'admin/Attribute_family@store');
    Route::get('edit/{id}', 'admin/Attribute_family@edit');
    Route::post('update', 'admin/Attribute_family@update');
    Route::post('delete/{id}', 'admin/Attribute_family@delete');
    Route::post('status', 'admin/Attribute_family@status');
});

// Order management (admin)
Route::group(['prefix' => 'order'], function () {
    Route::get('/', 'admin/Order@index');
    Route::post('/', 'admin/Order@index');
    Route::get('view/{id}', 'admin/Order@view');
    Route::post('update_status', 'admin/Order@update_status');
    Route::post('get_orders_server_side', 'admin/Order@get_orders_server_side');
    Route::get('export', 'admin/Order@export');
    Route::get('generate_invoice/{id}', 'admin/Order@generate_invoice');
    Route::get('print_invoice/{id}', 'admin/Order@print_invoice');
    Route::get('print_packing_slip/{id}', 'admin/Order@print_packing_slip');
    Route::get('print_shipping_label/{id}', 'admin/Order@print_shipping_label');
    Route::post('shipment', 'admin/Order@add_shipment');
    Route::post('refund', 'admin/Order@add_refund');
});

// Returns / RMA (admin)
Route::group(['prefix' => 'rma'], function () {
    Route::get('/', 'admin/Rma@index');
    Route::post('/', 'admin/Rma@index');
    Route::post('get_rma_server_side', 'admin/Rma@get_rma_server_side');
    Route::get('view/{id}', 'admin/Rma@view');
    Route::post('update_status', 'admin/Rma@update_status');
});

// Product reviews (admin moderation)
Route::group(['prefix' => 'review'], function () {
    Route::get('/', 'admin/Review@index');
    Route::post('/', 'admin/Review@index');
    Route::post('get_reviews_server_side', 'admin/Review@get_reviews_server_side');
    Route::post('approve', 'admin/Review@approve');
    Route::post('reject', 'admin/Review@reject');
    Route::post('reply', 'admin/Review@reply');
    Route::post('delete/{id}', 'admin/Review@delete');
});

// Coupons (admin)
Route::group(['prefix' => 'coupon'], function () {
    Route::get('/', 'admin/Coupon@index');
    Route::post('/', 'admin/Coupon@index');
    Route::get('create', 'admin/Coupon@create');
    Route::post('store', 'admin/Coupon@store');
    Route::get('edit/{id}', 'admin/Coupon@edit');
    Route::post('update', 'admin/Coupon@update');
    Route::post('delete/{id}', 'admin/Coupon@delete');
    Route::post('status', 'admin/Coupon@status');
    Route::post('get_coupons_server_side', 'admin/Coupon@get_coupons_server_side');
});

// Shipping zones + methods (admin)
Route::group(['prefix' => 'shipping'], function () {
    Route::get('/', 'admin/Shipping@index');
    Route::post('/', 'admin/Shipping@index');
    Route::get('zones', 'admin/Shipping@zones');
    Route::get('methods', 'admin/Shipping@methods');
    Route::post('get_zones_server_side', 'admin/Shipping@get_zones_server_side');
    Route::post('get_methods_server_side', 'admin/Shipping@get_methods_server_side');
    Route::get('zone_create', 'admin/Shipping@zone_create');
    Route::post('zone_store', 'admin/Shipping@zone_store');
    Route::get('zone_edit/{id}', 'admin/Shipping@zone_edit');
    Route::post('zone_update', 'admin/Shipping@zone_update');
    Route::post('zone_delete/{id}', 'admin/Shipping@zone_delete');
    Route::post('zone_status', 'admin/Shipping@zone_status');
    Route::get('method_create', 'admin/Shipping@method_create');
    Route::post('method_store', 'admin/Shipping@method_store');
    Route::get('method_edit/{id}', 'admin/Shipping@method_edit');
    Route::post('method_update', 'admin/Shipping@method_update');
    Route::post('method_delete/{id}', 'admin/Shipping@method_delete');
    Route::post('method_status', 'admin/Shipping@method_status');
});

// Tax categories + rates (admin)
Route::group(['prefix' => 'tax'], function () {
    Route::get('/', 'admin/Tax@index');
    Route::post('/', 'admin/Tax@index');
    Route::get('categories', 'admin/Tax@categories');
    Route::get('rates', 'admin/Tax@rates');
    Route::post('get_categories_server_side', 'admin/Tax@get_categories_server_side');
    Route::post('get_rates_server_side', 'admin/Tax@get_rates_server_side');
    Route::get('category_create', 'admin/Tax@category_create');
    Route::post('category_store', 'admin/Tax@category_store');
    Route::get('category_edit/{id}', 'admin/Tax@category_edit');
    Route::post('category_update', 'admin/Tax@category_update');
    Route::post('category_delete/{id}', 'admin/Tax@category_delete');
    Route::post('category_status', 'admin/Tax@category_status');
    Route::get('rate_create', 'admin/Tax@rate_create');
    Route::post('rate_store', 'admin/Tax@rate_store');
    Route::get('rate_edit/{id}', 'admin/Tax@rate_edit');
    Route::post('rate_update', 'admin/Tax@rate_update');
    Route::post('rate_delete/{id}', 'admin/Tax@rate_delete');
    Route::post('rate_status', 'admin/Tax@rate_status');
});

// Cart price rules (admin)
Route::group(['prefix' => 'cart_rule'], function () {
    Route::get('/', 'admin/Cart_rule@index');
    Route::post('/', 'admin/Cart_rule@index');
    Route::get('create', 'admin/Cart_rule@create');
    Route::post('store', 'admin/Cart_rule@store');
    Route::get('edit/{id}', 'admin/Cart_rule@edit');
    Route::post('update', 'admin/Cart_rule@update');
    Route::post('delete/{id}', 'admin/Cart_rule@delete');
    Route::post('status', 'admin/Cart_rule@status');
    Route::post('get_cart_rules_server_side', 'admin/Cart_rule@get_cart_rules_server_side');
});

// Catalog price rules (admin)
Route::group(['prefix' => 'catalog_rule'], function () {
    Route::get('/', 'admin/Catalog_rule@index');
    Route::post('/', 'admin/Catalog_rule@index');
    Route::get('create', 'admin/Catalog_rule@create');
    Route::post('store', 'admin/Catalog_rule@store');
    Route::get('edit/{id}', 'admin/Catalog_rule@edit');
    Route::post('update', 'admin/Catalog_rule@update');
    Route::post('delete/{id}', 'admin/Catalog_rule@delete');
    Route::post('status', 'admin/Catalog_rule@status');
    Route::post('reindex', 'admin/Catalog_rule@reindex');
    Route::post('get_catalog_rules_server_side', 'admin/Catalog_rule@get_catalog_rules_server_side');
});

// Payment settings (admin)
Route::group(['prefix' => 'payment-settings'], function () {
    Route::get('/', 'admin/Paymentsetting@index');
    Route::post('/', 'admin/Paymentsetting@index');
    Route::get('transactions', 'admin/Paymentsetting@transactions');
    Route::post('get_transactions_server_side', 'admin/Paymentsetting@get_transactions_server_side');
});

// CMS pages (admin)
Route::group(['prefix' => 'cms'], function () {
    Route::get('/', 'admin/Cms@index');
    Route::post('/', 'admin/Cms@index');
    Route::get('create', 'admin/Cms@create');
    Route::post('store', 'admin/Cms@store');
    Route::get('edit/{id}', 'admin/Cms@edit');
    Route::post('update', 'admin/Cms@update');
    Route::post('delete/{id}', 'admin/Cms@delete');
    Route::post('status', 'admin/Cms@status');
    Route::post('get_pages_server_side', 'admin/Cms@get_pages_server_side');
});

// FAQ (admin)
Route::group(['prefix' => 'faq'], function () {
    Route::get('/', 'admin/Faq@index');
    Route::post('/', 'admin/Faq@index');
    Route::get('create', 'admin/Faq@create');
    Route::post('store', 'admin/Faq@store');
    Route::get('edit/{id}', 'admin/Faq@edit');
    Route::post('update', 'admin/Faq@update');
    Route::post('delete/{id}', 'admin/Faq@delete');
    Route::post('status', 'admin/Faq@status');
    Route::post('get_faqs_server_side', 'admin/Faq@get_faqs_server_side');
});

// Blog posts (admin)
Route::group(['prefix' => 'blog'], function () {
    Route::get('/', 'admin/Blog@index');
    Route::post('/', 'admin/Blog@index');
    Route::get('create', 'admin/Blog@create');
    Route::post('store', 'admin/Blog@store');
    Route::get('edit/{id}', 'admin/Blog@edit');
    Route::post('update', 'admin/Blog@update');
    Route::post('delete/{id}', 'admin/Blog@delete');
    Route::post('status', 'admin/Blog@status');
    Route::post('get_blogs_server_side', 'admin/Blog@get_blogs_server_side');
});

// Contact messages (admin inbox — read/view/reply/delete)
Route::group(['prefix' => 'contact'], function () {
    Route::get('/', 'admin/Contact@index');
    Route::post('/', 'admin/Contact@index');
    Route::post('get_contacts_server_side', 'admin/Contact@get_contacts_server_side');
    Route::get('view/{id}', 'admin/Contact@view');
    Route::post('reply', 'admin/Contact@reply');
    Route::post('status', 'admin/Contact@status');
    Route::post('delete/{id}', 'admin/Contact@delete');
});

// Complaints (admin) — customer complaints inbox
Route::group(['prefix' => 'complaint'], function () {
    Route::get('/', 'admin/Complaint@index');
    Route::post('/', 'admin/Complaint@index');
    Route::post('get_complaints_server_side', 'admin/Complaint@get_complaints_server_side');
    Route::get('view/{id}', 'admin/Complaint@view');
    Route::post('status', 'admin/Complaint@status');
    Route::post('create_ticket/{id}', 'admin/Complaint@create_ticket');
    Route::post('delete/{id}', 'admin/Complaint@delete');
});

// Support tickets (admin) — reply thread opened on a complaint
Route::group(['prefix' => 'ticket'], function () {
    Route::get('/', 'admin/Ticket@index');
    Route::post('/', 'admin/Ticket@index');
    Route::post('get_tickets_server_side', 'admin/Ticket@get_tickets_server_side');
    Route::get('view/{id}', 'admin/Ticket@view');
    Route::post('reply', 'admin/Ticket@reply');
    Route::post('status', 'admin/Ticket@status');
    Route::post('delete/{id}', 'admin/Ticket@delete');
});

// Banners (admin)
Route::group(['prefix' => 'banner'], function () {
    Route::get('/', 'admin/Banner@index');
    Route::post('/', 'admin/Banner@index');
    Route::get('create', 'admin/Banner@create');
    Route::post('store', 'admin/Banner@store');
    Route::get('edit/{id}', 'admin/Banner@edit');
    Route::post('update', 'admin/Banner@update');
    Route::post('delete/{id}', 'admin/Banner@delete');
    Route::post('status', 'admin/Banner@status');
    Route::post('get_banners_server_side', 'admin/Banner@get_banners_server_side');
});

// Reports & Analytics (admin, read-only)
Route::group(['prefix' => 'report'], function () {
    Route::get('/', 'admin/Report@index');
    Route::get('sales', 'admin/Report@sales');
    Route::get('products', 'admin/Report@products');
    Route::get('customers', 'admin/Report@customers');
    Route::get('inventory', 'admin/Report@inventory');
    Route::get('payments', 'admin/Report@payments');
    Route::get('export/{type}', 'admin/Report@export');
});

// Flash Sale (admin)
Route::group(['prefix' => 'flash_sale'], function () {
    Route::get('/', 'admin/Flash_sale@index');
    Route::post('/', 'admin/Flash_sale@index');
    Route::get('create', 'admin/Flash_sale@create');
    Route::post('store', 'admin/Flash_sale@store');
    Route::get('edit/{id}', 'admin/Flash_sale@edit');
    Route::post('update', 'admin/Flash_sale@update');
    Route::post('delete/{id}', 'admin/Flash_sale@delete');
    Route::post('status', 'admin/Flash_sale@status');
    Route::post('get_flash_sales_server_side', 'admin/Flash_sale@get_flash_sales_server_side');
});

// Newsletter subscribers (admin)
Route::group(['prefix' => 'newsletter'], function () {
    Route::get('/', 'admin/Newsletter@index');
    Route::post('/', 'admin/Newsletter@index');
    Route::post('get_newsletter_server_side', 'admin/Newsletter@get_newsletter_server_side');
    Route::post('status', 'admin/Newsletter@status');
    Route::post('delete/{id}', 'admin/Newsletter@delete');
    Route::get('export', 'admin/Newsletter@export');
});

// Customer groups (admin)
Route::group(['prefix' => 'customer_group'], function () {
    Route::get('/', 'admin/Customer_group@index');
    Route::post('/', 'admin/Customer_group@index');
    Route::post('get_customer_groups_server_side', 'admin/Customer_group@get_customer_groups_server_side');
    Route::get('create', 'admin/Customer_group@create');
    Route::post('store', 'admin/Customer_group@store');
    Route::get('edit/{id}', 'admin/Customer_group@edit');
    Route::post('update', 'admin/Customer_group@update');
    Route::post('delete/{id}', 'admin/Customer_group@delete');
    Route::post('status', 'admin/Customer_group@status');
});

// Payment gateway callbacks (public; success/fail/cancel/ipn are CSRF-exempt)
Route::group(['prefix' => 'payment'], function () {
    Route::post('mock/pay', 'landing/Payment@mock_pay');
    Route::post('mock/cancel', 'landing/Payment@mock_cancel');
    Route::get('mock/{num}', 'landing/Payment@mock');
    Route::post('success', 'landing/Payment@success');
    Route::post('fail', 'landing/Payment@fail');
    Route::post('cancel', 'landing/Payment@cancel');
    Route::post('ipn', 'landing/Payment@ipn');
});

// Digital product downloads (secure token stream + public samples)
Route::group(['prefix' => 'download'], function () {
    Route::get('file/{token}', 'system/Download@file');
    Route::get('sample/{id}', 'system/Download@sample');
});

// -------------------------------------------------------------------------
// Public storefront (server-rendered)
// -------------------------------------------------------------------------
// ============================================================================
// STOREFRONT (public site) — served at ROOT, no 'landing' prefix.
// Defined AFTER all admin routes so admin exact paths (product, product/create,
// order, order/export, …) win; the storefront wildcards (product/{slug},
// order/{num}) catch the rest. `blog` and `contact` are exact collisions with
// the admin, so the public pages use `blogs` and `contact-us`.
// ============================================================================
Route::get('/', 'landing/Landing@index');
Route::get('shop', 'landing/Landing@shop');
Route::get('product/{slug}', 'landing/Landing@product');
Route::get('cart', 'landing/Landing@cart');
Route::post('cart/add', 'landing/Landing@add');
Route::post('cart/update', 'landing/Landing@update');
Route::post('cart/remove', 'landing/Landing@remove');
Route::post('cart/coupon', 'landing/Landing@apply_coupon');
Route::post('cart/coupon/remove', 'landing/Landing@remove_coupon');
Route::get('checkout', 'landing/Landing@checkout');
Route::post('checkout', 'landing/Landing@place_order');
Route::get('order/{num}', 'landing/Landing@order_success');
Route::get('page/{slug}', 'landing/Landing@page');
Route::post('cart/save_for_later', 'landing/Landing@save_for_later');
Route::post('cart/move_to_cart', 'landing/Landing@move_to_cart');
Route::post('cart/remove_saved', 'landing/Landing@remove_saved');
Route::get('faqs', 'landing/Landing@faqs');
Route::get('contact-us', 'landing/Landing@contact');
Route::post('contact-us/submit', 'landing/Landing@submit_contact');
Route::get('blogs', 'landing/Landing@blog');
Route::get('blogs/{slug}', 'landing/Landing@blog_post');
Route::post('subscribe', 'landing/Landing@subscribe');
Route::post('product/{slug}/review', 'landing/Landing@submit_review');

// Customer accounts (server-rendered storefront)
Route::get('account', 'landing/Account@index');
Route::get('account/login', 'landing/Account@login');
Route::post('account/login', 'landing/Account@authenticate');
Route::get('account/register', 'landing/Account@register');
Route::post('account/register', 'landing/Account@do_register');
Route::get('account/logout', 'landing/Account@logout');
Route::get('account/verify-email/{token}', 'landing/Account@verify_email');
Route::get('account/resend-verification', 'landing/Account@resend_verification');
Route::get('account/orders', 'landing/Account@orders');
Route::post('account/profile', 'landing/Account@update_profile');

// Wishlist (customer)
Route::get('account/wishlist', 'landing/Account@wishlist');
Route::post('account/wishlist/toggle', 'landing/Account@wishlist_toggle');
Route::post('account/wishlist/remove', 'landing/Account@wishlist_remove');
Route::get('account/returns', 'landing/Account@returns');
Route::get('account/return/{num}', 'landing/Account@return_form');
Route::post('account/return', 'landing/Account@submit_return');

// Digital downloads (customer library + free samples)
Route::get('account/downloads', 'landing/Account@downloads');
Route::get('account/support', 'landing/Account@support');
// Support desk (storefront): complaints + tickets
Route::get('account/complaints', 'landing/Account@complaints');
Route::get('account/complaint_form', 'landing/Account@complaint_form');
Route::post('account/submit_complaint', 'landing/Account@submit_complaint');
Route::get('account/complaint_view/{id}', 'landing/Account@complaint_view');
Route::get('account/tickets', 'landing/Account@tickets');
Route::get('account/ticket_view/{id}', 'landing/Account@ticket_view');
Route::post('account/ticket_reply', 'landing/Account@ticket_reply');

// Compare (session-based, guest-friendly)
Route::get('compare', 'landing/Landing@compare');
Route::post('compare/add', 'landing/Landing@compare_add');
Route::post('compare/remove', 'landing/Landing@compare_remove');
Route::post('compare/clear', 'landing/Landing@compare_clear');
