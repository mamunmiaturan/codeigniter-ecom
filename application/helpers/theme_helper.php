<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('get_default_theme_settings')) {
    function get_default_theme_settings(): array
    {
        return [
            'primary_color'       => '#007bff',
            'secondary_color'     => '#6c757d',
            'sidebar_color'       => '#ffffff',
            'sidebar_text_color'  => '#6c757d',
            'navbar_color'        => '#ffffff',
            'navbar_text_color'   => '#6c757d',
            'dark_mode'           => 0,
            'dark_skin'           => 'false',
            'font_family'         => 'System Default',
            'font_size'           => 'medium',
        ];
    }
}

if (!function_exists('render_theme_color_swatches')) {
    function render_theme_color_swatches(string $target): void
    {
        $colors = [
            '#007bff' => 'primary',
            '#6c757d' => 'secondary',
            '#28a745' => 'success',
            '#17a2b8' => 'info',
            '#ffc107' => 'warning',
            '#dc3545' => 'danger',
            '#343a40' => 'dark',
            '#ffffff' => 'white',
        ];
        echo '<div class="color-swatches">';
        foreach ($colors as $hex => $name) {
            echo '<div class="color-swatch" data-color="' . html_escape($hex) . '" data-target="' . html_escape($target) . '" style="background:' . html_escape($hex) . ';" title="' . html_escape(ucfirst($name)) . '"></div>';
        }
        echo '</div>';
    }
}

if (!function_exists('render_swatches')) {
    /** @deprecated Use render_theme_color_swatches() */
    function render_swatches(string $target): void
    {
        render_theme_color_swatches($target);
    }
}

if (!function_exists('save_user_theme_from_request')) {
    /**
     * Save current user's theme from POST (profile/settings forms).
     *
     * @return array{ok:bool, error?:string}
     */
    function save_user_theme_from_request(int $user_id): array
    {
        $ci = &get_instance();

        $allowed_theme_fields = [
            'primary_color', 'secondary_color', 'sidebar_color',
            'sidebar_text_color', 'navbar_color', 'navbar_text_color',
            'dark_mode', 'font_family', 'font_size',
        ];
        $color_fields = [
            'primary_color', 'secondary_color', 'sidebar_color',
            'sidebar_text_color', 'navbar_color', 'navbar_text_color',
        ];

        $config = [];
        foreach ($allowed_theme_fields as $input) {
            $value = $ci->input->post($input);
            if ($value === null) {
                continue;
            }
            if (in_array($input, $color_fields, true)) {
                if (!preg_match('/^#[0-9a-fA-F]{3,6}$/', $value)) {
                    return ['ok' => false, 'error' => translate('invalid_color_value') . ': ' . $input];
                }
            } elseif ($input === 'font_family') {
                $value = sanitizeString($value);
            } elseif ($input === 'font_size') {
                $allowed_sizes = ['extra_small', 'small', 'medium', 'large', 'extra_large'];
                if (in_array($value, $allowed_sizes, true)) {
                    // preset keyword — keep as-is
                } elseif (preg_match('/^\d{1,2}(\.\d+)?px$/', $value)) {
                    // already a custom px value
                } elseif (preg_match('/^\d{1,2}(\.\d+)?$/', $value)) {
                    // custom numeric -> clamp 8..30 and store as px
                    $value = max(8, min(30, (float) $value)) . 'px';
                } else {
                    $value = 'medium';
                }
            } elseif ($input === 'dark_mode') {
                $value = (int) $value;
            }
            $config[$input] = $value;
        }

        if (isset($config['dark_mode'])) {
            $config['dark_skin'] = ($config['dark_mode'] == 1 ? 'true' : 'false');
        }

        if (empty($config)) {
            return ['ok' => false, 'error' => translate('no_data_found')];
        }

        save_user_theme($user_id, $config);

        return ['ok' => true];
    }
}

if (!function_exists('reset_user_theme_to_default')) {
    function reset_user_theme_to_default(int $user_id): void
    {
        save_user_theme($user_id, get_default_theme_settings());
    }
}

