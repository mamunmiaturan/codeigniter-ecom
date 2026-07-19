<section class="panel">
    <header class="panel-heading">
        <div class="panel-actions">
            <a href="<?php echo base_url('inventory_source/low_stock'); ?>" class="btn btn-warning btn-sm"><i class="fas fa-exclamation-triangle"></i> <?php echo translate('low_stock') ?: 'Low Stock'; ?></a>
            <a href="<?php echo base_url('inventory_source/movements'); ?>" class="btn btn-default btn-sm"><i class="fas fa-list"></i> <?php echo translate('movements') ?: 'Movements'; ?></a>
        </div>
        <h2 class="panel-title"><i class="fas fa-warehouse"></i> <?php echo translate('warehouse_stock_report') ?: 'Warehouse Stock Report'; ?></h2>
    </header>
    <div class="panel-body">
        <table class="table table-bordered table-hover">
            <thead>
                <tr><th><?php echo translate('warehouse') ?: 'Warehouse'; ?></th><th>Code</th><th><?php echo translate('status'); ?></th><th class="text-right"><?php echo translate('on_hand') ?: 'On Hand'; ?></th></tr>
            </thead>
            <tbody>
                <?php $grand = 0; foreach ($by_source as $s): $grand += (int) $s['on_hand']; ?>
                    <tr>
                        <td><strong><?php echo html_escape($s['name']); ?></strong></td>
                        <td><code><?php echo html_escape($s['code']); ?></code></td>
                        <td><span class="badge badge-<?php echo $s['status'] === 'Active' ? 'success' : 'secondary'; ?>"><?php echo html_escape($s['status']); ?></span></td>
                        <td class="text-right"><?php echo number_format((int) $s['on_hand']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($by_source)): ?>
                    <tr><td colspan="4" class="text-center text-muted">No warehouses configured.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr><th colspan="3" class="text-right"><?php echo translate('total_on_hand') ?: 'Total on hand'; ?></th><th class="text-right"><?php echo number_format($grand); ?></th></tr>
            </tfoot>
        </table>
    </div>
</section>
