<?php
/**
 * Template Name: Daily Pulse Homepage
 */

get_header(); ?>

<main id="primary" class="site-main">

  <?php get_template_part('template-parts/hero-section'); ?>

  <!-- KATEGORİ PİLLLERİ -->
  <div class="dp-categories">
    <a class="dp-cat-pill active" href="#">Tümü</a>
    <a class="dp-cat-pill hot" href="<?php echo esc_url(get_category_link(get_cat_ID('firsatlar'))); ?>">🔥 Fırsatlar</a>
    <?php
    $cats = array('teknoloji', 'finans', 'saglik', 'yasam', 'seyahat', 'egitim');
    foreach ($cats as $cat_slug) {
        $cat = get_category_by_slug($cat_slug);
        if ($cat) {
            echo '<a class="dp-cat-pill" href="' . esc_url(get_category_link($cat->term_id)) . '">' . esc_html($cat->name) . '</a>';
        }
    }
    ?>
  </div>

  <!-- ÖNE ÇIKAN YAZILAR -->
  <div style="max-width:var(--dp-max-width);margin:0 auto;padding:0 24px;">
    <div class="dp-section-head" style="padding:0;">
      <h2>Öne Çıkanlar</h2>
      <a href="<?php echo esc_url(get_permalink(get_option('page_for_posts'))); ?>">Tümünü Gör →</a>
    </div>

    <?php
    $featured = new WP_Query(array(
        'posts_per_page' => 3,
        'meta_key'       => '_is_featured',
        'meta_value'     => '1',
        'post_status'    => 'publish',
    ));
    if (!$featured->have_posts()) {
        $featured = new WP_Query(array('posts_per_page' => 3, 'post_status' => 'publish'));
    }
    ?>

    <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:20px;margin-bottom:64px;">
      <?php
      $i = 0;
      while ($featured->have_posts()) : $featured->the_post();
          $post_cats  = get_the_category();
          $cat_slug   = !empty($post_cats) ? $post_cats[0]->slug : 'teknoloji';
          $cat_color  = dailypulse_category_color($cat_slug);
          $is_first   = ($i === 0);
      ?>
      <a href="<?php the_permalink(); ?>" class="dp-card dp-reveal" style="text-decoration:none;display:flex;flex-direction:column;<?php echo $is_first ? 'grid-row:span 2;' : ''; ?>">
        <?php if (has_post_thumbnail()) : ?>
          <div style="overflow:hidden;<?php echo $is_first ? 'aspect-ratio:4/3;' : 'aspect-ratio:16/9;'; ?>">
            <?php the_post_thumbnail($is_first ? 'card-featured' : 'card-regular', array('style' => 'width:100%;height:100%;object-fit:cover;')); ?>
          </div>
        <?php else : ?>
          <div style="<?php echo $is_first ? 'aspect-ratio:4/3;' : 'aspect-ratio:16/9;'; ?>background:linear-gradient(135deg,var(--dp-purple-700),var(--dp-purple-500));"></div>
        <?php endif; ?>

        <div style="padding:20px 22px 24px;flex:1;display:flex;flex-direction:column;">
          <div class="dp-card-meta" style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
            <span class="dp-tag dp-tag-<?php echo esc_attr($cat_color['class']); ?>"><?php echo esc_html(!empty($post_cats) ? $post_cats[0]->name : 'Genel'); ?></span>
            <span style="color:var(--dp-white-50);font-size:12px;"><?php echo get_the_date('j M'); ?></span>
          </div>
          <div class="dp-card-title" style="<?php echo $is_first ? 'font-size:22px;' : ''; ?>flex:1;">
            <?php the_title(); ?>
          </div>
          <?php if ($is_first) : ?>
            <div class="dp-card-excerpt" style="margin:10px 0;"><?php echo wp_trim_words(get_the_excerpt(), 25); ?></div>
          <?php endif; ?>
          <div style="display:flex;align-items:center;justify-content:space-between;padding-top:14px;border-top:1px solid var(--dp-white-05);margin-top:auto;">
            <span class="dp-card-read">Devamını Oku</span>
            <span style="font-size:11px;color:var(--dp-white-50);"><?php echo dailypulse_reading_time(); ?> dk okuma</span>
          </div>
        </div>
      </a>
      <?php $i++; endwhile; wp_reset_postdata(); ?>
    </div>
  </div>

  <!-- GÜNÜN FIRSATLARI -->
  <div style="max-width:var(--dp-max-width);margin:0 auto;padding:0 24px;">
    <div class="dp-section-head" style="padding:0;">
      <h2>Günün Fırsatları</h2>
      <a href="<?php echo esc_url(get_category_link(get_cat_ID('firsatlar'))); ?>">Tüm Fırsatlar →</a>
    </div>
  </div>
  <?php get_template_part('template-parts/deals-strip'); ?>

  <!-- MID CTA -->
  <div style="padding:0 24px;">
    <?php get_template_part('template-parts/mid-cta'); ?>
  </div>

  <!-- SON YAZILAR -->
  <div style="max-width:var(--dp-max-width);margin:0 auto;padding:0 24px;">
    <div class="dp-section-head" style="padding:0;">
      <h2>Son Yazılar</h2>
      <a href="<?php echo esc_url(get_permalink(get_option('page_for_posts'))); ?>">Arşiv →</a>
    </div>

    <?php
    $recent = new WP_Query(array(
        'posts_per_page' => 6,
        'offset'         => 3,
        'post_status'    => 'publish',
    ));
    ?>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:64px;">
      <?php while ($recent->have_posts()) : $recent->the_post();
          $post_cats = get_the_category();
          $cat_slug  = !empty($post_cats) ? $post_cats[0]->slug : 'teknoloji';
          $cat_color = dailypulse_category_color($cat_slug);
      ?>
      <a href="<?php the_permalink(); ?>" class="dp-card dp-reveal" style="text-decoration:none;display:flex;flex-direction:column;">
        <?php if (has_post_thumbnail()) : ?>
          <div style="overflow:hidden;aspect-ratio:16/9;">
            <?php the_post_thumbnail('card-regular', array('style' => 'width:100%;height:100%;object-fit:cover;')); ?>
          </div>
        <?php else : ?>
          <div style="aspect-ratio:16/9;background:linear-gradient(135deg,var(--dp-purple-700),var(--dp-purple-500));"></div>
        <?php endif; ?>
        <div style="padding:20px 22px 24px;flex:1;display:flex;flex-direction:column;">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
            <span class="dp-tag dp-tag-<?php echo esc_attr($cat_color['class']); ?>"><?php echo esc_html(!empty($post_cats) ? $post_cats[0]->name : 'Genel'); ?></span>
            <span style="color:var(--dp-white-50);font-size:12px;"><?php echo get_the_date('j M'); ?></span>
          </div>
          <div class="dp-card-title" style="flex:1;"><?php the_title(); ?></div>
          <div class="dp-card-excerpt" style="margin:8px 0;"><?php echo wp_trim_words(get_the_excerpt(), 15); ?></div>
          <div style="display:flex;align-items:center;justify-content:space-between;padding-top:14px;border-top:1px solid var(--dp-white-05);margin-top:auto;">
            <span class="dp-card-read">Devamını Oku</span>
            <span style="font-size:11px;color:var(--dp-white-50);"><?php echo dailypulse_reading_time(); ?> dk</span>
          </div>
        </div>
      </a>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>
  </div>

  <!-- SOCIAL PROOF -->
  <div style="padding:0 24px;">
    <?php get_template_part('template-parts/proof-bar'); ?>
  </div>

  <!-- FİNAL CTA -->
  <?php get_template_part('template-parts/final-cta'); ?>

</main>

<?php get_footer(); ?>
