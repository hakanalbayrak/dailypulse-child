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
        return Idk_Onbellek::kaydet(idk_css_satir_ici($html));
    });
}, 0);

/**
 * Sitenin kendi, render'ı engelleyen stil dosyalarını HTML'ye gömer (yalnızca anonim/önbellekli sayfalar).
 * Her biri ayrı bir istek ve gidiş-dönüş demekti; yavaş mobil ağda ilk boyamayı ~1 sn geciktiriyordu.
 * <noscript> içindekilere ve media='all' olmayanlara (async yüklenenler) dokunulmaz. Hata olursa <link> kalır.
 */
function idk_css_satir_ici($html)
{
    $site = untrailingslashit(site_url());
    $parcalar = preg_split('#(<noscript>.*?</noscript>)#s', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    $toplam = 0;
    foreach ($parcalar as $i => $p) {
        if ($i % 2 === 1) {
            continue;
        }
        $parcalar[$i] = preg_replace_callback(
            "#<link rel='stylesheet' id='([^']+)' href='(" . preg_quote($site, '#') . "/[^']+?\.css)(?:\?[^']*)?' media='all' />\s*#",
            function ($m) use (&$toplam, $site) {
                $url = $m[2];
                $dosya = ABSPATH . ltrim(substr($url, strlen($site)), '/');
                if (!is_file($dosya) || ($boyut = filesize($dosya)) > 110000 || $toplam + $boyut > 230000) {
                    return $m[0];
                }
                $css = (string) file_get_contents($dosya);
                if ($css === '' || stripos($css, '@import') !== false) {
                    return $m[0];
                }
                $taban = substr($url, 0, strrpos($url, '/') + 1);
                $css = preg_replace_callback('#url\(\s*([\'"]?)(?!data:|https?:|//|/|\#)([^\'")]+)\1\s*\)#i', function ($u) use ($taban) {
                    $parts = explode('/', rtrim($taban, '/') . '/' . $u[2]);
                    $out = [];
                    foreach ($parts as $x) {
                        if ($x === '..') {
                            array_pop($out);
                        } elseif ($x !== '.') {
                            $out[] = $x;
                        }
                    }
                    return 'url(' . implode('/', $out) . ')';
                }, $css);
                $toplam += strlen($css);
                return '<style id="' . esc_attr($m[1]) . '-satir-ici">' . str_replace('</style', '<\/style', $css) . "</style>\n";
            },
            $p
        );
    }
    return implode('', $parcalar);
}

// Değişiklikte temizle.
$idk_temizle = function () {
    Idk_Onbellek::temizle();
};
foreach (['save_post', 'deleted_post', 'trashed_post', 'edit_term', 'delete_term', 'wp_update_nav_menu', 'switch_theme',
          'customize_save_after', 'update_option_blogname', 'update_option_blogdescription', 'update_option_page_on_front',
          'update_option_page_for_posts', 'update_option_idk_ga_id', 'update_option_idk_adsense_id', 'widget_update_callback'] as $idk_h) {
    add_action($idk_h, $idk_temizle);
}
