<?php
$is_edit = !empty($zone);
$action  = $is_edit ? base_url('shipping/zone_update') : base_url('shipping/zone_store');
?>
<section class="panel">
	<?php echo form_open($action); ?>
	<?php if ($is_edit): ?>
		<input type="hidden" name="id" value="<?php echo encrypt_id($zone['id']); ?>">
	<?php endif; ?>
	<div class="panel-heading">
		<h4 class="panel-title">
			<i class="fas fa-map-marked-alt"></i>
			<?php echo $is_edit ? (translate('edit_shipping_zone') ?: 'Edit Shipping Zone') : (translate('add_shipping_zone') ?: 'Add Shipping Zone'); ?>
		</h4>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-6 mb-sm">
				<div class="form-group <?php if (form_error('name')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('name'); ?> <span class="required">*</span></label>
					<input class="form-control" name="name" type="text" value="<?php echo set_value('name', $is_edit ? $zone['name'] : ''); ?>">
					<span class="error"><?php echo form_error('name'); ?></span>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('sort_order') ?: 'Sort Order'; ?></label>
					<input class="form-control" name="sort_order" type="number" min="0" value="<?php echo set_value('sort_order', $is_edit ? $zone['sort_order'] : 0); ?>">
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
					echo form_dropdown('status', $status_options, set_value('status', $is_edit ? $zone['status'] : 'Active'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12 mb-sm">
				<div class="form-group <?php if (form_error('divisions')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('divisions') ?: 'Divisions'; ?> <span class="required">*</span></label>
					<input class="form-control" name="divisions" type="text" placeholder="Dhaka, Chattogram, Khulna" value="<?php echo set_value('divisions', $is_edit ? $zone['divisions'] : ''); ?>">
					<span class="error"><?php echo form_error('divisions'); ?></span>
					<small class="text-muted">
						<?php echo translate('divisions_hint') ?: 'Comma-separated division names this zone serves. Use'; ?>
						<code>*</code> <?php echo translate('divisions_hint_all') ?: 'to serve all divisions (fallback zone).'; ?>
					</small>
				</div>
			</div>
		</div>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-offset-9 col-md-3 text-right">
				<a href="<?php echo base_url('shipping'); ?>" class="btn btn-default"><?php echo translate('cancel'); ?></a>
				<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo translate('save'); ?></button>
			</div>
		</div>
	</footer>
	<?php echo form_close(); ?>
</section>
