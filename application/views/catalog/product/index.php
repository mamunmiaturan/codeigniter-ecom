<!-- Filter card — kept separate, above the table (matches audit/activity layout) -->
<section class="panel app-filter-panel mb-md">
	<header class="panel-heading">
		<h4 class="panel-title"><i class="fas fa-filter"></i> <?php echo translate('filter') ?: 'Filter'; ?></h4>
	</header>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-3">
				<div class="form-group mb-none">
					<label class="control-label"><?php echo translate('category') ?: 'Category'; ?></label>
					<select id="filter_category" class="form-control" data-plugin-selectTwo data-width="100%">
						<option value=""><?php echo translate('all') ?: 'All'; ?></option>
						<?php foreach ($categories as $cid => $cname): ?>
							<option value="<?php echo (int) $cid; ?>"><?php echo html_escape($cname); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="col-md-3">
				<div class="form-group mb-none">
					<label class="control-label"><?php echo translate('status'); ?></label>
					<select id="filter_status" class="form-control" data-plugin-selectTwo data-width="100%" data-minimum-results-for-search="Infinity">
						<option value=""><?php echo translate('all') ?: 'All'; ?></option>
						<option value="Draft"><?php echo translate('draft') ?: 'Draft'; ?></option>
						<option value="Active"><?php echo translate('active') ?: 'Active'; ?></option>
						<option value="Inactive"><?php echo translate('inactive') ?: 'Inactive'; ?></option>
					</select>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="panel">
	<header class="panel-heading">
		<div class="panel-actions">
			<?php if (get_permission('product', 'is_add')): ?>
				<a href="<?php echo base_url('product/create'); ?>" class="btn btn-primary btn-sm">
					<i class="fas fa-plus-circle"></i> <?php echo translate('add_product') ?: 'Add Product'; ?>
				</a>
			<?php endif; ?>
		</div>
		<h2 class="panel-title"><i class="fas fa-box-open"></i> <?php echo translate('products') ?: 'Products'; ?></h2>
	</header>
	<div class="panel-body">
				<table class="table table-bordered table-hover table-condensed" cellspacing="0" width="100%" id="product-table">
			<thead>
				<tr>
					<th><?php echo translate('sl') ?: 'SL'; ?></th>
					<th><?php echo translate('image') ?: 'Image'; ?></th>
					<th><?php echo translate('name'); ?></th>
					<th><?php echo translate('sku') ?: 'SKU'; ?></th>
					<th><?php echo translate('category') ?: 'Category'; ?></th>
					<th><?php echo translate('brand') ?: 'Brand'; ?></th>
					<th><?php echo translate('price') ?: 'Price'; ?></th>
					<th><?php echo translate('stock') ?: 'Stock'; ?></th>
					<th><?php echo translate('status'); ?></th>
					<th><?php echo translate('action'); ?></th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
</section>

<script type="text/javascript">
	$(document).ready(function () {
		var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
		var csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
		var csrfData = {};
		csrfData[csrfName] = csrfHash;
		$.ajaxSetup({ data: csrfData });

		var table = $('#product-table').DataTable({
			"processing": true,
			"serverSide": true,
			"ordering": true,
			"order": [[2, "asc"]],
			"pageLength": 25,
			"autoWidth": false,
			"columns": [
				{ "data": 0 }, { "data": 1 }, { "data": 2 }, { "data": 3 }, { "data": 4 },
				{ "data": 5 }, { "data": 6 }, { "data": 7 }, { "data": 8 }, { "data": 9 }
			],
			"columnDefs": [
				{ "targets": [0, 1, 9], "orderable": false },
				{ "targets": [1, 8, 9], "className": "text-center" }
			],
			"ajax": {
				"url": "<?php echo base_url('product/get_products_server_side'); ?>",
				"type": "POST",
				"data": function (d) {
					d[csrfName] = csrfHash;
					d.status = $('#filter_status').val();
					d.category_id = $('#filter_category').val();
				},
				"dataSrc": function (json) {
					if (json.csrfHash) {
						csrfHash = json.csrfHash;
						csrfData[csrfName] = csrfHash;
						$.ajaxSetup({ data: csrfData });
					}
					return json.data;
				}
			},
			"drawCallback": function () { $('[data-toggle="tooltip"]').tooltip(); },
			"language": { "search": "_INPUT_", "searchPlaceholder": "Search..." }
		});

		$(document).on('change', '#filter_status, #filter_category', function () { table.ajax.reload(); });

		$(document).on('click', '.btn-status-toggle', function () {
			var btn = $(this);
			$.ajax({
				type: 'POST',
				url: "<?php echo base_url('product/status'); ?>",
				data: { id: btn.data('id') },
				dataType: 'json',
				success: function (res) {
					if (res && res.status === 'success') {
						var active = (res.new_status === 'Active');
						btn.attr('data-active', active ? '1' : '0');
						var icon = btn.find('i');
						icon.toggleClass('fa-toggle-on text-success', active).toggleClass('fa-toggle-off text-danger', !active);
						var badge = btn.next('.badge');
						badge.removeClass('badge-success badge-secondary badge-warning')
							.addClass(active ? 'badge-success' : 'badge-secondary').text(res.new_status);
						swal({ toast: true, position: 'top-end', type: 'success', title: res.message, timer: 1200, showConfirmButton: false });
					} else if (res && res.message) {
						swal({ type: 'error', title: res.message });
					}
				}
			});
		});
	});
</script>
