<?php
/**
 * Load .env file manually for CodeIgniter 3
 */
if (file_exists(__DIR__ . '/.env')) {
	$lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	foreach ($lines as $line) {

		// Remove leading/trailing spaces
		$line = trim($line);

		// Skip comments and empty lines
		if ($line === '' || strpos($line, '#') === 0) {
			continue;
		}

		// Skip invalid lines that don't contain "="
		if (strpos($line, '=') === false) {
			continue;
		}

		// Safely split into name and value
		[$name, $value] = array_pad(explode('=', $line, 2), 2, '');

		$name = trim($name);
		$value = trim($value);

		// Remove surrounding quotes if present
		if (preg_match('/^["\'](.*)["\']$/', $value, $matches)) {
			$value = $matches[1];
		}

		putenv(sprintf('%s=%s', $name, $value));
		$_ENV[$name] = $value;
		$_SERVER[$name] = $value;
	}
}

/**
 * --- EMERGENCY FIREWALL & ADMIN WHITELISTING ---
 */
$is_cli = (PHP_SAPI === 'cli' || defined('STDIN'));
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// 1. Admin Whitelisting
if (!$is_cli && getenv('ADMIN_WHITELIST_ENABLED') === 'true') {
	$uri = $_SERVER['REQUEST_URI'] ?? '';
	$admin_paths = ['/admin', '/authentication', '/maintenance', '/migrate', '/dbtool'];
	$is_admin_route = false;
	foreach ($admin_paths as $path) {
		if (strpos($uri, $path) !== false) {
			$is_admin_route = true;
			break;
		}
	}

	if ($is_admin_route) {
		$allowed_ips = explode(',', getenv('ADMIN_ALLOWED_IPS') ?: '127.0.0.1');
		$allowed_ips = array_map('trim', $allowed_ips);
		if (!in_array($ip_address, $allowed_ips)) {
			header('HTTP/1.1 403 Forbidden');
			exit("Access Denied: Your IP ($ip_address) is not authorized to access this area.");
		}
	}
}

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2019, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */

/*
 *---------------------------------------------------------------
 * APPLICATION ENVIRONMENT
 *---------------------------------------------------------------
 *
 * You can load different configurations depending on your
 * current environment. Setting the environment also influences
 * things like logging and error reporting.
 *
 * This can be set to anything, but default usage is:
 *
 *     development
 *     testing
 *     production
 *
 * NOTE: If you change these, also change the error_reporting() code below
 */
define('ENVIRONMENT', isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'production');

/*
 *---------------------------------------------------------------
 * ERROR REPORTING
 *---------------------------------------------------------------
 *
 * Different environments will require different levels of error reporting.
 * By default development will show errors but testing and live will hide them.
 */
switch (ENVIRONMENT) {
	case 'development':
		error_reporting(-1);
		ini_set('display_errors', 1);
		break;

	case 'testing':
	case 'production':
		ini_set('display_errors', 0);
		// E_STRICT was removed (deprecated → fatal) in PHP 8.4. Even calling
		// constant('E_STRICT') triggers the deprecation, so we gate by version.
		$mask = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_NOTICE & ~E_USER_DEPRECATED;
		if (PHP_VERSION_ID < 80400) {
			$mask &= ~2048; // numeric value of E_STRICT for pre-8.4
		}
		error_reporting($mask);
		break;

	default:
		header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
		echo 'The application environment is not set correctly.';
		exit(1); // EXIT_ERROR
}

/*
 *---------------------------------------------------------------
 * SYSTEM DIRECTORY NAME
 *---------------------------------------------------------------
 *
 * This variable must contain the name of your "system" directory.
 * Set the path if it is not in the same directory as this file.
 */
$system_path = 'system';

/*
 *---------------------------------------------------------------
 * APPLICATION DIRECTORY NAME
 *---------------------------------------------------------------
 *
 * If you want this front controller to use a different "application"
 * directory than the default one you can set its name here. The directory
 * can also be renamed or relocated anywhere on your server. If you do,
 * use an absolute (full) server path.
 * For more info please see the user guide:
 *
 * https://codeigniter.com/user_guide/general/managing_apps.html
 *
 * NO TRAILING SLASH!
 */
$application_folder = 'application';

/*
 *---------------------------------------------------------------
 * VIEW DIRECTORY NAME
 *---------------------------------------------------------------
 *
 * If you want to move the view directory out of the application
 * directory, set the path to it here. The directory can be renamed
 * and relocated anywhere on your server. If blank, it will default
 * to the standard location inside your application directory.
 * If you do move this, use an absolute (full) server path.
 *
 * NO TRAILING SLASH!
 */
$view_folder = '';


