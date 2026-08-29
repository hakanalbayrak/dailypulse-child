<?php
/**
 * Makale içeriğinin sonuna otomatik eklenen iki blok:
 *
 *   1) ÜRÜN İNDEKSİ — karşılaştırılan ürünlerin "1. Ürün Adı / 2. ... "
 *      biçiminde numaralı link listesi.
 *   2) İLGİLİ MAKALELER — kategori/etiket ortaklığına göre bulunan,
 *      görsel + başlık + kısa özet kartları halinde 3 yazı.
 *
 * NEDEN TEMADA (İÇERİKTE DEĞİL):
 * 47 makalenin gövdesine bu iki bloğu tek tek elle eklemek yerine,
 * the_content filtresiyle görüntüleme anında üretiliyor:
 *   - Bir ürünün affiliate linki değişirse indeks otomatik günceli izler
 *     (post_content'e hiçbir şey yazılmıyor — kaynak veri hep h3
 *     başlıklarındaki gerçek linkler).
 *   - Taslaklar yayınlandıkça "ilgili makaleler" havuzu kendiliğinden
 *     büyür; bugün çoğu makale taslak olduğu için bazı yazılarda İlgili
 *     Makaleler bölümü az sayıda (hatta sıfır) kart gösterebilir — bu bir
 *     hata değil, henüz yayınlanmamış içeriğin doğal sonucu.
 *
 * Sadece 'post' tekil görünümünde, gerçek WordPress döngüsü içinde çalışır
 * (in_the_loop + is_main_query) — REST API'nin content.rendered alanı veya
 * özet (excerpt) üretimi gibi döngü dışı çağrılarda hiçbir şey eklemez.
 *
 * Saf yardımcı (kampanya_extract_product_links) WordPress'e bağımlı değil.
 */

if (!defined('KAMPANYA_CONTENT_EXTRAS')) {
    define('KAMPANYA_CONTENT_EXTRAS', true);
}

/* ------------------------------------------------------------------
   SAF YARDIMCI — WordPress'e bağımlı değil, test edilebilir
   ------------------------------------------------------------------ */

/**
 * Render edilmiş (do_blocks sonrası) makale HTML'inden numaralı ürün
 * başlıklarını çıkarır: <h3>1. <a href="...">Ürün Adı</a> — alt başlık</h3>
 *
 * Sıra korunur, aynı URL bir daha eklenmez.
 */
