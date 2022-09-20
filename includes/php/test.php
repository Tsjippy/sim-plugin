<?php
namespace SIM;

//Shortcode for testing
add_shortcode("test",function ($atts){
	global $Modules;

	$role = get_role( 'revisor' );
	foreach(['edit_others_posts', 'edit_others_pages','delete_others_pages','delete_others_posts','delete_mec-eventss','edit_users'] as $cap){
		$role->remove_cap( $cap );
	}
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );