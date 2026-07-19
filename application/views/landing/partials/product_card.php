<?php defined('BASEPATH') or exit('No direct script access allowed');
/** @var array $p  shaped product row (see Landing_model::shape) */
$ls_qs = $this->input->server('QUERY_STRING');
$ls_back = base_url($this->uri->uri_string()) . ($ls_qs ? '?' . $ls_qs : ''); ?>
<div class="col-6 col-md-4 col-lg-3">
    <div class="ls-product-card">
        <div class="ls-product-media">
            <a href="<?php echo base_url('product/' . rawurlencode($p['slug'])); ?>">
                <img src="<?php echo html_escape($p['image_url']); ?>" alt="<?php echo html_escape($p['name']); ?>" loading="lazy">
            </a>
            <?php if (!empty($p['on_sale'])): ?><span class="ls-badge-sale">-<?php echo (int) $p['discount_pct']; ?>%</span><?php endif; ?>
            <?php if (!empty($p['label'])): ?><span style="position:absolute;top:10px;right:10px;z-index:3;"><?php echo shop_label_badge($p['label']); ?></span><?php endif; ?>
        </div>
        <div class="ls-product-body">
            <span class="ls-cat-tag"><?php echo html_escape($p['category_name'] ?? 'Product'); ?></span>
            <h3 class="ls-product-title">
                <a href="<?php echo base_url('product/' . rawurlencode($p['slug'])); ?>"><?php echo html_escape($p['name']); ?></a>
            </h3>
            <?php $ls_composite = in_array($p['product_type'] ?? 'simple', ['configurable', 'grouped', 'bundle'], true); ?>
            <div class="d-flex align-items-center justify-content-between mt-auto pt-2">
                <div class="ls-price">
                    <?php if (!empty($p['price_from'])): ?><span class="text-muted small">From</span> <?php endif; ?>
                    <?php echo shop_money($p['effective_price']); ?>
                    <?php if (!empty($p['on_sale'])): ?><del><?php echo shop_money($p['price']); ?></del><?php endif; ?>
                </div>
                <?php if ($ls_composite): ?>
                    <a href="<?php echo base_url('product/' . rawurlencode($p['slug'])); ?>" class="ls-add-btn" aria-label="View options" title="Choose options">
                        <i class="bi bi-sliders"></i>
                    </a>
                <?php else: ?>
                    <form action="<?php echo base_url('cart/add'); ?>" method="post" class="m-0">
                        <input type="hidden" name="product_id" value="<?php echo (int) $p['id']; ?>">
                        <input type="hidden" name="quantity" value="1">
                        <input type="hidden" name="redirect" value="<?php echo html_escape($ls_back); ?>">
                        <button class="ls-add-btn" type="submit" aria-label="Add to cart"
                            <?php echo ($p['stock_status'] === 'out_of_stock') ? 'disabled title="Out of stock"' : ''; ?>>
                            <i class="bi bi-bag-plus"></i>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
