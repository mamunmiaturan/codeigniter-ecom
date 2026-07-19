<section class="panel">
    <header class="panel-heading">
        <div class="panel-actions">
            <a href="<?php echo base_url('product'); ?>" class="btn btn-default btn-sm"><i class="fas fa-arrow-left"></i> <?php echo translate('back') ?: 'Back to products'; ?></a>
        </div>
        <h2 class="panel-title"><i class="fas fa-file-import"></i> <?php echo translate('import_products') ?: 'Import Products (CSV)'; ?></h2>
    </header>
    <div class="panel-body">
        <p class="text-muted">
            Upload a CSV with the columns:
            <code>Name, SKU, Category, Brand, Price, Special Price, Stock, Status</code>.
            <strong>Name</strong> is required. Category and Brand are matched by name (left empty if not found).
            Rows are imported as <em>simple</em> products.
        </p>
        <a href="<?php echo base_url('product/import_sample'); ?>" class="btn btn-link btn-sm px-0"><i class="fas fa-download"></i> <?php echo translate('download_sample_csv') ?: 'Download sample CSV'; ?></a>
        <?php echo form_open_multipart(base_url('product/import_csv')); ?>
            <div class="form-group mt-2" style="max-width:420px;">
                <label class="control-label"><?php echo translate('csv_file') ?: 'CSV file'; ?> <span class="required">*</span></label>
                <input type="file" name="csv" accept=".csv,text/csv" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-success"><i class="fas fa-upload"></i> <?php echo translate('import') ?: 'Import'; ?></button>
        <?php echo form_close(); ?>
    </div>
</section>
