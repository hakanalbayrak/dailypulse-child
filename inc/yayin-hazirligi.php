<?php
/**
 * Yayın öncesi hazırlık (2026-10-05).
 *
 * Site denetiminde (scripts/site-denetimi.py) çıkan eksikler:
 *   - başlıklar çok uzundu (ana sayfa 133 karakter): arama sonucunda kesiliyor;
 *   - Hakkımızda / İletişim / Blog sayfalarında <h1> yoktu;
 *   - arşiv sayfalarında canonical yoktu;
 *   - sepet, iç etiket ("52-icerik-projesi") ve işlem sonrası sayfalar sitemap'teydi;
 *   - robots.txt /go/ yönlendirmelerini taramaya açıktı; llms.txt ve 404 sayfası yoktu;
 *   - çerez bildirimi yoktu.
 *
 * @package DailyPulse
 */

if (!defined('ABSPATH')) exit;

/**
 * İçeriği zayıf ya da amacı dışında olduğu için arama sonuçlarından ve
 * sitemap'ten çıkarılan yazı/sayfalar. Silinmiyor, yalnızca noindex.
 *   82-85  : "Kampanya" dönemi genel yazıları (ürün, buy-box ve h1 yok)
 *   3364   : abone-olundu, 3365: abonelik-iptal (işlem sonrası sayfalar)
 *   11-14  : WooCommerce mağaza/sepet/ödeme/hesabım — site satış yapmıyor; WooCommerce
 *            12'yi sepet sayfası olarak tanımadığı için is_cart() yetmiyordu
 */
function idk_noindex_idler() {
    // 3300 (2026-10-08'de 7 doğrulanmış ürünle yeniden yazıldı, indekste; eskiden 648 kelime, 10 ürün, yoğun affiliate), 3344 (kitap yazısı, 2026-10-08'de yeniden yazıldı ama ~550 kelime; 3358 genişletilip indekse alındı):
    // AdSense/Google "yetersiz içerik" değerlendirmesi için genişletilene kadar dizine girmez.
    return [82, 83, 84, 85, 3364, 3365, 11, 12, 13, 14, 3344];
}

/* ------------------------------------------------------------------
   1. BAŞLIKLAR — <title> 60 karakteri aşmasın
   ------------------------------------------------------------------ */
add_filter('document_title_parts', function ($p) {
    if (is_front_page()) {
        return ['title' => 'ince detay', 'tagline' => 'Türkiye’nin detay bülteni'];
    }
    // Makale başlığı arama sonucunda kesilecek kadar uzunsa, kısa bir SEO başlığı
    // (rank_math_title yazı meta'sı; scripts/fix-seo-basliklari.py yazar) kullanılır.
    if (is_singular()) {
        $ozel = trim((string) get_post_meta(get_queried_object_id(), 'rank_math_title', true));
        if ($ozel !== '') {
            $p['title'] = $ozel;
        }
    }
    $p['site'] = 'ince detay';
    // " – ince detay" = 13 karakter. Başlık zaten uzunsa markayı eklemeyip
    // başlığın kesilmesini önlüyoruz.
    $baslik = isset($p['title']) ? wp_strip_all_tags((string) $p['title']) : '';
    if (mb_strlen($baslik) + 13 > 60) {
        unset($p['site']);
    }
    return $p;
}, 20);

/* ------------------------------------------------------------------
   2. <h1> — başlığı temada gizli olan sayfalara görünür bir h1
   ------------------------------------------------------------------ */
add_filter('the_content', function ($c) {
    if (is_admin() || !is_page() || is_front_page() || !in_the_loop() || !is_main_query()) {
        return $c;
    }
    if (stripos($c, '<h1') !== false) {
        return $c;
    }
    if (function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page())) {
        return $c;
    }
    return '<h1 class="idk-sayfa-baslik">' . esc_html(get_the_title()) . "</h1>\n" . $c;
}, 5);

