<?php
$sym = html_escape($currency_symbol ?? '৳');
$status_class = function ($s) {
    $m = [
        'pending' => 'warning', 'confirmed' => 'info', 'processing' => 'primary',
        'shipped' => 'primary', 'delivered' => 'success', 'completed' => 'success',
        'cancelled' => 'danger', 'returned' => 'danger',
    ];
    return $m[$s] ?? 'secondary';
};
?>
<!-- User Stat Cards Row -->
<div class="row">
    <!-- Total Users -->
    <div class="col-sm-3 col-xs-12">
        <div class="stat-card-custom">
            <div class="stat-icon-box bg-custom-blue">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content-box">
                <span class="stat-count-text"><?= number_format($total_users) ?></span>
                <span class="stat-label-text">Total Users</span>
            </div>
        </div>
    </div>

    <!-- Active Users -->
    <div class="col-sm-3 col-xs-12">
        <div class="stat-card-custom">
            <div class="stat-icon-box bg-custom-green">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-content-box">
                <span class="stat-count-text"><?= number_format($active_users) ?></span>
                <span class="stat-label-text">Total Active Users</span>
            </div>
        </div>
    </div>

    <!-- Inactive Users -->
    <div class="col-sm-3 col-xs-12">
        <div class="stat-card-custom">
            <div class="stat-icon-box bg-custom-red">
                <i class="fas fa-user-slash"></i>
            </div>
            <div class="stat-content-box">
                <span class="stat-count-text"><?= number_format($inactive_users) ?></span>
                <span class="stat-label-text">Total Inactive Users</span>
            </div>
        </div>
    </div>

    <!-- New Users This Month -->
    <div class="col-sm-3 col-xs-12">
        <div class="stat-card-custom">
            <div class="stat-icon-box bg-custom-orange">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="stat-content-box">
                <span class="stat-count-text"><?= number_format($this_month_new_users) ?></span>
                <span class="stat-label-text">This Month New Users</span>
            </div>
        </div>
    </div>
</div>
<!-- Commerce Stat Cards Row -->
<div class="row">
    <!-- Total Revenue -->
    <div class="col-sm-3 col-xs-12">
        <div class="stat-card-custom">
            <div class="stat-icon-box bg-custom-green">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-content-box">
                <span class="stat-count-text"><?= $sym ?> <?= number_format($sales['revenue_total'], 0) ?></span>
                <span class="stat-label-text">Total Revenue</span>
            </div>
        </div>
    </div>

    <!-- This Month Revenue -->
    <div class="col-sm-3 col-xs-12">
        <div class="stat-card-custom">
            <div class="stat-icon-box bg-custom-blue">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content-box">
                <span class="stat-count-text"><?= $sym ?> <?= number_format($sales['revenue_month'], 0) ?></span>
                <span class="stat-label-text">This Month Revenue</span>
            </div>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="col-sm-3 col-xs-12">
        <div class="stat-card-custom">
            <div class="stat-icon-box bg-custom-orange">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-content-box">
                <span class="stat-count-text"><?= number_format($sales['orders_total']) ?></span>
                <span class="stat-label-text">Total Orders</span>
            </div>
        </div>
    </div>

    <!-- Low Stock -->
    <div class="col-sm-3 col-xs-12">
        <div class="stat-card-custom">
            <div class="stat-icon-box bg-custom-red">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content-box">
                <span class="stat-count-text"><?= number_format($low_stock_count) ?></span>
                <span class="stat-label-text">Low Stock Products</span>
            </div>
        </div>
    </div>
</div>

<style>
    /* Equal card heights within a row (task: "card er height same rakho"). */
    .dash-eq { display: flex; flex-wrap: wrap; }
    .dash-eq > [class*="col-"] { display: flex; }
    .dash-eq > [class*="col-"] > .panel { width: 100%; }
</style>

