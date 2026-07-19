<?php $tab = isset($tab) ? $tab : 'both'; // 'categories' | 'rates' | 'both' ?>
<div class="row">
	<?php if ($tab === 'both' || $tab === 'categories'): ?>
	<!-- ================= Tax Categories ================= -->
	<div class="col-md-12">
		<section class="panel">
			<header class="panel-heading">
				<div class="panel-actions">
					<?php if (get_permission('tax_category', 'is_add')): ?>
						<a href="<?php echo base_url('tax/category_create'); ?>" class="btn btn-primary btn-sm">
							<i class="fas fa-plus-circle"></i> <?php echo translate('add_tax_category') ?: 'Add Tax Category'; ?>
						</a>
					<?php endif; ?>
				</div>
				<h2 class="panel-title"><i class="fas fa-percent"></i> <?php echo translate('tax_categories') ?: 'Tax Categories'; ?></h2>
			</header>
			<div class="panel-body">
				<table class="table table-bordered table-hover table-condensed" cellspacing="0" width="100%" id="tax-category-table">
					<thead>
						<tr>
							<th><?php echo translate('sl') ?: 'SL'; ?></th>
							<th><?php echo translate('code') ?: 'Code'; ?></th>
							<th><?php echo translate('name'); ?></th>
							<th><?php echo translate('default') ?: 'Default'; ?></th>
							<th><?php echo translate('rates') ?: 'Rates'; ?></th>
							<th><?php echo translate('status'); ?></th>
							<th><?php echo translate('action'); ?></th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</section>
	</div>
	<?php endif; ?>

	<?php if ($tab === 'both' || $tab === 'rates'): ?>
	<!-- ================= Tax Rates ================= -->
	<div class="col-md-12">
		<section class="panel">
			<header class="panel-heading">
				<div class="panel-actions">
					<?php if (get_permission('tax_rate', 'is_add')): ?>
						<a href="<?php echo base_url('tax/rate_create'); ?>" class="btn btn-primary btn-sm">
							<i class="fas fa-plus-circle"></i> <?php echo translate('add_tax_rate') ?: 'Add Tax Rate'; ?>
						</a>
					<?php endif; ?>
				</div>
				<h2 class="panel-title"><i class="fas fa-money-bill-wave"></i> <?php echo translate('tax_rates') ?: 'Tax Rates'; ?></h2>
			</header>
			<div class="panel-body">
				<table class="table table-bordered table-hover table-condensed" cellspacing="0" width="100%" id="tax-rate-table">
					<thead>
						<tr>
							<th><?php echo translate('sl') ?: 'SL'; ?></th>
							<th><?php echo translate('identifier') ?: 'Identifier'; ?></th>
							<th><?php echo translate('name'); ?></th>
							<th><?php echo translate('country') ?: 'Country'; ?></th>
							<th><?php echo translate('state') ?: 'State'; ?></th>
							<th><?php echo translate('postcode') ?: 'Postcode'; ?></th>
							<th><?php echo translate('rate') ?: 'Rate'; ?></th>
							<th><?php echo translate('priority') ?: 'Priority'; ?></th>
							<th><?php echo translate('status'); ?></th>
							<th><?php echo translate('action'); ?></th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</section>
	</div>
	<?php endif; ?>
</div>

<script type="text/javascript">
	$(document).ready(function () {
		var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
		var csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
		var csrfData = {};
		csrfData[csrfName] = csrfHash;
		$.ajaxSetup({ data: csrfData });

		function refreshCsrf(json) {
			if (json && json.csrfHash) {
				csrfHash = json.csrfHash;
				csrfData[csrfName] = csrfHash;
				$.ajaxSetup({ data: csrfData });
			}
			return json.data;
		}

		function bindToggle(tableSelector, url) {
			$(document).on('click', tableSelector + ' .btn-status-toggle', function () {
				var btn = $(this);
				$.ajax({
					type: 'POST', url: url, data: { id: btn.data('id') }, dataType: 'json',
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
		}

		var ratesInited = false;
		function initRatesTable() {
			if (ratesInited) { return; }
			ratesInited = true;
			$('#tax-rate-table').DataTable({
				"processing": true, "serverSide": true, "ordering": true, "order": [[1, "asc"]],
				"pageLength": 25, "autoWidth": false,
				"columns": [
					{ "data": 0 }, { "data": 1 }, { "data": 2 }, { "data": 3 }, { "data": 4 },
					{ "data": 5 }, { "data": 6 }, { "data": 7 }, { "data": 8 }, { "data": 9 }
				],
				"columnDefs": [
					{ "targets": [0, 9], "orderable": false },
					{ "targets": [3, 4, 5, 6, 7, 8, 9], "className": "text-center" }
				],
				"ajax": {
					"url": "<?php echo base_url('tax/get_rates_server_side'); ?>", "type": "POST",
					"data": function (d) { d[csrfName] = csrfHash; }, "dataSrc": refreshCsrf
				},
				"drawCallback": function () { $('[data-toggle="tooltip"]').tooltip(); },
				"language": { "search": "_INPUT_", "searchPlaceholder": "Search..." }
			});
			bindToggle('#tax-rate-table', "<?php echo base_url('tax/rate_status'); ?>");
		}

		<?php if ($tab === 'both' || $tab === 'categories'): ?>
		$('#tax-category-table').DataTable({
			"processing": true, "serverSide": true, "ordering": true, "order": [[2, "asc"]],
			"pageLength": 25, "autoWidth": false,
			"columns": [
				{ "data": 0 }, { "data": 1 }, { "data": 2 }, { "data": 3 },
				{ "data": 4 }, { "data": 5 }, { "data": 6 }
			],
			"columnDefs": [
				{ "targets": [0, 4, 6], "orderable": false },
				{ "targets": [3, 4, 5, 6], "className": "text-center" }
			],
			"ajax": {
				"url": "<?php echo base_url('tax/get_categories_server_side'); ?>", "type": "POST",
				"data": function (d) { d[csrfName] = csrfHash; }, "dataSrc": refreshCsrf
			},
			"drawCallback": function () { $('[data-toggle="tooltip"]').tooltip(); },
			"language": { "search": "_INPUT_", "searchPlaceholder": "Search..." },
			// When both tables share a page, defer the second so they never race on
			// the single-use CSRF token. On a dedicated page there is no second table.
			"initComplete": function () { <?php echo ($tab === 'both') ? 'initRatesTable();' : ''; ?> }
		});
		bindToggle('#tax-category-table', "<?php echo base_url('tax/category_status'); ?>");
		<?php endif; ?>

		<?php if ($tab === 'rates'): ?>
		initRatesTable();
		<?php endif; ?>
	});
</script>