// Yazı listesi sayfası (/blog/): şablon sayfa içeriğini basmıyor, h1 yok.
// Görünür bir başlık şablonun düzenini bozabileceği için ekran okuyucuya açık,
// görsel olarak gizli.
add_action('loop_start', function ($q) {
    static $yapildi = false;
    if ($yapildi || !($q instanceof WP_Query) || !$q->is_main_query() || !is_home() || is_front_page()) {
        return;
    }
    $yapildi = true;
    $baslik = get_the_title((int) get_option('page_for_posts'));
    echo '<h1 class="screen-reader-text">' . esc_html($baslik ?: 'Blog') . "</h1>\n";
});

/* ------------------------------------------------------------------
   3. CANONICAL — çekirdek yalnızca tekil sayfalarda basıyor
   ------------------------------------------------------------------ */
add_action('wp_head', function () {
    if (!function_exists('kampanya_seo_should_run') || !kampanya_seo_should_run()) {
        return;
    }
    if (is_singular() || is_front_page() || !(is_home() || is_category() || is_tag() || is_tax())) {
        return;
    }
    $url   = kampanya_seo_current_url();
    $sayfa = (int) get_query_var('paged');
    if ($sayfa > 1) {
        $url = trailingslashit($url) . 'page/' . $sayfa . '/';
    }
    printf('<link rel="canonical" href="%s" />' . "\n", esc_url($url));
}, 2);

/* ------------------------------------------------------------------
   4. NOINDEX + SITEMAP DIŞLAMASI
   ------------------------------------------------------------------ */
add_filter('wp_robots', function ($r) {
    $woo = function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page());
    $ozel = is_singular() && in_array((int) get_queried_object_id(), idk_noindex_idler(), true);
    // Yalnızca noindex yazılar içeren bir kategori (ör. /category/egitim/) dizine girecek bir şey göstermez
    if (!$ozel && is_category()) {
        $var = get_posts(['numberposts' => 1, 'category' => (int) get_queried_object_id(), 'post_status' => 'publish',
                          'post__not_in' => idk_noindex_idler(), 'fields' => 'ids']);
        $ozel = !$var;
    }
    // Tarih arşivleri (/2026/, /2026/09/) kategori listelerinin kopyası: noindex
    if (is_tag() || is_search() || is_404() || is_date() || $woo || $ozel) {
        $r['noindex'] = true;
        $r['follow']  = true;
        unset($r['max-image-preview']);
    } else {
        // Arama sonucunda tam özet ve büyük görsel gösterilmesine izin ver
        $r['max-snippet']       = '-1';
        $r['max-video-preview'] = '-1';
    }
    return $r;
});

// Etiketler yalnızca iç düzen için kullanılıyor ("52-icerik-projesi")
add_filter('wp_sitemaps_taxonomies', function ($taxonomies) {
    unset($taxonomies['post_tag']);
    return $taxonomies;
});

add_filter('wp_sitemaps_posts_query_args', function ($args, $post_type) {
    $atla = idk_noindex_idler();
    if ($post_type === 'page' && function_exists('wc_get_page_id')) {
        foreach (['shop', 'cart', 'checkout', 'myaccount'] as $k) {
            $id = (int) wc_get_page_id($k);
            if ($id > 0) {
                $atla[] = $id;
            }
        }
    }
    $args['post__not_in'] = array_merge((array) ($args['post__not_in'] ?? []), $atla);
    return $args;
}, 10, 2);

/* ------------------------------------------------------------------
   5. robots.txt — /go/ yönlendirmeleri taranmasın
   ------------------------------------------------------------------ */
add_filter('robots_txt', function ($out) {
    return str_replace(
        'Allow: /wp-admin/admin-ajax.php',
        "Allow: /wp-admin/admin-ajax.php\nDisallow: /go/\nDisallow: /?s=\nDisallow: /search/",
        $out
    ) . (stripos($out, 'Sitemap:') === false ? "\nSitemap: " . home_url('/sitemap.xml') . "\n" : '')
      . "\nUser-agent: Mediapartners-Google\nDisallow:\n";
}, 20);

/* ------------------------------------------------------------------
   6. /sitemap.xml ve /llms.txt
   ------------------------------------------------------------------ */
