<?php
namespace SIM\MANDATORY;
use SIM;

add_action( 'wp_enqueue_scripts', function(){
    wp_register_style('sim_mandatory_style', plugins_url('css/mandatory.min.css', __DIR__), array(), ModuleVersion);
    wp_register_script('sim_mandatory_script', plugins_url('js/mandatory.min.js', __DIR__), array('sim_formsubmit_script'), ModuleVersion,true);
});