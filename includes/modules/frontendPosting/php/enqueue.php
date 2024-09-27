<?php
namespace SIM\FRONTENDPOSTING;
use SIM;

add_action( 'wp_after_insert_post', function($postId, $post){
    if(has_shortcode($post->post_content, 'front_end_post')){
        global $Modules;

        if(!is_array($Modules[MODULE_SLUG]['front_end_post_pages'])){
            $Modules[MODULE_SLUG]['front_end_post_pages']    = [$postId];
        }else{
            $Modules[MODULE_SLUG]['front_end_post_pages'][]  = $postId;
        }

        update_option('sim_modules', $Modules);
    }
}, 10, 2);

add_action( 'wp_trash_post', function($postId){
    global $Modules;
    $index  = array_search($postId, $Modules[MODULE_SLUG]['front_end_post_pages']);
    if($index){
        unset($Modules[MODULE_SLUG]['front_end_post_pages'][$index]);
        $Modules[MODULE_SLUG]['front_end_post_pages']   = array_values($Modules[MODULE_SLUG]['front_end_post_pages']);
        update_option('sim_modules', $Modules);
    }
} );

add_action( 'wp_enqueue_scripts', function () {

    wp_register_style('sim_frontend_style', plugins_url('css/frontend_posting.min.css', __DIR__), array(), MODULE_VERSION);
	
    //Load js
    wp_register_script('sim_forms_script', plugins_url('../forms/js/forms.min.js', __DIR__), array('sweetalert', 'sim_formsubmit_script'), MODULE_VERSION,true);

    $dependables    = apply_filters('sim-frontend-content-js', array('sim_fileupload_script', 'sim_forms_script'));
	wp_register_script('sim_frontend_script', plugins_url('js/frontend_posting.min.js', __DIR__), $dependables, MODULE_VERSION, true);

    wp_enqueue_script('sim_edit_post_script', plugins_url('js/edit_post.min.js', __DIR__), array('sim_formsubmit_script'), MODULE_VERSION, true);
    
    $frontendEditUrl    = SIM\getValidPageLink(SIM\getModuleOption(MODULE_SLUG, 'front_end_post_pages'));
    wp_add_inline_script('sim_edit_post_script', "var edit_post_url = '$frontendEditUrl'", 'before');

    $frontEndPostPages   = SIM\getModuleOption(MODULE_SLUG, 'front_end_post_pages');
    if(is_numeric(get_the_ID()) && in_array(get_the_ID(), $frontEndPostPages)){
        wp_enqueue_style('sim_frontend_style');
    }
});

add_action( 'wp_enqueue_media', function(){
    wp_enqueue_script('sim_library_cat_script', plugins_url('js/library.min.js', __DIR__), [], MODULE_VERSION);
    wp_localize_script(
        'sim_library_cat_script',
        'categories',
        get_categories( array(
            'taxonomy'		=> 'attachment_cat',
            'hide_empty' 	=> false
        ) )
    );
});