function idk_llms_uret() {
    $url  = untrailingslashit(home_url());
    $out  = "# ince detay\n\n";
    $out .= "> Türkiye'de alışveriş yapanlar için bağımsız ürün rehberleri ve karşılaştırmalar. "
          . "Ürünler; ilan özellikleri, satıcı bilgisi ve kullanıcı yorumları incelenerek değerlendirilir. "
          . "Metinlerde fiyat, puan ve stok bilgisi yazılmaz; bunlar hızla eskir.\n\n";
    $out .= "Dil: Türkçe. Bazı bağlantılar affiliate (ortaklık) bağlantısıdır; ayrıntılar Affiliate Disclosure sayfasındadır. "
          . "İletişim: info@incedetay.com\n\n";

    $sayfalar = ['hakkimizda', 'editoryal-ilkeler', 'sikca-sorulan-sorular', 'affiliate-disclosure', 'gizlilik-politikasi', 'iletisim'];
    $satir = [];
    foreach ($sayfalar as $slug) {
        $p = get_page_by_path($slug);
        if ($p && $p->post_status === 'publish') {
            $satir[] = '- [' . wp_strip_all_tags(get_the_title($p)) . '](' . get_permalink($p) . ')';
        }
    }
    if ($satir) {
        $out .= "## Site hakkında\n" . implode("\n", $satir) . "\n\n";
    }

    $yazilar = get_posts([
        'numberposts' => -1,
        'post_status' => 'publish',
        'post__not_in' => idk_noindex_idler(),
        'orderby'     => 'title',
        'order'       => 'ASC',
    ]);
    $gruplar = [];
    foreach ($yazilar as $y) {
        $kat = get_the_category($y->ID);
        $ad  = $kat ? $kat[0]->name : 'Genel';
        $d   = (string) get_post_meta($y->ID, 'rank_math_description', true);
        $d   = $d !== '' ? $d : wp_trim_words(wp_strip_all_tags($y->post_excerpt ?: $y->post_content), 22, '…');
        $gruplar[$ad][] = '- [' . wp_strip_all_tags($y->post_title) . '](' . get_permalink($y) . ')'
                        . ($d !== '' ? ': ' . preg_replace('/\s+/', ' ', $d) : '');
    }
    ksort($gruplar);
    foreach ($gruplar as $ad => $liste) {
        $out .= '## ' . $ad . "\n" . implode("\n", $liste) . "\n\n";
    }
    $out .= "## Sitemap\n- [sitemap.xml]($url/sitemap.xml)\n";
    return $out;
}

