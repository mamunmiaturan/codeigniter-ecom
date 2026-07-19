<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<section class="container-fluid container-xl py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-sm-9 col-md-6 col-lg-5">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4 p-md-5">

          <div class="text-center mb-4">
            <i class="bi bi-person-circle" style="font-size: 2.2rem; color: var(--accent-color, #4f46e5);"></i>
            <h1 class="h4 fw-bold mt-2 mb-1">Welcome back</h1>
            <p class="text-muted small mb-0">Log in to your customer account</p>
          </div>

          <form action="<?php echo base_url('account/login'); ?>" method="post" novalidate>
            <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">

            <div class="mb-3">
              <label class="form-label">Email</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="you@example.com" required autofocus>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label">Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control" placeholder="Your password" required>
              </div>
            </div>

            <button type="submit" class="btn btn-dark w-100 py-2 fw-semibold">Log in</button>
          </form>

          <p class="text-center text-muted small mt-4 mb-0">
            New customer?
            <a href="<?php echo base_url('account/register'); ?>" class="fw-semibold text-decoration-none">Create an account</a>
          </p>

          <hr class="my-3">
          <p class="text-center small mb-0">
            <a href="<?php echo base_url('login'); ?>" class="text-muted text-decoration-none"><i class="bi bi-shield-lock me-1"></i>Admin / Staff login</a>
          </p>

        </div>
      </div>
    </div>
  </div>
</section>
