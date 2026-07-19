<?php defined('BASEPATH') or exit('No direct script access allowed');
$ptype    = $product['product_type'] ?? 'simple';
$is_digital = in_array($ptype, ['virtual', 'downloadable'], true);
$out_of_stock = !$is_digital && ($product['stock_status'] === 'out_of_stock');
$samples  = $samples ?? [];
$desc = $product['description'] ?: $product['short_description'];

// ---- Configurable: build option axes + a JS variant map from variant JSON ----
$variants        = isset($variants) ? $variants : [];
$is_configurable = ($ptype === 'configurable');
$cfg_axes        = [];   // axis label => [distinct option values, first-seen order]
$cfg_variants    = [];   // JS: {id, price, stock, opts:{axis:val}}
$cfg_min_price   = null;
if ($is_configurable) {
    foreach ($variants as $v) {
        $opts = json_decode($v['attributes'] ?? '', true);
        if (!is_array($opts)) { $opts = []; }
        foreach ($opts as $axis => $val) {
            if (!isset($cfg_axes[$axis])) { $cfg_axes[$axis] = []; }
            if (!in_array($val, $cfg_axes[$axis], true)) { $cfg_axes[$axis][] = $val; }
        }
        $vp = (float) $v['price'];
        if ($vp > 0 && ($cfg_min_price === null || $vp < $cfg_min_price)) { $cfg_min_price = $vp; }
        $cfg_variants[] = ['id' => (int) $v['id'], 'price' => $vp, 'stock' => (int) $v['stock_quantity'], 'opts' => $opts];
    }
    // Configurable stock availability is per-variant; never gate the whole product.
    $out_of_stock = empty($cfg_variants);
}

// ---- Grouped: a list of associated products, each added as its own line ----
$is_grouped    = ($ptype === 'grouped');
$grouped_items = isset($grouped_items) ? $grouped_items : [];
if ($is_grouped) {
    $out_of_stock = empty($grouped_items);
}

// ---- Bundle: option groups of components; priced live from the selection ----
$is_bundle      = ($ptype === 'bundle');
$bundle_options = isset($bundle_options) ? $bundle_options : [];
$bundle_min     = null;
if ($is_bundle) {
    $bundle_min = 0.0;
    foreach ($bundle_options as $o) {
        $prices = [];
        foreach ($o['products'] as $bp) {
            if (!empty($bp['in_stock'])) { $prices[] = (float) $bp['display_price'] * max(1, (int) $bp['qty']); }
        }
        if (!empty($prices) && (int) $o['is_required'] === 1) { $bundle_min += min($prices); }
    }
    $out_of_stock = empty($bundle_options);
} ?>

