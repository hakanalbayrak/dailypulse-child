<?php
/**
 * Permanent redirects for merged articles, and closing username exposure.
 *
 * @package DailyPulse
 */

if (!defined('ABSPATH')) exit;

/**
 * 301 map for articles that were merged into another one.
 * Keys are request paths without slashes; values are target paths.
 * A merged post is set to draft, so its URL would otherwise 404 and lose
 * whatever ranking and inbound links it had.
 */
function kampanya_redirect_map() {
    return [
        // 2026-09-11: article 51 merged into article 50 (same product class,
        // the two posts were competing for the same search term)
        'kisisel-finans-planlayici-organizer-urunleri' => '/butce-takibi-icin-planlayici-defter-rehberi/',
    ];
}

add_action('template_redirect', function () {
    $path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    $map  = kampanya_redirect_map();
    if ($path !== '' && isset($map[$path])) {
        wp_redirect(home_url($map[$path]), 301);
        exit;
    }
}, 0);

/**
 * The login username (kampanya_admin) was public in three places: the core
 * users sitemap, /author/<login>/ archives (also reachable as ?author=1),
 * and the unauthenticated /wp/v2/users REST route. Together with an
 * exposed login page that is half a credential. The site publishes as an
 * Organization (see inc/seo.php), so none of them is needed.
 */
add_filter('wp_sitemaps_add_provider', function ($provider, $name) {
    return $name === 'users' ? false : $provider;
}, 10, 2);

add_action('template_redirect', function () {
    if (is_author()) {
        wp_redirect(home_url('/'), 301);
        exit;
    }
}, 1);

add_filter('rest_endpoints', function ($endpoints) {
    if (is_user_logged_in()) return $endpoints;
    foreach (array_keys($endpoints) as $route) {
        if (strpos($route, '/wp/v2/users') === 0) unset($endpoints[$route]);
    }
    return $endpoints;
});