/*
 * --------------------------------------------------------------------
 * DEFAULT CONTROLLER
 * --------------------------------------------------------------------
 *
 * Normally you will set your default controller in the routes.php file.
 * You can, however, force a custom routing by hard-coding a
 * specific controller class/function here. For most applications, you
 * WILL NOT set your routing here, but it's an option for those
 * special instances where you might want to override the standard
 * routing in a specific front controller that shares a common CI installation.
 *
 * IMPORTANT: If you set the routing here, NO OTHER controller will be
 * callable. In essence, this preference limits your application to ONE
 * specific controller. Leave the function name blank if you need
 * to call functions dynamically via the URI.
 *
 * Un-comment the $routing array below to use this feature
 */
// The directory name, relative to the "controllers" directory.  Leave blank
// if your controller is not in a sub-directory within the "controllers" one
// $routing['directory'] = '';

// The controller class file name.  Example:  mycontroller
// $routing['controller'] = '';

// The controller function you wish to be called.
// $routing['function']	= '';


/*
 * -------------------------------------------------------------------
 *  CUSTOM CONFIG VALUES
 * -------------------------------------------------------------------
 *
 * The $assign_to_config array below will be passed dynamically to the
 * config class when initialized. This allows you to set custom config
 * items or override any default config values found in the config.php file.
 * This can be handy as it permits you to share one application between
 * multiple front controller files, with each file containing different
 * config values.
 *
 * Un-comment the $assign_to_config array below to use this feature
 */
// $assign_to_config['name_of_config_item'] = 'value of config item';



// --------------------------------------------------------------------
// END OF USER CONFIGURABLE SETTINGS.  DO NOT EDIT BELOW THIS LINE
// --------------------------------------------------------------------

/*
 * ---------------------------------------------------------------
 *  Resolve the system path for increased reliability
 * ---------------------------------------------------------------
 */

// Set the current directory correctly for CLI requests
if (defined('STDIN')) {
	chdir(dirname(__FILE__));
}

if (($_temp = realpath($system_path)) !== FALSE) {
	$system_path = $_temp . DIRECTORY_SEPARATOR;
} else {
	// Ensure there's a trailing slash
	$system_path = strtr(
		rtrim($system_path, '/\\'),
		'/\\',
		DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR
	) . DIRECTORY_SEPARATOR;
}

// Is the system path correct?
if (!is_dir($system_path)) {
	header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
	echo 'Your system folder path does not appear to be set correctly. Please open the following file and correct this: ' . pathinfo(__FILE__, PATHINFO_BASENAME);
	exit(3); // EXIT_CONFIG
}

/*
 * -------------------------------------------------------------------
 *  Now that we know the path, set the main path constants
 * -------------------------------------------------------------------
 */
// The name of THIS file
define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));

// Path to the system directory
define('BASEPATH', $system_path);

