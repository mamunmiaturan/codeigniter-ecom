<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<section class="container-fluid container-xl py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-sm-10 col-md-7 col-lg-6">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4 p-md-5">

          <div class="text-center mb-4">
            <i class="bi bi-person-plus" style="font-size: 2.2rem; color: var(--accent-color, #4f46e5);"></i>
            <h1 class="h4 fw-bold mt-2 mb-1">Create your account</h1>
            <p class="text-muted small mb-0">Register to check out faster and track your orders</p>
          </div>

          <form action="<?php echo base_url('account/register'); ?>" method="post" novalidate>
            <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">

            <div class="mb-3">
              <label class="form-label">Full name</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="name" class="form-control" placeholder="Your name" required autofocus>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-6 mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Phone <span class="text-muted fw-normal">(optional)</span></label>
                <input type="text" name="phone" class="form-control" placeholder="01XXXXXXXXX">
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-6 mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Create a password" required>
              </div>
              <div class="col-md-6 mb-4">
                <label class="form-label">Confirm password</label>
                <input type="password" name="password_confirm" class="form-control" placeholder="Repeat password" required>
              </div>
            </div>

            <button type="submit" class="btn btn-dark w-100 py-2 fw-semibold">Create account</button>
          </form>

          <p class="text-center text-muted small mt-4 mb-0">
            Already have an account?
            <a href="<?php echo base_url('account/login'); ?>" class="fw-semibold text-decoration-none">Log in</a>
          </p>

        </div>
      </div>
    </div>
  </div>
</section>
