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
						<?php foreach ($statuses as $s): ?>
							<option value="<?php echo html_escape($s); ?>"><?php echo ucfirst($s); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="panel">
	<header class="panel-heading">
		<h2 class="panel-title"><i class="fas fa-box-open"></i> <?php echo translate('returns') ?: 'Returns / RMA'; ?></h2>
	</header>
	<div class="panel-body">
				<table class="table table-bordered table-hover table-condensed" cellspacing="0" width="100%" id="rma-table">
			<thead>
				<tr>
					<th><?php echo translate('sl') ?: 'SL'; ?></th>
					<th><?php echo translate('rma_number') ?: 'RMA #'; ?></th>
					<th><?php echo translate('order') ?: 'Order'; ?></th>
					<th><?php echo translate('reason') ?: 'Reason'; ?></th>
					<th><?php echo translate('status'); ?></th>
					<th><?php echo translate('date') ?: 'Date'; ?></th>
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

		var table = $('#rma-table').DataTable({
			"processing": true, "serverSide": true, "ordering": true,
			"order": [[5, "desc"]], "pageLength": 25, "autoWidth": false,
			"columns": [ {"data":0},{"data":1},{"data":2},{"data":3},{"data":4},{"data":5},{"data":6} ],
			"columnDefs": [ {"targets":[0,6],"orderable":false}, {"targets":[4,6],"className":"text-center"} ],
			"ajax": {
				"url": "<?php echo base_url('rma/get_rma_server_side'); ?>", "type": "POST",
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
	});
</script>
