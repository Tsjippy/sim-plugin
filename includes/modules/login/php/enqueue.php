<?php
namespace SIM\LOGIN;
use SIM;

add_action( 'wp_enqueue_scripts', function(){
    if(!is_user_logged_in()){
	    //login form
	    wp_register_style( 'sim_login_style', plugins_url('css/login.min.css', __DIR__), array(), ModuleVersion);
        wp_enqueue_style( 'sim_login_style');
        
        wp_enqueue_script('sim_login_script', plugins_url('js/login.min.js', __DIR__), array('sim_script'), ModuleVersion, true);
    }else{
        wp_enqueue_script('sim_logout_script', plugins_url('js/logout.min.js', __DIR__), array(), ModuleVersion, true);
    }

	wp_register_script('sim_2fa_script', plugins_url('js/2fa.min.js', __DIR__), array('sim_other_script','sim_table_script'), ModuleVersion, true);
});