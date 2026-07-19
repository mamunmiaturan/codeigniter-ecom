<?php
// PHPUnit Bootstrap — CodeIgniter 3 test environment
error_reporting(E_ALL);
define('ENVIRONMENT', 'testing');

$system_path      = __DIR__ . '/../system';
$application_folder = __DIR__ . '/../application';

define('BASEPATH', realpath($system_path)      . '/');
define('APPPATH',  realpath($application_folder) . '/');
define('FCPATH',   realpath(__DIR__ . '/../')    . '/');

// ── CI role constant ──────────────────────────────────────────────────────────
if (!defined('ROLE_SUPERMAN_ID')) {
    define('ROLE_SUPERMAN_ID', 1);
}

// ── Minimal CI global stubs ───────────────────────────────────────────────────
if (!function_exists('get_instance')) {
    function &get_instance() {
        static $ci;
        if (!isset($ci)) {
            $ci = new stdClass();
        }
        return $ci;
    }
}

if (!function_exists('config_item')) {
    function config_item($item) {
        static $defaults = ['charset' => 'UTF-8', 'base_url' => 'http://localhost/'];
        return $defaults[$item] ?? null;
    }
}

if (!function_exists('log_message')) {
    function log_message($level, $message) { /* swallow in tests */ }
}

if (!function_exists('base_url')) {
    function base_url($uri = '') {
        return 'http://localhost/' . ltrim($uri, '/');
    }
}

// ── Load application helpers ──────────────────────────────────────────────────
require_once APPPATH . 'helpers/general_helper.php';
