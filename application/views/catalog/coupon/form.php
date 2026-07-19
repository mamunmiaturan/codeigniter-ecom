<?php
$is_edit = !empty($coupon);
$action  = $is_edit ? base_url('coupon/update') : base_url('coupon/store');
$sym     = get_global_setting('currency_symbol') ?: '৳';
?>
<section class="panel">
	<?php echo form_open($action); ?>
	<?php if ($is_edit): ?>
		<input type="hidden" name="id" value="<?php echo encrypt_id($coupon['id']); ?>">
	<?php endif; ?>
	<div class="panel-heading">
		<h4 class="panel-title">
			<i class="fas fa-tags"></i>
			<?php echo $is_edit ? (translate('edit_coupon') ?: 'Edit Coupon') : (translate('add_coupon') ?: 'Add Coupon'); ?>
		</h4>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group <?php if (form_error('code')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('code') ?: 'Code'; ?> <span class="required">*</span></label>
					<input class="form-control" name="code" type="text" style="text-transform:uppercase;" value="<?php echo set_value('code', $is_edit ? $coupon['code'] : ''); ?>">
					<span class="error"><?php echo form_error('code'); ?></span>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('type') ?: 'Type'; ?></label>
					<?php
					$type_options = [
						'percentage'    => (translate('percentage') ?: 'Percentage (%)'),
						'fixed'         => (translate('fixed') ?: 'Fixed Amount'),
						'free_shipping' => (translate('free_shipping') ?: 'Free Shipping'),
					];
					echo form_dropdown('type', $type_options, set_value('type', $is_edit ? $coupon['type'] : 'percentage'), "class='form-control' id='coupon_type' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group" id="value_group">
					<label class="control-label"><?php echo translate('value') ?: 'Value'; ?> <small class="text-muted">(% or <?php echo html_escape($sym); ?>)</small></label>
					<input class="form-control" name="value" type="number" step="0.01" min="0" value="<?php echo set_value('value', $is_edit ? $coupon['value'] : '0'); ?>">
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('min_order') ?: 'Min Order Amount'; ?> (<?php echo html_escape($sym); ?>)</label>
					<input class="form-control" name="min_order_amount" type="number" step="0.01" min="0" value="<?php echo set_value('min_order_amount', $is_edit ? $coupon['min_order_amount'] : '0'); ?>">
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('max_discount') ?: 'Max Discount'; ?> (<?php echo html_escape($sym); ?>) <small class="text-muted">(% cap)</small></label>
					<input class="form-control" name="max_discount_amount" type="number" step="0.01" min="0" value="<?php echo set_value('max_discount_amount', $is_edit ? $coupon['max_discount_amount'] : ''); ?>">
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('status'); ?></label>
					<?php
					$status_options = ['Active' => (translate('active') ?: 'Active'), 'Inactive' => (translate('inactive') ?: 'Inactive')];
					echo form_dropdown('status', $status_options, set_value('status', $is_edit ? $coupon['status'] : 'Active'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('usage_limit') ?: 'Total Usage Limit'; ?></label>
					<input class="form-control" name="usage_limit" type="number" min="0" placeholder="<?php echo translate('unlimited') ?: 'Unlimited'; ?>" value="<?php echo set_value('usage_limit', $is_edit ? $coupon['usage_limit'] : ''); ?>">
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('per_user_limit') ?: 'Per-user Limit'; ?></label>
					<input class="form-control" name="usage_limit_per_user" type="number" min="0" placeholder="<?php echo translate('unlimited') ?: 'Unlimited'; ?>" value="<?php echo set_value('usage_limit_per_user', $is_edit ? $coupon['usage_limit_per_user'] : ''); ?>">
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('starts_at') ?: 'Starts At'; ?></label>
					<input class="form-control" name="starts_at" type="text" data-plugin-datepicker value="<?php echo set_value('starts_at', $is_edit && $coupon['starts_at'] ? substr($coupon['starts_at'], 0, 10) : ''); ?>">
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('expires_at') ?: 'Expires At'; ?></label>
					<input class="form-control" name="expires_at" type="text" data-plugin-datepicker value="<?php echo set_value('expires_at', $is_edit && $coupon['expires_at'] ? substr($coupon['expires_at'], 0, 10) : ''); ?>">
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('description'); ?></label>
					<input class="form-control" name="description" type="text" value="<?php echo set_value('description', $is_edit ? $coupon['description'] : ''); ?>">
				</div>
			</div>
		</div>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-offset-9 col-md-3 text-right">
				<a href="<?php echo base_url('coupon'); ?>" class="btn btn-default"><?php echo translate('cancel'); ?></a>
				<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo translate('save'); ?></button>
			</div>
		</div>
	</footer>
	<?php echo form_close(); ?>
</section>

<script type="text/javascript">
	$(document).ready(function () {
		function toggleValue() {
			var t = $('#coupon_type').val();
			$('#value_group').toggle(t !== 'free_shipping');
		}
		toggleValue();
		$('#coupon_type').on('change', toggleValue);
	});
</script>
