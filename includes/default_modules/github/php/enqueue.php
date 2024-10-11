<?php
namespace SIM\GITHUB;
use SIM;

add_action( 'wp_enqueue_scripts', function(){
    wp_register_style( 'sim_XXX_style', plugins_url('css/XXX.min.css', __DIR__), array(), MODULE_VERSION);

    // We cannot use the minified version as the dynamic js files depend on the function names
    wp_register_script('sim_XXXX_script',plugins_url('js/XXXX.js', __DIR__), array('sweetalert'), MODULE_VERSION,true);

    wp_enqueue_style('sim_XXX_style');
});