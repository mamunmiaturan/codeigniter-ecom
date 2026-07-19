<?php defined('BASEPATH') or exit('No direct script access allowed');
$badges = ['New' => 'danger', 'Under Review' => 'warning', 'Resolved' => 'success', 'Closed' => 'secondary'];
$cls = $badges[$complaint['status']] ?? 'secondary'; ?>
<section class="container-fluid container-xl py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">Complaint</h1>
        <a href="<?php echo base_url('account/complaints'); ?>" class="btn btn-outline-secondary btn-sm">Back to complaints</a>
    </div>
    <div class="row g-4">
        <div class="col-lg-3">
            <?php $this->load->view('landing/account/nav', ['active' => 'complaints']); ?>
        </div>
        <div class="col-lg-9">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h2 class="h6 fw-bold mb-0"><?php echo html_escape($complaint['subject']); ?></h2>
                        <span class="badge bg-<?php echo $cls; ?>"><?php echo html_escape($complaint['status']); ?></span>
                    </div>
                    <p class="text-muted small mb-3"><?php echo time_ago($complaint['created_at']); ?></p>
                    <div style="white-space:pre-wrap; line-height:1.7;"><?php echo html_escape($complaint['message']); ?></div>
                </div>
            </div>

            <?php if (!empty($ticket)): ?>
                <div class="alert alert-info d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-headset me-1"></i> Our team opened a support ticket on this complaint.</span>
                    <a href="<?php echo base_url('account/ticket_view/' . encrypt_id($ticket['id'])); ?>" class="btn btn-sm btn-info text-white">Open ticket <?php echo html_escape($ticket['ticket_number']); ?></a>
                </div>
            <?php else: ?>
                <p class="text-muted small">We&rsquo;ll review your complaint and open a support ticket if it needs follow-up.</p>
            <?php endif; ?>
        </div>
    </div>
</section>