<!-- Sales trend + Orders-by-status pie -->
<div class="row dash-eq">
    <div class="col-md-8">
        <section class="panel">
            <header class="panel-heading">
                <h2 class="panel-title"><i class="fas fa-chart-line"></i> Sales — Last 14 Days</h2>
            </header>
            <div class="panel-body">
                <canvas id="dashSalesChart" height="110"></canvas>
            </div>
        </section>
    </div>
    <div class="col-md-4">
        <section class="panel">
            <header class="panel-heading">
                <h2 class="panel-title"><i class="fas fa-chart-pie"></i> Orders by Status</h2>
            </header>
            <div class="panel-body" style="display:flex;align-items:center;justify-content:center;">
                <canvas id="dashStatusPie" height="230"></canvas>
            </div>
        </section>
    </div>
</div>

<!-- Orders + Top Sellers -->
<div class="row dash-eq">
    <div class="col-md-6">
        <section class="panel">
            <header class="panel-heading">
                <div class="panel-actions">
                    <a href="<?= base_url('order') ?>" class="btn btn-xs btn-default">View All</a>
                </div>
                <h2 class="panel-title"><i class="fas fa-receipt"></i> Recent Orders</h2>
            </header>
            <div class="panel-body">
                <table class="table table-hover table-condensed mb-none">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th class="text-right">Total</th>
                            <th class="text-center">Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_orders)): ?>
                            <tr><td colspan="5" class="text-center text-muted" style="padding: 20px;">No orders yet.</td></tr>
                        <?php else: foreach ($recent_orders as $o): ?>
                            <tr>
                                <td><a href="<?= base_url('order/view/' . encrypt_id($o['id'])) ?>"><strong><?= html_escape($o['order_number']) ?></strong></a></td>
                                <td><?= html_escape($o['customer_name']) ?><br><small class="text-muted"><?= html_escape($o['customer_phone']) ?></small></td>
                                <td class="text-right"><?= $sym ?> <?= number_format((float) $o['total'], 2) ?></td>
                                <td class="text-center"><span class="badge badge-<?= $status_class($o['status']) ?>"><?= ucfirst($o['status']) ?></span></td>
                                <td><small><?= time_ago($o['created_at']) ?></small></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="col-md-6">
        <section class="panel">
            <header class="panel-heading">
                <h2 class="panel-title"><i class="fas fa-trophy"></i> Top Selling Products</h2>
            </header>
            <div class="panel-body">
                <table class="table table-hover table-condensed mb-none">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Units</th>
                            <th class="text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_products)): ?>
                            <tr><td colspan="3" class="text-center text-muted" style="padding: 20px;">No sales yet.</td></tr>
                        <?php else: foreach ($top_products as $p): ?>
                            <tr>
                                <td><?= html_escape($p['product_name']) ?></td>
                                <td class="text-center"><span class="badge badge-primary"><?= (int) $p['units'] ?></span></td>
                                <td class="text-right"><small><?= $sym ?> <?= number_format((float) $p['revenue'], 0) ?></small></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<!-- Total orders trend + Low stock -->
<div class="row dash-eq">
    <div class="col-md-6">
        <section class="panel">
            <header class="panel-heading">
                <h2 class="panel-title"><i class="fas fa-chart-bar"></i> Total Orders — Last 14 Days</h2>
            </header>
            <div class="panel-body">
                <canvas id="dashOrdersChart" height="150"></canvas>
            </div>
        </section>
    </div>

    <div class="col-md-6">
        <section class="panel">
            <header class="panel-heading">
                <div class="panel-actions">
                    <a href="<?= base_url('product') ?>" class="btn btn-xs btn-default">Manage</a>
                </div>
                <h2 class="panel-title"><i class="fas fa-boxes"></i> Low Stock Products</h2>
            </header>
            <div class="panel-body">
                <table class="table table-hover table-condensed mb-none">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th class="text-center">On Hand</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($low_stock)): ?>
                            <tr><td colspan="3" class="text-center text-muted" style="padding: 20px;">All products are well stocked. 🎉</td></tr>
                        <?php else: foreach ($low_stock as $ls): ?>
                            <tr>
                                <td><?= html_escape($ls['name']) ?></td>
                                <td><small class="text-muted"><?= html_escape($ls['sku'] ?: '—') ?></small></td>
                                <td class="text-center"><span class="badge badge-<?= ((int) $ls['stock_quantity'] <= 0) ? 'danger' : 'warning' ?>"><?= (int) $ls['stock_quantity'] ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>


