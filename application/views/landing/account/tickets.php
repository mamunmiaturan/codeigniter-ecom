<?php defined('BASEPATH') or exit('No direct script access allowed');
$tickets = $tickets ?? [];
$badges = ['Open' => 'danger', 'In Progress' => 'warning', 'Answered' => 'info', 'Closed' => 'secondary']; ?>
<section class="container-fluid container-xl py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">My Support Tickets</h1>
        <a href="<?php echo base_url('account'); ?>" class="btn btn-outline-secondary btn-sm">Back to account</a>
    </div>
    <div class="row g-4">
        <div class="col-lg-3">
            <?php $this->load->view('landing/account/nav', ['active' => 'tickets']); ?>
        </div>
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (empty($tickets)): ?>
                        <p class="text-muted mb-0">You have no support tickets. Tickets are opened by our team when a complaint needs follow-up.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead><tr><th>Ticket</th><th>Subject</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                                <tbody>
                                    <?php foreach ($tickets as $t): $cls = $badges[$t['status']] ?? 'secondary'; ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo html_escape($t['ticket_number']); ?></td>
                                            <td><?php echo html_escape($t['subject']); ?></td>
                                            <td><span class="badge bg-<?php echo $cls; ?>"><?php echo html_escape($t['status']); ?></span></td>
                                            <td class="text-muted small"><?php echo time_ago($t['updated_at'] ?: $t['created_at']); ?></td>
                                            <td class="text-end"><a href="<?php echo base_url('account/ticket_view/' . encrypt_id($t['id'])); ?>" class="btn btn-outline-dark btn-sm">Open</a></td>
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
