<?php defined('BASEPATH') or exit('No direct script access allowed');
$profile = $profile ?: [];
$orders  = isset($orders) && is_array($orders) ? $orders : [];
$sup_phone = (string) get_global_setting('mobile_no');
$sup_email = (string) get_global_setting('site_email');
$sup_addr  = (string) get_global_setting('address');
$wa = preg_replace('/[^0-9]/', '', $sup_phone);
?>
<section class="container-fluid container-xl py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">Help &amp; Support</h1>
        <a href="<?php echo base_url('account'); ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to account</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-3">
            <?php $this->load->view('landing/account/nav', ['active' => 'support']); ?>
        </div>

        <div class="col-lg-9">
            <!-- Contact channels -->
            <div class="row g-3 mb-4">
                <?php if ($sup_phone !== ''): ?>
                <div class="col-md-4">
                    <a href="tel:<?php echo html_escape(preg_replace('/\s+/', '', $sup_phone)); ?>" class="acc-sup-card">
                        <span class="acc-sup-ic"><i class="bi bi-telephone"></i></span>
                        <strong>Call us</strong>
                        <span class="text-muted small"><?php echo html_escape($sup_phone); ?></span>
                    </a>
                </div>
                <?php endif; ?>
                <?php if ($sup_email !== ''): ?>
                <div class="col-md-4">
                    <a href="mailto:<?php echo html_escape($sup_email); ?>" class="acc-sup-card">
                        <span class="acc-sup-ic"><i class="bi bi-envelope"></i></span>
                        <strong>Email us</strong>
                        <span class="text-muted small"><?php echo html_escape($sup_email); ?></span>
                    </a>
                </div>
                <?php endif; ?>
                <?php if ($wa !== ''): ?>
                <div class="col-md-4">
                    <a href="https://wa.me/<?php echo html_escape($wa); ?>" target="_blank" rel="noopener" class="acc-sup-card">
                        <span class="acc-sup-ic"><i class="bi bi-whatsapp"></i></span>
                        <strong>WhatsApp</strong>
                        <span class="text-muted small">Chat with us</span>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Quick actions -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">How can we help?</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="<?php echo base_url('contact-us'); ?>" class="d-flex align-items-center gap-3 p-3 border rounded text-decoration-none text-dark acc-sup-link">
                                <i class="bi bi-chat-dots fs-4 text-success"></i>
                                <span><strong>Send us a message</strong><br><span class="text-muted small">We usually reply within a day</span></span>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="<?php echo base_url('faqs'); ?>" class="d-flex align-items-center gap-3 p-3 border rounded text-decoration-none text-dark acc-sup-link">
                                <i class="bi bi-question-circle fs-4 text-success"></i>
                                <span><strong>Browse FAQs</strong><br><span class="text-muted small">Answers to common questions</span></span>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="<?php echo base_url('account/orders'); ?>" class="d-flex align-items-center gap-3 p-3 border rounded text-decoration-none text-dark acc-sup-link">
                                <i class="bi bi-bag-check fs-4 text-success"></i>
                                <span><strong>Track an order</strong><br><span class="text-muted small">Check status &amp; delivery</span></span>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="<?php echo base_url('account/returns'); ?>" class="d-flex align-items-center gap-3 p-3 border rounded text-decoration-none text-dark acc-sup-link">
                                <i class="bi bi-arrow-return-left fs-4 text-success"></i>
                                <span><strong>Return an item</strong><br><span class="text-muted small">Start a return request</span></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent orders for quick reference -->
            <?php if ($orders): ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3">Need help with a recent order?</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr class="text-muted small"><th>Order</th><th>Date</th><th>Status</th><th class="text-end">Total</th></tr></thead>
                            <tbody>
                                <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td class="fw-semibold">#<?php echo html_escape((string) ($o['order_number'] ?? $o['id'] ?? '')); ?></td>
                                    <td class="small text-muted"><?php echo html_escape(isset($o['created_at']) ? time_ago($o['created_at']) : ''); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo html_escape(ucfirst((string) ($o['status'] ?? ''))); ?></span></td>
                                    <td class="text-end"><?php echo html_escape(shop_money((float) ($o['grand_total'] ?? $o['total'] ?? 0))); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($sup_addr !== ''): ?>
            <p class="text-muted small mt-3 mb-0"><i class="bi bi-geo-alt me-1"></i><?php echo html_escape($sup_addr); ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
    .acc-sup-card { display: flex; flex-direction: column; gap: 4px; padding: 1.25rem; background: #fff; border: 1px solid #eceef1; border-radius: 14px; text-decoration: none; color: #1f2937; transition: .16s; height: 100%; }
    .acc-sup-card:hover { border-color: transparent; box-shadow: 0 10px 24px rgba(13,148,136,.14); transform: translateY(-2px); color: #1f2937; }
    .acc-sup-ic { width: 44px; height: 44px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: color-mix(in srgb, var(--accent-color, #0d9488), transparent 88%); color: var(--accent-color, #0d9488); font-size: 1.25rem; margin-bottom: 4px; }
    .acc-sup-link:hover { border-color: var(--accent-color, #0d9488) !important; background: color-mix(in srgb, var(--accent-color, #0d9488), transparent 96%); }
</style>
