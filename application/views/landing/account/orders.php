<?php defined('BASEPATH') or exit('No direct script access allowed');
$orders = $orders ?? [];
$total  = $total ?? 0;
$page   = $page ?? 1;
$pages  = max(1, (int) ceil($total / 20)); ?>
<section class="container-fluid container-xl py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">My Orders</h1>
        <a href="<?php echo base_url('account'); ?>" class="btn btn-outline-secondary btn-sm">Back to account</a>
    </div>
    <div class="row g-4">
        <div class="col-lg-3">
            <?php $this->load->view('landing/account/nav', ['active' => 'orders']); ?>
        </div>
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (empty($orders)): ?>
                        <p class="text-muted mb-0">You have no orders yet. <a href="<?php echo base_url('shop'); ?>">Start shopping</a>.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $o): ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo html_escape($o['order_number']); ?></td>
                                            <td class="text-muted small"><?php echo time_ago($o['created_at'] ?? $o['placed_at'] ?? 'now'); ?></td>
                                            <td><?php echo (int) ($o['item_count'] ?? 0); ?></td>
                                            <td><?php echo shop_money($o['total']); ?></td>
                                            <td class="text-uppercase small"><?php echo html_escape($o['payment_method'] ?? ''); ?></td>
                                            <td><?php echo shop_order_status_badge($o['status']); ?></td>
                                            <td class="text-end">
                                                <a href="<?php echo base_url('order/' . rawurlencode($o['order_number'])); ?>" class="btn btn-sm btn-outline-secondary">View</a>
                                                <?php if (in_array($o['status'], ['delivered', 'completed'], true)): ?>
                                                    <a href="<?php echo base_url('account/return/' . rawurlencode($o['order_number'])); ?>" class="btn btn-sm btn-outline-dark">Return</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($pages > 1): ?>
                            <nav class="mt-3">
                                <ul class="pagination pagination-sm mb-0">
                                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="<?php echo base_url('account/orders?page=' . $i); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
