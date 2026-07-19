<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo html_escape((isset($title) ? $title : 'System') . ' - ' . get_site_name()); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $favicon = get_global_setting('favicon');
    if (empty($favicon)) $favicon = 'favicon.png';
    $favicon_path = 'uploads/app_image/' . $favicon;
    $favicon_url = file_exists(FCPATH . $favicon_path) ? base_url($favicon_path) : get_logo_url();
    ?>
    <link rel="icon" href="<?php echo $favicon_url; ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/backend/vendor/font-awesome/css/all.min.css'); ?>">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <script src="<?php echo base_url('assets/backend/vendor/jquery/jquery.js'); ?>"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; width: 100%; overflow: hidden; background: #111827; color: #d1d5db; font-family: 'Inter', -apple-system, sans-serif; }
    </style>
</head>
<body>
<?php $this->load->view($sub_page, $this->data); ?>
</body>
</html>
