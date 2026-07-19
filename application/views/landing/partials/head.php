<?php defined('BASEPATH') or exit('No direct script access allowed');
$site_name = get_global_setting('site_name') ?: 'Bazaar'; ?>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo html_escape($title); ?> · <?php echo html_escape($site_name); ?></title>
    <meta name="description" content="Shop <?php echo html_escape($site_name); ?> — real products, real prices, cash on delivery across Bangladesh.">
    <?php
    // Use the SAME system favicon as the admin panel (uploads/app_image/), so the
    // storefront and backend share one icon. Falls back to the bundled asset only
    // if the system favicon file is missing.
    $__fav = get_global_setting('favicon');
    if (empty($__fav)) { $__fav = 'favicon.png'; }
    $__fav_path = 'uploads/app_image/' . $__fav;
    $__fav_url = file_exists(FCPATH . $__fav_path)
        ? base_url($__fav_path . '?v=' . filemtime(FCPATH . $__fav_path))
        : base_url('assets/frontend/assets/img/favicon.png');
    ?>
    <link rel="icon" href="<?php echo $__fav_url; ?>">
    <link rel="shortcut icon" href="<?php echo $__fav_url; ?>">
    <link rel="apple-touch-icon" href="<?php echo $__fav_url; ?>">
    <link href="<?php echo base_url('assets/frontend/vendors/bootstrap/css/bootstrap.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo base_url('assets/frontend/vendors/bootstrap-icons/bootstrap-icons.css'); ?>" rel="stylesheet">
    <link href="<?php echo base_url('assets/frontend/assets/css/main.css'); ?>" rel="stylesheet">
    <?php
    // Landing Setting: dynamic accent colour + font for the storefront.
    $__la = get_global_setting('landing_accent_color');
    $__lf = get_global_setting('landing_font');
    $__lstack = ['Inter' => "'Inter',sans-serif", 'Roboto' => "'Roboto',sans-serif", 'Poppins' => "'Poppins',sans-serif", 'Open Sans' => "'Open Sans',sans-serif", 'Lato' => "'Lato',sans-serif", 'Montserrat' => "'Montserrat',sans-serif", 'Nunito' => "'Nunito',sans-serif"];
    ?>
    <?php if ($__lf && isset($__lstack[$__lf])): ?>
    <link href="https://fonts.googleapis.com/css2?family=<?php echo rawurlencode($__lf); ?>:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        <?php if ($__la): ?>:root { --accent-color: <?php echo html_escape($__la); ?> !important; }<?php endif; ?>
        <?php if ($__lf && isset($__lstack[$__lf])): ?>body.landing { font-family: <?php echo $__lstack[$__lf]; ?> !important; }<?php endif; ?>
        body.landing { background:#ffffff; }
        .ls-header { background:#fff; border-bottom:1px solid #eceef1; }
        .ls-brand { font-weight:800; font-size:1.35rem; letter-spacing:-.5px; text-decoration:none; color:#1a1a2e; }
        .ls-brand span { color:#4f46e5; }
        .ls-navlink { color:#4b5563; text-decoration:none; font-weight:500; font-size:.9rem; padding:.35rem .6rem; border-radius:8px; white-space:nowrap; }
        .ls-navlink:hover, .ls-navlink.active { color:#4f46e5; background:#eef2ff; }
        .ls-product-card { background:#fff; border:1px solid #eceef1; border-radius:16px; overflow:hidden; height:100%; display:flex; flex-direction:column; transition:.18s; }
        .ls-product-card:hover { box-shadow:0 10px 28px rgba(20,20,50,.10); transform:translateY(-3px); }
        .ls-product-media { position:relative; aspect-ratio:1/1; background:#f2f3f5; overflow:hidden; }
        .ls-product-media img { width:100%; height:100%; object-fit:cover; }
        .ls-badge-sale { position:absolute; top:10px; left:10px; background:#ef4444; color:#fff; font-size:.72rem; font-weight:700; padding:.2rem .5rem; border-radius:6px; }
        .ls-product-body { padding:.9rem 1rem 1rem; display:flex; flex-direction:column; gap:.25rem; flex:1; }
        .ls-cat-tag { font-size:.72rem; color:#9aa0ac; text-transform:uppercase; letter-spacing:.4px; }
        .ls-product-title { font-size:.95rem; font-weight:600; line-height:1.3; margin:0; }
        .ls-product-title a { color:#1a1a2e; text-decoration:none; }
        .ls-product-title a:hover { color:#4f46e5; }
        .ls-price { font-weight:800; color:#16a34a; font-size:1.02rem; }
        .ls-price del { color:#b0b4bb; font-weight:500; font-size:.82rem; margin-left:.3rem; }
        .ls-add-btn { border:0; background:#111827; color:#fff; width:38px; height:38px; border-radius:10px; display:grid; place-items:center; cursor:pointer; }
        .ls-add-btn:hover { background:#4f46e5; }
        .ls-cat-tile { background:#fff; border:1px solid #eceef1; border-radius:16px; padding:1.4rem .5rem; text-align:center; text-decoration:none; display:block; transition:.16s; }
        .ls-cat-tile:hover { border-color:#c7d2fe; box-shadow:0 8px 20px rgba(20,20,50,.08); }
        .ls-cat-tile i { font-size:1.7rem; color:#4f46e5; }
        .ls-cat-tile span { display:block; margin-top:.4rem; font-weight:600; color:#1f2937; font-size:.9rem; }
        .ls-hero { background:linear-gradient(120deg,#4f46e5 0%,#7c3aed 55%,#9333ea 100%); color:#fff; border-radius:0 0 28px 28px; }
        .section-title { font-weight:700; letter-spacing:-.4px; }
        .ls-summary { background:#fff; border:1px solid #eceef1; border-radius:16px; }
    </style>
</head>
