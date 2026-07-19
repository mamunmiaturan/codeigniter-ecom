<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en">
<?php $this->load->view('landing/partials/head'); ?>
<body class="landing">
    <?php
    // Top announcement bar. Uses the controller-passed value when present, else
    // loads the active announcement banner on demand so it shows storefront-wide.
    if (!isset($announcement)) {
        $ci =& get_instance();
        $ci->load->model('banner_model');
        $announcement = $ci->banner_model->get_active_by_type('announcement')[0] ?? null;
    }
    if (!empty($announcement)):
        $a_link = trim((string) ($announcement['link_url'] ?? ''));
        $a_href = $a_link === '' ? '' : (strpos($a_link, 'http') === 0 ? $a_link : base_url($a_link));
    ?>
    <div class="ls-announce">
        <div class="container-fluid container-xl text-center">
            <?php if ($a_href !== ''): ?>
                <a href="<?php echo $a_href; ?>"><?php echo html_escape($announcement['title']); ?></a>
            <?php else: ?>
                <span><?php echo html_escape($announcement['title']); ?></span>
            <?php endif; ?>
        </div>
    </div>
    <style>
        .ls-announce { background: #111; color: #fff; font-size: 13px; padding: 7px 0; letter-spacing: .2px; }
        .ls-announce a, .ls-announce span { color: #fff; text-decoration: none; font-weight: 600; }
        .ls-announce a:hover { text-decoration: underline; }
    </style>
    <?php endif; ?>
    <?php $this->load->view('landing/partials/header'); ?>
    <main class="main">
        <?php $this->load->view($content_view); ?>
    </main>
    <?php $this->load->view('landing/partials/footer'); ?>
    <?php
    // One-time-per-session promo popup. Controller-passed value when present,
    // else loaded on demand. Guarded client-side via sessionStorage.
    if (!isset($popup)) {
        $ci =& get_instance();
        $ci->load->model('banner_model');
        $popup = $ci->banner_model->get_active_by_type('popup')[0] ?? null;
    }
    if (!empty($popup)):
        $p_link = trim((string) ($popup['link_url'] ?? ''));
        $p_href = $p_link === '' ? base_url('shop') : (strpos($p_link, 'http') === 0 ? $p_link : base_url($p_link));
        $p_img  = !empty($popup['image']) ? base_url('uploads/banner/' . rawurlencode($popup['image'])) : '';
        $p_btn  = trim((string) ($popup['button_text'] ?? ''));
        $p_key  = 'ls_popup_seen_' . (int) $popup['id'];
    ?>
    <div id="lsPopup" class="ls-popup" role="dialog" aria-modal="true" aria-label="<?php echo html_escape($popup['title'] ?: 'Promotion'); ?>" hidden>
        <div class="ls-popup__backdrop" data-ls-popup-close></div>
        <div class="ls-popup__dialog">
            <button type="button" class="ls-popup__close" data-ls-popup-close aria-label="Close">&times;</button>
            <?php if ($p_img !== ''): ?><img src="<?php echo html_escape($p_img); ?>" alt="<?php echo html_escape($popup['title']); ?>" class="ls-popup__img"><?php endif; ?>
            <div class="ls-popup__body">
                <?php if (!empty($popup['title'])): ?><h4 class="ls-popup__title"><?php echo html_escape($popup['title']); ?></h4><?php endif; ?>
                <?php if (!empty($popup['subtitle'])): ?><p class="ls-popup__text"><?php echo html_escape($popup['subtitle']); ?></p><?php endif; ?>
                <?php if ($p_btn !== ''): ?><a href="<?php echo $p_href; ?>" class="btn btn-dark"><?php echo html_escape($p_btn); ?></a><?php endif; ?>
            </div>
        </div>
    </div>
    <style>
        .ls-popup { position: fixed; inset: 0; z-index: 1080; display: flex; align-items: center; justify-content: center; padding: 16px; }
        .ls-popup[hidden] { display: none; }
        .ls-popup__backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.55); }
        .ls-popup__dialog { position: relative; background: #fff; border-radius: 12px; max-width: 440px; width: 100%; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,.35); }
        .ls-popup__img { width: 100%; height: auto; display: block; }
        .ls-popup__body { padding: 24px; text-align: center; }
        .ls-popup__title { margin: 0 0 8px; font-weight: 700; }
        .ls-popup__text { color: #555; margin: 0 0 16px; }
        .ls-popup__close { position: absolute; top: 8px; right: 12px; border: 0; background: transparent; font-size: 26px; line-height: 1; cursor: pointer; color: #333; z-index: 2; }
    </style>
    <script>
    (function () {
        var key = '<?php echo $p_key; ?>';
        try { if (sessionStorage.getItem(key)) { return; } } catch (e) {}
        var el = document.getElementById('lsPopup');
        if (!el) { return; }
        function close() { el.hidden = true; try { sessionStorage.setItem(key, '1'); } catch (e) {} }
        setTimeout(function () { el.hidden = false; }, 1200);
        el.addEventListener('click', function (e) { if (e.target.hasAttribute('data-ls-popup-close')) { close(); } });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); } });
    })();
    </script>
    <?php endif; ?>
</body>
</html>
