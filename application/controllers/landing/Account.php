<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Storefront
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Account.php
 *
 * Storefront customer accounts (server-rendered). Customers reuse the existing
 * users + login_credential tables (role = ROLE_CUSTOMER_ID). The web session is
 * kept under the `store_customer` userdata key so it never collides with the
 * admin panel login. Routed under the landing/account/* URL prefix.
 */
class Account extends Frontend_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['customer_model', 'cart_model', 'landing_model', 'order_model', 'wishlist_model', 'return_model', 'sales_model', 'complaint_model', 'support_ticket_model']);
        $this->load->helper(['url', 'landing', 'store_auth']);
        $this->load->library('app_lib');
    }

    // ---------------------------------------------------------------- auth

    public function login()
    {
        if (is_customer_loggedin()) {
            redirect(base_url('account'));
            return;
        }
        $r = $this->input->get('redirect', true);
        if ($r) {
            $this->session->set_userdata('redirect_url', $r);
        }
        $this->_render('landing/account/login', 'Log in');
    }

    public function authenticate()
    {
        if (is_customer_loggedin()) {
            redirect(base_url('account'));
            return;
        }
        require_once APPPATH . 'requests/Login_Request.php';
        $req = new Login_Request();
        if (!$req->validate()) {
            set_alert('error', $req->first_error());
            redirect($req->back());
            return;
        }
        $email    = trim((string) $this->input->post('email'));
        $password = (string) $this->input->post('password');
        if ($email === '' || $password === '') {
            set_alert('error', 'Please enter your email and password.');
            redirect(base_url('account/login'));
            return;
        }
        $login = $this->customer_model->get_login_by_email($email);
        if (!$login || empty($login['password']) || !$this->app_lib->verify_password($password, $login['password'])) {
            set_alert('error', 'Invalid email or password.');
            redirect(base_url('account/login'));
            return;
        }
        if (isset($login['status']) && strcasecmp((string) $login['status'], 'Active') !== 0) {
            set_alert('error', 'Your account is not active. Please contact support.');
            redirect(base_url('account/login'));
            return;
        }

        $name = $login['name'] ?: $email;
        $this->_login_session((int) $login['user_id'], $name, $email);
        $this->customer_model->touch_last_login((int) $login['credential_id']);
        $this->_merge_guest_cart((int) $login['user_id']);
        set_alert('success', 'Welcome back, ' . $name . '!');

        $redirect = $this->session->userdata('redirect_url');
        $this->session->unset_userdata('redirect_url');
        redirect($redirect ?: base_url('account'));
    }

    public function register()
    {
        if (is_customer_loggedin()) {
            redirect(base_url('account'));
            return;
        }
        $this->_render('landing/account/register', 'Create account');
    }

    public function do_register()
    {
        if (is_customer_loggedin()) {
            redirect(base_url('account'));
            return;
        }
        require_once APPPATH . 'requests/Register_Request.php';
        $req = new Register_Request();
        if (!$req->validate()) {
            set_alert('error', $req->first_error());
            redirect($req->back());
            return;
        }
        $name     = trim((string) $this->input->post('name'));
        $email    = trim((string) $this->input->post('email'));
        $phone    = trim((string) $this->input->post('phone'));
        $password = (string) $this->input->post('password');
        $confirm  = (string) $this->input->post('password_confirm');

        if ($name === '' || $email === '' || $password === '') {
            set_alert('error', 'Name, email and password are required.');
            redirect(base_url('account/register'));
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_alert('error', 'Please enter a valid email address.');
            redirect(base_url('account/register'));
            return;
        }
        if ($password !== $confirm) {
            set_alert('error', 'Passwords do not match.');
            redirect(base_url('account/register'));
            return;
        }
        $valid = $this->app_lib->validate_password($password);
        if ($valid !== true) {
            set_alert('error', is_string($valid) ? $valid : 'Password does not meet the complexity requirements.');
            redirect(base_url('account/register'));
            return;
        }
        if ($this->customer_model->email_exists($email)) {
            set_alert('error', 'This email is already registered. Please log in instead.');
            redirect(base_url('account/login'));
            return;
        }

        $res = $this->customer_model->register([
            'name'          => $name,
            'email'         => $email,
            'phone'         => $phone,
            'password_hash' => $this->app_lib->pass_hashed($password),
        ]);
        if (!$res) {
            set_alert('error', 'Registration failed. Please try again.');
            redirect(base_url('account/register'));
            return;
        }

        // Email verification (best-effort, non-blocking — the account is still usable).
        $token = $this->customer_model->create_verify_token($res['user_id']);
        if ($token) {
            $this->_send_verification_email($email, $name, $token);
        }

        $this->_login_session($res['user_id'], $name, $email);
        $this->_merge_guest_cart($res['user_id']);
        set_alert('success', 'Welcome, ' . $name . '! Your account has been created. Please check your email to verify your address.');
        redirect(base_url('account'));
    }

    /** Public: confirm an email address from the link sent at registration. */
    public function verify_email($token = '')
    {
        $u = $this->customer_model->verify_by_token(rawurldecode((string) $token));
        if ($u) {
            set_alert('success', 'Your email has been verified. Thank you!');
        } else {
            set_alert('error', 'That verification link is invalid or has already been used.');
        }
        redirect(base_url(is_customer_loggedin() ? 'landing/account' : 'landing/account/login'));
    }

    /** Logged-in: re-send the verification email. */
    public function resend_verification()
    {
        if (!$this->_guard()) {
            return;
        }
        $uid     = customer_id();
        $profile = $this->customer_model->get_profile($uid);
        $token   = $this->customer_model->create_verify_token($uid);
        if ($token && $profile) {
            $this->_send_verification_email($profile['email'], $profile['name'], $token);
            set_alert('success', 'Verification email sent to ' . $profile['email'] . '.');
        } else {
            set_alert('error', 'Could not send the verification email.');
        }
        redirect(base_url('account'));
    }

    private function _send_verification_email($email, $name, $token)
    {
        try {
            $this->load->model('email_model');
            $link = base_url('account/verify-email/' . $token);
            $site = get_global_setting('site_name') ?: 'Our Store';
            $body  = '<p>Hi ' . html_escape($name) . ',</p>';
            $body .= '<p>Please confirm your email address for ' . html_escape($site) . ' by clicking the link below:</p>';
            $body .= '<p><a href="' . $link . '">' . $link . '</a></p>';
            $this->email_model->sendMail($email, 'Verify your email address', $body);
        } catch (\Throwable $e) {
            log_message('error', 'Verification email failed: ' . $e->getMessage());
        }
    }

    public function logout()
    {
        $this->session->unset_userdata('store_customer');
        set_alert('success', 'You have been logged out.');
        redirect(base_url('/'));
    }

    // ---------------------------------------------------------------- account

    public function index()
    {
        if (!$this->_guard()) {
            return;
        }
        $uid = customer_id();
        $this->data['profile'] = $this->customer_model->get_profile($uid);
        $recent = $this->order_model->get_customer_orders($uid, 1, 5);
        $this->data['orders'] = $recent['items'];
        $this->_render('landing/account/dashboard', 'My Account');
    }

    /** Logged-in: customer help & support centre. */
    public function support()
    {
        if (!$this->_guard()) {
            return;
        }
        $uid = customer_id();
        $this->data['profile'] = $this->customer_model->get_profile($uid);
        $recent = $this->order_model->get_customer_orders($uid, 1, 5);
        $this->data['orders']  = $recent['items'];
        $this->_render('landing/account/support', 'Help & Support');
    }

    public function orders()
    {
        if (!$this->_guard()) {
            return;
        }
        $page = max(1, (int) ($this->input->get('page') ?: 1));
        $res  = $this->order_model->get_customer_orders(customer_id(), $page, 20);
        $this->data['orders'] = $res['items'];
        $this->data['total']  = $res['total'];
        $this->data['page']   = $page;
        $this->_render('landing/account/orders', 'My Orders');
    }

    public function update_profile()
    {
        if (!$this->_guard()) {
            return;
        }
        $uid  = customer_id();
        $name = trim((string) $this->input->post('name'));
        if ($name === '') {
            set_alert('error', 'Name is required.');
            redirect(base_url('account'));
            return;
        }
        $this->customer_model->update_profile($uid, [
            'name'      => $name,
            'mobile_no' => trim((string) $this->input->post('phone')),
            'address'   => trim((string) $this->input->post('address')),
        ]);
        $c = current_customer();
        $c['name'] = $name;
        $this->session->set_userdata('store_customer', $c);
        set_alert('success', 'Your profile has been updated.');
        redirect(base_url('account'));
    }

    // ---------------------------------------------------------------- wishlist

    public function wishlist()
    {
        if (!$this->_guard()) {
            return;
        }
        $this->data['items'] = $this->wishlist_model->list_detailed(customer_id());
        $this->_render('landing/account/wishlist', 'My Wishlist');
    }

    public function wishlist_toggle()
    {
        if (!is_customer_loggedin()) {
            redirect(base_url('account/login'));
            return;
        }
        $pid = (int) $this->input->post('product_id');
        $back = $this->input->post('redirect') ?: ($_SERVER['HTTP_REFERER'] ?? base_url('/'));
        if ($pid > 0) {
            $action = $this->wishlist_model->toggle(customer_id(), $pid);
            set_alert('success', $action === 'added' ? 'Added to your wishlist.' : 'Removed from your wishlist.');
        }
        redirect($back);
    }

    public function wishlist_remove()
    {
        if (!$this->_guard()) {
            return;
        }
        $pid = (int) $this->input->post('product_id');
        if ($pid > 0) {
            $this->wishlist_model->remove(customer_id(), $pid);
            set_alert('success', 'Removed from your wishlist.');
        }
        redirect(base_url('account/wishlist'));
    }

    // ---------------------------------------------------------------- downloads

    public function downloads()
    {
        if (!$this->_guard()) {
            return;
        }
        $this->load->model('download_model');
        $this->data['downloads'] = $this->download_model->list_for_user(customer_id());
        $this->_render('landing/account/downloads', 'My Downloads');
    }

    // ---------------------------------------------------------------- returns

    public function returns()
    {
        if (!$this->_guard()) {
            return;
        }
        $this->data['returns'] = $this->return_model->get_for_customer(customer_id());
        $this->_render('landing/account/returns', 'My Returns');
    }

    public function return_form($order_number = '')
    {
        if (!$this->_guard()) {
            return;
        }
        $order = $this->order_model->get_by_number(rawurldecode($order_number), customer_id());
        if (!$order || !$this->_returnable($order)) {
            set_alert('error', 'This order is not eligible for a return.');
            redirect(base_url('account/orders'));
            return;
        }
        $this->data['order'] = $order;
        $this->data['items'] = $this->order_model->get_items($order['id']);
        $this->_render('landing/account/return_form', 'Request a Return');
    }

    public function submit_return()
    {
        if (!$this->_guard()) {
            return;
        }
        $order = $this->order_model->get_by_number((string) $this->input->post('order_number'), customer_id());
        if (!$order || !$this->_returnable($order)) {
            set_alert('error', 'This order is not eligible for a return.');
            redirect(base_url('account/orders'));
            return;
        }
        $qtys = (array) $this->input->post('return_qty'); // order_item_id => qty
        $by_id = [];
        foreach ($this->order_model->get_items($order['id']) as $oi) {
            $by_id[(int) $oi['id']] = $oi;
        }
        $items = [];
        foreach ($qtys as $oiid => $q) {
            $oiid = (int) $oiid;
            $q = (int) $q;
            if ($q > 0 && isset($by_id[$oiid])) {
                $items[] = [
                    'order_item_id' => $oiid,
                    'product_name'  => $by_id[$oiid]['product_name'],
                    'quantity'      => min($q, (int) $by_id[$oiid]['quantity']),
                ];
            }
        }
        if (empty($items)) {
            set_alert('error', 'Please select at least one item to return.');
            redirect(base_url('account/return/' . rawurlencode($order['order_number'])));
            return;
        }
        $rma = $this->return_model->create_request(
            $order['id'],
            ['user_id' => customer_id(), 'guest_token' => null],
            $this->input->post('reason'),
            $this->input->post('note'),
            $items,
            $this->input->post('request_type') === 'exchange' ? 'exchange' : 'return'
        );
        if ($rma) {
            set_alert('success', 'Return request ' . $rma . ' submitted. We will review it shortly.');
        } else {
            set_alert('error', 'Could not submit your return request.');
        }
        redirect(base_url('account/returns'));
    }

    private function _returnable($order)
    {
        return in_array($order['status'], ['delivered', 'completed'], true)
            && !$this->return_model->has_open_return($order['id']);
    }

    // ------------------------------------------------------------- complaints

    public function complaints()
    {
        if (!$this->_guard()) {
            return;
        }
        $this->data['complaints'] = $this->complaint_model->for_customer(customer_id());
        $this->_render('landing/account/complaints', 'My Complaints');
    }

    public function complaint_form()
    {
        if (!$this->_guard()) {
            return;
        }
        $this->_render('landing/account/complaint_form', 'File a Complaint');
    }

    public function submit_complaint()
    {
        if (!$this->_guard()) {
            return;
        }
        $subject = trim((string) $this->input->post('subject'));
        $message = trim((string) $this->input->post('message'));
        if ($subject === '' || $message === '') {
            set_alert('error', 'Subject and message are required.');
            redirect(base_url('account/complaint_form'));
            return;
        }
        $c = current_customer();
        $profile = $this->customer_model->get_profile(customer_id());
        $order_id = (int) $this->input->post('order_id') ?: null;
        $id = $this->complaint_model->insert([
            'customer_id' => customer_id(),
            'name'        => $c['name'] ?? '',
            'email'       => $c['email'] ?? '',
            'phone'       => $profile['mobile_no'] ?? null,
            'order_id'    => $order_id,
            'subject'     => $subject,
            'message'     => $message,
            'status'      => 'New',
        ]);
        if ($id) {
            set_alert('success', 'Your complaint has been submitted. Our team will review it shortly.');
            redirect(base_url('account/complaints'));
            return;
        }
        set_alert('error', 'Could not submit your complaint. Please try again.');
        redirect(base_url('account/complaint_form'));
    }

    public function complaint_view($hash = '')
    {
        if (!$this->_guard()) {
            return;
        }
        $id = decrypt_id($hash);
        $complaint = $id ? $this->complaint_model->find_for_customer($id, customer_id()) : null;
        if (!$complaint) {
            show_404();
            return;
        }
        $this->data['complaint'] = $complaint;
        $this->data['ticket']    = $this->db->where('complaint_id', $id)->get('support_tickets')->row_array();
        $this->_render('landing/account/complaint_view', 'Complaint');
    }

    // ---------------------------------------------------------------- tickets

    public function tickets()
    {
        if (!$this->_guard()) {
            return;
        }
        $this->data['tickets'] = $this->support_ticket_model->for_customer(customer_id());
        $this->_render('landing/account/tickets', 'My Tickets');
    }

    public function ticket_view($hash = '')
    {
        if (!$this->_guard()) {
            return;
        }
        $id = decrypt_id($hash);
        $ticket = $id ? $this->support_ticket_model->find_for_customer($id, customer_id()) : null;
        if (!$ticket) {
            show_404();
            return;
        }
        $this->data['ticket']  = $ticket;
        $this->data['replies'] = $this->support_ticket_model->replies($id);
        $this->_render('landing/account/ticket_view', $ticket['ticket_number']);
    }

    public function ticket_reply()
    {
        if (!$this->_guard()) {
            return;
        }
        $id = decrypt_id($this->input->post('id'));
        $ticket = $id ? $this->support_ticket_model->find_for_customer($id, customer_id()) : null;
        $message = trim((string) $this->input->post('message'));
        if (!$ticket || $message === '') {
            set_alert('error', 'Please enter a reply.');
            redirect(base_url('account/tickets'));
            return;
        }
        if ($ticket['status'] === 'Closed') {
            set_alert('error', 'This ticket is closed.');
            redirect(base_url('account/ticket_view/' . $this->input->post('id')));
            return;
        }
        $c = current_customer();
        $this->support_ticket_model->add_reply($id, 'customer', customer_id(), ($c['name'] ?? 'Customer'), $message);
        set_alert('success', 'Your reply has been sent.');
        redirect(base_url('account/ticket_view/' . encrypt_id($id)));
    }

    // ---------------------------------------------------------------- helpers

    private function _guard()
    {
        if (!is_customer_loggedin()) {
            // Stash the intended page, then send to the unified /login portal.
            $this->session->set_userdata('redirect_url', current_url());
            redirect(base_url('login'));
            return false;
        }
        return true;
    }

    private function _login_session($user_id, $name, $email)
    {
        $this->session->set_userdata('store_customer', [
            'id'    => (int) $user_id,
            'name'  => $name,
            'email' => $email,
        ]);
    }

    private function _merge_guest_cart($user_id)
    {
        $guest = $this->session->userdata('store_cart_token');
        if ($guest) {
            $this->cart_model->merge_guest_into_user($guest, (int) $user_id);
        }
    }

    /** Lean cart view-model for the header flyout on account pages. */
    private function _mini_cart()
    {
        $o = cart_owner();
        $cart_id = $this->cart_model->get_active_cart_id($o['user_id'], $o['guest_token'], false);
        $items = [];
        $subtotal = 0.0;
        $count = 0;
        if ($cart_id) {
            foreach ($this->cart_model->get_items_detailed($cart_id) as $r) {
                $base = ($r['special_price'] !== null && (float) $r['special_price'] > 0)
                    ? (float) $r['special_price'] : (float) $r['price'];
                $line = $base * (int) $r['quantity'];
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
                $count += (int) $r['quantity'];
            }
        }
        return ['items' => $items, 'count' => $count, 'subtotal' => $subtotal];
    }

    private function _render($view, $title)
    {
        $mini = $this->_mini_cart();
        $this->data['title']          = $title;
        $this->data['content_view']   = $view;
        $this->data['nav_categories'] = $this->landing_model->categories_tree();
        $this->data['cart_count']     = $mini['count'];
        $this->data['mini_cart']      = $mini;
        $this->load->view('landing/pages/layout', $this->data);
    }
}
