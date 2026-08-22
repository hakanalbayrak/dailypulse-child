<?php
/**
 * Stil ve script yükleme
 *
 * NOT — cache busting hakkında:
 * Asset URL'leri sabit bir sürüm ("1.0.0") ile yayınlandığında URL hiç
 * değişmiyor. Cloudflare bu dosyaları 1 yıl (max-age=31557600) önbelleğe
 * aldığı için, tema güncellense bile ziyaretçilere ESKİ CSS servis ediliyordu.
 * Çözüm: sürüm numarası olarak dosyanın değiştirilme zamanını (filemtime)
 * kullanmak. Böylece dosya her değiştiğinde URL de değişiyor, Cloudflare
 * bunu yeni bir kaynak olarak görüp origin'den taze içerik çekiyor.
 */

/**
 * Bir tema dosyasının filemtime değerini sürüm olarak döndürür.
 * Dosya yoksa sabit tema sürümüne geri düşer.
 */
function dailypulse_asset_version($relative_path) {
    $full_path = DAILYPULSE_DIR . $relative_path;
    return file_exists($full_path) ? (string) filemtime($full_path) : DAILYPULSE_VERSION;
}

function dailypulse_enqueue_assets() {
    wp_enqueue_style(
        'dailypulse-custom',
        DAILYPULSE_URI . '/assets/css/custom.css',
        array(),
        dailypulse_asset_version('/assets/css/custom.css')
    );

    wp_enqueue_script(
        'dailypulse-custom',
        DAILYPULSE_URI . '/assets/js/custom.js',
        array(),
        dailypulse_asset_version('/assets/js/custom.js'),
        true
    );

    wp_localize_script('dailypulse-custom', 'dpAjax', array(
        'url'   => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dp_subscribe_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'dailypulse_enqueue_assets');
