<?php
namespace SIM;

//Shortcode for testing
add_shortcode("test",function ($atts){
	global $Modules;

	wp_update_plugins();
	$test=new FORMS\EditFormResults();

	$test->autoArchive();
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );