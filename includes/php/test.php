<?php
namespace SIM;

//Shortcode for testing
add_shortcode("test",function ($atts){
	global $Modules;

	$settings = array(
		'wpautop'                   => false,
		'media_buttons'             => false,
		'forced_root_block'         => true,
		'convert_newlines_to_brs'   => true,
		'textarea_name'             => "testname",
		'textarea_rows'             => 10
	);

	return wp_editor(
		'test',
		'testid',
		$settings
	);
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );