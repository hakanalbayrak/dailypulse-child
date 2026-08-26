<?php
/**
 * inc/seo.php icindeki saf fonksiyonlarin testi.
 * WordPress gerektirmez: php tests/seo-test.php
 */
define('KAMPANYA_SEO_HEAD', true);

// Saf fonksiyonlari, WP kancalari calismadan yukle.
$src = file_get_contents(__DIR__ . '/../inc/seo.php');
$cut = strpos($src, '/* ------------------------------------------------------------------
   WORDPRESS TARAFI');
eval('?>' . substr($src, 0, $cut));

$pass = 0; $fail = 0;
function check($name, $got, $want) {
    global $pass, $fail;
    if ($got === $want) { $pass++; return; }
    $fail++;
    echo "FAIL  $name\n   got : " . var_export($got, true) . "\n   want: " . var_export($want, true) . "\n";
}
function ok($name, $cond, $info = '') {
    global $pass, $fail;
    if ($cond) { $pass++; return; }
    $fail++;
    echo "FAIL  $name  $info\n";
}

/* --- text_pure --- */
check('blok yorumlari temizlenir',
    kampanya_seo_text_pure('<!-- wp:paragraph --><p>Merhaba <strong>dünya</strong></p><!-- /wp:paragraph -->'),
    'Merhaba dünya');
check('html entity cozulur', kampanya_seo_text_pure('<p>a &amp; b &quot;c&quot;</p>'), 'a & b "c"');
check('script atilir', kampanya_seo_text_pure('<p>x</p><script>var a=1;</script>'), 'x');

/* --- trim_pure --- */
check('kisa metin degismez', kampanya_seo_trim_pure('kisa', 158), 'kisa');
$long = str_repeat('kelime ', 40);
$t = kampanya_seo_trim_pure($long, 158);
ok('kesilen metin siniri asmaz', mb_strlen($t, 'UTF-8') <= 158, '=' . mb_strlen($t, 'UTF-8'));
ok('kesilen metin ... ile biter', mb_substr($t, -1, 1, 'UTF-8') === '…');
$tr = kampanya_seo_trim_pure('şĞİöçü ' . str_repeat('ağ ', 100), 40);
ok('turkce karakterde bayt degil karakter sayilir', mb_strlen($tr, 'UTF-8') <= 40, '=' . mb_strlen($tr, 'UTF-8'));

/* --- description_pure --- */
check('kayitli aciklama tercih edilir',
    kampanya_seo_description_pure('Kayitli metin', '<p>Icerikten gelen</p>'), 'Kayitli metin');
check('kayit yoksa icerikten uretilir',
    kampanya_seo_description_pure('', '<!-- wp:paragraph --><p>Icerikten gelen</p>'), 'Icerikten gelen');
check('bos icerik bos doner', kampanya_seo_description_pure('', ''), '');

/* --- faq_pure : gercek makale yapisi --- */
$article = <<<'HTML'
<!-- wp:heading --><h2>Kim İçin?</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Giris.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2>Sıkça Sorulan Sorular</h2><!-- /wp:heading -->
<!-- wp:heading {"level":3} --><h3>Birinci soru nedir?</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Birinci cevap &amp; detay.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":3} --><h3>İkinci soru nedir?</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>İkinci cevap.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2>Sonuç</h2><!-- /wp:heading -->
<!-- wp:heading {"level":3} --><h3>Bu SSS disinda kalmali</h3><!-- /wp:heading -->
<!-- wp:paragraph --><p>Sayilmamali.</p><!-- /wp:paragraph -->
HTML;
$faq = kampanya_seo_faq_pure($article);
check('sss sayisi', count($faq), 2);
check('ilk soru', $faq[0]['q'], 'Birinci soru nedir?');
check('ilk cevap entity cozulur', $faq[0]['a'], 'Birinci cevap & detay.');
check('ikinci soru', $faq[1]['q'], 'İkinci soru nedir?');
ok('sonuc bolumundeki h3 sss sayilmaz', !in_array('Bu SSS disinda kalmali', array_column($faq, 'q'), true));

check('sss bolumu yoksa bos dizi', kampanya_seo_faq_pure('<h2>Baska</h2><h3>s</h3><p>c</p>'), []);
check('cevapsiz soru atlanir', kampanya_seo_faq_pure('<h2>Sıkça Sorulan Sorular</h2><h3>Soru?</h3>'), []);

/* --- class'li baslik varyanti (yeni sablon) --- */
$v2 = '<h2 class="wp-block-heading">Sıkça Sorulan Sorular</h2>'
    . '<h3 class="wp-block-heading">Soru bir?</h3><p>Cevap bir.</p>'
    . '<h3 class="wp-block-heading">Soru iki?</h3><p>Cevap iki.</p>';
check('class tasiyan basliklar da okunur', count(kampanya_seo_faq_pure($v2)), 2);

/* --- "Sık Sorulan Sorular" varyanti (6 makale bunu kullaniyor) --- */
$v3 = '<h2>Sık Sorulan Sorular</h2><h3>Soru a?</h3><p>Cevap a.</p><h3>Soru b?</h3><p>Cevap b.</p>';
check('"Sık Sorulan Sorular" da taninir', count(kampanya_seo_faq_pure($v3)), 2);

/* --- gercek makalelerle --- */
$dir = getenv('ART_DIR');
if ($dir && is_dir($dir)) {
    $files = glob($dir . '/*.html');
    $withFaq = 0; $bad = 0;
    foreach ($files as $f) {
        $html = file_get_contents($f);
        $faq  = kampanya_seo_faq_pure($html);
        if ($faq) { $withFaq++; }
        foreach ($faq as $x) {
            if ($x['q'] === '' || $x['a'] === '' || strpos($x['q'], '<') !== false) { $bad++; }
        }
        $d = kampanya_seo_description_pure('', $html);
        if (mb_strlen($d, 'UTF-8') > 158) { $bad++; }
    }
    echo "gercek makale: " . count($files) . " | sss bulunan: $withFaq | bozuk: $bad\n";
    ok('gercek makalelerde bozuk sss/aciklama yok', $bad === 0);
}

echo "\npass=$pass fail=$fail\n";
exit($fail ? 1 : 0);