add_action('init', function () {
    $yol = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    if ($yol !== 'llms.txt') {
        return;
    }
    $metin = get_transient('idk_llms_v2');
    if ($metin === false) {
        $metin = idk_llms_uret();
        set_transient('idk_llms_v2', $metin, 6 * HOUR_IN_SECONDS);
    }
    status_header(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo $metin;
    exit;
}, 1);

// Yeni yazı/sayfa yayınlanınca llms.txt tazelensin
add_action('save_post', function () {
    delete_transient('idk_llms_v2');
    delete_transient('idk_sitemap_v1');
});

/* ------------------------------------------------------------------
   7. ÇEREZ BİLDİRİMİ + izin verilirse analitik (GA4)
   ------------------------------------------------------------------ */
add_action('wp_footer', function () {
    if (is_admin() || is_feed() || is_embed()) {
        return;
    }
    $ga = (string) get_option('idk_ga_id', '');
    $ga = preg_match('/^G-[A-Z0-9]{4,14}$/', $ga) ? $ga : '';
    $ads = idk_adsense_id();
    $metin = $ads
        ? 'Bu site, çalışması için gerekli çerezleri kullanır. İzin verirseniz ziyaretçi sayısını anlamak ve reklam göstermek için analitik ve reklam çerezlerini de kullanırız.'
        : 'Bu site, çalışması için gerekli çerezleri kullanır. İzin verirseniz ziyaretçi sayısını anlamak için analitik çerezleri de kullanırız.';
    ?>
<div id="idk-cerez" class="idk-cerez" role="region" aria-label="Çerez tercihi">
  <p class="idk-cerez__metin"><?php echo esc_html($metin); ?> Ayrıntılar: <a href="<?php echo esc_url(home_url('/cerez-politikasi/')); ?>">Çerez Politikası</a></p>
  <div class="idk-cerez__dugmeler">
    <button type="button" class="idk-cerez__btn" data-idk-cerez="reddet">Reddet</button>
    <button type="button" class="idk-cerez__btn idk-cerez__btn--birincil" data-idk-cerez="kabul">Kabul et</button>
  </div>
</div>
<script>
(function () {
  var GA = <?php echo wp_json_encode($ga); ?>, ADS = <?php echo wp_json_encode($ads); ?>, ANAHTAR = 'idk_cerez', kutu = document.getElementById('idk-cerez');
  if (!kutu) { return; }
  function oku() {
    try { return localStorage.getItem(ANAHTAR); } catch (e) {
      var m = document.cookie.match(/(?:^|; )idk_cerez=([^;]+)/); return m ? m[1] : null;
    }
  }
  function yaz(v) {
    try { localStorage.setItem(ANAHTAR, v); } catch (e) {}
    document.cookie = 'idk_cerez=' + v + ';path=/;max-age=15552000;SameSite=Lax;Secure';
  }
  // Analitik etiketi <head>'de Consent Mode v2 ile (varsayılan: reddedildi) bulunur;
  // "Kabul et"ten sonra yalnızca izin durumu güncellenir.
  function analitik() {
    if (!GA || typeof window.gtag !== 'function') { return; }
    window.gtag('consent', 'update', { analytics_storage: 'granted' });
  }
  // Reklam komut dosyası da YALNIZCA "Kabul et"ten sonra yüklenir (hesap doğrulaması için
  // sayfada yalnızca hesap kimliği etiketi bulunur, reklam kodu değil).
  function reklam() {
    if (!ADS || window.idkAdsYuklendi) { return; }
    window.idkAdsYuklendi = true;
    var s = document.createElement('script');
    s.async = true; s.crossOrigin = 'anonymous';
    s.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' + ADS;
    document.head.appendChild(s);
  }
  var secim = oku();
  // Banner HTML'de görünür gelir (LCP'yi geciktirmesin); karar verilmişse head'deki betik sınıfla gizler.
  if (secim === 'kabul') { analitik(); reklam(); kutu.hidden = true; } else if (secim === 'reddet') { kutu.hidden = true; }
  kutu.addEventListener('click', function (e) {
    var b = e.target.closest('[data-idk-cerez]');
    if (!b) { return; }
    var v = b.getAttribute('data-idk-cerez');
    yaz(v); kutu.hidden = true; document.documentElement.classList.add('idk-cerez-tamam');
    if (v === 'kabul') { analitik(); reklam(); }
  });
  // Çerez politikasındaki "tercihleri değiştir" bağlantısı
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-idk-cerez-ac]')) { e.preventDefault(); document.documentElement.classList.remove('idk-cerez-tamam'); kutu.hidden = false; }
  });
})();
</script>
    <?php
}, 1); // 1: banner betiği, footer'daki engelleyici betiklerden (jQuery, form, Turnstile) ÖNCE çalışsın; yoksa LCP öğesi banner olup geç görünüyor

/* ------------------------------------------------------------------
   7b. GA4 etiketi + Consent Mode v2 (varsayılan: depolama reddedildi)
   Google'ın etiket denetleyicisi çerez penceresine tıklamaz; etiket <head>'de
   bulunmalı. İzin verilene kadar çerez yazılmaz, kimlik tutulmaz.
   ------------------------------------------------------------------ */
