<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_action( 'wp_enqueue_scripts', function(){
    wp_register_script( 'sim_user_management', plugins_url('js/user_management.js', __DIR__), array('sim_formsubmit_script'), ModuleVersion,true);
});