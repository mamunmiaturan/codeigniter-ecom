<section class="panel">
	<header class="panel-heading">
		<h2 class="panel-title"><i class="fas fa-users"></i> <?php echo translate('customers') ?: 'Customers'; ?>
			<span class="badge badge-secondary"><?php echo count($customers); ?></span>
		</h2>
	</header>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-bordered table-hover table-condensed" id="customer-table" width="100%">
				<thead>
					<tr>
						<th><?php echo translate('sl') ?: 'SL'; ?></th>
						<th><?php echo translate('name'); ?></th>
						<th><?php echo translate('email'); ?></th>
						<th><?php echo translate('mobile_no') ?: 'Mobile'; ?></th>
						<th class="text-center"><?php echo translate('orders') ?: 'Orders'; ?></th>
						<th class="text-end"><?php echo translate('total_spent') ?: 'Total Spent'; ?></th>
						<th><?php echo translate('joined') ?: 'Joined'; ?></th>
						<th class="text-center"><?php echo translate('status'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php $i = 1; foreach ($customers as $c): ?>
						<tr>
							<td><?php echo $i++; ?></td>
							<td>
								<strong><?php echo html_escape($c->name); ?></strong>
								<div class="text-muted" style="font-size:12px;"><?php echo html_escape($c->user_id); ?></div>
							</td>
							<td><?php echo html_escape($c->email); ?></td>
							<td><?php echo html_escape($c->mobile_no); ?></td>
							<td class="text-center"><span class="badge badge-info"><?php echo (int) $c->order_count; ?></span></td>
							<td class="text-end"><?php echo html_escape(get_global_setting('currency_symbol') ?: '৳') . ' ' . number_format((float) $c->total_spent, 2); ?></td>
							<td><?php echo $c->created_at ? time_ago($c->created_at) : '-'; ?></td>
							<td class="text-center">
								<span class="badge <?php echo $c->status === 'Active' ? 'badge-success' : 'badge-secondary'; ?>"><?php echo html_escape($c->status); ?></span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</section>

<script type="text/javascript">
	$(document).ready(function () {
		if ($.fn.DataTable) {
			$('#customer-table').DataTable({
				"pageLength": 25,
				"order": [[0, "asc"]],
				"autoWidth": false,
				"columnDefs": [{ "targets": [0, 4, 5, 7], "orderable": true }],
				"language": { "search": "_INPUT_", "searchPlaceholder": "Search customers..." }
			});
		}
	});
</script>
