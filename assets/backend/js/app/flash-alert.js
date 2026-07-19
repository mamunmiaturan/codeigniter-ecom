document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('js-flash-alert');
    if (!el) return;

    var type    = el.getAttribute('data-type')    || 'info';
    var message = el.getAttribute('data-message') || '';
    var mode    = el.getAttribute('data-mode')    || 'toast';  // 'toast' | 'modal'

    if (!message) return;

    if (mode === 'toast') {
        swal({ toast: true, position: 'top-end', type: type, title: message, timer: 2000, showConfirmButton: false });
    } else {
        swal({ title: type === 'success' ? 'Success' : 'Error', text: message, icon: type });
    }
});
