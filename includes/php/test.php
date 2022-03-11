<?php
namespace SIM;

//Shortcode for testing
add_shortcode("test",function ($atts){
	global $wpdb;
	//update all posts where this is attached
	/* $users = get_users();
    foreach($users as $user){
	} */

	//Send e-mail
    $subject  = "Test fancy e-mail";
    $message = 'Hi, Ewald,<br>
							<br>
							How are you?<br>
							<br>
							
							<img src="'.USERMANAGEMENT\get_profile_picture_url(45).'" alt="picture">';
    wp_mail( 'enharmsen@gmail.com', $subject, $message);

	return '';
});