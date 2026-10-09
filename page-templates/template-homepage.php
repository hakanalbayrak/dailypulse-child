<?php
/**
 * Template Name: Kampanya Homepage
 * Description: Ana sayfa — tek satırlık abone şeridi + 1 manşet / 3 liste / 6 kart (10 yazı, boş hücre yok)
 */

get_header();

$yazilar = get_posts([
    'posts_per_page'      => 10,
    'post_status'         => 'publish',
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
]);
$blog_url = get_permalink(get_option('page_for_posts'));

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
?>

<main id="k-homepage" class="k-homepage">

  <section class="idk-serit" aria-label="Bülten aboneliği">
    <div class="k-container idk-serit__ic">
      <div class="idk-serit__metin">
        <h1 class="idk-serit__baslik">Satın almadan önce <mark>ayrıntıya bakın</mark></h1>
        <p class="idk-serit__alt">Bağımsız ürün rehberleri, yeni yazılar çıktıkça e-posta kutunuzda. Haftada en fazla 2 e-posta.</p>
      </div>

      <form class="k-subscribe-form" id="k-subscribe-form" novalidate>
        <div class="k-form-row">
          <div class="k-email-wrap">
            <label class="screen-reader-text" for="k-email">E-posta adresiniz</label>
            <input type="email" id="k-email" name="email" class="k-email-input" placeholder="e-posta adresiniz" autocomplete="email" spellcheck="false" required>
            <span class="k-autocomplete" id="k-autocomplete" aria-hidden="true"></span>
          </div>
          <button type="submit" class="k-subscribe-btn" id="k-subscribe-btn">
            <span class="k-btn-text">Abone ol</span>
            <span class="k-btn-loading" hidden>…</span>
          </button>
        </div>
        <div class="k-consent-row">
          <label class="k-consent-label">
            <input type="checkbox" name="kvkk" id="k-kvkk" class="k-consent-checkbox" checked>
            <span class="k-consent-text">
              <a href="<?php echo esc_url(home_url('/kvkk-aydinlatma-metni/')); ?>" target="_blank" rel="noopener">KVKK Aydınlatma Metni</a>'ni ve
              <a href="<?php echo esc_url(home_url('/acik-riza-metni/')); ?>" target="_blank" rel="noopener">Açık Rıza Metni</a>'ni okudum, kabul ediyorum.
              İstediğiniz zaman <a href="<?php echo esc_url(home_url('/abonelikten-cik/')); ?>">abonelikten çıkabilirsiniz</a>.
            </span>
          </label>
        </div>
        <div class="k-form-msg" id="k-form-msg" role="alert" hidden></div>
      </form>
    </div>
  </section>

  <section class="k-posts-section" id="k-posts" aria-label="Son yazılar">
    <div class="k-container">
      <div class="k-section-head">
        <h2 class="k-section-title">Son yazılar</h2>
        <a href="<?php echo esc_url($blog_url); ?>" class="k-section-more">Tümünü gör →</a>
      </div>

      <?php if ($yazilar) : ?>
        <div class="idk-izgara">
          <?php foreach ($yazilar as $i => $p) :
              $tur = $i === 0 ? 'manset' : ($i <= 3 ? 'liste' : 'kart');
              idk_ana_kart($p, $tur, $i === 0);
          endforeach; ?>
        </div>
        <div class="k-posts-more"><a href="<?php echo esc_url($blog_url); ?>" class="k-btn-outline">Tüm yazılara git</a></div>
      <?php else : ?>
        <p class="k-no-posts">Henüz yayınlanmış yazı bulunmuyor. Yakında.</p>
      <?php endif; ?>
    </div>
  </section>

</main>

<?php if ($yazilar) : ?>
<script type="application/ld+json"><?php
echo wp_json_encode([
    '@context'        => 'https://schema.org',
    '@type'           => 'ItemList',
    'name'            => 'Son yazılar',
    'itemListElement' => array_map(function ($p, $i) {
        return ['@type' => 'ListItem', 'position' => $i + 1, 'url' => get_permalink($p->ID), 'name' => get_the_title($p->ID)];
    }, $yazilar, array_keys($yazilar)),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?></script>
<?php endif; ?>

<?php get_footer(); ?>
