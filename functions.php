<?php
/**
 * Kampanya.website — Blocksy Child Theme Functions
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
require_once DAILYPULSE_DIR . '/inc/seo.php';
require_once DAILYPULSE_DIR . '/inc/content-extras.php';

/**
 * Google Fonts yükle — Barlow (UI) + Barlow Condensed (display)
 *
 * Body fontu (Quicksand) Google CDN'den değil, temanın kendi
 * assets/fonts/quicksand/ klasöründen self-hosted olarak yükleniyor
 * (bkz. custom.css içindeki @font-face tanımları).
 */
function dailypulse_google_fonts() {
    $fonts_url = 'https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800;900&family=Barlow:wght@500;600;700&display=swap';
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
   FIX FAKE US ADDRESS — Replace DailyPulse demo placeholder
   with real İstanbul contact information
   ============================================================ */

/* ============================================================
   SSL CERTIFICATE MONITORING — Detect & Auto-Fix Certificate Issues
   Runs daily to ensure kampanya.website cert stays valid.
   Alerts if renewal fails or cert mismatch detected.
   ============================================================ */

add_action('init', function() {
    // Schedule daily SSL check if not already scheduled
    if (!wp_next_scheduled('kampanya_ssl_daily_check')) {
        wp_schedule_event(time(), 'daily', 'kampanya_ssl_daily_check');
    }
});

add_action('kampanya_ssl_daily_check', function() {
    kampanya_ssl_monitor();
});

function kampanya_ssl_monitor() {
    $domain = 'kampanya.website';
    $log_file = WP_CONTENT_DIR . '/ssl-monitor.log';
    
    // Get certificate info
    $stream = stream_context_create(array('ssl' => array('capture_peer_cert' => true)));
    $fp = @stream_socket_client('ssl://' . $domain . ':443', $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $stream);
    
    if (!$fp) {
        $msg = date('Y-m-d H:i:s') . " - SSL CONNECTION ERROR: {$errstr}\n";
        error_log($msg, 3, $log_file);
        kampanya_ssl_alert_admin("SSL Connection failed on $domain", $msg);
        return;
    }
    
    $context = stream_context_get_params($fp);
    $cert = $context['options']['ssl']['peer_certificate'];
    fclose($fp);
    
    if (!$cert) {
        $msg = date('Y-m-d H:i:s') . " - NO CERTIFICATE FOUND\n";
        error_log($msg, 3, $log_file);
        kampanya_ssl_alert_admin("No SSL certificate for $domain", $msg);
        return;
    }
    
    // Parse certificate
    $parsed = openssl_x509_parse($cert);
    
    if (!$parsed) {
        $msg = date('Y-m-d H:i:s') . " - CERTIFICATE PARSE FAILED\n";
        error_log($msg, 3, $log_file);
        return;
    }
    
    // Check if domain matches
    $subject = $parsed['subject']['CN'] ?? '';
    $alt_names = isset($parsed['extensions']['subjectAltName']) ? $parsed['extensions']['subjectAltName'] : '';
    
    $domain_ok = (
        strpos($subject, $domain) !== false ||
        strpos($alt_names, $domain) !== false ||
        strpos($subject, 'www.' . $domain) !== false ||
        strpos($alt_names, 'www.' . $domain) !== false
    );
    
    if (!$domain_ok) {
        $msg = "CERTIFICATE MISMATCH!\n"
            . "Domain: $domain\n"
            . "Subject: $subject\n"
            . "Alt Names: $alt_names\n"
            . "Expires: " . date('Y-m-d', $parsed['validTo_time_t']) . "\n";
        error_log(date('Y-m-d H:i:s') . " - " . $msg, 3, $log_file);
        kampanya_ssl_alert_admin("SSL CERTIFICATE MISMATCH ON $domain", $msg);
        return;
    }
    
    // Check expiration (alert if < 14 days)
    $expires = $parsed['validTo_time_t'];
    $days_left = floor(($expires - time()) / 86400);
    
    if ($days_left < 14) {
        $msg = "CERTIFICATE EXPIRES IN $days_left DAYS!\n"
            . "Subject: $subject\n"
            . "Expiration: " . date('Y-m-d H:i:s', $expires) . "\n";
        error_log(date('Y-m-d H:i:s') . " - " . $msg, 3, $log_file);
        kampanya_ssl_alert_admin("SSL Certificate expires soon on $domain", $msg);
        return;
    }
    
    // All good
    error_log(date('Y-m-d H:i:s') . " - SSL OK: $subject (expires in $days_left days)\n", 3, $log_file);
}

function kampanya_ssl_alert_admin($subject, $message) {
    $admin_email = get_option('admin_email');
    $from_email = 'admin@' . parse_url(home_url(), PHP_URL_HOST);
    
    $headers = array(
        'From: ' . get_bloginfo('name') . ' <' . $from_email . '>',
        'Content-Type: text/plain; charset=UTF-8',
    );
    
    $body = "KAMPANYA SSL MONITORING ALERT\n"
        . "============================\n\n"
        . $message . "\n"
        . "Please check your hosting SSL/TLS settings immediately.\n"
        . "If renewal is failing, manual renewal may be needed.\n\n"
        . "WordPress: " . home_url() . "\n"
        . "Logged at: " . WP_CONTENT_DIR . "/ssl-monitor.log\n";
    
    wp_mail($admin_email, '[SSL ALERT] ' . $subject, $body, $headers);
}

/* ============================================================
   REST ENDPOINT: kampanya/v1/ssl-status
   Quick SSL status check via API (for admin dashboard)
   ============================================================ */

add_action('rest_api_init', function () {
    register_rest_route('kampanya/v1', '/ssl-status', [
        'methods'             => 'GET',
        'callback'            => function () {
            $domain = 'kampanya.website';
            $stream = stream_context_create(array('ssl' => array('capture_peer_cert' => true)));
            $fp = @stream_socket_client('ssl://' . $domain . ':443', $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $stream);
            
            if (!$fp) {
                return rest_ensure_response([
                    'status'  => 'error',
                    'message' => 'SSL Connection failed: ' . $errstr,
                ]);
            }
            
            $context = stream_context_get_params($fp);
            $cert = $context['options']['ssl']['peer_certificate'];
            fclose($fp);
            
            if (!$cert) {
                return rest_ensure_response([
                    'status'  => 'error',
                    'message' => 'No certificate found',
                ]);
            }
            
            $parsed = openssl_x509_parse($cert);
            
            return rest_ensure_response([
                'status'     => 'ok',
                'domain'     => $domain,
                'subject'    => $parsed['subject']['CN'] ?? 'Unknown',
                'expires'    => date('Y-m-d H:i:s', $parsed['validTo_time_t']),
                'days_left'  => floor(($parsed['validTo_time_t'] - time()) / 86400),
            ]);
        },
        'permission_callback' => function () { return current_user_can('manage_options'); },
    ]);
});

add_action('init', function() {
    // Only run once on first activation or via manual trigger
    if (get_option('kampanya_address_fixed')) {
        return;
    }
    
    $header_settings = get_theme_mod('blocksy_header_settings', '[]');
    $header_data = json_decode($header_settings, true) ?: [];
    
    // Update or create offcanvas section
    if (!isset($header_data['offcanvas'])) {
        $header_data['offcanvas'] = [];
    }
    
    // Find and update the text element with old address
    $updated = false;
    if (is_array($header_data['offcanvas'])) {
        foreach ($header_data['offcanvas'] as &$item) {
            if (isset($item['content']) && strpos($item['content'], '304 North Cardinal') !== false) {
                $item['content'] = '<p><strong>Konumuz</strong></p><p>📍 İstanbul, Türkiye<br>info@kampanya.website<br>Tel: +90 533 466 80 88</p>';
                $updated = true;
                break;
            }
        }
    }
    
    if ($updated) {
        set_theme_mod('blocksy_header_settings', json_encode($header_data));
        update_option('kampanya_address_fixed', 1);
    }
}, 11);

/* ============================================================
   REST: kampanya/v1/debug-address — debug header settings
   ============================================================ */

add_action('rest_api_init', function () {
    register_rest_route('kampanya/v1', '/debug-address', [
        'methods'             => 'GET',
        'callback'            => function () {
            $option_data = get_option('theme_mods_dailypulse-child', []);
            
            // Search for any key containing address or contact info
            $search_keys = [];
            foreach ($option_data as $key => $value) {
                if (is_string($value) && (strpos($value, '304') !== false || strpos($value, 'Cardinal') !== false || strpos($value, 'Dorchester') !== false)) {
                    $search_keys[$key] = substr($value, 0, 200);
                }
            }
            
            return rest_ensure_response([
                'blocksy_header_settings' => $option_data['blocksy_header_settings'] ?? 'NOT FOUND',
                'found_fake_address_in_keys' => $search_keys,
                'all_keys_with_content' => array_filter(
                    array_map(function($k) use ($option_data) {
                        $v = $option_data[$k];
                        if (is_string($v) && strlen($v) > 20 && strlen($v) < 500) {
                            return $k . ': ' . substr($v, 0, 100);
                        }
                        return null;
                    }, array_keys($option_data))
                ),
            ]);
        },
        'permission_callback' => function () { return current_user_can('manage_options'); },
    ]);
});


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
    if (!is_singular('post') && !is_page()) return;
    ob_start('kampanya_strip_hero_section');
}, 1);

