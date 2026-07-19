<?php
defined('BASEPATH') or exit('No direct script access allowed');
$filter_panel_id = $filter_panel_id ?? 'page';
$filter_body_id = $filter_panel_id . '-filter-body';
$filter_toggle_id = 'toggle-' . $filter_panel_id . '-filter-btn';

$filter_fields = $filter_fields ?? '';
preg_match_all('/col-md-\d+/', $filter_fields, $matches);
$num_fields = count($matches[0]);

if ($num_fields > 0) {
    $col_map = [
        1 => 'col-md-12',
        2 => 'col-md-6',
        3 => 'col-md-4',
        4 => 'col-md-3',
        5 => 'col-md-2',
        6 => 'col-md-2',
    ];
    $target_col = $col_map[$num_fields] ?? 'col-md-3';
    $filter_fields = preg_replace('/col-md-\d+/', $target_col, $filter_fields);
}
?>
<section class="panel app-filter-panel mb-md">
    <header class="panel-heading" id="<?= html_escape($filter_toggle_id); ?>" style="cursor: pointer; user-select: none;">
        <h4 class="panel-title" style="display:flex;align-items:center;justify-content:space-between;">
            <span><i class="fas fa-filter"></i> <?= translate('filter'); ?></span>
            <i class="fas fa-chevron-down toggle-icon" style="transition: transform 0.2s ease;"></i>
        </h4>
    </header>
    <div class="panel-body" id="<?= html_escape($filter_body_id); ?>" style="display: none;">
        <form method="get" class="form-filter" id="form-<?= html_escape($filter_panel_id); ?>">
            <input type="hidden" name="tab" id="filter-tab-<?= html_escape($filter_panel_id); ?>" value="">
            <div class="row">
                <?= $filter_fields; ?>
                <div class="col-md-12" style="margin-top: 15px; text-align: right;">
                    <a href="<?= current_url(); ?>" class="btn btn-default no-auto-float" style="margin-right: 5px;"><i class="fas fa-undo"></i> <?= translate('reset'); ?></a>
                    <button type="submit" class="btn btn-primary no-auto-float"><i class="fas fa-filter"></i> <?= translate('filter'); ?></button>
                </div>
            </div>
        </form>
    </div>
</section>
<script type="text/javascript">
$(document).ready(function() {
    var key = 'filter_collapsed_<?= html_escape($filter_panel_id); ?>';
    var $body = $('#<?= html_escape($filter_body_id); ?>');
    var $icon = $('#<?= html_escape($filter_toggle_id); ?>').find('.toggle-icon');
    var $tabInput = $('#filter-tab-<?= html_escape($filter_panel_id); ?>');

    if (localStorage.getItem(key) === 'false') {
        $body.show();
        $icon.css('transform', 'rotate(180deg)');
    }
    $('#<?= html_escape($filter_toggle_id); ?>').on('click', function() {
        if ($body.is(':visible')) {
            $body.slideUp(200);
            $icon.css('transform', 'rotate(0deg)');
            localStorage.setItem(key, 'true');
        } else {
            $body.slideDown(200);
            $icon.css('transform', 'rotate(180deg)');
            localStorage.setItem(key, 'false');
        }
    });

    // Preserve active tab on filter submit
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        var tabId = $(e.target).attr('href').replace('#', '');
        $tabInput.val(tabId);
        // Also update URL
        var url = new URL(window.location.href);
        url.searchParams.set('tab', tabId);
        window.history.replaceState(null, '', url.toString());
    });

    // Set current tab from URL on load
    var urlParams = new URLSearchParams(window.location.search);
    var currentTab = urlParams.get('tab');
    if (currentTab) {
        $tabInput.val(currentTab);
    }
});
</script>
