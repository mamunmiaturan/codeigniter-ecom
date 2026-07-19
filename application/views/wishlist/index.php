<section class="panel">
	<header class="panel-heading">
		<h2 class="panel-title"><i class="fas fa-heart"></i> <?php echo translate('wishlist') ?: 'Wishlist'; ?>
			<span class="badge badge-secondary"><?php echo count($items); ?></span>
		</h2>
	</header>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-bordered table-hover table-condensed" id="wishlist-table" width="100%">
				<thead>
					<tr>
						<th><?php echo translate('sl') ?: 'SL'; ?></th>
						<th><?php echo translate('customer') ?: 'Customer'; ?></th>
						<th><?php echo translate('phone') ?: 'Phone'; ?></th>
						<th><?php echo translate('email'); ?></th>
						<th><?php echo translate('product') ?: 'Product'; ?></th>
						<th class="text-end"><?php echo translate('price'); ?></th>
						<th class="text-center"><?php echo translate('stock') ?: 'Stock'; ?></th>
						<th><?php echo translate('date') ?: 'Date'; ?></th>
					</tr>
				</thead>
				<tbody>
					<?php $i = 1; $sym = html_escape(get_global_setting('currency_symbol') ?: '৳'); foreach ($items as $it): ?>
						<tr>
							<td><?php echo $i++; ?></td>
							<td><strong><?php echo html_escape($it->customer_name); ?></strong></td>
							<td><?php echo html_escape($it->phone); ?></td>
							<td><?php echo html_escape($it->email); ?></td>
							<td>
								<?php if (!empty($it->thumbnail)): ?>
									<img src="<?php echo base_url('uploads/catalog/product/' . rawurlencode($it->thumbnail)); ?>" width="30" height="30" style="object-fit:cover;border-radius:5px;vertical-align:middle;margin-right:7px;">
								<?php endif; ?>
								<a href="<?php echo base_url('product/edit/' . encrypt_id($it->product_id)); ?>"><?php echo html_escape($it->product_name); ?></a>
							</td>
							<td class="text-end"><?php echo $sym . ' ' . number_format((float) $it->price, 2); ?></td>
							<td class="text-center">
								<span class="badge <?php echo ((int) $it->stock_quantity > 0 && $it->stock_status !== 'out_of_stock') ? 'badge-success' : 'badge-secondary'; ?>"><?php echo (int) $it->stock_quantity; ?></span>
							</td>
							<td><?php echo $it->created_at ? time_ago($it->created_at) : '-'; ?></td>
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
			$('#wishlist-table').DataTable({
				"pageLength": 25, "order": [[0, "asc"]], "autoWidth": false,
				"language": { "search": "_INPUT_", "searchPlaceholder": "Search customer or product..." }
			});
		}
	});
</script>
