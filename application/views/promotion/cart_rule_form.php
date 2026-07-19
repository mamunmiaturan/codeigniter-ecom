<?php
$is_edit = !empty($cart_rule);
$action  = $is_edit ? base_url('cart_rule/update') : base_url('cart_rule/store');
$sym     = get_global_setting('currency_symbol') ?: '৳';
$none    = translate('none') ?: '— None —';
$categories = isset($categories) ? $categories : [];
$groups     = isset($groups) ? $groups : [];
?>
<section class="panel">
	<?php echo form_open($action); ?>
	<?php if ($is_edit): ?>
		<input type="hidden" name="id" value="<?php echo encrypt_id($cart_rule['id']); ?>">
	<?php endif; ?>
	<div class="panel-heading">
		<h4 class="panel-title">
			<i class="fas fa-percent"></i>
			<?php echo $is_edit ? (translate('edit_cart_rule') ?: 'Edit Cart Price Rule') : (translate('add_cart_rule') ?: 'Add Cart Price Rule'); ?>
		</h4>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group <?php if (form_error('name')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('name'); ?> <span class="required">*</span></label>
					<input class="form-control" name="name" type="text" value="<?php echo set_value('name', $is_edit ? $cart_rule['name'] : ''); ?>">
					<span class="error"><?php echo form_error('name'); ?></span>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('action_type') ?: 'Action Type'; ?></label>
					<?php
					$action_options = [
						'percentage'    => (translate('percentage') ?: 'Percentage (%)'),
						'fixed'         => (translate('fixed') ?: 'Fixed Amount'),
						'free_shipping' => (translate('free_shipping') ?: 'Free Shipping'),
					];
					echo form_dropdown('action_type', $action_options, set_value('action_type', $is_edit ? $cart_rule['action_type'] : 'percentage'), "class='form-control' id='action_type' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('status'); ?></label>
					<?php
					$status_options = ['Active' => (translate('active') ?: 'Active'), 'Inactive' => (translate('inactive') ?: 'Inactive')];
					echo form_dropdown('status', $status_options, set_value('status', $is_edit ? $cart_rule['status'] : 'Active'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group value-group">
					<label class="control-label"><?php echo translate('discount_value') ?: 'Discount Value'; ?> <small class="text-muted">(% or <?php echo html_escape($sym); ?>)</small></label>
					<input class="form-control" name="discount_value" type="number" step="0.01" min="0" value="<?php echo set_value('discount_value', $is_edit ? $cart_rule['discount_value'] : '0'); ?>">
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group value-group">
					<label class="control-label"><?php echo translate('max_discount') ?: 'Max Discount'; ?> (<?php echo html_escape($sym); ?>) <small class="text-muted">(% cap)</small></label>
					<input class="form-control" name="max_discount" type="number" step="0.01" min="0" value="<?php echo set_value('max_discount', $is_edit ? $cart_rule['max_discount'] : ''); ?>">
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('min_subtotal') ?: 'Min Subtotal'; ?> (<?php echo html_escape($sym); ?>)</label>
					<input class="form-control" name="min_subtotal" type="number" step="0.01" min="0" value="<?php echo set_value('min_subtotal', $is_edit ? $cart_rule['min_subtotal'] : '0'); ?>">
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('category') ?: 'Category'; ?> <small class="text-muted">(<?php echo translate('optional') ?: 'optional'; ?>)</small></label>
					<?php
					$cat_options = ['' => $none] + $categories;
					echo form_dropdown('category_id', $cat_options, set_value('category_id', $is_edit ? $cart_rule['category_id'] : ''), "class='form-control' data-plugin-selectTwo data-width='100%'");
					?>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('customer_group') ?: 'Customer Group'; ?> <small class="text-muted">(<?php echo translate('optional') ?: 'optional'; ?>)</small></label>
					<?php
					$grp_options = ['' => (translate('all_customers') ?: '— All customers —')] + $groups;
					echo form_dropdown('customer_group_id', $grp_options, set_value('customer_group_id', $is_edit ? ($cart_rule['customer_group_id'] ?? '') : ''), "class='form-control' data-plugin-selectTwo data-width='100%'");
					?>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group <?php if (form_error('sort_order')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('sort_order') ?: 'Sort Order'; ?></label>
					<input class="form-control" name="sort_order" type="number" min="0" value="<?php echo set_value('sort_order', $is_edit ? $cart_rule['sort_order'] : '0'); ?>">
					<span class="error"><?php echo form_error('sort_order'); ?></span>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('starts_at') ?: 'Starts At'; ?></label>
					<input class="form-control" name="starts_at" type="text" data-plugin-datepicker value="<?php echo set_value('starts_at', $is_edit && $cart_rule['starts_at'] ? substr($cart_rule['starts_at'], 0, 10) : ''); ?>">
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('ends_at') ?: 'Ends At'; ?></label>
					<input class="form-control" name="ends_at" type="text" data-plugin-datepicker value="<?php echo set_value('ends_at', $is_edit && $cart_rule['ends_at'] ? substr($cart_rule['ends_at'], 0, 10) : ''); ?>">
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('usage_limit') ?: 'Total Usage Limit'; ?></label>
					<input class="form-control" name="usage_limit" type="number" min="0" placeholder="<?php echo translate('unlimited') ?: 'Unlimited'; ?>" value="<?php echo set_value('usage_limit', $is_edit ? $cart_rule['usage_limit'] : ''); ?>">
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('per_user_limit') ?: 'Per-user Limit'; ?></label>
					<input class="form-control" name="usage_limit_per_user" type="number" min="0" placeholder="<?php echo translate('unlimited') ?: 'Unlimited'; ?>" value="<?php echo set_value('usage_limit_per_user', $is_edit ? $cart_rule['usage_limit_per_user'] : ''); ?>">
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 mb-sm">
				<label class="control-label" style="display:block;"><?php echo translate('options') ?: 'Options'; ?></label>
				<label class="switch"><input type="checkbox" name="free_shipping" value="1" <?php echo set_checkbox('free_shipping', '1', $is_edit ? !empty($cart_rule['free_shipping']) : false); ?>> <?php echo translate('free_shipping') ?: 'Free Shipping'; ?></label>
				<label class="switch"><input type="checkbox" name="end_other_rules" value="1" <?php echo set_checkbox('end_other_rules', '1', $is_edit ? !empty($cart_rule['end_other_rules']) : false); ?>> <?php echo translate('end_other_rules') ?: 'Stop further rules'; ?></label>
			</div>
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('description'); ?></label>
					<input class="form-control" name="description" type="text" value="<?php echo set_value('description', $is_edit ? $cart_rule['description'] : ''); ?>">
				</div>
			</div>
		</div>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-offset-9 col-md-3 text-right">
				<a href="<?php echo base_url('cart_rule'); ?>" class="btn btn-default"><?php echo translate('cancel'); ?></a>
				<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo translate('save'); ?></button>
			</div>
		</div>
	</footer>
	<?php echo form_close(); ?>
</section>

<script type="text/javascript">
	$(document).ready(function () {
		function toggleValue() {
			var t = $('#action_type').val();
			$('.value-group').toggle(t !== 'free_shipping');
		}
		toggleValue();
		$('#action_type').on('change', toggleValue);
	});
</script>
