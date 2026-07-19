<?php
$is_edit = !empty($method);
$action  = $is_edit ? base_url('shipping/method_update') : base_url('shipping/method_store');
$symbol  = get_global_setting('currency_symbol') ?: '৳';
$none    = translate('select') ?: '— Select —';
$zones   = isset($zones) ? $zones : [];
?>
<section class="panel">
	<?php echo form_open($action); ?>
	<?php if ($is_edit): ?>
		<input type="hidden" name="id" value="<?php echo encrypt_id($method['id']); ?>">
	<?php endif; ?>
	<div class="panel-heading">
		<h4 class="panel-title">
			<i class="fas fa-truck"></i>
			<?php echo $is_edit ? (translate('edit_shipping_method') ?: 'Edit Shipping Method') : (translate('add_shipping_method') ?: 'Add Shipping Method'); ?>
		</h4>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group <?php if (form_error('zone_id')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('zone') ?: 'Zone'; ?> <span class="required">*</span></label>
					<?php
					$zone_options = ['' => $none] + $zones;
					echo form_dropdown('zone_id', $zone_options, set_value('zone_id', $is_edit ? $method['zone_id'] : ''), "class='form-control' data-plugin-selectTwo data-width='100%'");
					?>
					<span class="error"><?php echo form_error('zone_id'); ?></span>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group <?php if (form_error('code')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('code') ?: 'Code'; ?> <span class="required">*</span></label>
					<input class="form-control" name="code" type="text" placeholder="standard, express" value="<?php echo set_value('code', $is_edit ? $method['code'] : ''); ?>">
					<span class="error"><?php echo form_error('code'); ?></span>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group <?php if (form_error('title')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('title') ?: 'Title'; ?> <span class="required">*</span></label>
					<input class="form-control" name="title" type="text" value="<?php echo set_value('title', $is_edit ? $method['title'] : ''); ?>">
					<span class="error"><?php echo form_error('title'); ?></span>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('description'); ?></label>
					<input class="form-control" name="description" type="text" value="<?php echo set_value('description', $is_edit ? $method['description'] : ''); ?>">
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('type') ?: 'Type'; ?> <span class="required">*</span></label>
					<?php
					$type_options = [
						'flat'     => (translate('flat') ?: 'Flat'),
						'per_unit' => (translate('per_unit') ?: 'Per Unit'),
						'free'     => (translate('free') ?: 'Free'),
					];
					echo form_dropdown('type', $type_options, set_value('type', $is_edit ? $method['type'] : 'flat'), "id='method_type' class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
				</div>
			</div>
			<div class="col-md-3 mb-sm field-base-rate">
				<div class="form-group <?php if (form_error('base_rate')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('base_rate') ?: 'Base Rate'; ?> (<?php echo html_escape($symbol); ?>)</label>
					<input class="form-control" name="base_rate" type="number" step="0.01" min="0" value="<?php echo set_value('base_rate', $is_edit ? $method['base_rate'] : '0.00'); ?>">
					<span class="error"><?php echo form_error('base_rate'); ?></span>
				</div>
			</div>
			<div class="col-md-3 mb-sm field-per-unit">
				<div class="form-group <?php if (form_error('per_unit_rate')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('per_unit_rate') ?: 'Per Unit Rate'; ?> (<?php echo html_escape($symbol); ?>)</label>
					<input class="form-control" name="per_unit_rate" type="number" step="0.01" min="0" value="<?php echo set_value('per_unit_rate', $is_edit ? $method['per_unit_rate'] : '0.00'); ?>">
					<span class="error"><?php echo form_error('per_unit_rate'); ?></span>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group <?php if (form_error('free_over')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('free_over') ?: 'Free Over'; ?> (<?php echo html_escape($symbol); ?>)</label>
					<input class="form-control" name="free_over" type="number" step="0.01" min="0" value="<?php echo set_value('free_over', $is_edit ? $method['free_over'] : ''); ?>">
					<small class="text-muted"><?php echo translate('free_over_hint') ?: 'Leave blank to disable.'; ?></small>
					<span class="error"><?php echo form_error('free_over'); ?></span>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-3 mb-sm">
				<div class="form-group <?php if (form_error('min_days')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('min_days') ?: 'Min Days'; ?></label>
					<input class="form-control" name="min_days" type="number" min="0" value="<?php echo set_value('min_days', $is_edit ? $method['min_days'] : ''); ?>">
					<span class="error"><?php echo form_error('min_days'); ?></span>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group <?php if (form_error('max_days')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('max_days') ?: 'Max Days'; ?></label>
					<input class="form-control" name="max_days" type="number" min="0" value="<?php echo set_value('max_days', $is_edit ? $method['max_days'] : ''); ?>">
					<span class="error"><?php echo form_error('max_days'); ?></span>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('sort_order') ?: 'Sort Order'; ?></label>
					<input class="form-control" name="sort_order" type="number" min="0" value="<?php echo set_value('sort_order', $is_edit ? $method['sort_order'] : 0); ?>">
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
					echo form_dropdown('status', $status_options, set_value('status', $is_edit ? $method['status'] : 'Active'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
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

<script>
(function () {
	function sync() {
		var typeEl = document.getElementById('method_type');
		if (!typeEl) { return; }
		var type = typeEl.value;
		var base = document.querySelector('.field-base-rate');
		var per  = document.querySelector('.field-per-unit');
		if (base) { base.style.display = (type === 'free') ? 'none' : ''; }
		if (per)  { per.style.display  = (type === 'per_unit') ? '' : 'none'; }
	}
	// selectTwo fires jQuery change; bind through jQuery when available.
	if (window.jQuery) {
		jQuery(function ($) { $('#method_type').on('change', sync); sync(); });
	} else {
		document.addEventListener('DOMContentLoaded', function () {
			var el = document.getElementById('method_type');
			if (el) { el.addEventListener('change', sync); }
			sync();
		});
	}
})();
</script>
