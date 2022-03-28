<?php
namespace SIM;
use SMTPValidateEmail\Validator as SmtpEmailValidator;

//Shortcode for testing
add_shortcode("test",function ($atts){
	global $wpdb;
	//update all posts where this is attached
	$users = get_users();
    foreach($users as $user){

	}

	global $Modules;

	//$wpdb->query("ALTER TABLE `{$wpdb->prefix}sim_emails` CHANGE `reciepients` `recipients` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;"); 
	//$wpdb->query("ALTER TABLE `{$wpdb->prefix}sim_email_events` ADD `url` TEXT NOT NULL AFTER `time`;"); 
	//$wpdb->query("DROP TABLE `{$wpdb->prefix}sim_emails`");

	return '';
});