add_action('wp_head', function () {
    if (is_admin() || is_feed() || is_embed() || is_user_logged_in()) {
        return;
    }
    $ga = (string) get_option('idk_ga_id', '');
    if (!preg_match('/^G-[A-Z0-9]{4,14}$/', $ga)) {
        return;
    }
    ?>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('consent','default',{analytics_storage:'denied',ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied',wait_for_update:500});
try { if (localStorage.getItem('idk_cerez') === 'kabul') { gtag('consent','update',{analytics_storage:'granted'}); } } catch (e) {}
gtag('js', new Date());
gtag('config', <?php echo wp_json_encode($ga); ?>, {anonymize_ip: true});
</script>
<script>
// gtag.js sayfa yüklendikten sonra gelir (ilk boyamayı/LCP'yi geciktirmesin); dataLayer kuyruğu korunur.
addEventListener('load', function () {
  setTimeout(function () {
    var s = document.createElement('script');
    s.async = true; s.src = 'https://www.googletagmanager.com/gtag/js?id=<?php echo esc_js($ga); ?>';
    document.head.appendChild(s);
  }, 1500);
});
</script>
    <?php
}, 2);

/* ------------------------------------------------------------------
   8. GÜVENLİK BAŞLIKLARI (PageSpeed "Güven ve Güvenlik" uyarıları)
   CSP bilerek yok: satır içi betikler/stiller yüzünden kırılgan.
   ------------------------------------------------------------------ */
add_action('send_headers', function () {
    if (is_admin()) {
        return;
    }
    header('Strict-Transport-Security: max-age=31536000');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
});

/* ------------------------------------------------------------------
   9. <head> TEMİZLİĞİ — tarayıcıya ve arama motoruna işe yaramayan etiketler
   ------------------------------------------------------------------ */
add_action('init', function () {
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wp_shortlink_wp_head', 10);
    remove_action('template_redirect', 'wp_shortlink_header', 11);
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    add_filter('the_generator', '__return_empty_string');
    // Yorumlar kapalı: yorum akışı bağlantısı boşuna
    add_filter('feed_links_show_comments_feed', '__return_false');
}, 20);

add_action('wp_head', function () {
    echo '<meta name="theme-color" content="#14201B">' . "\n";
}, 3);

// jquery-migrate (eski jQuery API'leri için uyumluluk katmanı) ön yüzde gerekmiyor
add_action('wp_default_scripts', function ($scripts) {
    if (is_admin() || empty($scripts->registered['jquery'])) {
        return;
    }
    $scripts->registered['jquery']->deps = array_diff((array) $scripts->registered['jquery']->deps, ['jquery-migrate']);
});

/* ------------------------------------------------------------------
   10. BÜYÜK/KÜÇÜK HARF — /HAKKIMIZDA/ 200 dönüp kopya sayfa oluşturuyordu
   ------------------------------------------------------------------ */
add_action('init', function () {
    if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
        return;
    }
    $uri  = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $yol  = (string) parse_url($uri, PHP_URL_PATH);
    if ($yol === '' || $yol === strtolower($yol) || preg_match('~^/(wp-admin|wp-content|wp-includes|wp-json)/~i', $yol)) {
        return;
    }
    $q = (string) parse_url($uri, PHP_URL_QUERY);
    wp_redirect(untrailingslashit(home_url()) . strtolower($yol) . ($q !== '' ? '?' . $q : ''), 301);
    exit;
}, 1);

/* ------------------------------------------------------------------
   11. IndexNow — Bing, Yandex, Seznam, Naver'e yeni/güncellenen adresi anında bildirir
   (Google IndexNow'u desteklemiyor; Google için sitemap + Search Console.)
   Anahtar dosyası /<anahtar>.txt olarak sunulur; anahtar gizli değil, sahipliği kanıtlar.
   ------------------------------------------------------------------ */
function idk_indexnow_anahtar() {
    $k = (string) get_option('idk_indexnow_key', '');
    if (!preg_match('/^[a-f0-9]{32}$/', $k)) {
        $k = bin2hex(random_bytes(16));
        update_option('idk_indexnow_key', $k, false);
    }
    return $k;
}

add_action('init', function () {
    $yol = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    if (!preg_match('/^([a-f0-9]{32})\.txt$/', $yol, $m) || $m[1] !== idk_indexnow_anahtar()) {
        return;
    }
    status_header(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo $m[1];
    exit;
}, 1);

/** @return array ['ok'=>bool, ...] — $bekle=false iken yanıt beklenmez (yayın isteğini yavaşlatmaz) */
function idk_indexnow_gonder(array $urller, $bekle = false) {
    $urller = array_values(array_unique(array_filter($urller)));
    if (!$urller) {
        return ['ok' => false, 'hata' => 'adres yok'];
    }
    $anahtar = idk_indexnow_anahtar();
    $govde   = wp_json_encode([
        'host'        => parse_url(home_url(), PHP_URL_HOST),
        'key'         => $anahtar,
        'keyLocation' => home_url('/' . $anahtar . '.txt'),
        'urlList'     => array_slice($urller, 0, 10000),
    ]);
    $r = wp_remote_post('https://api.indexnow.org/indexnow', [
        'timeout'  => $bekle ? 20 : 3,
        'blocking' => (bool) $bekle,
        'headers'  => ['Content-Type' => 'application/json; charset=utf-8'],
        'body'     => $govde,
    ]);
    if (!$bekle) {
        return ['ok' => true, 'gonderilen' => count($urller)];
    }
    if (is_wp_error($r)) {
        return ['ok' => false, 'hata' => $r->get_error_message()];
    }
    $kod = (int) wp_remote_retrieve_response_code($r);
    return ['ok' => in_array($kod, [200, 202], true), 'kod' => $kod, 'gonderilen' => count($urller)];
}

// Yazı/sayfa yayınlanınca ya da yayındayken güncellenince
add_action('transition_post_status', function ($yeni, $eski, $post) {
    if ($yeni !== 'publish' || !in_array($post->post_type, ['post', 'page'], true)) {
        return;
    }
    if (in_array((int) $post->ID, idk_noindex_idler(), true)) {
        return;
    }
    idk_indexnow_gonder([get_permalink($post)]);
}, 10, 3);

/* ------------------------------------------------------------------
   12. HIZ — gövde fontunu önceden yükle, jQuery'yi render'ı engellemeyecek yere al
   ------------------------------------------------------------------ */
// Quicksand (self-host) yalnızca custom.css indirilip ayrıştırıldıktan sonra keşfediliyordu;
// ana sayfada en büyük boyama (LCP) hero metni, yani bu fontu bekleyen metin. Türkçe için hem
// latin hem latin-ext alt kümesi gerekli (ğ ş İ latin-ext'te, ı latin'de).
add_action('wp_head', function () {
    foreach (['fraunces/fraunces-tr-v2.woff2', 'sourcesans3/sourcesans3-tr-v2.woff2'] as $f) {
        printf('<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url(get_stylesheet_directory_uri() . '/assets/fonts/' . $f));
    }
}, 1);

// Ekranın üstünü boyamak için gerekmeyen stil dosyaları render'ı engellemesin (media=print →
// yüklenince all). Form altbilgide, "trending" bloğu mobilde gizli, ürün-inceleme eklentisi kullanılmıyor.
add_filter('style_loader_tag', function ($tag, $handle) {
    if (is_admin() || !in_array($handle, ['fluent-form-styles', 'fluentform-public-default', 'blocksy-ext-trending-styles', 'blocksy-ext-product-reviews-styles'], true)) {
        return $tag;
    }
    $async = preg_replace('/media=([\'"])all\1/', "media='print' onload=\"this.media='all'\"", $tag, 1);
    return $async . '<noscript>' . $tag . '</noscript>' . "\n";
}, 10, 2);

// Ana sayfa, liste (blog/kategori/arama) ve 404 şablonları blok içeriği basmaz ve kenar çubuğu yoktur:
// WP blok kütüphanesi + global-styles (~27 KB satır içi) ile Blocksy sidebar.css boşuna render'ı geciktiriyordu.
// Makale ve sayfalarda dokunulmaz (içerik blok kullanır).
function idk_liste_stilleri_temizle() {
    if (is_admin() || !(is_front_page() || is_home() || is_archive() || is_search() || is_404())
        || (function_exists('is_woocommerce') && is_woocommerce())) {
        return;
    }
    foreach (['ct-sidebar-styles', 'wp-block-library', 'wp-block-library-theme', 'global-styles', 'classic-theme-styles',
              'wp-block-heading', 'wp-block-paragraph', 'wp-block-columns', 'wp-block-group'] as $h) {
        wp_dequeue_style($h);
    }
}
add_action('wp_enqueue_scripts', 'idk_liste_stilleri_temizle', 100);
// Bazı çekirdek stilleri (global-styles, blok başına satır içi) bundan sonra kuyruğa girer; basılmadan hemen önce tekrar temizle.
add_action('wp_head', 'idk_liste_stilleri_temizle', 7);
add_action('wp_footer', 'idk_liste_stilleri_temizle', 0);
// global-styles (preset değişkenleri + utility sınıfları, ~23 KB) kuyruğa girmesin: kanca hiç çalışmasın.
add_action('wp', function () {
    if (is_admin() || !(is_front_page() || is_home() || is_archive() || is_search() || is_404())
        || (function_exists('is_woocommerce') && is_woocommerce())) {
        return;
    }
    foreach (['wp_enqueue_scripts', 'wp_footer'] as $kanca) {
        foreach ([1, 10, 20] as $oncelik) {
            remove_action($kanca, 'wp_enqueue_global_styles', $oncelik);
        }
    }
    remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles_css_custom_properties');
    remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
}, 1);

// jQuery <head>'de render'ı engelliyordu (~150 ms). Footer'a alınır; WordPress bağımlılık
// sırasını korur, yani jQuery isteyen betikler hâlâ ondan SONRA çalışır. Başlıkta jQuery'yi
// doğrudan çağıran satır içi betik varsa bu satır kaldırılmalı (bkz. scripts/saglik-taramasi).
add_action('wp_enqueue_scripts', function () {
    if (is_admin()) {
        return;
    }
    $w = wp_scripts();
    foreach (['jquery', 'jquery-core', 'jquery-migrate'] as $h) {
        if (isset($w->registered[$h])) {
            $w->add_data($h, 'group', 1);
        }
    }
}, 1);

/* ------------------------------------------------------------------
   13. GOOGLE ADSENSE HAZIRLIĞI (2026-10-05)
   - hesap kimliği kaydedilince: <meta name="google-adsense-account"> (hesap doğrulaması) ve
     /ads.txt; reklam komut dosyası YALNIZCA çerez bildirimi kabul edilince yüklenir (bkz. §7)
   - kimlik gizli değildir; yönetici eylemiyle kaydedilir (kampanya/v1/maintenance set_adsense_id)
   ------------------------------------------------------------------ */
function idk_adsense_id() {
    $id = (string) get_option('idk_adsense_id', '');
    return preg_match('/^ca-pub-\d{16}$/', $id) ? $id : '';
}

add_action('wp_head', function () {
    if ($id = idk_adsense_id()) {
        printf('<meta name="google-adsense-account" content="%s">' . "\n", esc_attr($id));
    }
}, 2);

add_action('init', function () {
    $yol = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    if ($yol !== 'ads.txt' || !($id = idk_adsense_id())) {
        return;
    }
    status_header(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'google.com, ' . substr($id, 3) . ", DIRECT, f08c47fec0942fa0\n";
    exit;
}, 1);

/* Sağlıkla ilgili yazıların sonuna tıbbi-tavsiye uyarısı: "güvenilmez sağlık iddiası" politikası */
function idk_saglik_yazilari() {
    return [3389, 3407, 3386, 3548, 3545, 3586, 3360, 3391, 3544, 3541, 3543, 3554];
}

add_filter('the_content', function ($c) {
    if (is_admin() || !is_singular('post') || !in_the_loop() || !is_main_query()) {
        return $c;
    }
    $id = get_the_ID();
    if (!in_array($id, idk_saglik_yazilari(), true) && !in_array('saglik', wp_get_post_categories($id, ['fields' => 'slugs']), true)) {
        return $c;
    }
    return $c . '<p class="idk-uyari"><em>Bu yazı genel bilgilendirme amaçlıdır; tıbbi tavsiye, teşhis ya da tedavi yerine geçmez. '
        . 'Sağlığınızla ilgili kararlar için bir sağlık uzmanına danışın.</em></p>';
}, 15);

/* ------------------------------------------------------------------
   14. TEK SİTEMAP: https://incedetay.com/sitemap.xml
   WordPress'in varsayılanı bir dizin dosyası + 3 alt dosya + /sitemap.xml'den 301 idi.
   Search Console'da tek ve doğrudan bir adres istendi: tüm adresler tek düz dosyada, yönlendirme yok.
   Kapsam eski dizinle birebir aynı: yayındaki yazı/sayfalar (noindex olanlar hariç) + dolu kategoriler.
   ------------------------------------------------------------------ */
add_filter('wp_sitemaps_enabled', '__return_false');

function idk_sitemap_xml() {
    $atla    = idk_noindex_idler();
    $yazilar = get_posts([
        'numberposts'  => -1,
        'post_status'  => 'publish',
        'post_type'    => ['post', 'page'],
        'post__not_in' => $atla,
        'orderby'      => 'modified',
        'order'        => 'DESC',
    ]);
    $ana    = (int) get_option('page_on_front');
    $blog   = (int) get_option('page_for_posts');
    $ogeler = [];
    $enYeni = $yazilar ? get_post_modified_time('c', true, $yazilar[0]) : gmdate('c');
    foreach ($yazilar as $p) {
        $ID  = (int) $p->ID;
        $url = ($ana && $ID === $ana) ? home_url('/') : get_permalink($p);
        // ana sayfa ve yazı listesi sayfası, sitedeki en son değişiklik kadar günceldir
        $ogeler[$url] = ($ID === $ana || $ID === $blog) ? $enYeni : get_post_modified_time('c', true, $p);
    }
    foreach (get_categories(['hide_empty' => true]) as $c) {
        $son = get_posts(['numberposts' => 1, 'category' => $c->term_id, 'post_status' => 'publish',
                          'post__not_in' => $atla, 'orderby' => 'modified', 'order' => 'DESC']);
        if ($son) {
            $ogeler[get_category_link($c)] = get_post_modified_time('c', true, $son[0]);
        }
    }
    $x = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($ogeler as $u => $m) {
        $x .= '<url><loc>' . htmlspecialchars($u, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc><lastmod>' . htmlspecialchars($m, ENT_XML1, 'UTF-8') . '</lastmod></url>' . "\n";
    }
    return $x . '</urlset>' . "\n";
}

add_action('init', function () {
    $yol = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    // eski WordPress sitemap adresleri (wp-sitemap.xml, wp-sitemap-posts-post-1.xml ...) kalıcı olarak tek dosyaya
    if (preg_match('~^wp-sitemap[\w.-]*\.(xml|xsl)$~', $yol)) {
        wp_redirect(home_url('/sitemap.xml'), 301);
        exit;
    }
    if ($yol !== 'sitemap.xml') {
        return;
    }
    $xml = get_transient('idk_sitemap_v1');
    if ($xml === false) {
        $xml = idk_sitemap_xml();
        set_transient('idk_sitemap_v1', $xml, HOUR_IN_SECONDS);
    }
    status_header(200);
    header('Content-Type: application/xml; charset=UTF-8');
    // X-Robots-Tag: noindex BİLEREK yok: URL Denetimi'nde "URL is not available to Google" uyarısı veriyordu
    echo $xml;
    exit;
}, 1);

/* ------------------------------------------------------------------
   15. SITE KIT — yalnızca YÖNETİM PANELİ için; ön yüze etiket basmasın
   Site Kit, bağlandığında kendi gtag/AdSense kodunu çerez onayından ÖNCE yükler. Burada izleme ve
   reklam etiketleri her zaman engellenir; tek kapı, çerez bildirimi (§7, §13). Filtre adı Site Kit
   1.189.0 kaynağından: includes/Core/Modules/Tags/Module_Web_Tag.php.
   ------------------------------------------------------------------ */
foreach (['analytics-4', 'adsense', 'ads', 'tagmanager'] as $idk_modul) {
    add_filter("googlesitekit_{$idk_modul}_tag_blocked", '__return_true');
}




// Çerez kararı verilmişse banner'ı ilk boyamadan ÖNCE gizle (banner artık HTML'de görünür gelir).
add_action('wp_head', function () {
    if (is_admin() || is_feed() || is_embed()) { return; }
    echo "<script>try{var v=localStorage.getItem('idk_cerez');if(!v){var m=document.cookie.match(/(?:^|; )idk_cerez=([^;]+)/);v=m&&m[1]}if(v==='kabul'||v==='reddet')document.documentElement.classList.add('idk-cerez-tamam')}catch(e){}</script>\n";
}, 1);
