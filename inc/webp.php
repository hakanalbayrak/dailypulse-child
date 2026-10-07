<?php
/**
 * WebP: yüklenen JPG/PNG dosyalarının yanına <dosya>.webp kopyası üretir ve ön yüzde,
 * kopya VARSA, görsel URL'sini ona çevirir (Lighthouse "modern görsel biçimleri").
 * Orijinal dosyalar silinmez; kopya yoksa URL aynen kalır. OG/schema görselleri değişmez
 * (filtreler wp_body_open'da, yani <head> çıktısından sonra eklenir).
 */
if (!defined('ABSPATH')) {
    exit;
}

function idk_webp_url($url)
{
    static $onbellek = [];
    if (isset($onbellek[$url])) {
        return $onbellek[$url];
    }
    $up = wp_get_upload_dir();
    $sonuc = $url;
    if (preg_match('/\.(jpe?g|png)$/i', $url) && strpos($url, $up['baseurl']) === 0) {
        $dosya = $up['basedir'] . substr($url, strlen($up['baseurl']));
        if (is_file($dosya . '.webp')) {
            $sonuc = $url . '.webp';
        }
    }
    return $onbellek[$url] = $sonuc;
}

/** Tek dosya için .webp üret. true = üretildi/zaten var. */
function idk_webp_uret($dosya, $zorla = false)
{
    if (!is_file($dosya) || !preg_match('/\.(jpe?g|png)$/i', $dosya)) {
        return false;
    }
    $hedef = $dosya . '.webp';
    if (!$zorla && is_file($hedef) && filemtime($hedef) >= filemtime($dosya)) {
        return true;
    }
    $e = wp_get_image_editor($dosya);
    if (is_wp_error($e)) {
        return false;
    }
    $boyut = @getimagesize($dosya);
    $e->set_quality($boyut && $boyut[0] <= 800 ? 58 : 70);
    $r = $e->save($hedef, 'image/webp');
    if (is_wp_error($r)) {
        return false;
    }
    // WebP, orijinalden büyükse (nadir) kullanma.
    if (filesize($hedef) >= filesize($dosya)) {
        @unlink($hedef);
        return false;
    }
    return true;
}

/** Ekin tüm boyutları (tam + ara boyutlar) için üret. */
function idk_webp_ek($id, $zorla = false)
{
    $meta = wp_get_attachment_metadata($id);
    $tam = get_attached_file($id);
    if (!$tam || !is_array($meta)) {
        return 0;
    }
    $n = idk_webp_uret($tam, $zorla && false) ? 1 : 0;
    foreach ((array) ($meta['sizes'] ?? []) as $s) {
        if (!empty($s['file']) && idk_webp_uret(dirname($tam) . '/' . $s['file'], $zorla && (int) ($s['width'] ?? 9999) <= 800)) {
            $n++;
        }
    }
    return $n;
}

add_filter('wp_generate_attachment_metadata', function ($meta, $id) {
    if (wp_attachment_is_image($id)) {
        idk_webp_ek($id);
    }
    return $meta;
}, 20, 2);

add_action('wp_body_open', function () {
    if (is_admin() || !wp_image_editor_supports(['mime_type' => 'image/webp']) && false) {
        return;
    }
    add_filter('wp_get_attachment_image_src', function ($img) {
        if (is_array($img) && !empty($img[0])) {
            $img[0] = idk_webp_url($img[0]);
        }
        return $img;
    }, 20);
    add_filter('wp_calculate_image_srcset', function ($sources) {
        if (is_array($sources)) {
            foreach ($sources as $k => $s) {
                $sources[$k]['url'] = idk_webp_url($s['url']);
            }
        }
        return $sources;
    }, 20);
    add_filter('wp_content_img_tag', function ($tag) {
        return preg_replace_callback('#https?://[^"\'\s,]+?\.(?:jpe?g|png)(?!\.webp)#i', function ($m) {
            return idk_webp_url($m[0]);
        }, $tag);
    }, 20);
}, 0);
