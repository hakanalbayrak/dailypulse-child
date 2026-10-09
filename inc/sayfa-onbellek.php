<?php
/**
 * Sayfa önbelleği — WordPress tarafı (bkz. onbellek-motor.php).
 *
 *  - wp-content/advanced-cache.php stub'ını kurar (başka birinin dosyasının üzerine yazmaz);
 *  - anonim, sorgusuz sayfa yanıtlarını önbelleğe alır;
 *  - içerik/menü/ayar/tema değişince ve tema sürümü değişince her şeyi temizler.
 * Güvenlik: kapalı anahtarı (cache/idk-sayfa/KAPALI) ve maintenance onbellek_kapat eylemi.
 */
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/onbellek-motor.php';

const IDK_ONBELLEK_ISARET = 'idk-sayfa-onbellek-stub-v1';

// Stub + sürüm kontrolü (ucuz: iki küçük dosya okuma). Sürüm değişince önbellek temizlenir.
add_action('init', function () {
    if (wp_doing_ajax()) {
        return;
    }
    $stub = WP_CONTENT_DIR . '/advanced-cache.php';
    $var = is_file($stub) ? (string) @file_get_contents($stub) : '';
    if (strpos($var, IDK_ONBELLEK_ISARET) === false && $var === '') {
        @file_put_contents($stub, "<?php\n// " . IDK_ONBELLEK_ISARET . "\n"
            . "\$idk_m = __DIR__ . '/themes/dailypulse-child/inc/onbellek-motor.php';\n"
            . "if (is_file(\$idk_m)) { try { require_once \$idk_m; Idk_Onbellek::sun(); } catch (\\Throwable \$e) {} }\n");
    }
    if (defined('KAMPANYA_TEMA_SURUM') && Idk_Onbellek::surum() !== KAMPANYA_TEMA_SURUM) {
        Idk_Onbellek::temizle();
        $d = Idk_Onbellek::dizin();
        if (is_dir($d) || @mkdir($d, 0755, true)) {
            @file_put_contents($d . '/.surum', KAMPANYA_TEMA_SURUM);
        }
    }
}, 1);

// Anonim ön yüz sayfalarını kaydet.
add_action('template_redirect', function () {
    if (!Idk_Onbellek::istek_uygun() || is_user_logged_in() || is_404() || is_search() || is_feed()
        || is_preview() || is_customize_preview() || is_robots() || is_trackback()) {
        return;
    }
    ob_start(function ($html) {
        return Idk_Onbellek::kaydet($html);
    });
}, 0);
