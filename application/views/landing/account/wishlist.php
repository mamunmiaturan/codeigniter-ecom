<?php defined('BASEPATH') or exit('No direct script access allowed');
$items = $items ?? []; ?>
<section class="container-fluid container-xl py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">My Wishlist</h1>
        <a href="<?php echo base_url('account'); ?>" class="btn btn-outline-secondary btn-sm">Back to account</a>
    </div>
    <div class="row g-4">
        <div class="col-lg-3">
            <?php $this->load->view('landing/account/nav', ['active' => 'wishlist']); ?>
        </div>
        <div class="col-lg-9">
            <?php if (empty($items)): ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-heart display-6 text-muted"></i>
                        <p class="text-muted mt-2 mb-3">Your wishlist is empty.</p>
                        <a href="<?php echo base_url('shop'); ?>" class="btn btn-dark btn-sm">Browse products</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($items as $it):
                        $special = ($it['special_price'] !== null && (float) $it['special_price'] > 0) ? (float) $it['special_price'] : null;
                        $eff = $special !== null ? $special : (float) $it['price'];
                        $img = $it['thumbnail'] ? base_url('uploads/catalog/product/' . $it['thumbnail']) : base_url('assets/frontend/assets/img/product/product-1.webp');
                        $oos = ($it['stock_status'] === 'out_of_stock'); ?>
                        <div class="col-md-6">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body d-flex" style="gap:14px;">
                                    <img src="<?php echo html_escape($img); ?>" width="90" height="90" style="object-fit:cover;border-radius:8px;border:1px solid #eee;" alt="">
                                    <div class="flex-grow-1">
                                        <a href="<?php echo base_url('product/' . rawurlencode($it['slug'])); ?>" class="fw-semibold text-decoration-none text-dark d-block mb-1"><?php echo html_escape($it['name']); ?></a>
                                        <div class="mb-2">
                                            <span class="fw-bold text-success"><?php echo shop_money($eff); ?></span>
                                            <?php if ($special !== null): ?><del class="text-muted small ms-1"><?php echo shop_money($it['price']); ?></del><?php endif; ?>
                                        </div>
                                        <div class="d-flex" style="gap:8px;">
                                            <form action="<?php echo base_url('cart/add'); ?>" method="post" class="m-0">
                                                <input type="hidden" name="product_id" value="<?php echo (int) $it['id']; ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <input type="hidden" name="redirect" value="<?php echo base_url('account/wishlist'); ?>">
                                                <button class="btn btn-dark btn-sm" type="submit" <?php echo $oos ? 'disabled' : ''; ?>><i class="bi bi-bag-plus me-1"></i>Add to cart</button>
                                            </form>
                                            <form action="<?php echo base_url('account/wishlist/remove'); ?>" method="post" class="m-0">
                                                <input type="hidden" name="product_id" value="<?php echo (int) $it['id']; ?>">
                                                <button class="btn btn-outline-danger btn-sm" type="submit" title="Remove"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
