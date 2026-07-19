<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Catalog
 * @version  : 1.0.0
 * @author   : Mamun Mia Turan
 * @filename : Product.php
 *
 * Admin CRUD for products. Permission module prefix: `product`.
 */
class Product extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('product_model');
        $this->load->model('category_model');
        $this->load->model('brand_model');
        $this->load->model('download_model');
        $this->load->model('tax_model');
        $this->load->model('attribute_family_model');
        $this->load->model('product_attribute_value_model');
        $this->load->model('composite_model');
        $this->load->model('inventory_model');
        $this->load->helper('catalog');
        $this->load->helper('eav');
    }

    public function index()
    {
        if (!get_permission('product', 'is_view')) {
            access_denied();
        }
        $this->data['categories'] = $this->category_model->get_dropdown();
        $this->data['title']      = translate('products') ?: 'Products';
        $this->data['sub_page']   = 'catalog/product/index';
        $this->data['main_menu']  = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    // ---- CSV export / import ----

    public function export()
    {
        if (!get_permission('product', 'is_view')) {
            access_denied();
        }
        $rows = $this->db->select('p.id, p.name, p.sku, c.name AS category, b.name AS brand, p.price, p.special_price, p.stock_quantity, p.stock_status, p.status, p.tags, p.label', false)
            ->from('products p')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->join('brands b', 'b.id = p.brand_id', 'left')
            ->where('p.deleted_at', null)
            ->order_by('p.id', 'ASC')
            ->get()->result_array();
        $this->_stream_csv('products_' . date('Y-m-d') . '.csv',
            ['ID', 'Name', 'SKU', 'Category', 'Brand', 'Price', 'Special Price', 'Stock', 'Stock Status', 'Status', 'Tags', 'Label'],
            $rows);
    }

    public function import()
    {
        if (!get_permission('product', 'is_add')) {
            access_denied();
        }
        $this->data['title']     = translate('import_products') ?: 'Import Products';
        $this->data['sub_page']  = 'catalog/product/import';
        $this->data['main_menu'] = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    public function import_sample()
    {
        if (!get_permission('product', 'is_add')) {
            access_denied();
        }
        $this->_stream_csv('product_import_sample.csv',
            ['Name', 'SKU', 'Category', 'Brand', 'Price', 'Special Price', 'Stock', 'Status'],
            [['Sample T-Shirt', 'TSHIRT-01', 'Fashion', 'Generic', '499', '399', '50', 'Active']]);
    }

    public function import_csv()
    {
        if (!get_permission('product', 'is_add')) {
            access_denied();
        }
        $this->load->helper('url');
        if (empty($_FILES['csv']['tmp_name']) || !is_uploaded_file($_FILES['csv']['tmp_name'])) {
            set_alert('error', translate('please_choose_a_csv_file') ?: 'Please choose a CSV file to import.');
            redirect(base_url('product/import'));
            return;
        }
        $fh = fopen($_FILES['csv']['tmp_name'], 'r');
        if (!$fh) {
            set_alert('error', 'Could not read the uploaded file.');
            redirect(base_url('product/import'));
            return;
        }
        $header = fgetcsv($fh);
        $map = [];
        foreach ((array) $header as $i => $h) {
            $map[strtolower(trim((string) $h))] = $i;
        }
        // Preload category/brand name -> id lookups.
        $cats = [];
        foreach ($this->db->select('id, name')->where('deleted_at', null)->get('categories')->result_array() as $c) {
            $cats[strtolower($c['name'])] = (int) $c['id'];
        }
        $brands = [];
        foreach ($this->db->select('id, name')->where('deleted_at', null)->get('brands')->result_array() as $b) {
            $brands[strtolower($b['name'])] = (int) $b['id'];
        }
        $added = 0;
        $skipped = 0;
        $now = date('Y-m-d H:i:s');
        $uid = get_loggedin_user_id();
        while (($r = fgetcsv($fh)) !== false) {
            $get = function ($key) use ($r, $map) {
                return (isset($map[$key]) && isset($r[$map[$key]])) ? trim((string) $r[$map[$key]]) : '';
            };
            $name = $get('name');
            if ($name === '') {
                $skipped++;
                continue;
            }
            $base = url_title($name, '-', true) ?: 'product';
            $slug = $base;
            $n = 1;
            while ($this->db->where('slug', $slug)->count_all_results('products') > 0) {
                $slug = $base . '-' . (++$n);
            }
            $stock  = (int) ($get('stock') ?: 0);
            $status = in_array($get('status'), ['Active', 'Inactive', 'Draft'], true) ? $get('status') : 'Draft';
            $ok = $this->db->insert('products', [
                'name'           => $name,
                'slug'           => $slug,
                'sku'            => $get('sku') ?: null,
                'category_id'    => $cats[strtolower($get('category'))] ?? null,
                'brand_id'       => $brands[strtolower($get('brand'))] ?? null,
                'price'          => (float) ($get('price') ?: 0),
                'special_price'  => $get('special price') !== '' ? (float) $get('special price') : null,
                'stock_quantity' => $stock,
                'stock_status'   => $stock > 0 ? 'in_stock' : 'out_of_stock',
                'status'         => $status,
                'product_type'   => 'simple',
                'created_by'     => $uid,
                'created_at'     => $now,
            ]);
            $ok ? $added++ : $skipped++;
        }
        fclose($fh);
        $this->log_activity('create', 'product', 0, "Imported {$added} products from CSV");
        set_alert('success', "Import complete: {$added} added, {$skipped} skipped.");
        redirect(base_url('product'));
    }

    /** Stream an array of rows as a downloadable CSV and end the request. */
    private function _stream_csv($filename, $header, $rows)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        $out = fopen('php://output', 'w');
        fputcsv($out, $header);
        foreach ($rows as $r) {
            fputcsv($out, array_values((array) $r));
        }
        fclose($out);
        exit;
    }

    public function create()
    {
        if (!get_permission('product', 'is_add')) {
            access_denied();
        }
        $this->_form_data(null);
        $this->data['title']     = translate('add_product') ?: 'Add Product';
        $this->data['sub_page']  = 'catalog/product/form';
        $this->data['main_menu'] = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    public function store()
    {
        if (!get_permission('product', 'is_add')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('product'));
            return;
        }

        $this->_set_rules();
        if ($this->form_validation->run() === false) {
            $this->create();
            return;
        }
        $attr_error = $this->_validate_attributes(null);
        if ($attr_error !== null) {
            set_alert('error', $attr_error);
            $this->create();
            return;
        }

        $upload = $this->_upload_image('thumbnail', 'product');
        if (!$upload['ok']) {
            set_alert('error', $upload['error']);
            redirect(base_url('product/create'));
            return;
        }

        $name = $this->input->post('name');
        $data = $this->_collect_post($name);
        $data['slug']       = $this->product_model->unique_slug($name);
        $data['created_by'] = get_loggedin_user_id();
        if ($upload['file']) {
            $data['thumbnail'] = $upload['file'];
        }

        $id = $this->product_model->insert($data);
        if ($id) {
            $this->_save_relations($id);
            $this->log_activity('create', 'product', $id, 'Created product: ' . $name);
            set_alert('success', translate('information_has_been_saved_successfully'));
            redirect(base_url('product'));
            return;
        }
        set_alert('error', translate('information_could_not_be_saved'));
        redirect(base_url('product/create'));
    }

    public function edit($hash = '')
    {
        if (!get_permission('product', 'is_edit')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        $product = $this->product_model->find($id);
        if (empty($product)) {
            show_404();
            return;
        }
        $this->_form_data($product);
        $this->data['title']     = translate('edit_product') ?: 'Edit Product';
        $this->data['sub_page']  = 'catalog/product/form';
        $this->data['main_menu'] = 'catalog';
        $this->load->view('layout/index', $this->data);
    }

    public function update()
    {
        if (!get_permission('product', 'is_edit')) {
            access_denied();
        }
        if (!$_POST) {
            redirect(base_url('product'));
            return;
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            show_404();
            return;
        }

        $this->_set_rules();
        if ($this->form_validation->run() === false) {
            $this->edit($this->input->post('id'));
            return;
        }
        $attr_error = $this->_validate_attributes($id);
        if ($attr_error !== null) {
            set_alert('error', $attr_error);
            $this->edit($this->input->post('id'));
            return;
        }

        $upload = $this->_upload_image('thumbnail', 'product');
        if (!$upload['ok']) {
            set_alert('error', $upload['error']);
            redirect(base_url('product/edit/' . encrypt_id($id)));
            return;
        }

        $name = $this->input->post('name');
        $data = $this->_collect_post($name);
        $data['slug']       = $this->product_model->unique_slug($name, $id);
        $data['updated_by'] = get_loggedin_user_id();
        if ($upload['file']) {
            $data['thumbnail'] = $upload['file'];
        }

        $saved = $this->product_model->update($id, $data);
        // Variants and gallery are stored in their own tables, so sync them
        // regardless of whether the base product row itself changed.
        $this->_save_relations($id);
        if ($saved) {
            $this->log_activity('update', 'product', $id, 'Updated product: ' . $name);
            set_alert('success', translate('information_has_been_updated_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('product'));
    }

    public function delete($hash = '')
    {
        if (!get_permission('product', 'is_delete')) {
            access_denied();
        }
        $id = decrypt_id($hash);
        if (!$id) {
            show_404();
            return;
        }
        if ($this->product_model->delete($id)) {
            $this->log_activity('delete', 'product', $id, 'Deleted product');
            set_alert('success', translate('information_has_been_delete_successfully'));
        } else {
            set_alert('error', translate('information_could_not_be_saved'));
        }
        redirect(base_url('product'));
    }

    public function status()
    {
        if (!get_permission('product', 'is_edit')) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('access_denied')], 403);
        }
        $id = decrypt_id($this->input->post('id'));
        if (!$id) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid ID'], 422);
        }
        $new = $this->product_model->toggle_status($id);
        if ($new === false) {
            return $this->jsonResponse(['status' => 'error', 'message' => translate('information_could_not_be_saved')], 422);
        }
        $this->log_activity('status', 'product', $id, 'Toggled product status to ' . $new);
        return $this->jsonResponse([
            'status'     => 'success',
            'new_status' => $new,
            'message'    => translate('information_has_been_updated_successfully'),
        ]);
    }

    public function get_products_server_side()
    {
        if (!get_permission('product', 'is_view')) {
            return $this->jsonResponse(['error' => 'Access Denied'], 403);
        }

        $draw     = intval($this->input->post('draw'));
        $start    = intval($this->input->post('start'));
        $length   = intval($this->input->post('length'));
        $search   = $this->input->post('search')['value'] ?? '';
        $status   = $this->input->post('status') ?? '';
        $category = $this->input->post('category_id') ?? '';

        $columns_map = [
            0 => 'p.id',
            2 => 'p.name',
            3 => 'p.sku',
            4 => 'category_name',
            5 => 'brand_name',
            6 => 'p.price',
            7 => 'p.stock_quantity',
            8 => 'p.status',
        ];
        $order_idx = $this->input->post('order')[0]['column'] ?? 2;
        $order_dir = strtolower($this->input->post('order')[0]['dir'] ?? 'asc');
        if (!in_array($order_dir, ['asc', 'desc'])) {
            $order_dir = 'asc';
        }
        $order_col = $columns_map[$order_idx] ?? 'p.name';

        $total = $this->product_model->count_all($status);
        $res   = $this->product_model->datatable($search, $start, $length, $order_col, $order_dir, $status, $category);

        $can_edit   = get_permission('product', 'is_edit');
        $can_delete = get_permission('product', 'is_delete');
        $symbol     = get_global_setting('currency_symbol') ?: '৳';

        $data = [];
        $i = $start + 1;
        foreach ($res['data'] as $row) {
            $thumb_html = $row->thumbnail
                ? '<img class="rounded" src="' . base_url('uploads/catalog/product/' . $row->thumbnail) . '" width="40" height="40" />'
                : '<span class="text-muted"><i class="fas fa-box-open"></i></span>';

            $price_html = html_escape($symbol) . ' ' . number_format((float) $row->price, 2);
            if ($row->special_price !== null && $row->special_price !== '' && (float) $row->special_price > 0) {
                $price_html = '<del class="text-muted">' . $price_html . '</del><br>'
                    . '<span class="text-success">' . html_escape($symbol) . ' ' . number_format((float) $row->special_price, 2) . '</span>';
            }

            $stock_badges = [
                'in_stock'     => 'success',
                'out_of_stock' => 'danger',
                'pre_order'    => 'info',
            ];
            $sb = $stock_badges[$row->stock_status] ?? 'secondary';
            $stock_html = (int) $row->stock_quantity
                . ' <span class="badge badge-' . $sb . '">' . html_escape(str_replace('_', ' ', $row->stock_status)) . '</span>';

            $data[] = [
                $i++,
                $thumb_html,
                html_escape($row->name),
                html_escape($row->sku ?: '—'),
                html_escape($row->category_name ?: '—'),
                html_escape($row->brand_name ?: '—'),
                $price_html,
                $stock_html,
                catalog_status_html($row->status, encrypt_id($row->id), $can_edit),
                catalog_row_actions('product', $row->id, $can_edit, $can_delete),
            ];
        }

        return $this->jsonResponse([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $res['filtered'],
            'data'            => $data,
            'csrfHash'        => $this->security->get_csrf_hash(),
        ]);
    }

    // Callback: SKU uniqueness (empty allowed)
    public function unique_sku($sku)
    {
        $ignore = null;
        if ($this->input->post('id')) {
            $ignore = decrypt_id($this->input->post('id'));
        }
        if (!$this->product_model->unique_sku($sku, $ignore)) {
            $this->form_validation->set_message('unique_sku', translate('sku_has_already_been_used') ?: 'The SKU has already been used.');
            return false;
        }
        return true;
    }

    // ---- helpers ----

    private function _form_data($product)
    {
        $this->data['product']    = $product;
        $this->data['categories'] = $this->category_model->get_dropdown();
        $this->data['brands']     = $this->brand_model->get_dropdown();
        $this->data['variants']   = $product ? $this->product_model->get_variants($product['id']) : [];
        $this->data['images']     = $product ? $this->product_model->get_images($product['id']) : [];
        $this->data['downloads']  = $product ? $this->download_model->get_for_product($product['id']) : [];
        $this->data['tax_categories'] = $this->tax_model->get_dropdown();

        // EAV custom attributes for the product's family (default family on create).
        $family_id = ($product && !empty($product['attribute_family_id']))
            ? (int) $product['attribute_family_id']
            : $this->attribute_family_model->default_family_id();
        $this->data['attribute_families'] = $this->attribute_family_model->get_dropdown();
        $this->data['current_family_id']  = $family_id;
        $this->data['attribute_groups']   = $family_id ? $this->attribute_family_model->grouped_attributes($family_id) : [];
        $this->data['attribute_values']   = $product ? $this->product_attribute_value_model->get_for_product($product['id']) : [];

        // Composite product authoring (grouped items + bundle option groups).
        $this->data['grouped_items']    = $product ? $this->composite_model->grouped_items($product['id']) : [];
        $this->data['bundle_options']   = $product ? $this->composite_model->bundle_options($product['id']) : [];
        $this->data['simple_products']  = $this->product_model->simple_dropdown($product ? $product['id'] : null);

        // Multi-source inventory: per-source stock for the base product.
        $this->data['source_stock']     = $this->inventory_model->per_source($product ? (int) $product['id'] : 0);
    }

    private function _set_rules()
    {
        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('price', 'Price', 'trim|required|numeric');
        $this->form_validation->set_rules('sku', 'SKU', 'trim|max_length[100]|callback_unique_sku');
        $this->form_validation->set_rules('stock_quantity', 'Stock Quantity', 'trim|is_natural');
        $this->form_validation->set_rules('status', 'Status', 'trim|in_list[Draft,Active,Inactive]');
        $this->form_validation->set_rules('stock_status', 'Stock Status', 'trim|in_list[in_stock,out_of_stock,pre_order]');
        $this->form_validation->set_rules('tags', 'Tags', 'trim|max_length[500]');
        $this->form_validation->set_rules('video_url', 'Video URL', 'trim|max_length[255]|valid_url');
        $this->form_validation->set_rules('label', 'Label', 'trim|max_length[40]');
    }

    private function _collect_post($name)
    {
        $type = $this->input->post('product_type');
        $type = in_array($type, ['simple', 'virtual', 'downloadable', 'configurable', 'grouped', 'bundle'], true) ? $type : 'simple';
        return [
            'name'              => $name,
            'sku'               => $this->input->post('sku') ?: null,
            'product_type'      => $type,
            'category_id'       => $this->_nullable_int($this->input->post('category_id')),
            'brand_id'          => $this->_nullable_int($this->input->post('brand_id')),
            'tax_category_id'   => $this->_nullable_int($this->input->post('tax_category_id')),
            'attribute_family_id' => $this->_nullable_int($this->input->post('attribute_family_id')) ?: $this->attribute_family_model->default_family_id(),
            'short_description' => $this->input->post('short_description'),
            'description'       => $this->input->post('description'),
            'price'             => (float) $this->input->post('price'),
            'special_price'     => $this->_nullable_dec($this->input->post('special_price')),
            'cost_price'        => $this->_nullable_dec($this->input->post('cost_price')),
            'currency'          => 'BDT',
            'stock_quantity'    => (int) $this->input->post('stock_quantity'),
            'stock_status'      => $this->input->post('stock_status') ?: 'in_stock',
            'unit'              => $this->input->post('unit'),
            'weight'            => $this->_nullable_dec($this->input->post('weight')),
            'has_variants'      => count($this->_parse_variants()) > 0 ? 1 : 0,
            'is_featured'       => $this->input->post('is_featured') ? 1 : 0,
            'status'            => $this->input->post('status') ?: 'Draft',
            'meta_title'        => $this->input->post('meta_title'),
            'meta_description'  => $this->input->post('meta_description'),
            'tags'              => $this->_nullable_str($this->input->post('tags')),
            'video_url'         => $this->_nullable_str($this->input->post('video_url')),
            'label'             => $this->_nullable_str($this->input->post('label')),
        ];
    }

    private function _nullable_str($value)
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function _nullable_int($value)
    {
        $value = (int) $value;
        return $value > 0 ? $value : null;
    }

    private function _nullable_dec($value)
    {
        $value = trim((string) $value);
        return ($value === '' || !is_numeric($value)) ? null : (float) $value;
    }

    // ---- variants + gallery ----

    /**
     * Persist a product's variants and gallery after the base row is saved.
     */
    private function _save_relations($product_id)
    {
        $this->product_model->save_variants($product_id, $this->_parse_variants());
        $this->_save_gallery($product_id);
        $this->_save_downloads($product_id);
        $this->_save_attributes($product_id);
        $this->_save_grouped_items($product_id);
        $this->_save_bundle_options($product_id);
        $this->_save_inventory_sources($product_id);
    }

    /**
     * Persist per-source stock for the base product from source_qty[<source_id>]
     * and refresh the cached rollup (products.stock_quantity = Σ active sources).
     * The rollup overwrites whatever _collect_post wrote, so it is authoritative.
     */
    private function _save_inventory_sources($product_id)
    {
        $map = $this->input->post('source_qty');
        if (!is_array($map) || empty($map)) {
            return; // no per-source input on this form submit
        }
        $clean = [];
        foreach ($map as $sid => $qty) {
            $clean[(int) $sid] = max(0, (int) $qty);
        }
        $this->inventory_model->save_stock($product_id, $clean);
    }

    /**
     * Persist a bundle product's option groups + their component products from the
     * nested POST arrays. Only relevant when product_type is bundle; other types
     * clear any stale rows.
     */
    private function _save_bundle_options($product_id)
    {
        if (($this->input->post('product_type') ?: 'simple') !== 'bundle') {
            $this->composite_model->save_bundle($product_id, []); // clear if type changed away
            return;
        }
        $labels = (array) $this->input->post('bundle_option_label');
        $types  = (array) $this->input->post('bundle_option_type');
        $ids    = (array) $this->input->post('bundle_option_id');
        $reqs   = (array) $this->input->post('bundle_option_required');
        $prods  = (array) $this->input->post('bundle_option_product');
        $pqtys  = (array) $this->input->post('bundle_option_pqty');

        $options = [];
        foreach ($labels as $i => $label) {
            $products = [];
            $pl = (isset($prods[$i]) && is_array($prods[$i])) ? $prods[$i] : [];
            $ql = (isset($pqtys[$i]) && is_array($pqtys[$i])) ? $pqtys[$i] : [];
            foreach ($pl as $j => $pid) {
                $pid = (int) $pid;
                if ($pid <= 0) {
                    continue;
                }
                $products[] = ['product_id' => $pid, 'qty' => (int) ($ql[$j] ?? 1), 'is_default' => 0];
            }
            $options[] = [
                'id'          => (int) ($ids[$i] ?? 0),
                'label'       => $label,
                'type'        => $types[$i] ?? 'select',
                'is_required' => !empty($reqs[$i]) ? 1 : 0,
                'products'    => $products,
            ];
        }
        $this->composite_model->save_bundle($product_id, $options);
    }

    /**
     * Persist a grouped product's associated items from the parallel POST arrays
     * grouped_product_id[] + grouped_qty[]. Only relevant when product_type is
     * grouped; for other types this simply clears any stale rows.
     */
    private function _save_grouped_items($product_id)
    {
        if (($this->input->post('product_type') ?: 'simple') !== 'grouped') {
            $this->composite_model->save_grouped($product_id, []); // clear if type changed away
            return;
        }
        $ids  = (array) $this->input->post('grouped_product_id');
        $qtys = (array) $this->input->post('grouped_qty');
        $rows = [];
        foreach ($ids as $i => $aid) {
            $rows[] = ['associated_product_id' => (int) $aid, 'qty' => (int) ($qtys[$i] ?? 1)];
        }
        $this->composite_model->save_grouped($product_id, $rows);
    }

    /**
     * Persist the product's EAV custom-attribute values. Inputs are posted under
     * attr[<code>] (attr[<code>][] for multi-value types); save_values() routes
     * each into the correct product_attribute_values column by attribute type.
     */
    private function _save_attributes($product_id)
    {
        $family_id = $this->_nullable_int($this->input->post('attribute_family_id')) ?: $this->attribute_family_model->default_family_id();
        if (!$family_id) {
            return;
        }
        $attrs  = $this->attribute_family_model->family_attributes($family_id);
        $values = $this->input->post('attr');
        $this->product_attribute_value_model->save_values($product_id, is_array($values) ? $values : [], $attrs);
    }

    /**
     * Enforce the family's is_required / is_unique attribute flags before the
     * product row is saved (mirrors Bagisto's request-side EAV validation).
     * Returns an error message, or null when all attribute values are valid.
     */
    private function _validate_attributes($exclude_product_id)
    {
        $family_id = $this->_nullable_int($this->input->post('attribute_family_id')) ?: $this->attribute_family_model->default_family_id();
        if (!$family_id) {
            return null;
        }
        $values = $this->input->post('attr');
        $values = is_array($values) ? $values : [];
        foreach ($this->attribute_family_model->family_attributes($family_id) as $a) {
            $code = $a['code'];
            $raw  = $values[$code] ?? null;

            if (!empty($a['is_required']) && $a['type'] !== 'boolean') {
                $empty = ($a['type'] === 'multiselect' || $a['type'] === 'checkbox')
                    ? empty(array_filter((array) $raw, function ($v) { return $v !== '' && $v !== null; }))
                    : ($raw === null || trim((string) $raw) === '');
                if ($empty) {
                    return sprintf(translate('attribute_is_required') ?: 'The "%s" field is required.', $a['name']);
                }
            }

            if (!empty($a['is_unique']) && $raw !== null && $raw !== '') {
                if (!$this->product_attribute_value_model->is_value_unique($a, $raw, $exclude_product_id)) {
                    return sprintf(translate('attribute_must_be_unique') ?: 'The value for "%s" is already used by another product.', $a['name']);
                }
            }
        }
        return null;
    }

    /**
     * Apply downloadable-file changes: delete checked files, then upload new
     * main + sample files into the protected uploads/downloads/ dir.
     */
    private function _save_downloads($product_id)
    {
        $dir = FCPATH . 'uploads/downloads/';
        foreach ((array) $this->input->post('delete_downloads') as $did) {
            $path = $this->download_model->delete_download((int) $did, $product_id);
            if ($path && is_file($dir . $path)) {
                @unlink($dir . $path);
            }
        }
        foreach ($this->_upload_download_files('download_files') as $f) {
            $this->download_model->add_download($product_id, $f['name'], $f['file'], 0, null, 0);
        }
        foreach ($this->_upload_download_files('sample_files') as $f) {
            $this->download_model->add_download($product_id, $f['name'], $f['file'], 1, null, 0);
        }
    }

    private function _upload_download_files($field)
    {
        if (empty($_FILES[$field]['name']) || empty($_FILES[$field]['name'][0])) {
            return [];
        }
        $dir = FCPATH . 'uploads/downloads/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $this->load->library('upload');
        $out = [];
        $count = count($_FILES[$field]['name']);
        for ($i = 0; $i < $count; $i++) {
            if (empty($_FILES[$field]['name'][$i])) {
                continue;
            }
            $orig = $_FILES[$field]['name'][$i];
            $_FILES['_dl_one'] = [
                'name'     => $orig,
                'type'     => $_FILES[$field]['type'][$i],
                'tmp_name' => $_FILES[$field]['tmp_name'][$i],
                'error'    => $_FILES[$field]['error'][$i],
                'size'     => $_FILES[$field]['size'][$i],
            ];
            $this->upload->initialize([
                'upload_path'   => $dir,
                'allowed_types' => 'zip|pdf|epub|mp3|mp4|png|jpg|jpeg|gif|webp|doc|docx|xls|xlsx|ppt|pptx|csv|txt|psd|ai|svg',
                'max_size'      => 51200, // 50 MB
                'encrypt_name'  => true,
            ]);
            if ($this->upload->do_upload('_dl_one')) {
                $out[] = ['name' => (pathinfo($orig, PATHINFO_FILENAME) ?: $this->upload->data('file_name')), 'file' => $this->upload->data('file_name')];
            }
        }
        return $out;
    }

    /**
     * Read the parallel variant_* POST arrays into clean rows. Blank rows (no
     * name) are skipped. `attributes` is normalised to a JSON string or null.
     */
    private function _parse_variants()
    {
        $ids      = (array) ($this->input->post('variant_id') ?: []);
        $names    = (array) ($this->input->post('variant_name') ?: []);
        $skus     = (array) ($this->input->post('variant_sku') ?: []);
        $prices   = (array) ($this->input->post('variant_price') ?: []);
        $specials = (array) ($this->input->post('variant_special_price') ?: []);
        $stocks   = (array) ($this->input->post('variant_stock') ?: []);
        $attrs    = (array) ($this->input->post('variant_attributes') ?: []);
        $statuses = (array) ($this->input->post('variant_status') ?: []);

        $rows = [];
        foreach ($names as $i => $name) {
            if (trim((string) $name) === '') {
                continue;
            }
            $rows[] = [
                'id'             => (int) ($ids[$i] ?? 0),
                'name'           => trim((string) $name),
                'sku'            => trim((string) ($skus[$i] ?? '')),
                'price'          => $prices[$i] ?? '',
                'special_price'  => $specials[$i] ?? '',
                'stock_quantity' => (int) ($stocks[$i] ?? 0),
                'attributes'     => $this->_parse_attr_string($attrs[$i] ?? ''),
                'status'         => ($statuses[$i] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active',
            ];
        }
        return $rows;
    }

    /**
     * "Color=Red; Size=M" (or comma-separated) -> JSON {"Color":"Red","Size":"M"}.
     * Returns null when nothing parseable is present.
     */
    private function _parse_attr_string($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        $pairs = preg_split('/[;,\n]+/', $raw);
        $out = [];
        foreach ($pairs as $pair) {
            if (strpos($pair, '=') === false) {
                continue;
            }
            list($k, $v) = array_map('trim', explode('=', $pair, 2));
            if ($k !== '') {
                $out[$k] = $v;
            }
        }
        return empty($out) ? null : json_encode($out, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Apply gallery changes from the form: delete checked images, upload new
     * files, then ensure exactly one primary image exists.
     */
    private function _save_gallery($product_id)
    {
        $dir = FCPATH . 'uploads/catalog/product/';

        // 1. Deletions
        foreach ((array) $this->input->post('delete_images') as $img_id) {
            $path = $this->product_model->delete_image((int) $img_id, $product_id);
            if ($path && is_file($dir . $path)) {
                @unlink($dir . $path);
            }
        }

        // 2. New uploads (multi-file)
        $files = $this->_upload_gallery('gallery');
        if (!empty($files)) {
            $sort = 0;
            foreach ($this->product_model->get_images($product_id) as $e) {
                $sort = max($sort, (int) $e['sort_order']);
            }
            foreach ($files as $f) {
                $this->product_model->add_image($product_id, $f, 0, ++$sort);
            }
        }

        // 3. Primary selection (explicit radio wins; else guarantee one primary)
        $primary = (int) $this->input->post('primary_image');
        if ($primary) {
            $this->product_model->set_primary_image($primary, $product_id);
        } else {
            $imgs = $this->product_model->get_images($product_id);
            $has_primary = false;
            foreach ($imgs as $im) {
                if (!empty($im['is_primary'])) {
                    $has_primary = true;
                    break;
                }
            }
            if (!$has_primary && !empty($imgs)) {
                $this->product_model->set_primary_image($imgs[0]['id'], $product_id);
            }
        }
    }

    /**
     * Upload the multi-file gallery[] field. Returns the stored filenames.
     */
    private function _upload_gallery($field)
    {
        if (empty($_FILES[$field]['name']) || empty($_FILES[$field]['name'][0])) {
            return [];
        }
        $dir = FCPATH . 'uploads/catalog/product/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $this->load->library('upload');

        $files = [];
        $count = count($_FILES[$field]['name']);
        for ($i = 0; $i < $count; $i++) {
            if (empty($_FILES[$field]['name'][$i])) {
                continue;
            }
            $_FILES['_gallery_one'] = [
                'name'     => $_FILES[$field]['name'][$i],
                'type'     => $_FILES[$field]['type'][$i],
                'tmp_name' => $_FILES[$field]['tmp_name'][$i],
                'error'    => $_FILES[$field]['error'][$i],
                'size'     => $_FILES[$field]['size'][$i],
            ];
            $this->upload->initialize([
                'upload_path'   => $dir,
                'allowed_types' => 'jpg|jpeg|png|gif|webp',
                'max_size'      => 4096,
                'encrypt_name'  => true,
            ]);
            if ($this->upload->do_upload('_gallery_one')) {
                $files[] = $this->upload->data('file_name');
            }
        }
        return $files;
    }

    private function _upload_image($field, $subdir)
    {
        if (empty($_FILES[$field]['name'])) {
            return ['ok' => true, 'file' => null];
        }
        $dir = FCPATH . 'uploads/catalog/' . $subdir . '/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $this->load->library('upload');
        $this->upload->initialize([
            'upload_path'   => $dir,
            'allowed_types' => 'jpg|jpeg|png|gif|webp',
            'max_size'      => 4096,
            'encrypt_name'  => true,
        ]);
        if ($this->upload->do_upload($field)) {
            return ['ok' => true, 'file' => $this->upload->data('file_name')];
        }
        return ['ok' => false, 'error' => strip_tags($this->upload->display_errors('', ' '))];
    }
}
