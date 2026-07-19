<!-- Filter card — kept separate, above the table (matches audit/activity layout) -->
<section class="panel app-filter-panel mb-md">
	<header class="panel-heading">
		<h4 class="panel-title"><i class="fas fa-filter"></i> <?php echo translate('filter') ?: 'Filter'; ?></h4>
	</header>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-3">
				<div class="form-group mb-none">
					<label class="control-label"><?php echo translate('status'); ?></label>
					<select id="filter_status" class="form-control" data-plugin-selectTwo data-width="100%" data-minimum-results-for-search="Infinity">
						<option value=""><?php echo translate('all') ?: 'All'; ?></option>
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
			<?php if (get_permission('flash_sale', 'is_add')): ?>
				<a href="<?php echo base_url('flash_sale/create'); ?>" class="btn btn-primary btn-sm"><i class="fas fa-plus-circle"></i> <?php echo translate('add_flash_sale') ?: 'Add Flash Sale'; ?></a>
			<?php endif; ?>
		</div>
		<h2 class="panel-title"><i class="fas fa-bolt"></i> <?php echo translate('flash_sale') ?: 'Flash Sale'; ?></h2>
	</header>
	<div class="panel-body">
				<table class="table table-bordered table-hover table-condensed" cellspacing="0" width="100%" id="flash-sale-table">
			<thead>
				<tr>
					<th><?php echo translate('sl') ?: 'SL'; ?></th>
					<th><?php echo translate('title') ?: 'Title'; ?></th>
					<th><?php echo translate('window') ?: 'Window'; ?></th>
					<th><?php echo translate('items') ?: 'Items'; ?></th>
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
		var csrfData = {}; csrfData[csrfName] = csrfHash;
		$.ajaxSetup({ data: csrfData });

		var table = $('#flash-sale-table').DataTable({
			"processing": true, "serverSide": true, "ordering": true,
			"order": [[2, "desc"]], "pageLength": 25, "autoWidth": false,
			"columns": [ {"data":0},{"data":1},{"data":2},{"data":3},{"data":4},{"data":5} ],
			"columnDefs": [ {"targets":[0,3,4,5],"orderable":false}, {"targets":[3,4,5],"className":"text-center"} ],
			"ajax": {
				"url": "<?php echo base_url('flash_sale/get_flash_sales_server_side'); ?>", "type": "POST",
				"data": function (d) { d[csrfName] = csrfHash; d.status = $('#filter_status').val(); },
				"dataSrc": function (json) {
					if (json.csrfHash) { csrfHash = json.csrfHash; csrfData[csrfName] = csrfHash; $.ajaxSetup({ data: csrfData }); }
					return json.data;
				}
			},
			"drawCallback": function () { $('[data-toggle="tooltip"]').tooltip(); },
			"language": { "search": "_INPUT_", "searchPlaceholder": "Search..." }
		});
		$(document).on('change', '#filter_status', function () { table.ajax.reload(); });

		$(document).on('click', '.btn-status-toggle', function () {
			var btn = $(this);
			$.ajax({ type: 'POST', url: "<?php echo base_url('flash_sale/status'); ?>", data: { id: btn.data('id') }, dataType: 'json',
				success: function (res) {
					if (res && res.status === 'success') { table.ajax.reload(null, false);
						swal({ toast: true, position: 'top-end', type: 'success', title: res.message, timer: 1200, showConfirmButton: false });
					} else if (res && res.message) { swal({ type: 'error', title: res.message }); }
				}
			});
		});
	});
</script>
