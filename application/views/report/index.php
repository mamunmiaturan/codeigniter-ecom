<?php
$sym = html_escape($currency ?? '৳');
$qs  = http_build_query(['from' => $from, 'to' => $to]);
$s   = $summary;

// Report cards: [url segment, icon, title, append date range?]
$cards = [
    ['sales',     'fas fa-chart-line',   translate('sales_report') ?: 'Sales Report',        translate('sales_report_hint') ?: 'Daily revenue, orders & status breakdown', true],
    ['products',  'fas fa-boxes',        translate('products_report') ?: 'Products Report',   translate('products_report_hint') ?: 'Best sellers by units and revenue',       true],
    ['customers', 'fas fa-users',        translate('customers_report') ?: 'Customers Report', translate('customers_report_hint') ?: 'Top customers by spend',                 true],
    ['inventory', 'fas fa-warehouse',    translate('inventory_report') ?: 'Inventory Report', translate('inventory_report_hint') ?: 'Low stock & inventory valuation',        false],
    ['payments',  'fas fa-credit-card',  translate('payments_report') ?: 'Payments Report',   translate('payments_report_hint') ?: 'Breakdown by method & payment status',    true],
];
?>
<section class="panel">
    <header class="panel-heading">
        <h2 class="panel-title"><i class="fas fa-chart-line"></i> <?php echo translate('reports') ?: 'Reports & Analytics'; ?></h2>
    </header>
    <div class="panel-body">
        <!-- Global date range -->
        <form method="get" action="<?php echo base_url('report'); ?>" class="form-inline" style="margin-bottom:20px;">
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

        <!-- Headline summary for the selected range -->
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

        <!-- Report links -->
        <div class="row" style="margin-top:10px;">
            <?php foreach ($cards as $c): ?>
                <?php $url = base_url('report/' . $c[0] . ($c[4] ? ('?' . $qs) : '')); ?>
                <div class="col-md-4 col-sm-6" style="margin-bottom:20px;">
                    <a href="<?php echo $url; ?>" class="panel" style="display:block; margin:0; padding:20px; text-decoration:none; border:1px solid #e5e7eb;">
                        <div style="display:flex; gap:15px; align-items:center;">
                            <div style="background:rgba(89,86,234,0.1); color:#5956ea; width:48px; height:48px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0;">
                                <i class="<?php echo $c[1]; ?>"></i>
                            </div>
                            <div>
                                <h4 style="margin:0 0 4px 0; font-weight:700; color:#2d3748; font-size:15px;"><?php echo html_escape($c[2]); ?></h4>
                                <p style="margin:0; font-size:12px; color:#718096; line-height:1.4;"><?php echo html_escape($c[3]); ?></p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
