<?php defined('BASEPATH') or exit('No direct script access allowed');
// Group active FAQs by category, preserving the sort order they arrive in.
$groups = [];
foreach (($faqs ?? []) as $f) {
    $cat = trim((string) ($f['category'] ?? '')) ?: 'General';
    $groups[$cat][] = $f;
}
$gi = 0;
?>
<section class="container-fluid container-xl py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <nav class="small mb-3">
                <a href="<?php echo base_url('/'); ?>" class="text-muted text-decoration-none">Home</a>
                <span class="text-muted mx-1">/</span><span>FAQs</span>
            </nav>
            <h1 class="h3 fw-bold mb-4">Frequently Asked Questions</h1>

            <?php if (empty($groups)): ?>
                <p class="text-muted">No FAQs are available at the moment.</p>
            <?php else: ?>
                <?php foreach ($groups as $category => $items): ?>
                    <h2 class="h5 fw-bold mt-4 mb-3"><?php echo html_escape($category); ?></h2>
                    <div class="accordion mb-4" id="faqAccordion<?php echo $gi; ?>">
                        <?php foreach ($items as $k => $f): $cid = $gi . '_' . $k; ?>
                            <div class="accordion-item">
                                <h3 class="accordion-header" id="faqHeading<?php echo $cid; ?>">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse<?php echo $cid; ?>" aria-expanded="false" aria-controls="faqCollapse<?php echo $cid; ?>">
                                        <?php echo html_escape($f['question']); ?>
                                    </button>
                                </h3>
                                <div id="faqCollapse<?php echo $cid; ?>" class="accordion-collapse collapse" aria-labelledby="faqHeading<?php echo $cid; ?>" data-bs-parent="#faqAccordion<?php echo $gi; ?>">
                                    <div class="accordion-body">
                                        <?php echo $f['answer']; // admin-authored HTML ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php $gi++; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
