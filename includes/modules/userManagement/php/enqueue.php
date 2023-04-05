<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_action( 'wp_enqueue_scripts', function(){
    wp_register_style( 'sim_useraccount', plugins_url('css/account.min.css', __DIR__), array(), MODULE_VERSION);

    wp_register_script( 'sim_user_management', plugins_url('js/user_management.min.js', __DIR__), array('sim_formsubmit_script'), MODULE_VERSION, true);
}, 99);