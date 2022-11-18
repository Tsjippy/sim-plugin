<?php
namespace SIM\LOGIN;
use SIM;

add_action( 'wp_enqueue_scripts', function(){
    if(!is_user_logged_in()){
	    //login form
	    wp_register_style( 'sim_login_style', plugins_url('css/login.min.css', __DIR__), array(), MODULE_VERSION);
        wp_enqueue_style( 'sim_login_style');

        wp_enqueue_script('sim_login_script', plugins_url('js/login.min.js', __DIR__), array('sim_script', 'sim_purify'), MODULE_VERSION, true);
    }else{
        wp_enqueue_script('sim_logout_script', plugins_url('js/logout.min.js', __DIR__), array(), MODULE_VERSION, true);
    }

    wp_register_style( 'sim_pw_reset_style', plugins_url('css/pw_reset.min.css', __DIR__), array(), MODULE_VERSION);

    wp_register_script('sim_password_strength_script', plugins_url('js/password_strength.min.js', __DIR__), array('password-strength-meter'), MODULE_VERSION,true);

	wp_register_script('sim_2fa_script', plugins_url('js/2fa.min.js', __DIR__), array('sim_table_script'), MODULE_VERSION, true);

    $passwordResetPage  = SIM\getModuleOption(MODULE_SLUG, 'password_reset_page');
    $registerPage       = SIM\getModuleOption(MODULE_SLUG, 'register_page');
    if(get_the_ID() == $passwordResetPage || get_the_ID() == $registerPage){
        wp_enqueue_style('sim_pw_reset_style');

	    wp_enqueue_script('sim_password_strength_script');
    }

    if(in_array(get_the_ID(), SIM\getModuleOption(MODULE_SLUG, '2fa_page', false))){
        wp_enqueue_script('sim_2fa_script');
    }
});