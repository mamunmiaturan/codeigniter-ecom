<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="row">
    <div class="col-md-12 col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <h2 class="panel-title"><?php echo translate('landing_setting') ?: 'Landing Setting'; ?></h2>
                <p class="panel-subtitle text-muted"><?php echo translate('landing_setting_hint') ?: 'Customize the storefront look — accent / button colour, font and hero text. Changes apply live to the public site.'; ?></p>
            </header>
            <div class="panel-body">
                <form action="<?php echo base_url('landing-setting'); ?>" method="post" class="form-horizontal form-bordered">

                    <div class="form-group">
                        <label class="col-md-3 control-label"><?php echo translate('accent_button_colour') ?: 'Accent / Button Colour'; ?></label>
                        <div class="col-md-9">
                            <?php $acc = get_global_setting('landing_accent_color') ?: '#0d9488'; ?>
                            <div class="landing-accent-picker d-flex align-items-center" style="display:flex;align-items:center;gap:10px;">
                                <input type="color" id="landing_accent_color" name="landing_accent_color" value="<?php echo html_escape($acc); ?>" style="width:48px;height:38px;padding:2px;border-radius:4px;">
                                <span id="landing_accent_hex" style="font-family:monospace;"><?php echo html_escape($acc); ?></span>
                            </div>
                            <?php
                            // Preset default colours (click to pick) + the custom colour input above — like Theme Settings.
                            if (function_exists('render_theme_color_swatches')) {
                                render_theme_color_swatches('landing_accent_color');
                            }
                            ?>
                            <span class="help-block text-muted"><?php echo translate('accent_colour_hint') ?: 'Pick a preset or choose a custom colour — used for buttons, links, prices and highlights across the storefront.'; ?></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label"><?php echo translate('font') ?: 'Font'; ?></label>
                        <div class="col-md-9">
                            <?php
                            $lf = get_global_setting('landing_font') ?: 'System Default';
                            $fonts = ['System Default', 'Inter', 'Roboto', 'Poppins', 'Open Sans', 'Lato', 'Montserrat', 'Nunito'];
                            ?>
                            <select name="landing_font" class="form-control">
                                <?php foreach ($fonts as $f): ?>
                                    <option value="<?php echo html_escape($f); ?>" <?php echo $lf === $f ? 'selected' : ''; ?>><?php echo html_escape($f); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label"><?php echo translate('hero_title') ?: 'Hero Title'; ?></label>
                        <div class="col-md-9">
                            <input type="text" name="landing_hero_title" class="form-control" maxlength="255"
                                   value="<?php echo html_escape(get_global_setting('landing_hero_title')); ?>"
                                   placeholder="e.g. Fashion for every mom &amp; little one.">
                            <span class="help-block text-muted"><?php echo translate('hero_title_hint') ?: 'Shown on the homepage hero when no slider banner is active. Leave blank for the default.'; ?></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-3 control-label"><?php echo translate('hero_subtitle') ?: 'Hero Subtitle'; ?></label>
                        <div class="col-md-9">
                            <textarea name="landing_hero_subtitle" class="form-control" rows="2" maxlength="500"
                                      placeholder="A short supporting line..."><?php echo html_escape(get_global_setting('landing_hero_subtitle')); ?></textarea>
                            <span class="help-block text-muted"><?php echo translate('hero_subtitle_hint') ?: 'Optional secondary line shown under the hero title.'; ?></span>
                        </div>
                    </div>

                    <footer class="panel-footer" style="display:flex;justify-content:flex-end;align-items:center;gap:10px;">
                        <a href="<?php echo base_url('/'); ?>" target="_blank" class="btn btn-default btn-sm"><i class="fas fa-external-link-alt"></i> <?php echo translate('preview') ?: 'Preview'; ?></a>
                        <button type="submit" name="landing_setting" value="1" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> <?php echo translate('save') ?: 'Save'; ?></button>
                    </footer>
                </form>
            </div>
        </section>
    </div>
</div>

<style>
    .color-swatches { margin-top: 8px; display: flex; gap: 6px; flex-wrap: wrap; }
    .color-swatch { width: 24px; height: 24px; border-radius: 5px; cursor: pointer; border: 1px solid rgba(0,0,0,0.12); transition: all .15s; }
    .color-swatch:hover { transform: scale(1.18); box-shadow: 0 2px 5px rgba(0,0,0,0.2); border-color: rgba(0,0,0,0.3); }
</style>
<script>
    (function () {
        var picker = document.getElementById('landing_accent_color');
        var hex = document.getElementById('landing_accent_hex');
        function sync(v) { if (hex) { hex.textContent = v; } }
        if (picker) {
            picker.addEventListener('input', function () { sync(picker.value); });
        }
        // Preset swatch click -> set the accent colour input + hex label (like Theme Settings).
        Array.prototype.forEach.call(document.querySelectorAll('.color-swatch[data-target="landing_accent_color"]'), function (sw) {
            sw.addEventListener('click', function () {
                var c = sw.getAttribute('data-color');
                if (picker && c) { picker.value = c; sync(c); }
            });
        });
    })();
</script>
