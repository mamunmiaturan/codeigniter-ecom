<section class="panel">
	<header class="panel-heading">
		<div class="panel-actions">
			<?php if (get_permission('attribute_family', 'is_add')): ?>
				<a href="<?php echo base_url('attribute_family/create'); ?>" class="btn btn-primary btn-sm">
					<i class="fas fa-plus-circle"></i> <?php echo translate('add_attribute_family') ?: 'Add Attribute Family'; ?>
				</a>
			<?php endif; ?>
		</div>
		<h2 class="panel-title"><i class="fas fa-sitemap"></i> <?php echo translate('attribute_families') ?: 'Attribute Families'; ?></h2>
	</header>
	<div class="panel-body">
		<table class="table table-bordered table-hover table-condensed" cellspacing="0" width="100%" id="family-table">
			<thead>
				<tr>
					<th><?php echo translate('sl') ?: 'SL'; ?></th>
					<th><?php echo translate('name'); ?></th>
					<th><?php echo translate('groups') ?: 'Groups'; ?></th>
					<th><?php echo translate('products') ?: 'Products'; ?></th>
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

		var table = $('#family-table').DataTable({
			"processing": true,
			"serverSide": true,
			"ordering": true,
			"order": [[1, "asc"]],
			"pageLength": 25,
			"autoWidth": false,
			"columns": [
				{ "data": 0 }, { "data": 1 }, { "data": 2 }, { "data": 3 },
				{ "data": 4 }, { "data": 5 }
			],
			"columnDefs": [
				{ "targets": [0, 2, 3, 5], "orderable": false },
				{ "targets": [2, 3, 4, 5], "className": "text-center" }
			],
			"ajax": {
				"url": "<?php echo base_url('attribute_family/get_families_server_side'); ?>",
				"type": "POST",
				"data": function (d) {
					d[csrfName] = csrfHash;
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

		$(document).on('click', '.btn-status-toggle', function () {
			var btn = $(this);
			$.ajax({
				type: 'POST',
				url: "<?php echo base_url('attribute_family/status'); ?>",
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