// Path to the front controller (this file) directory
define('FCPATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);

// Name of the "system" directory
define('SYSDIR', basename(BASEPATH));

// The path to the "application" directory
if (is_dir($application_folder)) {
	if (($_temp = realpath($application_folder)) !== FALSE) {
		$application_folder = $_temp;
	} else {
		$application_folder = strtr(
			rtrim($application_folder, '/\\'),
			'/\\',
			DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR
		);
	}
} elseif (is_dir(BASEPATH . $application_folder . DIRECTORY_SEPARATOR)) {
	$application_folder = BASEPATH . strtr(
		trim($application_folder, '/\\'),
		'/\\',
		DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR
	);
} else {
	header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
	echo 'Your application folder path does not appear to be set correctly. Please open the following file and correct this: ' . SELF;
	exit(3); // EXIT_CONFIG
}

define('APPPATH', $application_folder . DIRECTORY_SEPARATOR);

// The path to the "views" directory
if (!isset($view_folder[0]) && is_dir(APPPATH . 'views' . DIRECTORY_SEPARATOR)) {
	$view_folder = APPPATH . 'views';
} elseif (is_dir($view_folder)) {
	if (($_temp = realpath($view_folder)) !== FALSE) {
		$view_folder = $_temp;
	} else {
		$view_folder = strtr(
			rtrim($view_folder, '/\\'),
			'/\\',
			DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR
		);
	}
} elseif (is_dir(APPPATH . $view_folder . DIRECTORY_SEPARATOR)) {
	$view_folder = APPPATH . strtr(
		trim($view_folder, '/\\'),
		'/\\',
		DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR
	);
} else {
	header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
	echo 'Your view folder path does not appear to be set correctly. Please open the following file and correct this: ' . SELF;
	exit(3); // EXIT_CONFIG
}

define('VIEWPATH', $view_folder . DIRECTORY_SEPARATOR);


/*
 * --------------------------------------------------------------------
 *  SECURE HEADERS
 * --------------------------------------------------------------------
 */
if (!$is_cli) {
	header("X-Frame-Options: SAMEORIGIN");
	header("X-XSS-Protection: 1; mode=block");
	header("X-Content-Type-Options: nosniff");
	header("Referrer-Policy: strict-origin-when-cross-origin");
	header("Permissions-Policy: camera=(), microphone=(), geolocation=(), interest-cohort=()");
	// HSTS: preload only in production so dev hostnames don't get pinned to HTTPS.
	if ((getenv('CI_ENV') ?: 'production') === 'production') {
		header("Strict-Transport-Security: max-age=63072000; includeSubDomains; preload");
	} else {
		header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
	}
	// Cross-origin isolation — denies window.opener leakage and prevents Spectre-class side-channels.
	header("Cross-Origin-Opener-Policy: same-origin");
	header("Cross-Origin-Resource-Policy: same-site");
	// CSP — optional report endpoint via CSP_REPORT_URI env (e.g. report-uri.com).
	$csp = "default-src 'self'; "
		. "script-src 'self' 'unsafe-inline' https://js.pusher.com https://www.google.com https://www.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
		. "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
		. "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; "
		. "img-src 'self' data: https:; "
		. "connect-src 'self' wss://*.pusher.com https://*.pusher.com; "
		. "frame-src 'none'; object-src 'none'; base-uri 'self'; form-action 'self';";
	if ($report_uri = getenv('CSP_REPORT_URI')) {
		$csp .= " report-uri " . $report_uri . ";";
	}
	header("Content-Security-Policy: " . $csp);

	// Dynamic HTML pages must never be cached by proxies or shared caches
	$uri = $_SERVER['REQUEST_URI'] ?? '';
	$is_static_asset = preg_match('#\.(css|js|png|jpg|jpeg|gif|ico|woff2?|ttf|svg)(\?.*)?$#i', $uri);
	if ($is_static_asset) {
		header("Cache-Control: public, max-age=31536000, immutable");
	} else {
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Pragma: no-cache");
	}
}

/*
 * --------------------------------------------------------------------
 *  RESOLVE REAL USER IP (PROXY & CLOUDFLARE SECURE)
 * --------------------------------------------------------------------
 */
$real_user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
	$real_user_ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	$ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
	$real_user_ip = trim($ips[0]);
}

// Validate IP format to prevent header injection attacks
if (!filter_var($real_user_ip, FILTER_VALIDATE_IP)) {
	$real_user_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/*
 * --------------------------------------------------------------------
 *  SYSTEM FIREWALL CHECK (IP BLOCKING)
 * --------------------------------------------------------------------
 */
if (!$is_cli && file_exists(APPPATH . 'config/database.php')) {
	include(APPPATH . 'config/database.php');
	$db_config = $db['default'];

	try {
		$conn = @mysqli_connect($db_config['hostname'], $db_config['username'], $db_config['password'], $db_config['database']);
		if ($conn) {
			$ip = mysqli_real_escape_string($conn, $real_user_ip);
			// Check if table exists first to avoid fatal errors during migrations
			$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'firewall_ips'");
			if ($table_check && mysqli_num_rows($table_check) > 0) {
				$query = mysqli_query($conn, "SELECT id FROM firewall_ips WHERE ip_address = '$ip' LIMIT 1");
				if ($query && mysqli_num_rows($query) > 0) {
					http_response_code(403);
					die("<h1>403 Forbidden</h1><p>Your IP address ($ip) has been blocked by the system firewall.</p>");
				}
			}
			mysqli_close($conn);
		}
	} catch (Exception $e) {
		// Log the error using native PHP error_log as CI log_message is not yet available
		error_log('Firewall DB Connection failed: ' . $e->getMessage());
	}
}

/*
 * --------------------------------------------------------------------
 *  MAINTENANCE MODE CHECK
 * --------------------------------------------------------------------
 */
if (file_exists(FCPATH . '.maintenance') && !isset($_SERVER['argv'])) {
	$data = json_decode(file_get_contents(FCPATH . '.maintenance'), true);
	$bypass = false;

	// Check for IP bypass using resolved real IP
	if (in_array($real_user_ip, $data['allowed_ips'])) {
		$bypass = true;
	}

	// Check for Token bypass (via cookie)
	if (isset($_COOKIE['maintenance_bypass']) && $_COOKIE['maintenance_bypass'] === $data['secret']) {
		$bypass = true;
	}

	if (!$bypass) {
		http_response_code(503);
		include('maintenance_page.php');
		exit;
	}
}

/*
 * --------------------------------------------------------------------
 * LOAD THE BOOTSTRAP FILE
 * --------------------------------------------------------------------
 *
 * And away we go...
 */
require_once BASEPATH . 'core/CodeIgniter.php';
