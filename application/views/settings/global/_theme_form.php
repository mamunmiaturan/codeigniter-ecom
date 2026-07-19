<?php
$this->load->helper('theme');
$theme_config = $theme_config ?? [];
$form_action = $form_action ?? current_url();
$show_reset = $show_reset ?? true;
$submit_name = $submit_name ?? 'save_theme';
$submit_value = $submit_value ?? '1';
$font_preview = translate('font_preview_sample');
?>
<?php echo form_open($form_action, ['class' => 'form-horizontal form-bordered validate theme-preferences-form']); ?>

<div class="headers-line">
    <i class="fas fa-adjust"></i> <?= translate('base_theme'); ?>
    <p class="text-muted" style="font-weight:normal;font-size:12px;margin-top:5px;"><?= translate('base_theme_hint'); ?></p>
</div>

<div class="form-group">
    <label class="col-md-2 control-label"><?= translate('theme_mode'); ?></label>
    <div class="col-md-10">
        <div class="radio-custom radio-primary mb-sm">
            <input name="dark_mode" id="theme_light" value="0" type="radio" <?= (isset($theme_config['dark_mode']) && (int) $theme_config['dark_mode'] === 0 ? 'checked' : ''); ?>>
            <label for="theme_light">
                <strong><?= translate('light_mode'); ?></strong> — <span class="text-muted"><?= translate('light_mode_desc'); ?></span>
            </label>
        </div>
        <div class="radio-custom radio-primary">
            <input name="dark_mode" id="theme_dark" value="1" type="radio" <?= (isset($theme_config['dark_mode']) && (int) $theme_config['dark_mode'] === 1 ? 'checked' : ''); ?>>
            <label for="theme_dark">
                <strong><?= translate('dark_mode'); ?></strong> — <span class="text-muted"><?= translate('dark_mode_desc'); ?></span>
            </label>
        </div>
    </div>
</div>

<hr class="dotted short">

<div class="headers-line">
    <i class="fas fa-palette"></i> <?= translate('color_palette'); ?>
    <p class="text-muted" style="font-weight:normal;font-size:12px;margin-top:5px;"><?= translate('color_palette_hint'); ?></p>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label class="col-md-4 control-label"><?= translate('primary_color'); ?></label>
            <div class="col-md-8">
                <input type="color" class="form-control" name="primary_color" value="<?= html_escape($theme_config['primary_color'] ?? '#007bff'); ?>">
                <?php render_theme_color_swatches('primary_color'); ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="col-md-4 control-label"><?= translate('secondary_color'); ?></label>
            <div class="col-md-8">
                <input type="color" class="form-control" name="secondary_color" value="<?= html_escape($theme_config['secondary_color'] ?? '#6c757d'); ?>">
                <?php render_theme_color_swatches('secondary_color'); ?>
            </div>
        </div>
    </div>
</div>

<hr class="dotted short">

<div class="headers-line">
    <i class="fas fa-columns"></i> <?= translate('sidebar'); ?>
    <p class="text-muted" style="font-weight:normal;font-size:12px;margin-top:5px;"><?= translate('sidebar_theme_hint'); ?></p>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label class="col-md-4 control-label"><?= translate('background_color'); ?></label>
            <div class="col-md-8">
                <input type="color" class="form-control" name="sidebar_color" value="<?= html_escape($theme_config['sidebar_color'] ?? '#ffffff'); ?>">
                <?php render_theme_color_swatches('sidebar_color'); ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="col-md-4 control-label"><?= translate('text_color'); ?></label>
            <div class="col-md-8">
                <input type="color" class="form-control" name="sidebar_text_color" value="<?= html_escape($theme_config['sidebar_text_color'] ?? '#6c757d'); ?>">
                <?php render_theme_color_swatches('sidebar_text_color'); ?>
            </div>
        </div>
    </div>
</div>

<hr class="dotted short">

<div class="headers-line">
    <i class="fas fa-window-maximize"></i> <?= translate('navbar'); ?>
    <p class="text-muted" style="font-weight:normal;font-size:12px;margin-top:5px;"><?= translate('navbar_theme_hint'); ?></p>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label class="col-md-4 control-label"><?= translate('background_color'); ?></label>
            <div class="col-md-8">
                <input type="color" class="form-control" name="navbar_color" value="<?= html_escape($theme_config['navbar_color'] ?? '#ffffff'); ?>">
                <?php render_theme_color_swatches('navbar_color'); ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="col-md-4 control-label"><?= translate('text_color'); ?></label>
            <div class="col-md-8">
                <input type="color" class="form-control" name="navbar_text_color" value="<?= html_escape($theme_config['navbar_text_color'] ?? '#333333'); ?>">
                <?php render_theme_color_swatches('navbar_text_color'); ?>
            </div>
        </div>
    </div>
</div>

<hr class="dotted short">

<div class="headers-line">
    <i class="fas fa-font"></i> <?= translate('typography'); ?>
    <p class="text-muted" style="font-weight:normal;font-size:12px;margin-top:5px;"><?= translate('typography_hint'); ?></p>
</div>

