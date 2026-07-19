<?php
$hash = encrypt_id($ticket['id']);
$sbadge = ['Open' => 'danger', 'In Progress' => 'warning', 'Answered' => 'info', 'Closed' => 'secondary'][$ticket['status']] ?? 'secondary';
$pbadge = ['Low' => 'secondary', 'Medium' => 'info', 'High' => 'danger'][$ticket['priority']] ?? 'secondary';
?>
<div class="row">
	<div class="col-md-8">
		<section class="panel">
			<header class="panel-heading">
				<div class="panel-actions">
					<a href="<?php echo base_url('ticket'); ?>" class="btn btn-default btn-sm"><i class="fas fa-arrow-left"></i> <?php echo translate('back') ?: 'Back'; ?></a>
				</div>
				<h2 class="panel-title"><i class="fas fa-headset"></i> <?php echo html_escape($ticket['ticket_number']); ?> — <?php echo html_escape($ticket['subject']); ?></h2>
			</header>
			<div class="panel-body">
				<p class="mb-md">
					<span class="badge badge-<?php echo $sbadge; ?>"><?php echo html_escape($ticket['status']); ?></span>
					<span class="badge badge-<?php echo $pbadge; ?>"><?php echo html_escape($ticket['priority']); ?></span>
				</p>

				<!-- Conversation thread -->
				<div class="ticket-thread">
					<?php foreach ($replies as $r): $is_admin = ($r['sender_type'] === 'admin'); ?>
						<div class="tk-msg <?php echo $is_admin ? 'tk-admin' : 'tk-cust'; ?>">
							<div class="tk-meta">
								<strong><?php echo html_escape($r['sender_name'] ?: ($is_admin ? 'Support' : 'Customer')); ?></strong>
								<span class="badge badge-<?php echo $is_admin ? 'primary' : 'default'; ?>"><?php echo $is_admin ? 'Staff' : 'Customer'; ?></span>
								<span class="text-muted"><?php echo time_ago($r['created_at']); ?></span>
							</div>
							<div class="tk-body"><?php echo nl2br(html_escape($r['message'])); ?></div>
						</div>
					<?php endforeach; ?>
					<?php if (empty($replies)): ?>
						<p class="text-muted"><?php echo translate('no_records_found') ?: 'No messages yet.'; ?></p>
					<?php endif; ?>
				</div>

				<?php if (get_permission('ticket', 'is_edit') && $ticket['status'] !== 'Closed'): ?>
				<hr>
				<?php echo form_open(base_url('ticket/reply')); ?>
					<input type="hidden" name="id" value="<?php echo $hash; ?>">
					<div class="form-group">
						<label class="control-label"><?php echo translate('reply') ?: 'Reply'; ?></label>
						<textarea name="message" class="form-control" rows="4" required placeholder="<?php echo translate('type_your_reply') ?: 'Type your reply...'; ?>"></textarea>
					</div>
					<button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane"></i> <?php echo translate('send_reply') ?: 'Send Reply'; ?></button>
				</form>
				<?php endif; ?>
			</div>
		</section>
	</div>

	<div class="col-md-4">
		<?php if (get_permission('ticket', 'is_edit')): ?>
		<section class="panel">
			<header class="panel-heading"><h2 class="panel-title"><i class="fas fa-tasks"></i> <?php echo translate('status') ?: 'Status'; ?></h2></header>
			<div class="panel-body">
				<div class="form-group">
					<select id="ticket-status" class="form-control">
						<?php foreach ($statuses as $s): ?>
							<option value="<?php echo $s; ?>" <?php echo $ticket['status'] === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<button id="ticket-status-save" class="btn btn-primary btn-block btn-sm"><i class="fas fa-save"></i> <?php echo translate('update') ?: 'Update'; ?></button>
			</div>
		</section>
		<?php endif; ?>

		<section class="panel">
			<header class="panel-heading"><h2 class="panel-title"><i class="fas fa-info-circle"></i> <?php echo translate('details') ?: 'Details'; ?></h2></header>
			<div class="panel-body">
				<p class="mb-xs"><span class="text-muted"><?php echo translate('ticket') ?: 'Ticket'; ?>:</span> <strong><?php echo html_escape($ticket['ticket_number']); ?></strong></p>
				<?php if (!empty($ticket['complaint_id'])): ?>
					<p class="mb-xs"><span class="text-muted"><?php echo translate('complaint') ?: 'Complaint'; ?>:</span> <a href="<?php echo base_url('complaint/view/' . encrypt_id($ticket['complaint_id'])); ?>">#<?php echo (int) $ticket['complaint_id']; ?></a></p>
				<?php endif; ?>
				<p class="mb-none"><span class="text-muted"><?php echo translate('date') ?: 'Opened'; ?>:</span> <?php echo time_ago($ticket['created_at']); ?></p>
			</div>
		</section>
	</div>
</div>

<style>
	.ticket-thread { display: flex; flex-direction: column; gap: 12px; }
	.tk-msg { border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 14px; max-width: 88%; }
	.tk-admin { background: #eef2ff; align-self: flex-end; }
	.tk-cust { background: #f9fafb; align-self: flex-start; }
	.tk-meta { font-size: 12px; margin-bottom: 6px; display: flex; gap: 8px; align-items: center; }
	.tk-body { line-height: 1.6; }
</style>

<script type="text/javascript">
	$(function () {
		var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
		var csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
		$('#ticket-status-save').on('click', function () {
			var data = { id: '<?php echo $hash; ?>', status: $('#ticket-status').val() };
			data[csrfName] = csrfHash;
			$.post("<?php echo base_url('ticket/status'); ?>", data, function (res) {
				if (res && res.status === 'success') {
					swal({ toast: true, position: 'top-end', type: 'success', title: res.message, timer: 1200, showConfirmButton: false });
					setTimeout(function () { location.reload(); }, 900);
				} else { swal({ type: 'error', title: (res && res.message) || 'Error' }); }
			}, 'json');
		});
	});
</script>
