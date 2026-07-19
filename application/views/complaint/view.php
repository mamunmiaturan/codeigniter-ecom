<?php
$hash = encrypt_id($complaint['id']);
$badge = ['New' => 'danger', 'Under Review' => 'warning', 'Resolved' => 'success', 'Closed' => 'secondary'][$complaint['status']] ?? 'secondary';
?>
<div class="row">
	<div class="col-md-8">
		<section class="panel">
			<header class="panel-heading">
				<div class="panel-actions">
					<a href="<?php echo base_url('complaint'); ?>" class="btn btn-default btn-sm"><i class="fas fa-arrow-left"></i> <?php echo translate('back') ?: 'Back'; ?></a>
				</div>
				<h2 class="panel-title"><i class="fas fa-exclamation-circle"></i> <?php echo html_escape($complaint['subject']); ?></h2>
			</header>
			<div class="panel-body">
				<p class="text-muted mb-md">
					<span class="badge badge-<?php echo $badge; ?>"><?php echo html_escape($complaint['status']); ?></span>
					&nbsp;·&nbsp; <?php echo time_ago($complaint['created_at']); ?>
				</p>
				<div style="white-space:pre-wrap; line-height:1.7;"><?php echo html_escape($complaint['message']); ?></div>
			</div>
		</section>
	</div>

	<div class="col-md-4">
		<section class="panel">
			<header class="panel-heading"><h2 class="panel-title"><i class="fas fa-user"></i> <?php echo translate('customer') ?: 'Customer'; ?></h2></header>
			<div class="panel-body">
				<p class="mb-xs"><strong><?php echo html_escape($complaint['name']); ?></strong></p>
				<p class="mb-xs"><i class="fas fa-envelope text-muted"></i> <?php echo html_escape($complaint['email']); ?></p>
				<?php if (!empty($complaint['phone'])): ?><p class="mb-xs"><i class="fas fa-phone text-muted"></i> <?php echo html_escape($complaint['phone']); ?></p><?php endif; ?>
				<?php if (!empty($complaint['order_id'])): ?><p class="mb-none"><i class="fas fa-shopping-bag text-muted"></i> <?php echo translate('order') ?: 'Order'; ?> #<?php echo (int) $complaint['order_id']; ?></p><?php endif; ?>
			</div>
		</section>

		<?php if (get_permission('complaint', 'is_edit')): ?>
		<section class="panel">
			<header class="panel-heading"><h2 class="panel-title"><i class="fas fa-tasks"></i> <?php echo translate('status') ?: 'Status'; ?></h2></header>
			<div class="panel-body">
				<div class="form-group">
					<select id="complaint-status" class="form-control">
						<?php foreach ($statuses as $s): ?>
							<option value="<?php echo $s; ?>" <?php echo $complaint['status'] === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<button id="complaint-status-save" class="btn btn-primary btn-block btn-sm"><i class="fas fa-save"></i> <?php echo translate('update') ?: 'Update'; ?></button>
			</div>
		</section>
		<?php endif; ?>

		<section class="panel">
			<header class="panel-heading"><h2 class="panel-title"><i class="fas fa-headset"></i> <?php echo translate('ticket') ?: 'Ticket'; ?></h2></header>
			<div class="panel-body">
				<?php if (!empty($ticket)): ?>
					<p class="mb-sm"><?php echo translate('a_ticket_has_been_opened_on_this_complaint') ?: 'A ticket has been opened on this complaint.'; ?></p>
					<a href="<?php echo base_url('ticket/view/' . encrypt_id($ticket['id'])); ?>" class="btn btn-info btn-block btn-sm">
						<i class="fas fa-external-link-alt"></i> <?php echo html_escape($ticket['ticket_number']); ?>
					</a>
				<?php elseif (get_permission('ticket', 'is_add')): ?>
					<p class="text-muted mb-sm"><?php echo translate('open_a_support_ticket_to_work_this_complaint') ?: 'Open a support ticket to work this complaint with the customer.'; ?></p>
					<?php echo form_open(base_url('complaint/create_ticket/' . $hash)); ?>
						<button type="submit" class="btn btn-success btn-block btn-sm"><i class="fas fa-plus-circle"></i> <?php echo translate('create_ticket') ?: 'Create Ticket'; ?></button>
					<?php echo form_close(); ?>
				<?php else: ?>
					<p class="text-muted mb-none"><?php echo translate('no_ticket_yet') ?: 'No ticket yet.'; ?></p>
				<?php endif; ?>
			</div>
		</section>
	</div>
</div>

<script type="text/javascript">
	$(function () {
		var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
		var csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
		$('#complaint-status-save').on('click', function () {
			var data = { id: '<?php echo $hash; ?>', status: $('#complaint-status').val() };
			data[csrfName] = csrfHash;
			$.post("<?php echo base_url('complaint/status'); ?>", data, function (res) {
				if (res && res.status === 'success') {
					swal({ toast: true, position: 'top-end', type: 'success', title: res.message, timer: 1200, showConfirmButton: false });
					// Reload so the create-ticket form re-renders with a fresh CSRF token.
					setTimeout(function () { location.reload(); }, 900);
				} else { swal({ type: 'error', title: (res && res.message) || 'Error' }); }
			}, 'json');
		});
	});
</script>
