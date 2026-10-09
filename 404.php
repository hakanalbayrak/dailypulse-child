<?php
/**
 * 404 — sayfa bulunamadı (Tasarım B, 2026-10-09).
 *
 * Türkçe, arama kutusu, kategori çipleri ve son rehberler: ziyaretçiyi çıkmaz sokakta
 * bırakmaz. Son rehberler listedeki kartlarla aynı ızgarada (inc/kartlar.php).
 *
 * @package DailyPulse
 */
require_once get_stylesheet_directory() . '/inc/kartlar.php';
get_header();

$son = get_posts([
    'numberposts'  => 6,
    'post_status'  => 'publish',
    'post__not_in' => function_exists('idk_noindex_idler') ? idk_noindex_idler() : [],
]);
$kats = get_categories(['orderby' => 'count', 'order' => 'DESC', 'hide_empty' => true, 'exclude' => [1], 'number' => 8]);
?>
<main id="idk-404" class="idk-liste idk-404">
  <header class="idk-liste__bas">
    <div class="k-container">
      <p class="k-tag">404</p>
      <h1 class="idk-liste__baslik idk-404__baslik">Aradığınız sayfa bulunamadı</h1>
      <p class="idk-liste__alt">Bağlantı değişmiş ya da sayfa kaldırılmış olabilir. Aşağıdan arayabilir ya da bir kategoriden başlayabilirsiniz.</p>
      <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="idk-arama">
        <label class="screen-reader-text" for="idk-ara-404">Ara</label>
        <input type="search" id="idk-ara-404" name="s" placeholder="Ürün ya da konu ara" autocomplete="off">
        <button type="submit">Ara</button>
      </form>
      <nav class="idk-cipler" aria-label="Kategoriler">
        <?php foreach ($kats as $k) : ?>
          <a href="<?php echo esc_url(get_category_link($k)); ?>" class="idk-cip"><?php echo esc_html($k->name); ?></a>
        <?php endforeach; ?>
      </nav>
    </div>
  </header>
  <?php if ($son) : ?>
  <div class="k-container idk-liste__ic">
    <div class="k-section-head"><h2 class="k-section-title">Son rehberler</h2><a href="<?php echo esc_url(home_url('/blog/')); ?>" class="k-section-more">Tümünü gör →</a></div>
    <div class="idk-izgara idk-izgara--liste">
      <?php foreach ($son as $y) { idk_ana_kart($y, 'kart'); } ?>
    </div>
  </div>
  <?php endif; ?>
</main>
<?php get_footer();
