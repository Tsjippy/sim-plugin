<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Add link to the user menu to resend the confirmation e-mail
add_filter( 'user_row_actions', function ( $actions, $user ) {
    $actions['Resend welcome mail'] = "<a href='".SITEURL."/wp-admin/users.php?send_activation_email=$user->ID'>Resend welcome email</a>";
    return $actions;
}, 10, 2 );

add_action('admin_menu', function() {
	//Process the request
	if(!empty($_GET['send_activation_email']) && is_numeric($_GET['send_activation_email'] )){
		$userId    = $_GET['send_activation_email'];
		$email = get_userdata($userId )->user_email;
		SIM\printArray("Sending welcome email to $email");
		wp_new_user_notification($userId, null, 'user');
	}
});

//Apply our e-mail settings
add_filter( 'wp_new_user_notification_email', function($args, $user){
	
	$expirationDuration	= apply_filters( 'password_reset_expiration', DAY_IN_SECONDS );
	$key		 		= get_password_reset_key($user);
	if(is_wp_error($key)){
		return $key;
	}
	$validTill			= time()+$expirationDuration;

	$format				= get_option('date_format').' '.get_option('time_format');

	$validTillString	= date($format, $validTill);

	$pageUrl	= get_permalink(SIM\getModuleOption('login', 'password_reset_page')[0]);
	$url		= "$pageUrl?key=$key&login=$user->user_login";

	if(get_user_meta($user->ID, 'disabled', true) == 'pending'){
		$mail = new AccountApproveddMail($user, $url, $validTillString);
		$mail->filterMail();
	}else{
		$mail = new AccountCreatedMail($user, $url, $validTillString);
		$mail->filterMail();
	}

	$args['subject']	= $mail->subject;
	$args['message']	= $mail->message;

	return $args;
}, 10, 2);