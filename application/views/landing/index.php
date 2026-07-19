<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
// Homepage hero: live sliders from the Banners module, with a graceful fallback
// to a modern static hero when no active slider banners exist.
$sliders = (isset($sliders) && is_array($sliders)) ? $sliders : [];
$promos  = (isset($promos) && is_array($promos)) ? $promos : [];
$site_name = get_global_setting('site_name') ?: 'our store';

// Top categories for hero quick-links + the category showcase.
$home_cats = !empty($featured_categories) ? $featured_categories : ($categories ?? []);
$hero_chips = array_slice($home_cats, 0, 6);
?>

<?php if (!empty($sliders)): ?>
<!-- Hero slider (Banners module) -->
<section class="ls-hero-slider">
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="2000">
        <?php if (count($sliders) > 1): ?>
        <div class="carousel-indicators">
            <?php foreach ($sliders as $i => $b): ?>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?php echo $i; ?>" class="<?php echo $i === 0 ? 'active' : ''; ?>" aria-label="Slide <?php echo $i + 1; ?>"<?php echo $i === 0 ? ' aria-current="true"' : ''; ?>></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="carousel-inner">
            <?php foreach ($sliders as $i => $b): ?>
                <?php
                    $link = trim((string) ($b['link_url'] ?? ''));
                    $href = $link === '' ? base_url('shop') : (strpos($link, 'http') === 0 ? $link : base_url($link));
                    $img  = !empty($b['image']) ? base_url('uploads/banner/' . rawurlencode($b['image'])) : '';
                    $btn  = trim((string) ($b['button_text'] ?? ''));
                ?>
                <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
                    <div class="ls-hero ls-hero2<?php echo $img ? ' ls-hero--img' : ''; ?>"<?php echo $img ? ' style="background-image:linear-gradient(90deg,rgba(6,20,25,.75),rgba(6,20,25,.25)),url(\'' . html_escape($img) . '\');"' : ''; ?>>
                        <div class="container-fluid container-xl">
                            <div class="ls-hero-inner">
                                <?php if (!empty($b['title'])): ?><h1 class="ls-hero-title"><?php echo html_escape($b['title']); ?></h1><?php endif; ?>
                                <?php if (!empty($b['subtitle'])): ?><p class="ls-hero-sub"><?php echo html_escape($b['subtitle']); ?></p><?php endif; ?>
                                <a href="<?php echo $href; ?>" class="btn ls-hero-btn"><?php echo $btn !== '' ? html_escape($btn) : 'Shop now'; ?> <i class="bi bi-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($sliders) > 1): ?>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span>
        </button>
        <?php endif; ?>
    </div>
</section>
<?php else: ?>
<!-- Hero (modern static fallback — shown when no active slider banners) -->
<section class="ls-hero ls-hero2">
    <div class="container-fluid container-xl">
        <div class="ls-hero-inner">
            <span class="ls-hero-badge"><i class="bi bi-truck"></i> Cash on Delivery · All Bangladesh</span>
            <?php $__ht = (string) get_global_setting('landing_hero_title'); $__hs = (string) get_global_setting('landing_hero_subtitle'); ?>
            <h1 class="ls-hero-title"><?php echo $__ht !== '' ? html_escape($__ht) : 'Fashion for every mom &amp; little one.'; ?></h1>
            <p class="ls-hero-sub"><?php echo $__hs !== '' ? html_escape($__hs) : 'Sarees, kurtis, kids&rsquo; wear and more &mdash; real products at honest prices. Use code <b>SAVE10</b> for 10% off your first order.'; ?></p>
            <div class="ls-hero-cta">
                <a href="<?php echo base_url('shop'); ?>" class="btn ls-hero-btn">Start shopping <i class="bi bi-arrow-right ms-1"></i></a>
                <a href="<?php echo base_url('shop?category=womens-clothing'); ?>" class="btn ls-hero-btn-ghost">Women&rsquo;s Clothing</a>
            </div>
            <?php if ($hero_chips): ?>
            <div class="ls-hero-chips">
                <?php foreach ($hero_chips as $hc): ?>
                    <a href="<?php echo base_url('shop?category=' . urlencode((string) $hc['slug'])); ?>"><?php echo html_escape((string) $hc['name']); ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Trust / features bar -->
