<?php
$is_edit = !empty($group);
$action  = $is_edit ? base_url('customer_group/update') : base_url('customer_group/store');
?>
<section class="panel">
	<?php echo form_open($action); ?>
	<?php if ($is_edit): ?>
		<input type="hidden" name="id" value="<?php echo encrypt_id($group['id']); ?>">
	<?php endif; ?>
	<div class="panel-heading">
		<h4 class="panel-title">
			<i class="fas fa-users"></i>
			<?php echo $is_edit ? (translate('edit_customer_group') ?: 'Edit Customer Group') : (translate('add_customer_group') ?: 'Add Customer Group'); ?>
		</h4>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group <?php if (form_error('name')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('name'); ?> <span class="required">*</span></label>
					<input class="form-control" name="name" type="text" value="<?php echo set_value('name', $is_edit ? $group['name'] : ''); ?>">
					<span class="error"><?php echo form_error('name'); ?></span>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group <?php if (form_error('code')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('code') ?: 'Code'; ?> <span class="required">*</span></label>
					<input class="form-control" name="code" type="text" value="<?php echo set_value('code', $is_edit ? $group['code'] : ''); ?>">
					<span class="error"><?php echo form_error('code'); ?></span>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group <?php if (form_error('discount_percent')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('discount') ?: 'Discount'; ?> % <small class="text-muted">(0 - 100)</small></label>
					<input class="form-control" name="discount_percent" type="number" step="0.01" min="0" max="100" value="<?php echo set_value('discount_percent', $is_edit ? $group['discount_percent'] : '0'); ?>">
					<span class="error"><?php echo form_error('discount_percent'); ?></span>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('status'); ?></label>
					<?php
					$status_options = ['Active' => (translate('active') ?: 'Active'), 'Inactive' => (translate('inactive') ?: 'Inactive')];
					echo form_dropdown('status', $status_options, set_value('status', $is_edit ? $group['status'] : 'Active'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label" style="display:block;"><?php echo translate('default') ?: 'Default'; ?></label>
					<label class="switch">
						<input type="checkbox" name="is_default" value="1" <?php echo set_checkbox('is_default', '1', $is_edit ? !empty($group['is_default']) : false); ?>>
						<?php echo translate('set_as_default_group') ?: 'Set as default group'; ?>
					</label>
					<small class="text-muted d-block"><?php echo translate('default_group_hint') ?: 'Only one group can be the default; setting this clears it on others.'; ?></small>
				</div>
			</div>
		</div>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-offset-9 col-md-3 text-right">
				<a href="<?php echo base_url('customer_group'); ?>" class="btn btn-default"><?php echo translate('cancel'); ?></a>
				<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo translate('save'); ?></button>
			</div>
		</div>
	</footer>
	<?php echo form_close(); ?>
</section>
