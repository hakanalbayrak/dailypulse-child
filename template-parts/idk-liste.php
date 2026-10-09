<?php
/**
 * Ortak liste sayfası (blog, kategori, etiket, arama). 2026-10-09 Tasarım B.
 * Yan çubuk yok: 3 sütun kart ızgarası, kategori çipleri, sayfalama.
 * Sayfa başına 12 yazı (3 ve 2 sütunda tam satır).
 */
if (!defined('ABSPATH')) { exit; }
require_once get_stylesheet_directory() . '/inc/kartlar.php';

if (is_search()) {
    $baslik = 'Arama: “' . get_search_query() . '”';
    $alt    = sprintf('%d sonuç', (int) $GLOBALS['wp_query']->found_posts);
} elseif (is_category() || is_tag()) {
    $baslik = single_term_title('', false);
    $alt    = wp_strip_all_tags(term_description()) ?: sprintf('%d rehber', (int) $GLOBALS['wp_query']->found_posts);
} else {
    $baslik = 'Blog';
    $alt    = 'Bağımsız ürün rehberleri ve karşılaştırmalar, yeni yazılardan eskilere.';
}
$kats    = get_categories(['orderby' => 'count', 'order' => 'DESC', 'hide_empty' => true, 'exclude' => [1]]);
$gecerli = is_category() ? get_queried_object_id() : 0;
$blog    = get_permalink(get_option('page_for_posts'));
?>
<!-- ppp=<?php echo (int) get_query_var('posts_per_page'); ?> found=<?php echo (int) $GLOBALS['wp_query']->found_posts; ?> n=<?php echo (int) $GLOBALS['wp_query']->post_count; ?> -->
<main id="idk-liste" class="idk-liste">
  <header class="idk-liste__bas">
    <div class="k-container">
      <h1 class="idk-liste__baslik"><?php echo esc_html($baslik); ?></h1>
      <p class="idk-liste__alt"><?php echo esc_html($alt); ?></p>
      <nav class="idk-cipler" aria-label="Kategoriler">
        <a href="<?php echo esc_url($blog); ?>" class="idk-cip<?php echo (!$gecerli && !is_search()) ? ' idk-cip--on' : ''; ?>"<?php echo (!$gecerli && !is_search()) ? ' aria-current="page"' : ''; ?>>Tümü</a>
        <?php foreach ($kats as $k) : ?>
          <a href="<?php echo esc_url(get_category_link($k)); ?>" class="idk-cip<?php echo $gecerli === $k->term_id ? ' idk-cip--on' : ''; ?>"<?php echo $gecerli === $k->term_id ? ' aria-current="page"' : ''; ?>><?php echo esc_html($k->name); ?></a>
        <?php endforeach; ?>
      </nav>
    </div>
  </header>

  <div class="k-container idk-liste__ic">
    <?php if (have_posts()) : ?>
      <div class="idk-izgara idk-izgara--liste">
        <?php $i = 0; while (have_posts()) : the_post(); idk_ana_kart(get_post(), 'kart', $i === 0 && !is_paged()); $i++; endwhile; ?>
      </div>
      <?php the_posts_pagination(['mid_size' => 1, 'prev_text' => '← Önceki', 'next_text' => 'Sonraki →', 'screen_reader_text' => 'Sayfalar']); ?>
    <?php else : ?>
      <div class="idk-bos">
        <p>Bu aramayla eşleşen yazı bulunamadı. Başka bir sözcük deneyin ya da bir kategoriden başlayın.</p>
        <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="idk-arama">
          <label class="screen-reader-text" for="idk-ara">Ara</label>
          <input type="search" id="idk-ara" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="Ürün ya da konu ara" autocomplete="off">
          <button type="submit">Ara</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</main>
