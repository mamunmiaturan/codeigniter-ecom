<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<section class="container-fluid container-xl py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <nav class="small mb-3">
                <a href="<?php echo base_url('/'); ?>" class="text-muted text-decoration-none">Home</a>
                <span class="text-muted mx-1">/</span><span><?php echo html_escape($page['title']); ?></span>
            </nav>
            <h1 class="h3 fw-bold mb-4"><?php echo html_escape($page['title']); ?></h1>
            <div class="cms-content">
                <?php echo $page['content']; // admin-authored HTML ?>
            </div>
        </div>
    </div>
</section>
