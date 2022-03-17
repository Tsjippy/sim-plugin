<?php
namespace SIM;
use SMTPValidateEmail\Validator as SmtpEmailValidator;

//Shortcode for testing
add_shortcode("test",function ($atts){
	global $wpdb;
	//update all posts where this is attached
	/* $users = get_users();
    foreach($users as $user){
	} */

	global $Modules;

	//Send e-mail
    $subject  = "Test fancy e-mail";
    $message = 'Hi, Ewald,<br>
							<br>
							How are you?<br>
							<br>
							
							<img src="'.USERMANAGEMENT\get_profile_picture_url(45).'" alt="picture">';
    wp_mail( 'enhn@gmail.com', $subject, $message,['From: Me Myself <post@simnigeria.org>']);

	//$wpdb->query("ALTER TABLE `{$wpdb->prefix}sim_emails` CHANGE `reciepients` `recipients` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;"); 
	//$wpdb->query("ALTER TABLE `{$wpdb->prefix}sim_email_events` ADD `url` TEXT NOT NULL AFTER `time`;"); 
	//$wpdb->query("DROP TABLE `{$wpdb->prefix}sim_emails`");

	return '';
});