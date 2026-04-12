<?php
$deals = array(
    array(
        'brand'      => 'Trendyol',
        'title'      => 'Yazlık Giyim Koleksiyonu Süper İndirim',
        'desc'       => 'Seçili ürünlerde yarıyı aşkın indirimlerle yaza hazırlanın.',
        'badge'      => '%50 OFF',
        'badge_type' => '',
        'link'       => '#',
    ),
    array(
        'brand'      => 'Hepsiburada',
        'title'      => 'Elektronik Festivali Bugün Son',
        'desc'       => 'Laptop, telefon ve aksesuarlarda kaçırılmayacak fırsatlar.',
        'badge'      => 'SON GÜN',
        'badge_type' => 'red',
        'link'       => '#',
    ),
    array(
        'brand'      => 'Udemy',
        'title'      => 'Tüm Kurslar 49,99 TL\'den Başlayan Fiyatlarla',
        'desc'       => 'Kariyer geliştirme kurslarında sınırlı süre fırsatı.',
        'badge'      => '%30 OFF',
        'badge_type' => '',
        'link'       => '#',
    ),
    array(
        'brand'      => 'Gratis',
        'title'      => 'Cilt Bakım Ürünlerinde Özel Kampanya',
        'desc'       => 'Seçili markalarda 2 al 1 öde kampanyası devam ediyor.',
        'badge'      => '2 AL 1 ÖDE',
        'badge_type' => '',
        'link'       => '#',
    ),
);
?>
<div class="dp-deals-strip">
  <?php foreach ($deals as $deal) : ?>
    <a href="<?php echo esc_url($deal['link']); ?>" class="dp-deal-card dp-reveal">
      <div class="dp-deal-badge <?php echo esc_attr($deal['badge_type']); ?>"><?php echo esc_html($deal['badge']); ?></div>
      <div class="dp-deal-brand"><?php echo esc_html($deal['brand']); ?></div>
      <div class="dp-deal-title"><?php echo esc_html($deal['title']); ?></div>
      <div class="dp-deal-desc"><?php echo esc_html($deal['desc']); ?></div>
      <div class="dp-deal-cta">Fırsatı Yakala</div>
    </a>
  <?php endforeach; ?>
</div>
