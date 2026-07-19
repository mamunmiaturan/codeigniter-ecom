<?php
$sym = html_escape($currency ?? '৳');
$qs  = http_build_query(['from' => $from, 'to' => $to]);
?>
<section class="panel">
    <header class="panel-heading">
        <div class="panel-actions">
            <a href="<?php echo base_url('report/export/products?' . $qs); ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-file-csv"></i> <?php echo translate('export_csv') ?: 'Export CSV'; ?>
            </a>
            <a href="<?php echo base_url('report'); ?>" class="btn btn-default btn-sm">
                <i class="fas fa-arrow-left"></i> <?php echo translate('back') ?: 'Back'; ?>
            </a>
        </div>
        <h2 class="panel-title"><i class="fas fa-boxes"></i> <?php echo translate('products_report') ?: 'Products Report'; ?></h2>
    </header>
    <div class="panel-body">
        <form method="get" action="<?php echo base_url('report/products'); ?>" class="form-inline" style="margin-bottom:20px;">
            <div class="form-group" style="margin-right:10px;">
                <label style="margin-right:5px;"><?php echo translate('from') ?: 'From'; ?></label>
                <input type="date" name="from" value="<?php echo html_escape($from); ?>" class="form-control input-sm">
            </div>
            <div class="form-group" style="margin-right:10px;">
                <label style="margin-right:5px;"><?php echo translate('to') ?: 'To'; ?></label>
                <input type="date" name="to" value="<?php echo html_escape($to); ?>" class="form-control input-sm">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> <?php echo translate('apply') ?: 'Apply'; ?></button>
        </form>

        <table class="table table-bordered table-hover table-condensed" width="100%">
            <thead>
                <tr>
                    <th><?php echo translate('sl') ?: 'SL'; ?></th>
                    <th><?php echo translate('product') ?: 'Product'; ?></th>
                    <th class="text-center"><?php echo translate('units_sold') ?: 'Units Sold'; ?></th>
                    <th class="text-right"><?php echo translate('revenue') ?: 'Revenue'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="4" class="text-center text-muted" style="padding:20px;"><?php echo translate('no_data_found') ?: 'No data for this range.'; ?></td></tr>
                <?php else: $i = 1; foreach ($rows as $r): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo html_escape($r['product_name']); ?></td>
                        <td class="text-center"><span class="badge badge-primary"><?php echo number_format((int) $r['units']); ?></span></td>
                        <td class="text-right"><?php echo $sym; ?> <?php echo number_format((float) $r['revenue'], 2); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>
