<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<section class="container-fluid container-xl py-5">
    <div class="ls-summary mx-auto p-4 p-md-5" style="max-width:540px;">
        <div class="text-center">
            <div style="width:76px;height:76px;border-radius:50%;background:#d1e7dd;color:#0f5132;display:grid;place-items:center;font-size:2.2rem;margin:0 auto;">✓</div>
            <h2 class="h4 mt-3 mb-1">Thank you! Your order is placed.</h2>
            <p class="text-muted mb-0">Order number</p>
            <p class="fw-bold fs-4 text-dark"><?php echo html_escape($order['order_number']); ?></p>
        </div>

        <div class="border-top pt-3 mt-2">
            <?php foreach ($items as $it): ?>
                <div class="d-flex justify-content-between small mb-2">
                    <span><?php echo html_escape($it['product_name']); ?> <span class="text-muted">× <?php echo (int) $it['quantity']; ?></span></span>
                    <span><?php echo shop_money($it['line_total']); ?></span>
                </div>
            <?php endforeach; ?>
            <hr>
            <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Subtotal</span><span><?php echo shop_money($order['subtotal']); ?></span></div>
            <?php if ((float) $order['discount'] > 0): ?>
                <div class="d-flex justify-content-between small mb-1 text-success"><span>Discount<?php echo $order['coupon_code'] ? ' (' . html_escape($order['coupon_code']) . ')' : ''; ?></span><span>- <?php echo shop_money($order['discount']); ?></span></div>
            <?php endif; ?>
            <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Delivery<?php echo !empty($order['shipping_method_label']) ? ' (' . html_escape($order['shipping_method_label']) . ')' : ''; ?></span><span><?php echo shop_money($order['shipping_charge']); ?></span></div>
            <?php if ((float) $order['tax'] > 0): ?>
                <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Tax (VAT)</span><span><?php echo shop_money($order['tax']); ?></span></div>
            <?php endif; ?>
            <div class="d-flex justify-content-between fw-bold mt-2"><span>Total</span><span><?php echo shop_money($order['total']); ?></span></div>
        </div>

        <?php if (!empty($shipment)): ?>
            <div class="border-top pt-3 mt-3">
                <div class="alert alert-info mb-0 py-2 small">
                    <i class="bi bi-truck"></i> <b>Shipped via <?php echo html_escape($shipment['carrier']); ?></b>
                    <?php if (!empty($shipment['tracking_number'])): ?><br>Tracking: <?php if (!empty($shipment['tracking_url'])): ?><a href="<?php echo html_escape($shipment['tracking_url']); ?>" target="_blank" rel="noopener"><?php echo html_escape($shipment['tracking_number']); ?></a><?php else: ?><?php echo html_escape($shipment['tracking_number']); ?><?php endif; ?><?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="border-top pt-3 mt-3 small text-muted">
            <div><b class="text-dark">Deliver to:</b> <?php echo html_escape($order['customer_name']); ?> · <?php echo html_escape($order['customer_phone']); ?></div>
            <div><?php echo html_escape($order['shipping_address']); ?></div>
            <div class="mt-2">
                <b class="text-dark">Payment:</b> <?php echo strtoupper(html_escape($order['payment_method'])); ?>
                <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : ($order['payment_status'] === 'refunded' ? 'warning' : 'secondary'); ?>"><?php echo ucfirst($order['payment_status']); ?></span>
            </div>
            <div class="mt-1"><b class="text-dark">Status:</b> <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?></div>
        </div>

        <div class="text-center mt-4">
            <a href="<?php echo base_url('shop'); ?>" class="btn btn-dark px-4">Continue shopping</a>
            <?php if (!empty($can_return)): ?>
                <a href="<?php echo base_url('account/return/' . rawurlencode($order['order_number'])); ?>" class="btn btn-outline-secondary px-4">Request a return</a>
            <?php endif; ?>
        </div>
    </div>
</section>
