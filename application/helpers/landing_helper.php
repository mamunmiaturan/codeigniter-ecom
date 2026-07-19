<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package  : E-Commerce / Storefront
 * @filename : landing_helper.php
 */

if (!function_exists('shop_money')) {
    function shop_money($v)
    {
        $sym = get_global_setting('currency_symbol') ?: '৳';
        return $sym . ' ' . number_format((float) $v, 2);
    }
}

if (!function_exists('shop_order_status_badge')) {
    /**
     * Coloured Bootstrap badge for an order status.
     */
    function shop_order_status_badge($status)
    {
        $map = [
            'pending'    => 'secondary',
            'confirmed'  => 'info',
            'processing' => 'info',
            'packed'     => 'primary',
            'shipped'    => 'primary',
            'delivered'  => 'success',
            'completed'  => 'success',
            'cancelled'  => 'danger',
            'returned'   => 'warning',
            'refunded'   => 'warning',
        ];
        $cls = $map[strtolower((string) $status)] ?? 'secondary';
        return '<span class="badge bg-' . $cls . '">' . html_escape(ucfirst(str_replace('_', ' ', (string) $status))) . '</span>';
    }
}

if (!function_exists('shop_label_badge')) {
    /**
     * Coloured Bootstrap badge for a product marketing label (New, Hot, Sale,
     * Best Seller, Limited…). Returns '' for an empty label. $extra_class is
     * appended to the badge classes (e.g. positioning utilities on a card).
     */
    function shop_label_badge($label, $extra_class = '')
    {
        $label = trim((string) $label);
        if ($label === '') {
            return '';
        }
        $map = [
            'new'         => 'success',
            'hot'         => 'danger',
            'sale'        => 'warning',
            'best seller' => 'primary',
            'bestseller'  => 'primary',
            'limited'     => 'dark',
        ];
        $cls   = $map[strtolower($label)] ?? 'secondary';
        $extra = trim((string) $extra_class);
        return '<span class="badge bg-' . $cls . ($extra !== '' ? ' ' . html_escape($extra) : '') . '">'
            . html_escape($label) . '</span>';
    }
}

if (!function_exists('shop_video_embed')) {
    /**
     * Safe, responsive embed for a product video URL.
     *   - YouTube (watch / youtu.be / shorts / embed) and Vimeo watch URLs are
     *     converted to their embed iframe.
     *   - Direct .mp4 / .webm / .ogg links render a <video> tag.
     *   - Any other valid URL falls back to a plain "Watch video" link.
     * Returns '' when the URL is empty or not a valid URL. All output is escaped.
     */
    function shop_video_embed($url)
    {
        $url = trim((string) $url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        // ---- YouTube ----
        if (strpos($host, 'youtube.com') !== false || strpos($host, 'youtu.be') !== false) {
            $id = '';
            if (strpos($host, 'youtu.be') !== false) {
                $id = trim((string) parse_url($url, PHP_URL_PATH), '/');
            } else {
                parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
                $id = $q['v'] ?? '';
                if ($id === '' && preg_match('#/(?:embed|shorts)/([A-Za-z0-9_-]+)#', (string) parse_url($url, PHP_URL_PATH), $m)) {
                    $id = $m[1];
                }
            }
            $id = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $id);
            if ($id === '') {
                return '';
            }
            $src = 'https://www.youtube.com/embed/' . $id;
            return '<div class="ratio ratio-16x9 rounded-3 overflow-hidden">'
                . '<iframe src="' . html_escape($src) . '" title="Product video" loading="lazy" '
                . 'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
        }

        // ---- Vimeo ----
        if (strpos($host, 'vimeo.com') !== false) {
            if (preg_match('#/(\d+)#', (string) parse_url($url, PHP_URL_PATH), $m)) {
                $src = 'https://player.vimeo.com/video/' . $m[1];
                return '<div class="ratio ratio-16x9 rounded-3 overflow-hidden">'
                    . '<iframe src="' . html_escape($src) . '" title="Product video" loading="lazy" '
                    . 'allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe></div>';
            }
            return '';
        }

        // ---- Direct video file ----
        if (preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $url)) {
            return '<video class="w-100 rounded-3" controls preload="metadata">'
                . '<source src="' . html_escape($url) . '">'
                . 'Your browser does not support embedded video.</video>';
        }

        // ---- Fallback: a safe link ----
        return '<a href="' . html_escape($url) . '" target="_blank" rel="noopener noreferrer" class="btn btn-outline-dark btn-sm">'
            . '<i class="bi bi-play-circle me-1"></i>Watch video</a>';
    }
}

if (!function_exists('shop_stars')) {
    /**
     * Render a 0–5 star rating as filled/empty Bootstrap-Icons stars.
     * Supports half stars (rounded to nearest 0.5).
     */
    function shop_stars($rating)
    {
        $rating = max(0, min(5, (float) $rating));
        $half   = round($rating * 2) / 2;
        $out    = '<span class="ls-stars" style="color:#f5a623;white-space:nowrap;">';
        for ($i = 1; $i <= 5; $i++) {
            if ($half >= $i) {
                $out .= '<i class="bi bi-star-fill"></i>';
            } elseif ($half >= $i - 0.5) {
                $out .= '<i class="bi bi-star-half"></i>';
            } else {
                $out .= '<i class="bi bi-star text-muted"></i>';
            }
        }
        return $out . '</span>';
    }
}
