<?php
defined('BASEPATH') or exit('No direct script access allowed');
$sym  = get_global_setting('currency_symbol') ?: '৳';
$site = get_global_setting('site_name') ?: 'Bazaar';
function _inv_money($sym, $v) { return html_escape($sym) . ' ' . number_format((float) $v, 2); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice <?php echo html_escape($invoice['invoice_number']); ?></title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color:#222; font-size:13px; padding:24px; }
        .inv-head { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #222; padding-bottom:12px; margin-bottom:18px; }
        .inv-head h1 { margin:0; font-size:22px; }
        .muted { color:#666; }
        table { width:100%; border-collapse:collapse; margin-top:12px; }
        th, td { border:1px solid #ddd; padding:8px; text-align:left; }
        th { background:#f5f5f5; }
        .text-right { text-align:right; } .text-center { text-align:center; }
        tfoot td { border:none; }
        .totals td { padding:4px 8px; }
        .grand { font-size:16px; font-weight:bold; border-top:2px solid #222; }
        .print-btn { margin-bottom:16px; }
        @media print { .print-btn { display:none; } body { padding:0; } }
    </style>
</head>
<body>
    <div class="print-btn"><button onclick="window.print()">🖨 Print</button></div>

    <div class="inv-head">
        <div>
            <h1><?php echo html_escape($site); ?></h1>
            <div class="muted">Cash on delivery across Bangladesh</div>
        </div>
        <div class="text-right">
            <h2 style="margin:0;">INVOICE</h2>
            <div><strong><?php echo html_escape($invoice['invoice_number']); ?></strong></div>
            <div class="muted"><?php echo date('d M Y', strtotime($invoice['created_at'])); ?></div>
        </div>
    </div>

    <table style="border:none;margin-bottom:10px;">
        <tr style="border:none;">
            <td style="border:none;vertical-align:top;">
                <strong>Billed to</strong><br>
                <?php echo html_escape($order['customer_name']); ?><br>
                <?php echo html_escape($order['customer_phone']); ?><br>
                <?php echo html_escape(implode(', ', array_filter([$order['shipping_address'], $order['shipping_area'], $order['shipping_district'], $order['shipping_division'], $order['shipping_postcode']]))); ?>
            </td>
            <td style="border:none;vertical-align:top;" class="text-right">
                <strong>Order</strong> <?php echo html_escape($order['order_number']); ?><br>
                <strong>Placed</strong> <?php echo date('d M Y', strtotime($order['placed_at'])); ?><br>
                <strong>Payment</strong> <?php echo strtoupper($order['payment_method']); ?> · <?php echo ucfirst($order['payment_status']); ?>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr><th>Product</th><th class="text-center">Unit Price</th><th class="text-center">Qty</th><th class="text-right">Total</th></tr>
        </thead>
        <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><?php echo html_escape($it['product_name']); ?><?php if (!empty($it['variant_name'])): ?> <span class="muted">(<?php echo html_escape($it['variant_name']); ?>)</span><?php endif; ?><?php if (!empty($it['sku'])): ?><br><small class="muted">SKU: <?php echo html_escape($it['sku']); ?></small><?php endif; ?></td>
                    <td class="text-center"><?php echo _inv_money($sym, $it['unit_price']); ?></td>
                    <td class="text-center"><?php echo (int) $it['quantity']; ?></td>
                    <td class="text-right"><?php echo _inv_money($sym, $it['line_total']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot class="totals">
            <tr><td colspan="3" class="text-right">Subtotal</td><td class="text-right"><?php echo _inv_money($sym, $order['subtotal']); ?></td></tr>
            <?php if ((float) $order['discount'] > 0): ?><tr><td colspan="3" class="text-right">Discount<?php if (!empty($order['coupon_code'])) echo ' (' . html_escape($order['coupon_code']) . ')'; ?></td><td class="text-right">- <?php echo _inv_money($sym, $order['discount']); ?></td></tr><?php endif; ?>
            <tr><td colspan="3" class="text-right">Shipping<?php if (!empty($order['shipping_method_label'])) echo ' (' . html_escape($order['shipping_method_label']) . ')'; ?></td><td class="text-right"><?php echo _inv_money($sym, $order['shipping_charge']); ?></td></tr>
            <?php if ((float) $order['tax'] > 0): ?><tr><td colspan="3" class="text-right">Tax (VAT)</td><td class="text-right"><?php echo _inv_money($sym, $order['tax']); ?></td></tr><?php endif; ?>
            <tr class="grand"><td colspan="3" class="text-right">Grand Total</td><td class="text-right"><?php echo _inv_money($sym, $order['total']); ?></td></tr>
        </tfoot>
    </table>

    <p class="muted" style="margin-top:24px;text-align:center;">Thank you for shopping with <?php echo html_escape($site); ?>.</p>
</body>
</html>
