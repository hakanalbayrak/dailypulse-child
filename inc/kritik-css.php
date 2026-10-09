<?php
/**
 * Kritik CSS (scripts/kritik-css-uret.py üretir, assets/css/kritik/*.css + manifest.json).
 *
 * Sayfa türüne göre, o türdeki sayfalarda DOM'a eşleşen tüm kurallar HTML'ye gömülür; tam stil dosyaları
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
    $hedef = ['blocksy-dynamic-global', 'dailypulse-custom', 'ct-main-styles', 'ct-page-title-styles', 'ct-sidebar-styles'];
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
    $css = is_file($dizin . basename($girdi['dosya'])) ? trim((string) file_get_contents($dizin . basename($girdi['dosya']))) : '';
    if ($css === '') {
        return $durum;
    }
    foreach ($girdi['handles'] as $h) {
        if (idk_kritik_md5($h) !== ($girdi['md5'][$h] ?? false)) {
            return $durum;
        }
    }
    $durum = ['css' => str_replace('</style', '<\\/style', $css), 'handles' => $girdi['handles']];
    return $durum;
}

// Stil etiketleri basılmadan önce (wp_head 8) kritik CSS yerinde olmalı.
add_action('wp_head', function () {
    $d = idk_kritik_durum();
    if ($d) {
        echo '<style id="idk-kritik">' . $d['css'] . "</style>\n";
    }
}, 2);

add_filter('style_loader_tag', function ($tag, $handle) {
    $d = idk_kritik_durum();
    if (!$d || !in_array($handle, $d['handles'], true)) {
        return $tag;
    }
    $async = preg_replace("/media=(['\"])all\\1/", "media='print' onload=\"this.media='all';this.onload=null\"", $tag, 1);
    return $async . '<noscript>' . $tag . '</noscript>' . "\n";
}, 30, 2);
