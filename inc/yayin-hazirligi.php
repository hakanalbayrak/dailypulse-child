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
 */
function idk_noindex_idler() {
    return [82, 83, 84, 85, 3364, 3365];
}

/* ------------------------------------------------------------------
   1. BAŞLIKLAR — <title> 60 karakteri aşmasın
   ------------------------------------------------------------------ */
add_filter('document_title_parts', function ($p) {
    if (is_front_page()) {
        return ['title' => 'ince detay', 'tagline' => 'Türkiye’nin detay bülteni'];
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
    if (is_tag() || is_search() || is_404() || $woo || $ozel) {
        $r['noindex'] = true;
        $r['follow']  = true;
        unset($r['max-image-preview']);
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
        "Allow: /wp-admin/admin-ajax.php\nDisallow: /go/",
        $out
    );
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

    $sayfalar = ['hakkimizda', 'sikca-sorulan-sorular', 'affiliate-disclosure', 'gizlilik-politikasi', 'iletisim'];
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
    $out .= "## Sitemap\n- [wp-sitemap.xml]($url/wp-sitemap.xml)\n";
    return $out;
}

add_action('init', function () {
    $yol = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    if ($yol !== 'llms.txt') {
        return;
    }
    $metin = get_transient('idk_llms_v1');
    if ($metin === false) {
        $metin = idk_llms_uret();
        set_transient('idk_llms_v1', $metin, 6 * HOUR_IN_SECONDS);
    }
    status_header(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo $metin;
    exit;
}, 1);

// Yeni yazı/sayfa yayınlanınca llms.txt tazelensin
add_action('save_post', function () {
    delete_transient('idk_llms_v1');
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
    ?>
<div id="idk-cerez" class="idk-cerez" role="region" aria-label="Çerez tercihi" hidden>
  <p class="idk-cerez__metin">Bu site, çalışması için gerekli çerezleri kullanır. İzin verirseniz ziyaretçi sayısını anlamak için analitik çerezleri de kullanırız. Ayrıntılar: <a href="<?php echo esc_url(home_url('/cerez-politikasi/')); ?>">Çerez Politikası</a></p>
  <div class="idk-cerez__dugmeler">
    <button type="button" class="idk-cerez__btn" data-idk-cerez="reddet">Reddet</button>
    <button type="button" class="idk-cerez__btn idk-cerez__btn--birincil" data-idk-cerez="kabul">Kabul et</button>
  </div>
</div>
<script>
(function () {
  var GA = <?php echo wp_json_encode($ga); ?>, ANAHTAR = 'idk_cerez', kutu = document.getElementById('idk-cerez');
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
  // Analitik YALNIZCA "Kabul et"ten sonra yüklenir.
  function analitik() {
    if (!GA || window.idkGaYuklendi) { return; }
    window.idkGaYuklendi = true;
    var s = document.createElement('script');
    s.async = true; s.src = 'https://www.googletagmanager.com/gtag/js?id=' + GA;
    document.head.appendChild(s);
    window.dataLayer = window.dataLayer || [];
    function gtag() { window.dataLayer.push(arguments); }
    gtag('js', new Date());
    gtag('config', GA, { anonymize_ip: true });
  }
  var secim = oku();
  if (secim === 'kabul') { analitik(); } else if (secim !== 'reddet') { kutu.hidden = false; }
  kutu.addEventListener('click', function (e) {
    var b = e.target.closest('[data-idk-cerez]');
    if (!b) { return; }
    var v = b.getAttribute('data-idk-cerez');
    yaz(v); kutu.hidden = true;
    if (v === 'kabul') { analitik(); }
  });
  // Çerez politikasındaki "tercihleri değiştir" bağlantısı
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-idk-cerez-ac]')) { e.preventDefault(); kutu.hidden = false; }
  });
})();
</script>
    <?php
}, 30);
