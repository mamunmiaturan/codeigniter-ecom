<?php defined('BASEPATH') or exit('No direct script access allowed');
$site_name = get_global_setting('site_name') ?: 'Bazaar';

// Flash alert (survives the post-redirect PRG pattern)
$flash = null;
$flash_type = '';
foreach (['success', 'error', 'info', 'warning'] as $ty) {
    $m = $this->session->flashdata('alert-message-' . $ty);
    if ($m) {
        $flash = $m;
        $flash_type = $ty;
        break;
    }
}
$flash_class = ['success' => 'success', 'error' => 'danger', 'info' => 'info', 'warning' => 'warning'][$flash_type] ?? 'secondary';

// Storefront footer links from CMS pages flagged show_in_footer.
$CI = &get_instance();
$CI->load->model('cms_page_model');
$footer_pages = $CI->cms_page_model->footer_pages();

// Contact + social details (only rendered when present).
$foot_email   = (string) get_global_setting('site_email');
$foot_phone   = (string) get_global_setting('mobile_no');
$foot_address = (string) get_global_setting('address');
$foot_text    = (string) get_global_setting('footer_text');
$socials = array_filter([
    'facebook'  => (string) get_global_setting('facebook_url'),
    'instagram' => (string) get_global_setting('instagram_url'),
    'twitter-x' => (string) get_global_setting('twitter_url'),
    'youtube'   => (string) get_global_setting('youtube_url'),
    'linkedin'  => (string) get_global_setting('linkedin_url'),
]);

// Top-level categories for the Shop column (tree passed to the layout).
$foot_cats = array_slice($nav_categories ?? [], 0, 6);
$cust = function_exists('current_customer') ? current_customer() : null;
?>
<footer class="ls-footer">
    <div class="container-fluid container-xl">
        <div class="row gy-4">

            <!-- Brand + about + social -->
            <div class="col-lg-4 col-md-6">
                <a href="<?php echo base_url('/'); ?>" class="ls-foot-brand">
                    <img src="<?php echo asset_ver('uploads/app_image/logo-white.png'); ?>" alt="<?php echo html_escape($site_name); ?>" class="ls-foot-logo">
                </a>
                <p class="ls-foot-about">Women&rsquo;s &amp; kids&rsquo; clothing and fashion &mdash; real products, quality you can trust, with cash on delivery across Bangladesh.</p>
                <?php if ($socials): ?>
                    <div class="ls-foot-social">
                        <?php foreach ($socials as $icon => $url): ?>
                            <a href="<?php echo html_escape($url); ?>" target="_blank" rel="noopener" aria-label="<?php echo html_escape($icon); ?>"><i class="bi bi-<?php echo html_escape($icon); ?>"></i></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Shop by category -->
            <div class="col-lg-2 col-6 col-md-3">
                <h6 class="ls-foot-title">Shop</h6>
                <ul class="ls-foot-links">
                    <li><a href="<?php echo base_url('shop'); ?>">All Products</a></li>
                    <?php foreach ($foot_cats as $fc): ?>
                        <li><a href="<?php echo base_url('shop?category=' . urlencode((string) ($fc['slug'] ?? ''))); ?>"><?php echo html_escape((string) ($fc['name'] ?? '')); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Account & help -->
            <div class="col-lg-2 col-6 col-md-3">
                <h6 class="ls-foot-title">Account &amp; Help</h6>
                <ul class="ls-foot-links">
                    <?php if ($cust): ?>
                        <li><a href="<?php echo base_url('account'); ?>">My Account</a></li>
                        <li><a href="<?php echo base_url('account/orders'); ?>">My Orders</a></li>
                        <li><a href="<?php echo base_url('account/wishlist'); ?>">Wishlist</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo base_url('account/login'); ?>">Log in</a></li>
                        <li><a href="<?php echo base_url('account/register'); ?>">Create account</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo base_url('cart'); ?>">My Cart</a></li>
                    <?php
                    $has_contact = false;
                    foreach ($footer_pages as $fp):
                        if (stripos((string) $fp['title'], 'contact') !== false) { $has_contact = true; }
                    ?>
                        <li><a href="<?php echo base_url('page/' . rawurlencode($fp['slug'])); ?>"><?php echo html_escape($fp['title']); ?></a></li>
                    <?php endforeach; ?>
                    <?php if (!$has_contact): ?>
                        <li><a href="<?php echo base_url('contact-us'); ?>">Contact Us</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Contact + newsletter -->
            <div class="col-lg-4 col-md-12">
                <h6 class="ls-foot-title">Get in touch</h6>
                <ul class="ls-foot-contact">
                    <?php if ($foot_phone !== ''): ?>
                        <li><i class="bi bi-telephone"></i><a href="tel:<?php echo html_escape(preg_replace('/\s+/', '', $foot_phone)); ?>"><?php echo html_escape($foot_phone); ?></a></li>
                    <?php endif; ?>
                    <?php if ($foot_email !== ''): ?>
                        <li><i class="bi bi-envelope"></i><a href="mailto:<?php echo html_escape($foot_email); ?>"><?php echo html_escape($foot_email); ?></a></li>
                    <?php endif; ?>
                    <?php if ($foot_address !== ''): ?>
                        <li><i class="bi bi-geo-alt"></i><span><?php echo html_escape($foot_address); ?></span></li>
                    <?php endif; ?>
                </ul>
                <form action="<?php echo base_url('subscribe'); ?>" method="post" class="ls-foot-news">
                    <input type="hidden" name="redirect" value="<?php echo html_escape(current_url()); ?>">
                    <label class="ls-foot-news-label">Subscribe for deals &amp; new arrivals</label>
                    <div class="input-group">
                        <input type="email" name="email" class="form-control" placeholder="Your email address" required>
                        <button class="btn ls-foot-news-btn" type="submit">Subscribe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="ls-footer-bottom">
        <div class="container-fluid container-xl d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="ls-foot-copy"><?php echo $foot_text !== '' ? html_escape($foot_text) : ('© ' . date('Y') . ' ' . html_escape($site_name) . '. All rights reserved.'); ?></span>
            <span class="ls-foot-meta">
                <span class="ls-foot-pay"><i class="bi bi-cash-coin"></i> Cash on Delivery</span>
                <span class="ls-foot-pay"><i class="bi bi-truck"></i> Nationwide Shipping</span>
                <a href="<?php echo base_url('login'); ?>" class="ls-foot-admin"><i class="bi bi-shield-lock"></i> Admin</a>
            </span>
        </div>
    </div>
