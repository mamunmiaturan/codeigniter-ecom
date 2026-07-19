<?php defined('BASEPATH') or exit('No direct script access allowed');
$recent = isset($recent) ? $recent : [];
$thumb  = !empty($post['thumbnail']) ? base_url('uploads/blog/' . $post['thumbnail']) : '';
$tags   = !empty($post['tags']) ? array_filter(array_map('trim', explode(',', $post['tags']))) : [];
?>
<section class="container-fluid container-xl py-5">
    <div class="row justify-content-center g-5">
        <div class="col-lg-8">
            <nav class="small mb-3">
                <a href="<?php echo base_url('/'); ?>" class="text-muted text-decoration-none">Home</a>
                <span class="text-muted mx-1">/</span>
                <a href="<?php echo base_url('blogs'); ?>" class="text-muted text-decoration-none"><?php echo translate('blog') ?: 'Blog'; ?></a>
                <span class="text-muted mx-1">/</span><span><?php echo html_escape($post['title']); ?></span>
            </nav>

            <article>
                <?php if (!empty($post['category'])): ?>
                    <span class="badge bg-light text-muted mb-2"><?php echo html_escape($post['category']); ?></span>
                <?php endif; ?>
                <h1 class="h3 fw-bold mb-2"><?php echo html_escape($post['title']); ?></h1>
                <?php if (!empty($post['published_at'])): ?>
                    <div class="text-muted small mb-4"><i class="bi bi-calendar3"></i> <?php echo html_escape(date('d M Y', strtotime($post['published_at']))); ?></div>
                <?php endif; ?>

                <?php if ($thumb): ?>
                    <img src="<?php echo html_escape($thumb); ?>" class="img-fluid rounded mb-4 w-100" alt="<?php echo html_escape($post['title']); ?>" style="max-height:420px;object-fit:cover;">
                <?php endif; ?>

                <div class="blog-content">
                    <?php echo $post['content']; // admin-authored HTML ?>
                </div>

                <?php if (!empty($tags)): ?>
                    <div class="mt-4 d-flex flex-wrap gap-2">
                        <?php foreach ($tags as $tag): ?>
                            <span class="badge bg-light text-muted">#<?php echo html_escape($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>
        </div>

        <?php if (!empty($recent)): ?>
        <aside class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h6 fw-bold mb-3"><?php echo translate('recent_posts') ?: 'Recent Posts'; ?></h2>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($recent as $r): if ($r['slug'] === $post['slug']) continue; ?>
                            <li class="mb-3">
                                <a href="<?php echo base_url('blogs/' . rawurlencode($r['slug'])); ?>" class="text-dark text-decoration-none d-flex align-items-center" style="gap:10px;">
                                    <?php if (!empty($r['thumbnail'])): ?>
                                        <img src="<?php echo html_escape(base_url('uploads/blog/' . $r['thumbnail'])); ?>" alt="" width="56" height="56" class="rounded" style="object-fit:cover;flex-shrink:0;">
                                    <?php endif; ?>
                                    <span class="small">
                                        <?php echo html_escape($r['title']); ?>
                                        <?php if (!empty($r['published_at'])): ?>
                                            <span class="d-block text-muted"><?php echo html_escape(date('d M Y', strtotime($r['published_at']))); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </aside>
        <?php endif; ?>
    </div>
</section>
