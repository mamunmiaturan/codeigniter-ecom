<?php defined('BASEPATH') or exit('No direct script access allowed');
$c = isset($customer) && is_array($customer) ? $customer : [];
$divisions = $divisions ?? [];
$sel_division = $sel_division ?? '';
$sel_method = $sel_method ?? null;
$sel_postcode = $sel_postcode ?? '';
$payment_methods = $payment_methods ?? [];
$def_method_id = ($cart['shipping_method'] ?? null) ? (int) $cart['shipping_method']['id'] : 0;
?>

<section class="container-fluid container-xl py-5">
    <h1 class="section-title h4 mb-4">Checkout</h1>

    <form action="<?php echo base_url('checkout'); ?>" method="post" class="row g-4">
        <div class="col-lg-7">
            <div class="ls-summary p-4">
                <h5 class="mb-3">Delivery details</h5>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label small fw-semibold">Full name *</label><input class="form-control" name="name" value="<?php echo html_escape($c['name'] ?? ''); ?>" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Phone *</label><input class="form-control" name="phone" value="<?php echo html_escape($c['mobile_no'] ?? ''); ?>" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Email</label><input type="email" class="form-control" name="email" value="<?php echo html_escape($c['email'] ?? ''); ?>"></div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Division</label>
                        <select class="form-select" name="division" id="co-division">
                            <option value="">— Select division —</option>
                            <?php foreach ($divisions as $d): ?>
                                <option value="<?php echo html_escape($d); ?>" <?php echo strcasecmp($sel_division, $d) === 0 ? 'selected' : ''; ?>><?php echo html_escape($d); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Shipping &amp; tax update to your division.</small>
                    </div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">District</label><input class="form-control" name="district"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Area / Thana</label><input class="form-control" name="area"></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Full address *</label><textarea class="form-control" name="address" rows="2" required><?php echo html_escape($c['address'] ?? ''); ?></textarea></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Landmark</label><input class="form-control" name="landmark"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Postcode</label><input class="form-control" id="co-postcode" name="postcode" value="<?php echo html_escape($sel_postcode); ?>"></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Order note</label><input class="form-control" name="note" placeholder="Optional delivery instructions"></div>
                </div>
            </div>

            <div class="ls-summary p-4 mt-4">
                <h5 class="mb-3">Shipping method</h5>
                <?php if (empty($cart['shipping_methods'])): ?>
                    <p class="text-muted mb-0">No shipping method is available for this area. Please contact us.</p>
                <?php else: foreach ($cart['shipping_methods'] as $m):
                    $checked = $sel_method ? ((int) $sel_method === (int) $m['id']) : ($def_method_id === (int) $m['id']); ?>
                    <label class="d-flex justify-content-between align-items-center border rounded p-2 mb-2" style="cursor:pointer;">
                        <span>
                            <input type="radio" name="shipping_method" value="<?php echo (int) $m['id']; ?>" class="me-2" <?php echo $checked ? 'checked' : ''; ?>>
                            <?php echo html_escape($m['title']); ?>
                            <?php if (!empty($m['description'])): ?><small class="text-muted d-block ms-4"><?php echo html_escape($m['description']); ?></small><?php endif; ?>
                        </span>
                        <span class="fw-semibold"><?php echo !empty($m['is_free']) ? '<span class="text-success">FREE</span>' : shop_money($m['computed_rate']); ?></span>
                    </label>
                <?php endforeach; endif; ?>
            </div>

            <div class="ls-summary p-4 mt-4">
                <h5 class="mb-3">Payment method</h5>
                <?php if (empty($payment_methods)): ?>
                    <p class="text-muted mb-0">No payment method is configured.</p>
                <?php else: foreach ($payment_methods as $i => $pm): ?>
                    <label class="d-flex align-items-center border rounded p-2 mb-2" style="cursor:pointer;">
                        <input type="radio" name="payment_method" value="<?php echo html_escape($pm['code']); ?>" class="me-2" <?php echo $i === 0 ? 'checked' : ''; ?>>
                        <span>
                            <?php echo html_escape($pm['title']); ?>
                            <?php if (!empty($pm['description'])): ?><small class="text-muted d-block"><?php echo html_escape($pm['description']); ?></small><?php endif; ?>
                        </span>
                    </label>
                <?php endforeach; endif; ?>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="ls-summary p-4">
                <h5 class="mb-3">Order Summary</h5>
                <?php foreach ($cart['items'] as $it): ?>
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-truncate me-2"><?php echo html_escape($it['name']); ?> <span class="text-muted">× <?php echo (int) $it['quantity']; ?></span></span>
                        <span class="text-nowrap"><?php echo shop_money($it['line_total']); ?></span>
                    </div>
                <?php endforeach; ?>
                <hr>
                <div class="d-flex justify-content-between mb-1"><span class="text-muted">Subtotal</span><span><?php echo shop_money($cart['subtotal']); ?></span></div>
                <?php if ($cart['discount'] > 0): ?>
                    <div class="d-flex justify-content-between mb-1 text-success">
                        <span>Discount<?php echo ($cart['coupon'] ?? null) ? ' (' . html_escape($cart['coupon']['code']) . ')' : ''; ?></span>
                        <span>- <?php echo shop_money($cart['discount']); ?></span>
                    </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between mb-1"><span class="text-muted">Delivery</span><span><?php echo $cart['free_shipping'] ? '<span class="text-success">FREE</span>' : shop_money($cart['shipping']); ?></span></div>
                <?php if ($cart['tax'] > 0): ?>
                    <div class="d-flex justify-content-between mb-1"><span class="text-muted">Tax (VAT)</span><span><?php echo shop_money($cart['tax']); ?></span></div>
                <?php endif; ?>
                <hr>
                <div class="d-flex justify-content-between fw-bold fs-5 mb-3"><span>Payable</span><span><?php echo shop_money($cart['total']); ?></span></div>
                <button class="btn btn-success w-100 btn-lg" type="submit"><i class="bi bi-check2-circle me-1"></i> Place Order</button>
                <a href="<?php echo base_url('cart'); ?>" class="btn btn-link w-100 text-muted mt-1">Back to cart</a>
            </div>
        </div>
    </form>
</section>

<script>
    (function () {
        function recalc() {
            var d = document.getElementById('co-division');
            var p = document.getElementById('co-postcode');
            window.location = '<?php echo base_url('checkout'); ?>?division=' + encodeURIComponent(d ? d.value : '') + '&postcode=' + encodeURIComponent(p ? p.value : '');
        }
        var sel = document.getElementById('co-division');
        if (sel) { sel.addEventListener('change', recalc); }
        // Recompute tax when the customer finishes entering a postcode (only if a
        // postcode-scoped tax rate could apply; harmless for nationwide VAT).
        var pc = document.getElementById('co-postcode');
        if (pc) { pc.addEventListener('change', recalc); }
    })();
</script>