<div class="form-group">
    <label class="col-md-2 control-label"><?= translate('font_family'); ?></label>
    <div class="col-md-10">
        <div class="radio-custom radio-primary mb-xs">
            <input type="radio" name="font_family" id="font_inter" value="Inter" <?= (($theme_config['font_family'] ?? '') === 'Inter' ? 'checked' : ''); ?>>
            <label for="font_inter" style="font-family:'Inter',sans-serif;">Inter — <span class="text-muted"><?= $font_preview; ?></span></label>
        </div>
        <div class="radio-custom radio-primary mb-xs">
            <input type="radio" name="font_family" id="font_roboto" value="Roboto" <?= (($theme_config['font_family'] ?? '') === 'Roboto' ? 'checked' : ''); ?>>
            <label for="font_roboto" style="font-family:'Roboto',sans-serif;">Roboto — <span class="text-muted"><?= $font_preview; ?></span></label>
        </div>
        <div class="radio-custom radio-primary mb-xs">
            <input type="radio" name="font_family" id="font_poppins" value="Poppins" <?= (($theme_config['font_family'] ?? '') === 'Poppins' ? 'checked' : ''); ?>>
            <label for="font_poppins" style="font-family:'Poppins',sans-serif;">Poppins — <span class="text-muted"><?= $font_preview; ?></span></label>
        </div>
        <div class="radio-custom radio-primary mb-xs">
            <input type="radio" name="font_family" id="font_system" value="System Default" <?= (empty($theme_config['font_family']) || ($theme_config['font_family'] ?? '') === 'System Default' ? 'checked' : ''); ?>>
            <label for="font_system"><?= translate('system_default'); ?> — <span class="text-muted"><?= $font_preview; ?></span></label>
        </div>
    </div>
</div>

<div class="form-group">
    <label class="col-md-2 control-label"><?= translate('font_size') ?: 'Font Size'; ?></label>
    <div class="col-md-10">
        <?php
        $fs = (string) ($theme_config['font_size'] ?? 'medium');
        $presets = [
            'extra_small' => 'Extra Small',
            'small'       => 'Small',
            'medium'      => 'Medium (Default)',
            'large'       => 'Large',
            'extra_large' => 'Extra Large',
        ];
        $is_custom  = !isset($presets[$fs]);
        $custom_num = $is_custom ? rtrim($fs, 'px') : '';
        ?>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <select id="fontSizeSelect" class="form-control" style="max-width:200px;">
                <?php foreach ($presets as $val => $lbl): ?>
                    <option value="<?= $val; ?>" <?= (!$is_custom && $fs === $val) ? 'selected' : ''; ?>><?= $lbl; ?></option>
                <?php endforeach; ?>
                <option value="custom" <?= $is_custom ? 'selected' : ''; ?>>Custom (px)…</option>
            </select>
            <input type="number" id="fontSizeCustom" min="8" max="30" step="0.5" placeholder="px"
                   class="form-control" style="max-width:120px; <?= $is_custom ? '' : 'display:none;'; ?>"
                   value="<?= html_escape($custom_num); ?>">
            <input type="hidden" name="font_size" id="fontSizeHidden" value="<?= html_escape($fs); ?>">
        </div>
        <span class="help-block text-muted"><?= translate('font_size_hint') ?: 'Choose a preset or set a custom size in px — applies across the panel.'; ?></span>
        <script>
        (function () {
            var sel = document.getElementById('fontSizeSelect'),
                cust = document.getElementById('fontSizeCustom'),
                hid = document.getElementById('fontSizeHidden');
            if (!sel || !cust || !hid) { return; }
            function sync() {
                if (sel.value === 'custom') { cust.style.display = ''; hid.value = (cust.value || '14') + 'px'; }
                else { cust.style.display = 'none'; hid.value = sel.value; }
            }
            sel.addEventListener('change', sync);
            cust.addEventListener('input', function () { if (sel.value === 'custom') { hid.value = (cust.value || '14') + 'px'; } });
        })();
        </script>
    </div>
</div>

<footer class="panel-footer mt-md">
    <div class="row">
        <div class="col-md-12 text-right">
            <?php if ($show_reset): ?>
                <button type="button" class="btn btn-default" id="resetThemeBtn">
                    <i class="fas fa-redo"></i> <?= translate('reset'); ?>
                </button>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary" name="<?= html_escape($submit_name); ?>" value="<?= html_escape($submit_value); ?>">
                <i class="fas fa-save"></i> <?= translate('save'); ?>
            </button>
        </div>
    </div>
</footer>

<?php echo form_close(); ?>

<?php if (!defined('THEME_FORM_ASSETS_LOADED')): define('THEME_FORM_ASSETS_LOADED', true); ?>
<style>
    .color-swatches { margin-top: 8px; display: flex; gap: 6px; flex-wrap: wrap; }
    .color-swatch { width: 22px; height: 22px; border-radius: 4px; cursor: pointer; border: 1px solid rgba(0,0,0,0.1); transition: all 0.2s; }
    .color-swatch:hover { transform: scale(1.2); box-shadow: 0 2px 4px rgba(0,0,0,0.2); border-color: rgba(0,0,0,0.3); }
</style>
<script>
$(function() {
    $('.theme-preferences-form .color-swatch').on('click', function() {
        var color = $(this).data('color');
        var target = $(this).data('target');
        $('.theme-preferences-form input[name="' + target + '"]').val(color);
    });
    $('#resetThemeBtn').on('click', function() {
        var form = $(this).closest('form');
        swal({
            title: <?= json_encode(translate('are_you_sure')); ?>,
            text: <?= json_encode(translate('theme_reset_confirm')); ?>,
            type: 'warning',
            showCancelButton: true,
            confirmButtonClass: 'btn btn-danger swal2-btn-danger',
            cancelButtonClass: 'btn btn-default swal2-btn-default',
            confirmButtonText: <?= json_encode(translate('yes')); ?>,
            cancelButtonText: <?= json_encode(translate('cancel')); ?>,
            buttonsStyling: false
        }).then(function(result) {
            if (result && (result.value || result.isConfirmed)) {
                $('<input>').attr({ type: 'hidden', name: 'reset_theme', value: '1' }).appendTo(form);
                form.submit();
            }
        });
    });
});
</script>
<?php endif; ?>
