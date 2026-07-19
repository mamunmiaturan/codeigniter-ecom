document.addEventListener('DOMContentLoaded', function () {
    // Single-target toggle (login page: #togglePassword / #password)
    var singleToggle = document.getElementById('togglePassword');
    var singlePass   = document.getElementById('password');
    if (singleToggle && singlePass) {
        singleToggle.addEventListener('click', function () {
            var isHidden = singlePass.type === 'password';
            singlePass.type = isHidden ? 'text' : 'password';
            var icon = singleToggle.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            }
        });
    }

    // Multi-target toggles (reset page: [data-target])
    document.querySelectorAll('.toggle-password').forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            var targetId = this.getAttribute('data-target');
            var input    = document.getElementById(targetId);
            if (!input) return;
            var icon = this.querySelector('i');
            var type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            if (icon) {
                icon.classList.toggle('fa-eye-slash');
                icon.classList.toggle('fa-eye');
            }
        });
    });
});
