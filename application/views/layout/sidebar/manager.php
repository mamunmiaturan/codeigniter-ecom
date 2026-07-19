<!-- Compiled Static Sidebar for manager.php -->
<aside id="sidebar-left" class="sidebar-left">
    <div class="nano">
        <div class="nano-content">
            <nav id="menu" class="nav-main" role="navigation">
                <ul class="nav nav-main">
                    <!-- dashboard -->
                    <li class="<?php if ($main_menu == 'dashboard') echo 'nav-active'; ?>">
                        <a href="<?php echo base_url('dashboard'); ?>">
                            <i class="fas fa-home"></i>
                            <span><?php echo translate('dashboard') ?: 'dashboard'; ?></span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</aside>
<!-- end sidebar -->
<script>
    $(document).ready(function() {
        $('.nav-parent > a').off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $li = $(this).parent();
            var $children = $li.children('.nav-children');
            if ($li.hasClass('nav-expanded')) {
                $children.slideUp(200, function() { $li.removeClass('nav-expanded'); });
            } else {
                $('.nav-parent.nav-expanded').not($li).each(function() {
                    $(this).removeClass('nav-expanded').children('.nav-children').slideUp(200);
                });
                $li.addClass('nav-expanded');
                $children.slideDown(200);
            }
        });
        $('.nav-parent.nav-active').addClass('nav-expanded').children('.nav-children').show();
    });
</script>
