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
 * Google Fonts yükle — Newsreader (body) + Barlow (UI) + Barlow Condensed (display)
 */
function dailypulse_google_fonts() {
    $fonts_url = 'https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800;900&family=Barlow:wght@500;600;700&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;0,6..72,700;1,6..72,400&display=swap';
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
   LOGO — Inline SVG injected via wp_get_attachment_image filter
   Blocksy renders logos via wp_get_attachment_image(), so we
   intercept that call for the known logo attachment IDs and
   return our inline SVG (which can use fonts loaded on the page).
   ============================================================ */

/** Logo attachment IDs: 3060 = old placeholder, 3332 = new kampanya-logo.svg */
define('KAMPANYA_LOGO_IDS', [3060, 3332]);

add_filter('wp_get_attachment_image', 'kampanya_logo_inline', 10, 5);
function kampanya_logo_inline($html, $attachment_id, $size, $icon, $attr) {
    if (in_array((int) $attachment_id, KAMPANYA_LOGO_IDS)) {
        return kampanya_logo_svg();
    }
    return $html;
}

/**
 * The logo SVG — horizontal lockup.
 * viewBox 1060 × 220: tag mark 200 px, hairline, wordmark 790 px.
 * Barlow Condensed is loaded on the page via Google Fonts so
 * inline SVG can use it correctly.
 */
function kampanya_logo_svg() {
    // No <title> element — avoids browser native tooltip on hover
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1060 220"'
        . ' class="k-logo-svg" role="img" aria-label="kampanya.website">'
        . '<path d="M 14 30 L 132 30 L 186 100 L 132 170 L 14 170 Z" fill="#FFD600" stroke="#111111" stroke-width="3"/>'
        . '<circle cx="150" cy="100" r="9" fill="#111111"/>'
        . '<text x="26" y="145" font-family="\'Barlow Condensed\',sans-serif" font-weight="900" font-size="140" fill="#111111" letter-spacing="-2">k</text>'
        . '<line x1="222" y1="28" x2="222" y2="210" stroke="#111111" stroke-width="1"/>'
        . '<text x="248" y="162" font-size="180" font-family="\'Barlow Condensed\',sans-serif" font-weight="900" fill="#111111" letter-spacing="-2" textLength="790" lengthAdjust="spacingAndGlyphs">kampanya</text>'
        . '<rect x="248" y="178" width="790" height="2.5" fill="#111111"/>'
        . '<text x="248" y="208" font-size="25" font-family="\'Barlow Condensed\',sans-serif" font-weight="700" fill="#111111" letter-spacing="7">.WEBSITE</text>'
        . '</svg>';
}

/**
 * Dark variant — white wordmark + yellow tag mark for use on black footer
 */
function kampanya_logo_svg_dark() {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1060 220"'
        . ' class="k-logo-svg k-logo-svg--dark" role="img" aria-label="kampanya.website">'
        . '<path d="M 14 30 L 132 30 L 186 100 L 132 170 L 14 170 Z" fill="#FFD600" stroke="#FFD600" stroke-width="1"/>'
        . '<circle cx="150" cy="100" r="9" fill="#111111"/>'
        . '<text x="26" y="145" font-family="\'Barlow Condensed\',sans-serif" font-weight="900" font-size="140" fill="#111111" letter-spacing="-2">k</text>'
        . '<line x1="222" y1="28" x2="222" y2="210" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>'
        . '<text x="248" y="162" font-size="180" font-family="\'Barlow Condensed\',sans-serif" font-weight="900" fill="#FFFFFF" letter-spacing="-2" textLength="790" lengthAdjust="spacingAndGlyphs">kampanya</text>'
        . '<rect x="248" y="178" width="790" height="2.5" fill="#FFD600"/>'
        . '<text x="248" y="208" font-size="25" font-family="\'Barlow Condensed\',sans-serif" font-weight="700" fill="rgba(255,255,255,0.6)" letter-spacing="7">.WEBSITE</text>'
        . '</svg>';
}

/* ============================================================
   FOOTER LOGO — inject dark logo SVG into footer bottom bar
   ============================================================ */

add_action('wp_footer', 'kampanya_footer_logo_inject', 21);
function kampanya_footer_logo_inject() {
    $svg = kampanya_logo_svg_dark();
    ?>
    <script>
    (function(){
        function inject() {
            var bar = document.querySelector('.ct-footer [data-row*="bottom"] > div');
            if (!bar || document.querySelector('.k-footer-logo-wrap')) return;
            var link = document.createElement('a');
            link.className = 'k-footer-logo-wrap';
            link.href = '/';
            link.setAttribute('aria-label', 'Kampanya.website');
            link.innerHTML = <?php echo json_encode($svg); ?>;
            bar.insertBefore(link, bar.firstChild);
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', inject);
        } else {
            inject();
        }
    })();
    </script>
    <?php
}

/* ============================================================
   REST: kampanya/v1/set-logo — update custom_logo theme mod
   ============================================================ */
add_action('rest_api_init', function () {
    register_rest_route('kampanya/v1', '/set-logo', [
        'methods'             => 'POST',
        'callback'            => function (WP_REST_Request $r) {
            $id = intval($r->get_param('id'));
            if ($id <= 0) {
                return new WP_Error('invalid_id', 'Valid attachment ID required.', ['status' => 400]);
            }
            set_theme_mod('custom_logo', $id);
            return rest_ensure_response([
                'success'  => true,
                'logo_id'  => get_theme_mod('custom_logo'),
            ]);
        },
        'permission_callback' => function () { return current_user_can('manage_options'); },
    ]);
});

/* ============================================================
   LİTESPEED GUEST VARY — disable reload for private browsers
   The guest.vary.php reload misfires when iOS Private Relay is
   active, causing Safari to navigate to the raw server IP.
   Setting the vary cookie server-side prevents the JS from firing.
   ============================================================ */

add_action('wp_head', function() {
    if (empty($_COOKIE['_lscache_vary'])) {
        // Set the vary cookie via JS before LiteSpeed's inline script checks for it
        echo '<script>if(!document.cookie.match(/_lscache_vary/)){document.cookie="_lscache_vary=dguest; path=/; max-age=172800; secure; samesite=lax";}</script>' . "\n";
    }
}, 1);

/* ============================================================
   PREVENT LITESPEED REDIRECT LOOPS
   Override LiteSpeed's guest.vary.php behavior that causes
   redirects to raw server IPs on private networks
   ============================================================ */

if (!defined('LSCACHE_VARY_ACCEPT_QS') ) {
    define('LSCACHE_VARY_ACCEPT_QS', 1);
}

// Prevent LiteSpeed from triggering reload checks
add_filter('litespeed_is_mobile', '__return_false', 99);
add_filter('litespeed_is_tablet', '__return_false', 99);

/* ============================================================
   BLOCKSY — Disable single post hero/cover entirely.
   Strategy:
   1. PHP output buffering (server-side strip — primary, cache-safe)
   2. PHP filters (attempt)
   3. JS DOM removal with data-no-optimize (prevents LiteSpeed deferral)
   Live DOM confirmed: <main class="site-main"> > <div class="hero-section" data-type="type-2">
   The hero-section wraps both the featured image AND the entry-header.
   ============================================================ */

/**
 * Layer 1: Server-side strip via output buffering.
 * Runs before LiteSpeed caches the page — no CSS/JS dependency.
 * Matches: <div class="hero-section" ...> ... </header></div>
 */
add_action('template_redirect', function() {
    if (!is_singular('post')) return;
    ob_start('kampanya_strip_hero_section');
}, 1);

/**
 * Strip the Blocksy hero-section div from single post HTML.
 * Uses div-depth counting (not regex) — immune to PCRE limits on large pages.
 */
function kampanya_strip_hero_section($html) {
    $marker = '<div class="hero-section"';
    $start  = strpos($html, $marker);
    if ($start === false) return $html;

    $depth = 0;
    $pos   = $start;
    $len   = strlen($html);

    while ($pos < $len) {
        $next = strpos($html, '<', $pos);
        if ($next === false) break;

        if (substr($html, $next, 5) === '<div ') {
            $depth++;
            $pos = $next + 5;
        } elseif (substr($html, $next, 4) === '<div' && in_array($html[$next + 4], [' ', '>'])) {
            $depth++;
            $pos = $next + 4;
        } elseif (substr($html, $next, 6) === '</div>') {
            if ($depth === 0) {
                // We haven't entered the hero div yet — skip
                $pos = $next + 6;
            } else {
                $depth--;
                if ($depth === 0) {
                    // This </div> closes the hero-section
                    $end = $next + 6;
                    return substr($html, 0, $start) . substr($html, $end);
                }
                $pos = $next + 6;
            }
        } else {
            $pos = $next + 1;
        }
    }
    return $html;
}

// PHP filter attempts (Blocksy filter names vary by version)
add_filter('blocksy:hero:is-enabled',           '__return_false', 99);
add_filter('blocksy:header:hero:is-enabled',    '__return_false', 99);
add_filter('theme_mod_single_has_hero_section', function() { return 'no'; }, 99);
add_filter('theme_mod_page_has_hero_section',   function() { return 'no'; }, 99);
add_filter('theme_mod_post_has_hero_section',   function() { return 'no'; }, 99);

/**
 * Layer 3: JS DOM removal — data-no-optimize prevents LiteSpeed from
 * converting this to type="litespeed/javascript" (deferred).
 */
add_action('wp_head', function() {
    if (!is_singular('post') && !is_page()) return;
    echo '<script data-no-optimize="1">';
    echo '(function(){';
    echo 'function removeHero(){';
    echo 'var h=document.querySelectorAll("main.site-main>.hero-section,main.site-main>div[data-type],#main>.hero-section");';
    echo 'h.forEach(function(el){if(!el.closest(".entry-content"))el.remove();});';
    echo '}';
    echo 'removeHero();';
    echo 'document.addEventListener("DOMContentLoaded",removeHero);';
    echo '})();';
    echo '</script>' . "\n";
}, 1);

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
