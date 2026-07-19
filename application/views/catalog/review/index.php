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
						<option value="pending"><?php echo translate('pending') ?: 'Pending'; ?></option>
						<option value="approved"><?php echo translate('approved') ?: 'Approved'; ?></option>
						<option value="rejected"><?php echo translate('rejected') ?: 'Rejected'; ?></option>
					</select>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="panel">
	<header class="panel-heading">
		<h2 class="panel-title"><i class="fas fa-star"></i> <?php echo translate('reviews') ?: 'Reviews'; ?></h2>
	</header>
	<div class="panel-body">
				<table class="table table-bordered table-hover table-condensed" cellspacing="0" width="100%" id="review-table">
			<thead>
				<tr>
					<th><?php echo translate('sl') ?: 'SL'; ?></th>
					<th><?php echo translate('product') ?: 'Product'; ?></th>
					<th><?php echo translate('author') ?: 'Author'; ?></th>
					<th><?php echo translate('rating') ?: 'Rating'; ?></th>
					<th><?php echo translate('review') ?: 'Review'; ?></th>
					<th><?php echo translate('verified') ?: 'Verified'; ?></th>
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
		var csrfData = {};
		csrfData[csrfName] = csrfHash;
		$.ajaxSetup({ data: csrfData });

		var table = $('#review-table').DataTable({
			"processing": true,
			"serverSide": true,
			"ordering": true,
			"order": [[7, "desc"]],
			"pageLength": 25,
			"autoWidth": false,
			"columns": [
				{ "data": 0 }, { "data": 1 }, { "data": 2 }, { "data": 3 }, { "data": 4 },
				{ "data": 5 }, { "data": 6 }, { "data": 7 }, { "data": 8 }
			],
			"columnDefs": [
				{ "targets": [0, 4, 8], "orderable": false },
				{ "targets": [3, 5, 6, 8], "className": "text-center" }
			],
			"ajax": {
				"url": "<?php echo base_url('review/get_reviews_server_side'); ?>",
				"type": "POST",
				"data": function (d) {
					d[csrfName] = csrfHash;
					d.status = $('#filter_status').val();
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

		$(document).on('change', '#filter_status', function () { table.ajax.reload(); });

		function moderate(url, id) {
			$.ajax({
				type: 'POST', url: url, data: { id: id }, dataType: 'json',
				success: function (res) {
					if (res && res.status === 'success') {
						swal({ toast: true, position: 'top-end', type: 'success', title: res.message, timer: 1200, showConfirmButton: false });
						table.ajax.reload(null, false);
					} else if (res && res.message) {
						swal({ type: 'error', title: res.message });
					}
				}
			});
		}

		$(document).on('click', '.rv-approve', function () { moderate("<?php echo base_url('review/approve'); ?>", $(this).data('id')); });
		$(document).on('click', '.rv-reject', function () { moderate("<?php echo base_url('review/reject'); ?>", $(this).data('id')); });

		$(document).on('click', '.rv-reply', function () {
			var id = $(this).data('id');
			var current = $(this).data('reply') || '';
			var text = window.prompt('<?php echo translate('reply_to_review') ?: 'Reply to this review'; ?>:', current);
			if (text === null) { return; }
			$.ajax({
				type: 'POST', url: "<?php echo base_url('review/reply'); ?>", data: { id: id, reply: text }, dataType: 'json',
				success: function (res) {
					if (res && res.status === 'success') {
						swal({ toast: true, position: 'top-end', type: 'success', title: res.message, timer: 1200, showConfirmButton: false });
						table.ajax.reload(null, false);
					} else if (res && res.message) {
						swal({ type: 'error', title: res.message });
					}
				}
			});
		});
	});
</script>
