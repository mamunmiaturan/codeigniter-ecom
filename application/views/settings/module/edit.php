<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-edit"></i> <?php echo translate('edit_module'); ?></h4>
            </header>
            <?php echo form_open($this->uri->uri_string(), ['class' => 'form-horizontal']); ?>
            <div class="panel-body">
                <!-- Warning -->
                <div class="alert alert-warning mb-md">
                    <strong><?php echo translate('warning'); ?>:</strong>
                    <?php echo translate('module_edit_warning'); ?>
                </div>

                <!-- Module Name -->
                <div class="form-group">
                    <label class="col-md-2 control-label"><?php echo translate('module_name'); ?> <span class="required">*</span></label>
                    <div class="col-md-10">
                        <input type="text" class="form-control" name="module_name" value="<?php echo set_value('module_name', html_escape($module['module_name'])); ?>">
                        <span class="error"><?php echo form_error('module_name'); ?></span>
                    </div>
                </div>

                <!-- Module Prefix -->
                <div class="form-group">
                    <label class="col-md-2 control-label"><?php echo translate('module_prefix'); ?> <span class="required">*</span></label>
                    <div class="col-md-10">
                        <input type="text" class="form-control" name="module_prefix" value="<?php echo set_value('module_prefix', html_escape($module['module_prefix'])); ?>">
                        <span class="error"><?php echo form_error('module_prefix'); ?></span>
                    </div>
                </div>

                <!-- Permission Name -->
                <div class="form-group">
                    <label class="col-md-2 control-label"><?php echo translate('permission_name'); ?> <span class="required">*</span></label>
                    <div class="col-md-10">
                        <input type="text" class="form-control" name="permission_name" value="<?php echo set_value('permission_name', html_escape($module['permission_name'])); ?>">
                        <span class="error"><?php echo form_error('permission_name'); ?></span>
                    </div>
                </div>

                <!-- Permission Prefix -->
                <div class="form-group">
                    <label class="col-md-2 control-label"><?php echo translate('permission_prefix'); ?> <span class="required">*</span></label>
                    <div class="col-md-10">
                        <input type="text" class="form-control" name="permission_prefix" value="<?php echo set_value('permission_prefix', html_escape($module['permission_prefix'])); ?>">
                        <span class="error"><?php echo form_error('permission_prefix'); ?></span>
                    </div>
                </div>

                <!-- Permissions Checkboxes -->
                <div class="form-group">
                    <label class="col-md-2 control-label"><?php echo translate('permissions'); ?> <span class="required">*</span></label>
                    <div class="col-md-10">
                        <div class="checkbox-custom checkbox-default">
                            <input type="checkbox" id="select_all">
                            <label for="select_all"><strong><?php echo translate('select_all'); ?></strong></label>
                        </div>
                        <div class="row mt-sm">
                            <div class="col-xs-6 col-sm-3">
                                <div class="checkbox-custom checkbox-default">
                                    <input type="checkbox" name="show_view" value="1" class="cb_permission" id="p_view" <?php echo ($module['show_view']) ? 'checked' : ''; ?>>
                                    <label for="p_view"><?= translate('view'); ?></label>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-3">
                                <div class="checkbox-custom checkbox-default">
                                    <input type="checkbox" name="show_add" value="1" class="cb_permission" id="p_add" <?php echo ($module['show_add']) ? 'checked' : ''; ?>>
                                    <label for="p_add"><?= translate('add'); ?></label>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-3">
                                <div class="checkbox-custom checkbox-default">
                                    <input type="checkbox" name="show_edit" value="1" class="cb_permission" id="p_edit" <?php echo ($module['show_edit']) ? 'checked' : ''; ?>>
                                    <label for="p_edit"><?= translate('edit'); ?></label>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-3">
                                <div class="checkbox-custom checkbox-default">
                                    <input type="checkbox" name="show_delete" value="1" class="cb_permission" id="p_delete" <?php echo ($module['show_delete']) ? 'checked' : ''; ?>>
                                    <label for="p_delete"><?= translate('delete'); ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="panel-footer">
                <div class="row">
                    <div class="col-md-offset-10 col-md-2">
                        <button type="submit" class="btn btn-default btn-block" name="update" value="1">
                            <i class="fas fa-edit"></i> <?php echo translate('update'); ?>
                        </button>
                    </div>
                </div>
            </footer>
            <?php echo form_close(); ?>
        </section>
    </div>
</div>

<script src="<?php echo base_url('assets/backend/js/app/module-form.js'); ?>"></script>