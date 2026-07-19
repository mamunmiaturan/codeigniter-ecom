<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Storefront Flash Sale strip. Renders only when the homepage passed an active
 * sale ($flash_sale) with at least one shaped product row ($flash_items). Shows a
 * heading, a live countdown to ends_at, and a product-card grid. Renders nothing
 * otherwise.
 *
 * @var array $flash_sale   the active flash sale row (Flash_sale_model::active)
 * @var array $flash_items  shaped product rows (Flash_sale_model::active_items)
 */
if (empty($flash_sale) || empty($flash_items)) {
    return;
}
$fs_remaining = strtotime($flash_sale['ends_at']) - time();
if ($fs_remaining <= 0) {
    return;
}
?>
<section class="container-fluid container-xl py-4 ls-flash-sale">
    <div class="ls-flash-head d-flex flex-wrap align-items-center mb-4">
        <h2 class="section-title h4 mb-0 me-auto">
            <i class="bi bi-lightning-charge-fill text-warning"></i>
            <?php echo html_escape($flash_sale['title']); ?>
        </h2>
        <div class="ls-flash-timer d-flex align-items-center" data-remaining="<?php echo (int) $fs_remaining; ?>">
            <span class="me-2 fw-semibold text-danger text-uppercase small"><?php echo translate('ends_in') ?: 'Ends in'; ?></span>
            <span class="ls-flash-clock badge bg-dark fs-6">--:--:--</span>
        </div>
        <a href="<?php echo base_url('shop'); ?>" class="btn btn-sm btn-outline-dark ms-3"><?php echo translate('view_all') ?: 'View all'; ?></a>
    </div>
    <div class="row g-4">
        <?php foreach ($flash_items as $p) { $this->load->view('landing/partials/product_card', ['p' => $p]); } ?>
    </div>
    <style>
        .ls-flash-sale .ls-flash-clock { letter-spacing: 1px; font-variant-numeric: tabular-nums; }
    </style>
</section>
<script>
(function () {
    var box = document.querySelector('.ls-flash-sale .ls-flash-timer');
    if (!box) { return; }
    var clock = box.querySelector('.ls-flash-clock');
    var remaining = parseInt(box.getAttribute('data-remaining'), 10) || 0;
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function tick() {
        if (remaining <= 0) { clock.textContent = 'Ended'; return; }
        var d = Math.floor(remaining / 86400);
        var h = Math.floor((remaining % 86400) / 3600);
        var m = Math.floor((remaining % 3600) / 60);
        var s = remaining % 60;
        clock.textContent = (d > 0 ? d + 'd ' : '') + pad(h) + ':' + pad(m) + ':' + pad(s);
        remaining--;
        setTimeout(tick, 1000);
    }
    tick();
})();
</script>
