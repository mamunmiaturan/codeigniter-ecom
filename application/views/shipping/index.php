<?php $tab = isset($tab) ? $tab : 'both'; // 'zones' | 'methods' | 'both' ?>
<?php if ($tab === 'both' || $tab === 'zones'): ?>
<!-- ================= Shipping Zones ================= -->
<section class="panel">
	<header class="panel-heading">
		<div class="panel-actions">
			<?php if (get_permission('shipping_zone', 'is_add')): ?>
				<a href="<?php echo base_url('shipping/zone_create'); ?>" class="btn btn-primary btn-sm">
					<i class="fas fa-plus-circle"></i> <?php echo translate('add_shipping_zone') ?: 'Add Zone'; ?>
				</a>
			<?php endif; ?>
		</div>
		<h2 class="panel-title"><i class="fas fa-map-marked-alt"></i> <?php echo translate('shipping_zones') ?: 'Shipping Zones'; ?></h2>
	</header>
	<div class="panel-body">
		<table class="table table-bordered table-hover table-condensed" cellspacing="0" width="100%" id="zone-table">
			<thead>
				<tr>
					<th><?php echo translate('sl') ?: 'SL'; ?></th>
					<th><?php echo translate('name'); ?></th>
					<th><?php echo translate('divisions') ?: 'Divisions'; ?></th>
					<th><?php echo translate('sort_order') ?: 'Sort'; ?></th>
					<th><?php echo translate('status'); ?></th>
					<th><?php echo translate('action'); ?></th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
</section>
<?php endif; ?>

<?php if ($tab === 'both' || $tab === 'methods'): ?>
<!-- ================= Shipping Methods ================= -->
<!-- Filter card — kept separate, above the table (matches audit/activity layout) -->
<section class="panel app-filter-panel mb-md">
	<header class="panel-heading">
		<h4 class="panel-title"><i class="fas fa-filter"></i> <?php echo translate('filter') ?: 'Filter'; ?></h4>
	</header>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-3">
				<div class="form-group mb-none">
					<label class="control-label"><?php echo translate('zone') ?: 'Zone'; ?></label>
					<select id="filter_method_zone" class="form-control" data-plugin-selectTwo data-width="100%">
						<option value=""><?php echo translate('all') ?: 'All'; ?></option>
						<?php foreach ($zones as $zid => $zname): ?>
							<option value="<?php echo (int) $zid; ?>"><?php echo html_escape($zname); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="panel">
	<header class="panel-heading">
		<div class="panel-actions">
			<?php if (get_permission('shipping_method', 'is_add')): ?>
				<a href="<?php echo base_url('shipping/method_create'); ?>" class="btn btn-primary btn-sm">
					<i class="fas fa-plus-circle"></i> <?php echo translate('add_shipping_method') ?: 'Add Method'; ?>
				</a>
			<?php endif; ?>
		</div>
		<h2 class="panel-title"><i class="fas fa-truck"></i> <?php echo translate('shipping_methods') ?: 'Shipping Methods'; ?></h2>
	</header>
	<div class="panel-body">
		<table class="table table-bordered table-hover table-condensed" cellspacing="0" width="100%" id="method-table">
			<thead>
				<tr>
					<th><?php echo translate('sl') ?: 'SL'; ?></th>
					<th><?php echo translate('zone') ?: 'Zone'; ?></th>
					<th><?php echo translate('code') ?: 'Code'; ?></th>
					<th><?php echo translate('title') ?: 'Title'; ?></th>
					<th><?php echo translate('type') ?: 'Type'; ?></th>
					<th><?php echo translate('rate') ?: 'Rate'; ?></th>
					<th><?php echo translate('delivery') ?: 'Delivery'; ?></th>
					<th><?php echo translate('sort_order') ?: 'Sort'; ?></th>
					<th><?php echo translate('status'); ?></th>
					<th><?php echo translate('action'); ?></th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
</section>
<?php endif; ?>

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

		var zoneTable = null, methodTable = null;

		function initMethodTable() {
			if (methodTable) { return; }
			methodTable = $('#method-table').DataTable({
				"processing": true,
				"serverSide": true,
				"ordering": true,
				"order": [[7, "asc"]],
				"pageLength": 25,
				"autoWidth": false,
				"columns": [
					{ "data": 0 }, { "data": 1 }, { "data": 2 }, { "data": 3 }, { "data": 4 },
					{ "data": 5 }, { "data": 6 }, { "data": 7 }, { "data": 8 }, { "data": 9 }
				],
				"columnDefs": [
					{ "targets": [0, 6, 9], "orderable": false },
					{ "targets": [0, 4, 7, 8, 9], "className": "text-center" }
				],
				"ajax": {
					"url": "<?php echo base_url('shipping/get_methods_server_side'); ?>",
					"type": "POST",
					"data": function (d) {
						d[csrfName] = csrfHash;
						d.zone_id = $('#filter_method_zone').val();
					},
					"dataSrc": refreshCsrf
				},
				"drawCallback": function () { $('[data-toggle="tooltip"]').tooltip(); },
				"language": { "search": "_INPUT_", "searchPlaceholder": "Search..." }
			});
		}

		// ---- Status toggles (scoped per table -> correct endpoint) ----
		function bindToggle(tableSelector, url) {
			$(tableSelector).on('click', '.btn-status-toggle', function () {
				var btn = $(this);
				$.ajax({
					type: 'POST',
					url: url,
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
		}

<?php if ($tab === 'both' || $tab === 'zones'): ?>
		// ---- Zones table ----
		zoneTable = $('#zone-table').DataTable({
			"processing": true,
			"serverSide": true,
			"ordering": true,
			"order": [[3, "asc"]],
			"pageLength": 25,
			"autoWidth": false,
			"columns": [
				{ "data": 0 }, { "data": 1 }, { "data": 2 }, { "data": 3 }, { "data": 4 }, { "data": 5 }
			],
			"columnDefs": [
				{ "targets": [0, 5], "orderable": false },
				{ "targets": [0, 3, 4, 5], "className": "text-center" }
			],
			"ajax": {
				"url": "<?php echo base_url('shipping/get_zones_server_side'); ?>",
				"type": "POST",
				"data": function (d) { d[csrfName] = csrfHash; },
				"dataSrc": refreshCsrf
			},
			"drawCallback": function () { $('[data-toggle="tooltip"]').tooltip(); },
			"language": { "search": "_INPUT_", "searchPlaceholder": "Search..." }
<?php if ($tab === 'both'): ?>
			,
			// On the combined view, build the methods table only AFTER the zones
			// request finishes: csrf_regenerate = TRUE would otherwise race the
			// token between two simultaneous POSTs.
			"initComplete": function () { initMethodTable(); }
<?php endif; ?>
		});
		bindToggle('#zone-table', "<?php echo base_url('shipping/zone_status'); ?>");
<?php endif; ?>

<?php if ($tab === 'methods'): ?>
		// Methods-only page: no zones table to sequence behind, so init directly.
		initMethodTable();
<?php endif; ?>

<?php if ($tab === 'both' || $tab === 'methods'): ?>
		$(document).on('change', '#filter_method_zone', function () { if (methodTable) { methodTable.ajax.reload(); } });
		bindToggle('#method-table', "<?php echo base_url('shipping/method_status'); ?>");
<?php endif; ?>
	});
</script>