<section class="ls-trust">
    <div class="container-fluid container-xl">
        <div class="ls-trust-grid">
            <div class="ls-trust-item"><i class="bi bi-truck"></i><div><strong>Nationwide Delivery</strong><span>Fast shipping across Bangladesh</span></div></div>
            <div class="ls-trust-item"><i class="bi bi-cash-coin"></i><div><strong>Cash on Delivery</strong><span>Pay when it arrives</span></div></div>
            <div class="ls-trust-item"><i class="bi bi-arrow-repeat"></i><div><strong>Easy Returns</strong><span>Hassle-free 7-day returns</span></div></div>
            <div class="ls-trust-item"><i class="bi bi-headset"></i><div><strong>Friendly Support</strong><span>We&rsquo;re here to help</span></div></div>
        </div>
    </div>
</section>

<!-- Shop by Category -->
<?php if (!empty($home_cats)): ?>
<section class="container-fluid container-xl pt-4 pb-2">
    <div class="ls-sec-head">
        <div><span class="ls-sec-eyebrow">Browse</span><h2 class="ls-sec-title">Shop by Category</h2></div>
        <a href="<?php echo base_url('shop'); ?>" class="ls-sec-link">All products <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-3">
        <?php foreach (array_slice($home_cats, 0, 12) as $c): ?>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?php echo base_url('shop?category=' . urlencode((string) $c['slug'])); ?>" class="ls-cat-card">
                    <span class="ls-cat-ic"><i class="bi bi-<?php echo !empty($c['icon']) ? html_escape($c['icon']) : 'grid-3x3-gap'; ?>"></i></span>
                    <span class="ls-cat-name"><?php echo html_escape((string) $c['name']); ?></span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Flash Sale -->
<?php $this->load->view('landing/pages/flash_sale_section'); ?>

<!-- Promo banners -->
<?php if (!empty($promos)): ?>
<section class="container-fluid container-xl py-4">
    <div class="row g-4">
        <?php foreach (array_slice($promos, 0, 2) as $pb): ?>
            <?php
                $plink = trim((string) ($pb['link_url'] ?? ''));
                $phref = $plink === '' ? base_url('shop') : (strpos($plink, 'http') === 0 ? $plink : base_url($plink));
                $pimg  = !empty($pb['image']) ? base_url('uploads/banner/' . rawurlencode($pb['image'])) : '';
                $pbtn  = trim((string) ($pb['button_text'] ?? ''));
            ?>
            <div class="col-md-6">
                <a href="<?php echo $phref; ?>" class="ls-promo"<?php echo $pimg ? ' style="background-image:linear-gradient(90deg,rgba(6,20,25,.72),rgba(6,20,25,.15)),url(\'' . html_escape($pimg) . '\');"' : ''; ?>>
                    <div class="ls-promo-body">
                        <?php if (!empty($pb['title'])): ?><h3><?php echo html_escape($pb['title']); ?></h3><?php endif; ?>
                        <?php if (!empty($pb['subtitle'])): ?><p><?php echo html_escape($pb['subtitle']); ?></p><?php endif; ?>
                        <span class="ls-promo-btn"><?php echo $pbtn !== '' ? html_escape($pbtn) : 'Shop now'; ?> <i class="bi bi-arrow-right"></i></span>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Featured Products -->
<section class="container-fluid container-xl py-4">
    <div class="ls-sec-head">
        <div><span class="ls-sec-eyebrow">Handpicked</span><h2 class="ls-sec-title">Featured Products</h2></div>
        <a href="<?php echo base_url('shop'); ?>" class="ls-sec-link">View all <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-4">
        <?php if (empty($featured)): ?>
            <div class="col-12 text-muted">No featured products yet.</div>
        <?php else: foreach ($featured as $p) { $this->load->view('landing/partials/product_card', ['p' => $p]); } endif; ?>
    </div>
</section>

<!-- Best sellers -->
<?php if (!empty($best_selling)): ?>
<section class="container-fluid container-xl py-4">
    <div class="ls-sec-head">
        <div><span class="ls-sec-eyebrow">Popular</span><h2 class="ls-sec-title">Best Sellers</h2></div>
        <a href="<?php echo base_url('shop'); ?>" class="ls-sec-link">View all <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-4">
        <?php foreach ($best_selling as $p) { $this->load->view('landing/partials/product_card', ['p' => $p]); } ?>
    </div>
</section>
<?php endif; ?>

<!-- New arrivals -->
<?php if (!empty($latest)): ?>
<section class="container-fluid container-xl py-4">
    <div class="ls-sec-head">
        <div><span class="ls-sec-eyebrow">Just in</span><h2 class="ls-sec-title">New Arrivals</h2></div>
        <a href="<?php echo base_url('shop?sort=newest'); ?>" class="ls-sec-link">View all <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-4">
        <?php foreach ($latest as $p) { $this->load->view('landing/partials/product_card', ['p' => $p]); } ?>
    </div>
</section>
<?php endif; ?>

