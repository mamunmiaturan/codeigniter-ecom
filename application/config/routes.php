<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
// $route['default_controller'] = 'authentication';
// $route['home'] = 'authentication'; 
// $route['404_override'] = 'home/show_404';
// $route['translate_uri_dashes'] = FALSE;


$route['default_controller'] = 'home';
$route['favicon.ico'] = 'auth/authentication/favicon';
$route['home'] = 'auth/authentication';
$route['home/authentication'] = 'auth/authentication';
$route['home/contact'] = 'landing/landing/index';
$route['404_override'] = 'site/show_404';
$route['translate_uri_dashes'] = FALSE;

// -------------------------------------------------------------------------
// LARAVEL-STYLE ROUTING INTEGRATION
// -------------------------------------------------------------------------
// Require Route class and Laravel-style web routes definition
require_once APPPATH . 'config/Route.php';
require_once APPPATH . 'config/web.php';

// Require API routes wrapped in an 'api' prefix group
Route::group(['prefix' => 'api'], function() {
    require_once APPPATH . 'config/api.php';
});

// Merge Laravel routes into the native CodeIgniter $route array
$route = array_merge($route, Route::get_routes());

// -------------------------------------------------------------------------
// CLI COMMAND ROUTES (php artisan ... -> php index.php <controller> <method>)
// -------------------------------------------------------------------------
// These controllers live in controllers/system/ and are CLI-only. They MUST be
// registered as plain-string native routes (not Route::any verb-arrays): CI3
// matches CLI requests under the 'cli' verb, which the HTTP-verb-keyed arrays
// don't contain, so those would fall through to default routing and 404 because
// the class isn't in controllers/ root. The (.+) catch preserves the method and
// any trailing flags (--force, --seed) as segments; the controllers read those
// flags from $_SERVER['argv']. None of these have HTTP routes, so no conflict.
foreach (['migrate', 'migrategenerator', 'queue', 'dbtool', 'maintenance', 'cron'] as $cli_cmd) {
    $route[$cli_cmd]          = 'system/' . $cli_cmd;
    $route[$cli_cmd . '/(.+)'] = 'system/' . $cli_cmd . '/$1';
}
// language sync is CLI-only but the controller lives in admin/ and already has
// HTTP routes, so map just the one command instead of a catch-all.
$route['language/sync_cli'] = 'admin/language/sync_cli';

if (!function_exists('route_hash')) {
    function route_hash($method)
    {
        return $method;
    }
}