<?php
/**
 * 404 — sayfa bulunamadı.
 *
 * Blocksy'nin varsayılan 404'ü yarı İngilizceydi ("Oops! That page can't be
 * found"). Burada Türkçe, arama kutusu ve son rehberlerle: ziyaretçiyi
 * çıkmaz sokakta bırakmaz.
 *
 * @package DailyPulse
 */
get_header();

$son = get_posts([
    'numberposts'  => 6,
    'post_status'  => 'publish',
    'post__not_in' => function_exists('idk_noindex_idler') ? idk_noindex_idler() : [],
]);
?>
<div class="ct-container" data-vertical-spacing="top:bottom">
  <section id="primary" class="content-area idk-404">
    <main id="main" class="site-main">
      <h1 class="idk-404__baslik">Aradığınız sayfa bulunamadı</h1>
      <p class="idk-404__metin">Bağlantı değişmiş ya da sayfa kaldırılmış olabilir. Aşağıdan arayabilir ya da son rehberlere göz atabilirsiniz.</p>
      <?php get_search_form(); ?>
      <?php if ($son) : ?>
        <h2 class="idk-404__alt">Son rehberler</h2>
        <ul class="idk-404__liste">
          <?php foreach ($son as $y) : ?>
            <li><a href="<?php echo esc_url(get_permalink($y)); ?>"><?php echo esc_html(wp_strip_all_tags($y->post_title)); ?></a></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <p class="idk-404__dugmeler">
        <a class="idk-404__btn" href="<?php echo esc_url(home_url('/')); ?>">Ana sayfaya dön</a>
        <a class="idk-404__link" href="<?php echo esc_url(home_url('/blog/')); ?>">Tüm rehberler</a>
      </p>
    </main>
  </section>
</div>
<?php get_footer();
