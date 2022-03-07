<?php
namespace SIM\RECIPES;
use SIM;

add_action( 'wp_enqueue_scripts', function(){
    wp_register_script('sim_plurarize_script',plugins_url('js/recipe.min.js', __DIR__), array(), ModuleVersion,true);
});