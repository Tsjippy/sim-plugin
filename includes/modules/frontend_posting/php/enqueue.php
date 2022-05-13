<?php
namespace SIM\FRONTEND_POSTING;
use SIM;

add_action( 'save_post', function($post_ID, $post){
    if(has_shortcode($post->post_content, 'front_end_post')){
        global $Modules;

        if(!is_array($Modules['frontend_posting']['front_end_post_pages'])){
            $Modules['frontend_posting']['front_end_post_pages']    = [$post_ID];
        }else{
            $Modules['frontend_posting']['front_end_post_pages'][]  = $post_ID;
        }

        update_option('sim_modules', $Modules);
    }
}, 10, 2);

add_action( 'wp_enqueue_scripts', function ($hook) {
    wp_register_style('sim_frontend_style', plugins_url('css/frontend_posting.min.css', __DIR__), array(), ModuleVersion);
	
    //Load js
	wp_register_script('sim_frontend_script', plugins_url('js/frontend_posting.min.js', __DIR__), array('sim_fileupload_script', 'sim_formsubmit_script'), ModuleVersion, true);

    $frontEndPostPages   = SIM\get_module_option('frontend_posting', 'publish_post_page');
    if(get_the_ID() == $frontEndPostPages){
        wp_enqueue_style('sim_frontend_style');
    }
});