<?php defined('BASEPATH') or exit('No direct script access allowed');
$downloads = $downloads ?? []; ?>
<section class="container-fluid container-xl py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">My Downloads</h1>
        <a href="<?php echo base_url('account'); ?>" class="btn btn-outline-secondary btn-sm">Back to account</a>
    </div>
    <div class="row g-4">
        <div class="col-lg-3">
            <?php $this->load->view('landing/account/nav', ['active' => 'downloads']); ?>
        </div>
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (empty($downloads)): ?>
                        <p class="text-muted mb-0">You have no downloadable purchases yet. <a href="<?php echo base_url('shop'); ?>">Browse the shop</a>.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr><th>File</th><th>Order</th><th>Downloads</th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($downloads as $d):
                                        $limit     = $d['download_limit'] !== null ? (int) $d['download_limit'] : null;
                                        $used      = (int) $d['downloads_used'];
                                        $expired   = $d['expires_at'] !== null && $d['expires_at'] < date('Y-m-d H:i:s');
                                        $exhausted = $limit !== null && $used >= $limit;
                                        $available = !$expired && !$exhausted; ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo html_escape($d['name']); ?></td>
                                            <td class="text-muted small"><?php echo html_escape($d['order_number'] ?? ''); ?></td>
                                            <td class="small text-muted">
                                                <?php if ($limit !== null): ?>
                                                    <?php echo $used; ?> / <?php echo $limit; ?>
                                                <?php else: ?>
                                                    <?php echo $used; ?> <span class="text-muted">(unlimited)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if ($available): ?>
                                                    <a href="<?php echo base_url('download/file/' . rawurlencode($d['token'])); ?>" class="btn btn-sm btn-dark">
                                                        <i class="bi bi-download me-1"></i>Download
                                                    </a>
                                                <?php elseif ($expired): ?>
                                                    <span class="badge bg-secondary">Expired</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Limit reached</span>
                                                <?php endif; ?>
                                            </td>
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
