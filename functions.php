<?php
/**
 * The Daily Pulse — Blocksy Child Theme Functions
 */

if (!defined('ABSPATH')) exit;

define('DAILYPULSE_VERSION', '1.0.0');
define('DAILYPULSE_DIR', get_stylesheet_directory());
define('DAILYPULSE_URI', get_stylesheet_directory_uri());

// Include modülleri
require_once DAILYPULSE_DIR . '/inc/enqueue.php';
require_once DAILYPULSE_DIR . '/inc/customizer.php';
require_once DAILYPULSE_DIR . '/inc/widgets.php';
require_once DAILYPULSE_DIR . '/inc/shortcodes.php';

/**
 * Google Fonts yükle — Cabin (body) + Barlow Condensed (logo/wordmark)
 */
function dailypulse_google_fonts() {
    $fonts_url = 'https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800;900&family=Cabin:wght@400;500;600;700&display=swap';
    wp_enqueue_style('dailypulse-google-fonts', $fonts_url, array(), null);
}
add_action('wp_enqueue_scripts', 'dailypulse_google_fonts');

/**
 * Tema desteklerini ekle
 */
function dailypulse_theme_support() {
    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
    add_theme_support('custom-logo', array(
        'height'      => 32,
        'width'       => 32,
        'flex-height' => true,
        'flex-width'  => true,
    ));

    // Menü lokasyonları
    register_nav_menus(array(
        'primary'    => __('Ana Menü', 'dailypulse'),
        'footer'     => __('Footer Menü', 'dailypulse'),
        'categories' => __('Kategori Menü', 'dailypulse'),
    ));

    // Özel görsel boyutları
    add_image_size('card-featured', 800, 600, true);
    add_image_size('card-regular', 600, 340, true);
    add_image_size('card-small', 400, 225, true);
    add_image_size('deal-thumb', 260, 180, true);
}
add_action('after_setup_theme', 'dailypulse_theme_support');

/**
 * Excerpt uzunluğu
 */
function dailypulse_excerpt_length($length) {
    return 20;
}
add_filter('excerpt_length', 'dailypulse_excerpt_length');

/**
 * Excerpt "devamını oku" metni
 */
function dailypulse_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'dailypulse_excerpt_more');

/**
 * Okuma süresi hesapla
 */
function dailypulse_reading_time($post_id = null) {
    if (!$post_id) $post_id = get_the_ID();
    $content = get_post_field('post_content', $post_id);
    $word_count = str_word_count(strip_tags($content));
    $reading_time = ceil($word_count / 200);
    return $reading_time < 1 ? 1 : $reading_time;
}

/**
 * Kategori renk sistemi
 */
function dailypulse_category_color($cat_slug) {
    $colors = array(
        'teknoloji' => array('bg' => 'rgba(139,92,246,0.2)', 'text' => '#c4b5fd', 'class' => 'tech'),
        'firsatlar' => array('bg' => 'rgba(255,214,0,0.15)', 'text' => '#ffd600', 'class' => 'deals'),
        'yasam'     => array('bg' => 'rgba(0,230,118,0.15)', 'text' => '#00e676', 'class' => 'life'),
        'finans'    => array('bg' => 'rgba(255,87,34,0.15)', 'text' => '#ff5722', 'class' => 'finance'),
        'saglik'    => array('bg' => 'rgba(0,230,118,0.15)', 'text' => '#00e676', 'class' => 'life'),
        'seyahat'   => array('bg' => 'rgba(139,92,246,0.2)', 'text' => '#c4b5fd', 'class' => 'tech'),
        'egitim'    => array('bg' => 'rgba(255,87,34,0.15)', 'text' => '#ff5722', 'class' => 'finance'),
        'trending'  => array('bg' => 'rgba(255,23,68,0.15)', 'text' => '#ff1744', 'class' => 'trending'),
    );
    return isset($colors[$cat_slug]) ? $colors[$cat_slug] : $colors['teknoloji'];
}