<section class="container-fluid container-xl py-5">
    <nav class="small mb-4">
        <a href="<?php echo base_url('/'); ?>" class="text-muted text-decoration-none">Home</a>
        <span class="text-muted mx-1">/</span>
        <a href="<?php echo base_url('shop'); ?>" class="text-muted text-decoration-none">Shop</a>
        <?php if (!empty($product['category_slug'])): ?>
            <span class="text-muted mx-1">/</span>
            <a href="<?php echo base_url('shop?category=' . urlencode($product['category_slug'])); ?>" class="text-muted text-decoration-none"><?php echo html_escape($product['category_name']); ?></a>
        <?php endif; ?>
        <span class="text-muted mx-1">/</span><span><?php echo html_escape($product['name']); ?></span>
    </nav>

    <div class="row g-5">
        <div class="col-lg-6">
            <div class="ls-summary overflow-hidden">
                <img src="<?php echo html_escape($product['image_url']); ?>" class="img-fluid w-100" alt="<?php echo html_escape($product['name']); ?>" style="aspect-ratio:1/1;object-fit:cover;">
            </div>
            <?php if (!empty($images)): ?>
            <div class="d-flex gap-2 mt-3 flex-wrap">
                <?php foreach ($images as $im): ?>
                    <img src="<?php echo base_url('uploads/catalog/product/' . $im['image_path']); ?>" width="70" height="70" class="rounded-3 border" style="object-fit:cover;" alt="">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php $video_html = function_exists('shop_video_embed') ? shop_video_embed($product['video_url'] ?? '') : ''; ?>
            <?php if ($video_html): ?>
            <div class="mt-3">
                <div class="fw-semibold mb-2"><i class="bi bi-camera-video me-1"></i>Product video</div>
                <?php echo $video_html; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-6">
            <span class="badge bg-light text-dark border mb-2"><?php echo html_escape($product['category_name'] ?? 'Product'); ?></span>
            <?php if (!empty($product['label'])): ?><?php echo shop_label_badge($product['label'], 'mb-2'); ?> <?php endif; ?>
            <?php if ($ptype === 'downloadable'): ?>
                <span class="badge bg-primary mb-2"><i class="bi bi-download me-1"></i>Downloadable</span>
            <?php elseif ($ptype === 'virtual'): ?>
                <span class="badge bg-info text-dark mb-2"><i class="bi bi-cloud me-1"></i>Virtual</span>
            <?php endif; ?>
            <h1 class="h3 fw-bold mb-2"><?php echo html_escape($product['name']); ?></h1>

            <?php if (!empty($review_summary) && $review_summary['count'] > 0): ?>
                <div class="mb-3 d-flex align-items-center" style="gap:8px;">
                    <?php echo shop_stars($review_summary['average']); ?>
                    <span class="fw-semibold"><?php echo number_format($review_summary['average'], 1); ?></span>
                    <a href="#reviews" class="text-muted text-decoration-none small">(<?php echo (int) $review_summary['count']; ?> <?php echo $review_summary['count'] === 1 ? 'review' : 'reviews'; ?>)</a>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <?php if ($is_configurable): ?>
                    <span class="text-muted small" id="cfg-price-label"><?php echo ($cfg_min_price !== null) ? 'From' : ''; ?></span>
                    <span class="h3 fw-bold text-success" id="cfg-price"><?php echo shop_money($cfg_min_price !== null ? $cfg_min_price : $product['effective_price']); ?></span>
                <?php elseif ($is_bundle): ?>
                    <span class="text-muted small" id="bundle-price-label">From</span>
                    <span class="h3 fw-bold text-success" id="bundle-price"><?php echo shop_money($bundle_min !== null ? $bundle_min : 0); ?></span>
                <?php else: ?>
                    <span class="h3 fw-bold text-success"><?php echo shop_money($product['effective_price']); ?></span>
                    <?php if (!empty($product['on_sale'])): ?>
                        <del class="text-muted ms-2 fs-5"><?php echo shop_money($product['price']); ?></del>
                        <span class="badge bg-danger ms-1">-<?php echo (int) $product['discount_pct']; ?>%</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <p class="fw-semibold <?php echo $out_of_stock ? 'text-danger' : 'text-success'; ?>">
                <i class="bi <?php echo $out_of_stock ? 'bi-x-circle' : 'bi-check-circle'; ?>"></i>
                <?php
                if ($ptype === 'downloadable') {
                    echo 'Digital download — delivered instantly after payment';
                } elseif ($ptype === 'virtual') {
                    echo 'Virtual product — no shipping required';
                } elseif ($out_of_stock) {
                    echo 'Out of stock';
                } elseif ($product['stock_status'] === 'pre_order') {
                    echo 'Available for pre-order';
                } else {
                    echo 'In stock (' . (int) $product['stock_quantity'] . ' available)';
                }
                ?>
            </p>

            <?php if (!empty($samples)): ?>
            <div class="border rounded-3 p-3 mb-3 bg-light">
                <div class="fw-semibold mb-2"><i class="bi bi-file-earmark-arrow-down me-1"></i>Free samples</div>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($samples as $s): ?>
                        <li class="mb-1">
                            <a href="<?php echo base_url('download/sample/' . (int) $s['id']); ?>" class="text-decoration-none">
                                <i class="bi bi-download me-1"></i><?php echo html_escape($s['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($desc): ?><p class="text-secondary"><?php echo nl2br(html_escape($desc)); ?></p><?php endif; ?>

            <?php if (!empty($product['sku']) || !empty($product['brand_name'])): ?>
            <p class="small text-muted">
                <?php if (!empty($product['sku'])): ?>SKU: <?php echo html_escape($product['sku']); ?><?php endif; ?>
                <?php if (!empty($product['brand_name'])): ?> · Brand: <?php echo html_escape($product['brand_name']); ?><?php endif; ?>
            </p>
            <?php endif; ?>

            <?php $tag_list = array_values(array_filter(array_map('trim', explode(',', (string) ($product['tags'] ?? ''))), function ($t) { return $t !== ''; })); ?>
            <?php if (!empty($tag_list)): ?>
            <div class="mb-3">
                <?php foreach ($tag_list as $tag): ?>
                    <a href="<?php echo base_url('shop?search=' . urlencode($tag)); ?>" class="badge bg-light text-dark border text-decoration-none me-1 mb-1"><i class="bi bi-tag me-1"></i><?php echo html_escape($tag); ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php $specs = isset($attributes) ? $attributes : []; ?>
            <?php if (!empty($specs)): ?>
            <div class="mt-3 mb-2">
                <table class="table table-sm mb-0" style="max-width:420px;">
                    <tbody>
                        <?php foreach ($specs as $s): ?>
                            <tr>
                                <th class="text-muted fw-normal ps-0" style="width:40%;"><?php echo html_escape($s['name']); ?></th>
                                <td class="fw-semibold"><?php echo html_escape($s['value']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if ($is_configurable && !empty($cfg_axes)): ?>
            <div class="mb-3" id="cfg-axes" style="max-width:420px;">
                <?php foreach ($cfg_axes as $axis => $vals): ?>
                    <div class="mb-2">
                        <label class="form-label fw-semibold small mb-1"><?php echo html_escape($axis); ?></label>
                        <select class="form-select cfg-axis" data-axis="<?php echo html_escape($axis); ?>">
                            <option value="">Choose <?php echo html_escape($axis); ?></option>
                            <?php foreach ($vals as $val): ?>
                                <option value="<?php echo html_escape($val); ?>"><?php echo html_escape($val); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
                <div class="small mt-1" id="cfg-stock"></div>
            </div>
            <?php endif; ?>

<?php if ($is_bundle): ?>
            <form action="<?php echo base_url('cart/add'); ?>" method="post" class="mt-4" id="bundle-form" style="max-width:560px;">
                <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                <input type="hidden" name="redirect" value="<?php echo base_url('cart'); ?>">
                <?php if (empty($bundle_options)): ?>
                    <p class="text-muted">This bundle has no options configured yet.</p>
                <?php else: ?>
                    <?php foreach ($bundle_options as $o):
                        $single = in_array($o['type'], ['select', 'radio'], true); ?>
                        <div class="mb-3 bundle-option" data-option="<?php echo (int) $o['id']; ?>" data-required="<?php echo (int) $o['is_required']; ?>" data-single="<?php echo $single ? 1 : 0; ?>">
                            <div class="fw-semibold mb-1"><?php echo html_escape($o['label']); ?><?php if ((int) $o['is_required'] === 1): ?> <span class="text-danger">*</span><?php endif; ?></div>
                            <?php foreach ($o['products'] as $bp):
                                $ptxt = shop_money($bp['display_price']) . ((int) $bp['qty'] > 1 ? ' × ' . (int) $bp['qty'] : ''); ?>
                                <label class="d-flex align-items-center mb-1 <?php echo $bp['in_stock'] ? '' : 'text-muted'; ?>" style="gap:8px;cursor:pointer;">
                                    <input type="<?php echo $single ? 'radio' : 'checkbox'; ?>"
                                           name="<?php echo $single ? 'bundle_radio[' . (int) $o['id'] . ']' : 'bundle_selection[]'; ?>"
                                           value="<?php echo (int) $bp['id']; ?>" class="bundle-pick"
                                           data-price="<?php echo (float) $bp['display_price'] * max(1, (int) $bp['qty']); ?>"
                                           <?php echo $bp['in_stock'] ? '' : 'disabled'; ?>>
                                    <span class="flex-grow-1"><?php echo html_escape($bp['name']); ?></span>
                                    <span class="small text-success">+ <?php echo $ptxt; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                    <div class="d-flex align-items-center gap-3 mt-3">
                        <input type="number" name="quantity" value="1" min="1" class="form-control text-center" style="width:90px;">
                        <button class="btn btn-dark btn-lg px-4" type="submit" id="bundle-add" disabled><i class="bi bi-bag-plus me-1"></i> Add to cart</button>
                    </div>
                    <div class="small text-danger mt-1" id="bundle-warn"></div>
                <?php endif; ?>
            </form>
            <script>
            (function () {
                var form = document.getElementById('bundle-form');
                if (!form) { return; }
                var addBtn = document.getElementById('bundle-add');
                var priceEl = document.getElementById('bundle-price');
                var lblEl = document.getElementById('bundle-price-label');
                var warn = document.getElementById('bundle-warn');
                var money = function (n) { return '<?php echo addslashes(get_global_setting('currency_symbol') ?: '৳'); ?>' + Number(n).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}); };
                function recalc() {
                    var total = 0, ok = true;
                    form.querySelectorAll('.bundle-option').forEach(function (grp) {
                        var picked = grp.querySelectorAll('.bundle-pick:checked');
                        picked.forEach(function (i) { total += parseFloat(i.getAttribute('data-price')) || 0; });
                        if (grp.getAttribute('data-required') === '1' && picked.length === 0) { ok = false; }
                    });
                    if (priceEl) { priceEl.textContent = money(total); }
                    if (lblEl) { lblEl.textContent = ok ? '' : 'From'; }
                    addBtn.disabled = !ok;
                    if (warn) { warn.textContent = ok ? '' : 'Please choose all required options.'; }
                }
                form.querySelectorAll('.bundle-pick').forEach(function (i) { i.addEventListener('change', recalc); });
                recalc();
            })();
            </script>
            <?php elseif ($is_grouped): ?>
            <form action="<?php echo base_url('cart/add'); ?>" method="post" class="mt-4" style="max-width:520px;">
                <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                <input type="hidden" name="redirect" value="<?php echo base_url('cart'); ?>">
                <?php if (empty($grouped_items)): ?>
                    <p class="text-muted">No items are currently available in this collection.</p>
                <?php else: ?>
                    <div class="list-group mb-3">
                        <?php foreach ($grouped_items as $gi): ?>
                            <div class="list-group-item d-flex align-items-center gap-3">
                                <div class="flex-grow-1">
                                    <div class="fw-semibold"><?php echo html_escape($gi['name']); ?></div>
                                    <div class="small <?php echo $gi['in_stock'] ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo shop_money($gi['display_price']); ?> · <?php echo $gi['in_stock'] ? 'In stock' : 'Out of stock'; ?>
                                    </div>
                                </div>
                                <input type="number" name="qty[<?php echo (int) $gi['id']; ?>]" value="<?php echo $gi['in_stock'] ? (int) $gi['default_qty'] : 0; ?>" min="0" class="form-control text-center" style="width:80px;" <?php echo $gi['in_stock'] ? '' : 'disabled'; ?>>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="btn btn-dark btn-lg px-4" type="submit"><i class="bi bi-bag-plus me-1"></i> Add selected to cart</button>
                <?php endif; ?>
            </form>
            <?php else: ?>
            <form action="<?php echo base_url('cart/add'); ?>" method="post" class="d-flex align-items-center gap-3 mt-4" id="add-to-cart-form">
                <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                <input type="hidden" name="redirect" value="<?php echo base_url('cart'); ?>">
                <input type="hidden" name="variant_id" id="cfg-variant-id" value="">
                <input type="number" name="quantity" value="1" min="1" class="form-control text-center" style="width:90px;" <?php echo $out_of_stock ? 'disabled' : ''; ?>>
                <button class="btn btn-dark btn-lg px-4" type="submit" id="add-to-cart-btn" <?php echo ($out_of_stock || $is_configurable) ? 'disabled' : ''; ?>>
                    <i class="bi bi-bag-plus me-1"></i> Add to cart
                </button>
            </form>
            <?php endif; ?>
            <?php if ($is_configurable): ?>
            <script>
            (function () {
                var VARIANTS = <?php echo json_encode($cfg_variants); ?>;
                var axes  = document.querySelectorAll('.cfg-axis');
                var vidEl = document.getElementById('cfg-variant-id');
                var btn   = document.getElementById('add-to-cart-btn');
                var priceEl = document.getElementById('cfg-price');
                var priceLbl = document.getElementById('cfg-price-label');
                var stockEl = document.getElementById('cfg-stock');
                var money = function (n) { return '<?php echo addslashes(get_global_setting('currency_symbol') ?: '৳'); ?>' + Number(n).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}); };
                function resolve() {
                    var sel = {}, complete = true;
                    axes.forEach(function (a) {
                        if (a.value) { sel[a.getAttribute('data-axis')] = a.value; } else { complete = false; }
                    });
                    var match = null;
                    if (complete) {
                        match = VARIANTS.find(function (v) {
                            return Object.keys(sel).every(function (k) { return String(v.opts[k]) === String(sel[k]); });
                        });
                    }
                    if (match) {
                        vidEl.value = match.id;
                        if (priceEl) { priceEl.textContent = money(match.price); }
                        if (priceLbl) { priceLbl.textContent = ''; }
                        if (match.stock > 0) {
                            btn.disabled = false;
                            stockEl.innerHTML = '<span class="text-success">In stock (' + match.stock + ' available)</span>';
                        } else {
                            btn.disabled = true;
                            stockEl.innerHTML = '<span class="text-danger">Out of stock</span>';
                        }
                    } else {
                        vidEl.value = '';
                        btn.disabled = true;
                        stockEl.textContent = complete ? 'This combination is unavailable.' : '';
                    }
                }
                axes.forEach(function (a) { a.addEventListener('change', resolve); });
                resolve();
            })();
            </script>
            <?php endif; ?>

            <div class="d-flex align-items-center mt-3" style="gap:8px;">
                <?php if (function_exists('is_customer_loggedin') && is_customer_loggedin()): ?>
                    <form action="<?php echo base_url('account/wishlist/toggle'); ?>" method="post" class="m-0">
                        <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                        <input type="hidden" name="redirect" value="<?php echo base_url('product/' . rawurlencode($product['slug'])); ?>">
                        <button class="btn btn-outline-danger" type="submit">
                            <i class="bi <?php echo !empty($in_wishlist) ? 'bi-heart-fill' : 'bi-heart'; ?> me-1"></i><?php echo !empty($in_wishlist) ? 'In Wishlist' : 'Wishlist'; ?>
                        </button>
                    </form>
                <?php else: ?>
                    <a href="<?php echo base_url('account/login?redirect=' . urlencode(base_url('product/' . rawurlencode($product['slug'])))); ?>" class="btn btn-outline-danger">
                        <i class="bi bi-heart me-1"></i>Wishlist
                    </a>
                <?php endif; ?>
                <form action="<?php echo base_url('compare/add'); ?>" method="post" class="m-0">
                    <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                    <input type="hidden" name="redirect" value="<?php echo base_url('product/' . rawurlencode($product['slug'])); ?>">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-bar-chart me-1"></i>Compare</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ============ Reviews ============ -->
    <hr class="my-5" id="reviews">
    <div class="row g-4">
        <div class="col-lg-4">
            <h2 class="h5 mb-3">Customer Reviews</h2>
            <?php if (!empty($review_summary) && $review_summary['count'] > 0): ?>
                <div class="display-6 fw-bold mb-0"><?php echo number_format($review_summary['average'], 1); ?><span class="fs-5 text-muted">/5</span></div>
                <div class="mb-1"><?php echo shop_stars($review_summary['average']); ?></div>
                <p class="text-muted small mb-3"><?php echo (int) $review_summary['count']; ?> <?php echo $review_summary['count'] === 1 ? 'rating' : 'ratings'; ?></p>
                <?php foreach ([5, 4, 3, 2, 1] as $star):
                    $c   = (int) $review_summary['breakdown'][$star];
                    $pct = $review_summary['count'] ? round($c * 100 / $review_summary['count']) : 0; ?>
                    <div class="d-flex align-items-center mb-1" style="gap:8px;">
                        <span class="small text-muted" style="width:34px;"><?php echo $star; ?> <i class="bi bi-star-fill" style="color:#f5a623;"></i></span>
                        <div class="progress flex-grow-1" style="height:8px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width:<?php echo $pct; ?>%"></div>
                        </div>
                        <span class="small text-muted" style="width:28px;"><?php echo $c; ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">No reviews yet. Be the first to review this product.</p>
            <?php endif; ?>
            <div id="review-cta" class="mt-3">
                <?php $this->load->view('landing/partials/review_form', ['product' => $product]); ?>
            </div>
        </div>
        <div class="col-lg-8">
            <?php if (!empty($reviews)): ?>
                <?php foreach ($reviews as $rv): ?>
                    <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong><?php echo html_escape($rv['author_name']); ?></strong>
                                <?php if (!empty($rv['is_verified_purchase'])): ?><span class="badge bg-success ms-1">Verified Purchase</span><?php endif; ?>
                            </div>
                            <small class="text-muted"><?php echo time_ago($rv['created_at']); ?></small>
                        </div>
                        <div class="my-1"><?php echo shop_stars($rv['rating']); ?></div>
                        <?php if (!empty($rv['title'])): ?><div class="fw-semibold"><?php echo html_escape($rv['title']); ?></div><?php endif; ?>
                        <?php if (!empty($rv['comment'])): ?><p class="text-secondary mb-1"><?php echo nl2br(html_escape($rv['comment'])); ?></p><?php endif; ?>
                        <?php if (!empty($rv['admin_reply'])): ?>
                            <div class="bg-light rounded p-2 small mt-1"><strong>Store reply:</strong> <?php echo nl2br(html_escape($rv['admin_reply'])); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">There are no reviews for this product yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($fbt)): ?>
    <hr class="my-5">
    <h2 class="section-title h5 mb-4">Frequently Bought Together</h2>
    <div class="row g-4">
        <?php foreach ($fbt as $p) { $this->load->view('landing/partials/product_card', ['p' => $p]); } ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($related)): ?>
    <hr class="my-5">
    <h2 class="section-title h5 mb-4">Related products</h2>
    <div class="row g-4">
        <?php foreach ($related as $p) { $this->load->view('landing/partials/product_card', ['p' => $p]); } ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($recently_viewed)): ?>
    <hr class="my-5">
    <h2 class="section-title h5 mb-4">Recently Viewed</h2>
    <div class="row g-4">
        <?php foreach ($recently_viewed as $p) { $this->load->view('landing/partials/product_card', ['p' => $p]); } ?>
    </div>
    <?php endif; ?>
</section>
