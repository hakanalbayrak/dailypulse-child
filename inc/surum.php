<?php
/**
 * Deploy stamp, rewritten by scripts/tema-deploy.py on every deploy and
 * printed in <head> so the deploy check can prove the pushed version is the
 * one actually running (not just that "a" child theme is running).
 */
if (!defined('ABSPATH')) exit;
define('KAMPANYA_TEMA_SURUM', '20261006-075054');
add_action('wp_head', function () {
    echo '<meta name="kampanya-tema-surum" content="' . esc_attr(KAMPANYA_TEMA_SURUM) . '" />' . "\n";
}, 2);
