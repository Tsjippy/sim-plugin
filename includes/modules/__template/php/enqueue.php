<?php
namespace SIM\ADMIN;
use SIM;

add_action( 'wp_after_insert_post', function($post_ID, $post){
    if(has_shortcode($post->post_content, 'formbuilder') || has_shortcode($post->post_content, 'formselector')){
        global $Modules;

        if(!is_array($Modules['forms']['formbuilder_pages'])){
            $Modules['forms']['formbuilder_pages']    = [$post_ID];
        }elseif(!in_array($post_ID, $Modules['forms']['formbuilder_pages'])){
            $Modules['forms']['formbuilder_pages'][]  = $post_ID;
        }

        update_option('sim_modules', $Modules);
    }
}, 10, 2);

add_action( 'wp_trash_post', function($postId){
    global $Modules;
    $index  = array_search($postId, $Modules[MODULE_SLUG]['formbuilder_pages']);
    if($index){
        unset($Modules[MODULE_SLUG]['formbuilder_pages'][$index]);
        $Modules[MODULE_SLUG]['formbuilder_pages']   = array_values($Modules[MODULE_SLUG]['formbuilder_pages']);
        update_option('sim_modules', $Modules);
    }
} );

add_action( 'wp_enqueue_scripts', function(){
    wp_register_style( 'sim_XXX_style', plugins_url('css/XXX.min.css', __DIR__), array(), MODULE_VERSION);

    // We cannot use the minified version as the dynamic js files depend on the function names
    wp_register_script('sim_XXXX_script',plugins_url('js/XXXX.js', __DIR__), array('sweetalert'), MODULE_VERSION,true);

    $formbuilderPages   = SIM\getModuleOption('XXX', 'XXX_pages');
    if(is_numeric(get_the_ID()) && in_array(get_the_ID(), $formbuilderPages)){
        wp_enqueue_style('sim_XXX_style');
    }
});