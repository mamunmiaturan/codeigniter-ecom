<section class="panel">
    <header class="panel-heading">
        <div class="panel-actions">
            <a href="<?php echo base_url('inventory_source/report'); ?>" class="btn btn-default btn-sm"><i class="fas fa-warehouse"></i> <?php echo translate('warehouse_report') ?: 'Warehouse Report'; ?></a>
        </div>
        <h2 class="panel-title"><i class="fas fa-exclamation-triangle"></i> <?php echo translate('low_stock_products') ?: 'Low Stock Products'; ?></h2>
    </header>
    <div class="panel-body">
        <form method="get" action="<?php echo base_url('inventory_source/low_stock'); ?>" class="form-inline mb-md">
            <label class="control-label"><?php echo translate('threshold') ?: 'Threshold (at or below)'; ?>&nbsp;</label>
            <input type="number" name="threshold" min="0" value="<?php echo (int) $threshold; ?>" class="form-control" style="width:110px">
            <button class="btn btn-primary btn-sm"><?php echo translate('apply') ?: 'Apply'; ?></button>
        </form>
        <table class="table table-bordered table-hover table-condensed">
            <thead>
                <tr><th>SL</th><th><?php echo translate('product') ?: 'Product'; ?></th><th>SKU</th><th class="text-center"><?php echo translate('on_hand') ?: 'On Hand'; ?></th><th><?php echo translate('status'); ?></th></tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="5" class="text-center text-muted">No products at or below <?php echo (int) $threshold; ?> units.</td></tr>
                <?php else: $i = 1; foreach ($items as $p): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo html_escape($p['name']); ?></td>
                        <td><small class="text-muted"><?php echo html_escape($p['sku'] ?: '—'); ?></small></td>
                        <td class="text-center"><span class="badge badge-<?php echo ((int) $p['stock_quantity'] <= 0) ? 'danger' : 'warning'; ?>"><?php echo (int) $p['stock_quantity']; ?></span></td>
                        <td><?php echo html_escape(str_replace('_', ' ', $p['stock_status'])); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>
