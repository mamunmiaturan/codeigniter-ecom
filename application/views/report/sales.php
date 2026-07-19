<?php
$sym = html_escape($currency ?? '৳');
$qs  = http_build_query(['from' => $from, 'to' => $to]);
$s   = $summary;
$status_class = function ($st) {
    $m = [
        'pending' => 'warning', 'confirmed' => 'info', 'processing' => 'primary',
        'shipped' => 'primary', 'delivered' => 'success', 'completed' => 'success',
        'cancelled' => 'danger', 'returned' => 'danger',
        'paid' => 'success', 'failed' => 'danger', 'refunded' => 'secondary',
    ];
    return $m[$st] ?? 'secondary';
};
?>
<section class="panel">
    <header class="panel-heading">
        <div class="panel-actions">
            <a href="<?php echo base_url('report/export/sales?' . $qs); ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-file-csv"></i> <?php echo translate('export_csv') ?: 'Export CSV'; ?>
            </a>
            <a href="<?php echo base_url('report'); ?>" class="btn btn-default btn-sm">
                <i class="fas fa-arrow-left"></i> <?php echo translate('back') ?: 'Back'; ?>
            </a>
        </div>
        <h2 class="panel-title"><i class="fas fa-chart-line"></i> <?php echo translate('sales_report') ?: 'Sales Report'; ?></h2>
    </header>
    <div class="panel-body">
        <form method="get" action="<?php echo base_url('report/sales'); ?>" class="form-inline" style="margin-bottom:20px;">
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

        <!-- Summary -->
        <div class="row">
            <div class="col-sm-3 col-xs-6">
                <div class="stat-card-custom">
                    <div class="stat-icon-box bg-custom-green"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="stat-content-box">
                        <span class="stat-count-text"><?php echo $sym; ?> <?php echo number_format((float) $s['revenue'], 0); ?></span>
                        <span class="stat-label-text"><?php echo translate('revenue') ?: 'Revenue'; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-3 col-xs-6">
                <div class="stat-card-custom">
                    <div class="stat-icon-box bg-custom-blue"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-content-box">
                        <span class="stat-count-text"><?php echo number_format((int) $s['orders']); ?></span>
                        <span class="stat-label-text"><?php echo translate('orders') ?: 'Orders'; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-3 col-xs-6">
                <div class="stat-card-custom">
                    <div class="stat-icon-box bg-custom-orange"><i class="fas fa-cubes"></i></div>
                    <div class="stat-content-box">
                        <span class="stat-count-text"><?php echo number_format((int) $s['items_sold']); ?></span>
                        <span class="stat-label-text"><?php echo translate('items_sold') ?: 'Items Sold'; ?></span>
                    </div>
                </div>
            </div>
            <div class="col-sm-3 col-xs-6">
                <div class="stat-card-custom">
                    <div class="stat-icon-box bg-custom-red"><i class="fas fa-receipt"></i></div>
                    <div class="stat-content-box">
                        <span class="stat-count-text"><?php echo $sym; ?> <?php echo number_format((float) $s['avg_order_value'], 0); ?></span>
                        <span class="stat-label-text"><?php echo translate('avg_order_value') ?: 'Avg Order Value'; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales by day -->
        <table class="table table-bordered table-hover table-condensed" width="100%">
            <thead>
                <tr>
                    <th><?php echo translate('date') ?: 'Date'; ?></th>
                    <th class="text-center"><?php echo translate('orders') ?: 'Orders'; ?></th>
                    <th class="text-right"><?php echo translate('revenue') ?: 'Revenue'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="3" class="text-center text-muted" style="padding:20px;"><?php echo translate('no_data_found') ?: 'No data for this range.'; ?></td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?php echo date('d M Y', strtotime($r['date'])); ?></td>
                        <td class="text-center"><?php echo number_format((int) $r['orders']); ?></td>
                        <td class="text-right"><?php echo $sym; ?> <?php echo number_format((float) $r['revenue'], 2); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <!-- Breakdowns -->
        <div class="row" style="margin-top:15px;">
            <div class="col-md-6">
                <h4 style="font-size:14px; font-weight:700;"><?php echo translate('by_order_status') ?: 'By Order Status'; ?></h4>
                <table class="table table-bordered table-condensed" width="100%">
                    <thead>
                        <tr>
                            <th><?php echo translate('status') ?: 'Status'; ?></th>
                            <th class="text-center"><?php echo translate('orders') ?: 'Orders'; ?></th>
                            <th class="text-right"><?php echo translate('amount') ?: 'Amount'; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($s['by_status'])): ?>
                            <tr><td colspan="3" class="text-center text-muted" style="padding:15px;">—</td></tr>
                        <?php else: foreach ($s['by_status'] as $b): ?>
                            <tr>
                                <td><span class="badge badge-<?php echo $status_class($b['status']); ?>"><?php echo ucfirst(html_escape($b['status'])); ?></span></td>
                                <td class="text-center"><?php echo number_format((int) $b['orders']); ?></td>
                                <td class="text-right"><?php echo $sym; ?> <?php echo number_format((float) $b['amount'], 2); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <h4 style="font-size:14px; font-weight:700;"><?php echo translate('by_payment_status') ?: 'By Payment Status'; ?></h4>
                <table class="table table-bordered table-condensed" width="100%">
                    <thead>
                        <tr>
                            <th><?php echo translate('payment_status') ?: 'Payment Status'; ?></th>
                            <th class="text-center"><?php echo translate('orders') ?: 'Orders'; ?></th>
                            <th class="text-right"><?php echo translate('amount') ?: 'Amount'; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($s['by_payment_status'])): ?>
                            <tr><td colspan="3" class="text-center text-muted" style="padding:15px;">—</td></tr>
                        <?php else: foreach ($s['by_payment_status'] as $b): ?>
                            <tr>
                                <td><span class="badge badge-<?php echo $status_class($b['payment_status']); ?>"><?php echo ucfirst(html_escape($b['payment_status'])); ?></span></td>
                                <td class="text-center"><?php echo number_format((int) $b['orders']); ?></td>
                                <td class="text-right"><?php echo $sym; ?> <?php echo number_format((float) $b['amount'], 2); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
