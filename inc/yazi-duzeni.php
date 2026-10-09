<?php
/**
 * Makale düzeni (2026-10-09, Tasarım B): içerik iki sütuna oturur.
 *   sol  — sabit "Bu yazıda" menüsü (h2 başlıkları ve numaralı ürün başlıkları)
 *   sağ  — okuma sütunu (740 px)
 * Boş yan marjlar menü ile dolar; telefonda menü katlanır (details).
 * Başlıklara kalıcı id eklenir (menü bağlantıları ve dış bağlantılar için).
 * Sadece tekil yazı, ana sorgu, en az 3 menü öğesi olduğunda çalışır.
 */
if (!defined('ABSPATH')) { exit; }

function idk_baslik_id($metin, &$kullanilan) {
    $t = remove_accents(strtr(wp_strip_all_tags($metin), ['İ' => 'i', 'I' => 'i', 'ı' => 'i']));
    $t = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($t)), '-');
    $t = $t !== '' ? substr($t, 0, 48) : 'bolum';
    $id = $t; $n = 2;
    while (isset($kullanilan[$id])) { $id = $t . '-' . $n++; }
    $kullanilan[$id] = true;
    return $id;
}

add_filter('the_content', function ($icerik) {
    if (!is_singular('post') || !in_the_loop() || !is_main_query()) { return $icerik; }
    $kullanilan = [];
    $ogeler = [];
    $icerik = preg_replace_callback('#<(h2|h3)([^>]*)>(.*?)</\1>#is', function ($m) use (&$kullanilan, &$ogeler) {
        $etiket = strtolower($m[1]);
        $metin  = trim(wp_strip_all_tags($m[3]));
        $urun   = $etiket === 'h3' && preg_match('/^\d+\.\s/u', $metin);
        if ($etiket === 'h3' && !$urun) { return $m[0]; }
        if ($metin === '' || stripos($metin, 'İlgili Rehberler') === 0 || stripos($metin, 'Bunun gibi rehberler') === 0) { return $m[0]; }
        if (preg_match('/\sid=["\']([^"\']+)["\']/', $m[2], $idm)) {
            $id = $idm[1]; $kullanilan[$id] = true; $attrs = $m[2];
        } else {
            $id = idk_baslik_id($metin, $kullanilan);
            $attrs = $m[2] . ' id="' . esc_attr($id) . '"';
        }
        $ogeler[] = ['id' => $id, 'metin' => $metin, 'urun' => $urun];
        return '<' . $etiket . $attrs . '>' . $m[3] . '</' . $etiket . '>';
    }, $icerik);

    if (count($ogeler) < 3) { return $icerik; }

    $li = '';
    foreach ($ogeler as $o) {
        $ad = $o['urun'] ? preg_replace('/^\d+\.\s*/u', '', $o['metin']) : $o['metin'];
        $li .= sprintf('<li class="%s"><a href="#%s">%s</a></li>', $o['urun'] ? 'idk-ic__urun' : 'idk-ic__bolum', esc_attr($o['id']), esc_html(mb_strimwidth($ad, 0, 56, '…')));
    }
    $menu = '<aside class="idk-icindekiler" aria-label="Bu yazıda"><details><summary>Bu yazıda</summary><ol>' . $li . '</ol></details></aside>'
          . '<script>(function(){var d=document.querySelector(".idk-icindekiler details");if(d&&matchMedia("(min-width:1000px)").matches)d.open=true})()</script>';
    return '<div class="idk-yazi">' . $menu . '<div class="idk-yazi__govde">' . $icerik . '</div></div>';
}, 99);

/**
 * Üst menüde açık sayfayı işaretle (aria-current): Blocksy özel bağlantılı menü öğelerinde
 * current-menu-item sınıfını vermiyor; menü altı çizgisi ve ekran okuyucu için gerekli.
 */
add_filter('nav_menu_link_attributes', function ($atts, $item) {
    $yol = untrailingslashit(wp_parse_url($item->url, PHP_URL_PATH) ?: '/');
    $su  = untrailingslashit(wp_parse_url(home_url(add_query_arg([])), PHP_URL_PATH) ?: '/');
    if ($yol === $su || (is_singular('post') === false && is_home() && $yol === untrailingslashit(wp_parse_url(get_permalink(get_option('page_for_posts')), PHP_URL_PATH)))) {
        $atts['aria-current'] = 'page';
    }
    return $atts;
}, 10, 2);
