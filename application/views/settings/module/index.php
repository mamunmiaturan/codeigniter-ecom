<section class="panel">
    <?php
    $create_active = isset($validation_error);
    $page_tabs = [
        ['id' => 'list', 'icon' => 'fas fa-list-ul', 'label' => translate('module_list'), 'active' => !$create_active],
    ];
    if (get_permission('modules', 'is_add')) {
        $page_tabs[] = ['id' => 'create', 'icon' => 'far fa-edit', 'label' => translate('create_module'), 'active' => $create_active];
    }
    $this->load->view('layout/_page_tabs_start', ['page_tabs' => $page_tabs]);
    ?>
    <!-- MODULE LIST -->
    <div class="tab-pane <?= !$create_active ? 'active' : ''; ?>" id="list">
                <div class="mb-md">
                    <table class="table table-bordered table-hover table-condensed table-default" id="table-default">
                        <thead>
                            <tr>
                                <th width="50"><?= translate('sl'); ?></th>
                                <th><?= translate('module_details'); ?></th>
                                <th class="text-center"><?= translate('permissions'); ?></th>
                                <th class="text-center"><?= translate('action'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($modules)):
                                $count = 1;
                                foreach ($modules as $row): ?>
                                    <tr>
                                        <td><?= $count++; ?></td>
                                        <td>
                                            <div class="module-info">
                                                <span class="module-name"><?= html_escape($row['module_name']); ?></span>
                                                <span class="permission-name"><?= html_escape($row['permission_name']); ?></span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            if ($row['show_view'])
                                                echo '<span class="permission-badge badge-view" data-toggle="tooltip" title="' . translate('view') . '"><i class="fas fa-eye"></i></span>';
                                            if ($row['show_add'])
                                                echo '<span class="permission-badge badge-add" data-toggle="tooltip" title="' . translate('add') . '"><i class="fas fa-plus"></i></span>';
                                            if ($row['show_edit'])
                                                echo '<span class="permission-badge badge-edit" data-toggle="tooltip" title="' . translate('edit') . '"><i class="fas fa-edit"></i></span>';
                                            if ($row['show_delete'])
                                                echo '<span class="permission-badge badge-delete" data-toggle="tooltip" title="' . translate('delete') . '"><i class="fas fa-trash-alt"></i></span>';

                                            if (!$row['show_view'] && !$row['show_add'] && !$row['show_edit'] && !$row['show_delete']) {
                                                echo '<span class="label label-default">' . translate('no_permissions') . '</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if (get_permission('modules', 'is_edit')): ?>
                                                <a href="<?= base_url('module/' . route_hash('edit') . '/' . encrypt_id($row['module_id']) . '/' . encrypt_id($row['permission_id'])); ?>"
                                                    class="btn btn-circle icon btn-default" data-toggle="tooltip"
                                                    title="<?= translate('edit'); ?>">
                                                    <i class="fas fa-pen-nib"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (get_permission('modules', 'is_delete')): ?>
                                                <button class="btn btn-circle icon btn-danger" onclick="confirm_modal('<?= base_url('module/' . route_hash('delete') . '/' . encrypt_id($row['module_id']) . '/' . encrypt_id($row['permission_id'])); ?>')" data-toggle="tooltip" title="<?= translate('delete'); ?>">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                            <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (get_permission('modules', 'is_add')): ?>
            <!-- CREATE MODULE -->
            <div class="tab-pane <?= isset($validation_error) ? 'active' : ''; ?>" id="create">
                <?php echo form_open($this->uri->uri_string(), ['class' => 'form-horizontal']); ?>

                <div class="form-group <?= form_error('module_type') ? 'has-error' : ''; ?>">
                    <label class="col-md-2 control-label"><?= translate('module_type'); ?> <span class="required">*</span></label>
                    <div class="col-md-10">
                        <select name="module_type" id="moduleType" class="form-control" data-plugin-selectTwo data-width="100%" required>
                            <option value="" disabled selected><?= translate('select_module_type') ?></option>
                            <option value="existing" <?= set_select('module_type', 'existing'); ?>>Use Existing Module</option>
                            <option value="new" <?= set_select('module_type', 'new'); ?>>Create New Module</option>
                        </select>
                        <span class="error"><?= form_error('module_type'); ?></span>
                    </div>
                </div>

                <div id="moduleFormFields" style="display:none;">
                    <!-- EXISTING MODULE -->
                    <div class="form-group <?= form_error('existing_module_id') ? 'has-error' : ''; ?>" id="existingModule" style="display:none;">
                        <label class="col-md-2 control-label"><?= translate('existing_module'); ?> <span class="required">*</span></label>
                        <div class="col-md-10">
                            <select name="existing_module_id" id="existingModuleSelect" class="form-control" data-plugin-selectTwo data-width="100%">
                                <option value="" disabled selected><?= translate('select_module_name') ?></option>
                                <?php foreach ($module_names as $module): ?>
                                    <option value="<?= $module['id']; ?>" <?= set_select('existing_module_id', $module['id']); ?>><?= html_escape($module['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="error"><?= form_error('existing_module_id'); ?></span>
                        </div>
                    </div>

                    <!-- NEW MODULE -->
                    <div class="form-group <?= form_error('name') ? 'has-error' : ''; ?>" id="newModule" style="display:none;">
                        <label class="col-md-2 control-label"><?= translate('module_name'); ?> <span class="required">*</span></label>
                        <div class="col-md-10">
                            <input type="text" name="name" class="form-control" value="<?= set_value('name'); ?>">
                            <span class="error"><?= form_error('name'); ?></span>
                            <div class="mt-md">
                                <label class="control-label" style="display:block; text-align:left; font-weight:bold; margin-bottom: 5px;">
                                    <?= translate('is_file_create'); ?>
                                </label>
                                <div class="radio-custom radio-inline radio-primary mr-md">
                                    <input type="radio" name="create_files" value="1" id="create_files_yes">
                                    <label for="create_files_yes"><?= translate('yes'); ?></label>
                                </div>
                                <div class="radio-custom radio-inline radio-primary">
                                    <input type="radio" name="create_files" value="0" id="create_files_no" checked>
                                    <label for="create_files_no"><?= translate('no'); ?></label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- COMMON PERMISSION NAME -->
                    <div class="form-group <?= form_error('permission_name') ? 'has-error' : ''; ?>">
                        <label class="col-md-2 control-label"><?= translate('permission_name'); ?> <span class="required">*</span></label>
                        <div class="col-md-10">
                            <input type="text" name="permission_name" class="form-control" value="<?= set_value('permission_name'); ?>">
                            <span class="error"><?= form_error('permission_name'); ?></span>
                        </div>
                    </div>

                    <!-- PERMISSIONS -->
                    <div class="form-group">
                        <label class="col-md-2 control-label"><?= translate('permissions'); ?></label>
                        <div class="col-md-10">
                            <div class="checkbox-custom checkbox-default">
                                <input type="checkbox" id="selectAllPermissions">
                                <label for="selectAllPermissions"><strong><?= translate('select_all'); ?></strong></label>
                            </div>
                            <div class="row mt-sm">
                                <div class="col-xs-6 col-sm-3">
                                    <div class="checkbox-custom checkbox-default">
                                        <input type="checkbox" name="show_view" value="1" class="cb_permission" id="p_view">
                                        <label for="p_view"><?= translate('view'); ?></label>
                                    </div>
                                </div>
                                <div class="col-xs-6 col-sm-3">
                                    <div class="checkbox-custom checkbox-default">
                                        <input type="checkbox" name="show_add" value="1" class="cb_permission" id="p_add">
                                        <label for="p_add"><?= translate('add'); ?></label>
                                    </div>
                                </div>
                                <div class="col-xs-6 col-sm-3">
                                    <div class="checkbox-custom checkbox-default">
                                        <input type="checkbox" name="show_edit" value="1" class="cb_permission" id="p_edit">
                                        <label for="p_edit"><?= translate('edit'); ?></label>
                                    </div>
                                </div>
                                <div class="col-xs-6 col-sm-3">
                                    <div class="checkbox-custom checkbox-default">
                                        <input type="checkbox" name="show_delete" value="1" class="cb_permission" id="p_delete">
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
                            <button type="submit" name="save" value="1" class="btn btn-default btn-block">
                                <i class="fas fa-plus-circle"></i> <?= translate('save'); ?>
                            </button>
                        </div>
                    </div>
                </footer>

                <?php echo form_close(); ?>
            </div>
            <?php endif; ?>
    </div>
    <?php $this->load->view('layout/_page_tabs_end'); ?>
</section>

<script src="<?php echo base_url('assets/backend/js/app/module-form.js'); ?>"></script>