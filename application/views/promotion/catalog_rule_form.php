<?php
$is_edit = !empty($rule);
$action  = $is_edit ? base_url('catalog_rule/update') : base_url('catalog_rule/store');
$symbol  = get_global_setting('currency_symbol') ?: '৳';
$none    = translate('none') ?: '— None —';

$fmt_dt = function ($v) {
	$v = trim((string) $v);
	if ($v === '') {
		return '';
	}
	$ts = strtotime($v);
	return $ts ? date('Y-m-d\TH:i', $ts) : '';
};
$cur_scope = $is_edit ? $rule['scope'] : 'all';
?>
<section class="panel">
	<?php echo form_open($action); ?>
	<?php if ($is_edit): ?>
		<input type="hidden" name="id" value="<?php echo encrypt_id($rule['id']); ?>">
	<?php endif; ?>
	<div class="panel-heading">
		<h4 class="panel-title">
			<i class="fas fa-percentage"></i>
			<?php echo $is_edit ? (translate('edit_catalog_rule') ?: 'Edit Catalog Price Rule') : (translate('add_catalog_rule') ?: 'Add Catalog Price Rule'); ?>
		</h4>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-6 mb-sm">
				<div class="form-group <?php if (form_error('name')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('name'); ?> <span class="required">*</span></label>
					<input class="form-control" name="name" type="text" value="<?php echo set_value('name', $is_edit ? $rule['name'] : ''); ?>">
					<span class="error"><?php echo form_error('name'); ?></span>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group <?php if (form_error('sort_order')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('sort_order') ?: 'Sort Order'; ?></label>
					<input class="form-control" name="sort_order" type="number" min="0" value="<?php echo set_value('sort_order', $is_edit ? $rule['sort_order'] : 0); ?>">
					<span class="error"><?php echo form_error('sort_order'); ?></span>
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
					echo form_dropdown('status', $status_options, set_value('status', $is_edit ? $rule['status'] : 'Active'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('description'); ?></label>
					<textarea class="form-control" rows="2" name="description"><?php echo set_value('description', $is_edit ? $rule['description'] : ''); ?></textarea>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('starts_at') ?: 'Starts At'; ?></label>
					<input class="form-control" name="starts_at" type="datetime-local" value="<?php echo set_value('starts_at', $is_edit ? $fmt_dt($rule['starts_at']) : ''); ?>">
				</div>
			</div>
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('ends_at') ?: 'Ends At'; ?></label>
					<input class="form-control" name="ends_at" type="datetime-local" value="<?php echo set_value('ends_at', $is_edit ? $fmt_dt($rule['ends_at']) : ''); ?>">
				</div>
			</div>
		</div>

		<hr>
		<h5 class="mb-2"><i class="fas fa-bullseye"></i> <?php echo translate('scope') ?: 'Scope'; ?></h5>
		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('scope') ?: 'Scope'; ?></label>
					<?php
					$scope_options = [
						'all'      => (translate('all') ?: 'All'),
						'category' => (translate('category') ?: 'Category'),
						'product'  => (translate('product') ?: 'Product'),
					];
					echo form_dropdown('scope', $scope_options, set_value('scope', $cur_scope), "id='rule-scope' class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
				</div>
			</div>
			<div class="col-md-4 mb-sm scope-field scope-category" style="<?php echo $cur_scope === 'category' ? '' : 'display:none;'; ?>">
				<div class="form-group">
					<label class="control-label"><?php echo translate('category') ?: 'Category'; ?></label>
					<?php
					$cat_options = ['' => $none] + $categories;
					echo form_dropdown('category_id', $cat_options, set_value('category_id', $is_edit ? $rule['category_id'] : ''), "class='form-control' data-plugin-selectTwo data-width='100%'");
					?>
				</div>
			</div>
			<div class="col-md-4 mb-sm scope-field scope-product" style="<?php echo $cur_scope === 'product' ? '' : 'display:none;'; ?>">
				<div class="form-group">
					<label class="control-label"><?php echo translate('product') ?: 'Product'; ?></label>
					<?php
					$prod_options = ['' => $none] + $products;
					echo form_dropdown('product_id', $prod_options, set_value('product_id', $is_edit ? $rule['product_id'] : ''), "class='form-control' data-plugin-selectTwo data-width='100%'");
					?>
				</div>
			</div>
		</div>

		<hr>
		<h5 class="mb-2"><i class="fas fa-tags"></i> <?php echo translate('discount') ?: 'Discount'; ?></h5>
		<div class="row">
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('action_type') ?: 'Action Type'; ?></label>
					<?php
					$action_options = [
						'percentage' => (translate('percentage') ?: 'Percentage'),
						'fixed'      => (translate('fixed') ?: 'Fixed Amount'),
					];
					echo form_dropdown('action_type', $action_options, set_value('action_type', $is_edit ? $rule['action_type'] : 'percentage'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group <?php if (form_error('discount_value')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('discount_value') ?: 'Discount Value'; ?> (%/<?php echo html_escape($symbol); ?>) <span class="required">*</span></label>
					<input class="form-control" name="discount_value" type="number" step="0.01" min="0" value="<?php echo set_value('discount_value', $is_edit ? $rule['discount_value'] : '0.00'); ?>">
					<span class="error"><?php echo form_error('discount_value'); ?></span>
				</div>
			</div>
			<div class="col-md-4 mb-sm">
				<div class="form-group">
					<label class="control-label" style="display:block;"><?php echo translate('end_other_rules') ?: 'Stop Other Rules'; ?></label>
					<label class="switch"><input type="checkbox" name="end_other_rules" value="1" <?php echo set_checkbox('end_other_rules', '1', $is_edit ? !empty($rule['end_other_rules']) : false); ?>> <?php echo translate('end_other_rules_hint') ?: 'Do not apply further rules'; ?></label>
				</div>
			</div>
		</div>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-offset-9 col-md-3 text-right">
				<a href="<?php echo base_url('catalog_rule'); ?>" class="btn btn-default"><?php echo translate('cancel'); ?></a>
				<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo translate('save'); ?></button>
			</div>
		</div>
	</footer>
	<?php echo form_close(); ?>
</section>

<script>
(function () {
	function syncScope() {
		var scope = $('#rule-scope').val();
		$('.scope-category').toggle(scope === 'category');
		$('.scope-product').toggle(scope === 'product');
	}
	$(document).on('change', '#rule-scope', syncScope);
	syncScope();
})();
</script>
