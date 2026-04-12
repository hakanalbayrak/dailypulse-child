<?php
/**
 * Customizer — proof bar rakamları ve site ayarları
 */
function dailypulse_customizer($wp_customize) {
    $wp_customize->add_section('dailypulse_settings', array(
        'title'    => 'Daily Pulse Ayarları',
        'priority' => 30,
    ));

    $fields = array(
        'dp_subscriber_count' => array('label' => 'Abone Sayısı',        'default' => '53K+'),
        'dp_monthly_reads'    => array('label' => 'Aylık Okuma',          'default' => '2.4M'),
        'dp_open_rate'        => array('label' => 'Email Açılma Oranı',   'default' => '%68'),
        'dp_post_count'       => array('label' => 'Blog Yazısı Sayısı',   'default' => '340+'),
    );

    foreach ($fields as $id => $field) {
        $wp_customize->add_setting($id, array(
            'default'           => $field['default'],
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control($id, array(
            'label'   => $field['label'],
            'section' => 'dailypulse_settings',
            'type'    => 'text',
        ));
    }
}
add_action('customize_register', 'dailypulse_customizer');
