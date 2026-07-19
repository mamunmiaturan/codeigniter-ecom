<?php
defined('BASEPATH') or exit('No direct script access allowed');
if (empty($page_tabs) || !is_array($page_tabs)) {
    return;
}
?>
<div class="tabs-custom">
    <div class="tabs-header-bar clearfix">
        <ul class="nav nav-tabs">
            <?php foreach ($page_tabs as $tab): ?>
            <li class="<?= !empty($tab['active']) ? 'active' : ''; ?>">
                <?php if (!empty($tab['url'])): ?>
                <a href="<?= html_escape($tab['url']); ?>">
                    <?php if (!empty($tab['icon'])): ?><i class="<?= html_escape($tab['icon']); ?>"></i><?php endif; ?>
                    <?= $tab['label'] ?? ''; ?>
                </a>
                <?php else: ?>
                <a href="#<?= html_escape($tab['id'] ?? 'list'); ?>" data-toggle="tab">
                    <?php if (!empty($tab['icon'])): ?><i class="<?= html_escape($tab['icon']); ?>"></i><?php endif; ?>
                    <?= $tab['label'] ?? ''; ?>
                </a>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php if (!empty($page_tab_actions)): ?>
        <div class="tabs-header-actions"><?= $page_tab_actions; ?></div>
        <?php endif; ?>
    </div>
    <div class="tab-content">