function kampanya_extract_product_links($rendered_html) {
    $out  = [];
    $seen = [];
    $ok = preg_match_all(
        '~<h3[^>]*>\s*\d+\.\s*<a[^>]+href="([^"]+)"[^>]*>(.*?)</a>~s',
        (string) $rendered_html,
        $m,
        PREG_SET_ORDER
    );
    if (!$ok) {
        return $out;
    }
    foreach ($m as $row) {
        $url  = html_entity_decode($row[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $name = trim(html_entity_decode(strip_tags($row[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($name === '' || $url === '' || isset($seen[$url])) {
            continue;
        }
        $seen[$url] = true;
        $out[] = ['name' => $name, 'url' => $url];
    }
    return $out;
}

/* ------------------------------------------------------------------
   WORDPRESS TARAFI
   ------------------------------------------------------------------ */

function kampanya_render_product_index($links) {
    if (count($links) < 2) {
        return '';
    }
    $items = '';
    foreach ($links as $l) {
        $items .= sprintf(
            '<li><a href="%s" rel="nofollow sponsored noopener noreferrer" target="_blank">%s</a></li>',
            esc_url($l['url']),
            esc_html($l['name'])
        );
    }
    return '<div class="k-product-index"><ol>' . $items . '</ol></div>';
}

/**
 * Aynı kategoriden, yoksa aynı etiketten, o da yetmezse en yeni yazılardan
 * $limit kadar ilişkili yazı döndürür. Sadece yayınlanmış (publish) yazılar
 * arasından seçer.
 */
function kampanya_related_posts_query($post_id, $limit = 3) {
    $cats = wp_get_post_categories($post_id);
    $tags = wp_get_post_tags($post_id, ['fields' => 'ids']);

    $collected = [];
    $exclude   = [$post_id];

    $base_args = [
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'orderby'             => 'date',
        'order'               => 'DESC',
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
    ];

    if (!empty($cats) && count($collected) < $limit) {
        $q = new WP_Query(array_merge($base_args, [
            'category__in'   => $cats,
            'post__not_in'   => $exclude,
            'posts_per_page' => $limit - count($collected),
        ]));
        foreach ($q->posts as $p) {
            $collected[$p->ID] = $p;
            $exclude[]         = $p->ID;
        }
    }

    if (!empty($tags) && count($collected) < $limit) {
        $q = new WP_Query(array_merge($base_args, [
            'tag__in'        => $tags,
            'post__not_in'   => $exclude,
            'posts_per_page' => $limit - count($collected),
        ]));
        foreach ($q->posts as $p) {
            $collected[$p->ID] = $p;
            $exclude[]         = $p->ID;
        }
    }

    if (count($collected) < $limit) {
        $q = new WP_Query(array_merge($base_args, [
            'post__not_in'   => $exclude,
            'posts_per_page' => $limit - count($collected),
        ]));
        foreach ($q->posts as $p) {
            $collected[$p->ID] = $p;
            $exclude[]         = $p->ID;
        }
    }

    return array_values($collected);
}

/**
 * Manuel özet üretir — get_the_excerpt() KASITLI OLARAK kullanılmıyor:
 * özeti manuel girilmemiş yazılarda WordPress çekirdeği (wp_trim_excerpt)
 * özeti üretmeden önce içeriği 'the_content' filtresinden geçiriyor. Biz
 * zaten 'the_content' filtresinin içindeyiz (kampanya_content_extras),
 * bu yüzden get_the_excerpt() her ilişkili yazı için filtreyi yeniden
 * tetikler — is_singular/in_the_loop/is_main_query hâlâ true olduğundan
 * guard bunu durduramaz ve sonsuz özyineleme (500 hatası) oluşur.
 * Basit, filtre zincirine hiç girmeyen bir kırpma yeterli ve daha isabetli.
 */
function kampanya_plain_excerpt($post, $words = 20) {
    if (!empty($post->post_excerpt)) {
        return wp_trim_words(wp_strip_all_tags($post->post_excerpt), $words);
    }
    $text = strip_shortcodes((string) $post->post_content);
    $text = preg_replace('~<!--.*?-->~s', ' ', $text);
    $text = wp_strip_all_tags($text);
    return wp_trim_words($text, $words);
}

function kampanya_render_related_posts($posts) {
    if (empty($posts)) {
        return '';
    }
    $cards = '';
    foreach ($posts as $p) {
        $title   = get_the_title($p);
        $excerpt = kampanya_plain_excerpt($p, 20);
        $img     = has_post_thumbnail($p)
            ? get_the_post_thumbnail($p, 'card-regular', ['alt' => esc_attr($title)])
            : '';

        $cards .= '<a class="k-related-card" href="' . esc_url(get_permalink($p)) . '">';
        if ($img) {
            $cards .= '<div class="k-related-card__img-wrap">' . $img . '</div>';
        }
        $cards .= '<div class="k-related-card__body">';
        $cards .= '<h3 class="k-related-card__title">' . esc_html($title) . '</h3>';
        if ($excerpt !== '') {
            $cards .= '<p class="k-related-card__excerpt">' . esc_html($excerpt) . '</p>';
        }
        $cards .= '</div></a>';
    }
    return '<div class="k-related"><p class="k-related__heading">İlginizi Çekebilir</p>'
        . '<div class="k-related-grid">' . $cards . '</div></div>';
}

add_filter('the_content', 'kampanya_content_extras', 20);
function kampanya_content_extras($content) {
    if (is_admin() || !is_singular('post') || !in_the_loop() || !is_main_query()) {
        return $content;
    }
    $post_id = get_the_ID();
    if (!$post_id) {
        return $content;
    }

    // Yeniden giriş koruması: kampanya_plain_excerpt() bilerek the_content
    // filtresine hiç girmiyor (bkz. yorum), ama ileride bu fonksiyon
    // değişirse ya da başka bir kod yolu 'the_content'i içeriden tekrar
    // tetiklerse burada sonsuz özyinelemeyi kesin olarak durduruyoruz.
    static $running = false;
    if ($running) {
        return $content;
    }
    $running = true;

    $extra  = kampanya_render_product_index(kampanya_extract_product_links($content));
    $extra .= kampanya_render_related_posts(kampanya_related_posts_query($post_id, 3));

    $running = false;

    if ($extra === '') {
        return $content;
    }
    return $content . "\n" . $extra;
}
