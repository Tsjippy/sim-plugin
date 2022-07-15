<?php
namespace SIM\MEDIAGALLERY;
use SIM;

add_action( 'save_post', function($postId, $post){
    if(has_shortcode($post->post_content, 'mediagallery')){
        global $Modules;

        $Modules[MODULE_SLUG]['mediagallery_pages'][]  = $postId;
		update_option('sim_modules', $Modules);
    }
}, 10, 2);

add_action( 'wp_enqueue_scripts', function(){
    wp_register_style( 'sim_gallery_style', plugins_url('css/media_gallery.min.css', __DIR__), array(), MODULE_VERSION);

    wp_register_script('sim_gallery_script', plugins_url('js/media_gallery.min.js', __DIR__), array('sim_formsubmit_script'), MODULE_VERSION, true);

    $pages   = SIM\getModuleOption(MODULE_SLUG, 'mediagallery_pages');
    if(in_array(get_the_ID(), $pages)){
        wp_enqueue_style('sim_gallery_style');
		wp_enqueue_script('sim_gallery_script');
    }
});