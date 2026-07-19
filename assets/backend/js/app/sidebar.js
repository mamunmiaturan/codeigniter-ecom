$(document).ready(function () {
    $('.nav-parent > a').off('click').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $li       = $(this).parent();
        var $children = $li.children('.nav-children');
        if ($li.hasClass('nav-expanded')) {
            $children.slideUp(200, function () { $li.removeClass('nav-expanded'); });
        } else {
            $('.nav-parent.nav-expanded').not($li).each(function () {
                $(this).removeClass('nav-expanded').children('.nav-children').slideUp(200);
            });
            $li.addClass('nav-expanded');
            $children.slideDown(200);
        }
    });
    $('.nav-parent.nav-active').addClass('nav-expanded').children('.nav-children').show();
});
