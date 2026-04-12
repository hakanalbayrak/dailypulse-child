<?php
/**
 * Shortcode'lar
 */

// [dp_subscribe style="orange|yellow|green" text="Abone Ol"]
function dailypulse_subscribe_shortcode($atts) {
    $atts = shortcode_atts(array(
        'style' => 'orange',
        'text'  => 'Abone Ol',
    ), $atts);

    $btn_class = 'dp-btn-' . esc_attr($atts['style']);

    ob_start(); ?>
    <form class="dp-subscribe-form" style="display:flex;gap:12px;max-width:480px;margin:16px auto;" action="#" method="post">
        <input type="email" name="email" placeholder="E-posta adresiniz" required style="flex:1;">
        <button type="submit" class="<?php echo $btn_class; ?>"><?php echo esc_html($atts['text']); ?></button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('dp_subscribe', 'dailypulse_subscribe_shortcode');

// [dp_proof_bar]
function dailypulse_proof_bar_shortcode() {
    ob_start();
    get_template_part('template-parts/proof-bar');
    return ob_get_clean();
}
add_shortcode('dp_proof_bar', 'dailypulse_proof_bar_shortcode');

// [dp_post_cta title="..." text="..."]
function dailypulse_post_cta_shortcode($atts) {
    $atts = shortcode_atts(array(
        'title' => 'Bu Yazıyı Beğendiniz mi?',
        'text'  => 'Her hafta benzer içerikler için abone olun.',
    ), $atts);

    ob_start(); ?>
    <div class="dp-post-cta">
        <h3><?php echo esc_html($atts['title']); ?></h3>
        <p><?php echo esc_html($atts['text']); ?></p>
        <form class="dp-subscribe-form" style="display:flex;gap:12px;max-width:400px;margin:0 auto;" action="#" method="post">
            <input type="email" name="email" placeholder="E-posta adresiniz" required style="flex:1;">
            <button type="submit" class="dp-btn-orange">Abone Ol</button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('dp_post_cta', 'dailypulse_post_cta_shortcode');

// AJAX handler — FluentCRM entegrasyonu
function dailypulse_ajax_subscribe() {
    check_ajax_referer('dp_subscribe_nonce', 'nonce');

    $email = sanitize_email($_POST['email'] ?? '');
    $name  = sanitize_text_field($_POST['name'] ?? '');

    if (!is_email($email)) {
        wp_send_json_error(array('message' => 'Geçersiz e-posta adresi.'));
    }

    // FluentCRM varsa abone ekle
    if (function_exists('FluentCrmApi')) {
        $subscriber_data = array(
            'email'  => $email,
            'status' => 'subscribed',
        );
        if (!empty($name)) {
            $parts = explode(' ', $name, 2);
            $subscriber_data['first_name'] = $parts[0];
            if (!empty($parts[1])) $subscriber_data['last_name'] = $parts[1];
        }
        $api = FluentCrmApi('contacts');
        $api->createOrUpdate($subscriber_data);
    }

    wp_send_json_success(array('message' => 'Başarıyla abone oldunuz!'));
}
add_action('wp_ajax_dp_subscribe', 'dailypulse_ajax_subscribe');
add_action('wp_ajax_nopriv_dp_subscribe', 'dailypulse_ajax_subscribe');
