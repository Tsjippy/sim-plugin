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
    $userId    = $_GET['send_activation_email'];
	if(is_numeric($userId )){
		$email = get_userdata($userId )->user_email;
		SIM\printArray("Sending welcome email to $email");
		wp_new_user_notification($userId, null, 'user');
	}
});

//Apply our e-mail settings
add_filter( 'wp_new_user_notification_email', function($args, $user){
	$key		 = get_password_reset_key($user);
	if(is_wp_error($key)) return $key;

	$pageurl	 = get_permalink(SIM\get_module_option('login', 'password_reset_page'));
	$url		 = "$pageurl?key=$key&login=$user->user_login";

	if(get_user_meta($user->ID, 'disabled', true) == 'pending'){
		$mail = new AccountApproveddMail($user, $url);
		$mail->filterMail();
	}else{
		$mail = new AccountCreatedMail($user, $url);
		$mail->filterMail();
	}

	$args['subject']	= $mail->subject;
	$args['message']	= $mail->message;

	return $args;
}, 10, 2);