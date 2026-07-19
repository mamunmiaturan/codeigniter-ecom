<?php defined('BASEPATH') or exit('No direct script access allowed');
$returns = $returns ?? [];
$badges = ['requested' => 'secondary', 'approved' => 'info', 'rejected' => 'danger', 'received' => 'primary', 'refunded' => 'success', 'cancelled' => 'secondary']; ?>
<section class="container-fluid container-xl py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">My Returns</h1>
        <a href="<?php echo base_url('account'); ?>" class="btn btn-outline-secondary btn-sm">Back to account</a>
    </div>
    <div class="row g-4">
        <div class="col-lg-3">
            <?php $this->load->view('landing/account/nav', ['active' => 'returns']); ?>
        </div>
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (empty($returns)): ?>
                        <p class="text-muted mb-0">You have no return requests. You can request a return from a delivered order in <a href="<?php echo base_url('account/orders'); ?>">My Orders</a>.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead><tr><th>RMA #</th><th>Order</th><th>Reason</th><th>Status</th><th>Date</th></tr></thead>
                                <tbody>
                                    <?php foreach ($returns as $r): $cls = $badges[$r['status']] ?? 'secondary'; ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo html_escape($r['rma_number']); ?></td>
                                            <td><?php echo html_escape($r['order_number'] ?? '—'); ?></td>
                                            <td><?php echo html_escape($r['reason'] ?: '—'); ?></td>
                                            <td><span class="badge bg-<?php echo $cls; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                                            <td class="text-muted small"><?php echo time_ago($r['created_at']); ?></td>
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
