<?php
/**
 * Kritik CSS (scripts/kritik-css-uret.py üretir, assets/css/kritik/*.css + manifest.json).
 *
 * Sayfa türüne göre, o türdeki sayfalarda DOM'a eşleşen tüm kurallar (her stil dosyası için ayrı parça, kendi <link>'inin yerinde) HTML'ye gömülür; tam stil dosyaları
 * (Blocksy main/page-title/sidebar, global.css, custom.min.css) render'ı ENGELLEMEDEN yüklenir. Kural seçimi
 * "ekranın üstü" değil "sayfadaki her öğe" olduğundan tam CSS gelince yalnızca etkileşim durumları eklenir,
 * yerleşim kaymaz.
 *
 * Güvenlik: kritik CSS yalnızca manifesteki md5'ler canlı stil dosyalarıyla BİREBİR tutuyorsa ve sayfadaki
 * kuyruklanmış stil dosyaları kümesi manifestle aynıysa kullanılır. Blocksy global.css'i yeniden ürettiyse,
 * custom.css değiştiyse ya da bir güncelleme geldiyse o tür için özellik kendiliğinden kapanır (sayfa eskisi
 * gibi çalışır) ta ki script yeniden çalıştırılana dek. ?idk_kritik=0 özelliği kapatır (üretici bunu kullanır).
 */
if (!defined('ABSPATH')) {
    exit;
}

/** Sayfa türü anahtarı ('' = bu sayfada kullanma). */
function idk_kritik_tur()
{
    if (function_exists('is_woocommerce') && (is_woocommerce() || is_cart() || is_checkout() || is_account_page())) {
        return '';
    }
    if (is_front_page()) {
        return 'anasayfa';
    }
    if (is_404()) {
        return 'k404';
    }
    if (is_home() || is_archive() || is_search()) {
        return 'liste';
    }
    if (is_singular('post')) {
        return 'yazi';
    }
    if (is_page()) {
        return 'sayfa';
    }
    return '';
}

/** Stil dosyasının diskteki md5'i (yalnızca wp-content altındaki dosyalar), yoksa null. */
function idk_kritik_md5($handle)
{
    static $onbellek = [];
    if (array_key_exists($handle, $onbellek)) {
        return $onbellek[$handle];
    }
    $onbellek[$handle] = null;
    $kayit = wp_styles()->registered[$handle] ?? null;
    if (!$kayit || !is_string($kayit->src) || $kayit->src === '') {
        return null;
    }
    $src   = preg_replace('#^https?:#', '', strtok($kayit->src, '?'));
    $kok   = preg_replace('#^https?:#', '', content_url());
    if (strpos($src, $kok) !== 0) {
        return null;
    }
    $yol = WP_CONTENT_DIR . substr($src, strlen($kok));
    $onbellek[$handle] = is_file($yol) ? md5_file($yol) : null;
    return $onbellek[$handle];
}

/** ['css' => ..., 'handles' => [...]] ya da false. Sayfa başına bir kez hesaplanır. */
function idk_kritik_durum()
{
    static $durum = null;
    if ($durum !== null) {
        return $durum;
    }
    $durum = false;
    if (isset($_GET['idk_kritik']) || is_admin() || is_user_logged_in() || is_feed() || is_preview()
        || is_customize_preview() || is_embed() || is_robots()) {
        return $durum;
    }
    $tur = idk_kritik_tur();
    if ($tur === '') {
        return $durum;
    }
    $dizin    = get_stylesheet_directory() . '/assets/css/kritik/';
    $manifest = is_file($dizin . 'manifest.json') ? json_decode((string) file_get_contents($dizin . 'manifest.json'), true) : null;
    $adaylar  = is_array($manifest) && is_array($manifest[$tur] ?? null) ? $manifest[$tur] : [];
    // Sayfada şu an kuyruklanmış hedef stil dosyaları, bir manifest girdisindeki kümeyle aynı olmalı
    // (aynı türde bile sayfalar farklı stil dosyaları yükleyebilir: blog dizini page-title.css yüklemez).
    $hedef = ['blocksy-dynamic-global', 'dailypulse-custom', 'ct-main-styles', 'ct-page-title-styles', 'ct-sidebar-styles',
              'fluent-form-styles', 'fluentform-public-default'];
    $aktif = array_values(array_filter($hedef, function ($h) {
        return wp_style_is($h, 'enqueued');
    }));
    sort($aktif);
    $girdi = null;
    foreach ($adaylar as $aday) {
        $k = $aday['handles'] ?? [];
        sort($k);
        if ($k === $aktif && !empty($aday['md5']) && !empty($aday['dosya'])) {
            $girdi = $aday;
            break;
        }
    }
    if (!$girdi) {
        return $durum;
    }
    $veri = is_file($dizin . basename($girdi['dosya'])) ? json_decode((string) file_get_contents($dizin . basename($girdi['dosya'])), true) : null;
    $css  = is_array($veri) && is_array($veri['css'] ?? null) ? $veri['css'] : [];
    foreach ($girdi['handles'] as $h) {
        if (!isset($css[$h]) || !is_string($css[$h])) {
            return $durum;
        }
    }
    foreach ($girdi['handles'] as $h) {
        if (idk_kritik_md5($h) !== ($girdi['md5'][$h] ?? false)) {
            return $durum;
        }
    }
    $durum = ['css' => array_map(function ($c) {
        return str_replace('</style', '<\\/style', $c);
    }, $css), 'handles' => $girdi['handles']];
    return $durum;
}

add_filter('style_loader_tag', function ($tag, $handle) {
    $d = idk_kritik_durum();
    if (!$d || !in_array($handle, $d['handles'], true)) {
        return $tag;
    }
    $kritik = '<style id="idk-kritik-' . esc_attr($handle) . '">' . $d['css'][$handle] . "</style>\n";
    if (strpos($tag, "media='print'") !== false) {
        // yayin-hazirligi.php bu etiketi zaten engellemesiz yapmış (+ noscript): yalnızca kritik parçayı öne ekle
        return $kritik . $tag;
    }
    $async = preg_replace("/media=(['\"])all\\1/", "media='print' onload=\"this.media='all';this.onload=null\"", $tag, 1);
    // Kritik kural parçası, ait olduğu <link>'in tam yerinde: WordPress'in satır içi stilleriyle (global-styles vb.)
    // olan basamaklı sıra (eşit özgüllükte sonraki kazanır) özgün sayfayla birebir aynı kalır.
    return $kritik . $async . '<noscript>' . $tag . '</noscript>' . "\n";
}, 30, 2);
