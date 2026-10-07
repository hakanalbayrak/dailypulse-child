<?php
/**
 * Sayfa önbelleği — SAF PHP motoru (WordPress fonksiyonu kullanmaz).
 *
 * wp-content/advanced-cache.php (tema kurar) bunu WordPress yüklenmeden ÖNCE çağırır; önbellekte
 * taze bir kopya varsa doğrudan sunar ve çıkar (TTFB ~450 ms → ~30 ms). Kopyayı oluşturan taraf
 * inc/sayfa-onbellek.php'dir (WordPress tarafı). Burada hata olursa stub yutar, site normal çalışır.
 *
 * Kapsam: yalnızca anonim GET, sorgu dizesi YOK, yol "/" ile biter. Kapalı anahtarı: dizin/KAPALI.
 */
if (!class_exists('Idk_Onbellek', false)) {
    final class Idk_Onbellek
    {
        const TTL = 21600; // 6 saat
        const IZINLI_BASLIK = ['content-type', 'strict-transport-security', 'x-content-type-options', 'x-frame-options', 'referrer-policy', 'permissions-policy', 'link', 'x-robots-tag'];

        public static function dizin()
        {
            return dirname(__DIR__, 3) . '/cache/idk-sayfa';
        }

        public static function surum()
        {
            $s = @file_get_contents(self::dizin() . '/.surum');
            return $s === false ? '' : trim($s);
        }

        public static function kapali()
        {
            return is_file(self::dizin() . '/KAPALI');
        }

        /** İstek bu motorun kapsamında mı? (WordPress'siz kontroller) */
        public static function istek_uygun()
        {
            if (PHP_SAPI === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET' || !empty($_SERVER['QUERY_STRING'])) {
                return false;
            }
            $yol = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
            if ($yol === '' || $yol[0] !== '/' || substr($yol, -1) !== '/' || strpos($yol, '..') !== false) {
                return false;
            }
            if (preg_match('#^/(wp-admin|wp-json|wp-login|wp-content|wp-includes|go/|feed|xmlrpc|cart|checkout|my-account|shop)#i', $yol)) {
                return false;
            }
            foreach (array_keys($_COOKIE) as $k) {
                if (preg_match('/^(wordpress_logged_in|wordpress_sec|wp-postpass|comment_author|woocommerce_)/', $k)) {
                    return false;
                }
            }
            return !self::kapali();
        }

        public static function dosya()
        {
            $yol = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
            $anahtar = strtolower(($_SERVER['HTTP_HOST'] ?? '') . '|' . (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'h' : 's') . '|' . $yol);
            return self::dizin() . '/' . sha1($anahtar) . '.idk';
        }

        /** Taze kopya varsa sunar ve çıkar; yoksa sessizce döner. */
        public static function sun()
        {
            if (!self::istek_uygun()) {
                return;
            }
            $f = self::dosya();
            $ham = @file_get_contents($f);
            if ($ham === false || ($p = strpos($ham, "\n")) === false) {
                return;
            }
            $meta = json_decode(substr($ham, 0, $p), true);
            if (!is_array($meta) || (time() - (int) ($meta['t'] ?? 0)) > self::TTL || ($meta['s'] ?? '') !== self::surum()) {
                return;
            }
            foreach ((array) ($meta['h'] ?? []) as $h) {
                header($h);
            }
            header('X-Idk-Cache: HIT');
            echo substr($ham, $p + 1);
            exit;
        }

        /** Çıktı tamponu geri çağrısı: tam, 200 yanıtlı, çerezsiz HTML'yi kaydeder. */
        public static function kaydet($govde)
        {
            if (http_response_code() !== 200 || strpos($govde, '</html>') === false || defined('DONOTCACHEPAGE')) {
                return $govde;
            }
            $basliklar = [];
            foreach (headers_list() as $h) {
                $ad = strtolower(trim(strstr($h, ':', true)));
                if ($ad === 'set-cookie') {
                    return $govde;
                }
                if (in_array($ad, self::IZINLI_BASLIK, true)) {
                    $basliklar[] = $h;
                }
            }
            $d = self::dizin();
            if (!is_dir($d) && !@mkdir($d, 0755, true)) {
                return $govde;
            }
            $meta = json_encode(['t' => time(), 's' => self::surum(), 'h' => $basliklar]);
            $gecici = $d . '/' . uniqid('t', true) . '.tmp';
            if (@file_put_contents($gecici, $meta . "\n" . $govde) !== false) {
                @rename($gecici, self::dosya());
            }
            return $govde;
        }

        public static function temizle()
        {
            $n = 0;
            foreach ((array) glob(self::dizin() . '/*.idk') as $f) {
                if (@unlink($f)) {
                    $n++;
                }
            }
            return $n;
        }
    }
}
