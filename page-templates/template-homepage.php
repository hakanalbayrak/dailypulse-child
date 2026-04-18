<?php
/**
 * Template Name: Kampanya Homepage
 * Description: Ana sayfa — Hero abone formu + Blog listesi
 */

get_header(); ?>

<main id="k-homepage" class="k-homepage">

  <!-- ======================================================
       HERO — E-posta Abone Bölümü
       ====================================================== -->
  <section class="k-hero" aria-label="Bülten aboneliği">
    <div class="k-hero__inner">

      <div class="k-hero__badge">
        <span class="k-tag">📬 Ücretsiz Bülten</span>
      </div>

      <h1 class="k-hero__title">
        En İyi Kampanyaları<br>
        <span class="k-hero__title-accent">Kaçırmayın</span>
      </h1>

      <p class="k-hero__subtitle">
        Türkiye'nin en güncel indirimleri, fırsatları ve kampanyaları doğrudan<br class="k-hero__br">
        e-posta kutunuza gelsin. Haftada en fazla 2 e-posta, sıfır spam.
      </p>

      <!-- FORM -->
      <form class="k-subscribe-form" id="k-subscribe-form" novalidate>

        <div class="k-form-row">
          <div class="k-email-wrap">
            <input
              type="email"
              id="k-email"
              name="email"
              class="k-email-input"
              placeholder="e-posta adresiniz"
              autocomplete="email"
              spellcheck="false"
              required
            >
            <!-- Otomatik tamamlama önerisi -->
            <span class="k-autocomplete" id="k-autocomplete" aria-hidden="true"></span>
          </div>
          <button type="submit" class="k-subscribe-btn" id="k-subscribe-btn">
            <span class="k-btn-text">Abone Ol</span>
            <span class="k-btn-loading" hidden>…</span>
          </button>
        </div>

        <!-- KVKK ONAY -->
        <div class="k-consent-row">
          <label class="k-consent-label">
            <input
              type="checkbox"
              name="kvkk"
              id="k-kvkk"
              class="k-consent-checkbox"
              checked
            >
            <span class="k-consent-text">
              <a href="<?php echo esc_url(home_url('/kvkk-aydinlatma-metni/')); ?>" target="_blank" rel="noopener">KVKK Aydınlatma Metni</a>'ni ve
              <a href="<?php echo esc_url(home_url('/acik-riza-metni/')); ?>" target="_blank" rel="noopener">Açık Rıza Metni</a>'ni okudum, kabul ediyorum.
            </span>
          </label>
        </div>

        <!-- MESAJLAR -->
        <div class="k-form-msg" id="k-form-msg" role="alert" hidden></div>

      </form>

      <p class="k-hero__trust">
        <span>🔒 Verileriniz güvende</span>
        <span>·</span>
        <span>İstediğiniz zaman <a href="<?php echo esc_url(home_url('/abonelikten-cik/')); ?>">abonelikten çıkın</a></span>
      </p>

    </div><!-- .k-hero__inner -->
  </section>


  <!-- ======================================================
       BLOG LİSTESİ
       ====================================================== -->
  <section class="k-posts-section" id="k-posts" aria-label="Son yazılar">
    <div class="k-container">

      <div class="k-section-head">
        <h2 class="k-section-title">Son Yazılar</h2>
        <a href="<?php echo esc_url(get_permalink(get_option('page_for_posts'))); ?>" class="k-section-more">Tümünü Gör →</a>
      </div>

      <?php
      $args = [
          'posts_per_page' => 9,
          'post_status'    => 'publish',
          'no_found_rows'  => true,
      ];
      $the_query = new WP_Query($args);
      ?>

      <?php if ($the_query->have_posts()) : ?>
        <div class="k-posts-grid">
          <?php
          $index = 0;
          while ($the_query->have_posts()) :
              $the_query->the_post();
              $cats     = get_the_category();
              $cat_name = !empty($cats) ? esc_html($cats[0]->name) : 'Genel';
              $is_hero  = ($index === 0);
          ?>
          <article class="k-post-card<?php echo $is_hero ? ' k-post-card--hero' : ''; ?>">
            <a href="<?php the_permalink(); ?>" class="k-post-card__link" tabindex="-1" aria-hidden="true">
              <?php if (has_post_thumbnail()) : ?>
                <div class="k-post-card__img-wrap">
                  <?php the_post_thumbnail(
                      $is_hero ? 'card-featured' : 'card-regular',
                      ['class' => 'k-post-card__img', 'alt' => get_the_title()]
                  ); ?>
                </div>
              <?php else : ?>
                <div class="k-post-card__img-wrap k-post-card__img-wrap--placeholder"></div>
              <?php endif; ?>
            </a>

            <div class="k-post-card__body">
              <div class="k-post-card__meta">
                <span class="k-tag k-post-cat"><?php echo $cat_name; ?></span>
                <time class="k-post-card__date" datetime="<?php echo get_the_date('Y-m-d'); ?>">
                  <?php echo get_the_date('j M Y'); ?>
                </time>
              </div>

              <h3 class="k-post-card__title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
              </h3>

              <?php if ($is_hero || !empty(get_the_excerpt())) : ?>
                <p class="k-post-card__excerpt"><?php echo wp_trim_words(get_the_excerpt(), $is_hero ? 30 : 18); ?></p>
              <?php endif; ?>

              <a href="<?php the_permalink(); ?>" class="k-post-card__read">
                Devamını Oku <span aria-hidden="true">→</span>
              </a>
            </div>
          </article>
          <?php
          $index++;
          endwhile;
          wp_reset_postdata();
          ?>
        </div><!-- .k-posts-grid -->

        <!-- DAHA FAZLA butonu -->
        <div class="k-posts-more">
          <a href="<?php echo esc_url(get_permalink(get_option('page_for_posts'))); ?>" class="k-btn-outline">
            Tüm Yazılara Git
          </a>
        </div>

      <?php else : ?>
        <p class="k-no-posts">Henüz yayınlanmış yazı bulunmuyor. Yakında!</p>
      <?php endif; ?>

    </div><!-- .k-container -->
  </section>

</main>

<?php get_footer(); ?>
