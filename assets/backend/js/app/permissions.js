$(document).ready(function () {
    // 1. Column-wise Toggle
    $('#all_view').change(function ()   { $('.cb_view').prop('checked', $(this).prop('checked')).trigger('change'); });
    $('#all_add').change(function ()    { $('.cb_add').prop('checked', $(this).prop('checked')).trigger('change'); });
    $('#all_edit').change(function ()   { $('.cb_edit').prop('checked', $(this).prop('checked')).trigger('change'); });
    $('#all_delete').change(function () { $('.cb_delete').prop('checked', $(this).prop('checked')).trigger('change'); });

    // 2. Global Toggle (all checkboxes)
    $('#all_row_select').change(function () {
        var isChecked = $(this).prop('checked');
        $('input[type="checkbox"]').not(this).prop('checked', isChecked).trigger('change');
    });

    // 3. Row-wise Toggle
    $('.cb_row_toggle').change(function () {
        var isChecked = $(this).prop('checked');
        $(this).closest('tr').find('.cb_action').prop('checked', isChecked).trigger('change');
    });

    // 4. Module Group-wise Toggle
    $('.btn-toggle-module').click(function (e) {
        e.stopPropagation();
        var moduleId        = $(this).data('module-id');
        var groupCheckboxes = $('.module-group-' + moduleId).find('input[type="checkbox"]');
        var anyUnchecked    = groupCheckboxes.filter(':not(:checked)').length > 0;
        groupCheckboxes.prop('checked', anyUnchecked).trigger('change');
    });

    // 5. Accordion Expand/Collapse
    $('.module-header-title').click(function () {
        var moduleId  = $(this).data('module-id');
        var headerRow = $(this).closest('.module-header-row');
        var targetRows = $('.module-group-' + moduleId);
        headerRow.toggleClass('collapsed');
        if (headerRow.hasClass('collapsed')) { targetRows.fadeOut(200); }
        else { targetRows.fadeIn(200); }
    });

    // 6. Real-time Instant Search/Filter
    $('#permissionSearch').keyup(function () {
        var val = $(this).val().toLowerCase().trim();
        if (val === '') { $('.permission-row').show(); $('.module-header-row').show(); return; }
        $('.module-header-row').each(function () {
            var moduleId   = $(this).data('module-id');
            var headerRow  = $(this);
            var rows       = $('.module-group-' + moduleId);
            var matchCount = 0;
            rows.each(function () {
                var text = $(this).data('search-term');
                if (text.indexOf(val) !== -1) { $(this).show(); matchCount++; }
                else { $(this).hide(); }
            });
            if (matchCount > 0) { headerRow.show(); } else { headerRow.hide(); }
        });
    });

    // 7 & 8. Visual feedback helpers
    function styleActiveStates() {
        $('.permission-row').each(function () {
            var row          = $(this);
            var actionInputs = row.find('.cb_action');
            var allChecked   = actionInputs.length > 0 && actionInputs.filter(':not(:checked)').length === 0;
            row.toggleClass('row-active', allChecked);
            actionInputs.each(function () {
                $(this).closest('td').toggleClass('cell-checked', $(this).prop('checked'));
            });
        });
    }

    function updateRowToggles() {
        $('.permission-row').each(function () {
            var row              = $(this);
            var actionCheckboxes = row.find('.cb_action');
            var rowToggle        = row.find('.cb_row_toggle');
            if (actionCheckboxes.length > 0) {
                rowToggle.prop('checked', actionCheckboxes.filter(':not(:checked)').length === 0);
            }
        });
    }

    updateRowToggles();
    styleActiveStates();

    $('input[type="checkbox"]').change(function () {
        var row = $(this).closest('tr');
        if (row.hasClass('permission-row')) {
            var actionCheckboxes = row.find('.cb_action');
            row.find('.cb_row_toggle').prop('checked', actionCheckboxes.filter(':not(:checked)').length === 0);
        }
        styleActiveStates();
    });
});