/**
 * Strip the Blocksy hero-section div from single post HTML.
 * Uses div-depth counting (not regex) — immune to PCRE limits on large pages.
 */
function kampanya_strip_hero_section($html) {
    $marker = '<div class="hero-section';
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
   KAMPANYA REST API — Set Rank Math SEO meta on any post/page
   Usage: POST /wp-json/kampanya/v1/seo-meta
     body: { "id": 123, "title": "...", "description": "...", "focus": "..." }
   ============================================================ */
add_action('rest_api_init', function () {
    register_rest_route('kampanya/v1', '/seo-meta', [
        'methods'             => 'POST',
        'callback'            => function (WP_REST_Request $r) {
            $id = intval($r->get_param('id'));
            if (!$id || !get_post($id)) {
                return new WP_Error('invalid_id', 'Valid post/page ID required.', ['status' => 400]);
            }
            $set = [];
            if ($t = $r->get_param('title')) {
                update_post_meta($id, 'rank_math_title', sanitize_text_field($t));
                $set[] = 'title';
            }
            if ($d = $r->get_param('description')) {
                update_post_meta($id, 'rank_math_description', sanitize_text_field($d));
                $set[] = 'description';
            }
            if ($f = $r->get_param('focus')) {
                update_post_meta($id, 'rank_math_focus_keyword', sanitize_text_field($f));
                $set[] = 'focus';
            }
            return rest_ensure_response([
                'ok'      => true,
                'post_id' => $id,
                'set'     => $set,
                'current' => [
                    'title'       => get_post_meta($id, 'rank_math_title', true),
                    'description' => get_post_meta($id, 'rank_math_description', true),
                    'focus'       => get_post_meta($id, 'rank_math_focus_keyword', true),
                ],
            ]);
        },
        'permission_callback' => function () { return current_user_can('manage_options'); },
    ]);
});

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
   KAMPANYA REST API — Abone ol / Abonelikten çık (double opt-in)
   ============================================================ */

add_action('rest_api_init', function () {

    register_rest_route('kampanya/v1', '/subscribe', [
        'methods'             => 'POST',
        'callback'            => 'kampanya_rest_subscribe',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('kampanya/v1', '/confirm-email', [
        'methods'             => 'GET',
        'callback'            => 'kampanya_rest_confirm_email',
        'permission_callback' => '__return_true',
    ]);

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
    $existing    = $contact_api->getContact($email);

    if ($existing && $existing->status === 'subscribed') {
        return rest_ensure_response([
            'success' => true,
            'message' => 'Bu e-posta adresi zaten bültenimize kayıtlı. 👍',
        ]);
    }

    // Add as pending until email is confirmed
    $contact_api->createOrUpdate([
        'email'  => $email,
        'status' => 'pending',
        'lists'  => [3],
    ]);

    // Generate token, store for 24h
    $token = bin2hex(random_bytes(32));
    set_transient('k_confirm_' . $token, $email, DAY_IN_SECONDS);

    $confirm_url = home_url('/wp-json/kampanya/v1/confirm-email?token=' . $token);
    $mail_result = kampanya_send_confirmation_email($email, $confirm_url);

    return rest_ensure_response([
        'success'     => true,
        'message'     => 'Teşekkürler! Gelen kutunuza bir onay e-postası gönderdik. Lütfen e-postayı onaylayın. 📬',
        '_mail_debug' => $mail_result,
    ]);
}

function kampanya_rest_confirm_email(WP_REST_Request $request) {
    $token = sanitize_text_field($request->get_param('token'));

    if (!$token) {
        wp_redirect(home_url('/?k_status=invalid'));
        exit;
    }

    $email = get_transient('k_confirm_' . $token);

    if (!$email) {
        wp_redirect(home_url('/?k_status=expired'));
        exit;
    }

    if (!function_exists('FluentCrmApi')) {
        wp_redirect(home_url('/?k_status=error'));
        exit;
    }

    FluentCrmApi('contacts')->createOrUpdate([
        'email'  => $email,
        'status' => 'subscribed',
        'lists'  => [3],
    ]);

    delete_transient('k_confirm_' . $token);

    kampanya_send_welcome_email($email);

    wp_redirect(home_url('/abone-olundu/'));
    exit;
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

    if ($contact && in_array($contact->status, ['subscribed', 'pending'])) {
        $contact->status = 'unsubscribed';
        $contact->save();
    }

    kampanya_send_unsubscribe_confirmation($email);

    return rest_ensure_response([
        'success' => true,
        'message' => 'Aboneliğiniz iptal edildi. Bir onay e-postası gönderdik.',
    ]);
}

/* ---- Email helpers ---- */

function kampanya_smtp_send($to, $subject, $body) {
    // Resend Transactional Email API — key stored in WP options
    $api_key = get_option('k_resend_key', '');

    if (!$api_key) {
        error_log('kampanya_smtp_send: Resend API key not configured');
        return ['ok' => false, 'error' => 'credentials_missing'];
    }

    $response = wp_remote_post('https://api.resend.com/emails', [
        'timeout' => 15,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => wp_json_encode([
            'from'    => 'Kampanya.Website <bildirim@kampanya.website>',
            'to'      => [$to],
            'subject' => $subject,
            'html'    => $body,
        ]),
    ]);

    if (is_wp_error($response)) {
        error_log('kampanya_smtp_send error: ' . $response->get_error_message());
        return ['ok' => false, 'error' => $response->get_error_message()];
    }

    $code      = wp_remote_retrieve_response_code($response);
    $resp_body = json_decode(wp_remote_retrieve_body($response), true);
    error_log('kampanya_smtp_send Resend: code=' . $code . ' resp=' . json_encode($resp_body));
    return ['ok' => ($code >= 200 && $code < 300), 'code' => $code, 'body' => $resp_body];
}

function kampanya_email_base($title, $content, $accent = '#FFD600') {
    return '<!DOCTYPE html>
<html lang="tr" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>' . esc_html($title) . '</title>
<style>
@media only screen and (max-width:600px){
  .ew{width:100%!important}
  .ep{padding:28px 20px!important}
  .ef{padding:20px!important}
}
</style>
</head>
<body style="margin:0;padding:0;background-color:#0F0D11;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#0F0D11;padding:40px 16px;">
<tr><td align="center">
  <table class="ew" width="560" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;">

    <!-- LOGO ROW -->
    <tr>
      <td style="padding:0 0 20px 0;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td style="vertical-align:middle;">
              <span style="font-family:\'Arial Black\',Impact,Arial,sans-serif;font-size:20px;font-weight:900;color:' . $accent . ';letter-spacing:-0.5px;text-transform:uppercase;">KAMPANYA</span><span style="font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:400;color:rgba(255,255,255,0.35);letter-spacing:3px;text-transform:uppercase;">.WEBSITE</span>
            </td>
          </tr>
          <tr>
            <td style="padding-top:6px;">
              <div style="height:2px;width:32px;background-color:' . $accent . ';"></div>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <!-- CARD -->
    <tr>
      <td style="background-color:#17141A;border-radius:12px;overflow:hidden;">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">

          <!-- TOP ACCENT LINE -->
          <tr><td style="height:3px;background-color:' . $accent . ';font-size:0;line-height:0;">&nbsp;</td></tr>

          ' . $content . '

          <!-- FOOTER -->
          <tr>
            <td class="ef" style="padding:20px 40px;border-top:1px solid #2A2730;">
              <p style="margin:0;color:#3D3A42;font-size:11px;text-align:center;line-height:1.8;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.3px;">
                <a href="' . home_url() . '" style="color:' . $accent . ';text-decoration:none;font-weight:700;font-size:11px;">KAMPANYA.WEBSITE</a>
                &nbsp;&nbsp;·&nbsp;&nbsp;
                <a href="' . home_url('/abonelik-iptal') . '" style="color:#3D3A42;text-decoration:underline;">Abonelikten çık</a>
                &nbsp;&nbsp;·&nbsp;&nbsp;
                <a href="' . home_url('/gizlilik-politikasi') . '" style="color:#3D3A42;text-decoration:underline;">Gizlilik</a>
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>

    <!-- BOTTOM TAGLINE -->
    <tr>
      <td style="padding:16px 0 0;text-align:center;">
        <p style="margin:0;color:#2A2730;font-size:10px;font-family:Arial,Helvetica,sans-serif;letter-spacing:2px;text-transform:uppercase;">Haftalık fırsatlar &nbsp;·&nbsp; indirimler &nbsp;·&nbsp; kampanyalar</p>
      </td>
    </tr>

  </table>
</td></tr>
</table>
</body></html>';
}

function kampanya_email_btn($url, $label, $style = 'primary') {
    if ($style === 'ghost') {
        return '<table cellpadding="0" cellspacing="0" border="0" style="margin:0 0 32px;">
          <tr>
            <td style="border:2px solid #2A2730;border-radius:6px;">
              <a href="' . esc_url($url) . '" style="display:inline-block;padding:14px 36px;color:#555;font-family:\'Arial Black\',Impact,Arial,sans-serif;font-size:12px;font-weight:900;text-decoration:none;letter-spacing:1.5px;text-transform:uppercase;">' . esc_html($label) . '</a>
            </td>
          </tr>
        </table>';
    }
    return '<table cellpadding="0" cellspacing="0" border="0" style="margin:0 0 32px;">
      <tr>
        <td style="background-color:#FFD600;border-radius:6px;">
          <a href="' . esc_url($url) . '" style="display:inline-block;padding:16px 44px;color:#17141A;font-family:\'Arial Black\',Impact,Arial,sans-serif;font-size:13px;font-weight:900;text-decoration:none;letter-spacing:1.5px;text-transform:uppercase;">' . esc_html($label) . '</a>
        </td>
      </tr>
    </table>';
}

function kampanya_send_confirmation_email($email, $confirm_url) {
    $content = '
      <tr>
        <td class="ep" style="padding:44px 40px 36px;">
          <p style="margin:0 0 6px;font-family:\'Arial Black\',Impact,Arial,sans-serif;font-size:11px;font-weight:900;color:#FFD600;letter-spacing:3px;text-transform:uppercase;">Adım 1 / 2</p>
          <h1 style="margin:0 0 20px;font-family:\'Arial Black\',Impact,Arial,sans-serif;font-size:30px;font-weight:900;color:#FFFFFF;letter-spacing:-0.5px;text-transform:uppercase;line-height:1.05;">E-postanızı<br>onaylayın</h1>
          <p style="color:#6B6875;font-size:15px;line-height:1.75;margin:0 0 36px;font-family:Arial,Helvetica,sans-serif;">Bültenimize abone olmak için aşağıdaki butona tıklayın. Her hafta en iyi fırsatlar ve kampanyalar doğrudan gelen kutunuza gelecek.</p>
          ' . kampanya_email_btn($confirm_url, 'Aboneliğimi Onayla') . '
          <p style="color:#3D3A42;font-size:12px;line-height:1.7;margin:0;font-family:Arial,Helvetica,sans-serif;">Bu bağlantı 24 saat geçerlidir. Bu isteği siz yapmadıysanız e-postayı silebilirsiniz.</p>
        </td>
      </tr>';

    return kampanya_smtp_send($email, 'Aboneliğinizi onaylayın — Kampanya.Website', kampanya_email_base('Aboneliğinizi Onaylayın', $content));
}

function kampanya_send_welcome_email($email) {
    $content = '
      <tr>
        <td class="ep" style="padding:44px 40px 36px;">
          <p style="margin:0 0 6px;font-family:\'Arial Black\',Impact,Arial,sans-serif;font-size:11px;font-weight:900;color:#FFD600;letter-spacing:3px;text-transform:uppercase;">Hoş geldiniz</p>
          <h1 style="margin:0 0 20px;font-family:\'Arial Black\',Impact,Arial,sans-serif;font-size:30px;font-weight:900;color:#FFD600;letter-spacing:-0.5px;text-transform:uppercase;line-height:1.05;">Aboneliğiniz<br>onaylandı!</h1>
          <p style="color:#6B6875;font-size:15px;line-height:1.75;margin:0 0 16px;font-family:Arial,Helvetica,sans-serif;">Artık Kampanya.Website bülteninin bir parçasısınız.</p>
          <p style="color:#6B6875;font-size:15px;line-height:1.75;margin:0 0 36px;font-family:Arial,Helvetica,sans-serif;">Her hafta en güncel indirimler, fırsatlar ve kampanyalar — doğrudan gelen kutunuza. Bir şey kaçırmayacaksınız.</p>
          ' . kampanya_email_btn(home_url('/firsatlar'), 'Fırsatları Keşfet') . '
        </td>
      </tr>';

    kampanya_smtp_send($email, 'Bültenimize hoş geldiniz — Kampanya.Website', kampanya_email_base('Hoş Geldiniz', $content));
}

function kampanya_send_unsubscribe_confirmation($email) {
    $content = '
      <tr>
        <td class="ep" style="padding:44px 40px 36px;">
          <p style="margin:0 0 6px;font-family:\'Arial Black\',Impact,Arial,sans-serif;font-size:11px;font-weight:900;color:#3D3A42;letter-spacing:3px;text-transform:uppercase;">Bildirim</p>
          <h1 style="margin:0 0 20px;font-family:\'Arial Black\',Impact,Arial,sans-serif;font-size:30px;font-weight:900;color:#FFFFFF;letter-spacing:-0.5px;text-transform:uppercase;line-height:1.05;">Aboneliğiniz<br>iptal edildi</h1>
          <p style="color:#6B6875;font-size:15px;line-height:1.75;margin:0 0 12px;font-family:Arial,Helvetica,sans-serif;"><span style="color:#3D3A42;font-size:13px;">' . esc_html($email) . '</span><br>adresi bülten listemizden çıkarıldı.</p>
          <p style="color:#6B6875;font-size:15px;line-height:1.75;margin:0 0 36px;font-family:Arial,Helvetica,sans-serif;">Fikrinizi değiştirirseniz istediğiniz zaman tekrar abone olabilirsiniz.</p>
          ' . kampanya_email_btn(home_url(), 'Ana Sayfaya Dön', 'ghost') . '
        </td>
      </tr>';

    kampanya_smtp_send($email, 'Aboneliğiniz iptal edildi — Kampanya.Website', kampanya_email_base('Abonelik İptal', $content, '#2A2730'));
}

/* ============================================================
   TURKISH STRING TRANSLATIONS — Override Blocksy/WooCommerce English strings
   ============================================================ */
add_filter('gettext', 'kampanya_tr_strings', 20, 3);
add_filter('gettext_with_context', 'kampanya_tr_strings_ctx', 20, 4);
function kampanya_tr_strings($translation, $text, $domain) {
    static $map = null;
    if ($map === null) {
        $map = [
            // ── Search & archive headings ──
            'Search'                                    => 'Ara',
            'Search for...'                             => 'Ara...',
            'Search...'                                 => 'Ara...',
            'No results'                                => 'Sonuç bulunamadı',
            'No results found'                          => 'Sonuç bulunamadı',
            'Search Results'                            => 'Arama Sonuçları',
            'Search Results for: %s'                    => '"%s" için arama sonuçları',
            'Search Results for %s'                     => '"%s" için arama sonuçları',
            'Nothing Found'                             => 'Sonuç Bulunamadı',
            'Nothing found'                             => 'Sonuç bulunamadı',
            'Sorry, but nothing matched your search terms. Please try again with some different keywords.' => 'Üzgünüz, arama teriminizle eşleşen içerik bulunamadı. Lütfen farklı anahtar kelimelerle tekrar deneyin.',
            'It seems we can\'t find what you\'re looking for. Perhaps searching can help.' => 'Aradığınız içerik bulunamadı. Belki arama yaparak bulabilirsiniz.',
            'It seems we can\'t find what you\'re looking for.'  => 'Aradığınız içerik bulunamadı.',
            'Perhaps searching can help.'               => 'Belki arama yaparak bulabilirsiniz.',
            // ── 404 page ──
            'Oops! That page can\'t be found.'             => 'Ups! Sayfa bulunamadı.',
            "Oops! That page can’t be found."         => 'Ups! Sayfa bulunamadı.',
            'Page not found'                               => 'Sayfa Bulunamadı',
            'Error 404'                                    => 'Hata 404',
            'It looks like nothing was found at this location. Maybe try to search for something else?' => 'Bu sayfada hiçbir şey bulunamadı. Başka bir şey aramayı deneyin.',
            'It looks like nothing was found at this location.' => 'Bu sayfada hiçbir şey bulunamadı.',
            'Maybe try to search for something else?'   => 'Başka bir şey aramayı deneyin.',
            'Go back'                                   => 'Geri dön',
            'Go to homepage'                            => 'Ana sayfaya git',
            'Return to homepage'                        => 'Ana sayfaya dön',
            // ── Trending / archive ──
            'Trending now'                              => 'Şu An Trend',
            'Trending'                                  => 'Trend',
            'Popular posts'                             => 'Popüler Yazılar',
            // ── Categories / tags ──
            'Categories'                                => 'Kategoriler',
            'Tags'                                      => 'Etiketler',
            'Archives'                                  => 'Arşiv',
            'Category Archives: %s'                     => '%s Kategorisi',
            'Tag Archives: %s'                          => '%s Etiketi',
            'Author Archives: %s'                       => '%s Yazarı',
            'Monthly Archives: %s'                      => '%s Arşivi',
            'Yearly Archives: %s'                       => '%s Arşivi',
            // ── Post meta ──
            'Recent Posts'                              => 'Son Yazılar',
            'Recent Comments'                           => 'Son Yorumlar',
            'Read more'                                 => 'Devamını oku',
            'Read More'                                 => 'Devamını oku',
            'Continue reading'                          => 'Devamını oku',
            'Continue reading %s'                       => '%s yazısının devamını oku',
            'Posted on'                                 => 'Yayın tarihi',
            'Posted in'                                 => 'Kategori',
            'by'                                        => 'Yazar',
            'Tagged'                                    => 'Etiket',
            'Published'                                 => 'Yayınlandı',
            'Updated'                                   => 'Güncellendi',
            'Written by'                                => 'Yazan',
            'Author'                                    => 'Yazar',
            // ── Comments ──
            'Leave a comment'                           => 'Yorum yap',
            'Leave a Reply'                             => 'Yorum Yap',
            'Comments are closed.'                      => 'Yorumlar kapatılmıştır.',
            'Post Comment'                              => 'Yorumu Gönder',
            'Comment'                                   => 'Yorum',
            'Your comment'                              => 'Yorumunuz',
            'Your name'                                 => 'Adınız',
            'Your email'                                => 'E-posta adresiniz',
            'Name'                                      => 'Ad',
            'Email'                                     => 'E-posta',
            'Website'                                   => 'Website',
            '%s comment'                                => '%s yorum',
            '%s comments'                               => '%s yorum',
            'One comment'                               => '1 yorum',
            'No comments'                               => 'Yorum yok',
            // ── Pagination ──
            'Older posts'                               => 'Daha eski yazılar',
            'Newer posts'                               => 'Daha yeni yazılar',
            'Previous'                                  => 'Önceki',
            'Next'                                      => 'Sonraki',
            'Page %s of %s'                             => '%s / %s sayfası',
            'Older Entries'                             => 'Eski Yazılar',
            'Newer Entries'                             => 'Yeni Yazılar',
            // ── Forms / buttons ──
            'Submit'                                    => 'Gönder',
            'Subscribe'                                 => 'Abone Ol',
            'First Name'                                => 'Ad',
            'Last Name'                                 => 'Soyad',
            // ── WooCommerce ──
            'Cart'                                      => 'Sepet',
            'Checkout'                                  => 'Ödeme',
            'My account'                                => 'Hesabım',
            'My Account'                                => 'Hesabım',
            'Shop'                                      => 'Mağaza',
            'Add to cart'                               => 'Sepete ekle',
            'Product'                                   => 'Ürün',
            'Price'                                     => 'Fiyat',
            'Quantity'                                  => 'Adet',
            'Total'                                     => 'Toplam',
            'Continue shopping'                         => 'Alışverişe devam et',
            'Proceed to checkout'                       => 'Ödemeye geç',
            'Apply coupon'                              => 'Kuponu uygula',
            'Coupon code'                               => 'Kupon kodu',
            'Update cart'                               => 'Sepeti güncelle',
            'Your cart is currently empty.'             => 'Sepetiniz şu an boş.',
            // ── Misc Blocksy UI ──
            'Popular'                                   => 'Popüler',
            'Social Icons'                              => 'Sosyal Medya',
            'Socials'                                   => 'Sosyal Medya',
            'Skip to content'                           => 'İçeriğe geç',
            'Back to top'                               => 'Yukarı çık',
            'Load more'                                 => 'Daha fazla yükle',
            'Show more'                                 => 'Daha fazla göster',
            'Share'                                     => 'Paylaş',
            'Share on Facebook'                         => 'Facebook\'ta paylaş',
            'Share on Twitter'                          => 'Twitter\'da paylaş',
            'Copy link'                                 => 'Bağlantıyı kopyala',
            'Link copied'                               => 'Bağlantı kopyalandı',
            'Table of Contents'                         => 'İçindekiler',
            'By'                                        => 'Yazar:',
            'Min read'                                  => 'dk okuma',
            'min read'                                  => 'dk okuma',
        ];
    }
    return isset($map[$text]) ? $map[$text] : $translation;
}
function kampanya_tr_strings_ctx($translation, $text, $context, $domain) {
    return kampanya_tr_strings($translation, $text, $domain);
}

/* ============================================================
   BLOCKSY JS LOCALIZATION — Override English front-end strings
   ============================================================ */
add_action('wp_footer', 'kampanya_override_blocksy_js_strings', 5);
function kampanya_override_blocksy_js_strings() {
    echo '<script>
if (typeof ct_localizations !== "undefined") {
    ct_localizations.show_more_text              = "Daha fazla göster";
    ct_localizations.more_text                   = "Daha fazla";
    ct_localizations.search_live_results         = "Arama sonuçları";
    ct_localizations.search_live_no_results      = "Sonuç bulunamadı";
    ct_localizations.search_live_no_result       = "Sonuç bulunamadı";
    ct_localizations.search_live_one_result      = "%s sonuç bulundu. Seçmek için Tab tuşuna basın.";
    ct_localizations.search_live_many_results    = "%s sonuç bulundu. Seçmek için Tab tuşuna basın.";
    ct_localizations.clipboard_copied            = "Kopyalandı!";
    ct_localizations.clipboard_failed            = "Kopyalanamadı";
    ct_localizations.expand_submenu              = "Alt menüyü aç";
    ct_localizations.collapse_submenu            = "Alt menüyü kapat";
    if (ct_localizations.search_live_stock_status_texts) {
        ct_localizations.search_live_stock_status_texts.instock    = "Stokta var";
        ct_localizations.search_live_stock_status_texts.outofstock = "Stokta yok";
    }
}
</script>' . "\n";
}

/* ============================================================
   SEARCH PAGE TITLE — translate archive heading
   ============================================================ */
add_filter('get_the_archive_title', function($title) {
    if (is_search()) {
        $q = get_search_query();
        return $q
            ? sprintf('"<span>%s</span>" için arama sonuçları', esc_html($q))
            : 'Arama Sonuçları';
    }
    return $title;
});

/* ============================================================
   404 PAGE — translate Blocksy's hardcoded English strings
   ============================================================ */
add_filter('the_content', function($content) {
    if (is_404()) {
        $content = str_replace(
            ["Oops! That page can't be found.", "It looks like nothing was found at this location.", "Maybe try to search for something else?"],
            ["Ups! Bu sayfa bulunamadı.", "Bu konumda hiçbir şey bulunamadı.", "Başka bir şey aramayı deneyin."],
            $content
        );
    }
    return $content;
});


/* ============================================================
   GÜNCELLEME / DOSYA SİSTEMİ — FTP sorma sorununu çöz

   Sorun: WordPress eklenti/tema/çekirdek güncellemesi yaparken FTP
   bilgisi soruyor ve girilen bilgiler kabul edilmiyor. Site Health'in
   teşhisi: "Dosya sahipliği nedeniyle siteniz güncellemeleri FTP
   üzerinden yapıyor."

   Sebep: get_filesystem_method() geçici bir dosya oluşturup sahibini
   hedef dizinin sahibiyle karşılaştırıyor. cPanel/LiteSpeed kurulumunda
   bu kontrol yanlış negatif verebiliyor ve WP "ftpext" yöntemine
   düşüyor. Oysa PHP kullanıcısı dizine gerçekten yazabiliyor — WP
   Pusher'ın tema dosyalarını başarıyla yazabilmesi bunun kanıtı.

   Çözüm: filesystem_method filtresiyle 'direct' yöntemini zorlamak.
   Bu, WordPress'in dosyaları doğrudan PHP ile yazmasını sağlar.

   Güvenlik notu: 'direct' yöntemi tek başına bir güvenlik zafiyeti
   değildir; paylaşımlı sunucularda kullanıcı ayrımı için var olan FTP
   fallback'i devre dışı bırakır. Yazma gerçekten mümkün değilse
   güncelleme FTP formu yerine düz bir hata verir (veri kaybı olmaz).
   ============================================================ */
function kampanya_force_direct_filesystem($method) {
    return 'direct';
}
add_filter('filesystem_method', 'kampanya_force_direct_filesystem', 10, 1);

/* ============================================================
   KAMPANYA REST API — Bakım / teşhis

   Sadece manage_options yetkisi olan (yönetici) kullanıcılar
   çağırabilir. Yapabildiği işlemler sabit bir allowlist ile
   sınırlıdır — rastgele kod/komut çalıştırmaz.
   ============================================================ */
add_action('rest_api_init', function () {
    register_rest_route('kampanya/v1', '/maintenance', [
        'methods'             => 'POST',
        'callback'            => 'kampanya_maintenance',
        'permission_callback' => function (WP_REST_Request $r) {
            return current_user_can('manage_options');
        },
        'args' => [
            'action' => [
                'required' => true,
                'type'     => 'string',
                'enum'     => ['diagnose', 'fix_litespeed_qs', 'list_updates', 'update_plugins', 'seo_diagnose'],
            ],
        ],
    ]);
});

/**
 * Bir UID'yi kullanıcı adına çevirir. posix_* yoksa ya da arama
 * başarısızsa güvenli bir metin döndürür (PHP 8'de false['name']
 * uyarısına düşmemek için).
 */
function kampanya_uid_name($uid) {
    if (!function_exists('posix_getpwuid') || $uid === false || $uid === null) {
        return 'unknown';
    }
    $info = @posix_getpwuid($uid);
    return (is_array($info) && isset($info['name'])) ? $info['name'] : (string) $uid;
}

function kampanya_maintenance(WP_REST_Request $request) {
    $action = $request->get_param('action');

    /**
     * Rank Math neden hicbir sey basmiyor? Salt okunur teshis.
     * Eklenti "aktif" görünüyor ama frontend'de ne meta description ne de
     * schema çikiyor; rankmath/v1 REST namespace'i de yok. Buradaki
     * sinyaller sorunun yükleme mi yoksa ayar mi oldugunu ayirir.
     */
    if ($action === 'seo_diagnose') {
        $active = (array) get_option('active_plugins', []);
        $rm_files = array_values(array_filter($active, function ($f) {
            return stripos($f, 'seo-by-rank-math') !== false || stripos($f, 'rank-math') !== false;
        }));

        $modules = get_option('rank_math_modules', null);
        $titles  = get_option('rank-math-options-titles', null);
        $general = get_option('rank-math-options-general', null);

        $out = [
            'active_plugin_entries' => $rm_files,
            'plugin_file_exists'    => array_map(function ($f) {
                return [$f => file_exists(trailingslashit(WP_PLUGIN_DIR) . $f)];
            }, $rm_files),
            'class_RankMath'        => class_exists('RankMath'),
            'const_RANK_MATH_VERSION' => defined('RANK_MATH_VERSION') ? RANK_MATH_VERSION : null,
            'wizard_completed'      => get_option('rank_math_registration_skip', null),
            'modules_option'        => is_array($modules) ? array_values($modules) : $modules,
            'titles_option_keys'    => is_array($titles) ? array_slice(array_keys($titles), 0, 40) : $titles,
            'noindex_post'          => is_array($titles) && isset($titles['pt_post_custom_robots']) ? $titles['pt_post_custom_robots'] : null,
            'robots_post'           => is_array($titles) && isset($titles['pt_post_robots']) ? $titles['pt_post_robots'] : null,
            'general_option_keys'   => is_array($general) ? array_slice(array_keys($general), 0, 40) : $general,
            'blog_public'           => get_option('blog_public'),
            'wp_head_hooked'        => [],
            'other_seo_plugins'     => array_values(array_filter($active, function ($f) {
                return preg_match('~(wordpress-seo|all-in-one-seo|seopress|squirrly|slim-seo)~i', $f);
            })),
        ];

        global $wp_filter;
        if (isset($wp_filter['wp_head'])) {
            foreach ($wp_filter['wp_head']->callbacks as $prio => $cbs) {
                foreach ($cbs as $id => $cb) {
                    $name = is_string($cb['function']) ? $cb['function'] : (
                        is_array($cb['function'])
                            ? (is_object($cb['function'][0]) ? get_class($cb['function'][0]) : (string) $cb['function'][0]) . '::' . $cb['function'][1]
                            : 'closure'
                    );
                    if (stripos($name, 'rank') !== false || stripos($name, 'seo') !== false) {
                        $out['wp_head_hooked'][] = $prio . ' ' . $name;
                    }
                }
            }
        }

        return new WP_REST_Response($out, 200);
    }

    if ($action === 'diagnose') {
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $plugins_dir = WP_PLUGIN_DIR;
        $probe       = trailingslashit($plugins_dir) . '.kampanya-write-probe';
        $can_write   = false;
        if (@file_put_contents($probe, 'x') !== false) {
            $can_write = true;
            @unlink($probe);
        }

        // Kendi filtremizi geçici olarak kaldırıp WordPress'in filtresiz
        // olarak hangi yöntemi seçeceğini de ölçüyoruz — yoksa teşhis
        // her zaman 'direct' der ve hiçbir şey öğrenemeyiz.
        remove_filter('filesystem_method', 'kampanya_force_direct_filesystem', 10);
        $raw_method = get_filesystem_method();
        add_filter('filesystem_method', 'kampanya_force_direct_filesystem', 10, 1);

        return [
            'filesystem' => [
                'method_without_our_filter' => $raw_method,
                'method_with_our_filter'    => get_filesystem_method(),
                'plugins_dir'               => $plugins_dir,
                'plugins_writable'          => $can_write,
                'php_user'                  => function_exists('posix_geteuid')
                    ? kampanya_uid_name(posix_geteuid())
                    : 'posix_unavailable',
                'plugins_dir_owner'         => file_exists($plugins_dir)
                    ? kampanya_uid_name(@fileowner($plugins_dir))
                    : 'unknown',
                'fs_method_const'           => defined('FS_METHOD') ? FS_METHOD : null,
            ],
            'litespeed' => [
                'remove_query_strings' => get_option('litespeed.conf.optm-qs_rm', 'unset'),
                'css_combine'          => get_option('litespeed.conf.optm-css_comb', 'unset'),
            ],
            'core' => [
                'current_version' => get_bloginfo('version'),
            ],
        ];
    }

    if ($action === 'fix_litespeed_qs') {
        // LiteSpeed'in "Remove Query Strings" ayarı asset URL'lerindeki
        // ?ver=... kısmını siliyor. Bu da filemtime tabanlı cache
        // busting'i etkisiz bırakıp Cloudflare'in 1 yıllık önbelleğinde
        // takılı kalmamıza sebep oluyor. Kapatıyoruz.
        $before = get_option('litespeed.conf.optm-qs_rm', 'unset');
        update_option('litespeed.conf.optm-qs_rm', 0);

        if (class_exists('\LiteSpeed\Purge')) {
            \LiteSpeed\Purge::purge_all();
        }

        return [
            'setting' => 'litespeed.conf.optm-qs_rm',
            'before'  => $before,
            'after'   => get_option('litespeed.conf.optm-qs_rm', 'unset'),
        ];
    }

    if ($action === 'list_updates') {
        require_once ABSPATH . 'wp-admin/includes/update.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        wp_update_plugins();
        $plugin_updates = get_site_transient('update_plugins');
        $pending = [];
        if (!empty($plugin_updates->response)) {
            foreach ($plugin_updates->response as $file => $data) {
                $installed = get_plugin_data(WP_PLUGIN_DIR . '/' . $file, false, false);
                $pending[] = [
                    'file'    => $file,
                    'name'    => $installed['Name'] ?? $file,
                    'from'    => $installed['Version'] ?? '?',
                    'to'      => $data->new_version ?? '?',
                ];
            }
        }

        wp_version_check();
        $core = get_site_transient('update_core');
        $core_update = null;
        if (!empty($core->updates) && isset($core->updates[0]) && $core->updates[0]->response === 'upgrade') {
            $core_update = $core->updates[0]->current;
        }

        return [
            'core_current'   => get_bloginfo('version'),
            'core_update_to' => $core_update,
            'plugins'        => $pending,
        ];
    }

    if ($action === 'update_plugins') {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/update.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';

        wp_update_plugins();
        $updates = get_site_transient('update_plugins');
        if (empty($updates->response)) {
            return ['updated' => [], 'note' => 'Güncellenecek eklenti yok'];
        }

        // Hangi eklentiler aktifti? Upgrader bazen deaktive edebiliyor;
        // sonda aynı durumu geri yüklüyoruz.
        $was_active = [];
        foreach (array_keys($updates->response) as $file) {
            $was_active[$file] = is_plugin_active($file);
        }

        $skin     = new Automatic_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader($skin);
        $results  = $upgrader->bulk_upgrade(array_keys($updates->response));

        $report = [];
        foreach ((array) $results as $file => $result) {
            $ok = ($result && !is_wp_error($result));
            if ($ok && !empty($was_active[$file]) && !is_plugin_active($file)) {
                activate_plugin($file);
            }
            $data = file_exists(WP_PLUGIN_DIR . '/' . $file)
                ? get_plugin_data(WP_PLUGIN_DIR . '/' . $file, false, false)
                : [];
            $report[] = [
                'file'        => $file,
                'name'        => $data['Name'] ?? $file,
                'new_version' => $data['Version'] ?? '?',
                'success'     => $ok,
                'error'       => is_wp_error($result) ? $result->get_error_message() : null,
                'reactivated' => !empty($was_active[$file]) && is_plugin_active($file),
            ];
        }

        if (class_exists('\LiteSpeed\Purge')) {
            \LiteSpeed\Purge::purge_all();
        }

        return ['updated' => $report, 'messages' => $skin->get_upgrade_messages()];
    }

    return new WP_Error('unknown_action', 'Bilinmeyen işlem', ['status' => 400]);
}

/* ============================================================
   ADMIN BAR İKONLARI — inline garanti

   custom.css içindeki dashicons düzeltmesi doğru çalışıyor, ancak
   LiteSpeed giriş yapmış kullanıcılar için AYRI bir birleştirilmiş CSS
   paketi üretiyor ve bu paket bayatlayabiliyor (Cloudflare de üstüne
   önbelleğe alıyor). Sonuç: yönetici çubuğundaki ikonlar bazı
   kullanıcılarda hâlâ bozuk görünüyor.

   Bu kuralı doğrudan <head> içine basarak paketlemeden/CDN'den tamamen
   bağımsız hale getiriyoruz. Sadece yönetici çubuğu görünürken çıkar,
   yani normal ziyaretçiye hiçbir maliyeti yok.
   ============================================================ */
add_action('wp_head', 'kampanya_adminbar_dashicons_inline', 99);
function kampanya_adminbar_dashicons_inline() {
    if (!is_admin_bar_showing()) {
        return;
    }
    echo '<style id="kampanya-adminbar-dashicons">'
       . '#wpadminbar .ab-icon,'
       . '#wpadminbar .ab-icon:before,'
       . '#wpadminbar .ab-item:before,'
       . '#wpadminbar .ab-item:after,'
       . '#wpadminbar #adminbarsearch:before,'
       . '#wpadminbar .wp-admin-bar-arrow,'
       . '#wpadminbar [class^="dashicons"],'
       . '#wpadminbar [class*=" dashicons"],'
       . '#wpadminbar [class^="dashicons"]:before,'
       . '#wpadminbar [class*=" dashicons"]:before'
       . '{font-family:dashicons!important}'
       . '</style>';
}
