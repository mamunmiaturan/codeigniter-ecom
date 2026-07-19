<?php
$sym = get_global_setting('currency_symbol') ?: '৳';
// Inline status badge helper (defined before first use).
if (!function_exists('status_badge_html')) {
	function status_badge_html($status)
	{
		$map = [
			'pending' => 'warning', 'confirmed' => 'info', 'processing' => 'primary',
			'shipped' => 'primary', 'delivered' => 'success', 'completed' => 'success',
			'cancelled' => 'danger', 'returned' => 'danger',
		];
		$cls = $map[$status] ?? 'secondary';
		return '<span class="badge badge-' . $cls . '">' . ucfirst($status) . '</span>';
	}
}
?>
<div class="row">
	<div class="col-md-8">
		<section class="panel">
			<header class="panel-heading">
				<h2 class="panel-title" style="display:flex;align-items:center;justify-content:space-between;">
					<span><i class="fas fa-shopping-bag"></i> <?php echo translate('order') ?: 'Order'; ?> #<?php echo html_escape($order['order_number']); ?></span>
					<span><?php echo status_badge_html($order['status']); ?></span>
				</h2>
			</header>
			<div class="panel-body">
				<div class="row">
					<div class="col-md-6">
						<p><strong><?php echo translate('placed_at') ?: 'Placed'; ?>:</strong> <?php echo date('d M Y, h:i A', strtotime($order['placed_at'])); ?></p>
						<p><strong><?php echo translate('payment') ?: 'Payment'; ?>:</strong>
							<span class="badge badge-<?php echo $order['payment_method'] === 'cod' ? 'secondary' : 'info'; ?>"><?php echo strtoupper($order['payment_method']); ?></span>
							<span class="badge badge-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>"><?php echo ucfirst($order['payment_status']); ?></span>
						</p>
					</div>
					<div class="col-md-6">
						<p><strong><?php echo translate('customer') ?: 'Customer'; ?>:</strong> <?php echo html_escape($order['customer_name']); ?></p>
						<p><strong><?php echo translate('phone') ?: 'Phone'; ?>:</strong> <?php echo html_escape($order['customer_phone']); ?>
							<?php if (!empty($order['customer_email'])): ?> · <?php echo html_escape($order['customer_email']); ?><?php endif; ?></p>
					</div>
				</div>
				<div class="alert alert-light" style="border:1px solid #eee;">
					<strong><i class="fas fa-map-marker-alt"></i> <?php echo translate('shipping_address') ?: 'Shipping Address'; ?>:</strong><br>
					<?php
					$addr = array_filter([
						$order['shipping_address'],
						$order['shipping_area'],
						$order['shipping_district'],
						$order['shipping_division'],
						$order['shipping_postcode'],
					]);
					echo html_escape(implode(', ', $addr));
					if (!empty($order['shipping_landmark'])) echo '<br><small class="text-muted">' . translate('landmark') . ': ' . html_escape($order['shipping_landmark']) . '</small>';
					?>
					<?php if (!empty($order['note'])): ?><br><small class="text-muted"><?php echo translate('note') ?: 'Note'; ?>: <?php echo html_escape($order['note']); ?></small><?php endif; ?>
				</div>

				<table class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th><?php echo translate('product') ?: 'Product'; ?></th>
							<th class="text-center"><?php echo translate('unit_price') ?: 'Unit Price'; ?></th>
							<th class="text-center"><?php echo translate('qty') ?: 'Qty'; ?></th>
							<th class="text-right"><?php echo translate('total') ?: 'Total'; ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($items as $it): ?>
							<tr>
								<td>
									<?php if (!empty($it['thumbnail'])): ?>
										<img src="<?php echo base_url('uploads/catalog/product/' . $it['thumbnail']); ?>" width="32" height="32" class="rounded" style="vertical-align:middle;margin-right:6px;">
									<?php endif; ?>
									<?php echo html_escape($it['product_name']); ?>
									<?php if (!empty($it['variant_name'])): ?><small class="text-muted">(<?php echo html_escape($it['variant_name']); ?>)</small><?php endif; ?>
									<?php if (!empty($it['sku'])): ?><br><small class="text-muted">SKU: <?php echo html_escape($it['sku']); ?></small><?php endif; ?>
								</td>
								<td class="text-center"><?php echo html_escape($sym) . ' ' . number_format((float) $it['unit_price'], 2); ?></td>
								<td class="text-center"><?php echo (int) $it['quantity']; ?></td>
								<td class="text-right"><?php echo html_escape($sym) . ' ' . number_format((float) $it['line_total'], 2); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
					<tfoot>
						<tr><td colspan="3" class="text-right"><?php echo translate('subtotal') ?: 'Subtotal'; ?></td><td class="text-right"><?php echo html_escape($sym) . ' ' . number_format((float) $order['subtotal'], 2); ?></td></tr>
						<tr><td colspan="3" class="text-right"><?php echo translate('shipping') ?: 'Shipping'; ?></td><td class="text-right"><?php echo html_escape($sym) . ' ' . number_format((float) $order['shipping_charge'], 2); ?></td></tr>
						<?php if ((float) $order['discount'] > 0 || !empty($order['coupon_code'])): ?><tr><td colspan="3" class="text-right"><?php echo translate('discount') ?: 'Discount'; ?><?php if (!empty($order['coupon_code'])) echo ' <span class="badge badge-success">' . html_escape($order['coupon_code']) . '</span>'; ?></td><td class="text-right"><?php echo (float) $order['discount'] > 0 ? '- ' . html_escape($sym) . ' ' . number_format((float) $order['discount'], 2) : '—'; ?></td></tr><?php endif; ?>
						<tr><td colspan="3" class="text-right"><strong><?php echo translate('grand_total') ?: 'Grand Total'; ?></strong></td><td class="text-right"><strong><?php echo html_escape($sym) . ' ' . number_format((float) $order['total'], 2); ?></strong></td></tr>
					</tfoot>
				</table>
			</div>
		</section>
	</div>

	<div class="col-md-4">
		<?php if (get_permission('order', 'is_edit')): ?>
			<section class="panel">
				<header class="panel-heading"><h2 class="panel-title"><i class="fas fa-sync-alt"></i> <?php echo translate('update_status') ?: 'Update Status'; ?></h2></header>
				<div class="panel-body">
					<?php echo form_open(base_url('order/update_status')); ?>
					<input type="hidden" name="order_id" value="<?php echo encrypt_id($order['id']); ?>">
					<div class="form-group">
						<label class="control-label"><?php echo translate('status'); ?></label>
						<?php
						$opts = [];
						foreach ($statuses as $s) { $opts[$s] = ucfirst($s); }
						echo form_dropdown('status', $opts, $order['status'], "class='form-control' data-plugin-selectTwo data-width='100%'");
						?>
					</div>
					<div class="form-group">
						<label class="control-label"><?php echo translate('note') ?: 'Note'; ?></label>
						<input type="text" class="form-control" name="note" placeholder="<?php echo translate('optional') ?: 'Optional'; ?>">
					</div>
					<button type="submit" class="btn btn-success btn-block"><i class="fas fa-check"></i> <?php echo translate('update') ?: 'Update'; ?></button>
					<?php echo form_close(); ?>
				</div>
			</section>
		<?php endif; ?>

		<section class="panel">
			<header class="panel-heading"><h2 class="panel-title"><i class="fas fa-file-invoice-dollar"></i> <?php echo translate('invoice') ?: 'Invoice'; ?></h2></header>
			<div class="panel-body">
				<?php if (!empty($invoice)): ?>
					<p class="mb-sm"><strong><?php echo html_escape($invoice['invoice_number']); ?></strong></p>
					<a href="<?php echo base_url('order/print_invoice/' . encrypt_id($order['id'])); ?>" target="_blank" class="btn btn-default btn-sm btn-block"><i class="fas fa-print"></i> <?php echo translate('print_invoice') ?: 'Print Invoice'; ?></a>
				<?php else: ?>
					<a href="<?php echo base_url('order/generate_invoice/' . encrypt_id($order['id'])); ?>" class="btn btn-primary btn-sm btn-block"><i class="fas fa-file-invoice"></i> <?php echo translate('generate_invoice') ?: 'Generate Invoice'; ?></a>
				<?php endif; ?>
				<a href="<?php echo base_url('order/print_packing_slip/' . encrypt_id($order['id'])); ?>" target="_blank" class="btn btn-default btn-sm btn-block mt-xs"><i class="fas fa-box-open"></i> <?php echo translate('packing_slip') ?: 'Packing Slip'; ?></a>
				<a href="<?php echo base_url('order/print_shipping_label/' . encrypt_id($order['id'])); ?>" target="_blank" class="btn btn-default btn-sm btn-block mt-xs"><i class="fas fa-tag"></i> <?php echo translate('shipping_label') ?: 'Shipping Label'; ?></a>
			</div>
		</section>

		<?php if (get_permission('order', 'is_edit')): ?>
		<section class="panel">
			<header class="panel-heading"><h2 class="panel-title"><i class="fas fa-truck"></i> <?php echo translate('shipment') ?: 'Shipment'; ?></h2></header>
			<div class="panel-body">
				<?php foreach ($shipments as $sh): ?>
					<div style="padding-bottom:6px;margin-bottom:6px;border-bottom:1px solid #f2f2f2;">
						<strong><?php echo html_escape($sh['carrier']); ?></strong>
						<?php if (!empty($sh['tracking_number'])): ?><br><small><?php echo translate('tracking') ?: 'Tracking'; ?>: <?php echo html_escape($sh['tracking_number']); ?></small><?php endif; ?>
						<br><small class="text-muted"><?php echo time_ago($sh['created_at']); ?></small>
					</div>
				<?php endforeach; ?>
				<?php echo form_open(base_url('order/add_shipment')); ?>
					<input type="hidden" name="order_id" value="<?php echo encrypt_id($order['id']); ?>">
					<div class="form-group"><input class="form-control input-sm" name="carrier" placeholder="<?php echo translate('carrier') ?: 'Carrier (e.g. Pathao, SteadFast)'; ?>" required></div>
					<div class="form-group"><input class="form-control input-sm" name="tracking_number" placeholder="<?php echo translate('tracking_number') ?: 'Tracking number'; ?>"></div>
					<div class="form-group"><input class="form-control input-sm" name="tracking_url" placeholder="<?php echo translate('tracking_url') ?: 'Tracking URL (optional)'; ?>"></div>
					<button class="btn btn-success btn-sm btn-block" type="submit"><i class="fas fa-plus"></i> <?php echo translate('add_shipment') ?: 'Add Shipment'; ?></button>
				<?php echo form_close(); ?>
			</div>
		</section>

		<section class="panel">
			<header class="panel-heading"><h2 class="panel-title"><i class="fas fa-undo"></i> <?php echo translate('refund') ?: 'Refund'; ?></h2></header>
			<div class="panel-body">
				<?php $refunded = 0.0; foreach ($refunds as $rf) { $refunded += (float) $rf['amount']; } ?>
				<?php if (!empty($refunds)): ?>
					<p class="mb-sm text-danger"><?php echo translate('refunded') ?: 'Refunded'; ?>: <?php echo html_escape($sym) . ' ' . number_format($refunded, 2); ?> / <?php echo html_escape($sym) . ' ' . number_format((float) $order['total'], 2); ?></p>
					<?php foreach ($refunds as $rf): ?><small class="text-muted" style="display:block;"><?php echo html_escape($sym) . ' ' . number_format((float) $rf['amount'], 2); ?> · <?php echo html_escape($rf['reason'] ?: '—'); ?> · <?php echo time_ago($rf['created_at']); ?></small><?php endforeach; ?>
					<hr style="margin:8px 0;">
				<?php endif; ?>
				<?php echo form_open(base_url('order/add_refund')); ?>
					<input type="hidden" name="order_id" value="<?php echo encrypt_id($order['id']); ?>">
					<div class="form-group"><input type="number" step="0.01" min="0" class="form-control input-sm" name="amount" placeholder="<?php echo translate('amount') ?: 'Amount'; ?>"></div>
					<div class="form-group"><input class="form-control input-sm" name="reason" placeholder="<?php echo translate('reason') ?: 'Reason'; ?>"></div>
					<button class="btn btn-warning btn-sm btn-block" type="submit"><i class="fas fa-undo"></i> <?php echo translate('record_refund') ?: 'Record Refund'; ?></button>
				<?php echo form_close(); ?>
			</div>
		</section>
		<?php endif; ?>

		<?php if (!empty($returns)): ?>
		<section class="panel">
			<header class="panel-heading"><h2 class="panel-title"><i class="fas fa-box-open"></i> <?php echo translate('returns') ?: 'Returns'; ?></h2></header>
			<div class="panel-body">
				<?php foreach ($returns as $rt): ?>
					<div style="padding:4px 0;">
						<a href="<?php echo base_url('rma/view/' . encrypt_id($rt['id'])); ?>"><strong><?php echo html_escape($rt['rma_number']); ?></strong></a>
						<span class="badge badge-info"><?php echo ucfirst($rt['status']); ?></span>
						<small class="text-muted pull-right"><?php echo time_ago($rt['created_at']); ?></small>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endif; ?>

		<section class="panel">
			<header class="panel-heading"><h2 class="panel-title"><i class="fas fa-history"></i> <?php echo translate('timeline') ?: 'Timeline'; ?></h2></header>
			<div class="panel-body">
				<ul class="list-unstyled">
					<?php foreach (array_reverse($history) as $h): ?>
						<li style="padding:6px 0;border-bottom:1px solid #f2f2f2;">
							<?php echo status_badge_html($h['status']); ?>
							<small class="text-muted pull-right"><?php echo time_ago($h['created_at']); ?></small>
							<?php if (!empty($h['note'])): ?><br><small><?php echo html_escape($h['note']); ?></small><?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
		<a href="<?php echo base_url('order'); ?>" class="btn btn-default btn-block"><i class="fas fa-arrow-left"></i> <?php echo translate('back_to_list') ?: 'Back to Orders'; ?></a>
	</div>
</div>