<script type="text/javascript">
    $(document).ready(function() {
        // Session Countdown Timer
        <?php
        $login_time = $this->session->userdata('login_time') ?: time();
        $elapsed = time() - $login_time;
        $remaining = max(0, (int)config_item('sess_expiration') - $elapsed);
        ?>
        var sessionTimeLeft = <?= (int)$remaining ?>;
        var countdownTimer = setInterval(function() {
            if (sessionTimeLeft <= 0) {
                clearInterval(countdownTimer);
                $('#session-countdown-timer').text("Expired");
                window.location.href = '<?= base_url('logout') ?>';
                return;
            }
            sessionTimeLeft--;
            var minutes = Math.floor(sessionTimeLeft / 60);
            var seconds = sessionTimeLeft % 60;
            if (seconds < 10) seconds = '0' + seconds;
            $('#session-countdown-timer').text(minutes + ":" + seconds);
        }, 1000);
    });
</script>

<!-- Dashboard charts (Chart.js v3, bundled) -->
<script src="<?= asset_ver('assets/backend/vendor/chartjs/chart.min.js') ?>"></script>
<script type="text/javascript">
(function () {
    if (typeof Chart === 'undefined') return;
    var css     = getComputedStyle(document.documentElement);
    var primary = (css.getPropertyValue('--primary-color') || '#5956ea').trim();
    var sym     = <?= json_encode($sym) ?>;
    var labels  = <?= json_encode($trend['labels']) ?>;

    function rgba(hex, a) {
        hex = (hex || '').replace('#', '');
        if (hex.length === 3) hex = hex.split('').map(function (c) { return c + c; }).join('');
        var n = parseInt(hex, 16) || 0;
        return 'rgba(' + ((n >> 16) & 255) + ',' + ((n >> 8) & 255) + ',' + (n & 255) + ',' + a + ')';
    }

    // Sales — line
    var sc = document.getElementById('dashSalesChart');
    if (sc) new Chart(sc, {
        type: 'line',
        data: { labels: labels, datasets: [{
            label: 'Sales', data: <?= json_encode($trend['revenue']) ?>,
            borderColor: primary, backgroundColor: rgba(primary, 0.12),
            fill: true, tension: 0.35, borderWidth: 2, pointRadius: 3, pointBackgroundColor: primary
        }] },
        options: {
            responsive: true, maintainAspectRatio: true, aspectRatio: 2.8,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function (c) { return sym + ' ' + Number(c.parsed.y).toLocaleString(); } } }
            },
            scales: { y: { beginAtZero: true, ticks: { callback: function (v) { return sym + ' ' + v; } } } }
        }
    });

    // Total orders — bar
    var oc = document.getElementById('dashOrdersChart');
    if (oc) new Chart(oc, {
        type: 'bar',
        data: { labels: labels, datasets: [{
            label: 'Orders', data: <?= json_encode($trend['orders']) ?>,
            backgroundColor: primary, borderRadius: 4, maxBarThickness: 26
        }] },
        options: {
            responsive: true, maintainAspectRatio: true, aspectRatio: 3,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    // Orders by status — doughnut
    var pie = document.getElementById('dashStatusPie');
    if (pie) {
        var sd = <?= json_encode($orders_by_status) ?>;
        var cmap = {
            pending: '#f0ad4e', confirmed: '#5bc0de', processing: primary, shipped: primary,
            delivered: '#5cb85c', completed: '#5cb85c', cancelled: '#d9534f', returned: '#d9534f'
        };
        var pl = [], pv = [], pc = [];
        Object.keys(sd).forEach(function (k) {
            if (sd[k] > 0) { pl.push(k.charAt(0).toUpperCase() + k.slice(1)); pv.push(sd[k]); pc.push(cmap[k] || '#adb5bd'); }
        });
        if (!pv.length) { pl = ['No orders']; pv = [1]; pc = ['#e9ecef']; }
        new Chart(pie, {
            type: 'doughnut',
            data: { labels: pl, datasets: [{ data: pv, backgroundColor: pc, borderWidth: 2, borderColor: '#fff' }] },
            options: {
                responsive: true, maintainAspectRatio: true, aspectRatio: 1, cutout: '58%',
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10, font: { size: 11 } } } }
            }
        });
    }
})();
</script>
