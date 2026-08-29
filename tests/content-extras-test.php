<?php
/**
 * inc/content-extras.php icindeki saf fonksiyonun testi.
 * WordPress gerektirmez: php tests/content-extras-test.php
 * Gercek makalelerle dogrulama icin: ART_DIR=/path php tests/content-extras-test.php
 */

// Saf fonksiyonu, WP kancalari calismadan yukle.
$src = file_get_contents(__DIR__ . '/../inc/content-extras.php');
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

/* --- temel cikarim --- */
$html = '<h3>1. <a href="https://kampanya.website/go/urun-a" rel="nofollow sponsored">Ürün A</a> — Alt Başlık</h3>'
      . '<h3>2. <a href="https://kampanya.website/go/urun-b">Ürün B</a></h3>';
$links = kampanya_extract_product_links($html);
check('iki urun bulunur', count($links), 2);
check('ilk urun adi dogru', $links[0]['name'], 'Ürün A');
check('ilk urun linki dogru', $links[0]['url'], 'https://kampanya.website/go/urun-a');
check('ikinci urun adi dogru', $links[1]['name'], 'Ürün B');

/* --- numarasiz h3 yoksayilir (FAQ sorulari vb.) --- */
$html2 = '<h3>Bu ürün gerçekten işe yarıyor mu?</h3><p>Evet.</p>'
       . '<h3>1. <a href="https://kampanya.website/go/tek-urun">Tek Ürün</a></h3>';
$links2 = kampanya_extract_product_links($html2);
check('sadece numarali baslik sayilir', count($links2), 1);

/* --- ayni URL tekrar etmez --- */
$html3 = '<h3>1. <a href="https://kampanya.website/go/x">X</a></h3>'
       . '<p><a href="https://kampanya.website/go/x">X tekrar</a></p>'
       . '<h3>2. <a href="https://kampanya.website/go/x">X</a> tekrar h3</h3>';
$links3 = kampanya_extract_product_links($html3);
check('tekrarlanan URL bir kez sayilir', count($links3), 1);

/* --- bos girdi --- */
check('bos icerik bos dizi doner', kampanya_extract_product_links(''), []);
check('urunsuz icerik bos dizi doner', kampanya_extract_product_links('<p>Merhaba</p>'), []);

/* --- gercek makalelerle (yeniden siralama sonrasi render edilmis hali) --- */
$dir = getenv('ART_DIR');
if ($dir && is_dir($dir)) {
    $files = glob($dir . '/*.html');
    $withProducts = 0; $bad = 0;
    foreach ($files as $f) {
        $html = file_get_contents($f);
        $links = kampanya_extract_product_links($html);
        if (count($links) >= 2) { $withProducts++; }
        $urls = array_column($links, 'url');
        if (count($urls) !== count(array_unique($urls))) { $bad++; }
        foreach ($links as $l) {
            if ($l['name'] === '' || strpos($l['name'], '<') !== false) { $bad++; }
        }
    }
    echo "gercek makale: " . count($files) . " | urun listesi bulunan: $withProducts | bozuk: $bad\n";
    ok('gercek makalelerde bozuk urun listesi yok', $bad === 0);
}

echo "\npass=$pass fail=$fail\n";
exit($fail ? 1 : 0);
