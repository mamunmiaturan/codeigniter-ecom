<?php
$is_edit = !empty($attribute);
$action  = $is_edit ? base_url('attribute/update') : base_url('attribute/store');
$options = isset($options) && is_array($options) ? $options : [];

// Dropdown option sets (value => label).
$type_options = [];
foreach (eav_types() as $t) {
	$type_options[$t] = ucfirst($t);
}
$swatch_options = ['' => (translate('select') ?: 'Select')];
foreach (eav_swatch_types() as $s) {
	$swatch_options[$s] = ucfirst($s);
}
$validation_options = ['' => (translate('none') ?: 'None')];
foreach (eav_validations() as $v) {
	$validation_options[$v] = ucfirst($v);
}
$status_options = ['Active' => (translate('active') ?: 'Active'), 'Inactive' => (translate('inactive') ?: 'Inactive')];

$cur_type       = $is_edit ? $attribute['type'] : 'text';
$cur_validation = $is_edit ? $attribute['validation'] : '';
$option_types   = ['select', 'multiselect', 'checkbox'];
$is_option_type = in_array($cur_type, $option_types, true);
?>
<section class="panel">
	<?php echo form_open($action); ?>
	<?php if ($is_edit): ?>
		<input type="hidden" name="id" value="<?php echo encrypt_id($attribute['id']); ?>">
	<?php endif; ?>
	<div class="panel-heading">
		<h4 class="panel-title">
			<i class="fas fa-tags"></i>
			<?php echo $is_edit ? (translate('edit_attribute') ?: 'Edit Attribute') : (translate('add_attribute') ?: 'Add Attribute'); ?>
		</h4>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group <?php if (form_error('code')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('code') ?: 'Code'; ?> <span class="required">*</span></label>
					<input class="form-control" name="code" type="text" placeholder="e.g. color, size" value="<?php echo set_value('code', $is_edit ? $attribute['code'] : ''); ?>">
					<small class="text-muted"><?php echo translate('attribute_code_hint') ?: 'Lowercase letters, numbers and underscores only.'; ?></small>
					<span class="error"><?php echo form_error('code'); ?></span>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group <?php if (form_error('admin_name')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('admin_name') ?: 'Admin Name'; ?> <span class="required">*</span></label>
					<input class="form-control" name="admin_name" type="text" value="<?php echo set_value('admin_name', $is_edit ? $attribute['admin_name'] : ''); ?>">
					<span class="error"><?php echo form_error('admin_name'); ?></span>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group <?php if (form_error('name')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('name'); ?> <span class="required">*</span></label>
					<input class="form-control" name="name" type="text" value="<?php echo set_value('name', $is_edit ? $attribute['name'] : ''); ?>">
					<span class="error"><?php echo form_error('name'); ?></span>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group <?php if (form_error('type')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('type') ?: 'Type'; ?> <span class="required">*</span></label>
					<?php echo form_dropdown('type', $type_options, set_value('type', $cur_type), "id='attr-type' class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'"); ?>
					<span class="error"><?php echo form_error('type'); ?></span>
				</div>
			</div>
			<div class="col-md-4 mb-sm" id="swatch-wrap" style="<?php echo $is_option_type ? '' : 'display:none;'; ?>">
				<div class="form-group">
					<label class="control-label"><?php echo translate('swatch_type') ?: 'Swatch Type'; ?></label>
					<?php echo form_dropdown('swatch_type', $swatch_options, set_value('swatch_type', $is_edit ? $attribute['swatch_type'] : ''), "id='attr-swatch' class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'"); ?>
				</div>
			</div>
			<div class="col-md-4 mb-sm" id="validation-wrap" style="<?php echo ($cur_type === 'text') ? '' : 'display:none;'; ?>">
				<div class="form-group">
					<label class="control-label"><?php echo translate('validation') ?: 'Validation'; ?></label>
					<?php echo form_dropdown('validation', $validation_options, set_value('validation', $cur_validation), "id='attr-validation' class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'"); ?>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 mb-sm" id="regex-wrap" style="<?php echo ($cur_type === 'text' && $cur_validation === 'regex') ? '' : 'display:none;'; ?>">
				<div class="form-group">
					<label class="control-label"><?php echo translate('regex') ?: 'Regex Pattern'; ?></label>
					<input class="form-control" name="regex" type="text" placeholder="/^[A-Z0-9]+$/" value="<?php echo set_value('regex', $is_edit ? $attribute['regex'] : ''); ?>">
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('default_value') ?: 'Default Value'; ?></label>
					<input class="form-control" name="default_value" type="text" value="<?php echo set_value('default_value', $is_edit ? $attribute['default_value'] : ''); ?>">
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group <?php if (form_error('position')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('position') ?: 'Position'; ?></label>
					<input class="form-control" name="position" type="number" min="0" value="<?php echo set_value('position', $is_edit ? $attribute['position'] : 0); ?>">
					<span class="error"><?php echo form_error('position'); ?></span>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('status'); ?></label>
					<?php echo form_dropdown('status', $status_options, set_value('status', $is_edit ? $attribute['status'] : 'Active'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'"); ?>
				</div>
			</div>
			<div class="col-md-9 mb-sm">
				<div class="form-group">
					<label class="control-label" style="display:block;"><?php echo translate('options') ?: 'Options'; ?></label>
					<label class="switch" style="margin-right:20px;"><input type="checkbox" name="is_required" value="1" <?php echo set_checkbox('is_required', '1', $is_edit ? !empty($attribute['is_required']) : false); ?>> <?php echo translate('is_required') ?: 'Required'; ?></label>
					<label class="switch" style="margin-right:20px;"><input type="checkbox" name="is_unique" value="1" <?php echo set_checkbox('is_unique', '1', $is_edit ? !empty($attribute['is_unique']) : false); ?>> <?php echo translate('is_unique') ?: 'Unique'; ?></label>
					<label class="switch" style="margin-right:20px;"><input type="checkbox" name="is_filterable" value="1" <?php echo set_checkbox('is_filterable', '1', $is_edit ? !empty($attribute['is_filterable']) : false); ?>> <?php echo translate('is_filterable') ?: 'Filterable'; ?></label>
					<label class="switch" style="margin-right:20px;"><input type="checkbox" name="is_configurable" value="1" <?php echo set_checkbox('is_configurable', '1', $is_edit ? !empty($attribute['is_configurable']) : false); ?>> <?php echo translate('is_configurable') ?: 'Configurable'; ?></label>
					<label class="switch" style="margin-right:20px;"><input type="checkbox" name="is_visible_on_front" value="1" <?php echo set_checkbox('is_visible_on_front', '1', $is_edit ? !empty($attribute['is_visible_on_front']) : false); ?>> <?php echo translate('is_visible_on_front') ?: 'Visible On Front'; ?></label>
				</div>
			</div>
		</div>

		<div id="options-section" style="<?php echo $is_option_type ? '' : 'display:none;'; ?>">
			<hr>
			<h5><i class="fas fa-list-ul"></i> <?php echo translate('attribute_options') ?: 'Attribute Options'; ?></h5>
			<div class="table-responsive">
				<table class="table table-bordered table-condensed" id="attribute-options-table">
					<thead>
						<tr>
							<th style="width:30%;"><?php echo translate('label') ?: 'Label'; ?></th>
							<th style="width:30%;"><?php echo translate('admin_name') ?: 'Admin Name'; ?></th>
							<th style="width:15%;"><?php echo translate('swatch') ?: 'Swatch'; ?></th>
							<th style="width:15%;"><?php echo translate('sort_order') ?: 'Sort'; ?></th>
							<th style="width:10%;"></th>
						</tr>
					</thead>
					<tbody id="options-body">
						<?php foreach ($options as $opt): ?>
							<?php $swatch_val = (isset($opt['swatch_value']) && preg_match('/^#[0-9a-fA-F]{6}$/', (string) $opt['swatch_value'])) ? $opt['swatch_value'] : '#000000'; ?>
							<tr class="option-row">
								<td>
									<input type="hidden" name="option_id[]" value="<?php echo (int) $opt['id']; ?>">
									<input type="text" class="form-control" name="option_label[]" value="<?php echo html_escape($opt['label']); ?>">
								</td>
								<td><input type="text" class="form-control" name="option_admin_name[]" value="<?php echo html_escape($opt['admin_name']); ?>"></td>
								<td><input type="color" class="form-control" name="option_swatch[]" value="<?php echo html_escape($swatch_val); ?>"></td>
								<td><input type="number" class="form-control" name="option_sort[]" min="0" value="<?php echo (int) $opt['sort_order']; ?>"></td>
								<td class="text-center"><button type="button" class="btn btn-sm btn-danger option-remove"><i class="fas fa-times"></i></button></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<button type="button" class="btn btn-sm btn-default" id="add-option-row"><i class="fas fa-plus"></i> <?php echo translate('add_option') ?: 'Add Option'; ?></button>
		</div>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-offset-9 col-md-3 text-right">
				<a href="<?php echo base_url('attribute'); ?>" class="btn btn-default"><?php echo translate('cancel'); ?></a>
				<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo translate('save'); ?></button>
			</div>
		</div>
	</footer>
	<?php echo form_close(); ?>
</section>

<script type="text/javascript">
	(function () {
		var optionTypes = ['select', 'multiselect', 'checkbox'];
		var typeSel = document.getElementById('attr-type');
		var validationSel = document.getElementById('attr-validation');

		function setDisplay(id, show) {
			var el = document.getElementById(id);
			if (el) { el.style.display = show ? '' : 'none'; }
		}

		function currentType() {
			return typeSel ? typeSel.value : '';
		}

		function toggleRegex() {
			var isText = (currentType() === 'text');
			var v = validationSel ? validationSel.value : '';
			setDisplay('regex-wrap', isText && v === 'regex');
		}

		function toggleType() {
			var t = currentType();
			var isOption = optionTypes.indexOf(t) !== -1;
			var isText = (t === 'text');
			setDisplay('options-section', isOption);
			setDisplay('swatch-wrap', isOption);
			setDisplay('validation-wrap', isText);
			toggleRegex();
		}

		// Bind BOTH native change and the jQuery change (select2 fires the jQuery event).
		if (typeSel) {
			typeSel.addEventListener('change', toggleType);
			if (window.jQuery) { window.jQuery(typeSel).on('change', toggleType); }
		}
		if (validationSel) {
			validationSel.addEventListener('change', toggleRegex);
			if (window.jQuery) { window.jQuery(validationSel).on('change', toggleRegex); }
		}

		// Repeatable option rows.
		var optionRow = ''
			+ '<tr class="option-row">'
			+ '<td><input type="hidden" name="option_id[]" value="0">'
			+ '<input type="text" class="form-control" name="option_label[]" value=""></td>'
			+ '<td><input type="text" class="form-control" name="option_admin_name[]" value=""></td>'
			+ '<td><input type="color" class="form-control" name="option_swatch[]" value="#000000"></td>'
			+ '<td><input type="number" class="form-control" name="option_sort[]" min="0" value="0"></td>'
			+ '<td class="text-center"><button type="button" class="btn btn-sm btn-danger option-remove"><i class="fas fa-times"></i></button></td>'
			+ '</tr>';

		var addBtn = document.getElementById('add-option-row');
		var body = document.getElementById('options-body');
		if (addBtn && body) {
			addBtn.addEventListener('click', function () {
				body.insertAdjacentHTML('beforeend', optionRow);
			});
		}
		if (body) {
			body.addEventListener('click', function (e) {
				var btn = e.target.closest ? e.target.closest('.option-remove') : null;
				if (btn) {
					var tr = btn.closest('tr');
					if (tr && tr.parentNode) { tr.parentNode.removeChild(tr); }
				}
			});
		}

		toggleType();
	})();
</script>
