// Command Palette — Ctrl+K / Cmd+K
// Route config is read from <script id="js-app-routes" type="application/json"> in the page.
$(document).ready(function () {
    var routesEl = document.getElementById('js-app-routes');
    var routes   = routesEl ? JSON.parse(routesEl.textContent) : {};

    var $backdrop = $('<div class="cmd-palette-backdrop" id="cmdPalette">' +
        '<div class="cmd-palette-card">' +
            '<div class="cmd-palette-search-container">' +
                '<i class="fas fa-search search-icon"></i>' +
                '<input type="text" class="cmd-palette-input" id="cmdInput" placeholder="Search pages, settings, or actions (Ctrl + K)..." autocomplete="off">' +
            '</div>' +
            '<div class="cmd-palette-results" id="cmdResults"></div>' +
            '<div class="cmd-palette-footer">' +
                '<span>Use <kbd>↑</kbd> <kbd>↓</kbd> to navigate, <kbd>Enter ⏎</kbd> to open</span>' +
                '<span>Press <kbd>ESC</kbd> to close</span>' +
            '</div>' +
        '</div>' +
    '</div>');
    $('body').append($backdrop);

    var $input   = $('#cmdInput');
    var $results = $('#cmdResults');
    var commands = [];

    // Crawl sidebar for accessible pages (respects user permissions via rendered menu)
    $('#menu ul.nav-main li').each(function () {
        var $li        = $(this);
        var $a         = $li.find('> a');
        var menuTitle  = $.trim($a.contents().filter(function () { return this.nodeType === 3; }).text());
        var href       = $a.attr('href');
        var iconClass  = $a.find('i').attr('class') || 'fas fa-link';
        var $subLi     = $li.find('.nav-children li');

        if ($subLi.length > 0) {
            $subLi.each(function () {
                var $subA   = $(this).find('a');
                var subHref = $subA.attr('href');
                if (subHref && subHref !== '#' && subHref !== 'javascript:void(0);') {
                    commands.push({ title: menuTitle + ' → ' + $.trim($subA.text()), url: subHref, icon: iconClass, desc: 'Manage and view ' + $.trim($subA.text()) });
                }
            });
        } else if (href && href !== '#' && href !== 'javascript:void(0);') {
            commands.push({ title: menuTitle, url: href, icon: iconClass, desc: 'Go to ' + menuTitle });
        }
    });

    if (routes.dashboard) {
        commands.unshift({ title: 'Dashboard', url: routes.dashboard, icon: 'fas fa-tachometer-alt', desc: 'Go to main admin panel overview' });
    }
    if (routes.logout) {
        commands.push({ title: 'Logout / Sign Out', url: routes.logout, icon: 'fas fa-sign-out-alt', desc: 'Safely log out of your session', shortcut: 'Exit' });
    }

    commands = commands.filter(function (cmd, idx, self) {
        return cmd.title && cmd.url && self.findIndex(function (c) { return c.url === cmd.url; }) === idx;
    });

    function renderResults(query) {
        $results.empty();
        query = query.toLowerCase().trim();
        var filtered = commands.filter(function (cmd) {
            return cmd.title.toLowerCase().indexOf(query) !== -1 || cmd.desc.toLowerCase().indexOf(query) !== -1;
        });
        if (filtered.length === 0) {
            $results.append('<div style="padding:20px;text-align:center;color:#8b929a;">No matching options found.</div>');
            return;
        }
        filtered.forEach(function (cmd, idx) {
            var shortcutHtml = cmd.shortcut
                ? '<span class="cmd-palette-shortcut">' + cmd.shortcut + '</span>'
                : '<span class="cmd-palette-shortcut"><i class="fas fa-chevron-right"></i></span>';
            $results.append(
                '<a href="' + cmd.url + '" class="cmd-palette-item' + (idx === 0 ? ' selected' : '') + '" data-index="' + idx + '">' +
                    '<div class="cmd-palette-item-left">' +
                        '<i class="' + cmd.icon + '"></i>' +
                        '<div><span class="cmd-palette-item-title">' + cmd.title + '</span>' +
                        '<span class="cmd-palette-item-desc">' + cmd.desc + '</span></div>' +
                    '</div>' + shortcutHtml +
                '</a>');
        });
    }

    function showPalette() { $backdrop.addClass('active'); $input.val('').focus(); renderResults(''); }
    function hidePalette() { $backdrop.removeClass('active'); }

    $(document).on('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            $backdrop.hasClass('active') ? hidePalette() : showPalette();
        }
        if (e.key === 'Escape' && $backdrop.hasClass('active')) { hidePalette(); }
        if ($backdrop.hasClass('active')) {
            var $sel = $results.find('.cmd-palette-item.selected');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                var $next = $sel.next('.cmd-palette-item');
                if ($next.length) { $sel.removeClass('selected'); $next.addClass('selected'); $next[0].scrollIntoView({ block: 'nearest' }); }
            }
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                var $prev = $sel.prev('.cmd-palette-item');
                if ($prev.length) { $sel.removeClass('selected'); $prev.addClass('selected'); $prev[0].scrollIntoView({ block: 'nearest' }); }
            }
            if (e.key === 'Enter') { e.preventDefault(); if ($sel.length) { window.location.href = $sel.attr('href'); } }
        }
    });

    $input.on('input', function () { renderResults($(this).val()); });
    $backdrop.on('click', function (e) { if ($(e.target).hasClass('cmd-palette-backdrop')) { hidePalette(); } });
    $results.on('mouseenter', '.cmd-palette-item', function () {
        $results.find('.cmd-palette-item').removeClass('selected');
        $(this).addClass('selected');
    });
});
