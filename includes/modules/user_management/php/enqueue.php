<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Password strength js
add_action( 'wp_enqueue_scripts', function(){
    wp_register_script('sim_password_strength_script',plugins_url('js/account/password_strength.js', __DIR__),array('password-strength-meter', 'sim_other_script'), ModuleVersion,true);
});