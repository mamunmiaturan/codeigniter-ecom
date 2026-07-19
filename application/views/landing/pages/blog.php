<?php defined('BASEPATH') or exit('No direct script access allowed');
$posts = isset($posts) ? $posts : [];
$page  = isset($page) ? (int) $page : 1;
$pages = isset($pages) ? (int) $pages : 1;
$total = isset($total) ? (int) $total : count($posts);
?>
<section class="container-fluid container-xl py-5">
    <div class="mb-4">
        <h1 class="section-title h4 mb-1"><?php echo translate('blog') ?: 'Blog'; ?></h1>
        <div class="text-muted small"><?php echo $total; ?> <?php echo $total === 1 ? 'post' : 'posts'; ?></div>
    </div>

    <?php if (empty($posts)): ?>
        <div class="text-center py-5">
            <div style="font-size:3rem;">📝</div>
            <p class="text-muted mb-0">No blog posts have been published yet.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($posts as $post):
                $thumb = !empty($post['thumbnail']) ? base_url('uploads/blog/' . $post['thumbnail']) : '';
                $url   = base_url('blogs/' . rawurlencode($post['slug'])); ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <article class="card h-100 shadow-sm border-0">
                        <a href="<?php echo $url; ?>" class="text-decoration-none">
                            <?php if ($thumb): ?>
                                <img src="<?php echo html_escape($thumb); ?>" class="card-img-top" alt="<?php echo html_escape($post['title']); ?>" loading="lazy" style="height:200px;object-fit:cover;">
                            <?php else: ?>
                                <div class="card-img-top d-flex align-items-center justify-content-center bg-light text-muted" style="height:200px;font-size:2.5rem;">📰</div>
                            <?php endif; ?>
                        </a>
                        <div class="card-body d-flex flex-column">
                            <?php if (!empty($post['category'])): ?>
                                <span class="badge bg-light text-muted align-self-start mb-2"><?php echo html_escape($post['category']); ?></span>
                            <?php endif; ?>
                            <h2 class="h5 mb-2">
                                <a href="<?php echo $url; ?>" class="text-dark text-decoration-none"><?php echo html_escape($post['title']); ?></a>
                            </h2>
                            <?php if (!empty($post['published_at'])): ?>
                                <div class="text-muted small mb-2"><i class="bi bi-calendar3"></i> <?php echo html_escape(date('d M Y', strtotime($post['published_at']))); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($post['excerpt'])): ?>
                                <p class="text-muted small mb-3"><?php echo html_escape($post['excerpt']); ?></p>
                            <?php endif; ?>
                            <a href="<?php echo $url; ?>" class="btn btn-sm btn-outline-dark mt-auto align-self-start">Read more</a>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($pages > 1): ?>
        <nav class="mt-5">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo base_url('blogs' . ($i > 1 ? '?page=' . $i : '')); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
