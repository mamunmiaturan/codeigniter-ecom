<?php defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Storefront header — a faithful, pixel-accurate copy of the ShopWise theme
 * header (demo/index.html), wired to live cart/catalog data and honest about
 * features that do not exist server-side (no customer accounts, wishlist,
 * store locator or help pages). All visual styling comes from
 * assets/frontend/assets/css/main.css, which head.php already loads globally; the small
 * <style> block below only pins the header (main.css never makes it sticky —
 * that relied on the theme's main.js, which we do NOT load) and provides the
 * dynamic, horizontally-scrollable category strip that replaces the megamenu.
 * Only the Bootstrap 5 bundle is available, so dropdowns and the #mobileSearch
 * collapse work natively; nothing here needs main.js.
 */
$site_name  = get_global_setting('site_name') ?: 'Bazaar';
$search_q   = (string) $this->input->get('search', true);
$is_shop    = ($this->router->method === 'shop');
$active_cat = $is_shop ? trim((string) $this->input->get('category', true)) : null;

// Live mini-cart view-model from Landing::_cart_summary() (defensive defaults
// in case a future controller path renders this partial without it).
$mc          = (isset($mini_cart) && is_array($mini_cart)) ? $mini_cart : [];
$mc_items    = (isset($mc['items']) && is_array($mc['items'])) ? $mc['items'] : [];
$mc_count    = isset($mc['count']) ? (int) $mc['count'] : 0;
$mc_subtotal = isset($mc['subtotal']) ? (float) $mc['subtotal'] : 0.0;
$badge_count = isset($cart_count) ? (int) $cart_count : $mc_count;

// Storefront customer session (null when browsing as a guest).
$cust = function_exists('current_customer') ? current_customer() : null;
?>
<header id="header" class="header">

  <!-- Top Utility Bar (main.css hides this under 768px) -->
  <div class="utility-bar">
    <div class="container-fluid container-xl">
      <div class="row align-items-center">
        <div class="col-auto">
          <div class="utility-links">
            <a href="<?php echo base_url('faqs'); ?>" class="utility-link"><i class="bi bi-question-circle"></i><span>Help</span></a>
            <span class="utility-divider"></span>
            <a href="<?php echo base_url('contact-us'); ?>" class="utility-link"><i class="bi bi-headset"></i><span>Support</span></a>
          </div>
        </div>
        <div class="col text-end">
          <span class="promo-text">Cash on delivery all over Bangladesh &middot; Flat &#2547;60 shipping, free with code FREESHIP</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Header -->
  <div class="main-bar">
    <div class="container-fluid container-xl">
      <div class="row align-items-center gy-2">

        <!-- Logo -->
        <div class="col-auto">
          <a href="<?php echo base_url('/'); ?>" class="logo d-flex align-items-center">
            <img src="<?php echo get_logo_url(); ?>" alt="<?php echo html_escape($site_name); ?>" class="ls-logo-img">
          </a>
        </div>

        <!-- Search (desktop) -->
        <div class="col d-none d-lg-block">
          <form class="search-bar" action="<?php echo base_url('shop'); ?>" method="get" role="search">
            <i class="bi bi-search search-icon"></i>
            <input type="text" class="search-field" name="search" value="<?php echo html_escape($search_q); ?>" placeholder="Search for products, brands, and more..." aria-label="Search products">
            <button class="search-submit" type="submit">Search</button>
          </form>
        </div>

        <!-- Actions -->
        <div class="col-auto ms-auto ms-lg-0">
          <div class="action-group d-flex align-items-center">

            <!-- Mobile Search Toggle (Bootstrap collapse) -->
            <button class="action-btn mobile-search-toggle d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#mobileSearch" aria-expanded="false" aria-controls="mobileSearch" aria-label="Toggle search">
              <i class="bi bi-search"></i>
            </button>

            <!-- Account (honest guest content — no customer accounts server-side) -->
            <div class="dropdown">
              <button class="action-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account">
                <i class="bi bi-person-circle"></i>
              </button>
              <div class="dropdown-menu account-flyout">
                <?php if ($cust): ?>
                  <div class="flyout-header">
                    <h6><?php echo html_escape($cust['name']); ?></h6>
                    <p><?php echo html_escape($cust['email']); ?></p>
                  </div>
                  <div class="flyout-links">
                    <a href="<?php echo base_url('account'); ?>"><i class="bi bi-person"></i><span>My Account</span></a>
                    <a href="<?php echo base_url('account/orders'); ?>"><i class="bi bi-bag-check"></i><span>My Orders</span></a>
                    <a href="<?php echo base_url('account/wishlist'); ?>"><i class="bi bi-heart"></i><span>Wishlist</span></a>
                    <a href="<?php echo base_url('account/logout'); ?>"><i class="bi bi-box-arrow-right"></i><span>Log out</span></a>
                  </div>
                <?php elseif (function_exists('is_loggedin') && is_loggedin()): ?>
                  <!-- Admin/staff session (separate from storefront customers): show
                       useful staff links instead of guest Log in/Register (which bounce
                       an already-logged-in admin back to the dashboard). -->
                  <div class="flyout-header">
                    <h6><?php echo html_escape(get_loggedin_name() ?: 'Staff'); ?></h6>
                    <p>Admin / Staff account</p>
                  </div>
                  <div class="flyout-links">
                    <a href="<?php echo base_url('dashboard'); ?>"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
                    <a href="<?php echo base_url('cart'); ?>"><i class="bi bi-bag"></i><span>My Cart</span></a>
                    <a href="<?php echo base_url('logout'); ?>"><i class="bi bi-box-arrow-right"></i><span>Log out</span></a>
                  </div>
                <?php else: ?>
                  <div class="flyout-header">
                    <h6>Welcome</h6>
                    <p>Log in for faster checkout &amp; order tracking.</p>
                  </div>
                  <div class="flyout-actions">
                    <a href="<?php echo base_url('account/login'); ?>" class="btn btn-primary-action">Log in</a>
                    <a href="<?php echo base_url('account/register'); ?>" class="btn btn-outline-action">Register</a>
                  </div>
                  <div class="flyout-links">
                    <a href="<?php echo base_url('cart'); ?>"><i class="bi bi-bag"></i><span>My Cart</span></a>
                    <a href="<?php echo base_url('login'); ?>"><i class="bi bi-shield-lock"></i><span>Admin / Staff Login</span></a>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Wishlist -->
            <a href="<?php echo base_url('account/wishlist'); ?>" class="action-btn d-none d-md-flex" title="Wishlist" aria-label="Wishlist">
              <i class="bi bi-heart"></i>
            </a>
            <!-- Compare -->
            <a href="<?php echo base_url('compare'); ?>" class="action-btn d-none d-md-flex" title="Compare" aria-label="Compare">
              <i class="bi bi-bar-chart"></i>
            </a>

            <!-- Cart (live mini-cart) -->
            <div class="dropdown">
              <button class="action-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Cart">
                <i class="bi bi-bag"></i>
                <?php if ($badge_count > 0): ?><span class="badge-count"><?php echo $badge_count; ?></span><?php endif; ?>
              </button>
              <div class="dropdown-menu cart-flyout">
                <div class="flyout-top">
                  <h6>Your Bag</h6>
                  <span class="items-label"><?php echo $mc_count; ?> item(s)</span>
                </div>

                <?php if (empty($mc_items)): ?>
                  <div class="flyout-items">
                    <div class="flyout-empty text-center py-4 px-3">
                      <i class="bi bi-bag-x d-block mb-2"></i>
                      <p class="mb-3 text-muted small">Your bag is empty.</p>
                      <a href="<?php echo base_url('shop'); ?>" class="btn btn-dark btn-sm">Start shopping</a>
                    </div>
                  </div>
                <?php else: ?>
                  <div class="flyout-items">
                    <?php foreach ($mc_items as $it): ?>
                      <?php
                        $it_name  = isset($it['name']) ? (string) $it['name'] : '';
                        $it_slug  = isset($it['slug']) ? (string) $it['slug'] : '';
                        $it_image = isset($it['image']) ? (string) $it['image'] : '';
                        $it_price = isset($it['unit_price']) ? (float) $it['unit_price'] : 0.0;
                        $it_qty   = isset($it['quantity']) ? (int) $it['quantity'] : 0;
                      ?>
                      <div class="flyout-item">
                        <div class="flyout-item-thumb">
                          <img src="<?php echo html_escape($it_image); ?>" alt="<?php echo html_escape($it_name); ?>" class="img-fluid" loading="lazy">
                        </div>
                        <div class="flyout-item-details">
                          <h6><a href="<?php echo base_url('product/' . rawurlencode($it_slug)); ?>"><?php echo html_escape($it_name); ?></a></h6>
                          <div class="item-bottom">
                            <span class="item-price"><?php echo html_escape(shop_money($it_price)); ?></span>
                            <span class="item-qty">x<?php echo $it_qty; ?></span>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  <div class="flyout-bottom">
                    <div class="subtotal-row">
                      <span>Subtotal</span>
                      <span class="subtotal-value"><?php echo html_escape(shop_money($mc_subtotal)); ?></span>
                    </div>
                    <a href="<?php echo base_url('checkout'); ?>" class="btn btn-proceed">Proceed to Checkout</a>
                    <a href="<?php echo base_url('cart'); ?>" class="link-viewbag">View full bag &rarr;</a>
                  </div>
                <?php endif; ?>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Category strip (replaces the theme megamenu; horizontally scrollable, no JS) -->
  <div class="nav-strip">
    <div class="container-fluid container-xl">
      <nav class="ls-catnav" aria-label="Product categories">
        <a class="ls-catlink<?php echo ($is_shop && $active_cat === '') ? ' active' : ''; ?>" href="<?php echo base_url('shop'); ?>">
          <i class="bi bi-grid"></i><span>All Products</span>
        </a>
        <?php foreach (($nav_categories ?? []) as $c): ?>
          <?php
            $c_name   = isset($c['name']) ? (string) $c['name'] : '';
            $c_slug   = isset($c['slug']) ? (string) $c['slug'] : '';
            $c_icon   = !empty($c['icon']) ? (string) $c['icon'] : '';
            $children = (isset($c['children']) && is_array($c['children'])) ? $c['children'] : [];
            $child_on = false;
            foreach ($children as $ch) { if ($is_shop && $active_cat === (string) ($ch['slug'] ?? '')) { $child_on = true; break; } }
            $c_active = $child_on || ($is_shop && $c_slug !== '' && $active_cat === $c_slug);
          ?>
          <?php if (!empty($children)): ?>
            <!-- Parent category with a sub-category dropdown -->
            <div class="dropdown ls-catdrop">
              <a class="ls-catlink dropdown-toggle<?php echo $c_active ? ' active' : ''; ?>" href="<?php echo base_url('shop?category=' . urlencode($c_slug)); ?>" data-bs-toggle="dropdown" aria-expanded="false">
                <?php if ($c_icon !== ''): ?><i class="bi bi-<?php echo html_escape($c_icon); ?>"></i><?php endif; ?>
                <span><?php echo html_escape($c_name); ?></span>
              </a>
              <ul class="dropdown-menu ls-catmenu">
                <li>
                  <a class="dropdown-item fw-semibold" href="<?php echo base_url('shop?category=' . urlencode($c_slug)); ?>">
                    <i class="bi bi-grid"></i> All <?php echo html_escape($c_name); ?>
                  </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <?php foreach ($children as $ch): ?>
                  <?php
                    $ch_slug = (string) ($ch['slug'] ?? '');
                    $ch_name = (string) ($ch['name'] ?? '');
                    $ch_icon = !empty($ch['icon']) ? (string) $ch['icon'] : '';
                    $ch_on   = ($is_shop && $active_cat === $ch_slug);
                  ?>
                  <li>
                    <a class="dropdown-item<?php echo $ch_on ? ' active' : ''; ?>" href="<?php echo base_url('shop?category=' . urlencode($ch_slug)); ?>">
                      <?php if ($ch_icon !== ''): ?><i class="bi bi-<?php echo html_escape($ch_icon); ?>"></i><?php endif; ?>
                      <?php echo html_escape($ch_name); ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php else: ?>
            <a class="ls-catlink<?php echo $c_active ? ' active' : ''; ?>" href="<?php echo base_url('shop?category=' . urlencode($c_slug)); ?>">
              <?php if ($c_icon !== ''): ?><i class="bi bi-<?php echo html_escape($c_icon); ?>"></i><?php endif; ?>
              <span><?php echo html_escape($c_name); ?></span>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
      </nav>
    </div>
  </div>

  <!-- Mobile Search (Bootstrap collapse) -->
  <div class="collapse" id="mobileSearch">
    <div class="container-fluid container-xl">
      <form class="mobile-search" action="<?php echo base_url('shop'); ?>" method="get" role="search">
        <div class="mobile-search-inner">
          <i class="bi bi-search"></i>
          <input type="text" class="form-control" name="search" value="<?php echo html_escape($search_q); ?>" placeholder="What are you looking for?" aria-label="Search products">
        </div>
      </form>
    </div>
  </div>

</header>

<style>
  /* main.css styles .header but only gives it a z-index (997) — it is NOT sticky
     (the theme's stickiness came from main.js, which we don't load). Pin it here.
     The theme's Bootstrap `position-relative` class is intentionally dropped from
     the header above, because `.position-relative { position: relative !important }`
     would beat this rule. Nothing in this partial needs it — the badge is
     positioned inside .action-btn and the flyouts inside their .dropdown wrappers. */
  #header { position: sticky; top: 0; z-index: 1020; }

  /* Storefront brand logo (light-theme / colour logo on the white header). */
  .header .logo { text-decoration: none; }
  /* Matches the footer logo height (.ls-foot-logo, 42px). !important needed to beat
     the ShopWise theme's own .header .logo img height rule (which forced ~28px). */
  .header .logo .ls-logo-img { height: 42px !important; max-height: 42px !important; width: auto !important; max-width: 200px !important; object-fit: contain; display: block; margin-right: 0 !important; }
  @media (max-width: 575px) { .header .logo .ls-logo-img { height: 36px !important; max-height: 36px !important; } }

  /* Empty-bag state inside the cart flyout. */
  .cart-flyout .flyout-empty i { font-size: 1.7rem; color: color-mix(in srgb, var(--default-color, #212529), transparent 60%); }

  /* The product name is a real link now, but should look like the theme's plain <h6>. */
  .cart-flyout .flyout-item-details h6 a { color: inherit; text-decoration: none; }
  .cart-flyout .flyout-item-details h6 a:hover { color: var(--accent-color, #4f46e5); }

  /* Honest muted note in the account flyout (a non-navigating <span>, not a fake link). */
  .account-flyout .flyout-note {
    display: block;
    padding: 8px 20px 4px;
    font-size: 12px;
    color: color-mix(in srgb, var(--default-color, #212529), transparent 45%);
  }

  /* Dynamic category strip — replaces the theme's JS megamenu. Lives in the theme's
     .nav-strip band and scrolls horizontally on small screens (no hamburger, no JS). */
  .nav-strip .ls-catnav {
    display: flex;
    align-items: center;
    flex-wrap: nowrap;
    gap: 2px;
    padding: 8px 0;
    /* Single row. Overflow stays visible on desktop so the sub-category
       dropdowns are not clipped; narrow screens fall back to horizontal scroll. */
    overflow: visible;
  }
  @media (max-width: 991px) {
    .nav-strip .ls-catnav { overflow-x: auto; overflow-y: visible; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
    .nav-strip .ls-catnav::-webkit-scrollbar { display: none; }
  }
  .nav-strip .ls-catlink {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    flex: 0 0 auto;
    white-space: nowrap;
    padding: 7px 11px;
    border-radius: 8px;
    font-size: 14.5px;
    font-weight: 500;
    text-decoration: none;
    color: color-mix(in srgb, var(--default-color, #212529), transparent 25%);
    transition: all 0.2s ease;
  }
  .nav-strip .ls-catlink i { font-size: 16px; }
  .nav-strip .ls-catdrop .dropdown-toggle::after { margin-left: 3px; }
  .nav-strip .ls-catlink:hover {
    color: var(--accent-color, #4f46e5);
    background-color: color-mix(in srgb, var(--accent-color, #4f46e5), transparent 92%);
  }
  .nav-strip .ls-catlink.active {
    color: var(--accent-color, #4f46e5);
    background-color: color-mix(in srgb, var(--accent-color, #4f46e5), transparent 88%);
    font-weight: 600;
  }
  .nav-strip .ls-catlink:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent-color, #4f46e5), transparent 85%);
  }

  /* Sub-category dropdown inside the category strip */
  .nav-strip .ls-catdrop { position: relative; flex: 0 0 auto; }
  .nav-strip .ls-catdrop .dropdown-toggle::after { margin-left: 5px; vertical-align: 1px; }
  .nav-strip .ls-catmenu {
    padding: 6px;
    margin-top: 4px;
    border: 1px solid color-mix(in srgb, var(--default-color, #212529), transparent 88%);
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.10);
    min-width: 232px;
  }
  .nav-strip .ls-catmenu .dropdown-item {
    display: flex;
    align-items: center;
    gap: 8px;
    border-radius: 6px;
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 500;
    color: color-mix(in srgb, var(--default-color, #212529), transparent 20%);
  }
  .nav-strip .ls-catmenu .dropdown-item i { font-size: 15px; opacity: .85; }
  .nav-strip .ls-catmenu .dropdown-item:hover,
  .nav-strip .ls-catmenu .dropdown-item.active {
    background-color: color-mix(in srgb, var(--accent-color, #4f46e5), transparent 90%);
    color: var(--accent-color, #4f46e5);
  }
  .nav-strip .ls-catmenu .dropdown-divider { margin: 5px 6px; }
  /* Desktop: reveal on hover as well as click, like a typical category menu. */
  @media (min-width: 992px) {
    .nav-strip .ls-catdrop:hover > .ls-catmenu { display: block; }
  }
</style>
