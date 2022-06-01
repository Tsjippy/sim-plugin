<?php
namespace SIM\LOGIN;
use SIM;

/**
 * sends the password reset e-mail
 * 
 * @param	object	$user	WP_User
 * 
 * @param	true|WP_Error	true on success, error on failure
 */
function sendPasswordResetMessage($user){
	$key		 = get_password_reset_key($user);
	if(is_wp_error($key)) return $key;

	$pageurl	 = get_permalink(SIM\getModuleOption('login', 'password_reset_page'));
	$url		 = "$pageurl?key=$key&login=$user->user_login";

	//Send e-mail
	$mail	= new PasswordResetMail($user, $url);
	$mail->filterMail();
						
	$result				= wp_mail( $user->user_email, $mail->subject, $mail->message);

	if(!$result){
		return new \WP_Error('sending email failed', 'Sending e-mail failed');
	}
}