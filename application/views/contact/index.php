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
						<option value="New"><?php echo translate('new') ?: 'New'; ?></option>
						<option value="Read"><?php echo translate('read') ?: 'Read'; ?></option>
						<option value="Replied"><?php echo translate('replied') ?: 'Replied'; ?></option>
						<option value="Closed"><?php echo translate('closed') ?: 'Closed'; ?></option>
					</select>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="panel">
	<header class="panel-heading">
		<h2 class="panel-title"><i class="fas fa-envelope-open-text"></i> <?php echo translate('contact_messages') ?: 'Contact Messages'; ?></h2>
	</header>
	<div class="panel-body">
				<table class="table table-bordered table-hover table-condensed" cellspacing="0" width="100%" id="contact-table">
			<thead>
				<tr>
					<th><?php echo translate('sl') ?: 'SL'; ?></th>
					<th><?php echo translate('name') ?: 'Name'; ?></th>
					<th><?php echo translate('subject') ?: 'Subject'; ?></th>
					<th><?php echo translate('status'); ?></th>
					<th><?php echo translate('received') ?: 'Received'; ?></th>
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

		function refreshCsrf(token) {
			if (!token) { return; }
			csrfHash = token;
			csrfData[csrfName] = csrfHash;
			$.ajaxSetup({ data: csrfData });
		}

		var table = $('#contact-table').DataTable({
			"processing": true,
			"serverSide": true,
			"ordering": true,
			"order": [[4, "desc"]],
			"pageLength": 25,
			"autoWidth": false,
			"columns": [
				{ "data": 0 }, { "data": 1 }, { "data": 2 }, { "data": 3 }, { "data": 4 }, { "data": 5 }
			],
			"columnDefs": [
				{ "targets": [0, 5], "orderable": false },
				{ "targets": [3, 5], "className": "text-center" }
			],
			"ajax": {
				"url": "<?php echo base_url('contact/get_contacts_server_side'); ?>",
				"type": "POST",
				"data": function (d) {
					d[csrfName] = csrfHash;
					d.status = $('#filter_status').val();
				},
				"dataSrc": function (json) {
					refreshCsrf(json.csrfHash);
					return json.data;
				}
			},
			"drawCallback": function () { $('[data-toggle="tooltip"]').tooltip(); },
			"language": { "search": "_INPUT_", "searchPlaceholder": "Search..." }
		});

		$(document).on('change', '#filter_status', function () { table.ajax.reload(); });
	});
</script>
