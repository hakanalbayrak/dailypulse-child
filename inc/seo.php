<?php
/**
 * SEO head çıktısı — meta açıklama, sosyal medya kartları ve schema (JSON-LD).
 *
 * NEDEN TEMADA:
 * Rank Math sitede kurulu olmasına rağmen ön yüzde hiçbir şey basmıyordu
 * (wp_head'e tek bir kanca bile eklemiyor, REST namespace'i yok, kendi
 * sitemap'i 404). Kurulum sihirbazı hiç tamamlanmadığı için eklenti uykuda
 * kalmıştı. Sitenin ihtiyacı olan üç şey (açıklama, sosyal kart, schema)
 * ~200 satır ile burada karşılanıyor; 60 modüllü bir eklentiye gerek yok.
 *
 * WordPress çekirdeği zaten şunları veriyor, burada TEKRARLAMIYORUZ:
 *   - <title>            (_wp_render_title_tag)
 *   - rel="canonical"    (rel_canonical)
 *   - XML sitemap        (/wp-sitemap.xml)
 *
 * KİLİTLENME YOK: açıklamalar Rank Math'in standart post meta anahtarında
 * (rank_math_description) saklanıyor. Eklenti bir gün düzgün çalışırsa aynı
 * değerleri kendisi okur; o zaman KAMPANYA_SEO_HEAD sabitini false yapmak
 * yeterli.
 *
 * Saf fonksiyonlar (kampanya_seo_*_pure) WordPress olmadan test edilebilir;
 * tests/seo-test.php bunları gerçek makale HTML'iyle doğruluyor.
 */

if (!defined('KAMPANYA_SEO_HEAD')) {
    define('KAMPANYA_SEO_HEAD', true);
}

/* ------------------------------------------------------------------
   SAF YARDIMCILAR — WordPress'e bağımlı değil, test edilebilir
   ------------------------------------------------------------------ */

/**
 * HTML içerikten düz metin çıkarır.
 */
function kampanya_seo_text_pure($html) {
    $t = preg_replace('~<!--.*?-->~s', ' ', (string) $html);   // Gutenberg blok yorumları
    $t = preg_replace('~<(script|style)\b.*?</\1>~is', ' ', $t);
    $t = preg_replace('~<[^>]+>~', ' ', $t);
    $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $t = preg_replace('~\s+~u', ' ', $t);
    return trim($t);
}

/**
 * Belirtilen uzunluğu aşmayan, kelime sınırında kesilmiş metin.
 */
function kampanya_seo_trim_pure($text, $max = 158) {
    $text = trim((string) $text);
    if ($text === '' || mb_strlen($text, 'UTF-8') <= $max) {
        return $text;
    }
    $cut = mb_substr($text, 0, $max - 1, 'UTF-8');
    $sp  = mb_strrpos($cut, ' ', 0, 'UTF-8');
    if ($sp !== false && $sp > 40) {
        $cut = mb_substr($cut, 0, $sp, 'UTF-8');
    }
    return rtrim($cut, " ,;:—-") . '…';
}

/**
 * Meta açıklamayı belirler: önce kayıtlı değer, yoksa içerikten üret.
 */
function kampanya_seo_description_pure($stored, $content, $max = 158) {
    $stored = trim((string) $stored);
    if ($stored !== '') {
        return kampanya_seo_trim_pure($stored, $max);
    }
    return kampanya_seo_trim_pure(kampanya_seo_text_pure($content), $max);
}

/**
 * "Sıkça Sorulan Sorular" bölümündeki soru/cevap çiftlerini çıkarır.
 *
 * Yapı: <h2>Sıkça Sorulan Sorular</h2> ardından (h3 soru + p cevap)*
 * Bir sonraki <h2> geldiğinde bölüm biter.
 */
function kampanya_seo_faq_pure($content, $limit = 10) {
    $html = preg_replace('~<!--.*?-->~s', '', (string) $content);
    // Şablonlar iki varyant kullanıyor: "Sıkça Sorulan Sorular" ve
    // "Sık Sorulan Sorular". İkisini de yakala.
    if (!preg_match('~<h2[^>]*>\s*S[ıi]k(?:[çc]a)? Sorulan Sorular\s*</h2>~iu', $html, $m, PREG_OFFSET_CAPTURE)) {
        return [];
    }
    $start   = $m[0][1] + strlen($m[0][0]);
    $rest    = substr($html, $start);
    $nextH2  = preg_match('~<h2[^>]*>~i', $rest, $m2, PREG_OFFSET_CAPTURE) ? $m2[0][1] : strlen($rest);
    $section = substr($rest, 0, $nextH2);

    if (!preg_match_all('~<h3[^>]*>(.*?)</h3>(.*?)(?=<h3|$)~is', $section, $mm, PREG_SET_ORDER)) {
        return [];
    }
    $out = [];
    foreach ($mm as $pair) {
        $q = kampanya_seo_text_pure($pair[1]);
        $a = '';
        if (preg_match_all('~<p[^>]*>(.*?)</p>~is', $pair[2], $ps)) {
            $a = kampanya_seo_text_pure(implode(' ', $ps[1]));
        }
        if ($q !== '' && $a !== '') {
            $out[] = ['q' => $q, 'a' => $a];
        }
        if (count($out) >= $limit) {
            break;
        }
    }
    return $out;
}

/* ------------------------------------------------------------------
   WORDPRESS TARAFI
   ------------------------------------------------------------------ */

