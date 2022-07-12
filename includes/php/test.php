<?php
namespace SIM;

use SIM\FORMS\Formbuilder;
use SMTPValidateEmail\Validator as SmtpEmailValidator;

//Shortcode for testing
add_shortcode("test",function ($atts){
	global $wpdb;
	/* //update all posts where this is attached
 	$users = get_users();
    foreach($users as $user){
		$pageID	= get_user_meta($user->ID, "missionary_page_id", true);
		delete_user_meta($user->ID, "missionary_page_id");

		update_user_meta($user->ID, "user_page_id", $pageID);
	}

	$posts=get_posts(array(
		'meta_key'=>'missionary_id',
		'post_type'		=> 'any',
		'numberposts'	=> -1

	));

	foreach($posts as $post){
		$id	= get_post_meta($post->ID, 'missionary_id', true);
		update_post_meta($post->ID, 'user_id', $id);
		delete_post_meta($post->ID, 'missionary_id');
	} */

	//$wpdb->query("ALTER TABLE `{$wpdb->prefix}sim_emails` CHANGE `reciepients` `recipients` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;"); 
	//$wpdb->query("ALTER TABLE `{$wpdb->prefix}sim_email_events` ADD `url` TEXT NOT NULL AFTER `time`;"); 
	//$wpdb->query("DROP TABLE `{$wpdb->prefix}sim_emails`");

	BANKING\testMailImport();

	return '';
});