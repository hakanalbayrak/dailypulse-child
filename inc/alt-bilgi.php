<?php
/**
 * Alt bilgi (2026-10-09, Tasarım B): Blocksy'nin tek satırlık alt çubuğu yerine
 * üç sütunlu, kendi alt bilgimiz. Blocksy footer'ı CSS ile gizlenir
 * (.ct-footer { display:none }); bu blok wp_footer'da basılır.
 *
 *  - marka + tek cümle + satış ortaklığı notu
 *  - rehberler: içinde yazı olan kategoriler (yazı sayısına göre)
 *  - kurumsal: footer menüsü (id 18) olduğu gibi
 */
if (!defined('ABSPATH')) { exit; }

add_action('wp_footer', function () {
    if (is_admin()) { return; }
    $kats = get_categories(['orderby' => 'count', 'order' => 'DESC', 'number' => 8, 'hide_empty' => true, 'exclude' => [1]]);
    $menu = wp_get_nav_menu_items(18) ?: [];
    ?>
<footer class="idk-altbilgi" aria-label="Alt bilgi">
  <div class="idk-altbilgi__ic">
    <div class="idk-altbilgi__marka">
      <a href="<?php echo esc_url(home_url('/')); ?>" class="idk-altbilgi__logo" aria-label="ince detay ana sayfa"><?php echo kampanya_logo_svg_dark(); ?></a>
      <p>Satın almadan önce ayrıntıya bakın. Bağımsız ürün rehberleri ve karşılaştırmalar.</p>
      <p class="idk-altbilgi__not">Bazı bağlantılar satış ortaklığı içerir; bu size ek maliyet getirmez. <a href="<?php echo esc_url(home_url('/affiliate-disclosure/')); ?>">Ayrıntı</a></p>
    </div>
    <nav class="idk-altbilgi__kol" aria-label="Rehberler">
      <h2>Rehberler</h2>
      <ul>
        <?php foreach ($kats as $k) : ?>
          <li><a href="<?php echo esc_url(get_category_link($k)); ?>"><?php echo esc_html($k->name); ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <nav class="idk-altbilgi__kol" aria-label="Kurumsal">
      <h2>Kurumsal</h2>
      <ul>
        <?php foreach ($menu as $m) : ?>
          <li><a href="<?php echo esc_url($m->url); ?>"><?php echo esc_html($m->title); ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
  </div>
  <p class="idk-altbilgi__alt">© <?php echo esc_html(date_i18n('Y')); ?> ince detay</p>
</footer>
    <?php
}, 5);
