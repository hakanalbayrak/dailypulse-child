<?php
/**
 * Ortak yazı kartı (ana sayfa, blog, kategori, arama). 2026-10-09 Tasarım B.
 * \$tur: manset | liste | kart
 */
if (!defined('ABSPATH')) { exit; }

/** Tek bir kart: $tur = manset | liste | kart */
if (!function_exists('idk_ana_kart')) {
function idk_ana_kart($p, $tur, $ilk = false) {
    $kat   = get_the_category($p->ID);
    $ad    = !empty($kat) ? esc_html($kat[0]->name) : 'Genel';
    $boyut = $tur === 'manset' ? 'card-featured' : ($tur === 'liste' ? 'thumbnail' : 'card-regular');
    $attr  = ['class' => 'idk-kart__img', 'alt' => ''];
    if ($ilk) { $attr['loading'] = 'eager'; $attr['fetchpriority'] = 'high'; }
    $link  = get_permalink($p->ID);
    $baslik = get_the_title($p->ID);
    ?>
    <article class="idk-kart idk-kart--<?php echo esc_attr($tur); ?>">
      <a class="idk-kart__gorsel" href="<?php echo esc_url($link); ?>" tabindex="-1" aria-hidden="true">
        <?php if (has_post_thumbnail($p->ID)) : echo get_the_post_thumbnail($p->ID, $boyut, $attr); else : ?>
          <span class="idk-kart__img idk-kart__img--bos"></span>
        <?php endif; ?>
      </a>
      <div class="idk-kart__govde">
        <p class="idk-kart__ust"><span class="k-tag"><?php echo $ad; ?></span><time datetime="<?php echo esc_attr(get_the_date('Y-m-d', $p->ID)); ?>"><?php echo esc_html(get_the_date('j M Y', $p->ID)); ?></time></p>
        <h3 class="idk-kart__baslik"><a href="<?php echo esc_url($link); ?>"><?php echo esc_html($baslik); ?></a></h3>
        <?php if ($tur === 'manset') : ?>
          <p class="idk-kart__ozet"><?php echo esc_html(wp_trim_words(get_the_excerpt($p), 32)); ?></p>
          <a class="idk-kart__oku" href="<?php echo esc_url($link); ?>">Rehberi oku <span aria-hidden="true">→</span></a>
        <?php endif; ?>
      </div>
    </article>
    <?php
}
}
