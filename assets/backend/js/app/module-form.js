$(document).ready(function () {
    // Module create form: toggle section visibility based on type dropdown
    var $moduleType = $('#moduleType');
    if ($moduleType.length) {
        $moduleType.change(function () {
            var selected = $(this).val();
            if (selected) {
                $('#moduleFormFields').show();
                $('#existingModule, #newModule').hide();
                if (selected === 'existing') { $('#existingModule').show(); }
                else if (selected === 'new')  { $('#newModule').show(); }
            } else {
                $('#moduleFormFields').hide();
                $('#existingModule, #newModule').hide();
            }
        }).trigger('change');

        // Select / Deselect all permissions on module create form
        $('#selectAllPermissions').click(function () {
            $('.cb_permission').prop('checked', $(this).prop('checked'));
        });
    }

    // Module edit form: select-all shortcut
    $('#select_all').click(function () {
        $('.cb_permission').prop('checked', $(this).prop('checked'));
    });
});
