<?php
namespace SIM\LOGIN;
use SIM;

add_action( 'save_post', function($post_ID, $post){
    if(has_shortcode($post->post_content, 'formbuilder')){
        global $Modules;

        if(!is_array($Modules['forms']['formbuilder_pages'])){
            $Modules['forms']['formbuilder_pages']    = [$post_ID];
        }elseif(!in_array($post_ID, $Modules['forms']['formbuilder_pages'])){
            $Modules['forms']['formbuilder_pages'][]  = $post_ID;
        }

        update_option('sim_modules', $Modules);
    }
}, 10, 2);

add_action( 'wp_enqueue_scripts', function(){
    if(!is_user_logged_in()){
	    //login form
	    wp_register_style( 'sim_login_style', plugins_url('css/login.min.css', __DIR__), array(), ModuleVersion);
        wp_enqueue_style( 'sim_login_style');
        
        wp_enqueue_script('sim_login_script', plugins_url('js/login.min.js', __DIR__), array('sim_script', 'sim_purify'), ModuleVersion, true);
    }else{
        wp_enqueue_script('sim_logout_script', plugins_url('js/logout.min.js', __DIR__), array(), ModuleVersion, true);
    }

    wp_register_style( 'sim_pw_reset_style', plugins_url('css/pw_reset.min.css', __DIR__), array(), ModuleVersion);

    wp_register_script('sim_password_strength_script', plugins_url('js/password_strength.min.js', __DIR__), array('password-strength-meter'), ModuleVersion,true);

	wp_register_script('sim_2fa_script', plugins_url('js/2fa.min.js', __DIR__), array('sim_table_script'), ModuleVersion, true);

    $passwordResetPage    = SIM\getModuleOption('login', 'password_reset_page');
    $registerPage          = SIM\getModuleOption('login', 'register_page');
    if(get_the_ID() == $passwordResetPage or get_the_ID() == $registerPage){
        wp_enqueue_style('sim_pw_reset_style');
        
	    wp_enqueue_script('sim_password_strength_script');
    }
});