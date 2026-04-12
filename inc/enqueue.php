<?php
/**
 * Stil ve script yükleme
 */
function dailypulse_enqueue_assets() {
    wp_enqueue_style(
        'dailypulse-custom',
        DAILYPULSE_URI . '/assets/css/custom.css',
        array(),
        DAILYPULSE_VERSION
    );

    wp_enqueue_script(
        'dailypulse-custom',
        DAILYPULSE_URI . '/assets/js/custom.js',
        array(),
        DAILYPULSE_VERSION,
        true
    );

    wp_localize_script('dailypulse-custom', 'dpAjax', array(
        'url'   => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dp_subscribe_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'dailypulse_enqueue_assets');
