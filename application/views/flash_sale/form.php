<?php
$is_edit = !empty($sale);
$action  = $is_edit ? base_url('flash_sale/update') : base_url('flash_sale/store');

// datetime-local expects "Y-m-d\TH:i"
$fmt_dt = function ($value) {
	$value = trim((string) $value);
	return $value !== '' ? date('Y-m-d\TH:i', strtotime($value)) : '';
};

// Rows to prefill: POST (validation redisplay) > existing items (edit) > none.
$post_pids = $this->input->post('product_id');
if (is_array($post_pids)) {
	$post_prices = (array) $this->input->post('sale_price');
	$rows = [];
	foreach ($post_pids as $i => $pid) {
		$rows[] = ['product_id' => (int) $pid, 'sale_price' => $post_prices[$i] ?? ''];
	}
} elseif (!empty($items)) {
	$rows = [];
	foreach ($items as $it) {
		$rows[] = ['product_id' => (int) $it['product_id'], 'sale_price' => $it['sale_price']];
	}
} else {
	$rows = [];
}

// Reusable <option> list for the product dropdowns ($products = id => label map).
$build_options = function ($selected = 0) use ($products) {
	$html = '<option value="">' . (translate('select_product') ?: 'Select product') . '</option>';
	foreach ($products as $pid => $label) {
		$sel = ((int) $selected === (int) $pid) ? ' selected' : '';
		$html .= '<option value="' . (int) $pid . '"' . $sel . '>' . html_escape($label) . '</option>';
	}
	return $html;
};
?>
<section class="panel">
	<?php echo form_open($action); ?>
	<?php if ($is_edit): ?><input type="hidden" name="id" value="<?php echo encrypt_id($sale['id']); ?>"><?php endif; ?>
	<div class="panel-heading">
		<h4 class="panel-title"><i class="fas fa-bolt"></i> <?php echo $is_edit ? (translate('edit_flash_sale') ?: 'Edit Flash Sale') : (translate('add_flash_sale') ?: 'Add Flash Sale'); ?></h4>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-6 mb-sm">
				<div class="form-group <?php if (form_error('title')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('title') ?: 'Title'; ?> <span class="required">*</span></label>
					<input class="form-control" name="title" type="text" value="<?php echo set_value('title', $is_edit ? $sale['title'] : ''); ?>">
					<span class="error"><?php echo form_error('title'); ?></span>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('status'); ?></label>
					<?php
					$status_options = ['Active' => (translate('active') ?: 'Active'), 'Inactive' => (translate('inactive') ?: 'Inactive')];
					echo form_dropdown('status', $status_options, set_value('status', $is_edit ? $sale['status'] : 'Active'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-3 mb-sm">
				<div class="form-group <?php if (form_error('starts_at')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('starts_at') ?: 'Starts At'; ?> <span class="required">*</span></label>
					<input class="form-control" name="starts_at" type="datetime-local" value="<?php echo set_value('starts_at', $is_edit ? $fmt_dt($sale['starts_at']) : ''); ?>">
					<span class="error"><?php echo form_error('starts_at'); ?></span>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group <?php if (form_error('ends_at')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('ends_at') ?: 'Ends At'; ?> <span class="required">*</span></label>
					<input class="form-control" name="ends_at" type="datetime-local" value="<?php echo set_value('ends_at', $is_edit ? $fmt_dt($sale['ends_at']) : ''); ?>">
					<span class="error"><?php echo form_error('ends_at'); ?></span>
				</div>
			</div>
		</div>

		<hr>
		<h4 class="mb-md"><i class="fas fa-boxes"></i> <?php echo translate('flash_sale_items') ?: 'Sale Products'; ?></h4>
		<table class="table table-bordered" id="fs-items-table">
			<thead>
				<tr>
					<th><?php echo translate('product') ?: 'Product'; ?></th>
					<th style="width:200px;"><?php echo translate('sale_price') ?: 'Sale Price'; ?></th>
					<th style="width:60px;"></th>
				</tr>
			</thead>
			<tbody id="fs-items-body">
				<?php foreach ($rows as $r): ?>
					<tr>
						<td><select name="product_id[]" class="form-control"><?php echo $build_options($r['product_id']); ?></select></td>
						<td><input type="number" step="0.01" min="0" name="sale_price[]" class="form-control" value="<?php echo html_escape($r['sale_price']); ?>"></td>
						<td class="text-center"><button type="button" class="btn btn-danger btn-sm fs-remove-row"><i class="fas fa-trash"></i></button></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<button type="button" class="btn btn-default btn-sm" id="fs-add-row"><i class="fas fa-plus"></i> <?php echo translate('add_item') ?: 'Add Item'; ?></button>
		<small class="text-muted d-block mt-sm"><?php echo translate('flash_sale_items_hint') ?: 'Set a sale price for each product; leave a row blank to skip it.'; ?></small>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-offset-9 col-md-3 text-right">
				<a href="<?php echo base_url('flash_sale'); ?>" class="btn btn-default"><?php echo translate('cancel'); ?></a>
				<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo translate('save'); ?></button>
			</div>
		</div>
	</footer>
	<?php echo form_close(); ?>
</section>

<!-- Blank row template (not submitted; text/html script is inert) -->
<script type="text/html" id="fs-row-template">
	<tr>
		<td><select name="product_id[]" class="form-control"><?php echo $build_options(0); ?></select></td>
		<td><input type="number" step="0.01" min="0" name="sale_price[]" class="form-control"></td>
		<td class="text-center"><button type="button" class="btn btn-danger btn-sm fs-remove-row"><i class="fas fa-trash"></i></button></td>
	</tr>
</script>

<script type="text/javascript">
	$(document).ready(function () {
		var $body = $('#fs-items-body');
		if ($body.children('tr').length === 0) {
			$body.append($('#fs-row-template').html());
		}
		$('#fs-add-row').on('click', function () {
			$body.append($('#fs-row-template').html());
		});
		$(document).on('click', '.fs-remove-row', function () {
			$(this).closest('tr').remove();
		});
	});
</script>
