<?php defined('BASEPATH') or exit('No direct script access allowed');
$c_phone = (string) get_global_setting('mobile_no');
$c_email = (string) get_global_setting('site_email');
$c_addr  = (string) get_global_setting('address');
$c_wa    = preg_replace('/[^0-9]/', '', $c_phone);
$c_soc = array_filter([
    'facebook'  => (string) get_global_setting('facebook_url'),
    'instagram' => (string) get_global_setting('instagram_url'),
    'twitter-x' => (string) get_global_setting('twitter_url'),
    'youtube'   => (string) get_global_setting('youtube_url'),
]);
?>
<section class="container-fluid container-xl py-5">
    <nav class="small mb-3">
        <a href="<?php echo base_url('/'); ?>" class="text-muted text-decoration-none">Home</a>
        <span class="text-muted mx-1">/</span><span>Contact Us</span>
    </nav>
    <h1 class="h3 fw-bold mb-1">Contact Us</h1>
    <p class="text-muted mb-4">Have a question about an order or a product? Send us a message and our team will get back to you.</p>

    <div class="row g-4 align-items-stretch">

        <!-- Contact info panel -->
        <div class="col-lg-4">
            <div class="ls-contact-info h-100">
                <h2 class="h5 fw-bold mb-1">Get in touch</h2>
                <p class="ls-ci-lead">We&rsquo;re here to help every day. Reach us through any of the channels below.</p>
                <ul class="ls-ci-list">
                    <?php if ($c_phone !== ''): ?>
                        <li><span class="ls-ci-ic"><i class="bi bi-telephone"></i></span><a href="tel:<?php echo html_escape(preg_replace('/\s+/', '', $c_phone)); ?>"><?php echo html_escape($c_phone); ?></a></li>
                    <?php endif; ?>
                    <?php if ($c_email !== ''): ?>
                        <li><span class="ls-ci-ic"><i class="bi bi-envelope"></i></span><a href="mailto:<?php echo html_escape($c_email); ?>"><?php echo html_escape($c_email); ?></a></li>
                    <?php endif; ?>
                    <?php if ($c_wa !== ''): ?>
                        <li><span class="ls-ci-ic"><i class="bi bi-whatsapp"></i></span><a href="https://wa.me/<?php echo html_escape($c_wa); ?>" target="_blank" rel="noopener">Chat on WhatsApp</a></li>
                    <?php endif; ?>
                    <?php if ($c_addr !== ''): ?>
                        <li><span class="ls-ci-ic"><i class="bi bi-geo-alt"></i></span><span><?php echo html_escape($c_addr); ?></span></li>
                    <?php endif; ?>
                </ul>
                <?php if ($c_soc): ?>
                    <div class="ls-ci-social">
                        <?php foreach ($c_soc as $ic => $url): ?>
                            <a href="<?php echo html_escape($url); ?>" target="_blank" rel="noopener" aria-label="<?php echo html_escape($ic); ?>"><i class="bi bi-<?php echo html_escape($ic); ?>"></i></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Message form -->
        <div class="col-lg-8">
            <div class="ls-contact-form h-100">
                <h2 class="h5 fw-bold mb-3">Send us a message</h2>
                <?php echo form_open(base_url('contact-us/submit'), ['class' => 'row g-3']); ?>
                    <div class="col-md-6">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" maxlength="150" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" maxlength="150" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" maxlength="30">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" maxlength="200">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea name="message" class="form-control" rows="5" required></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn ls-contact-btn"><i class="bi bi-send me-1"></i> Send Message</button>
                    </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</section>

<style>
    .ls-contact-info {
        background: linear-gradient(150deg, #0d9488, #0f766e 60%, #115e59);
        color: #fff; border-radius: 18px; padding: 2rem;
    }
    .ls-contact-info h2 { color: #fff; }
    .ls-ci-lead { opacity: .9; font-size: 14px; margin-bottom: 1.5rem; }
    .ls-ci-list { list-style: none; padding: 0; margin: 0 0 1.5rem; }
    .ls-ci-list li { display: flex; align-items: center; gap: 12px; margin-bottom: 1rem; line-height: 1.4; }
    .ls-ci-ic { width: 40px; height: 40px; flex: 0 0 auto; border-radius: 50%; background: rgba(255,255,255,.15); display: inline-flex; align-items: center; justify-content: center; font-size: 1.05rem; }
    .ls-ci-list a, .ls-ci-list span { color: #fff; text-decoration: none; word-break: break-word; }
    .ls-ci-list a:hover { text-decoration: underline; }
    .ls-ci-social { display: flex; gap: 10px; }
    .ls-ci-social a { width: 38px; height: 38px; border-radius: 50%; background: rgba(255,255,255,.15); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; text-decoration: none; transition: .16s; }
    .ls-ci-social a:hover { background: #fff; color: #0f766e; }

    .ls-contact-form { background: #fff; border: 1px solid #eceef1; border-radius: 18px; padding: 2rem; }
    .ls-contact-form .form-control:focus { border-color: var(--accent-color, #0d9488); box-shadow: 0 0 0 .2rem color-mix(in srgb, var(--accent-color, #0d9488), transparent 82%); }
    .ls-contact-btn { background: var(--accent-color, #0d9488); color: #fff; font-weight: 600; padding: .55rem 1.5rem; }
    .ls-contact-btn:hover { filter: brightness(1.08); color: #fff; }
</style>