if (!function_exists('can_use_full_theme_customization')) {
    /** Admin & Superman: full color / font customization. */
    function can_use_full_theme_customization(): bool
    {
        return in_array((int) loggedin_role_id(), [ROLE_SUPERMAN_ID, ROLE_ADMIN_ID], true);
    }
}

if (!function_exists('get_theme_presets')) {
    /**
     * Pre-built themes for branch staff, kitchen, rider, customer, etc.
     *
     * @return array<string, array{slug:string,label_key:string,settings:array}>
     */
    function get_theme_presets(): array
    {
        $presets = [
            'default_light' => [
                'slug'       => 'default_light',
                'label_key'  => 'theme_preset_default_light',
                'settings'   => [
                    'primary_color'      => '#007bff',
                    'secondary_color'    => '#6c757d',
                    'sidebar_color'      => '#ffffff',
                    'sidebar_text_color' => '#6c757d',
                    'navbar_color'       => '#ffffff',
                    'navbar_text_color'  => '#374151',
                    'dark_mode'          => 0,
                    'dark_skin'          => 'false',
                    'font_family'        => 'System Default',
                ],
            ],
            'ocean' => [
                'slug'       => 'ocean',
                'label_key'  => 'theme_preset_ocean',
                'settings'   => [
                    'primary_color'      => '#0891b2',
                    'secondary_color'    => '#64748b',
                    'sidebar_color'      => '#f0fdfa',
                    'sidebar_text_color' => '#0f766e',
                    'navbar_color'       => '#ffffff',
                    'navbar_text_color'  => '#134e4a',
                    'dark_mode'          => 0,
                    'dark_skin'          => 'false',
                    'font_family'        => 'System Default',
                ],
            ],
            'emerald' => [
                'slug'       => 'emerald',
                'label_key'  => 'theme_preset_emerald',
                'settings'   => [
                    'primary_color'      => '#059669',
                    'secondary_color'    => '#6b7280',
                    'sidebar_color'      => '#ecfdf5',
                    'sidebar_text_color' => '#047857',
                    'navbar_color'       => '#ffffff',
                    'navbar_text_color'  => '#14532d',
                    'dark_mode'          => 0,
                    'dark_skin'          => 'false',
                    'font_family'        => 'System Default',
                ],
            ],
            'sunset' => [
                'slug'       => 'sunset',
                'label_key'  => 'theme_preset_sunset',
                'settings'   => [
                    'primary_color'      => '#ea580c',
                    'secondary_color'    => '#78716c',
                    'sidebar_color'      => '#fff7ed',
                    'sidebar_text_color' => '#c2410c',
                    'navbar_color'       => '#ffffff',
                    'navbar_text_color'  => '#7c2d12',
                    'dark_mode'          => 0,
                    'dark_skin'          => 'false',
                    'font_family'        => 'System Default',
                ],
            ],
            'royal' => [
                'slug'       => 'royal',
                'label_key'  => 'theme_preset_royal',
                'settings'   => [
                    'primary_color'      => '#7c3aed',
                    'secondary_color'    => '#6b7280',
                    'sidebar_color'      => '#f5f3ff',
                    'sidebar_text_color' => '#5b21b6',
                    'navbar_color'       => '#ffffff',
                    'navbar_text_color'  => '#4c1d95',
                    'dark_mode'          => 0,
                    'dark_skin'          => 'false',
                    'font_family'        => 'System Default',
                ],
            ],
            'slate' => [
                'slug'       => 'slate',
                'label_key'  => 'theme_preset_slate',
                'settings'   => [
                    'primary_color'      => '#475569',
                    'secondary_color'    => '#94a3b8',
                    'sidebar_color'      => '#f8fafc',
                    'sidebar_text_color' => '#334155',
                    'navbar_color'       => '#ffffff',
                    'navbar_text_color'  => '#1e293b',
                    'dark_mode'          => 0,
                    'dark_skin'          => 'false',
                    'font_family'        => 'System Default',
                ],
            ],
            'midnight' => [
                'slug'       => 'midnight',
                'label_key'  => 'theme_preset_midnight',
                'settings'   => [
                    'primary_color'      => '#6366f1',
                    'secondary_color'    => '#94a3b8',
                    'sidebar_color'      => '#1e293b',
                    'sidebar_text_color' => '#e2e8f0',
                    'navbar_color'       => '#0f172a',
                    'navbar_text_color'  => '#f1f5f9',
                    'dark_mode'          => 1,
                    'dark_skin'          => 'true',
                    'font_family'        => 'System Default',
                ],
            ],
            'dark_emerald' => [
                'slug'       => 'dark_emerald',
                'label_key'  => 'theme_preset_dark_emerald',
                'settings'   => [
                    'primary_color'      => '#10b981',
                    'secondary_color'    => '#6b7280',
                    'sidebar_color'      => '#14532d',
                    'sidebar_text_color' => '#d1fae5',
                    'navbar_color'       => '#052e16',
                    'navbar_text_color'  => '#ecfdf5',
                    'dark_mode'          => 1,
                    'dark_skin'          => 'true',
                    'font_family'        => 'System Default',
                ],
            ],
            'rose' => [
                'slug'       => 'rose',
                'label_key'  => 'theme_preset_rose',
                'settings'   => [
                    'primary_color'      => '#e11d48',
                    'secondary_color'    => '#9ca3af',
                    'sidebar_color'      => '#fff1f2',
                    'sidebar_text_color' => '#be123c',
                    'navbar_color'       => '#ffffff',
                    'navbar_text_color'  => '#881337',
                    'dark_mode'          => 0,
                    'dark_skin'          => 'false',
                    'font_family'        => 'System Default',
                ],
            ],
            'corporate' => [
                'slug'       => 'corporate',
                'label_key'  => 'theme_preset_corporate',
                'settings'   => [
                    'primary_color'      => '#1e40af',
                    'secondary_color'    => '#64748b',
                    'sidebar_color'      => '#eff6ff',
                    'sidebar_text_color' => '#1e3a8a',
                    'navbar_color'       => '#ffffff',
                    'navbar_text_color'  => '#172554',
                    'dark_mode'          => 0,
                    'dark_skin'          => 'false',
                    'font_family'        => 'System Default',
                ],
            ],
        ];

        return $presets;
    }
}

