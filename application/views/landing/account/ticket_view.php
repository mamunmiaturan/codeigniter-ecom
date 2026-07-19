<?php defined('BASEPATH') or exit('No direct script access allowed');
$replies = $replies ?? [];
$badges = ['Open' => 'danger', 'In Progress' => 'warning', 'Answered' => 'info', 'Closed' => 'secondary'];
$cls = $badges[$ticket['status']] ?? 'secondary'; ?>
<section class="container-fluid container-xl py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0"><?php echo html_escape($ticket['ticket_number']); ?></h1>
        <a href="<?php echo base_url('account/tickets'); ?>" class="btn btn-outline-secondary btn-sm">Back to tickets</a>
    </div>
    <div class="row g-4">
        <div class="col-lg-3">
            <?php $this->load->view('landing/account/nav', ['active' => 'tickets']); ?>
        </div>
        <div class="col-lg-9">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h2 class="h6 fw-bold mb-0"><?php echo html_escape($ticket['subject']); ?></h2>
                        <span class="badge bg-<?php echo $cls; ?>"><?php echo html_escape($ticket['status']); ?></span>
                    </div>

                    <div class="acc-thread d-flex flex-column gap-2">
                        <?php foreach ($replies as $r): $mine = ($r['sender_type'] === 'customer'); ?>
                            <div class="acc-msg <?php echo $mine ? 'acc-mine' : 'acc-staff'; ?>">
                                <div class="small text-muted mb-1">
                                    <strong><?php echo html_escape($r['sender_name'] ?: ($mine ? 'You' : 'Support')); ?></strong>
                                    · <?php echo $mine ? 'You' : 'Staff'; ?> · <?php echo time_ago($r['created_at']); ?>
                                </div>
                                <div style="line-height:1.6;"><?php echo nl2br(html_escape($r['message'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($replies)): ?><p class="text-muted mb-0">No messages yet.</p><?php endif; ?>
                    </div>

                    <?php if ($ticket['status'] !== 'Closed'): ?>
                        <hr>
                        <?php echo form_open(base_url('account/ticket_reply')); ?>
                            <input type="hidden" name="id" value="<?php echo encrypt_id($ticket['id']); ?>">
                            <label class="form-label">Reply</label>
                            <textarea name="message" class="form-control mb-2" rows="3" required placeholder="Write your reply..."></textarea>
                            <button type="submit" class="btn btn-dark btn-sm"><i class="bi bi-send me-1"></i> Send Reply</button>
                        </form>
                    <?php else: ?>
                        <p class="text-muted small mb-0 mt-2">This ticket is closed. Reopen it by filing a new complaint if you still need help.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .acc-msg { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 14px; max-width: 88%; }
    .acc-mine { background: #eef2ff; align-self: flex-end; }
    .acc-staff { background: #f9fafb; align-self: flex-start; }
</style>
