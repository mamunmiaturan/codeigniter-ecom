<section class="panel">
    <?php echo form_open(base_url('inventory_source/transfer_save')); ?>
    <header class="panel-heading">
        <h2 class="panel-title"><i class="fas fa-exchange-alt"></i> <?php echo translate('stock_transfer') ?: 'Stock Transfer'; ?></h2>
    </header>
    <div class="panel-body">
        <p class="text-muted">Move on-hand stock of a base product from one warehouse to another. The transfer is recorded in the stock movement ledger.</p>
        <div class="row">
            <div class="col-md-6 mb-sm">
                <div class="form-group">
                    <label class="control-label">Product <span class="required">*</span></label>
                    <?php echo form_dropdown('product_id', ['' => 'Select product'] + $products, set_value('product_id'), "class='form-control' data-plugin-selectTwo data-width='100%'"); ?>
                </div>
            </div>
            <div class="col-md-3 mb-sm">
                <div class="form-group">
                    <label class="control-label">Quantity <span class="required">*</span></label>
                    <input type="number" name="qty" min="1" class="form-control" value="<?php echo set_value('qty'); ?>">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-sm">
                <div class="form-group">
                    <label class="control-label">From warehouse <span class="required">*</span></label>
                    <?php echo form_dropdown('from_source', ['' => 'Select warehouse'] + $sources, set_value('from_source'), "class='form-control' data-plugin-selectTwo data-width='100%'"); ?>
                </div>
            </div>
            <div class="col-md-4 mb-sm">
                <div class="form-group">
                    <label class="control-label">To warehouse <span class="required">*</span></label>
                    <?php echo form_dropdown('to_source', ['' => 'Select warehouse'] + $sources, set_value('to_source'), "class='form-control' data-plugin-selectTwo data-width='100%'"); ?>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-8 mb-sm">
                <div class="form-group">
                    <label class="control-label">Note</label>
                    <input type="text" name="note" class="form-control" value="<?php echo set_value('note'); ?>" placeholder="Optional reason / reference">
                </div>
            </div>
        </div>
    </div>
    <footer class="panel-footer text-right">
        <a href="<?php echo base_url('inventory_source/movements'); ?>" class="btn btn-default"><?php echo translate('cancel'); ?></a>
        <button type="submit" class="btn btn-success"><i class="fas fa-exchange-alt"></i> <?php echo translate('transfer') ?: 'Transfer'; ?></button>
    </footer>
    <?php echo form_close(); ?>
</section>
