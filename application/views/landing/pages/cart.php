<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<section class="container-fluid container-xl py-5">
    <h1 class="section-title h4 mb-4">Your Cart</h1>

    <?php if (empty($cart['items'])): ?>
        <div class="text-center py-5">
            <div style="font-size:3.5rem;">🛒</div>
            <p class="text-muted mb-3">Your cart is empty.</p>
            <a href="<?php echo base_url('shop'); ?>" class="btn btn-dark">Start shopping</a>
        </div>
    <?php else: ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <?php foreach ($cart['items'] as $it): ?>
            <div class="ls-summary d-flex gap-3 align-items-center p-3 mb-3">
                <a href="<?php echo base_url('product/' . rawurlencode($it['slug'])); ?>">
                    <img src="<?php echo html_escape($it['image']); ?>" width="76" height="76" class="rounded-3" style="object-fit:cover;" alt="">
                </a>
                <div class="flex-grow-1">
                    <a href="<?php echo base_url('product/' . rawurlencode($it['slug'])); ?>" class="fw-semibold text-dark text-decoration-none"><?php echo html_escape($it['name']); ?></a>
                    <div class="text-success fw-bold"><?php echo shop_money($it['unit_price']); ?></div>
                </div>
                <form action="<?php echo base_url('cart/update'); ?>" method="post" class="m-0">
                    <input type="hidden" name="item_id" value="<?php echo (int) $it['id']; ?>">
                    <input type="number" name="quantity" value="<?php echo (int) $it['quantity']; ?>" min="0"
                           class="form-control form-control-sm text-center" style="width:72px;" onchange="this.form.submit()">
                </form>
                <div class="text-end" style="min-width:96px;">
                    <div class="fw-bold mb-1"><?php echo shop_money($it['line_total']); ?></div>
                    <form action="<?php echo base_url('cart/remove'); ?>" method="post" class="m-0">
                        <input type="hidden" name="item_id" value="<?php echo (int) $it['id']; ?>">
                        <button class="btn btn-link btn-sm text-danger p-0" type="submit"><i class="bi bi-trash"></i> Remove</button>
                    </form>
                    <form action="<?php echo base_url('cart/save_for_later'); ?>" method="post" class="m-0">
                        <input type="hidden" name="item_id" value="<?php echo (int) $it['id']; ?>">
                        <input type="hidden" name="product_id" value="<?php echo (int) ($it['product_id'] ?? 0); ?>">
                        <button class="btn btn-link btn-sm text-muted p-0" type="submit"><i class="bi bi-bookmark"></i> Save for later</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
            <a href="<?php echo base_url('shop'); ?>" class="btn btn-outline-dark btn-sm mt-1"><i class="bi bi-arrow-left me-1"></i> Continue shopping</a>
        </div>

        <div class="col-lg-4">
            <div class="ls-summary p-4">
                <h5 class="mb-3">Order Summary</h5>

                <?php if ($cart['coupon']): ?>
                    <div class="d-flex justify-content-between align-items-center bg-success bg-opacity-10 text-success rounded-3 px-3 py-2 mb-3">
                        <span>🏷️ <b><?php echo html_escape($cart['coupon']['code']); ?></b> applied</span>
                        <form action="<?php echo base_url('cart/coupon/remove'); ?>" method="post" class="m-0">
                            <button class="btn btn-sm btn-link text-danger p-0" type="submit">Remove</button>
                        </form>
                    </div>
                <?php else: ?>
                    <form action="<?php echo base_url('cart/coupon'); ?>" method="post" class="input-group mb-3">
                        <input name="code" class="form-control" placeholder="Coupon code" style="text-transform:uppercase;">
                        <button class="btn btn-dark" type="submit">Apply</button>
                    </form>
                <?php endif; ?>

                <?php if (!empty($cart['applied_rules'])): ?>
                    <?php foreach ($cart['applied_rules'] as $ar): ?>
                        <div class="small text-success mb-1"><i class="bi bi-tag"></i> <?php echo html_escape($ar['name']); ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="d-flex justify-content-between mb-1"><span class="text-muted">Subtotal</span><span><?php echo shop_money($cart['subtotal']); ?></span></div>
                <?php if ($cart['discount'] > 0): ?>
                    <div class="d-flex justify-content-between mb-1 text-success"><span>Discount</span><span>- <?php echo shop_money($cart['discount']); ?></span></div>
                <?php endif; ?>
                <div class="d-flex justify-content-between mb-1"><span class="text-muted">Delivery <small>(est.)</small></span><span><?php echo $cart['free_shipping'] ? '<span class="text-success">FREE</span>' : shop_money($cart['shipping']); ?></span></div>
                <?php if (($cart['tax'] ?? 0) > 0): ?>
                    <div class="d-flex justify-content-between mb-1"><span class="text-muted">Tax (VAT)</span><span><?php echo shop_money($cart['tax']); ?></span></div>
                <?php endif; ?>
                <hr>
                <div class="d-flex justify-content-between fw-bold fs-5"><span>Total</span><span><?php echo shop_money($cart['total']); ?></span></div>
                <p class="text-muted small mt-2 mb-0">Final delivery &amp; tax are confirmed at checkout for your division.</p>
                <a href="<?php echo base_url('checkout'); ?>" class="btn btn-dark w-100 mt-3">Proceed to Checkout</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($saved_items)): ?>
    <hr class="my-4">
    <h2 class="section-title h5 mb-3">Saved for later</h2>
    <div class="row g-3">
        <?php foreach ($saved_items as $p): ?>
            <div class="col-6 col-md-3">
                <div class="ls-summary p-2 h-100 d-flex flex-column">
                    <a href="<?php echo base_url('product/' . rawurlencode($p['slug'])); ?>">
                        <img src="<?php echo html_escape($p['image_url']); ?>" class="rounded-3 w-100" style="height:120px;object-fit:cover;" alt="<?php echo html_escape($p['name']); ?>">
                    </a>
                    <a href="<?php echo base_url('product/' . rawurlencode($p['slug'])); ?>" class="small fw-semibold text-dark text-decoration-none mt-2"><?php echo html_escape($p['name']); ?></a>
                    <div class="text-success fw-bold small mb-2"><?php echo shop_money($p['effective_price']); ?></div>
                    <div class="mt-auto d-flex gap-2">
                        <form action="<?php echo base_url('cart/move_to_cart'); ?>" method="post" class="m-0 flex-grow-1">
                            <input type="hidden" name="product_id" value="<?php echo (int) $p['id']; ?>">
                            <button class="btn btn-dark btn-sm w-100" type="submit"><i class="bi bi-cart-plus"></i> Move to cart</button>
                        </form>
                        <form action="<?php echo base_url('cart/remove_saved'); ?>" method="post" class="m-0">
                            <input type="hidden" name="product_id" value="<?php echo (int) $p['id']; ?>">
                            <button class="btn btn-outline-danger btn-sm" type="submit" title="Remove"><i class="bi bi-x-lg"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