/* ============================================================
   LOGO — Inline SVG (price-tag mark + Barlow Condensed wordmark)
   ============================================================ */

/**
 * Always treat as having a custom logo so Blocksy uses the_custom_logo()
 */
add_filter('has_custom_logo', '__return_true');

/**
 * Replace WordPress custom logo output with our inline SVG
 */
add_filter('get_custom_logo', 'kampanya_inline_svg_logo');
function kampanya_inline_svg_logo($html) {
    $home = esc_url(home_url('/'));
    return '<a href="' . $home . '" class="custom-logo-link k-logo-link" rel="home" itemprop="url">'
         . kampanya_logo_svg()
         . '</a>';
}

/**
 * The logo SVG — horizontal lockup, light variant
 * viewBox: 1060 x 220 (tag mark 200 wide, wordmark 790 wide, padding)
 */
function kampanya_logo_svg() {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1060 220"
        class="k-logo-svg" role="img" aria-label="kampanya.website">'
        . '<title>kampanya.website</title>'
        /* Price-tag mark: chevron-right pentagon */
        . '<path d="M 14 30 L 132 30 L 186 100 L 132 170 L 14 170 Z" fill="#FFD600" stroke="#111111" stroke-width="3"/>'
        /* Punch hole */
        . '<circle cx="150" cy="100" r="9" fill="#111111"/>'
        /* K cut into tag */
        . '<text x="26" y="145" font-family="\'Barlow Condensed\',sans-serif" font-weight="900" font-size="140" fill="#111111" letter-spacing="-2">k</text>'
        /* Vertical hairline divider */
        . '<line x1="222" y1="28" x2="222" y2="210" stroke="#111111" stroke-width="1"/>'
        /* "kampanya" wordmark */
        . '<text x="248" y="162" font-size="180" font-family="\'Barlow Condensed\',sans-serif" font-weight="900" fill="#111111" letter-spacing="-2" textLength="790" lengthAdjust="spacingAndGlyphs">kampanya</text>'
        /* Underline rule */
        . '<rect x="248" y="178" width="790" height="2.5" fill="#111111"/>'
        /* ".WEBSITE" subtitle */
        . '<text x="248" y="208" font-size="25" font-family="\'Barlow Condensed\',sans-serif" font-weight="700" fill="#111111" letter-spacing="7">.WEBSITE</text>'
        . '</svg>';
}

/* ============================================================
   FAVİCON — SVG (yellow tag mark with K)
   ============================================================ */

add_action('wp_head', 'kampanya_favicon_links', 1);
function kampanya_favicon_links() {
    $uri = DAILYPULSE_URI;
    echo '<link rel="icon" type="image/svg+xml" href="' . $uri . '/assets/images/favicon.svg">' . "\n";
}

/**
 * Override Blocksy copyright text
 */
function kampanya_override_copyright($text) {
    if (is_string($text)) {
        $text = str_replace('The Daily Pulse Team', 'Kampanya.website', $text);
        $text = str_replace('The Daily Pulse', 'Kampanya.website', $text);
        $text = str_replace('thedailypulse.com', 'kampanya.website', $text);
    }
    return $text;
}
add_filter('blocksy:footer:copyright:text', 'kampanya_override_copyright');
add_filter('the_content', 'kampanya_override_copyright');

/**
 * Replace copyright in entire footer output
 */
function kampanya_footer_copyright_buffer_start() {
    ob_start();
}
function kampanya_footer_copyright_buffer_end() {
    $html = ob_get_clean();
    $html = str_replace('The Daily Pulse Team', 'Kampanya.website', $html);
    $html = str_replace('The Daily Pulse', 'Kampanya.website', $html);
    $html = str_replace('thedailypulse.com', 'kampanya.website', $html);
    echo $html;
}
add_action('wp_footer', 'kampanya_footer_copyright_buffer_start', 1);
add_action('wp_footer', 'kampanya_footer_copyright_buffer_end', 999);

/* ============================================================
   KAMPANYA REST API — Cache Purge
   ============================================================ */