<!-- Featured Brands -->
<?php if (!empty($featured_brands)): ?>
<section class="container-fluid container-xl py-4">
    <div class="ls-sec-head">
        <div><span class="ls-sec-eyebrow">Trusted</span><h2 class="ls-sec-title">Featured Brands</h2></div>
    </div>
    <div class="row g-3 align-items-center">
        <?php foreach ($featured_brands as $b): ?>
            <div class="col-4 col-md-2 text-center">
                <a href="<?php echo base_url('shop?search=' . urlencode($b['name'])); ?>" class="ls-brand" title="<?php echo html_escape($b['name']); ?>">
                    <?php if (!empty($b['logo'])): ?>
                        <img src="<?php echo base_url('uploads/catalog/brand/' . rawurlencode($b['logo'])); ?>" alt="<?php echo html_escape($b['name']); ?>">
                    <?php else: ?>
                        <span class="fw-semibold text-dark small"><?php echo html_escape($b['name']); ?></span>
                    <?php endif; ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Benefits band -->
<section class="ls-benefits">
    <div class="container-fluid container-xl">
        <h2 class="ls-benefits-title">Why shop with <?php echo html_escape($site_name); ?>?</h2>
        <div class="row g-4 mt-1">
            <div class="col-md-4"><div class="ls-benefit"><i class="bi bi-patch-check"></i><h3>Real products, honest prices</h3><p>Every item is genuine and fairly priced &mdash; no surprises at checkout.</p></div></div>
            <div class="col-md-4"><div class="ls-benefit"><i class="bi bi-shield-lock"></i><h3>Shop with confidence</h3><p>Secure accounts, order tracking and cash on delivery nationwide.</p></div></div>
            <div class="col-md-4"><div class="ls-benefit"><i class="bi bi-heart"></i><h3>Made for moms &amp; kids</h3><p>A curated range of women&rsquo;s and children&rsquo;s fashion, all in one place.</p></div></div>
        </div>
    </div>
</section>

