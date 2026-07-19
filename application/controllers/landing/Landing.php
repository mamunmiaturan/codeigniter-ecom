<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Storefront
 * @author   : Mamun Mia Turan
 * @filename : Landing.php
 *
 * Public, server-rendered storefront. Renders the ShopWise-styled theme with
 * live catalog data (real products, prices and images) and reuses the existing
 * Cart / Coupon / Order models. Anonymous shoppers are tracked by a guest cart
 * token kept in the PHP session, so guest checkout (COD) works without login.
 */
class Landing extends Frontend_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['landing_model', 'landing/catalog_model', 'landing/listing_model', 'landing/product_detail_model', 'cart_model', 'coupon_model', 'product_model', 'order_model', 'review_model', 'customer_model', 'wishlist_model', 'sales_model', 'return_model', 'download_model']);
        $this->load->helper(['url', 'landing', 'store_auth']);
    }

    // ---------------------------------------------------------------- pages

    public function index()
    {
        $this->data['categories']   = $this->catalog_model->categories();
        $this->data['featured']     = $this->listing_model->featured(8);
        $this->data['best_selling'] = $this->listing_model->best_selling(8);
        $this->data['latest']       = $this->listing_model->latest(8);
        $this->data['featured_categories'] = $this->catalog_model->featured_categories(8);
        $this->data['featured_brands']     = $this->catalog_model->featured_brands(12);
        $this->load->model('banner_model');
        $this->data['sliders']      = $this->banner_model->get_active_by_type('slider');
        $this->data['promos']       = $this->banner_model->get_active_by_type('promo');
        $this->data['announcement'] = $this->banner_model->get_active_by_type('announcement')[0] ?? null;
        $this->data['popup']        = $this->banner_model->get_active_by_type('popup')[0] ?? null;
        $this->load->model('flash_sale_model');
        $this->data['flash_sale']  = $this->flash_sale_model->active();
        $this->data['flash_items'] = $this->flash_sale_model->active_items();
        $this->_render('landing/index', 'Home');
    }

    public function shop()
    {
        $attr = $this->input->get('attr', true);
        $filters = [
            'category' => trim((string) $this->input->get('category', true)),
            'search'   => trim((string) $this->input->get('search', true)),
            'sort'     => trim((string) $this->input->get('sort', true)),
            'attr'     => is_array($attr) ? $attr : [],
            'in_stock' => $this->input->get('in_stock') ? 1 : 0,
        ];
        $page = (int) ($this->input->get('page') ?: 1);

        $this->data['result']           = $this->listing_model->list_products($filters, $page, 12);
        $this->data['filters']          = $filters;
        $this->data['categories']       = $this->catalog_model->categories();
        $this->data['current_category'] = $filters['category'] ? $this->catalog_model->category_by_slug($filters['category']) : null;
        $this->data['facets']           = $this->catalog_model->filterable_attributes($filters['category']);
        $this->data['sel_attr']         = $filters['attr'];
        $this->_render('landing/pages/shop', 'Shop');
    }

    public function product($slug = '')
    {
        $p = $this->product_detail_model->product(rawurldecode($slug));
        if (!$p) {
            $this->show_404();
            return;
        }
        $this->data['product'] = $p;
        $this->data['images']  = $this->product_detail_model->images($p['id']);
        $this->data['related'] = $this->product_detail_model->related($p['category_id'], $p['id'], 4);
        $this->data['fbt']     = $this->product_detail_model->frequently_bought_together($p['id'], 4);
        $this->data['review_summary'] = $this->review_model->rating_summary($p['id']);
        $this->data['reviews']        = $this->review_model->approved_for_product($p['id'], 20, 0);
        $this->data['in_wishlist']    = is_customer_loggedin() ? $this->wishlist_model->exists(customer_id(), $p['id']) : false;
        $this->data['samples']        = (($p['product_type'] ?? 'simple') === 'downloadable')
            ? $this->download_model->samples($p['id']) : [];
        $this->load->model('product_attribute_value_model');
        $this->data['attributes']     = $this->product_attribute_value_model->get_display_values($p['id']);
        $this->data['variants']       = (($p['product_type'] ?? 'simple') === 'configurable')
            ? $this->product_model->get_active_variants($p['id']) : [];
        $this->load->model('composite_model');
        $this->data['grouped_items']  = (($p['product_type'] ?? 'simple') === 'grouped')
            ? $this->composite_model->grouped_saleable($p['id']) : [];
        $this->data['bundle_options'] = (($p['product_type'] ?? 'simple') === 'bundle')
            ? $this->composite_model->bundle_options($p['id']) : [];
        // Recently viewed (session-backed, guest-friendly): show the prior list,
        // then push the current product to the front for next time.
        $rv = $this->session->userdata('recently_viewed');
        $rv = is_array($rv) ? $rv : [];
        $rv = array_values(array_filter($rv, function ($x) use ($p) {
            return (int) $x !== (int) $p['id'];
        }));
        $this->data['recently_viewed'] = $this->product_detail_model->by_ids(array_slice($rv, 0, 4));
        array_unshift($rv, (int) $p['id']);
        $this->session->set_userdata('recently_viewed', array_slice($rv, 0, 12));
        $this->_render('landing/pages/product', $p['name']);
    }

    public function cart()
    {
        $this->data['cart'] = $this->_cart_summary();
        $saved = $this->session->userdata('saved_items');
        $this->data['saved_items'] = $this->product_detail_model->by_ids(is_array($saved) ? $saved : [], 20);
        $this->_render('landing/pages/cart', 'Your Cart');
    }

    public function checkout()
    {
        $division  = trim((string) $this->input->get('division', true));
        $method_id = (int) $this->input->get('shipping_method') ?: null;
        $postcode  = trim((string) $this->input->get('postcode', true));

        $cart = $this->_cart_summary($division, $method_id, $postcode);
        if (empty($cart['items'])) {
            redirect(base_url('cart'));
            return;
        }
        $this->load->model('payment_model');
        $this->data['cart']            = $cart;
        $this->data['customer']        = is_customer_loggedin() ? $this->customer_model->get_profile(customer_id()) : null;
        $this->data['payment_methods'] = $this->payment_model->available_for_checkout();
        $this->data['divisions']       = ['Dhaka', 'Chattogram', 'Khulna', 'Rajshahi', 'Sylhet', 'Barishal', 'Rangpur', 'Mymensingh'];
        $this->data['sel_division']    = $division;
        $this->data['sel_method']      = $method_id;
        $this->data['sel_postcode']    = $postcode;
        $this->_render('landing/pages/checkout', 'Checkout');
    }

    public function order_success($num = '')
    {
        $order = $this->order_model->get_by_number($num);
        if (!$order) {
            $this->show_404();
            return;
        }
        $this->data['order'] = $order;
        $this->data['items'] = $this->order_model->get_items($order['id']);
        $this->data['shipment'] = $this->sales_model->latest_shipment($order['id']);
        $this->data['can_return'] = is_customer_loggedin()
            && (int) ($order['user_id'] ?? 0) === (int) customer_id()
            && in_array($order['status'], ['delivered', 'completed'], true)
            && !$this->return_model->has_open_return($order['id']);
        $this->_render('landing/pages/success', 'Order Confirmed');
    }

    public function page($slug = '')
    {
        $this->load->model('cms_page_model');
        $p = $this->cms_page_model->get_active_by_slug(rawurldecode($slug));
        if (!$p) {
            $this->show_404();
            return;
        }
        $this->data['page'] = $p;
        $this->_render('landing/pages/page', $p['meta_title'] ?: $p['title']);
    }

    public function faqs()
    {
        $this->load->model('faq_model');
        $this->data['faqs'] = $this->faq_model->active();
        $this->_render('landing/pages/faqs', 'FAQs');
    }

    public function contact()
    {
        $this->_render('landing/pages/contact', 'Contact Us');
    }

    public function submit_contact()
    {
        require_once APPPATH . 'requests/Contact_Request.php';
        $req = new Contact_Request();
        if (!$req->validate()) {
            set_alert('error', $req->first_error());
            redirect($req->back());
            return;
        }
        $this->load->model('contact_message_model');
        $name    = trim((string) $this->input->post('name'));
        $email   = trim((string) $this->input->post('email'));
        $message = trim((string) $this->input->post('message'));

        if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_alert('error', 'Please provide your name, a valid email and a message.');
            redirect(base_url('contact-us'));
            return;
        }

        $this->contact_message_model->insert([
            'name'    => $name,
            'email'   => $email,
            'phone'   => trim((string) $this->input->post('phone')) ?: null,
            'subject' => trim((string) $this->input->post('subject')) ?: null,
            'message' => $message,
            'status'  => 'New',
        ]);

        set_alert('success', 'Thank you! Your message has been sent — we will get back to you soon.');
        redirect(base_url('contact-us'));
    }

    public function blog()
    {
        $this->load->model('blog_post_model');
        $page   = max(1, (int) ($this->input->get('page') ?: 1));
        $per    = 9;
        $offset = ($page - 1) * $per;
        $total  = $this->blog_post_model->count_published();

        $this->data['posts'] = $this->blog_post_model->published_list($per, $offset);
        $this->data['page']  = $page;
        $this->data['pages'] = (int) ceil($total / $per);
        $this->data['total'] = $total;
        $this->_render('landing/pages/blog', translate('blog') ?: 'Blog');
    }

    public function blog_post($slug = '')
    {
        $this->load->model('blog_post_model');
        $post = $this->blog_post_model->get_active_by_slug(rawurldecode($slug));
        if (!$post) {
            $this->show_404();
            return;
        }
        $this->data['post']   = $post;
        $this->data['recent'] = $this->blog_post_model->recent(5);
        $this->_render('landing/pages/blog_post', $post['meta_title'] ?: $post['title']);
    }

    public function show_404()
    {
        $this->output->set_status_header(404);
        $this->_render('landing/pages/404', 'Page Not Found');
    }

    public function subscribe()
    {
        require_once APPPATH . 'requests/Subscribe_Request.php';
        $req = new Subscribe_Request();
        if (!$req->validate()) {
            set_alert('error', $req->first_error());
            redirect($this->input->post('redirect') ?: base_url('/'));
            return;
        }
        $this->load->model('newsletter_model');
        $email    = strtolower(trim((string) $this->input->post('email')));
        $redirect = $this->input->post('redirect') ?: ($_SERVER['HTTP_REFERER'] ?? base_url('/'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_alert('error', 'Please enter a valid email address.');
            redirect($redirect);
            return;
        }
        $res = $this->newsletter_model->subscribe($email, 'footer', is_customer_loggedin() ? customer_id() : null);
        set_alert('success', $res === 'exists' ? "You're already subscribed." : 'Thanks for subscribing to our newsletter!');
        redirect($redirect);
    }

    // -------------------------------------------------------------- actions

    public function add()
    {
        $pid = (int) $this->input->post('product_id');
        $qty = max(1, (int) $this->input->post('quantity'));
        $product = $pid ? $this->product_model->find($pid) : null;

        if (!$product || $product['status'] !== 'Active' || !empty($product['deleted_at'])) {
            set_alert('error', 'This product is not available.');
            redirect($this->_back());
            return;
        }
        $ptype = $product['product_type'] ?? 'simple';

        // Grouped: fan out into one independent cart line per chosen constituent.
        if ($ptype === 'grouped') {
            $this->_add_grouped($product);
            return;
        }
        // Bundle: one cart line priced from the chosen components.
        if ($ptype === 'bundle') {
            $this->_add_bundle($product);
            return;
        }

        // A chosen variant (colour/size combo) resolves availability + price.
        $variant_id = (int) $this->input->post('variant_id') ?: null;
        $variant = $variant_id ? $this->product_model->get_variant($variant_id, $pid) : null;
        if ($variant_id && !$variant) {
            set_alert('error', 'Please choose a valid option.');
            redirect($this->_back());
            return;
        }
        // Configurable products cannot be added without picking a variant.
        if ($ptype === 'configurable' && !$variant) {
            set_alert('error', 'Please choose the available options before adding to cart.');
            redirect($this->_back());
            return;
        }

        if ($variant) {
            if ((int) $variant['stock_quantity'] <= 0) {
                set_alert('error', $product['name'] . ' (' . $variant['name'] . ') is out of stock.');
                redirect($this->_back());
                return;
            }
            // A configurable variant must carry its own price — its parent price is
            // a 0 placeholder, so falling back to it would sell the variant free.
            if ($ptype === 'configurable' && (float) $variant['price'] <= 0) {
                set_alert('error', 'This option is not available for purchase.');
                redirect($this->_back());
                return;
            }
            // Unit price is recomputed live by Cart_model::effective_price; store
            // the variant price (its override) as a best-effort value.
            $price = ((float) $variant['price'] > 0) ? (float) $variant['price']
                : (($product['special_price'] !== null && (float) $product['special_price'] > 0) ? (float) $product['special_price'] : (float) $product['price']);
        } else {
            if ($product['stock_status'] === 'out_of_stock') {
                set_alert('error', $product['name'] . ' is out of stock.');
                redirect($this->_back());
                return;
            }
            $price = ($product['special_price'] !== null && (float) $product['special_price'] > 0)
                ? (float) $product['special_price'] : (float) $product['price'];
        }

        $cart_id = $this->_cart_id(true);
        $this->cart_model->add_or_increment($cart_id, $pid, $variant_id, $qty, $price);

        $label = $variant ? ($product['name'] . ' — ' . $variant['name']) : $product['name'];
        set_alert('success', $label . ' added to cart.');
        redirect($this->input->post('redirect') ?: base_url('cart'));
    }

    /**
     * Grouped add-to-cart: each chosen associated product (qty[<id>] > 0) becomes
     * its OWN independent cart line, priced by the normal effective_price path.
     * The grouped parent itself is never carted and contributes no price.
     */
    private function _add_grouped($product)
    {
        $this->load->model('composite_model');
        $qtys = (array) $this->input->post('qty'); // associated_product_id => qty
        $saleable = [];
        foreach ($this->composite_model->grouped_saleable($product['id']) as $r) {
            $saleable[(int) $r['id']] = $r;
        }

        $cart_id = null;
        $added = 0;
        foreach ($qtys as $aid => $q) {
            $aid = (int) $aid;
            $q = (int) $q;
            if ($q <= 0 || !isset($saleable[$aid])) {
                continue;
            }
            $row = $saleable[$aid];
            if (!$row['in_stock']) {
                continue;
            }
            if ($cart_id === null) {
                $cart_id = $this->_cart_id(true);
            }
            $this->cart_model->add_or_increment($cart_id, $aid, null, $q, (float) $row['display_price']);
            $added++;
        }

        if ($added === 0) {
            set_alert('error', 'Please choose at least one available item.');
            redirect($this->_back());
            return;
        }
        set_alert('success', $added . ' item' . ($added === 1 ? '' : 's') . ' from ' . $product['name'] . ' added to cart.');
        redirect($this->input->post('redirect') ?: base_url('cart'));
    }

    /**
     * Bundle add-to-cart: the chosen option-products resolve to a component list
     * stored on ONE cart line. The line price is recomputed live from the
     * components by Cart_model, so bundle pricing composes on the effective_price
     * pipeline. Required option groups must each have a selection.
     */
    private function _add_bundle($product)
    {
        $this->load->model('composite_model');
        $qty = max(1, (int) $this->input->post('quantity'));
        $index   = $this->composite_model->bundle_option_product_index($product['id']);
        $options = $this->composite_model->bundle_options($product['id']);

        // Selected bundle_option_product ids: checkbox groups post bundle_selection[],
        // single-choice (radio/select) groups post bundle_radio[<option_id>].
        $selected = array_map('intval', (array) $this->input->post('bundle_selection'));
        foreach ((array) $this->input->post('bundle_radio') as $bopid) {
            $selected[] = (int) $bopid;
        }
        $selected = array_values(array_filter($selected, function ($id) use ($index) { return $id > 0 && isset($index[$id]); }));

        // Every required option group must be covered.
        foreach ($options as $o) {
            if ((int) $o['is_required'] !== 1) {
                continue;
            }
            $covered = false;
            foreach ($selected as $bopid) {
                if ($index[$bopid]['option_id'] === (int) $o['id']) {
                    $covered = true;
                    break;
                }
            }
            if (!$covered) {
                set_alert('error', 'Please choose an option for "' . $o['label'] . '".');
                redirect($this->_back());
                return;
            }
        }

        // Build the component list (sum qty when the same product recurs); check stock.
        $comps = [];
        foreach ($selected as $bopid) {
            $bp = $index[$bopid];
            if (!$bp['in_stock']) {
                set_alert('error', $bp['name'] . ' is out of stock.');
                redirect($this->_back());
                return;
            }
            $pid = $bp['product_id'];
            if (!isset($comps[$pid])) {
                $comps[$pid] = ['product_id' => $pid, 'qty' => 0, 'name' => $bp['name']];
            }
            $comps[$pid]['qty'] += $bp['qty'];
        }
        if (empty($comps)) {
            set_alert('error', 'Please choose at least one option.');
            redirect($this->_back());
            return;
        }
        $comps = array_values($comps);

        // Best-effort stored unit price (recomputed live at cart/checkout).
        $unit = 0.0;
        foreach ($comps as $c) {
            $unit += $this->cart_model->component_effective_price($c['product_id']) * (int) $c['qty'];
        }
        $unit = round($unit, 2);

        $cart_id = $this->_cart_id(true);
        $this->cart_model->add_bundle($cart_id, $product['id'], $qty, $unit, ['components' => $comps]);
        set_alert('success', $product['name'] . ' added to cart.');
        redirect($this->input->post('redirect') ?: base_url('cart'));
    }

    public function update()
    {
        $cart_id = $this->_cart_id(false);
        if ($cart_id) {
            $item = $this->cart_model->get_item($cart_id, (int) $this->input->post('item_id'));
            if ($item) {
                $qty = (int) $this->input->post('quantity');
                if ($qty <= 0) {
                    $this->cart_model->remove_item($cart_id, $item->id);
                } else {
                    $this->cart_model->update_qty($cart_id, $item->id, $qty);
                }
            }
        }
        redirect(base_url('cart'));
    }

    public function remove()
    {
        $cart_id = $this->_cart_id(false);
        if ($cart_id) {
            $this->cart_model->remove_item($cart_id, (int) $this->input->post('item_id'));
        }
        redirect(base_url('cart'));
    }

    /** Move a cart line into the session-backed "saved for later" list. */
    public function save_for_later()
    {
        $item_id = (int) $this->input->post('item_id');
        $pid     = (int) $this->input->post('product_id');
        $cart_id = $this->_cart_id(false);
        if ($cart_id && $item_id) {
            $this->cart_model->remove_item($cart_id, $item_id);
        }
        if ($pid) {
            $saved = $this->session->userdata('saved_items');
            $saved = is_array($saved) ? array_values(array_filter($saved, function ($x) use ($pid) {
                return (int) $x !== $pid;
            })) : [];
            array_unshift($saved, $pid);
            $this->session->set_userdata('saved_items', array_slice($saved, 0, 30));
            set_alert('success', 'Item saved for later.');
        }
        redirect(base_url('cart'));
    }

    /** Move a saved item back into the cart (simple products only). */
    public function move_to_cart()
    {
        $pid     = (int) $this->input->post('product_id');
        $product = $pid ? $this->product_model->find($pid) : null;
        if (!$product || ($product['status'] ?? '') !== 'Active') {
            set_alert('error', 'That product is no longer available.');
            redirect(base_url('cart'));
            return;
        }
        $ptype = $product['product_type'] ?? 'simple';
        if (in_array($ptype, ['configurable', 'grouped', 'bundle'], true)) {
            // These need option selection — send the shopper to the product page.
            redirect(base_url('product/' . rawurlencode($product['slug'])));
            return;
        }
        $price = ($product['special_price'] !== null && (float) $product['special_price'] > 0)
            ? (float) $product['special_price'] : (float) $product['price'];
        $cart_id = $this->_cart_id(true);
        $this->cart_model->add_or_increment($cart_id, $pid, null, 1, $price);
        $this->_forget_saved($pid);
        set_alert('success', 'Moved to your cart.');
        redirect(base_url('cart'));
    }

    public function remove_saved()
    {
        $this->_forget_saved((int) $this->input->post('product_id'));
        redirect(base_url('cart'));
    }

    private function _forget_saved($pid)
    {
        $saved = $this->session->userdata('saved_items');
        $saved = is_array($saved) ? array_values(array_filter($saved, function ($x) use ($pid) {
            return (int) $x !== (int) $pid;
        })) : [];
        $this->session->set_userdata('saved_items', $saved);
    }

    public function apply_coupon()
    {
        $code    = strtoupper(trim((string) $this->input->post('code')));
        $cart_id = $this->_cart_id(true);
        $subtotal = $this->_subtotal($cart_id);

        if ($code === '' || $subtotal <= 0) {
            redirect(base_url('cart'));
            return;
        }
        $v = $this->coupon_model->validate($code, $subtotal, cart_owner());
        if ($v['ok']) {
            $this->cart_model->set_coupon($cart_id, $v['coupon']['code']);
            set_alert('success', 'Coupon "' . $v['coupon']['code'] . '" applied.');
        } else {
            set_alert('error', $v['message']);
        }
        redirect(base_url('cart'));
    }

    public function remove_coupon()
    {
        $cart_id = $this->_cart_id(false);
        if ($cart_id) {
            $this->cart_model->clear_coupon($cart_id);
        }
        redirect(base_url('cart'));
    }

    public function place_order()
    {
        $owner   = cart_owner();
        $cart_id = $this->cart_model->get_active_cart_id($owner['user_id'], $owner['guest_token'], false);
        if (!$cart_id) {
            redirect(base_url('cart'));
            return;
        }

        require_once APPPATH . 'requests/Checkout_Request.php';
        $req = new Checkout_Request();
        if (!$req->validate()) {
            set_alert('error', $req->first_error());
            redirect($req->back());
            return;
        }

        $name    = trim((string) $this->input->post('name'));
        $phone   = trim((string) $this->input->post('phone'));
        $address = trim((string) $this->input->post('address'));
        if ($name === '' || $phone === '' || $address === '') {
            set_alert('error', 'Name, phone and full address are required.');
            redirect(base_url('checkout'));
            return;
        }

        $customer = [
            'name'  => $name,
            'phone' => $phone,
            'email' => trim((string) $this->input->post('email')) ?: null,
        ];
        $shipping = [
            'division' => trim((string) $this->input->post('division')),
            'district' => trim((string) $this->input->post('district')),
            'area'     => trim((string) $this->input->post('area')),
            'address'  => $address,
            'landmark' => trim((string) $this->input->post('landmark')),
            'postcode' => trim((string) $this->input->post('postcode')),
        ];
        $note = trim((string) $this->input->post('note')) ?: null;
        $shipping_method_id = (int) $this->input->post('shipping_method') ?: null;

        $this->load->model('payment_model');
        $payment_method = trim((string) $this->input->post('payment_method')) ?: 'cod';
        if (!$this->payment_model->is_valid_method($payment_method)) {
            $payment_method = 'cod';
        }

        $res = $this->order_model->create_from_cart(
            $cart_id,
            $owner,
            $customer,
            $shipping,
            $payment_method,
            $note,
            $shipping_method_id
        );

        if (!$res['ok']) {
            set_alert('error', $res['message']);
            redirect(base_url('checkout'));
            return;
        }

        // Online payment: send the customer to the gateway (order is pending until paid).
        $gw = $this->payment_model->gateway($payment_method);
        if ($gw && $gw->is_online()) {
            $order = $this->order_model->find_order($res['order_id']);
            $begin = $gw->begin($order);
            if (!empty($begin['redirect'])) {
                redirect($begin['redirect']);
                return;
            }
            if (!empty($begin['error'])) {
                set_alert('error', $begin['error'] . ' Your order was placed as pending payment.');
            }
        }
        redirect(base_url('order/' . $res['order_number']));
    }

    /**
     * Submit a product review from the storefront (customer must be logged in).
     */
    public function submit_review($slug = '')
    {
        $return = base_url('product/' . rawurlencode($slug));
        if (!is_customer_loggedin()) {
            redirect(base_url('account/login?redirect=' . urlencode($return)));
            return;
        }
        $p = $this->product_model->get_active_by_slug(rawurldecode($slug));
        if (!$p) {
            $this->show_404();
            return;
        }

        require_once APPPATH . 'requests/Review_Request.php';
        $req = new Review_Request();
        if (!$req->validate()) {
            set_alert('error', $req->first_error());
            redirect($return . '#reviews');
            return;
        }

        $rating = (int) $this->input->post('rating');
        if ($rating < 1 || $rating > 5) {
            set_alert('error', 'Please choose a rating between 1 and 5.');
            redirect($return . '#reviews');
            return;
        }

        $uid = customer_id();
        if ($this->review_model->user_already_reviewed($uid, $p['id'])) {
            set_alert('error', 'You have already reviewed this product.');
            redirect($return . '#reviews');
            return;
        }

        $cust     = current_customer();
        $verified = $this->review_model->user_purchased($uid, $p['id']);
        $this->review_model->create([
            'product_id'           => $p['id'],
            'user_id'              => $uid,
            'author_name'          => $cust['name'] ?? 'Customer',
            'author_email'         => $cust['email'] ?? null,
            'rating'               => $rating,
            'title'                => trim((string) $this->input->post('title')) ?: null,
            'comment'              => trim((string) $this->input->post('comment')) ?: null,
            'is_verified_purchase' => $verified ? 1 : 0,
        ]);
        set_alert('success', 'Thank you! Your review has been submitted and is awaiting moderation.');
        redirect($return . '#reviews');
    }

    // -------------------------------------------------------------- compare

    public function compare()
    {
        $products  = [];
        $summaries = [];
        foreach ($this->_compare_ids() as $id) {
            $p = $this->product_model->find($id);
            if ($p && $p['status'] === 'Active' && empty($p['deleted_at'])) {
                $products[] = $p;
                $summaries[$p['id']] = $this->review_model->rating_summary($p['id']);
            }
        }
        $this->data['products']  = $products;
        $this->data['summaries'] = $summaries;
        $this->_render('landing/pages/compare', 'Compare Products');
    }

    public function compare_add()
    {
        $pid  = (int) $this->input->post('product_id');
        $back = $this->input->post('redirect') ?: ($_SERVER['HTTP_REFERER'] ?? base_url('/'));
        if ($pid > 0) {
            $ids = $this->_compare_ids();
            if (in_array($pid, $ids, true)) {
                set_alert('info', 'This product is already in your compare list.');
            } elseif (count($ids) >= 4) {
                set_alert('error', 'You can compare up to 4 products at a time. Remove one first.');
            } else {
                $ids[] = $pid;
                $this->session->set_userdata('store_compare', $ids);
                set_alert('success', 'Added to compare.');
            }
        }
        redirect($back);
    }

    public function compare_remove()
    {
        $pid = (int) $this->input->post('product_id');
        $ids = array_values(array_diff($this->_compare_ids(), [$pid]));
        $this->session->set_userdata('store_compare', $ids);
        redirect($this->input->post('redirect') ?: base_url('compare'));
    }

    public function compare_clear()
    {
        $this->session->unset_userdata('store_compare');
        redirect(base_url('compare'));
    }

    private function _compare_ids()
    {
        $ids = $this->session->userdata('store_compare');
        return is_array($ids) ? array_map('intval', $ids) : [];
    }

    // -------------------------------------------------------------- helpers

    /** Persistent guest cart token kept in the session. */
    private function _token()
    {
        return store_cart_token();
    }

    /**
     * Resolve the active cart id for the current owner (logged-in customer by
     * user_id, else the anonymous guest token).
     */
    private function _cart_id($create)
    {
        $o = cart_owner();
        return $this->cart_model->get_active_cart_id($o['user_id'], $o['guest_token'], $create);
    }

    private function _subtotal($cart_id)
    {
        $sub = 0.0;
        foreach ($this->cart_model->get_items_detailed($cart_id) as $r) {
            $sub += (float) $r['effective_price'] * (int) $r['quantity'];
        }
        return $sub;
    }

    /**
     * Full cart view-model: variant/catalog-rule-aware line prices, coupon +
     * auto cart-rule discounts, method-derived shipping and per-line tax.
     * $division / $shipping_method_id refine shipping + tax at checkout.
     */
    private function _cart_summary($division = '', $shipping_method_id = null, $postcode = '')
    {
        $this->load->model(['cart_rule_model', 'shipping_model', 'tax_model']);

        $cart_id  = $this->_cart_id(false);
        $items    = [];
        $subtotal = 0.0;
        $count    = 0;
        $physical_count = 0;
        $tax_lines = [];
        $rule_lines = [];
        $has_physical = false;

        if ($cart_id) {
            foreach ($this->cart_model->get_items_detailed($cart_id) as $r) {
                $base = (float) $r['effective_price'];
                $line = $base * (int) $r['quantity'];
                if (!in_array($r['product_type'] ?? 'simple', ['virtual', 'downloadable'], true)) {
                    $has_physical = true;
                    $physical_count += (int) $r['quantity'];
                }
                $items[] = [
                    'id'         => (int) $r['id'],
                    'name'       => $r['name'],
                    'slug'       => $r['slug'],
                    'image'      => !empty($r['thumbnail'])
                        ? base_url('uploads/catalog/product/' . $r['thumbnail'])
                        : base_url('assets/frontend/assets/img/product/product-1.webp'),
                    'unit_price' => $base,
                    'quantity'   => (int) $r['quantity'],
                    'line_total' => $line,
                ];
                $subtotal += $line;
                $count    += (int) $r['quantity'];
                $tax_lines[]  = ['taxable' => $line, 'tax_category_id' => $r['tax_category_id'] ?? null];
                $rule_lines[] = ['category_id' => $r['category_id'] ?? null, 'line_total' => $line];
            }
        }

        // Coupon (coded)
        $coupon_discount = 0.0;
        $coupon = null;
        $free_shipping = false;
        $code = $cart_id ? $this->cart_model->get_coupon_code($cart_id) : null;
        if ($code) {
            $v = $this->coupon_model->validate($code, $subtotal, cart_owner());
            if ($v['ok']) {
                $coupon_discount = (float) $v['discount'];
                $free_shipping   = (bool) $v['free_shipping'];
                $coupon          = $v['coupon'];
            } else {
                $this->cart_model->clear_coupon($cart_id);
            }
        }

        // Auto cart price rules (stack on top of the coupon)
        $cr = $this->cart_rule_model->evaluate($subtotal, cart_owner(), $rule_lines);
        $cart_rule_discount = (float) $cr['discount'];
        if ($cr['free_shipping']) {
            $free_shipping = true;
        }

        $discount = round(min($coupon_discount + $cart_rule_discount, $subtotal), 2);
        $disc_sub = max(0, $subtotal - $discount);

        // Shipping — method-derived; available list for the checkout selector.
        // A digital-only cart (all virtual/downloadable) has no shipping.
        $shipping = 0.0;
        $shipping_method = null;
        $shipping_methods = [];
        if (!empty($items) && $has_physical) {
            // Per-unit shipping is priced on physical quantity only — digital
            // (virtual/downloadable) items ship nothing and must not inflate it.
            $avail = $this->shipping_model->available_methods($division, $disc_sub, $physical_count);
            $shipping_methods = $avail['methods'];
            $shipping_method  = $this->shipping_model->resolve($division, $disc_sub, $physical_count, $shipping_method_id);
            if ($shipping_method && !$free_shipping) {
                $shipping = (float) $shipping_method['computed_rate'];
            }
        }

        // Tax — exclusive, per-line by category, matched on the (optional) division.
        $tax = 0.0;
        if (!empty($items)) {
            $tax_res = $this->tax_model->compute_for_lines($tax_lines, ['country' => 'BD', 'state' => $division, 'postcode' => $postcode]);
            $tax = (float) $tax_res['tax'];
        }

        return [
            'items'              => $items,
            'count'              => $count,
            'subtotal'           => $subtotal,
            'coupon_discount'    => $coupon_discount,
            'cart_rule_discount' => $cart_rule_discount,
            'discount'           => $discount,
            'coupon'             => $coupon,
            'applied_rules'      => $cr['applied'],
            'free_shipping'      => $free_shipping,
            'has_physical'       => $has_physical,
            'shipping'           => $shipping,
            'shipping_method'    => $shipping_method,
            'shipping_methods'   => $shipping_methods,
            'tax'                => $tax,
            'total'              => round($disc_sub + $shipping + $tax, 2),
        ];
    }

    private function _back()
    {
        return $this->input->post('redirect') ?: ($_SERVER['HTTP_REFERER'] ?? base_url('/'));
    }

    private function _render($view, $title)
    {
        $summary = $this->_cart_summary();
        $this->data['title']          = $title;
        $this->data['content_view']   = $view;
        $this->data['nav_categories'] = $this->catalog_model->categories_tree();
        $this->data['cart_count']     = $summary['count'];
        $this->data['mini_cart']      = $summary; // live cart for the header flyout (items/subtotal/count)
        $this->load->view('landing/pages/layout', $this->data);
    }
}
