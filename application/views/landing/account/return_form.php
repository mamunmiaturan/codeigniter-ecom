<?php defined('BASEPATH') or exit('No direct script access allowed');
$items = $items ?? []; ?>
<section class="container-fluid container-xl py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">Request a Return</h1>
        <a href="<?php echo base_url('account/orders'); ?>" class="btn btn-outline-secondary btn-sm">Back to orders</a>
    </div>
    <div class="row g-4">
        <div class="col-lg-3">
            <?php $this->load->view('landing/account/nav', ['active' => 'returns']); ?>
        </div>
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-body">
                    <p class="text-muted">Order <strong><?php echo html_escape($order['order_number']); ?></strong> — select the items and quantities you'd like to return.</p>
                    <form action="<?php echo base_url('account/return'); ?>" method="post">
                        <input type="hidden" name="order_number" value="<?php echo html_escape($order['order_number']); ?>">
                        <div class="table-responsive mb-3">
                            <table class="table align-middle">
                                <thead><tr><th>Product</th><th class="text-center">Ordered</th><th style="width:120px;">Return qty</th></tr></thead>
                                <tbody>
                                    <?php foreach ($items as $it): ?>
                                        <tr>
                                            <td><?php echo html_escape($it['product_name']); ?><?php if (!empty($it['variant_name'])): ?> <span class="text-muted">(<?php echo html_escape($it['variant_name']); ?>)</span><?php endif; ?></td>
                                            <td class="text-center"><?php echo (int) $it['quantity']; ?></td>
                                            <td><input type="number" min="0" max="<?php echo (int) $it['quantity']; ?>" value="0" class="form-control form-control-sm" name="return_qty[<?php echo (int) $it['id']; ?>]"></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mb-3">
                            <label class="form-label d-block">Request type</label>
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="request_type" id="rt_return" value="return" checked>
                                <label class="btn btn-outline-dark btn-sm" for="rt_return"><i class="bi bi-arrow-return-left me-1"></i> Return &amp; refund</label>
                                <input type="radio" class="btn-check" name="request_type" id="rt_exchange" value="exchange">
                                <label class="btn btn-outline-dark btn-sm" for="rt_exchange"><i class="bi bi-arrow-repeat me-1"></i> Exchange</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <select name="reason" class="form-select" required>
                                <option value="">— Select a reason —</option>
                                <option>Damaged / defective</option>
                                <option>Wrong item received</option>
                                <option>Not as described</option>
                                <option>No longer needed</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Additional details (optional)</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="Tell us more..."></textarea>
                        </div>
                        <button class="btn btn-dark" type="submit"><i class="bi bi-box-arrow-in-left me-1"></i> Submit return request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
