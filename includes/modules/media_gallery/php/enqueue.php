<?php
namespace SIM\MEDIAGALLERY;
use SIM;

add_action( 'save_post', function($post_ID, $post){
    if(has_shortcode($post->post_content, 'mediagallery')){
        global $Modules;

        if(!is_array($Modules['mediagallery']['mediagallery_pages'])){
            $Modules['mediagallery']['mediagallery_pages']    = [$post_ID];
			update_option('sim_modules', $Modules);
        }elseif(!in_array($post_ID, $Modules['mediagallery']['mediagallery_pages'])){
            $Modules['mediagallery']['mediagallery_pages'][]  = $post_ID;
			update_option('sim_modules', $Modules);
        }
    }
}, 10, 2);

add_action( 'wp_enqueue_scripts', function(){
    wp_register_style( 'sim_gallery_style', plugins_url('css/media_gallery.min.css', __DIR__), array(), ModuleVersion);

    wp_register_script('sim_gallery_script', plugins_url('js/media_gallery.min.js', __DIR__), array('sim_formsubmit_script'), ModuleVersion, true);

    $pages   = SIM\get_module_option('mediagallery', 'mediagallery_pages');
    if(in_array(get_the_ID(), $pages)){
        wp_enqueue_style('sim_gallery_style');
		wp_enqueue_script('sim_gallery_script');
    }
});