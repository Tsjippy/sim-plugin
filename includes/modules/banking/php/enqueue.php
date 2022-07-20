<?php
namespace SIM\BANKING;
use SIM;

//load js and css
add_action( 'wp_enqueue_scripts', function(){
    wp_register_style('sim_account_statements_style', plugins_url('css/banking.min.css', __DIR__), array(), MODULE_VERSION);
	wp_register_script('sim_account_statements_script', plugins_url('js/account_statements.min.js', __DIR__), array(), MODULE_VERSION, true);
});