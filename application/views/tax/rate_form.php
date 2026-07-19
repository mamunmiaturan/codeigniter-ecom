<?php
$is_edit = !empty($rate);
$action  = $is_edit ? base_url('tax/rate_update') : base_url('tax/rate_store');
?>
<section class="panel">
	<?php echo form_open($action); ?>
	<?php if ($is_edit): ?>
		<input type="hidden" name="id" value="<?php echo encrypt_id($rate['id']); ?>">
	<?php endif; ?>
	<div class="panel-heading">
		<h4 class="panel-title">
			<i class="fas fa-money-bill-wave"></i>
			<?php echo $is_edit ? (translate('edit_tax_rate') ?: 'Edit Tax Rate') : (translate('add_tax_rate') ?: 'Add Tax Rate'); ?>
		</h4>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group <?php if (form_error('identifier')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('identifier') ?: 'Identifier'; ?> <span class="required">*</span></label>
					<input class="form-control" name="identifier" type="text" value="<?php echo set_value('identifier', $is_edit ? $rate['identifier'] : ''); ?>">
					<span class="error"><?php echo form_error('identifier'); ?></span>
				</div>
			</div>
			<div class="col-md-5 mb-sm">
				<div class="form-group <?php if (form_error('name')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('name'); ?> <span class="required">*</span></label>
					<input class="form-control" name="name" type="text" value="<?php echo set_value('name', $is_edit ? $rate['name'] : ''); ?>">
					<span class="error"><?php echo form_error('name'); ?></span>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('status'); ?></label>
					<?php
					$status_options = [
						'Active'   => (translate('active') ?: 'Active'),
						'Inactive' => (translate('inactive') ?: 'Inactive'),
					];
					echo form_dropdown('status', $status_options, set_value('status', $is_edit ? $rate['status'] : 'Active'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-3 mb-sm">
				<div class="form-group <?php if (form_error('country')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('country') ?: 'Country'; ?></label>
					<input class="form-control" name="country" type="text" maxlength="2" placeholder="BD" value="<?php echo set_value('country', $is_edit ? $rate['country'] : 'BD'); ?>">
					<small class="text-muted"><?php echo translate('country_code_hint') ?: '2-letter ISO code (e.g. BD).'; ?></small>
					<span class="error"><?php echo form_error('country'); ?></span>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group <?php if (form_error('state')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('state') ?: 'State'; ?> / <?php echo translate('division') ?: 'Division'; ?></label>
					<input class="form-control" name="state" type="text" placeholder="*" value="<?php echo set_value('state', $is_edit ? $rate['state'] : '*'); ?>">
					<small class="text-muted"><?php echo translate('tax_wildcard_hint') ?: 'Use * to match all.'; ?></small>
					<span class="error"><?php echo form_error('state'); ?></span>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group <?php if (form_error('postcode')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('postcode') ?: 'Postcode'; ?></label>
					<input class="form-control" name="postcode" type="text" placeholder="*" value="<?php echo set_value('postcode', $is_edit ? $rate['postcode'] : '*'); ?>">
					<small class="text-muted"><?php echo translate('tax_wildcard_hint') ?: 'Use * to match all.'; ?></small>
					<span class="error"><?php echo form_error('postcode'); ?></span>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group <?php if (form_error('rate')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('rate') ?: 'Rate'; ?> (%) <span class="required">*</span></label>
					<input class="form-control" name="rate" type="number" step="0.0001" min="0" value="<?php echo set_value('rate', $is_edit ? $rate['rate'] : '0'); ?>">
					<span class="error"><?php echo form_error('rate'); ?></span>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-3 mb-sm">
				<div class="form-group <?php if (form_error('priority')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('priority') ?: 'Priority'; ?></label>
					<input class="form-control" name="priority" type="number" step="1" value="<?php echo set_value('priority', $is_edit ? $rate['priority'] : '0'); ?>">
					<small class="text-muted"><?php echo translate('tax_priority_hint') ?: 'Higher priority matches first.'; ?></small>
					<span class="error"><?php echo form_error('priority'); ?></span>
				</div>
			</div>
		</div>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-offset-9 col-md-3 text-right">
				<a href="<?php echo base_url('tax'); ?>" class="btn btn-default"><?php echo translate('cancel'); ?></a>
				<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo translate('save'); ?></button>
			</div>
		</div>
	</footer>
	<?php echo form_close(); ?>
</section>
