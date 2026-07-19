<?php defined('BASEPATH') or exit('No direct script access allowed');
$products  = $products ?? [];
$summaries = $summaries ?? []; ?>
<section class="container-fluid container-xl py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">Compare Products</h1>
        <?php if (!empty($products)): ?>
            <form action="<?php echo base_url('compare/clear'); ?>" method="post" class="m-0">
                <button class="btn btn-outline-secondary btn-sm" type="submit">Clear all</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($products)): ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-bar-chart-line display-6 text-muted"></i>
                <p class="text-muted mt-2 mb-3">You haven't added any products to compare yet.</p>
                <a href="<?php echo base_url('shop'); ?>" class="btn btn-dark btn-sm">Browse products</a>
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered bg-white align-middle">
                <tbody>
                    <tr>
                        <th style="width:150px;">Product</th>
                        <?php foreach ($products as $p):
                            $img = $p['thumbnail'] ? base_url('uploads/catalog/product/' . $p['thumbnail']) : base_url('assets/frontend/assets/img/product/product-1.webp'); ?>
                            <td class="text-center" style="min-width:200px;">
                                <img src="<?php echo html_escape($img); ?>" width="110" height="110" style="object-fit:cover;border-radius:8px;border:1px solid #eee;" alt=""><br>
                                <a href="<?php echo base_url('product/' . rawurlencode($p['slug'])); ?>" class="fw-semibold text-decoration-none d-block mt-2"><?php echo html_escape($p['name']); ?></a>
                                <form action="<?php echo base_url('compare/remove'); ?>" method="post" class="mt-1 m-0">
                                    <input type="hidden" name="product_id" value="<?php echo (int) $p['id']; ?>">
                                    <button class="btn btn-link btn-sm text-danger p-0" type="submit"><i class="bi bi-x-circle"></i> Remove</button>
                                </form>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <th>Price</th>
                        <?php foreach ($products as $p):
                            $special = ($p['special_price'] !== null && (float) $p['special_price'] > 0) ? (float) $p['special_price'] : null;
                            $eff = $special !== null ? $special : (float) $p['price']; ?>
                            <td class="text-center">
                                <span class="fw-bold text-success"><?php echo shop_money($eff); ?></span>
                                <?php if ($special !== null): ?><br><del class="text-muted small"><?php echo shop_money($p['price']); ?></del><?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <th>Rating</th>
                        <?php foreach ($products as $p):
                            $s = $summaries[$p['id']] ?? ['average' => 0, 'count' => 0]; ?>
                            <td class="text-center">
                                <?php if ($s['count'] > 0): ?>
                                    <?php echo shop_stars($s['average']); ?><br>
                                    <span class="small text-muted"><?php echo number_format($s['average'], 1); ?> (<?php echo (int) $s['count']; ?>)</span>
                                <?php else: ?>
                                    <span class="text-muted small">No reviews</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <th>Availability</th>
                        <?php foreach ($products as $p):
                            $badge = $p['stock_status'] === 'out_of_stock' ? 'danger' : ($p['stock_status'] === 'pre_order' ? 'info' : 'success'); ?>
                            <td class="text-center"><span class="badge bg-<?php echo $badge; ?>"><?php echo html_escape(str_replace('_', ' ', ucfirst($p['stock_status']))); ?></span></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <th>SKU</th>
                        <?php foreach ($products as $p): ?>
                            <td class="text-center text-muted"><?php echo html_escape($p['sku'] ?: '—'); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <th></th>
                        <?php foreach ($products as $p):
                            $oos = ($p['stock_status'] === 'out_of_stock'); ?>
                            <td class="text-center">
                                <form action="<?php echo base_url('cart/add'); ?>" method="post" class="m-0">
                                    <input type="hidden" name="product_id" value="<?php echo (int) $p['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <input type="hidden" name="redirect" value="<?php echo base_url('compare'); ?>">
                                    <button class="btn btn-dark btn-sm" type="submit" <?php echo $oos ? 'disabled' : ''; ?>><i class="bi bi-bag-plus me-1"></i>Add to cart</button>
                                </form>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
