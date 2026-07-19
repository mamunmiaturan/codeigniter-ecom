<?php
$is_edit = !empty($tax_category);
$action  = $is_edit ? base_url('tax/category_update') : base_url('tax/category_store');
$rates_all         = isset($rates_all) ? $rates_all : [];
$selected_rate_ids = isset($selected_rate_ids) ? $selected_rate_ids : [];
$all_label = translate('all') ?: 'All';

// Checkbox state source: a re-rendered form after a validation failure carries
// the posted selection; otherwise fall back to the mapped ids (edit) / none (create).
$posted_rates = $this->input->post('rate_ids');
$use_posted   = is_array($posted_rates);
$selected_map = [];
foreach ($use_posted ? $posted_rates : $selected_rate_ids as $sid) {
	$selected_map[(int) $sid] = true;
}
?>
<section class="panel">
	<?php echo form_open($action); ?>
	<?php if ($is_edit): ?>
		<input type="hidden" name="id" value="<?php echo encrypt_id($tax_category['id']); ?>">
	<?php endif; ?>
	<div class="panel-heading">
		<h4 class="panel-title">
			<i class="fas fa-percent"></i>
			<?php echo $is_edit ? (translate('edit_tax_category') ?: 'Edit Tax Category') : (translate('add_tax_category') ?: 'Add Tax Category'); ?>
		</h4>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group <?php if (form_error('code')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('code') ?: 'Code'; ?> <span class="required">*</span></label>
					<input class="form-control" name="code" type="text" value="<?php echo set_value('code', $is_edit ? $tax_category['code'] : ''); ?>">
					<span class="error"><?php echo form_error('code'); ?></span>
				</div>
			</div>
			<div class="col-md-5 mb-sm">
				<div class="form-group <?php if (form_error('name')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('name'); ?> <span class="required">*</span></label>
					<input class="form-control" name="name" type="text" value="<?php echo set_value('name', $is_edit ? $tax_category['name'] : ''); ?>">
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
					echo form_dropdown('status', $status_options, set_value('status', $is_edit ? $tax_category['status'] : 'Active'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-9 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('description'); ?></label>
					<textarea class="form-control" rows="2" name="description"><?php echo set_value('description', $is_edit ? $tax_category['description'] : ''); ?></textarea>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label" style="display:block;"><?php echo translate('default_tax_category') ?: 'Default Category'; ?></label>
					<label class="switch">
						<input type="checkbox" name="is_default" value="1" <?php echo set_checkbox('is_default', '1', $is_edit ? !empty($tax_category['is_default']) : false); ?>>
						<?php echo translate('is_default') ?: 'Default'; ?>
					</label>
					<small class="text-muted" style="display:block;"><?php echo translate('default_tax_category_hint') ?: 'Applied to products with no specific tax category.'; ?></small>
				</div>
			</div>
		</div>

		<!-- ================= Mapped Tax Rates ================= -->
		<hr>
		<h5 class="mb-1"><i class="fas fa-money-bill-wave"></i> <?php echo translate('tax_rates') ?: 'Tax Rates'; ?></h5>
		<p class="text-muted" style="font-size:13px;">
			<?php echo translate('tax_category_rates_hint') ?: 'Select which tax rates belong to this category.'; ?>
		</p>
		<?php if (empty($rates_all)): ?>
			<p class="text-muted">
				<?php echo translate('no_tax_rates_yet') ?: 'No tax rates have been created yet.'; ?>
				<a href="<?php echo base_url('tax/rate_create'); ?>"><?php echo translate('add_tax_rate') ?: 'Add Tax Rate'; ?></a>
			</p>
		<?php else: ?>
			<div class="row">
				<?php foreach ($rates_all as $r):
					$loc = html_escape($r['country'])
						. ' / ' . ($r['state'] === '*' ? $all_label : html_escape($r['state']))
						. ' / ' . ($r['postcode'] === '*' ? $all_label : html_escape($r['postcode']));
					$pct = rtrim(rtrim(number_format((float) $r['rate'], 4, '.', ''), '0'), '.') . '%';
					$is_checked = !empty($selected_map[(int) $r['id']]);
				?>
					<div class="col-md-4 mb-sm">
						<label class="checkbox-custom" style="cursor:pointer;display:block;padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;">
							<input type="checkbox" name="rate_ids[]" value="<?php echo (int) $r['id']; ?>" <?php echo $is_checked ? 'checked' : ''; ?>>
							<strong><?php echo html_escape($r['identifier']); ?></strong> &mdash; <?php echo html_escape($r['name']); ?>
							<span class="badge badge-info"><?php echo $pct; ?></span>
							<?php if ($r['status'] !== 'Active'): ?>
								<span class="badge badge-secondary"><?php echo translate('inactive') ?: 'Inactive'; ?></span>
							<?php endif; ?>
							<br><small class="text-muted"><?php echo $loc; ?></small>
						</label>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
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