if (!function_exists('get_theme_preset_by_slug')) {
    function get_theme_preset_by_slug(string $slug): ?array
    {
        $presets = get_theme_presets();
        return $presets[$slug] ?? null;
    }
}

if (!function_exists('apply_theme_preset')) {
    function apply_theme_preset(int $user_id, string $slug): bool
    {
        $preset = get_theme_preset_by_slug($slug);
        if (!$preset) {
            return false;
        }
        save_user_theme($user_id, $preset['settings']);
        return true;
    }
}

if (!function_exists('detect_active_theme_preset_slug')) {
    function detect_active_theme_preset_slug(?array $theme_config = null): ?string
    {
        $theme_config = $theme_config ?? get_theme_setting_row();
        if (empty($theme_config)) {
            return null;
        }
        $keys = ['primary_color', 'secondary_color', 'sidebar_color', 'sidebar_text_color', 'navbar_color', 'navbar_text_color', 'dark_mode', 'dark_skin'];
        foreach (get_theme_presets() as $slug => $preset) {
            $match = true;
            foreach ($keys as $key) {
                $a = (string) ($theme_config[$key] ?? '');
                $b = (string) ($preset['settings'][$key] ?? '');
                if ($key === 'dark_mode') {
                    $a = (int) $a;
                    $b = (int) $b;
                }
                if ($a !== $b) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                return $slug;
            }
        }
        return null;
    }
}

if (!function_exists('theme_page_url')) {
    function theme_page_url(): string
    {
        return base_url('profile/' . route_hash('theme'));
    }
}
