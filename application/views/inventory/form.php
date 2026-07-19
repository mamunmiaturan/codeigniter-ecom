<?php
$is_edit = !empty($source);
$action  = $is_edit ? base_url('inventory_source/update') : base_url('inventory_source/store');
?>
<section class="panel">
	<?php echo form_open($action); ?>
	<?php if ($is_edit): ?>
		<input type="hidden" name="id" value="<?php echo encrypt_id($source['id']); ?>">
	<?php endif; ?>
	<div class="panel-heading">
		<h4 class="panel-title">
			<i class="fas fa-warehouse"></i>
			<?php echo $is_edit ? (translate('edit_inventory_source') ?: 'Edit Inventory Source') : (translate('add_inventory_source') ?: 'Add Inventory Source'); ?>
		</h4>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-3 mb-sm">
				<div class="form-group <?php if (form_error('code')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('code') ?: 'Code'; ?> <span class="required">*</span></label>
					<input class="form-control" name="code" type="text" value="<?php echo set_value('code', $is_edit ? $source['code'] : ''); ?>">
					<span class="error"><?php echo form_error('code'); ?></span>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group <?php if (form_error('name')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('name') ?: 'Name'; ?> <span class="required">*</span></label>
					<input class="form-control" name="name" type="text" value="<?php echo set_value('name', $is_edit ? $source['name'] : ''); ?>">
					<span class="error"><?php echo form_error('name'); ?></span>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group <?php if (form_error('priority')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('priority') ?: 'Priority'; ?> <small class="text-muted">(<?php echo translate('lower_first') ?: 'lower first'; ?>)</small></label>
					<input class="form-control" name="priority" type="number" min="0" step="1" value="<?php echo set_value('priority', $is_edit ? $source['priority'] : '0'); ?>">
					<span class="error"><?php echo form_error('priority'); ?></span>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('status') ?: 'Status'; ?></label>
					<?php
					$status_options = ['Active' => (translate('active') ?: 'Active'), 'Inactive' => (translate('inactive') ?: 'Inactive')];
					echo form_dropdown('status', $status_options, set_value('status', $is_edit ? $source['status'] : 'Active'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
				</div>
			</div>
		</div>

		<hr class="dotted short">
		<h4 class="mb-sm"><i class="fas fa-address-card"></i> <?php echo translate('contact') ?: 'Contact'; ?></h4>
		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('contact_name') ?: 'Contact Name'; ?></label>
					<input class="form-control" name="contact_name" type="text" value="<?php echo set_value('contact_name', $is_edit ? $source['contact_name'] : ''); ?>">
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('contact_email') ?: 'Contact Email'; ?></label>
					<input class="form-control" name="contact_email" type="text" value="<?php echo set_value('contact_email', $is_edit ? $source['contact_email'] : ''); ?>">
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('contact_number') ?: 'Contact Number'; ?></label>
					<input class="form-control" name="contact_number" type="text" value="<?php echo set_value('contact_number', $is_edit ? $source['contact_number'] : ''); ?>">
				</div>
			</div>
		</div>

		<hr class="dotted short">
		<h4 class="mb-sm"><i class="fas fa-map-marker-alt"></i> <?php echo translate('address') ?: 'Address'; ?></h4>
		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('street') ?: 'Street'; ?></label>
					<input class="form-control" name="street" type="text" value="<?php echo set_value('street', $is_edit ? $source['street'] : ''); ?>">
				</div>
			</div>
			<div class="col-md-2 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('city') ?: 'City'; ?></label>
					<input class="form-control" name="city" type="text" value="<?php echo set_value('city', $is_edit ? $source['city'] : ''); ?>">
				</div>
			</div>
			<div class="col-md-2 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('state') ?: 'State'; ?></label>
					<input class="form-control" name="state" type="text" value="<?php echo set_value('state', $is_edit ? $source['state'] : ''); ?>">
				</div>
			</div>
			<div class="col-md-2 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('country') ?: 'Country'; ?></label>
					<input class="form-control" name="country" type="text" value="<?php echo set_value('country', $is_edit ? $source['country'] : ''); ?>">
				</div>
			</div>
			<div class="col-md-2 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('postcode') ?: 'Postcode'; ?></label>
					<input class="form-control" name="postcode" type="text" value="<?php echo set_value('postcode', $is_edit ? $source['postcode'] : ''); ?>">
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label" style="display:block;"><?php echo translate('default') ?: 'Default'; ?></label>
					<label class="switch">
						<input type="checkbox" name="is_default" value="1" <?php echo set_checkbox('is_default', '1', $is_edit ? !empty($source['is_default']) : false); ?>>
						<?php echo translate('set_as_default_source') ?: 'Set as default source'; ?>
					</label>
					<small class="text-muted d-block"><?php echo translate('default_source_hint') ?: 'Only one source can be the default; setting this clears it on others.'; ?></small>
				</div>
			</div>
		</div>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-offset-9 col-md-3 text-right">
				<a href="<?php echo base_url('inventory_source'); ?>" class="btn btn-default"><?php echo translate('cancel'); ?></a>
				<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo translate('save'); ?></button>
			</div>
		</div>
	</footer>
	<?php echo form_close(); ?>
</section>
