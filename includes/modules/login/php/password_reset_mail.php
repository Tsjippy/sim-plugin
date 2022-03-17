<?php
namespace SIM\LOGIN;
use SIM;

add_action('wp_ajax_nopriv_request_pwd_reset', function(){
    $username   = sanitize_text_field($_POST['username']);

    if(empty($username))   wp_die('No username given', 500);

	$user	= get_user_by('login', $username);
    if(!$user)	wp_die('Invalid username', 500);

	$email  = $user->user_email;
    if(!$email or strpos('.empty', $email) !== false) wp_die("No valid e-mail found for user $username");

	$result = send_password_reset_message($user);

    if(is_wp_error($result)){
        wp_die( $result->get_error_message(), 500);
    }
    
	wp_die("Password reset link send to $email");
}); 

//sends the password reset e-mail
function send_password_reset_message($user){
	$key		 = get_password_reset_key($user);
	if(is_wp_error($key)) return $key;

	$pageurl	 = get_permalink(SIM\get_module_option('login', 'password_reset_page'));
	$url		 = "$pageurl?key=$key&login=$user->user_login";

	//Send e-mail
	$passwordResetMail	= new PasswordResetMail($user, $url);
	$passwordResetMail->filterMail();
						
	$result				= wp_mail( $user->user_email, $passwordResetMail->subject, $passwordResetMail->message);

	if(!$result){
		return new \WP_Error('sending email failed', 'Sending e-mail failed');
	}
}