add_action('rest_api_init', function () {
    register_rest_route('kampanya/v1', '/purge-cache', [
        'methods'             => 'POST',
        'callback'            => 'kampanya_purge_cache',
        'permission_callback' => function (WP_REST_Request $r) {
            return current_user_can('manage_options');
        },
    ]);
});

function kampanya_purge_cache() {
    $purged = [];

    // LiteSpeed Cache
    if (class_exists('\LiteSpeed\Purge')) {
        \LiteSpeed\Purge::purge_all();
        $purged[] = 'litespeed';
    } elseif (function_exists('litespeed_purge_all')) {
        litespeed_purge_all();
        $purged[] = 'litespeed_fn';
    }

    // W3 Total Cache (fallback)
    if (function_exists('w3tc_pgcache_flush')) {
        w3tc_pgcache_flush();
        $purged[] = 'w3tc';
    }

    // WP Super Cache (fallback)
    if (function_exists('wp_cache_clear_cache')) {
        wp_cache_clear_cache();
        $purged[] = 'wpsc';
    }

    return rest_ensure_response([
        'success' => true,
        'purged'  => $purged,
        'message' => empty($purged) ? 'No cache plugin found' : 'Cache purged: ' . implode(', ', $purged),
    ]);
}

/* ============================================================
   KAMPANYA REST API — Abone ol / Abonelikten çık
   ============================================================ */

add_action('rest_api_init', function () {

    // Abone ol
    register_rest_route('kampanya/v1', '/subscribe', [
        'methods'             => 'POST',
        'callback'            => 'kampanya_rest_subscribe',
        'permission_callback' => '__return_true',
    ]);

    // Abonelikten çık
    register_rest_route('kampanya/v1', '/unsubscribe', [
        'methods'             => 'POST',
        'callback'            => 'kampanya_rest_unsubscribe',
        'permission_callback' => '__return_true',
    ]);
});

function kampanya_rest_subscribe(WP_REST_Request $request) {
    $email = sanitize_email(trim($request->get_param('email')));

    if (!is_email($email)) {
        return new WP_Error('invalid_email', 'Lütfen geçerli bir e-posta adresi girin.', ['status' => 400]);
    }

    if (!function_exists('FluentCrmApi')) {
        return new WP_Error('plugin_unavailable', 'Abonelik servisi şu an kullanılamıyor.', ['status' => 503]);
    }

    $contact_api = FluentCrmApi('contacts');

    $data = [
        'email'  => $email,
        'status' => 'subscribed',
        'lists'  => [3], // Genel Aboneler listesi
    ];

    $result = $contact_api->createOrUpdate($data);

    if (is_wp_error($result)) {
        return new WP_Error('subscribe_failed', 'Kayıt sırasında bir hata oluştu, lütfen tekrar deneyin.', ['status' => 500]);
    }

    return rest_ensure_response([
        'success' => true,
        'message' => 'Abone oldunuz! En güncel fırsatlar yakında e-postanızda. 🎉',
    ]);
}

function kampanya_rest_unsubscribe(WP_REST_Request $request) {
    $email = sanitize_email(trim($request->get_param('email')));

    if (!is_email($email)) {
        return new WP_Error('invalid_email', 'Lütfen geçerli bir e-posta adresi girin.', ['status' => 400]);
    }

    if (!function_exists('FluentCrmApi')) {
        return new WP_Error('plugin_unavailable', 'Servis şu an kullanılamıyor.', ['status' => 503]);
    }

    $contact_api = FluentCrmApi('contacts');
    $contact     = $contact_api->getContact($email);

    if (!$contact) {
        // Sessizce başarı dön — kullanıcıya "zaten abone değilsin" demek yerine
        return rest_ensure_response([
            'success' => true,
            'message' => 'Aboneliğiniz iptal edildi.',
        ]);
    }

    $contact->status = 'unsubscribed';
    $contact->save();

    return rest_ensure_response([
        'success' => true,
        'message' => 'Aboneliğiniz başarıyla iptal edildi. Artık e-posta almayacaksınız.',
    ]);
}
