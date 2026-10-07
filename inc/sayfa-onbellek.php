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
 * Kritik CSS: ekranın üstünü boyamak için gereken kurallar (scripts/kritik-css.mjs üretir, sayfa türüne
 * göre assets/css/kritik-*.css) HTML'ye gömülür; tam stil dosyaları ilk boyamadan SONRA yüklenir
 * (media=print → all). Yalnızca anonim/önbellekli sayfalarda; kritik dosya yoksa hiçbir şey değişmez.
 */
function idk_css_satir_ici($html)
{
    if (is_file(Idk_Onbellek::dizin() . '/INLINE_KAPALI')) {
        return $html;
    }
    $tip = is_front_page() ? 'anasayfa' : (is_singular('post') ? 'yazi' : (is_page() ? 'sayfa' : 'arsiv'));
    $dosya = get_stylesheet_directory() . '/assets/css/kritik-' . $tip . '.css';
    $kritik = is_file($dosya) ? trim((string) file_get_contents($dosya)) : '';
    // Kritik CSS sayfaya gömülünce göreli url(../fonts/...) sayfa adresine göre çözülüp 404 verir; mutlak yap.
    $kritik = preg_replace('#url\((["\']?)\.\./#', 'url($1' . get_stylesheet_directory_uri() . '/assets/', $kritik);
    if ($kritik === '') {
        return $html;
    }
    $kimlikler = ['blocksy-dynamic-global-css', 'dailypulse-custom-css', 'ct-main-styles-css', 'ct-page-title-styles-css'];
    $parcalar = preg_split('#(<noscript>.*?</noscript>)#s', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    $eklendi = false;
    $adresler = [];
    foreach ($parcalar as $i => $p) {
        if ($i % 2 === 1) {
            continue;
        }
        foreach ($kimlikler as $k) {
            $p = preg_replace_callback(
                "#<link rel='stylesheet' id='(" . preg_quote($k, '#') . ")' href='([^']+)' media='all' />#",
                function ($m) use (&$eklendi, &$adresler, $kritik) {
                    $once = '';
                    if (!$eklendi) {
                        $eklendi = true;
                        $once = '<style id="idk-kritik">' . str_replace('</style', '<\\/style', $kritik) . "</style>\n";
                    }
                    $adresler[] = html_entity_decode($m[2]);
                    return $once . "<noscript><link rel='stylesheet' id='" . $m[1] . "-ns' href='" . $m[2] . "' media='all' /></noscript>";
                },
                $p
            );
        }
        $parcalar[$i] = $p;
    }
    $html = implode('', $parcalar);
    if ($adresler) {
        // Tam stil dosyaları sayfa yüklendikten ~1 sn sonra, sırayla (kritik CSS ilk ekranı zaten boyadı).
        $js = '<script>addEventListener("load",function(){setTimeout(function(){' . wp_json_encode($adresler, JSON_UNESCAPED_SLASHES)
            . '.forEach(function(u){var l=document.createElement("link");l.rel="stylesheet";l.href=u;document.head.appendChild(l)})},1000)})</script>';
        $html = preg_replace('#</head>#', $js . "\n</head>", $html, 1);
    }
    return $html;
}
