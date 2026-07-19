<div class="row">
    <div class="col-md-12">
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title">
                    <i class="far fa-envelope"></i> <?php echo translate('send_sms'); ?>
                </h4>
            </header>
            <?php echo form_open($this->uri->uri_string(), array('class' => 'form-horizontal form-bordered validate')); ?>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-md-2 control-label"><?php echo translate('select_users'); ?> <span class="required">*</span></label>
                    <div class="col-md-10">
                        <select class="form-control" name="user_ids[]" multiple required data-plugin-selectTwo data-width="100%">
                            <?php foreach ($user_list as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo html_escape($user['name']); ?>
                                    (<?php echo html_escape($user['mobile_no'] ?: 'No Mobile'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-md-2 control-label"><?php echo translate('message'); ?> <span class="required">*</span></label>
                    <div class="col-md-10">
                        <textarea class="form-control" name="message" rows="4" required></textarea>
                    </div>
                </div>
            </div>
            <?php if (get_permission('send_sms', 'is_add')): ?>
            <footer class="panel-footer">
                <div class="row">
                    <div class="col-md-offset-10 col-md-2">
                        <button type="submit" class="btn btn-default btn-block">
                            <i class="fas fa-paper-plane"></i> <?php echo translate('send'); ?>
                        </button>
                    </div>
                </div>
            </footer>
            <?php endif; ?>
            <?php echo form_close(); ?>
        </section>
    </div>
</div>