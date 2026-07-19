<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Safely fetch CI instance to respect software skin settings without breaking during early boots
$theme_config = [];
$redirect_url = '/';
$actual_base_url = '/';

if (function_exists('get_instance') && class_exists('CI_Controller')) {
    $CI =& get_instance();
    if (isset($CI->db)) {
        $theme_config = $CI->db->get_where('theme_settings', array('id' => 1))->row_array();
    }
    if (function_exists('base_url')) {
        $redirect_url = base_url('dashboard');
        $actual_base_url = base_url();
    }
}

// Manual base_url reconstruction fallback if URL helper is not loaded
if ($actual_base_url === '/') {
    $http = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $path = str_replace(basename($script), '', $script);
    $actual_base_url = $http . '://' . $host . $path;
    $redirect_url = $actual_base_url . 'dashboard';
}
?><!DOCTYPE html>
<html lang="en" class="<?php echo (isset($theme_config['dark_skin']) && $theme_config['dark_skin'] == 'true' ? 'dark' : ''); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Error</title>
    <link rel="shortcut icon" href="<?php echo $actual_base_url . 'uploads/app_image/logo.png'; ?>">
    
    <!-- Modern Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo $actual_base_url . 'assets/backend/vendor/font-awesome/css/all.min.css'; ?>">
    
    <style type="text/css">
        :root {
            --primary: #5352ed;
            --primary-hover: #3742fa;
            --bg-color: #f0f3f8;
            --card-bg: #ffffff;
            --text-main: #2f3542;
            --text-muted: #747d8c;
            --border-color: #dfe4ea;
            --warning-color: #ffa502;
        }

        html.dark {
            --bg-color: #171717;
            --card-bg: #1c1c24;
            --text-main: #e6edf3;
            --text-muted: #8a99ad;
            --border-color: #232330;
            --warning-color: #ffa502;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Outfit', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            transition: all 0.3s ease;
        }

        /* Container matching user software forms */
        #container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 460px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 35px 30px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            animation: fadeInUp 0.6s ease-out;
        }

        html.dark #container {
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-box {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo-box img {
            height: 65px;
            width: auto;
        }

        .error-icon {
            font-size: 60px;
            color: var(--warning-color);
            margin-bottom: 12px;
        }

        h1 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 14px;
            color: var(--text-main);
        }

        p {
            font-size: 14px;
            line-height: 1.6;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        #container p {
            margin-bottom: 20px;
        }

        /* Submit Button matching user software style */
        .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 35px;
            border-radius: 50px;
            font-family: 'Outfit', sans-serif;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(83, 82, 237, 0.3);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
            transform: scale(1.03);
            box-shadow: 0 15px 30px rgba(83, 82, 237, 0.4);
            color: white;
        }

        /* Code/Debug Box */
        code {
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 13px;
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--warning-color);
            display: block;
            margin: 20px 0;
            padding: 12px 16px;
            text-align: left;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div id="container">
        <div class="logo-box">
            <img src="<?php echo $actual_base_url . 'uploads/app_image/logo.png'; ?>" alt="Auth Logo">
        </div>
        <div class="error-icon">
            <i class="fas fa-database"></i>
        </div>
        <h1><?php echo $heading; ?></h1>
        <?php echo $message; ?>
        
        <div style="margin-top: 15px;">
            <a href="<?php echo $redirect_url; ?>" class="btn-submit">
                Return to Safety <i class="fas fa-shield-alt"></i>
            </a>
        </div>
    </div>
</body>
</html>