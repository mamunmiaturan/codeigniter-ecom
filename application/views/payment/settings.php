<?php
/**
 * Payment Settings — single-form editor over payment_model->methods().
 * One <form> posts every method; the controller loops and calls save_setting().
 * Only the sslcommerz method exposes editable credential/config fields.
 */
$can_edit = get_permission('payment_method', 'is_edit');
$methods  = isset($methods) ? $methods : [];
?>
<section class="panel">
	<?php echo form_open(base_url('payment-settings')); ?>
	<input type="hidden" name="save_payment_settings" value="1">
	<header class="panel-heading">
		<h2 class="panel-title"><i class="fas fa-credit-card"></i> <?php echo translate('payment_settings') ?: 'Payment Settings'; ?></h2>
	</header>
	<div class="panel-body">
		<p class="text-muted" style="font-size:13px;">
			<?php echo translate('payment_settings_hint') ?: 'Enable the payment methods your store accepts, rename them, and set the order they appear at checkout.'; ?>
		</p>

		<?php if (empty($methods)): ?>
			<p class="text-muted"><?php echo translate('no_data_found') ?: 'No payment methods available.'; ?></p>
		<?php else: ?>
			<?php foreach ($methods as $code => $m):
				$is_active   = !empty($m['is_active']);
				$is_online   = !empty($m['is_online']);
				$title       = $m['title'] ?? ucfirst($code);
				$description = $m['description'] ?? '';
				$sort        = (int) ($m['sort'] ?? 0);
				$cfg         = isset($m['config']) && is_array($m['config']) ? $m['config'] : [];
			?>
			<div class="payment-method-card mb-md" style="border:1px solid #e5e7eb;border-radius:8px;padding:18px;">
				<div class="row">
					<div class="col-md-8">
						<h4 class="mb-1" style="margin-top:0;">
							<?php echo html_escape($title); ?>
							<?php if ($is_online): ?>
								<span class="badge badge-info"><?php echo translate('online') ?: 'Online'; ?></span>
							<?php else: ?>
								<span class="badge badge-secondary"><?php echo translate('offline') ?: 'Offline'; ?></span>
							<?php endif; ?>
							<small class="text-muted">(<?php echo html_escape($code); ?>)</small>
						</h4>
						<?php if ($description !== ''): ?>
							<p class="text-muted" style="font-size:13px;margin-bottom:0;"><?php echo html_escape($description); ?></p>
						<?php endif; ?>
					</div>
					<div class="col-md-4 text-right">
						<div class="form-group" style="margin-bottom:0;">
							<label class="switch">
								<input type="checkbox" name="is_active[<?php echo html_escape($code); ?>]" value="1" <?php echo $is_active ? 'checked' : ''; ?> <?php echo $can_edit ? '' : 'disabled'; ?>>
								<?php echo translate('active') ?: 'Active'; ?>
							</label>
						</div>
					</div>
				</div>

				<div class="row" style="margin-top:12px;">
					<div class="col-md-8 mb-sm">
						<div class="form-group">
							<label class="control-label"><?php echo translate('title') ?: 'Title'; ?></label>
							<input class="form-control" type="text" name="title[<?php echo html_escape($code); ?>]" value="<?php echo html_escape($title); ?>" <?php echo $can_edit ? '' : 'readonly'; ?>>
						</div>
					</div>
					<div class="col-md-4 mb-sm">
						<div class="form-group">
							<label class="control-label"><?php echo translate('sort_order') ?: 'Sort Order'; ?></label>
							<input class="form-control" type="number" name="sort_order[<?php echo html_escape($code); ?>]" value="<?php echo $sort; ?>" <?php echo $can_edit ? '' : 'readonly'; ?>>
						</div>
					</div>
				</div>

				<?php if ($code === 'sslcommerz'): ?>
				<!-- SSLCommerz credentials -->
				<div class="row" style="margin-top:6px;padding-top:12px;border-top:1px dashed #e5e7eb;">
					<div class="col-md-12 mb-sm">
						<label class="switch">
							<input type="checkbox" name="ssl_sandbox" value="1" <?php echo !empty($cfg['sandbox']) ? 'checked' : ''; ?> <?php echo $can_edit ? '' : 'disabled'; ?>>
							<?php echo translate('sandbox_mode') ?: 'Sandbox Mode'; ?>
						</label>
						<small class="text-muted" style="display:block;">
							<?php echo translate('sslcommerz_sandbox_hint') ?: 'Use the SSLCommerz sandbox for testing. Turn off for live payments.'; ?>
						</small>
					</div>
					<div class="col-md-6 mb-sm">
						<div class="form-group">
							<label class="control-label"><?php echo translate('store_id') ?: 'Store ID'; ?></label>
							<input class="form-control" type="text" name="ssl_store_id" value="<?php echo html_escape($cfg['store_id'] ?? ''); ?>" autocomplete="off" <?php echo $can_edit ? '' : 'readonly'; ?>>
						</div>
					</div>
					<div class="col-md-6 mb-sm">
						<div class="form-group">
							<label class="control-label"><?php echo translate('store_password') ?: 'Store Password'; ?></label>
							<input class="form-control" type="password" name="ssl_store_passwd" value="<?php echo html_escape($cfg['store_passwd'] ?? ''); ?>" autocomplete="new-password" <?php echo $can_edit ? '' : 'readonly'; ?>>
						</div>
					</div>
				</div>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	<?php if ($can_edit && !empty($methods)): ?>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-offset-9 col-md-3 text-right">
				<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo translate('save') ?: 'Save'; ?></button>
			</div>
		</div>
	</footer>
	<?php endif; ?>
	<?php echo form_close(); ?>
</section>
