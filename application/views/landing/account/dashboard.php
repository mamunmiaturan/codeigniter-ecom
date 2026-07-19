<?php defined('BASEPATH') or exit('No direct script access allowed');
$profile = $profile ?: []; ?>
<section class="container-fluid container-xl py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">Hello, <?php echo html_escape($profile['name'] ?? 'Customer'); ?></h1>
        <a href="<?php echo base_url('account/logout'); ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-box-arrow-right me-1"></i> Log out</a>
    </div>
    <div class="row g-4">
        <div class="col-lg-3">
            <?php $this->load->view('landing/account/nav', ['active' => 'dashboard']); ?>
        </div>
        <div class="col-lg-9">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">Profile</h2>
                    <form action="<?php echo base_url('account/profile'); ?>" method="post" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input name="name" class="form-control" value="<?php echo html_escape($profile['name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input class="form-control" value="<?php echo html_escape($profile['email'] ?? ''); ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input name="phone" class="form-control" value="<?php echo html_escape($profile['phone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input name="address" class="form-control" value="<?php echo html_escape($profile['address'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-dark btn-sm" type="submit"><i class="bi bi-save me-1"></i> Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 fw-bold mb-0">Recent Orders</h2>
                        <a href="<?php echo base_url('account/orders'); ?>" class="small text-decoration-none">View all</a>
                    </div>
                    <?php if (empty($orders)): ?>
                        <p class="text-muted mb-0">You haven't placed any orders yet. <a href="<?php echo base_url('shop'); ?>">Start shopping</a>.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr><th>Order</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $o): ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo html_escape($o['order_number']); ?></td>
                                            <td class="text-muted small"><?php echo time_ago($o['created_at'] ?? $o['placed_at'] ?? 'now'); ?></td>
                                            <td><?php echo shop_money($o['total']); ?></td>
                                            <td><?php echo shop_order_status_badge($o['status']); ?></td>
                                            <td class="text-end"><a href="<?php echo base_url('order/' . rawurlencode($o['order_number'])); ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
