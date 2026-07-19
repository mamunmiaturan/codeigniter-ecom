// Dismiss the full-screen preloader as soon as the page is usable.
//
// Previously this waited for window 'load', which fires only after EVERY asset
// and image has downloaded (56 CSS/JS files + every product/category image in a
// table). The PHP renders in well under 100ms, so the overlay was the entire
// perceived load time. Hide on DOMContentLoaded instead — the DOM and CSS are
// ready then, so the page is painted and interactive; images/DataTables finish
// filling in behind the (already-gone) overlay.
(function () {
    var hidden = false;
    function hide() {
        if (hidden) return;
        var p = document.getElementById('preloader');
        if (!p) return;
        hidden = true;
        p.classList.add('preloader-hidden');
        setTimeout(function () { p.style.display = 'none'; }, 250);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hide);
    } else {
        hide(); // DOM already parsed
    }
    // Fallbacks: never let the overlay linger past a real load or a hard cap.
    window.addEventListener('load', hide);
    setTimeout(hide, 3000);
})();
