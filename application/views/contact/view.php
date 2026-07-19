<?php
// Inline status badge helper (defined before first use).
if (!function_exists('contact_status_badge_html')) {
	function contact_status_badge_html($status)
	{
		$map = ['New' => 'danger', 'Read' => 'info', 'Replied' => 'success', 'Closed' => 'secondary'];
		$cls = $map[$status] ?? 'secondary';
		return '<span class="badge badge-' . $cls . '">' . html_escape(translate(strtolower($status)) ?: $status) . '</span>';
	}
}
$can_edit = get_permission('contact', 'is_edit');
?>
<div class="row">
	<div class="col-md-8">
		<section class="panel">
			<header class="panel-heading">
				<div class="panel-actions">
					<a href="<?php echo base_url('contact'); ?>" class="btn btn-default btn-sm"><i class="fas fa-arrow-left"></i> <?php echo translate('back') ?: 'Back'; ?></a>
				</div>
				<h2 class="panel-title" style="display:flex;align-items:center;gap:8px;">
					<i class="fas fa-envelope"></i>
					<span><?php echo html_escape($message['subject'] ?: (translate('contact_message') ?: 'Contact Message')); ?></span>
					<?php echo contact_status_badge_html($message['status']); ?>
				</h2>
			</header>
			<div class="panel-body">
				<div class="row">
					<div class="col-md-6">
						<p><strong><?php echo translate('name') ?: 'Name'; ?>:</strong> <?php echo html_escape($message['name']); ?></p>
						<p><strong><?php echo translate('email') ?: 'Email'; ?>:</strong>
							<?php if (!empty($message['email'])): ?>
								<a href="mailto:<?php echo html_escape($message['email']); ?>"><?php echo html_escape($message['email']); ?></a>
							<?php else: ?><span class="text-muted">—</span><?php endif; ?>
						</p>
					</div>
					<div class="col-md-6">
						<p><strong><?php echo translate('phone') ?: 'Phone'; ?>:</strong> <?php echo html_escape($message['phone'] ?: '—'); ?></p>
						<p><strong><?php echo translate('received') ?: 'Received'; ?>:</strong> <?php echo time_ago($message['created_at']); ?></p>
					</div>
				</div>

				<div class="alert alert-light" style="border:1px solid #eee;white-space:pre-wrap;"><?php echo html_escape($message['message']); ?></div>

				<?php if (!empty($message['admin_reply'])): ?>
					<div class="alert alert-success" style="white-space:pre-wrap;">
						<strong><i class="fas fa-reply"></i> <?php echo translate('reply') ?: 'Reply'; ?>:</strong><br>
						<?php echo html_escape($message['admin_reply']); ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
	</div>

	<div class="col-md-4">
		<?php if ($can_edit): ?>
			<section class="panel">
				<header class="panel-heading"><h2 class="panel-title"><i class="fas fa-reply"></i> <?php echo translate('reply') ?: 'Reply'; ?></h2></header>
				<div class="panel-body">
					<?php echo form_open(base_url('contact/reply')); ?>
					<input type="hidden" name="id" value="<?php echo encrypt_id($message['id']); ?>">
					<div class="form-group">
						<label class="control-label"><?php echo translate('reply_message') ?: 'Reply Message'; ?></label>
						<textarea class="form-control" name="reply" rows="5" placeholder="<?php echo translate('type_your_reply') ?: 'Type your reply...'; ?>"><?php echo html_escape($message['admin_reply'] ?? ''); ?></textarea>
						<?php if (!empty($message['email'])): ?>
							<small class="text-muted"><?php echo translate('reply_will_be_emailed_to_customer') ?: 'A copy will be emailed to the customer.'; ?></small>
						<?php endif; ?>
					</div>
					<button type="submit" class="btn btn-success btn-block"><i class="fas fa-paper-plane"></i> <?php echo translate('send_reply') ?: 'Send Reply'; ?></button>
					<?php echo form_close(); ?>
				</div>
			</section>

			<section class="panel">
				<header class="panel-heading"><h2 class="panel-title"><i class="fas fa-tasks"></i> <?php echo translate('status'); ?></h2></header>
				<div class="panel-body">
					<?php echo form_open(base_url('contact/status')); ?>
					<input type="hidden" name="id" value="<?php echo encrypt_id($message['id']); ?>">
					<div class="form-group">
						<?php
						$opts = [];
						foreach ($statuses as $s) { $opts[$s] = translate(strtolower($s)) ?: $s; }
						echo form_dropdown('status', $opts, $message['status'], "class='form-control' data-plugin-selectTwo data-width='100%'");
						?>
					</div>
					<button type="submit" class="btn btn-primary btn-block"><i class="fas fa-check"></i> <?php echo translate('update') ?: 'Update'; ?></button>
					<?php echo form_close(); ?>
				</div>
			</section>
		<?php endif; ?>
	</div>
</div>
