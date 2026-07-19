<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Laravel-style Route Registrar for CodeIgniter 3
 * 
 * Enables modern routing syntax:
 * Route::get('user/{id}', 'User@profile');
 */
class Route
{
    private static $routes = [];
    private static $group_options = [];

    public static function group($options, $callback)
    {
        $previous_options = self::$group_options;
        
        // Accumulate prefixes for nested groups
        if (isset($options['prefix'])) {
            $prefix = trim($options['prefix'], '/');
            $current_prefix = isset(self::$group_options['prefix']) ? trim(self::$group_options['prefix'], '/') : '';
            self::$group_options['prefix'] = $current_prefix ? $current_prefix . '/' . $prefix : $prefix;
        }

        call_user_func($callback);

        // Restore options to parent state
        self::$group_options = $previous_options;
    }

    public static function get($uri, $action)
    {
        self::add('GET', $uri, $action);
    }

    public static function post($uri, $action)
    {
        self::add('POST', $uri, $action);
    }

    public static function put($uri, $action)
    {
        self::add('PUT', $uri, $action);
    }

    public static function delete($uri, $action)
    {
        self::add('DELETE', $uri, $action);
    }

    public static function any($uri, $action)
    {
        self::add('ANY', $uri, $action);
    }

    private static function add($method, $uri, $action)
    {
        $uri = trim($uri, '/');

        // Apply group prefix if active
        if (isset(self::$group_options['prefix'])) {
            $prefix = trim(self::$group_options['prefix'], '/');
            $uri = $prefix . ($uri ? '/' . $uri : '');
        }

        // Count how many placeholders are present in the original URI
        $placeholder_count = preg_match_all('/\{[a-zA-Z_]+\??\}/', $uri, $matches);

        // Convert Laravel style placeholders {id} or {id?} to CI3 placeholders (:any)
        $ci_uri = preg_replace('/\{[a-zA-Z_]+\?\}/', '(:any)?', $uri); // Optional parameter
        $ci_uri = preg_replace('/\{[a-zA-Z_]+\}/', '(:any)', $ci_uri);  // Required parameter

        // Convert action 'UserController@index' to CodeIgniter 'user/index'
        if (is_string($action) && strpos($action, '@') !== false) {
            list($controller, $method_name) = explode('@', $action);
            // Normalize controller name (lowercase, strip 'Controller' if present)
            $controller = str_replace('Controller', '', $controller);
            $controller = strtolower($controller);
            $ci_action = $controller . '/' . $method_name;
        } else {
            $ci_action = $action;
        }

        // If placeholders exist, append /$1, /$2, etc. to target action
        if ($placeholder_count > 0) {
            for ($i = 1; $i <= $placeholder_count; $i++) {
                $ci_action .= '/$' . $i;
            }
        }

        $method = strtolower($method);
        if ($method === 'any') {
            $methods = ['get', 'post', 'put', 'delete', 'patch', 'head', 'options'];
            if (!isset(self::$routes[$ci_uri]) || !is_array(self::$routes[$ci_uri])) {
                self::$routes[$ci_uri] = [];
            }
            foreach ($methods as $m) {
                if (!isset(self::$routes[$ci_uri][$m])) {
                    self::$routes[$ci_uri][$m] = $ci_action;
                }
            }
        } else {
            if (!isset(self::$routes[$ci_uri]) || !is_array(self::$routes[$ci_uri])) {
                self::$routes[$ci_uri] = [];
            }
            self::$routes[$ci_uri][$method] = $ci_action;
        }

        // Always answer CORS preflight: map OPTIONS to the same action. The
        // Api_Controller short-circuits OPTIONS with a 204 in its constructor,
        // so the concrete method never runs — this just gives the router a
        // verb to match instead of falling through to a 404.
        if (!isset(self::$routes[$ci_uri]['options'])) {
            self::$routes[$ci_uri]['options'] = $ci_action;
        }
    }

    public static function get_routes()
    {
        return self::$routes;
    }
}
