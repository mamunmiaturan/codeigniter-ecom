<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| API Routes (Laravel-style)
|--------------------------------------------------------------------------
| All routes here are auto-prefixed with "api/" by routes.php's group wrapper.
*/

// Authentication endpoints
Route::post('v1/auth/login',   'api/Auth@login');
Route::post('v1/auth/refresh', 'api/Auth@refresh');
Route::post('v1/auth/logout',  'api/Auth@logout');
Route::get('v1/auth/me',       'api/Auth@me');

// Storefront catalog (public, read-only)
Route::get('v1/categories',        'api/Catalog@categories');
Route::get('v1/categories/{slug}', 'api/Catalog@category');
Route::get('v1/brands',            'api/Catalog@brands');
Route::get('v1/brands/{slug}',     'api/Catalog@brand');
Route::get('v1/attributes',        'api/Catalog@attributes');
Route::get('v1/products',          'api/Catalog@products');
Route::get('v1/products/{slug}',   'api/Catalog@product');

// Product reviews (list public; submit requires a customer bearer token)
Route::get('v1/products/{slug}/reviews',  'api/Review@index');
Route::post('v1/products/{slug}/reviews', 'api/Review@create');

// Storefront customer accounts
Route::post('v1/customer/register', 'api/Customer@register');
Route::get('v1/customer/profile',   'api/Customer@profile');
Route::post('v1/customer/profile',  'api/Customer@update_profile');
Route::get('v1/customer/downloads', 'api/Customer@downloads');

// Saved addresses (auth)
Route::get('v1/customer/addresses',         'api/Address@index');
Route::post('v1/customer/addresses',        'api/Address@create');
Route::post('v1/customer/addresses/update', 'api/Address@update');
Route::post('v1/customer/addresses/delete', 'api/Address@remove');
Route::post('v1/customer/addresses/default','api/Address@set_default');

// Shopping cart (guest or authenticated)
Route::get('v1/cart',         'api/Cart@index');
Route::post('v1/cart/add',    'api/Cart@add');
Route::post('v1/cart/update', 'api/Cart@update');
Route::post('v1/cart/remove', 'api/Cart@remove');
Route::post('v1/cart/clear',  'api/Cart@clear');
Route::post('v1/cart/merge',  'api/Cart@merge');
Route::post('v1/cart/coupon',        'api/Cart@apply_coupon');
Route::post('v1/cart/coupon/remove', 'api/Cart@remove_coupon');

// Wishlist (customer bearer token)
Route::get('v1/wishlist',         'api/Wishlist@index');
Route::post('v1/wishlist/add',    'api/Wishlist@add');
Route::post('v1/wishlist/remove', 'api/Wishlist@remove');
Route::post('v1/wishlist/toggle', 'api/Wishlist@toggle');

// Checkout + customer orders
Route::post('v1/checkout',       'api/Checkout@index');
Route::get('v1/orders',          'api/Order@index');
Route::get('v1/orders/{number}', 'api/Order@show');
