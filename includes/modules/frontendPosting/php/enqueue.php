<?php
namespace SIM\FRONTENDPOSTING;
use SIM;

add_action( 'save_post', function($postId, $post){
    if(has_shortcode($post->post_content, 'front_end_post')){
        global $Modules;

        if(!is_array($Modules['frontend_posting']['front_end_post_pages'])){
            $Modules['frontend_posting']['front_end_post_pages']    = [$postId];
        }else{
            $Modules['frontend_posting']['front_end_post_pages'][]  = $postId;
        }

        update_option('sim_modules', $Modules);
    }
}, 10, 2);

add_action( 'wp_enqueue_scripts', function () {
    wp_register_style('sim_frontend_style', plugins_url('css/frontend_posting.min.css', __DIR__), array(), MODULE_VERSION);
	
    //Load js
    wp_register_script('sim_forms_script', plugins_url('../forms/js/forms.min.js', __DIR__), array('sweetalert', 'sim_formsubmit_script'), MODULE_VERSION,true);

	wp_register_script('sim_frontend_script', plugins_url('js/frontend_posting.min.js', __DIR__), array('sim_fileupload_script', 'sim_forms_script'), MODULE_VERSION, true);

    $frontEndPostPages   = SIM\getModuleOption(MODULE_SLUG, 'publish_post_page');
    if(get_the_ID() == $frontEndPostPages){
        wp_enqueue_style('sim_frontend_style');
    }
});