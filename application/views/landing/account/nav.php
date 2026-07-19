<?php defined('BASEPATH') or exit('No direct script access allowed');
$active = $active ?? '';
$items = [
    'dashboard' => ['url' => 'landing/account',         'icon' => 'bi-speedometer2', 'label' => 'Dashboard'],
    'orders'    => ['url' => 'landing/account/orders',  'icon' => 'bi-bag',          'label' => 'My Orders'],
    'downloads' => ['url' => 'landing/account/downloads','icon' => 'bi-download',    'label' => 'My Downloads'],
    'wishlist'  => ['url' => 'landing/account/wishlist','icon' => 'bi-heart',        'label' => 'Wishlist'],
    'returns'   => ['url' => 'landing/account/returns', 'icon' => 'bi-arrow-return-left', 'label' => 'Returns'],
    'complaints' => ['url' => 'landing/account/complaints', 'icon' => 'bi-exclamation-circle', 'label' => 'Complaints'],
    'tickets'   => ['url' => 'landing/account/tickets', 'icon' => 'bi-ticket-perforated', 'label' => 'Support Tickets'],
    'support'   => ['url' => 'landing/account/support', 'icon' => 'bi-headset',      'label' => 'Help & Support'],
]; ?>
<div class="list-group shadow-sm mb-3">
    <?php foreach ($items as $key => $it): ?>
        <a href="<?php echo base_url($it['url']); ?>" class="list-group-item list-group-item-action <?php echo $active === $key ? 'active' : ''; ?>">
            <i class="bi <?php echo $it['icon']; ?> me-2"></i><?php echo $it['label']; ?>
        </a>
    <?php endforeach; ?>
    <a href="<?php echo base_url('account/logout'); ?>" class="list-group-item list-group-item-action text-danger">
        <i class="bi bi-box-arrow-right me-2"></i>Log out
    </a>
</div>
