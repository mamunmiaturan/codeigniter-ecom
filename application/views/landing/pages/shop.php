<?php defined('BASEPATH') or exit('No direct script access allowed');
$heading = $filters['search'] !== ''
    ? 'Search: "' . html_escape($filters['search']) . '"'
    : ($current_category ? html_escape($current_category['name']) : 'All Products');
$facets   = isset($facets) ? $facets : [];
$sel_attr = isset($sel_attr) ? $sel_attr : [];
$has_attr = false;
foreach ($sel_attr as $v) { if (!empty($v)) { $has_attr = true; break; } }
// selected option ids as strings, per attribute code
$sel_of = function ($code) use ($sel_attr) {
    $v = $sel_attr[$code] ?? [];
    return array_map('strval', is_array($v) ? $v : [$v]);
}; ?>

<section class="container-fluid container-xl py-5">
    <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
        <div class="me-auto">
            <h1 class="section-title h4 mb-1"><?php echo $heading; ?></h1>
            <div class="text-muted small"><?php echo (int) $result['total']; ?> product<?php echo $result['total'] === 1 ? '' : 's'; ?> found</div>
        </div>
        <form method="get" action="<?php echo base_url('shop'); ?>" class="d-flex flex-column align-items-start align-items-md-end gap-2">
            <?php if ($filters['category'] !== ''): ?><input type="hidden" name="category" value="<?php echo html_escape($filters['category']); ?>"><?php endif; ?>
            <?php if ($filters['search'] !== ''): ?><input type="hidden" name="search" value="<?php echo html_escape($filters['search']); ?>"><?php endif; ?>
            <?php foreach ($sel_attr as $code => $vals): foreach ((array) $vals as $v): if ($v === '' || $v === null) continue; ?>
                <input type="hidden" name="attr[<?php echo html_escape($code); ?>][]" value="<?php echo html_escape($v); ?>">
            <?php endforeach; endforeach; ?>
            <select name="sort" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">Sort: Featured</option>
                <option value="newest" <?php echo $filters['sort'] === 'newest' ? 'selected' : ''; ?>>Newest</option>
                <option value="price_asc" <?php echo $filters['sort'] === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="price_desc" <?php echo $filters['sort'] === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                <option value="name" <?php echo $filters['sort'] === 'name' ? 'selected' : ''; ?>>Name (A–Z)</option>
            </select>
            <label class="small text-nowrap" style="cursor:pointer;">
                <input type="checkbox" name="in_stock" value="1" <?php echo !empty($filters['in_stock']) ? 'checked' : ''; ?> onchange="this.form.submit()"> In stock only
            </label>
        </form>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="<?php echo base_url('shop'); ?>" class="btn btn-sm <?php echo $filters['category'] === '' ? 'btn-dark' : 'btn-outline-secondary'; ?>">All</a>
        <?php foreach ($categories as $c): ?>
            <a href="<?php echo base_url('shop?category=' . urlencode($c['slug'])); ?>"
               class="btn btn-sm <?php echo $filters['category'] === $c['slug'] ? 'btn-dark' : 'btn-outline-secondary'; ?>"><?php echo html_escape($c['name']); ?></a>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <!-- Facet sidebar -->
        <aside class="col-lg-3">
            <?php if (!empty($facets)): ?>
                <form method="get" action="<?php echo base_url('shop'); ?>" id="facet-form" class="card shadow-sm">
                    <div class="card-body">
                        <?php if ($filters['category'] !== ''): ?><input type="hidden" name="category" value="<?php echo html_escape($filters['category']); ?>"><?php endif; ?>
                        <?php if ($filters['search'] !== ''): ?><input type="hidden" name="search" value="<?php echo html_escape($filters['search']); ?>"><?php endif; ?>
                        <?php if ($filters['sort'] !== ''): ?><input type="hidden" name="sort" value="<?php echo html_escape($filters['sort']); ?>"><?php endif; ?>
                        <?php if (!empty($filters['in_stock'])): ?><input type="hidden" name="in_stock" value="1"><?php endif; ?>
                        <div class="d-flex align-items-center mb-2">
                            <h2 class="h6 fw-bold mb-0 me-auto">Filters</h2>
                            <?php if ($has_attr): ?>
                                <a href="<?php echo base_url('shop' . ($filters['category'] !== '' ? '?category=' . urlencode($filters['category']) : '')); ?>" class="small text-decoration-none text-danger">Clear</a>
                            <?php endif; ?>
                        </div>
                        <?php foreach ($facets as $f): $sel = $sel_of($f['code']); ?>
                            <div class="mb-3">
                                <div class="fw-semibold small text-uppercase text-muted mb-1"><?php echo html_escape($f['name']); ?></div>
                                <?php foreach ($f['options'] as $o):
                                    $checked = in_array((string) $o['id'], $sel, true); ?>
                                    <label class="d-flex align-items-center mb-1" style="gap:6px;cursor:pointer;">
                                        <input type="checkbox" name="attr[<?php echo html_escape($f['code']); ?>][]" value="<?php echo (int) $o['id']; ?>" <?php echo $checked ? 'checked' : ''; ?> onchange="document.getElementById('facet-form').submit()">
                                        <?php if (!empty($o['swatch']) && preg_match('/^#[0-9a-fA-F]{6}$/', $o['swatch'])): ?>
                                            <span style="display:inline-block;width:14px;height:14px;border-radius:3px;border:1px solid #ccc;background:<?php echo html_escape($o['swatch']); ?>;"></span>
                                        <?php endif; ?>
                                        <span class="small flex-grow-1"><?php echo html_escape($o['label']); ?></span>
                                        <span class="badge bg-light text-muted"><?php echo (int) $o['count']; ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                        <noscript><button type="submit" class="btn btn-dark btn-sm w-100">Apply</button></noscript>
                    </div>
                </form>
            <?php endif; ?>
        </aside>

        <!-- Product grid -->
        <div class="col-lg-<?php echo !empty($facets) ? '9' : '12'; ?>">
            <div class="row g-4">
                <?php if (empty($result['items'])): ?>
                    <div class="col-12 text-center py-5">
                        <div style="font-size:3rem;">🔍</div>
                        <p class="text-muted mb-3">No products match your filters.</p>
                        <a href="<?php echo base_url('shop'); ?>" class="btn btn-dark">Browse all products</a>
                    </div>
                <?php else: foreach ($result['items'] as $p) { $this->load->view('landing/partials/product_card', ['p' => $p]); } endif; ?>
            </div>

            <?php if ($result['pages'] > 1): ?>
            <nav class="mt-5">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $result['pages']; $i++):
                        $q = array_filter([
                            'category' => $filters['category'],
                            'search'   => $filters['search'],
                            'sort'     => $filters['sort'],
                            'in_stock' => $filters['in_stock'],
                            'attr'     => $sel_attr,
                            'page'     => $i,
                        ], function ($v) { return $v !== '' && $v !== null && $v !== []; }); ?>
                        <li class="page-item <?php echo $i === $result['page'] ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo base_url('shop?' . http_build_query($q)); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</section>
