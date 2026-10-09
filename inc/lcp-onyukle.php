<?php
/**
 * LCP görselini <head> başında önyükle.
 *
 * Kritik CSS (inc/kritik-css.php) head'i ~70 KB büyüttü; ilk görselin <img> etiketi bunun arkasında kaldığı için
 * tarayıcı onu geç keşfediyor (Lighthouse "Load Delay" ana sayfada 1,7–2 sn). Preload, görsel isteğini ilk baytlarla
 * başlatır. href/imagesrcset/imagesizes, sayfadaki <img> ile AYNI olmalı; aksi halde tarayıcı iki dosya indirir.
 *   - Ana sayfa: manşet kartı görseli (template-homepage.php, idk_ana_kart 'manset' = 'card-featured').
 *   - Makale: gövdedeki ilk <img> (yazi-duzeni.php ile aynı srcset/sizes).
 */
if (!defined('ABSPATH')) {
    exit;
}

function idk_lcp_webp($url)
{
    return function_exists('idk_webp_url') ? idk_webp_url($url) : $url;
}

add_action('wp_head', function () {
    if (is_admin() || is_feed() || is_embed() || is_customize_preview()) {
        return;
    }
    $href = $srcset = $sizes = '';

    if (is_front_page()) {
        $yazilar = get_posts(['posts_per_page' => 1, 'post_status' => 'publish', 'ignore_sticky_posts' => true, 'no_found_rows' => true]);
        $id = $yazilar ? get_post_thumbnail_id($yazilar[0]) : 0;
        if ($id) {
            // idk_ana_kart ile aynı çağrı: get_the_post_thumbnail(..., 'card-featured') -> src + (varsa) srcset/sizes
            $img = wp_get_attachment_image($id, 'card-featured', false, ['loading' => 'eager', 'fetchpriority' => 'high']);   // template ilk kart için aynı öznitelikleri verir (sizes=auto farkı olmasın)
            if (preg_match('/\ssrc="([^"]+)"/', $img, $m)) {
                $href = idk_lcp_webp(html_entity_decode($m[1]));
            }
            if (preg_match('/\ssrcset="([^"]+)"/', $img, $m)) {
                $srcset = implode(', ', array_map(function ($p) {
                    $p = preg_split('/\s+/', trim($p), 2);
                    return idk_lcp_webp($p[0]) . (isset($p[1]) ? ' ' . $p[1] : '');
                }, explode(',', html_entity_decode($m[1]))));
            }
            if (preg_match('/\ssizes="([^"]+)"/', $img, $m)) {
                $sizes = html_entity_decode($m[1]);
            }
        }
    } elseif (is_singular('post')) {
        $icerik = (string) get_post_field('post_content', get_queried_object_id());
        if (preg_match('#<img\b[^>]*>#i', $icerik, $t) && preg_match('/\ssrc=["\']([^"\']+)["\']/i', $t[0], $m)) {
            $href = idk_lcp_webp($m[1]);
            $id   = attachment_url_to_postid(preg_replace('/\.webp$/i', '', $m[1]));
            $meta = $id ? wp_get_attachment_metadata($id) : null;
            if ($meta && function_exists('idk_icerik_gorsel_srcset')) {
                $srcset = idk_icerik_gorsel_srcset($id, $meta);
                $sizes  = $srcset !== '' ? '(max-width: 760px) 100vw, 740px' : '';
            }
        }
    }

    if ($href === '') {
        return;
    }
    printf(
        '<link rel="preload" as="image" href="%s"%s%s fetchpriority="high">' . "\n",
        esc_url($href),
        $srcset !== '' ? ' imagesrcset="' . esc_attr($srcset) . '"' : '',
        ($srcset !== '' && $sizes !== '') ? ' imagesizes="' . esc_attr($sizes) . '"' : ''
    );
}, 1);
