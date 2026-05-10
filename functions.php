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

function kampanya_email_base($title, $content) {
    return '<!DOCTYPE html>
<html lang="tr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . esc_html($title) . '</title></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:40px 16px;">
  <tr><td align="center">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
      <tr><td style="background:#17141A;padding:28px 40px;">
        <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;letter-spacing:-.3px;">Kampanya.Website</h1>
        <p style="margin:4px 0 0;color:#aaa;font-size:13px;">Haftalık fırsatlar ve kampanyalar</p>
      </td></tr>
      ' . $content . '
      <tr><td style="background:#f8f9fa;padding:20px 40px;border-top:1px solid #eee;">
        <p style="margin:0;color:#aaa;font-size:12px;text-align:center;line-height:1.6;">
          Kampanya.Website &nbsp;·&nbsp;
          <a href="' . home_url('/abonelikten-cik') . '" style="color:#aaa;text-decoration:underline;">Abonelikten çık</a> &nbsp;·&nbsp;
          <a href="' . home_url('/gizlilik-politikasi') . '" style="color:#aaa;text-decoration:underline;">Gizlilik Politikası</a>
        </p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>';
}

function kampanya_email_btn($url, $label, $bg = '#FF6B35') {
    return '<table cellpadding="0" cellspacing="0" style="margin:0 auto 28px;">
      <tr><td style="background:' . $bg . ';border-radius:8px;">
        <a href="' . esc_url($url) . '" style="display:inline-block;padding:15px 40px;color:#ffffff;font-size:15px;font-weight:700;text-decoration:none;letter-spacing:-.1px;">' . esc_html($label) . '</a>
      </td></tr>
    </table>';
}

function kampanya_send_confirmation_email($email, $confirm_url) {
    $content = '
      <tr><td style="padding:36px 40px 28px;">
        <h2 style="margin:0 0 14px;font-size:20px;color:#17141A;font-weight:700;">E-posta adresinizi onaylayın</h2>
        <p style="color:#555;font-size:15px;line-height:1.7;margin:0 0 24px;">Bültenimize abone olmak için aşağıdaki butona tıklayarak e-posta adresinizi onaylayın. Her hafta en iyi fırsat ve kampanyalar doğrudan gelen kutunuza gelecek.</p>
        ' . kampanya_email_btn($confirm_url, 'Aboneliğimi Onayla') . '
        <p style="color:#bbb;font-size:12px;line-height:1.6;margin:0;">Bu bağlantı 24 saat geçerlidir. Bu isteği siz yapmadıysanız bu e-postayı görmezden gelebilirsiniz.</p>
      </td></tr>';

    return kampanya_smtp_send($email, 'Aboneliğinizi onaylayın — Kampanya.Website', kampanya_email_base('Aboneliğinizi Onaylayın', $content));
}

function kampanya_send_welcome_email($email) {
    $content = '
      <tr><td style="padding:36px 40px 28px;">
        <h2 style="margin:0 0 14px;font-size:20px;color:#17141A;font-weight:700;">Aramıza hoş geldiniz! 🎉</h2>
        <p style="color:#555;font-size:15px;line-height:1.7;margin:0 0 12px;">Aboneliğiniz onaylandı. Artık Kampanya.Website bülteninin bir parçasısınız.</p>
        <p style="color:#555;font-size:15px;line-height:1.7;margin:0 0 24px;">Her hafta en güncel indirimler, fırsatlar ve kampanyalar doğrudan gelen kutunuza gelecek. Bir şey kaçırmayacaksınız!</p>
        ' . kampanya_email_btn(home_url('/firsatlar'), 'Fırsatları Keşfet') . '
      </td></tr>';

    kampanya_smtp_send($email, 'Hoş geldiniz! Kampanya bültenine katıldınız 🎉', kampanya_email_base('Hoş Geldiniz', $content));
}

function kampanya_send_unsubscribe_confirmation($email) {
    $content = '
      <tr><td style="padding:36px 40px 28px;">
        <h2 style="margin:0 0 14px;font-size:20px;color:#17141A;font-weight:700;">Aboneliğiniz İptal Edildi</h2>
        <p style="color:#555;font-size:15px;line-height:1.7;margin:0 0 12px;"><strong>' . esc_html($email) . '</strong> adresi bülten listemizden çıkarıldı.</p>
        <p style="color:#555;font-size:15px;line-height:1.7;margin:0 0 24px;">Artık Kampanya.Website bülteninden e-posta almayacaksınız. Fikrinizi değiştirirseniz istediğiniz zaman tekrar abone olabilirsiniz.</p>
        ' . kampanya_email_btn(home_url(), 'Ana Sayfaya Dön', '#17141A') . '
      </td></tr>';

    kampanya_smtp_send($email, 'Aboneliğiniz iptal edildi — Kampanya.Website', kampanya_email_base('Abonelik İptal', $content));
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

