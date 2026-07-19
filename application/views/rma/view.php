<?php
$badges = ['requested' => 'warning', 'approved' => 'info', 'rejected' => 'danger', 'received' => 'primary', 'refunded' => 'success', 'cancelled' => 'secondary'];
$sb = $badges[$req['status']] ?? 'secondary';
?>
<div class="row">
	<div class="col-md-8">
		<section class="panel">
			<header class="panel-heading">
				<h2 class="panel-title" style="display:flex;align-items:center;justify-content:space-between;">
					<span><i class="fas fa-box-open"></i> RMA #<?php echo html_escape($req['rma_number']); ?></span>
					<span class="badge badge-<?php echo $sb; ?>"><?php echo ucfirst($req['status']); ?></span>
				</h2>
			</header>
			<div class="panel-body">
				<div class="row">
					<div class="col-md-6">
						<p><strong><?php echo translate('order') ?: 'Order'; ?>:</strong>
							<?php if (!empty($order)): ?><a href="<?php echo base_url('order/view/' . encrypt_id($order['id'])); ?>"><?php echo html_escape($order['order_number']); ?></a><?php else: ?>—<?php endif; ?>
						</p>
						<p><strong><?php echo translate('requested_at') ?: 'Requested'; ?>:</strong> <?php echo time_ago($req['created_at']); ?></p>
					</div>
					<div class="col-md-6">
						<p><strong><?php echo translate('reason') ?: 'Reason'; ?>:</strong> <?php echo html_escape($req['reason'] ?: '—'); ?></p>
						<?php if (!empty($order)): ?><p><strong><?php echo translate('customer') ?: 'Customer'; ?>:</strong> <?php echo html_escape($order['customer_name']); ?></p><?php endif; ?>
					</div>
				</div>
				<?php if (!empty($req['customer_note'])): ?>
					<div class="alert alert-light" style="border:1px solid #eee;"><strong><?php echo translate('customer_note') ?: 'Customer note'; ?>:</strong><br><?php echo nl2br(html_escape($req['customer_note'])); ?></div>
				<?php endif; ?>

				<table class="table table-bordered table-condensed">
					<thead><tr><th><?php echo translate('product') ?: 'Product'; ?></th><th class="text-center"><?php echo translate('qty') ?: 'Qty'; ?></th></tr></thead>
					<tbody>
						<?php foreach ($ritems as $it): ?>
							<tr><td><?php echo html_escape($it['product_name']); ?></td><td class="text-center"><?php echo (int) $it['quantity']; ?></td></tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php if (!empty($req['admin_note'])): ?><p class="text-muted"><strong><?php echo translate('admin_note') ?: 'Admin note'; ?>:</strong> <?php echo html_escape($req['admin_note']); ?></p><?php endif; ?>
			</div>
		</section>
	</div>

	<div class="col-md-4">
		<?php if (get_permission('rma', 'is_edit')): ?>
			<section class="panel">
				<header class="panel-heading"><h2 class="panel-title"><i class="fas fa-sync-alt"></i> <?php echo translate('update_status') ?: 'Update Status'; ?></h2></header>
				<div class="panel-body">
					<?php echo form_open(base_url('rma/update_status')); ?>
					<input type="hidden" name="id" value="<?php echo encrypt_id($req['id']); ?>">
					<div class="form-group">
						<label class="control-label"><?php echo translate('status'); ?></label>
						<?php $opts = []; foreach ($statuses as $s) { $opts[$s] = ucfirst($s); }
						echo form_dropdown('status', $opts, $req['status'], "class='form-control' data-plugin-selectTwo data-width='100%'"); ?>
					</div>
					<div class="form-group">
						<label class="control-label"><?php echo translate('admin_note') ?: 'Admin note'; ?></label>
						<textarea class="form-control" name="admin_note" rows="3"><?php echo html_escape($req['admin_note'] ?? ''); ?></textarea>
					</div>
					<button type="submit" class="btn btn-success btn-block"><i class="fas fa-check"></i> <?php echo translate('update') ?: 'Update'; ?></button>
					<?php echo form_close(); ?>
				</div>
			</section>
		<?php endif; ?>
		<a href="<?php echo base_url('rma'); ?>" class="btn btn-default btn-block"><i class="fas fa-arrow-left"></i> <?php echo translate('back_to_list') ?: 'Back to Returns'; ?></a>
	</div>
</div>