function kampanya_seo_should_run() {
    if (!KAMPANYA_SEO_HEAD || is_admin() || is_feed() || is_embed()) {
        return false;
    }
    /*
     * Başka bir SEO eklentisi devreye girerse etiketler ikiye katlanmasın diye
     * kapatma yolu: wp-config.php'de define('KAMPANYA_SEO_HEAD', false) ya da
     * add_filter('kampanya_seo_head', '__return_false').
     * Not: Rank Math'i otomatik algılamaya çalışmıyoruz — eklentinin iç
     * nesnelerini yoklamak kırılgan; açık bir anahtar daha güvenilir.
     */
    return (bool) apply_filters('kampanya_seo_head', true);
}

function kampanya_seo_current_description() {
    if (is_singular()) {
        $id     = get_queried_object_id();
        $stored = get_post_meta($id, 'rank_math_description', true);
        $post   = get_post($id);
        return kampanya_seo_description_pure($stored, $post ? $post->post_content : '');
    }
    if (is_home() || is_front_page()) {
        return kampanya_seo_trim_pure(get_bloginfo('description'));
    }
    if (is_category() || is_tag() || is_tax()) {
        $d = term_description();
        return kampanya_seo_trim_pure(kampanya_seo_text_pure($d));
    }
    return kampanya_seo_trim_pure(get_bloginfo('description'));
}

function kampanya_seo_current_image() {
    if (is_singular() && has_post_thumbnail()) {
        $src = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full');
        if ($src) {
            return ['url' => $src[0], 'w' => $src[1], 'h' => $src[2]];
        }
    }
    return null;
}

/**
 * meta description + Open Graph + Twitter kartları.
 */
add_action('wp_head', 'kampanya_seo_meta_tags', 2);
function kampanya_seo_meta_tags() {
    if (!kampanya_seo_should_run()) {
        return;
    }
    $desc = kampanya_seo_current_description();
    $title = is_singular() ? get_the_title(get_queried_object_id()) : get_bloginfo('name');
    $url   = is_singular() ? get_permalink(get_queried_object_id()) : home_url('/');
    $img   = kampanya_seo_current_image();

    echo "\n<!-- kampanya seo -->\n";
    if ($desc !== '') {
        printf('<meta name="description" content="%s" />' . "\n", esc_attr($desc));
    }
    printf('<meta property="og:type" content="%s" />' . "\n", is_singular('post') ? 'article' : 'website');
    printf('<meta property="og:title" content="%s" />' . "\n", esc_attr($title));
    if ($desc !== '') {
        printf('<meta property="og:description" content="%s" />' . "\n", esc_attr($desc));
    }
    printf('<meta property="og:url" content="%s" />' . "\n", esc_url($url));
    printf('<meta property="og:site_name" content="%s" />' . "\n", esc_attr(get_bloginfo('name')));
    echo '<meta property="og:locale" content="tr_TR" />' . "\n";
    if ($img) {
        printf('<meta property="og:image" content="%s" />' . "\n", esc_url($img['url']));
        printf('<meta property="og:image:width" content="%d" />' . "\n", (int) $img['w']);
        printf('<meta property="og:image:height" content="%d" />' . "\n", (int) $img['h']);
    }
    printf('<meta name="twitter:card" content="%s" />' . "\n", $img ? 'summary_large_image' : 'summary');
    printf('<meta name="twitter:title" content="%s" />' . "\n", esc_attr($title));
    if ($desc !== '') {
        printf('<meta name="twitter:description" content="%s" />' . "\n", esc_attr($desc));
    }
    if ($img) {
        printf('<meta name="twitter:image" content="%s" />' . "\n", esc_url($img['url']));
    }
}

/**
 * JSON-LD: yazılarda BlogPosting + (varsa) FAQPage, ana sayfada WebSite.
 */
add_action('wp_head', 'kampanya_seo_schema', 3);
function kampanya_seo_schema() {
    if (!kampanya_seo_should_run()) {
        return;
    }
    $graph = [];

    if (is_singular('post')) {
        $id   = get_queried_object_id();
        $desc = kampanya_seo_current_description();
        $img  = kampanya_seo_current_image();
        $post = get_post($id);

        $article = [
            '@type'            => 'BlogPosting',
            '@id'              => get_permalink($id) . '#article',
            'mainEntityOfPage' => get_permalink($id),
            'headline'         => wp_strip_all_tags(get_the_title($id)),
            'description'      => $desc,
            'datePublished'    => get_the_date('c', $id),
            'dateModified'     => get_the_modified_date('c', $id),
            'inLanguage'       => 'tr-TR',
            'author'           => [
                '@type' => 'Organization',
                'name'  => get_bloginfo('name'),
                'url'   => home_url('/'),
            ],
            'publisher'        => [
                '@type' => 'Organization',
                'name'  => get_bloginfo('name'),
                'url'   => home_url('/'),
            ],
        ];
        if ($img) {
            $article['image'] = [
                '@type'  => 'ImageObject',
                'url'    => $img['url'],
                'width'  => (int) $img['w'],
                'height' => (int) $img['h'],
            ];
        }
        $graph[] = $article;

        $faq = kampanya_seo_faq_pure($post ? $post->post_content : '');
        if (count($faq) >= 2) {
            $items = [];
            foreach ($faq as $f) {
                $items[] = [
                    '@type'          => 'Question',
                    'name'           => $f['q'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
                ];
            }
            $graph[] = [
                '@type'      => 'FAQPage',
                '@id'        => get_permalink($id) . '#faq',
                'mainEntity' => $items,
            ];
        }
    } elseif (is_front_page() || is_home()) {
        $graph[] = [
            '@type'       => 'WebSite',
            '@id'         => home_url('/') . '#website',
            'url'         => home_url('/'),
            'name'        => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'inLanguage'  => 'tr-TR',
        ];
    }

    if (!$graph) {
        return;
    }
    $json = wp_json_encode(
        ['@context' => 'https://schema.org', '@graph' => $graph],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    echo '<script type="application/ld+json">' . $json . '</script>' . "\n";
}
