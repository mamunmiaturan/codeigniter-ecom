<?php
$sym = html_escape($currency ?? '৳');
$stock_badge = function ($qty) {
    return ((int) $qty <= 0) ? 'danger' : 'warning';
};
?>
<section class="panel">
    <header class="panel-heading">
        <div class="panel-actions">
            <a href="<?php echo base_url('report/export/inventory'); ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-file-csv"></i> <?php echo translate('export_csv') ?: 'Export CSV'; ?>
            </a>
            <a href="<?php echo base_url('report'); ?>" class="btn btn-default btn-sm">
                <i class="fas fa-arrow-left"></i> <?php echo translate('back') ?: 'Back'; ?>
            </a>
        </div>
        <h2 class="panel-title"><i class="fas fa-warehouse"></i> <?php echo translate('inventory_report') ?: 'Inventory Report'; ?></h2>
    </header>
    <div class="panel-body">
        <!-- Inventory is a point-in-time snapshot: filter the low-stock list by threshold. -->
        <form method="get" action="<?php echo base_url('report/inventory'); ?>" class="form-inline" style="margin-bottom:20px;">
            <div class="form-group" style="margin-right:10px;">
                <label style="margin-right:5px;"><?php echo translate('low_stock_threshold') ?: 'Low Stock Threshold'; ?></label>
                <input type="number" name="threshold" min="0" value="<?php echo (int) $threshold; ?>" class="form-control input-sm" style="width:90px;">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> <?php echo translate('apply') ?: 'Apply'; ?></button>
        </form>

        <div class="row">
            <div class="col-sm-4 col-xs-12">
                <div class="stat-card-custom">
                    <div class="stat-icon-box bg-custom-green"><i class="fas fa-coins"></i></div>
                    <div class="stat-content-box">
                        <span class="stat-count-text"><?php echo $sym; ?> <?php echo number_format((float) $valuation_total, 0); ?></span>
                        <span class="stat-label-text"><?php echo translate('inventory_valuation') ?: 'Inventory Valuation'; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-4 col-xs-12">
                <div class="stat-card-custom">
                    <div class="stat-icon-box bg-custom-red"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-content-box">
                        <span class="stat-count-text"><?php echo number_format(count($low_stock)); ?></span>
                        <span class="stat-label-text"><?php echo translate('low_stock_products') ?: 'Low Stock Products'; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low stock -->
        <h4 style="font-size:14px; font-weight:700;"><i class="fas fa-exclamation-triangle text-warning"></i> <?php echo translate('low_stock_products') ?: 'Low Stock Products'; ?> (&le; <?php echo (int) $threshold; ?>)</h4>
        <table class="table table-bordered table-hover table-condensed" width="100%">
            <thead>
                <tr>
                    <th><?php echo translate('product') ?: 'Product'; ?></th>
                    <th><?php echo translate('sku') ?: 'SKU'; ?></th>
                    <th class="text-center"><?php echo translate('on_hand') ?: 'On Hand'; ?></th>
                    <th class="text-right"><?php echo translate('price') ?: 'Price'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($low_stock)): ?>
                    <tr><td colspan="4" class="text-center text-muted" style="padding:20px;"><?php echo translate('all_products_well_stocked') ?: 'All products are well stocked.'; ?></td></tr>
                <?php else: foreach ($low_stock as $r): ?>
                    <tr>
                        <td><?php echo html_escape($r['name']); ?></td>
                        <td><small class="text-muted"><?php echo html_escape($r['sku'] ?: '—'); ?></small></td>
                        <td class="text-center"><span class="badge badge-<?php echo $stock_badge($r['stock_quantity']); ?>"><?php echo (int) $r['stock_quantity']; ?></span></td>
                        <td class="text-right"><?php echo $sym; ?> <?php echo number_format((float) $r['price'], 2); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <!-- Valuation -->
        <h4 style="font-size:14px; font-weight:700; margin-top:20px;"><i class="fas fa-coins text-success"></i> <?php echo translate('inventory_valuation') ?: 'Inventory Valuation'; ?></h4>
        <table class="table table-bordered table-hover table-condensed" width="100%">
            <thead>
                <tr>
                    <th><?php echo translate('product') ?: 'Product'; ?></th>
                    <th><?php echo translate('sku') ?: 'SKU'; ?></th>
                    <th class="text-center"><?php echo translate('stock') ?: 'Stock'; ?></th>
                    <th class="text-right"><?php echo translate('price') ?: 'Price'; ?></th>
                    <th class="text-right"><?php echo translate('stock_value') ?: 'Stock Value'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($valuation)): ?>
                    <tr><td colspan="5" class="text-center text-muted" style="padding:20px;"><?php echo translate('no_data_found') ?: 'No data.'; ?></td></tr>
                <?php else: foreach ($valuation as $r): ?>
                    <tr>
                        <td><?php echo html_escape($r['name']); ?></td>
                        <td><small class="text-muted"><?php echo html_escape($r['sku'] ?: '—'); ?></small></td>
                        <td class="text-center"><?php echo (int) $r['stock_quantity']; ?></td>
                        <td class="text-right"><?php echo $sym; ?> <?php echo number_format((float) $r['price'], 2); ?></td>
                        <td class="text-right"><?php echo $sym; ?> <?php echo number_format((float) $r['stock_value'], 2); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <?php if (!empty($valuation)): ?>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-right"><?php echo translate('grand_total') ?: 'Grand Total'; ?></th>
                        <th class="text-right"><?php echo $sym; ?> <?php echo number_format((float) $valuation_total, 2); ?></th>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
</section>
