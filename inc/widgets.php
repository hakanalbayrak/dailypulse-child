<?php
/**
 * Widget alanları
 */
function dailypulse_widgets_init() {
    register_sidebar(array(
        'name'          => 'Blog Sidebar',
        'id'            => 'blog-sidebar',
        'before_widget' => '<div class="dp-widget">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="dp-widget-title">',
        'after_title'   => '</h4>',
    ));

    register_sidebar(array(
        'name'          => 'Post Sonrası CTA',
        'id'            => 'after-post-cta',
        'before_widget' => '<div class="dp-post-cta">',
        'after_widget'  => '</div>',
        'before_title'  => '',
        'after_title'   => '',
    ));
}
add_action('widgets_init', 'dailypulse_widgets_init');
