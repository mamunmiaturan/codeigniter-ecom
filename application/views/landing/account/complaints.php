<?php defined('BASEPATH') or exit('No direct script access allowed');
$complaints = $complaints ?? [];
$badges = ['New' => 'danger', 'Under Review' => 'warning', 'Resolved' => 'success', 'Closed' => 'secondary']; ?>
<section class="container-fluid container-xl py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">My Complaints</h1>
        <a href="<?php echo base_url('account/complaint_form'); ?>" class="btn btn-dark btn-sm"><i class="bi bi-plus-lg me-1"></i> File a Complaint</a>
    </div>
    <div class="row g-4">
        <div class="col-lg-3">
            <?php $this->load->view('landing/account/nav', ['active' => 'complaints']); ?>
        </div>
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (empty($complaints)): ?>
                        <p class="text-muted mb-0">You haven&rsquo;t filed any complaints. If something went wrong with an order, <a href="<?php echo base_url('account/complaint_form'); ?>">let us know</a>.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead><tr><th>Subject</th><th>Status</th><th>Date</th><th></th></tr></thead>
                                <tbody>
                                    <?php foreach ($complaints as $c): $cls = $badges[$c['status']] ?? 'secondary'; ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo html_escape($c['subject']); ?></td>
                                            <td><span class="badge bg-<?php echo $cls; ?>"><?php echo html_escape($c['status']); ?></span></td>
                                            <td class="text-muted small"><?php echo time_ago($c['created_at']); ?></td>
                                            <td class="text-end"><a href="<?php echo base_url('account/complaint_view/' . encrypt_id($c['id'])); ?>" class="btn btn-outline-dark btn-sm">View</a></td>
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