<style>
    /* ---- Hero ---- */
    .ls-hero2 { background: linear-gradient(120deg, var(--accent-color) 0%, color-mix(in srgb, var(--accent-color), black 15%) 55%, color-mix(in srgb, var(--accent-color), black 28%) 100%); color: #fff; border-radius: 0; overflow: hidden; min-height: 440px; display: flex; align-items: center; }
    .ls-hero2.ls-hero--img { background-size: cover; background-position: center; }
    .ls-hero-slider .carousel-item { min-height: 440px; }
    .ls-hero-inner { max-width: 640px; padding: 1.5rem 0; width: 100%; }
    @media (max-width: 767px) { .ls-hero2, .ls-hero-slider .carousel-item { min-height: 340px; } }
    .ls-hero-badge { display: inline-flex; align-items: center; gap: 7px; background: rgba(255,255,255,.15); color: #fff; padding: 6px 14px; border-radius: 100px; font-size: 13px; font-weight: 600; margin-bottom: 1.1rem; }
    .ls-hero2 .ls-hero-title { color: #fff !important; font-weight: 800; font-size: clamp(2rem, 4.5vw, 3.25rem); line-height: 1.08; letter-spacing: -1px; margin-bottom: 1rem; }
    .ls-hero2 .ls-hero-sub { color: #fff; font-size: 1.08rem; line-height: 1.6; opacity: .92; margin-bottom: 1.6rem; }
    .ls-hero-cta { display: flex; flex-wrap: wrap; gap: .75rem; }
    .ls-hero-btn { background: #fff; color: color-mix(in srgb, var(--accent-color), black 15%); font-weight: 700; padding: .7rem 1.6rem; border-radius: 100px; border: none; }
    .ls-hero-btn:hover { background: #f1f5f9; color: color-mix(in srgb, var(--accent-color), black 15%); }
    .ls-hero-btn-ghost { background: transparent; color: #fff; font-weight: 700; padding: .7rem 1.5rem; border-radius: 100px; border: 1.5px solid rgba(255,255,255,.55); }
    .ls-hero-btn-ghost:hover { background: rgba(255,255,255,.12); color: #fff; }
    .ls-hero-chips { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1.6rem; }
    .ls-hero-chips a { font-size: 13px; color: #fff; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2); padding: 5px 13px; border-radius: 100px; text-decoration: none; transition: .15s; }
    .ls-hero-chips a:hover { background: rgba(255,255,255,.25); }
    .ls-hero-slider .carousel-control-prev, .ls-hero-slider .carousel-control-next { width: 6%; }

    /* The ShopWise theme sets `section { padding: 60px 0 }` on every <section>;
       neutralise it for the hero + trust band so they sit flush (their inner
       elements handle spacing) and the big vertical gaps disappear. */
    .ls-hero-slider, section.ls-hero, .ls-trust { padding-top: 0 !important; padding-bottom: 0 !important; }

    /* ---- Trust bar ---- */
    .ls-trust { background: #fff; border-bottom: 1px solid #eef0f3; }
    .ls-trust-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; padding: 1.4rem 0; }
    .ls-trust-item { display: flex; align-items: center; gap: 12px; }
    .ls-trust-item i { font-size: 1.7rem; color: var(--accent-color, #0d9488); flex: 0 0 auto; }
    .ls-trust-item strong { display: block; font-size: 14px; color: #1f2937; }
    .ls-trust-item span { font-size: 12.5px; color: #6b7280; }
    @media (max-width: 767px) { .ls-trust-grid { grid-template-columns: repeat(2, 1fr); gap: 1.1rem; } }

    /* ---- Section headers ---- */
    .ls-sec-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; }
    .ls-sec-eyebrow { display: block; font-size: 12px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: var(--accent-color, #0d9488); margin-bottom: 2px; }
    .ls-sec-title { font-weight: 800; letter-spacing: -.5px; font-size: 1.5rem; margin: 0; color: #111827; }
    .ls-sec-link { font-weight: 600; font-size: 14px; color: #374151; text-decoration: none; white-space: nowrap; }
    .ls-sec-link:hover { color: var(--accent-color, #0d9488); }
    .ls-sec-link i { transition: transform .15s; }
    .ls-sec-link:hover i { transform: translateX(3px); }

    /* ---- Category cards ---- */
    .ls-cat-card { display: flex; flex-direction: column; align-items: center; gap: .6rem; background: #fff; border: 1px solid #eceef1; border-radius: 16px; padding: 1.3rem .5rem; text-align: center; text-decoration: none; transition: .18s; height: 100%; }
    .ls-cat-card:hover { border-color: transparent; box-shadow: 0 10px 26px rgba(13,148,136,.14); transform: translateY(-3px); }
    .ls-cat-ic { width: 54px; height: 54px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: color-mix(in srgb, var(--accent-color, #0d9488), transparent 88%); color: var(--accent-color, #0d9488); font-size: 1.5rem; transition: .18s; }
    .ls-cat-card:hover .ls-cat-ic { background: var(--accent-color, #0d9488); color: #fff; }
    .ls-cat-name { font-weight: 600; color: #1f2937; font-size: .9rem; }

    /* ---- Promo banners ---- */
    .ls-promo { display: block; min-height: 190px; border-radius: 18px; overflow: hidden; text-decoration: none; background: linear-gradient(120deg, #0f766e, #115e59); background-size: cover; background-position: center; position: relative; }
    .ls-promo-body { padding: 2rem; color: #fff; max-width: 70%; }
    .ls-promo-body h3 { font-weight: 800; font-size: 1.4rem; margin-bottom: .35rem; }
    .ls-promo-body p { opacity: .9; margin-bottom: .9rem; }
    .ls-promo-btn { display: inline-flex; align-items: center; gap: 6px; background: #fff; color: #0f766e; font-weight: 700; padding: .5rem 1.1rem; border-radius: 100px; font-size: 14px; }

    /* ---- Brands ---- */
    .ls-brand { display: flex; align-items: center; justify-content: center; height: 74px; padding: .5rem; border: 1px solid #eceef1; border-radius: 14px; background: #fff; text-decoration: none; transition: .16s; }
    .ls-brand:hover { border-color: color-mix(in srgb, var(--accent-color, #0d9488), transparent 60%); box-shadow: 0 6px 16px rgba(20,20,50,.06); }
    .ls-brand img { max-height: 46px; max-width: 100%; object-fit: contain; filter: grayscale(1); opacity: .8; transition: .16s; }
    .ls-brand:hover img { filter: none; opacity: 1; }

    /* ---- Benefits band ---- */
    .ls-benefits { background: linear-gradient(120deg, var(--accent-color) 0%, color-mix(in srgb, var(--accent-color), black 18%) 55%, color-mix(in srgb, var(--accent-color), black 34%) 100%); color: rgba(255,255,255,.85); margin-top: 3rem; padding: 3.5rem 0; }
    .ls-benefits-title { color: #fff; font-weight: 800; letter-spacing: -.5px; text-align: center; }
    .ls-benefit { text-align: center; padding: 1rem; }
    .ls-benefit i { font-size: 2rem; color: #fff; }
    .ls-benefit h3 { color: #fff; font-size: 1.05rem; font-weight: 700; margin: .8rem 0 .4rem; }
    .ls-benefit p { font-size: 14px; color: rgba(255,255,255,.8); margin: 0; }
</style>
