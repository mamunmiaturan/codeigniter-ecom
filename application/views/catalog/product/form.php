<?php
$is_edit = !empty($product);
$action  = $is_edit ? base_url('product/update') : base_url('product/store');
$symbol  = get_global_setting('currency_symbol') ?: '৳';
$none    = translate('none') ?: '— None —';
$variants = isset($variants) ? $variants : [];
$images   = isset($images) ? $images : [];
$downloads = isset($downloads) ? $downloads : [];
$cur_type  = $is_edit ? ($product['product_type'] ?? 'simple') : 'simple';
?>
<section class="panel">
	<?php echo form_open_multipart($action); ?>
	<?php if ($is_edit): ?>
		<input type="hidden" name="id" value="<?php echo encrypt_id($product['id']); ?>">
	<?php endif; ?>
	<div class="panel-heading">
		<h4 class="panel-title">
			<i class="fas fa-box-open"></i>
			<?php echo $is_edit ? (translate('edit_product') ?: 'Edit Product') : (translate('add_product') ?: 'Add Product'); ?>
		</h4>
	</div>
	<div class="panel-body">
		<div class="row">
			<div class="col-md-6 mb-sm">
				<div class="form-group <?php if (form_error('name')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('name'); ?> <span class="required">*</span></label>
					<input class="form-control" name="name" type="text" value="<?php echo set_value('name', $is_edit ? $product['name'] : ''); ?>">
					<span class="error"><?php echo form_error('name'); ?></span>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group <?php if (form_error('sku')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('sku') ?: 'SKU'; ?></label>
					<input class="form-control" name="sku" type="text" value="<?php echo set_value('sku', $is_edit ? $product['sku'] : ''); ?>">
					<span class="error"><?php echo form_error('sku'); ?></span>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('status'); ?></label>
					<?php
					$status_options = [
						'Draft'    => (translate('draft') ?: 'Draft'),
						'Active'   => (translate('active') ?: 'Active'),
						'Inactive' => (translate('inactive') ?: 'Inactive'),
					];
					echo form_dropdown('status', $status_options, set_value('status', $is_edit ? $product['status'] : 'Draft'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('category') ?: 'Category'; ?></label>
					<?php
					$cat_options = ['' => $none] + $categories;
					echo form_dropdown('category_id', $cat_options, set_value('category_id', $is_edit ? $product['category_id'] : ''), "class='form-control' data-plugin-selectTwo data-width='100%'");
					?>
				</div>
			</div>
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('brand') ?: 'Brand'; ?></label>
					<?php
					$brand_options = ['' => $none] + $brands;
					echo form_dropdown('brand_id', $brand_options, set_value('brand_id', $is_edit ? $product['brand_id'] : ''), "class='form-control' data-plugin-selectTwo data-width='100%'");
					?>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('product_type') ?: 'Product Type'; ?></label>
					<?php
					$type_options = [
						'simple'       => (translate('type_simple') ?: 'Simple (physical)'),
						'virtual'      => (translate('type_virtual') ?: 'Virtual (service — no shipping)'),
						'downloadable' => (translate('type_downloadable') ?: 'Downloadable (digital files)'),
						'configurable' => (translate('type_configurable') ?: 'Configurable (variants)'),
						'grouped'      => (translate('type_grouped') ?: 'Grouped (list of products)'),
						'bundle'       => (translate('type_bundle') ?: 'Bundle (kit of components)'),
					];
					echo form_dropdown('product_type', $type_options, set_value('product_type', $cur_type), "class='form-control' id='product_type' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
					<small class="text-muted"><?php echo translate('product_type_hint') ?: 'Virtual and downloadable products skip shipping and stock. Downloadable products deliver files after payment.'; ?></small>
				</div>
			</div>
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('tax_category') ?: 'Tax Category'; ?></label>
					<?php
					$tax_cats = isset($tax_categories) ? $tax_categories : [];
					$tax_options = ['' => $none] + $tax_cats;
					echo form_dropdown('tax_category_id', $tax_options, set_value('tax_category_id', $is_edit ? ($product['tax_category_id'] ?? '') : ''), "class='form-control' data-plugin-selectTwo data-width='100%'");
					?>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('attribute_family') ?: 'Attribute Family'; ?></label>
					<?php
					$fam_opts = isset($attribute_families) ? $attribute_families : [];
					echo form_dropdown('attribute_family_id', $fam_opts, set_value('attribute_family_id', isset($current_family_id) ? $current_family_id : ''), "class='form-control' data-plugin-selectTwo data-width='100%'");
					?>
					<small class="text-muted"><?php echo translate('attribute_family_hint') ?: 'Which custom attributes this product has. Change + save to load a different family\'s fields.'; ?></small>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-3 mb-sm">
				<div class="form-group <?php if (form_error('price')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('price') ?: 'Price'; ?> (<?php echo html_escape($symbol); ?>) <span class="required">*</span></label>
					<input class="form-control" name="price" type="number" step="0.01" min="0" value="<?php echo set_value('price', $is_edit ? $product['price'] : '0.00'); ?>">
					<span class="error"><?php echo form_error('price'); ?></span>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('special_price') ?: 'Special Price'; ?> (<?php echo html_escape($symbol); ?>)</label>
					<input class="form-control" name="special_price" type="number" step="0.01" min="0" value="<?php echo set_value('special_price', $is_edit ? $product['special_price'] : ''); ?>">
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('cost_price') ?: 'Cost Price'; ?> (<?php echo html_escape($symbol); ?>)</label>
					<input class="form-control" name="cost_price" type="number" step="0.01" min="0" value="<?php echo set_value('cost_price', $is_edit ? $product['cost_price'] : ''); ?>">
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label" style="display:block;"><?php echo translate('is_featured') ?: 'Featured'; ?></label>
					<label class="switch"><input type="checkbox" name="is_featured" value="1" <?php echo set_checkbox('is_featured', '1', $is_edit ? !empty($product['is_featured']) : false); ?>> <?php echo translate('featured') ?: 'Featured'; ?></label>
				</div>
			</div>
		</div>
		<div class="row" id="physical-fields">
			<div class="col-md-3 mb-sm">
				<div class="form-group <?php if (form_error('stock_quantity')) echo 'has-error'; ?>">
					<label class="control-label"><?php echo translate('stock_quantity') ?: 'Stock Quantity'; ?></label>
					<?php $source_stock = isset($source_stock) ? $source_stock : []; ?>
					<?php if (empty($source_stock)): ?>
						<input class="form-control" name="stock_quantity" type="number" min="0" value="<?php echo set_value('stock_quantity', $is_edit ? $product['stock_quantity'] : 0); ?>">
					<?php else: ?>
						<?php /* Per-source stock; the rollup (stock_quantity) is recomputed on save. */ ?>
						<input type="hidden" name="stock_quantity" value="<?php echo (int) ($is_edit ? $product['stock_quantity'] : 0); ?>">
						<?php foreach ($source_stock as $ss): ?>
							<div class="mb-1">
								<input class="form-control" type="number" min="0" name="source_qty[<?php echo (int) $ss['inventory_source_id']; ?>]" value="<?php echo (int) $ss['qty']; ?>">
								<small class="text-muted"><?php echo html_escape($ss['source_name']); ?><?php echo $ss['source_status'] === 'Inactive' ? ' <span class="text-danger">(inactive)</span>' : ''; ?></small>
							</div>
						<?php endforeach; ?>
						<small class="text-muted d-block"><?php echo translate('stock_by_source_hint') ?: 'On-hand per warehouse; total = sum of active sources.'; ?></small>
					<?php endif; ?>
					<span class="error"><?php echo form_error('stock_quantity'); ?></span>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('stock_status') ?: 'Stock Status'; ?></label>
					<?php
					$stock_options = [
						'in_stock'     => (translate('in_stock') ?: 'In Stock'),
						'out_of_stock' => (translate('out_of_stock') ?: 'Out of Stock'),
						'pre_order'    => (translate('pre_order') ?: 'Pre Order'),
					];
					echo form_dropdown('stock_status', $stock_options, set_value('stock_status', $is_edit ? $product['stock_status'] : 'in_stock'), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('unit') ?: 'Unit'; ?></label>
					<input class="form-control" name="unit" type="text" placeholder="pcs, kg, box" value="<?php echo set_value('unit', $is_edit ? $product['unit'] : ''); ?>">
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('weight') ?: 'Weight (kg)'; ?></label>
					<input class="form-control" name="weight" type="number" step="0.01" min="0" value="<?php echo set_value('weight', $is_edit ? $product['weight'] : ''); ?>">
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('short_description') ?: 'Short Description'; ?></label>
					<textarea class="form-control" rows="2" name="short_description"><?php echo set_value('short_description', $is_edit ? $product['short_description'] : ''); ?></textarea>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('description'); ?></label>
					<textarea class="form-control" rows="5" name="description"><?php echo set_value('description', $is_edit ? $product['description'] : ''); ?></textarea>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('tags') ?: 'Tags'; ?></label>
					<input class="form-control" name="tags" type="text" placeholder="tag1, tag2, tag3" value="<?php echo set_value('tags', $is_edit ? ($product['tags'] ?? '') : ''); ?>">
					<small class="text-muted"><?php echo translate('tags_hint') ?: 'Comma-separated keywords shown as badges on the product page.'; ?></small>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('video_url') ?: 'Video URL'; ?></label>
					<input class="form-control" name="video_url" type="url" placeholder="https://youtu.be/…" value="<?php echo set_value('video_url', $is_edit ? ($product['video_url'] ?? '') : ''); ?>">
					<small class="text-muted"><?php echo translate('video_url_hint') ?: 'YouTube / Vimeo link or a direct .mp4 URL.'; ?></small>
				</div>
			</div>
			<div class="col-md-3 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('label') ?: 'Label'; ?></label>
					<?php
					$label_options = [
						'' => $none,
						'New'         => (translate('label_new') ?: 'New'),
						'Hot'         => (translate('label_hot') ?: 'Hot'),
						'Sale'        => (translate('label_sale') ?: 'Sale'),
						'Best Seller' => (translate('label_best_seller') ?: 'Best Seller'),
						'Limited'     => (translate('label_limited') ?: 'Limited'),
					];
					echo form_dropdown('label', $label_options, set_value('label', $is_edit ? ($product['label'] ?? '') : ''), "class='form-control' data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
					?>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label><?php echo translate('thumbnail') ?: 'Thumbnail'; ?></label>
					<?php $cur = ($is_edit && !empty($product['thumbnail'])) ? base_url('uploads/catalog/product/' . $product['thumbnail']) : ''; ?>
					<input type="file" name="thumbnail" class="dropify" data-allowed-file-extensions="jpg jpeg png gif webp" data-height="120" <?php echo $cur ? 'data-default-file="' . $cur . '"' : ''; ?> />
				</div>
			</div>

			<!-- ================= Gallery (multiple images) ================= -->
			<div class="col-md-12 mb-sm">
				<div class="form-group">
					<label><?php echo translate('gallery_images') ?: 'Gallery Images'; ?></label>
					<small class="text-muted d-block mb-2"><?php echo translate('gallery_images_hint') ?: 'Add multiple images. Choose one as the primary (shown first); tick “Remove” to delete an existing image.'; ?></small>
					<?php if (!empty($images)): ?>
					<div class="row g-2 mb-2">
						<?php foreach ($images as $img): ?>
							<div class="col-4 col-md-2 mb-2">
								<div style="border:1px solid #e6e8eb;border-radius:8px;padding:6px;text-align:center;">
									<img src="<?php echo base_url('uploads/catalog/product/' . rawurlencode($img['image_path'])); ?>" alt="" style="width:100%;height:84px;object-fit:cover;border-radius:6px;">
									<label style="display:block;font-size:12px;margin:5px 0 0;cursor:pointer;">
										<input type="radio" name="primary_image" value="<?php echo (int) $img['id']; ?>" <?php echo !empty($img['is_primary']) ? 'checked' : ''; ?>> <?php echo translate('primary') ?: 'Primary'; ?>
									</label>
									<label style="display:block;font-size:12px;color:#d9534f;cursor:pointer;margin:0;">
										<input type="checkbox" name="delete_images[]" value="<?php echo (int) $img['id']; ?>"> <?php echo translate('remove') ?: 'Remove'; ?>
									</label>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>
					<!-- Incremental multi-image picker: click "Add Images" as many times
					     as you like; each pick is appended (with a preview + remove). -->
					<input type="file" id="galleryInput" name="gallery[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple hidden>
					<button type="button" id="galleryAddBtn" class="btn btn-default btn-sm"><i class="fas fa-plus"></i> <?php echo translate('add_images') ?: 'Add Images'; ?></button>
					<div id="galleryPreview" class="d-flex flex-wrap mt-2" style="gap:10px;"></div>
				</div>
			</div>

			<div class="col-md-6 mb-sm">
				<div class="form-group">
					<label class="control-label"><?php echo translate('meta_title') ?: 'Meta Title'; ?></label>
					<input class="form-control" name="meta_title" type="text" value="<?php echo set_value('meta_title', $is_edit ? $product['meta_title'] : ''); ?>">
				</div>
				<div class="form-group">
					<label class="control-label"><?php echo translate('meta_description') ?: 'Meta Description'; ?></label>
					<input class="form-control" name="meta_description" type="text" value="<?php echo set_value('meta_description', $is_edit ? $product['meta_description'] : ''); ?>">
				</div>
			</div>
		</div>

		<!-- ================= Custom Attributes (EAV) ================= -->
		<?php if (!empty($attribute_groups)):
			$attr_values = isset($attribute_values) ? $attribute_values : []; ?>
			<hr>
			<h5 class="mb-1"><i class="fas fa-tags"></i> <?php echo translate('custom_attributes') ?: 'Custom Attributes'; ?></h5>
			<?php foreach ($attribute_groups as $grp):
				if (empty($grp['attributes'])) { continue; } ?>
				<h6 class="text-muted mt-3 mb-2"><?php echo html_escape($grp['group']['name']); ?></h6>
				<div class="row">
					<?php foreach ($grp['attributes'] as $attr):
						$code   = $attr['code'];
						$fname  = 'attr[' . $code . ']';
						$stored = array_key_exists((int) $attr['id'], $attr_values) ? $attr_values[(int) $attr['id']] : (string) ($attr['default_value'] ?? '');
						$req    = !empty($attr['is_required']) ? ' <span class="required">*</span>' : ''; ?>
						<div class="col-md-4 mb-sm">
							<div class="form-group">
								<label class="control-label"><?php echo html_escape($attr['name']); ?><?php echo $req; ?></label>
								<?php
								switch ($attr['type']):
									case 'select':
										$opts = ['' => $none];
										foreach ($attr['options'] as $o) { $opts[$o['id']] = $o['label']; }
										echo form_dropdown($fname, $opts, set_value($fname, $stored), "class='form-control' data-plugin-selectTwo data-width='100%'");
										break;
									case 'multiselect':
									case 'checkbox':
										$sel = is_array($stored) ? $stored : (strlen((string) $stored) ? explode(',', (string) $stored) : []);
										$sel = array_map('strval', $sel);
										// Sentinel so deselecting every option still posts the key (empty -> cleared).
										echo '<input type="hidden" name="' . $fname . '[]" value="">';
										echo '<select name="' . $fname . '[]" multiple class="form-control" data-plugin-selectTwo data-width="100%">';
										foreach ($attr['options'] as $o) {
											$s = in_array((string) $o['id'], $sel, true) ? 'selected' : '';
											echo '<option value="' . (int) $o['id'] . '" ' . $s . '>' . html_escape($o['label']) . '</option>';
										}
										echo '</select>';
										break;
									case 'boolean':
										echo '<label class="switch"><input type="hidden" name="' . $fname . '" value="0"><input type="checkbox" name="' . $fname . '" value="1" ' . (!empty($stored) ? 'checked' : '') . '> ' . (translate('yes') ?: 'Yes') . '</label>';
										break;
									case 'textarea':
										echo '<textarea class="form-control" rows="2" name="' . $fname . '">' . html_escape(set_value($fname, (string) $stored)) . '</textarea>';
										break;
									case 'price':
										echo '<input class="form-control" type="number" step="0.01" min="0" name="' . $fname . '" value="' . html_escape(set_value($fname, (string) $stored)) . '">';
										break;
									case 'date':
										echo '<input class="form-control" type="date" name="' . $fname . '" value="' . html_escape(set_value($fname, (string) $stored)) . '">';
										break;
									case 'datetime':
										echo '<input class="form-control" type="datetime-local" name="' . $fname . '" value="' . html_escape(set_value($fname, (string) $stored)) . '">';
										break;
									default: // text (image/file authoring not supported here yet)
										echo '<input class="form-control" type="text" name="' . $fname . '" value="' . html_escape(set_value($fname, (string) $stored)) . '">';
								endswitch;
								?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>

		<!-- ================= Downloadable files ================= -->
		<div id="downloadable-section" style="<?php echo $cur_type === 'downloadable' ? '' : 'display:none;'; ?>">
			<hr>
			<h5 class="mb-1"><i class="fas fa-download"></i> <?php echo translate('downloadable_files') ?: 'Downloadable Files'; ?></h5>
			<p class="text-muted" style="font-size:13px;">
				<?php echo translate('downloadable_files_hint') ?: 'Main files are delivered to the customer after payment. Sample files are downloadable for free from the product page.'; ?>
			</p>
			<?php if ($is_edit && !empty($downloads)): ?>
				<div class="table-responsive">
					<table class="table table-bordered mb-2" style="min-width:640px;">
						<thead>
							<tr>
								<th><?php echo translate('name'); ?></th>
								<th><?php echo translate('file') ?: 'File'; ?></th>
								<th style="width:90px;"><?php echo translate('type') ?: 'Type'; ?></th>
								<th style="width:80px;"><?php echo translate('delete') ?: 'Delete'; ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($downloads as $d): ?>
								<tr>
									<td><?php echo html_escape($d['name']); ?></td>
									<td><code><?php echo html_escape($d['file_path']); ?></code></td>
									<td>
										<?php if (!empty($d['is_sample'])): ?>
											<span class="badge badge-info"><?php echo translate('sample') ?: 'Sample'; ?></span>
										<?php else: ?>
											<span class="badge badge-success"><?php echo translate('main_file') ?: 'Main'; ?></span>
										<?php endif; ?>
									</td>
									<td class="text-center">
										<label style="cursor:pointer;color:#dc3545;"><input type="checkbox" name="delete_downloads[]" value="<?php echo (int) $d['id']; ?>"> <?php echo translate('delete') ?: 'Delete'; ?></label>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php elseif ($is_edit): ?>
				<p class="text-muted"><?php echo translate('no_download_files_yet') ?: 'No files uploaded yet.'; ?></p>
			<?php endif; ?>
			<div class="row">
				<div class="col-md-6 mb-sm">
					<div class="form-group">
						<label class="control-label"><?php echo translate('add_main_files') ?: 'Add main (paid) files'; ?></label>
						<input type="file" name="download_files[]" class="form-control" multiple>
						<small class="text-muted"><?php echo translate('download_upload_hint') ?: 'Delivered after successful payment (max 50MB each).'; ?></small>
					</div>
				</div>
				<div class="col-md-6 mb-sm">
					<div class="form-group">
						<label class="control-label"><?php echo translate('add_sample_files') ?: 'Add sample (free) files'; ?></label>
						<input type="file" name="sample_files[]" class="form-control" multiple>
						<small class="text-muted"><?php echo translate('sample_upload_hint') ?: 'Free preview downloadable from the product page.'; ?></small>
					</div>
				</div>
			</div>
		</div>

		<!-- ================= Grouped products ================= -->
		<div id="grouped-section" style="<?php echo $cur_type === 'grouped' ? '' : 'display:none;'; ?>">
			<hr>
			<h5 class="mb-1"><i class="fas fa-object-group"></i> <?php echo translate('grouped_products') ?: 'Grouped Products'; ?></h5>
			<p class="text-muted" style="font-size:13px;"><?php echo translate('grouped_products_hint') ?: 'A curated list of simple products. Customers pick quantities of each; every chosen item is added to the cart as its own line.'; ?></p>
			<?php
			$grouped_items  = isset($grouped_items) ? $grouped_items : [];
			$simple_products = isset($simple_products) ? $simple_products : [];
			$simple_opts = ['' => $none] + $simple_products;
			?>
			<div class="table-responsive">
				<table class="table table-bordered mb-2" id="grouped-table" style="min-width:520px;">
					<thead>
						<tr>
							<th><?php echo translate('product') ?: 'Product'; ?></th>
							<th style="width:120px;"><?php echo translate('default_qty') ?: 'Default Qty'; ?></th>
							<th style="width:50px;"></th>
						</tr>
					</thead>
					<tbody id="grouped-body">
						<?php foreach ($grouped_items as $gi): ?>
						<tr class="grouped-row">
							<td><?php echo form_dropdown('grouped_product_id[]', $simple_opts, (int) $gi['associated_product_id'], "class='form-control' data-plugin-selectTwo data-width='100%'"); ?></td>
							<td><input class="form-control" type="number" min="1" name="grouped_qty[]" value="<?php echo (int) $gi['qty']; ?>"></td>
							<td class="text-center align-middle"><button type="button" class="btn btn-danger btn-sm grouped-remove">&times;</button></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<button type="button" class="btn btn-default btn-sm" id="grouped-add"><i class="fas fa-plus"></i> <?php echo translate('add_product') ?: 'Add Product'; ?></button>
			</div>
		</div>

		<!-- ================= Bundle options ================= -->
		<?php
		$bundle_options = isset($bundle_options) ? $bundle_options : [];
		$bundle_prod_row = function ($oidx, $pid = '', $qty = 1) use ($simple_products, $none) {
			$opts = ['' => $none] + (isset($simple_products) ? $simple_products : []);
			ob_start(); ?>
			<tr class="bundle-prod-row">
				<td><?php echo form_dropdown('bundle_option_product[' . $oidx . '][]', $opts, $pid, "class='form-control'"); ?></td>
				<td><input class="form-control" type="number" min="1" name="bundle_option_pqty[<?php echo $oidx; ?>][]" value="<?php echo (int) $qty ?: 1; ?>"></td>
				<td class="text-center align-middle"><button type="button" class="btn btn-danger btn-sm bundle-prod-remove">&times;</button></td>
			</tr>
			<?php return ob_get_clean();
		};
		$bundle_card = function ($oidx, $opt) use ($bundle_prod_row) {
			$id    = (int) ($opt['id'] ?? 0);
			$label = html_escape($opt['label'] ?? '');
			$type  = $opt['type'] ?? 'select';
			$req   = !empty($opt['is_required']);
			$type_opts = ['select' => 'Dropdown (choose one)', 'radio' => 'Radio (choose one)', 'checkbox' => 'Checkbox (choose many)', 'multiselect' => 'Multi-select (choose many)'];
			ob_start(); ?>
			<div class="bundle-option-card panel panel-default mb-md" data-oidx="<?php echo $oidx; ?>">
				<div class="panel-body">
					<input type="hidden" name="bundle_option_id[<?php echo $oidx; ?>]" value="<?php echo $id; ?>">
					<div class="row">
						<div class="col-md-5 mb-sm"><label class="control-label"><?php echo translate('option_label') ?: 'Option Label'; ?> <span class="required">*</span></label><input class="form-control" name="bundle_option_label[<?php echo $oidx; ?>]" value="<?php echo $label; ?>"></div>
						<div class="col-md-3 mb-sm"><label class="control-label"><?php echo translate('type') ?: 'Type'; ?></label>
							<select class="form-control" name="bundle_option_type[<?php echo $oidx; ?>]">
								<?php foreach ($type_opts as $tv => $tl): ?><option value="<?php echo $tv; ?>" <?php echo $type === $tv ? 'selected' : ''; ?>><?php echo $tl; ?></option><?php endforeach; ?>
							</select>
						</div>
						<div class="col-md-2 mb-sm"><label class="control-label" style="display:block;"><?php echo translate('required') ?: 'Required'; ?></label><label class="switch"><input type="checkbox" name="bundle_option_required[<?php echo $oidx; ?>]" value="1" <?php echo $req ? 'checked' : ''; ?>> <?php echo translate('yes') ?: 'Yes'; ?></label></div>
						<div class="col-md-2 mb-sm text-right"><label class="control-label" style="display:block;">&nbsp;</label><button type="button" class="btn btn-danger btn-sm bundle-option-remove" title="<?php echo translate('remove') ?: 'Remove'; ?>"><i class="fas fa-trash"></i></button></div>
					</div>
					<table class="table table-bordered mt-1 mb-1" style="min-width:420px;">
						<thead><tr><th><?php echo translate('product') ?: 'Product'; ?></th><th style="width:110px;"><?php echo translate('qty') ?: 'Qty'; ?></th><th style="width:50px;"></th></tr></thead>
						<tbody class="bundle-prod-body">
							<?php foreach ((array) ($opt['products'] ?? []) as $bp) { echo $bundle_prod_row($oidx, (int) $bp['product_id'], (int) $bp['qty']); } ?>
						</tbody>
					</table>
					<button type="button" class="btn btn-default btn-sm bundle-prod-add" data-oidx="<?php echo $oidx; ?>"><i class="fas fa-plus"></i> <?php echo translate('add_product') ?: 'Add Product'; ?></button>
				</div>
			</div>
			<?php return ob_get_clean();
		};
		?>
		<div id="bundle-section" style="<?php echo $cur_type === 'bundle' ? '' : 'display:none;'; ?>">
			<hr>
			<h5 class="mb-1"><i class="fas fa-box"></i> <?php echo translate('bundle_options') ?: 'Bundle Options'; ?></h5>
			<p class="text-muted" style="font-size:13px;"><?php echo translate('bundle_options_hint') ?: 'Option groups of component products. The bundle is priced live as the sum of the chosen components; required groups must be picked.'; ?></p>
			<div id="bundle-options">
				<?php foreach ($bundle_options as $oi => $opt) { echo $bundle_card($oi, $opt); } ?>
			</div>
			<button type="button" class="btn btn-primary btn-sm" id="bundle-option-add"><i class="fas fa-plus-circle"></i> <?php echo translate('add_option_group') ?: 'Add Option Group'; ?></button>
			<template id="bundle-option-template"><?php echo $bundle_card('__OIDX__', []); ?></template>
			<template id="bundle-product-template"><?php echo $bundle_prod_row('__OIDX__'); ?></template>
		</div>

		<!-- ================= Variants ================= -->
		<hr>
		<h5 class="mb-1"><i class="fas fa-layer-group"></i> <?php echo translate('variants') ?: 'Variants'; ?></h5>
		<p class="text-muted" style="font-size:13px;">
			<?php echo translate('variants_hint') ?: 'Add variants such as size or colour. Attributes format:'; ?>
			<code>Color=Red; Size=M</code>. <?php echo translate('variants_optional_hint') ?: 'Leave empty for a simple product.'; ?>
		</p>
		<div class="table-responsive">
			<table class="table table-bordered mb-2" id="variants-table" style="min-width:820px;">
				<thead>
					<tr>
						<th style="min-width:140px;"><?php echo translate('name'); ?> <span class="required">*</span></th>
						<th><?php echo translate('sku') ?: 'SKU'; ?></th>
						<th style="min-width:160px;"><?php echo translate('attributes') ?: 'Attributes'; ?></th>
						<th><?php echo translate('price') ?: 'Price'; ?></th>
						<th><?php echo translate('special_price') ?: 'Special'; ?></th>
						<th><?php echo translate('stock') ?: 'Stock'; ?></th>
						<th><?php echo translate('status'); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody id="variants-body">
					<?php foreach ($variants as $v):
						$attr_str = '';
						if (!empty($v['attributes'])) {
							$decoded = json_decode($v['attributes'], true);
							if (is_array($decoded)) {
								$parts = [];
								foreach ($decoded as $k => $val) { $parts[] = $k . '=' . $val; }
								$attr_str = implode('; ', $parts);
							}
						}
					?>
					<tr class="variant-row">
						<td>
							<input type="hidden" name="variant_id[]" value="<?php echo (int) $v['id']; ?>">
							<input class="form-control" name="variant_name[]" value="<?php echo html_escape($v['name']); ?>">
						</td>
						<td><input class="form-control" name="variant_sku[]" value="<?php echo html_escape($v['sku']); ?>"></td>
						<td><input class="form-control" name="variant_attributes[]" value="<?php echo html_escape($attr_str); ?>" placeholder="Color=Red; Size=M"></td>
						<td><input class="form-control" type="number" step="0.01" min="0" name="variant_price[]" value="<?php echo html_escape($v['price']); ?>"></td>
						<td><input class="form-control" type="number" step="0.01" min="0" name="variant_special_price[]" value="<?php echo html_escape($v['special_price']); ?>"></td>
						<td><input class="form-control" type="number" min="0" name="variant_stock[]" value="<?php echo (int) $v['stock_quantity']; ?>"></td>
						<td>
							<select class="form-control" name="variant_status[]">
								<option value="Active" <?php echo $v['status'] === 'Active' ? 'selected' : ''; ?>><?php echo translate('active') ?: 'Active'; ?></option>
								<option value="Inactive" <?php echo $v['status'] === 'Inactive' ? 'selected' : ''; ?>><?php echo translate('inactive') ?: 'Inactive'; ?></option>
							</select>
						</td>
						<td class="text-center align-middle"><button type="button" class="btn btn-danger btn-sm variant-remove" title="Remove">&times;</button></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<button type="button" class="btn btn-default btn-sm" id="variant-add"><i class="fas fa-plus"></i> <?php echo translate('add_variant') ?: 'Add Variant'; ?></button>
		</div>
	</div>
	<footer class="panel-footer">
		<div class="row">
			<div class="col-md-offset-9 col-md-3 text-right">
				<a href="<?php echo base_url('product'); ?>" class="btn btn-default"><?php echo translate('cancel'); ?></a>
				<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo translate('save'); ?></button>
			</div>
		</div>
	</footer>
	<?php echo form_close(); ?>
</section>

<script>
(function () {
	var addBtn = document.getElementById('variant-add');
	var body   = document.getElementById('variants-body');

	// ---- product type toggle: hide physical fields + show downloadable manager ----
	var typeSel = document.getElementById('product_type');
	var physical = document.getElementById('physical-fields');
	var dlSection = document.getElementById('downloadable-section');
	var groupedSection = document.getElementById('grouped-section');
	var bundleSection = document.getElementById('bundle-section');
	function currentType() {
		return typeSel ? typeSel.value : 'simple';
	}
	function applyType() {
		var t = currentType();
		var isDigital = (t === 'virtual' || t === 'downloadable');
		if (physical) { physical.style.display = (isDigital || t === 'grouped' || t === 'bundle') ? 'none' : ''; }
		if (dlSection) { dlSection.style.display = (t === 'downloadable') ? '' : 'none'; }
		if (groupedSection) { groupedSection.style.display = (t === 'grouped') ? '' : 'none'; }
		if (bundleSection) { bundleSection.style.display = (t === 'bundle') ? '' : 'none'; }
	}
	if (typeSel) {
		// select2 fires jQuery 'change'; also bind native.
		typeSel.addEventListener('change', applyType);
		if (window.jQuery) { window.jQuery(typeSel).on('change', applyType); }
		applyType();
	}

	// ---- grouped products: repeatable product rows ----
	var gBody = document.getElementById('grouped-body');
	var gAdd  = document.getElementById('grouped-add');
	if (gBody && gAdd) {
		var gTemplate = <?php echo json_encode(
			'<tr class="grouped-row">' .
			'<td>' . form_dropdown('grouped_product_id[]', ['' => $none] + (isset($simple_products) ? $simple_products : []), '', "class='form-control'") . '</td>' .
			'<td><input class="form-control" type="number" min="1" name="grouped_qty[]" value="1"></td>' .
			'<td class="text-center align-middle"><button type="button" class="btn btn-danger btn-sm grouped-remove">&times;</button></td>' .
			'</tr>'
		); ?>;
		gAdd.addEventListener('click', function () { gBody.insertAdjacentHTML('beforeend', gTemplate); });
		gBody.addEventListener('click', function (e) {
			var btn = e.target.closest ? e.target.closest('.grouped-remove') : null;
			if (btn) { var tr = btn.closest('tr'); if (tr) { tr.parentNode.removeChild(tr); } }
		});
	}

	// ---- bundle: nested option groups + component rows ----
	var bOpts = document.getElementById('bundle-options');
	var bAdd  = document.getElementById('bundle-option-add');
	var bOptTpl = document.getElementById('bundle-option-template');
	var bProdTpl = document.getElementById('bundle-product-template');
	if (bOpts && bAdd && bOptTpl && bProdTpl) {
		var bSeq = <?php echo count($bundle_options); ?>;
		bAdd.addEventListener('click', function () {
			bOpts.insertAdjacentHTML('beforeend', bOptTpl.innerHTML.replace(/__OIDX__/g, bSeq));
			bSeq++;
		});
		bOpts.addEventListener('click', function (e) {
			var t = e.target;
			var rmOpt = t.closest ? t.closest('.bundle-option-remove') : null;
			if (rmOpt) { var c = rmOpt.closest('.bundle-option-card'); if (c) { c.parentNode.removeChild(c); } return; }
			var addProd = t.closest ? t.closest('.bundle-prod-add') : null;
			if (addProd) {
				var oidx = addProd.getAttribute('data-oidx');
				var body = addProd.closest('.bundle-option-card').querySelector('.bundle-prod-body');
				body.insertAdjacentHTML('beforeend', bProdTpl.innerHTML.replace(/__OIDX__/g, oidx));
				return;
			}
			var rmProd = t.closest ? t.closest('.bundle-prod-remove') : null;
			if (rmProd) { var row = rmProd.closest('tr'); if (row) { row.parentNode.removeChild(row); } return; }
		});
	}

	if (!body) { return; }

	function rowHtml() {
		return '<tr class="variant-row">' +
			'<td><input type="hidden" name="variant_id[]" value="0"><input class="form-control" name="variant_name[]"></td>' +
			'<td><input class="form-control" name="variant_sku[]"></td>' +
			'<td><input class="form-control" name="variant_attributes[]" placeholder="Color=Red; Size=M"></td>' +
			'<td><input class="form-control" type="number" step="0.01" min="0" name="variant_price[]"></td>' +
			'<td><input class="form-control" type="number" step="0.01" min="0" name="variant_special_price[]"></td>' +
			'<td><input class="form-control" type="number" min="0" name="variant_stock[]" value="0"></td>' +
			'<td><select class="form-control" name="variant_status[]"><option value="Active"><?php echo translate('active') ?: 'Active'; ?></option><option value="Inactive"><?php echo translate('inactive') ?: 'Inactive'; ?></option></select></td>' +
			'<td class="text-center align-middle"><button type="button" class="btn btn-danger btn-sm variant-remove" title="Remove">&times;</button></td>' +
			'</tr>';
	}

	if (addBtn) {
		addBtn.addEventListener('click', function () {
			body.insertAdjacentHTML('beforeend', rowHtml());
		});
	}

	body.addEventListener('click', function (e) {
		var btn = e.target.closest ? e.target.closest('.variant-remove') : null;
		if (btn) {
			var tr = btn.closest('tr');
			if (tr) { tr.parentNode.removeChild(tr); }
		}
	});
})();

	// ---- Incremental gallery image picker ----
	(function () {
		var input = document.getElementById('galleryInput');
		var addBtn = document.getElementById('galleryAddBtn');
		var preview = document.getElementById('galleryPreview');
		if (!input || !addBtn || !preview || typeof DataTransfer === 'undefined') { return; }

		var store = new DataTransfer(); // accumulates picks across multiple selections

		addBtn.addEventListener('click', function () { input.click(); });

		input.addEventListener('change', function () {
			Array.prototype.forEach.call(input.files, function (f) {
				if (f && f.type.indexOf('image/') === 0) { store.items.add(f); }
			});
			input.files = store.files; // keep every accumulated file on the submitted field
			render();
		});

		function render() {
			preview.innerHTML = '';
			Array.prototype.forEach.call(store.files, function (f, i) {
				var url = URL.createObjectURL(f);
				var cell = document.createElement('div');
				cell.style.cssText = 'position:relative;width:84px;height:84px;border:1px solid #e6e8eb;border-radius:8px;overflow:hidden;';
				cell.innerHTML =
					'<img src="' + url + '" style="width:100%;height:100%;object-fit:cover;">' +
					'<button type="button" data-i="' + i + '" title="Remove" ' +
					'style="position:absolute;top:2px;right:2px;border:0;background:rgba(0,0,0,.6);color:#fff;border-radius:50%;width:20px;height:20px;line-height:18px;padding:0;cursor:pointer;">&times;</button>';
				preview.appendChild(cell);
			});
			Array.prototype.forEach.call(preview.querySelectorAll('button[data-i]'), function (b) {
				b.addEventListener('click', function () {
					var idx = parseInt(b.getAttribute('data-i'), 10);
					var next = new DataTransfer();
					Array.prototype.forEach.call(store.files, function (f, j) { if (j !== idx) { next.items.add(f); } });
					store = next;
					input.files = store.files;
					render();
				});
			});
		}
	})();
</script>
