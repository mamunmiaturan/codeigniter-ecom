// Reads CSRF credentials from <meta> tags injected by MY_Controller::_output()
// and wires them into every jQuery AJAX request automatically.
$(function () {
    var tokenName = $('meta[name="csrf-name"]').attr('content');
    var tokenHash = $('meta[name="csrf-token"]').attr('content');
    if (!tokenName || !tokenHash) return;
    $.ajaxSetup({ data: { [tokenName]: tokenHash } });
});