</footer>

<style>
    .ls-footer {
        margin-top: 0;
        background: #14171f;
        color: rgba(255, 255, 255, 0.72);
        padding: 3.25rem 0 0;
        font-size: 14px;
    }
    .ls-foot-brand {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #fff;
        text-decoration: none;
        margin-bottom: 0.9rem;
    }
    .ls-foot-logo { height: 42px; width: auto; max-width: 200px; object-fit: contain; display: block; }
    .ls-foot-brand i { color: var(--accent-color, #4f46e5); font-size: 1.5rem; }
    .ls-foot-about { line-height: 1.7; max-width: 340px; margin-bottom: 1rem; text-align: justify; }
    .ls-foot-social { display: flex; gap: 10px; }
    .ls-foot-social a {
        display: inline-flex; align-items: center; justify-content: center;
        width: 38px; height: 38px; border-radius: 50%;
        background: rgba(255, 255, 255, 0.08); color: #fff;
        text-decoration: none; font-size: 17px; transition: all 0.2s ease;
    }
    .ls-foot-social a:hover { background: var(--accent-color, #4f46e5); transform: translateY(-2px); }
    .ls-foot-title {
        color: #fff; font-size: 15px; font-weight: 700;
        margin-bottom: 1rem; text-transform: uppercase; letter-spacing: .4px;
    }
    .ls-foot-links, .ls-foot-contact { list-style: none; padding: 0; margin: 0; }
    .ls-foot-links li { margin-bottom: 0.55rem; }
    .ls-foot-links a { color: rgba(255, 255, 255, 0.72); text-decoration: none; transition: color 0.15s ease; }
    .ls-foot-links a:hover { color: #fff; padding-left: 3px; }
    .ls-foot-contact li { display: flex; gap: 10px; margin-bottom: 0.7rem; line-height: 1.5; }
    .ls-foot-contact i { color: var(--accent-color, #4f46e5); font-size: 16px; margin-top: 2px; }
    .ls-foot-contact a { color: rgba(255, 255, 255, 0.72); text-decoration: none; }
    .ls-foot-contact a:hover { color: #fff; }
    .ls-foot-news { margin-top: 1.1rem; }
    .ls-foot-news-label { display: block; margin-bottom: 0.5rem; font-size: 13px; }
    .ls-foot-news .form-control {
        background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.14);
        color: #fff; box-shadow: none;
    }
    .ls-foot-news .form-control::placeholder { color: rgba(255, 255, 255, 0.45); }
    .ls-foot-news .form-control:focus { background: rgba(255, 255, 255, 0.12); border-color: var(--accent-color, #4f46e5); color: #fff; }
    .ls-foot-news-btn {
        background: var(--accent-color, #4f46e5); color: #fff; font-weight: 600; border: none; white-space: nowrap;
    }
    .ls-foot-news-btn:hover { filter: brightness(1.08); color: #fff; }
    .ls-footer-bottom {
        margin-top: 2.5rem; border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding: 1.1rem 0; font-size: 13px;
    }
    .ls-foot-copy { color: rgba(255, 255, 255, 0.55); }
    .ls-foot-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; }
    .ls-foot-pay { color: rgba(255, 255, 255, 0.6); display: inline-flex; align-items: center; gap: 6px; }
    .ls-foot-pay i { color: var(--accent-color, #4f46e5); }
    .ls-foot-admin { color: rgba(255, 255, 255, 0.6); text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
    .ls-foot-admin:hover { color: #fff; }
</style>

<?php if ($flash): ?>
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1080">
    <div class="alert alert-<?php echo $flash_class; ?> shadow-sm mb-0" id="ls-flash"><?php echo html_escape($flash); ?></div>
</div>
<script>setTimeout(function(){ var f = document.getElementById('ls-flash'); if (f) f.remove(); }, 3500);</script>
<?php endif; ?>

<script src="<?php echo base_url('assets/frontend/vendors/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
<script>
    // Init Bootstrap tooltips for time_ago() spans (hover shows the actual date).
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.bootstrap || !bootstrap.Tooltip) { return; }
        document.querySelectorAll('[data-toggle="tooltip"], [data-bs-toggle="tooltip"]').forEach(function (el) {
            try { new bootstrap.Tooltip(el); } catch (e) {}
        });
    });
</script>
