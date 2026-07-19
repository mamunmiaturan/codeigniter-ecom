<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<section class="container-fluid container-xl py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 fw-bold mb-0">File a Complaint</h1>
        <a href="<?php echo base_url('account/complaints'); ?>" class="btn btn-outline-secondary btn-sm">Back to complaints</a>
    </div>
    <div class="row g-4">
        <div class="col-lg-3">
            <?php $this->load->view('landing/account/nav', ['active' => 'complaints']); ?>
        </div>
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php echo form_open(base_url('account/submit_complaint'), ['class' => 'row g-3']); ?>
                        <div class="col-md-8">
                            <label class="form-label">Subject <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control" required maxlength="200" value="<?php echo set_value('subject'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Order number <span class="text-muted small">(optional)</span></label>
                            <input type="text" name="order_id" class="form-control" placeholder="e.g. 1024" value="<?php echo set_value('order_id'); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">What went wrong? <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="6" required placeholder="Describe the issue in detail..."><?php echo set_value('message'); ?></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-dark"><i class="bi bi-send me-1"></i> Submit Complaint</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
