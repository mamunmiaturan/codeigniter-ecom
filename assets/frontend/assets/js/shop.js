/**
 * ShopWise storefront — live API integration layer.
 * Drives the demo theme (BootstrapMade ShopWise) off the CI3 /api/v1 backend.
 * Keeps the theme header/footer/CSS; renders content into <main class="main">.
 */
(function () {
  "use strict";
  const API = location.origin + '/api/v1';
  const IMG_FALLBACK = 'assets/img/product/product-1.webp';

  // ---------- helpers ----------
  const money = v => '৳' + Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const qs = k => new URLSearchParams(location.search).get(k) || '';

  const auth = {
    get token() { try { return (JSON.parse(localStorage.getItem('bazaar_auth') || 'null') || {}).token || null; } catch (e) { return null; } },
    get name() { try { return (JSON.parse(localStorage.getItem('bazaar_auth') || 'null') || {}).name || ''; } catch (e) { return ''; } },
    set(o) { localStorage.setItem('bazaar_auth', JSON.stringify(o)); },
    clear() { localStorage.removeItem('bazaar_auth'); }
  };

  async function api(path, { method = 'GET', body = null, cart = false } = {}) {
    const h = { 'Accept': 'application/json' };
    if (body) h['Content-Type'] = 'application/json';
    if (auth.token) h['Authorization'] = 'Bearer ' + auth.token;
    else if (cart) { const ct = localStorage.getItem('cart_token'); if (ct) h['X-Cart-Token'] = ct; }
    const res = await fetch(API + path, { method, headers: h, body: body ? JSON.stringify(body) : null });
    let j = {}; try { j = await res.json(); } catch (e) {}
    if (!res.ok || j.status === 'error') throw new Error(j.message || ('Request failed (' + res.status + ')'));
    return j.data;
  }
  const keepToken = d => { if (d && d.cart_token) localStorage.setItem('cart_token', d.cart_token); return d; };

  function toast(msg, err) {
    let box = document.getElementById('sw-toasts');
    if (!box) { box = document.createElement('div'); box.id = 'sw-toasts'; box.style.cssText = 'position:fixed;right:16px;bottom:16px;z-index:3000;display:flex;flex-direction:column;gap:8px'; document.body.appendChild(box); }
    const t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = 'background:' + (err ? '#dc3545' : '#212529') + ';color:#fff;padding:10px 16px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.2);font-size:14px;max-width:320px';
    box.appendChild(t);
    setTimeout(() => t.remove(), 2600);
  }

  const main = () => document.querySelector('main.main');
  const page = (location.pathname.split('/').pop() || 'index.html').toLowerCase();

  // ---------- header cart badge ----------
  function cartBadges() {
    const out = [];
    document.querySelectorAll('.bi-bag, .bi-bag-fill, .bi-cart, .bi-cart2, .bi-cart3').forEach(i => {
      const host = i.closest('a,button'); if (!host) return;
      const b = host.querySelector('.badge-count'); if (b) out.push(b);
    });
    return out;
  }
  async function refreshBadge() {
    try { const c = keepToken(await api('/cart', { cart: true })); cartBadges().forEach(b => { b.textContent = c.item_count; b.style.display = c.item_count > 0 ? '' : 'none'; }); }
    catch (e) {}
  }

  // ---------- product card (theme markup) ----------
  function card(p) {
    const sale = p.pricing.on_sale;
    const badge = sale ? `<div class="badge-label discount">-${p.pricing.discount_pct}%</div>`
      : (p.is_featured ? `<div class="badge-label">Featured</div>` : '');
    const priceHtml = sale
      ? `<div class="product-price">${money(p.pricing.effective_price)} <del style="opacity:.5;font-weight:400;font-size:.8em">${money(p.pricing.price)}</del></div>`
      : `<div class="product-price">${money(p.pricing.effective_price)}</div>`;
    const url = 'product-details.html?slug=' + encodeURIComponent(p.slug);
    return `<div class="col-lg-3 col-md-6">
      <div class="product-card">
        <div class="product-media">
          <a href="${url}"><img src="${p.thumbnail || IMG_FALLBACK}" alt="${esc(p.name)}" class="img-fluid" loading="lazy"></a>
          ${badge}
        </div>
        <div class="product-body">
          <div class="product-meta"><span class="category-tag">${esc(p.category ? p.category.name : '')}</span></div>
          <h4 class="product-title"><a href="${url}">${esc(p.name)}</a></h4>
          <div class="product-footer">
            ${priceHtml}
            <a href="#" class="cart-btn" data-add="${p.id}" aria-label="Add to cart"><i class="bi bi-bag-plus"></i></a>
          </div>
        </div>
      </div>
    </div>`;
  }

  // global add-to-cart (delegated) for any [data-add]
  document.addEventListener('click', async e => {
    const btn = e.target.closest('[data-add]');
    if (!btn) return;
    e.preventDefault();
    try { keepToken(await api('/cart/add', { method: 'POST', cart: true, body: { product_id: parseInt(btn.getAttribute('data-add'), 10), quantity: 1 } })); toast('Added to cart'); refreshBadge(); }
    catch (err) { toast(err.message, true); }
  });

  // ---------- HOME: fill #shop-grid ----------
  async function initHome() {
    const grid = document.getElementById('shop-grid');
    if (!grid) return;
    grid.innerHTML = '<div class="col-12 text-center py-5 text-muted">Loading products…</div>';
    try {
      const d = await api('/products?per_page=12');
      grid.innerHTML = d.items.map(card).join('') || '<div class="col-12 text-center py-5">No products.</div>';
    } catch (e) { grid.innerHTML = '<div class="col-12 text-center py-5 text-danger">Failed to load products.</div>'; }
  }

  // ---------- CATEGORY / listing ----------
  async function initCategory() {
    const el = main(); if (!el) return;
    const state = { search: qs('search'), category: qs('category'), page: 1 };
    el.innerHTML = `<div class="container py-5">
      <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
        <h1 class="h3 mb-0 me-auto">${state.search ? 'Search: ' + esc(state.search) : (state.category ? 'Category' : 'All Products')}</h1>
        <div class="d-flex gap-2" id="cat-chips"></div>
      </div>
      <div class="row g-4" id="cat-grid"></div>
      <div class="text-center mt-4"><button class="btn btn-outline-secondary d-none" id="cat-more">Load more</button></div>
    </div>`;
    const grid = document.getElementById('cat-grid');
    const more = document.getElementById('cat-more');
    try {
      const cats = await api('/categories');
      document.getElementById('cat-chips').innerHTML =
        `<a href="category.html" class="btn btn-sm ${state.category ? 'btn-outline-secondary' : 'btn-dark'}">All</a>` +
        cats.items.map(c => `<a href="category.html?category=${encodeURIComponent(c.slug)}" class="btn btn-sm ${state.category === c.slug ? 'btn-dark' : 'btn-outline-secondary'}">${esc(c.name)}</a>`).join('');
    } catch (e) {}
    async function load(reset) {
      if (reset) { state.page = 1; grid.innerHTML = '<div class="col-12 text-center py-5 text-muted">Loading…</div>'; }
      const p = new URLSearchParams({ page: state.page, per_page: 12 });
      if (state.search) p.set('search', state.search);
      if (state.category) p.set('category', state.category);
      try {
        const d = await api('/products?' + p.toString());
        const html = d.items.map(card).join('');
        grid.innerHTML = reset ? (html || '<div class="col-12 text-center py-5">No products found.</div>') : grid.innerHTML + html;
        more.classList.toggle('d-none', !d.pagination.has_more);
        if (d.pagination.has_more) state.page++;
      } catch (e) { grid.innerHTML = '<div class="col-12 text-danger text-center py-5">' + esc(e.message) + '</div>'; }
    }
    more.addEventListener('click', () => load(false));
    load(true);
  }

  // ---------- PRODUCT DETAILS ----------
  async function initProduct() {
    const el = main(); if (!el) return;
    const slug = qs('slug');
    el.innerHTML = '<div class="container py-5 text-center text-muted">Loading…</div>';
    if (!slug) { el.innerHTML = '<div class="container py-5 text-center">No product selected. <a href="category.html">Browse products</a></div>'; return; }
    let p;
    try { p = await api('/products/' + encodeURIComponent(slug)); }
    catch (e) { el.innerHTML = '<div class="container py-5 text-center">Product not found. <a href="category.html">Back to shop</a></div>'; return; }
    const sale = p.pricing.on_sale;
    const gallery = (p.images && p.images.length ? p.images.map(i => i.url) : [p.thumbnail || IMG_FALLBACK]);
    el.innerHTML = `<div class="container py-5">
      <nav class="mb-3"><a href="index.html" class="text-muted text-decoration-none">Home</a> / <a href="category.html" class="text-muted text-decoration-none">Shop</a> / <span>${esc(p.name)}</span></nav>
      <div class="row g-5">
        <div class="col-lg-6">
          <div class="border rounded-4 overflow-hidden bg-light"><img id="pd-img" src="${gallery[0]}" alt="${esc(p.name)}" class="img-fluid w-100" style="object-fit:cover"></div>
        </div>
        <div class="col-lg-6">
          <span class="badge bg-light text-dark mb-2">${esc(p.category ? p.category.name : 'Product')}</span>
          <h1 class="h3 fw-bold">${esc(p.name)}</h1>
          <div class="my-3">
            <span class="h3 fw-bold text-success">${money(p.pricing.effective_price)}</span>
            ${sale ? `<del class="text-muted ms-2">${money(p.pricing.price)}</del> <span class="badge bg-danger ms-1">-${p.pricing.discount_pct}%</span>` : ''}
          </div>
          <p class="${p.stock.status === 'out_of_stock' ? 'text-danger' : 'text-success'} mb-3">${p.stock.status === 'out_of_stock' ? 'Out of stock' : (p.stock.status === 'pre_order' ? 'Available for pre-order' : 'In stock (' + p.stock.quantity + ')')}</p>
          <p class="text-secondary">${esc(p.short_description || p.description || '')}</p>
          ${p.sku ? `<p class="small text-muted">SKU: ${esc(p.sku)}${p.brand ? ' · Brand: ' + esc(p.brand.name) : ''}</p>` : ''}
          <div class="d-flex align-items-center gap-3 mt-4">
            <div class="input-group" style="width:140px">
              <button class="btn btn-outline-secondary" type="button" id="pd-dec">−</button>
              <input id="pd-qty" type="number" class="form-control text-center" value="1" min="1">
              <button class="btn btn-outline-secondary" type="button" id="pd-inc">+</button>
            </div>
            <button class="btn btn-dark px-4" id="pd-add" ${p.stock.status === 'out_of_stock' ? 'disabled' : ''}><i class="bi bi-bag-plus me-1"></i> Add to cart</button>
            <a class="btn btn-success px-4" id="pd-buy" href="#">Buy now</a>
          </div>
        </div>
      </div>
    </div>`;
    const qty = document.getElementById('pd-qty');
    document.getElementById('pd-dec').onclick = () => qty.value = Math.max(1, parseInt(qty.value || 1) - 1);
    document.getElementById('pd-inc').onclick = () => qty.value = parseInt(qty.value || 1) + 1;
    async function add() { keepToken(await api('/cart/add', { method: 'POST', cart: true, body: { product_id: p.id, quantity: Math.max(1, parseInt(qty.value || 1)) } })); refreshBadge(); }
    document.getElementById('pd-add').onclick = async () => { try { await add(); toast('Added to cart'); } catch (e) { toast(e.message, true); } };
    document.getElementById('pd-buy').onclick = async e => { e.preventDefault(); try { await add(); location.href = 'cart.html'; } catch (err) { toast(err.message, true); } };
  }

  // ---------- CART ----------
  async function renderCart() {
    const el = main(); if (!el) return;
    let c; try { c = keepToken(await api('/cart', { cart: true })); } catch (e) { el.innerHTML = '<div class="container py-5 text-danger">' + esc(e.message) + '</div>'; return; }
    if (!c.items.length) { el.innerHTML = '<div class="container py-5 text-center"><div style="font-size:3rem">🛍️</div><h3 class="mt-3">Your cart is empty</h3><a href="category.html" class="btn btn-dark mt-2">Start shopping</a></div>'; refreshBadge(); return; }
    el.innerHTML = `<div class="container py-5"><h1 class="h3 fw-bold mb-4">Your Cart</h1>
      <div class="row g-4">
        <div class="col-lg-8"><div class="d-flex flex-column gap-3" id="cart-list"></div></div>
        <div class="col-lg-4">
          <div class="border rounded-4 p-4">
            <h5 class="mb-3">Order Summary</h5>
            <div id="cart-coupon" class="mb-3"></div>
            <div class="d-flex justify-content-between mb-1"><span class="text-muted">Subtotal</span><span>${money(c.subtotal)}</span></div>
            ${parseFloat(c.discount) > 0 ? `<div class="d-flex justify-content-between mb-1 text-success"><span>Discount</span><span>- ${money(c.discount)}</span></div>` : ''}
            <div class="d-flex justify-content-between mb-1"><span class="text-muted">Delivery</span><span>${c.coupon && c.coupon.free_shipping ? 'FREE' : money(60)}</span></div>
            <hr><div class="d-flex justify-content-between fw-bold fs-5"><span>Total</span><span>${money((c.coupon && c.coupon.free_shipping ? 0 : 60) + parseFloat(c.total))}</span></div>
            <a href="checkout.html" class="btn btn-dark w-100 mt-3">Proceed to Checkout</a>
          </div>
        </div>
      </div></div>`;
    document.getElementById('cart-list').innerHTML = c.items.map(it => `
      <div class="d-flex gap-3 align-items-center border rounded-4 p-3">
        <img src="${it.product.thumbnail || IMG_FALLBACK}" width="72" height="72" class="rounded-3" style="object-fit:cover" alt="">
        <div class="flex-grow-1">
          <a href="product-details.html?slug=${encodeURIComponent(it.product.slug)}" class="fw-semibold text-decoration-none text-dark">${esc(it.product.name)}</a>
          ${it.variant ? `<div class="small text-muted">${esc(it.variant.name)}</div>` : ''}
          <div class="text-success fw-bold">${money(it.unit_price)}</div>
        </div>
        <div class="input-group" style="width:120px">
          <button class="btn btn-outline-secondary btn-sm" data-qty="${it.id}" data-d="-1">−</button>
          <span class="form-control form-control-sm text-center">${it.quantity}</span>
          <button class="btn btn-outline-secondary btn-sm" data-qty="${it.id}" data-d="1">+</button>
        </div>
        <div class="text-end" style="min-width:90px"><div class="fw-bold">${money(it.line_total)}</div><button class="btn btn-link btn-sm text-danger p-0" data-rm="${it.id}">Remove</button></div>
      </div>`).join('');

    const cbox = document.getElementById('cart-coupon');
    cbox.innerHTML = c.coupon
      ? `<div class="d-flex justify-content-between align-items-center bg-success-subtle text-success rounded-3 px-3 py-2"><span>🏷️ <b>${esc(c.coupon.code)}</b></span><button class="btn btn-sm btn-link text-danger p-0" id="rm-coupon">Remove</button></div>`
      : `<div class="input-group"><input class="form-control" id="coupon-code" placeholder="Coupon code" style="text-transform:uppercase"><button class="btn btn-dark" id="apply-coupon">Apply</button></div>`;

    el.querySelectorAll('[data-qty]').forEach(b => b.onclick = async () => {
      const id = +b.getAttribute('data-qty'), d = +b.getAttribute('data-d');
      const cur = c.items.find(x => x.id === id); if (!cur) return;
      try { keepToken(await api('/cart/update', { method: 'POST', cart: true, body: { item_id: id, quantity: cur.quantity + d } })); renderCart(); refreshBadge(); } catch (e) { toast(e.message, true); }
    });
    el.querySelectorAll('[data-rm]').forEach(b => b.onclick = async () => {
      try { keepToken(await api('/cart/remove', { method: 'POST', cart: true, body: { item_id: +b.getAttribute('data-rm') } })); renderCart(); refreshBadge(); } catch (e) { toast(e.message, true); }
    });
    const ap = document.getElementById('apply-coupon');
    if (ap) ap.onclick = async () => { const code = (document.getElementById('coupon-code').value || '').trim(); if (!code) return; try { keepToken(await api('/cart/coupon', { method: 'POST', cart: true, body: { code } })); renderCart(); } catch (e) { toast(e.message, true); } };
    const rc = document.getElementById('rm-coupon');
    if (rc) rc.onclick = async () => { try { keepToken(await api('/cart/coupon/remove', { method: 'POST', cart: true, body: {} })); renderCart(); } catch (e) { toast(e.message, true); } };
    refreshBadge();
  }

  // ---------- CHECKOUT ----------
  async function initCheckout() {
    const el = main(); if (!el) return;
    let c; try { c = keepToken(await api('/cart', { cart: true })); } catch (e) {}
    if (!c || !c.items.length) { el.innerHTML = '<div class="container py-5 text-center"><h3>Your cart is empty</h3><a href="category.html" class="btn btn-dark mt-2">Shop now</a></div>'; return; }
    let addresses = [];
    if (auth.token) { try { addresses = (await api('/customer/addresses')).items; } catch (e) {} }
    let prof = {};
    if (auth.token) { try { prof = await api('/customer/profile'); } catch (e) {} }
    const shipFree = c.coupon && c.coupon.free_shipping;
    const payable = (shipFree ? 0 : 60) + parseFloat(c.total);

    const addrPicker = (auth.token && addresses.length) ? `
      <div class="mb-3">
        <label class="form-label fw-semibold">Deliver to</label>
        ${addresses.map(a => `<label class="d-block border rounded-3 p-2 mb-2"><input type="radio" name="addr" value="${a.id}" ${a.is_default ? 'checked' : ''}> <b>${esc(a.label || a.name)}</b> ${a.is_default ? '<span class="badge bg-success-subtle text-success">Default</span>' : ''}<div class="small text-muted">${esc(a.name)} · ${esc(a.phone)} — ${esc([a.address, a.area, a.district].filter(Boolean).join(', '))}</div></label>`).join('')}
        <label class="d-block border rounded-3 p-2"><input type="radio" name="addr" value="new"> Use a new address</label>
      </div>` : '';

    el.innerHTML = `<div class="container py-5"><h1 class="h3 fw-bold mb-4">Checkout</h1>
      <form id="co-form" class="row g-4">
        <div class="col-lg-7">
          <div class="border rounded-4 p-4">
            ${addrPicker}
            <div id="co-fields">
              <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Full name *</label><input class="form-control" id="f-name" value="${esc(prof.name || '')}"></div>
                <div class="col-md-6"><label class="form-label">Phone *</label><input class="form-control" id="f-phone" value="${esc(prof.phone || '')}"></div>
                <div class="col-md-6"><label class="form-label">Division</label><input class="form-control" id="f-division"></div>
                <div class="col-md-6"><label class="form-label">District</label><input class="form-control" id="f-district"></div>
                <div class="col-md-12"><label class="form-label">Area</label><input class="form-control" id="f-area"></div>
                <div class="col-md-12"><label class="form-label">Full address *</label><textarea class="form-control" id="f-address" rows="2"></textarea></div>
              </div>
            </div>
            <div class="mt-3"><label class="form-label">Order note</label><input class="form-control" id="f-note"></div>
            <div class="alert alert-warning mt-3 mb-0 py-2">💵 Cash on Delivery</div>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="border rounded-4 p-4">
            <h5 class="mb-3">Order Summary</h5>
            <div id="co-items" class="mb-3"></div>
            <div class="d-flex justify-content-between mb-1"><span class="text-muted">Subtotal</span><span>${money(c.subtotal)}</span></div>
            ${parseFloat(c.discount) > 0 ? `<div class="d-flex justify-content-between mb-1 text-success"><span>Discount ${c.coupon ? '(' + esc(c.coupon.code) + ')' : ''}</span><span>- ${money(c.discount)}</span></div>` : ''}
            <div class="d-flex justify-content-between mb-1"><span class="text-muted">Delivery</span><span>${shipFree ? 'FREE' : money(60)}</span></div>
            <hr><div class="d-flex justify-content-between fw-bold fs-5"><span>Payable</span><span>${money(payable)}</span></div>
            <button class="btn btn-success w-100 mt-3" id="co-place" type="submit">Place order</button>
          </div>
        </div>
      </form></div>`;
    document.getElementById('co-items').innerHTML = c.items.map(it => `<div class="d-flex justify-content-between small mb-1"><span>${esc(it.product.name)} × ${it.quantity}</span><span>${money(it.line_total)}</span></div>`).join('');

    function toggleFields() {
      const sel = el.querySelector('input[name="addr"]:checked');
      const useNew = !sel || sel.value === 'new';
      document.getElementById('co-fields').style.display = useNew ? '' : 'none';
    }
    el.querySelectorAll('input[name="addr"]').forEach(r => r.onchange = toggleFields);
    toggleFields();

    document.getElementById('co-form').onsubmit = async e => {
      e.preventDefault();
      const btn = document.getElementById('co-place'); btn.disabled = true;
      try {
        const sel = el.querySelector('input[name="addr"]:checked');
        let body = { note: document.getElementById('f-note').value };
        if (auth.token && sel && sel.value !== 'new') { body.address_id = parseInt(sel.value, 10); }
        else {
          body.name = document.getElementById('f-name').value.trim();
          body.phone = document.getElementById('f-phone').value.trim();
          body.division = document.getElementById('f-division').value.trim();
          body.district = document.getElementById('f-district').value.trim();
          body.area = document.getElementById('f-area').value.trim();
          body.address = document.getElementById('f-address').value.trim();
          if (!body.name || !body.phone || !body.address) { toast('Name, phone and address are required', true); btn.disabled = false; return; }
        }
        const order = keepToken(await api('/checkout', { method: 'POST', cart: true, body }));
        localStorage.removeItem('cart_token');
        el.innerHTML = `<div class="container py-5"><div class="mx-auto text-center border rounded-4 p-5" style="max-width:460px">
          <div style="width:70px;height:70px;border-radius:50%;background:#d1e7dd;color:#0f5132;display:grid;place-items:center;font-size:2rem;margin:0 auto">✓</div>
          <h2 class="h4 mt-3">Order placed!</h2>
          <p class="text-muted mb-1">Your order number</p>
          <p class="fw-bold fs-5 text-dark">${esc(order.order_number)}</p>
          <p class="text-muted">Total <b>${money(order.totals.total)}</b> · Cash on Delivery</p>
          <a href="index.html" class="btn btn-dark mt-2">Continue shopping</a>
          ${auth.token ? '<a href="account.html" class="btn btn-outline-secondary mt-2 ms-1">My orders</a>' : ''}
        </div></div>`;
        refreshBadge();
      } catch (err) { toast(err.message, true); btn.disabled = false; }
    };
  }

  // ---------- LOGIN / REGISTER ----------
  function initLogin() {
    const el = main(); if (!el) return;
    if (auth.token) { el.innerHTML = '<div class="container py-5 text-center"><h3>You are logged in</h3><a href="account.html" class="btn btn-dark mt-2">Go to account</a></div>'; return; }
    el.innerHTML = `<div class="container py-5"><div class="row justify-content-center"><div class="col-md-6 col-lg-5">
      <ul class="nav nav-pills nav-justified mb-4 bg-light rounded-pill p-1">
        <li class="nav-item"><button class="nav-link active rounded-pill" id="tab-login">Login</button></li>
        <li class="nav-item"><button class="nav-link rounded-pill" id="tab-reg">Register</button></li>
      </ul>
      <div class="border rounded-4 p-4">
        <form id="form-login">
          <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" id="l-email" required></div>
          <div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" id="l-pass" required></div>
          <button class="btn btn-dark w-100">Login</button>
        </form>
        <form id="form-reg" class="d-none">
          <div class="mb-3"><label class="form-label">Full name</label><input class="form-control" id="r-name" required></div>
          <div class="mb-3"><label class="form-label">Phone</label><input class="form-control" id="r-phone"></div>
          <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" id="r-email" required></div>
          <div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" id="r-pass" required><div class="form-text">8+ chars incl. a letter, number &amp; symbol.</div></div>
          <button class="btn btn-dark w-100">Create account</button>
        </form>
      </div>
    </div></div></div>`;
    const lf = document.getElementById('form-login'), rf = document.getElementById('form-reg');
    document.getElementById('tab-login').onclick = () => { lf.classList.remove('d-none'); rf.classList.add('d-none'); document.getElementById('tab-login').classList.add('active'); document.getElementById('tab-reg').classList.remove('active'); };
    document.getElementById('tab-reg').onclick = () => { rf.classList.remove('d-none'); lf.classList.add('d-none'); document.getElementById('tab-reg').classList.add('active'); document.getElementById('tab-login').classList.remove('active'); };
    async function afterAuth(token, name, email) {
      auth.set({ token, name, email });
      const gt = localStorage.getItem('cart_token');
      if (gt) { try { await api('/cart/merge', { method: 'POST', body: { cart_token: gt } }); } catch (e) {} localStorage.removeItem('cart_token'); }
      location.href = 'account.html';
    }
    lf.onsubmit = async e => { e.preventDefault(); try { const d = await api('/auth/login', { method: 'POST', body: { email: document.getElementById('l-email').value, password: document.getElementById('l-pass').value } }); if (d.mfa_required) { toast('This account uses 2FA', true); return; } let nm = document.getElementById('l-email').value.split('@')[0]; auth.set({ token: d.access_token, name: nm, email: document.getElementById('l-email').value }); try { const pr = await api('/customer/profile'); await afterAuth(d.access_token, pr.name, pr.email); } catch (er) { await afterAuth(d.access_token, nm, ''); } } catch (err) { toast(err.message, true); } };
    rf.onsubmit = async e => { e.preventDefault(); try { const d = await api('/customer/register', { method: 'POST', body: { name: document.getElementById('r-name').value, phone: document.getElementById('r-phone').value, email: document.getElementById('r-email').value, password: document.getElementById('r-pass').value } }); await afterAuth(d.auth.access_token, d.customer.name, d.customer.email); } catch (err) { toast(err.message, true); } };
  }

  // ---------- ACCOUNT ----------
  async function initAccount() {
    const el = main(); if (!el) return;
    if (!auth.token) { el.innerHTML = '<div class="container py-5 text-center"><h3>Please log in</h3><a href="login.html" class="btn btn-dark mt-2">Login / Register</a></div>'; return; }
    el.innerHTML = `<div class="container py-5">
      <div class="d-flex align-items-center mb-4"><h1 class="h3 fw-bold mb-0 me-auto">My Account</h1><button class="btn btn-outline-danger btn-sm" id="acc-logout">Logout</button></div>
      <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><button class="nav-link active" data-tab="orders">Orders</button></li>
        <li class="nav-item"><button class="nav-link" data-tab="addr">Addresses</button></li>
      </ul>
      <div id="acc-body">Loading…</div></div>`;
    document.getElementById('acc-logout').onclick = () => { auth.clear(); location.href = 'index.html'; };
    const body = document.getElementById('acc-body');
    const tabs = el.querySelectorAll('[data-tab]');
    tabs.forEach(t => t.onclick = () => { tabs.forEach(x => x.classList.remove('active')); t.classList.add('active'); t.getAttribute('data-tab') === 'orders' ? showOrders() : showAddresses(); });

    async function showOrders() {
      body.innerHTML = 'Loading…';
      try {
        const d = await api('/orders');
        if (!d.items.length) { body.innerHTML = '<p class="text-muted">No orders yet.</p>'; return; }
        body.innerHTML = '<div class="list-group">' + d.items.map(o => `<button class="list-group-item list-group-item-action d-flex justify-content-between" data-order="${esc(o.order_number)}"><span><b>${esc(o.order_number)}</b><br><small class="text-muted">${esc(o.placed_at)}</small></span><span class="text-end">${money(o.total)}<br><span class="badge bg-dark text-capitalize">${esc(o.status)}</span></span></button>`).join('') + '</div>';
        body.querySelectorAll('[data-order]').forEach(b => b.onclick = () => showOrder(b.getAttribute('data-order')));
      } catch (e) { body.innerHTML = '<p class="text-danger">' + esc(e.message) + '</p>'; }
    }
    async function showOrder(num) {
      body.innerHTML = 'Loading…';
      try {
        const o = await api('/orders/' + encodeURIComponent(num));
        body.innerHTML = `<button class="btn btn-link p-0 mb-2" id="back">← Back to orders</button>
          <div class="border rounded-4 p-3">
            <div class="d-flex justify-content-between"><b>${esc(o.order_number)}</b><span class="badge bg-dark text-capitalize">${esc(o.status)}</span></div>
            <hr>${o.items.map(i => `<div class="d-flex justify-content-between small mb-1"><span>${esc(i.product_name)} × ${i.quantity}</span><span>${money(i.line_total)}</span></div>`).join('')}
            <hr><div class="d-flex justify-content-between"><span class="text-muted">Subtotal</span><span>${money(o.totals.subtotal)}</span></div>
            ${parseFloat(o.totals.discount) > 0 ? `<div class="d-flex justify-content-between text-success"><span>Discount</span><span>- ${money(o.totals.discount)}</span></div>` : ''}
            <div class="d-flex justify-content-between"><span class="text-muted">Delivery</span><span>${money(o.totals.shipping_charge)}</span></div>
            <div class="d-flex justify-content-between fw-bold"><span>Total</span><span>${money(o.totals.total)}</span></div>
            <hr><div class="small text-muted">Deliver to: ${esc(o.shipping.name)} · ${esc(o.shipping.phone)}<br>${esc([o.shipping.address, o.shipping.area, o.shipping.district].filter(Boolean).join(', '))}</div>
          </div>`;
        document.getElementById('back').onclick = showOrders;
      } catch (e) { body.innerHTML = '<p class="text-danger">' + esc(e.message) + '</p>'; }
    }
    async function showAddresses() {
      body.innerHTML = 'Loading…';
      try {
        const d = await api('/customer/addresses');
        body.innerHTML = `<div class="row g-3 mb-4">${d.items.map(a => `<div class="col-md-6"><div class="border rounded-4 p-3 h-100 ${a.is_default ? 'border-success' : ''}">
          <div class="fw-semibold">${esc(a.label || a.name)} ${a.is_default ? '<span class="badge bg-success-subtle text-success">Default</span>' : ''}</div>
          <div class="small text-muted">${esc(a.name)} · ${esc(a.phone)}<br>${esc([a.address, a.area, a.district, a.division].filter(Boolean).join(', '))}</div>
          <div class="mt-2 d-flex gap-2">${a.is_default ? '' : `<button class="btn btn-sm btn-outline-secondary" data-def="${a.id}">Set default</button>`}<button class="btn btn-sm btn-outline-danger" data-del="${a.id}">Delete</button></div>
        </div></div>`).join('') || '<div class="col-12 text-muted">No saved addresses.</div>'}</div>
        <div class="border rounded-4 p-3"><h6>Add address</h6>
          <form id="addr-form" class="row g-2">
            <div class="col-6"><input class="form-control" id="a-label" placeholder="Label (Home)"></div>
            <div class="col-6"><input class="form-control" id="a-name" placeholder="Name" required></div>
            <div class="col-6"><input class="form-control" id="a-phone" placeholder="Phone" required></div>
            <div class="col-6"><input class="form-control" id="a-area" placeholder="Area"></div>
            <div class="col-6"><input class="form-control" id="a-district" placeholder="District"></div>
            <div class="col-6"><input class="form-control" id="a-division" placeholder="Division"></div>
            <div class="col-12"><textarea class="form-control" id="a-address" placeholder="Full address" required></textarea></div>
            <div class="col-12"><label><input type="checkbox" id="a-default"> Set as default</label></div>
            <div class="col-12"><button class="btn btn-dark btn-sm">Add address</button></div>
          </form></div>`;
        body.querySelectorAll('[data-def]').forEach(b => b.onclick = async () => { try { await api('/customer/addresses/default', { method: 'POST', body: { id: +b.getAttribute('data-def') } }); showAddresses(); } catch (e) { toast(e.message, true); } });
        body.querySelectorAll('[data-del]').forEach(b => b.onclick = async () => { try { await api('/customer/addresses/delete', { method: 'POST', body: { id: +b.getAttribute('data-del') } }); showAddresses(); } catch (e) { toast(e.message, true); } });
        document.getElementById('addr-form').onsubmit = async e => {
          e.preventDefault();
          try {
            await api('/customer/addresses', { method: 'POST', body: {
              label: document.getElementById('a-label').value, name: document.getElementById('a-name').value, phone: document.getElementById('a-phone').value,
              area: document.getElementById('a-area').value, district: document.getElementById('a-district').value, division: document.getElementById('a-division').value,
              address: document.getElementById('a-address').value, is_default: document.getElementById('a-default').checked ? 1 : 0
            }});
            showAddresses();
          } catch (er) { toast(er.message, true); }
        };
      } catch (e) { body.innerHTML = '<p class="text-danger">' + esc(e.message) + '</p>'; }
    }
    showOrders();
  }

  // ---------- header: search + account link ----------
  function initHeader() {
    document.querySelectorAll('form.search-bar, form.mobile-search').forEach(f => {
      f.addEventListener('submit', e => {
        e.preventDefault();
        const inp = f.querySelector('input[type="text"], .search-field, input[type="search"]');
        const q = inp ? inp.value.trim() : '';
        location.href = 'category.html' + (q ? '?search=' + encodeURIComponent(q) : '');
      });
    });
    // reflect auth on the header account button
    if (auth.token) {
      document.querySelectorAll('a[href="login.html"]').forEach(a => { a.textContent = auth.name ? ('Hi, ' + auth.name.split(' ')[0]) : 'My Account'; a.setAttribute('href', 'account.html'); });
    }
  }

  // ---------- boot ----------
  document.addEventListener('DOMContentLoaded', () => {
    initHeader();
    refreshBadge();
    if (page === 'index.html' || page === '') initHome();
    else if (page === 'category.html') initCategory();
    else if (page === 'product-details.html') initProduct();
    else if (page === 'cart.html') renderCart();
    else if (page === 'checkout.html') initCheckout();
    else if (page === 'login.html' || page === 'register.html') initLogin();
    else if (page === 'account.html') initAccount();
  });
})();
