<div class="row">
	<div class="col-md-12">
		<section class="panel">
			<header class="panel-heading">
				<h2 class="panel-title"><i class="fas fa-headset"></i> <?php echo translate('tickets') ?: 'Support Tickets'; ?></h2>
			</header>
			<div class="panel-body">
				<div class="table-responsive">
					<table class="table table-bordered table-hover table-condensed" cellspacing="0" width="100%" id="ticket-table">
						<thead>
							<tr>
								<th><?php echo translate('sl') ?: 'SL'; ?></th>
								<th><?php echo translate('ticket') ?: 'Ticket'; ?></th>
								<th><?php echo translate('subject') ?: 'Subject'; ?></th>
								<th><?php echo translate('customer') ?: 'Customer'; ?></th>
								<th><?php echo translate('priority') ?: 'Priority'; ?></th>
								<th><?php echo translate('status') ?: 'Status'; ?></th>
								<th><?php echo translate('date') ?: 'Date'; ?></th>
								<th><?php echo translate('action') ?: 'Action'; ?></th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
			</div>
		</section>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function () {
		var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
		var csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
		var csrfData = {}; csrfData[csrfName] = csrfHash;
		$.ajaxSetup({ data: csrfData });
		function refreshCsrf(json) {
			if (json && json.csrfHash) { csrfHash = json.csrfHash; csrfData[csrfName] = csrfHash; $.ajaxSetup({ data: csrfData }); }
			return json.data;
		}
		$('#ticket-table').DataTable({
			"processing": true,
			"serverSide": true,
			"ordering": true,
			"order": [[6, "desc"]],
			"pageLength": 25,
			"autoWidth": false,
			"columns": [ { "data": 0 }, { "data": 1 }, { "data": 2 }, { "data": 3 }, { "data": 4 }, { "data": 5 }, { "data": 6 }, { "data": 7 } ],
			"columnDefs": [ { "targets": [0, 4, 5, 7], "orderable": false }, { "targets": [0, 4, 5, 7], "className": "text-center" } ],
			"ajax": {
				"url": "<?php echo base_url('ticket/get_tickets_server_side'); ?>",
				"type": "POST",
				"data": function (d) { d[csrfName] = csrfHash; },
				"dataSrc": refreshCsrf
			},
			"drawCallback": function () { $('[data-toggle="tooltip"]').tooltip(); },
			"language": { "search": "_INPUT_", "searchPlaceholder": "Search..." }
		});
	});
</script>
