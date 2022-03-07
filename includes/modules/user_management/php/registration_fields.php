<?php
namespace SIM\USERMANAGEMENT;
use SIM;

function get_available_username($first_name=null, $last_name=null){
	if($first_name == null) $first_name = ( !empty( $_POST['first_name'] ) ) ? sanitize_text_field( $_POST['first_name'] ) : '';
	if($last_name == null)	$last_name = ( !empty( $_POST['last_name'] ) ) ? sanitize_text_field( $_POST['last_name'] ) : '';
	
	//Check if a user with this username already exists
	$i =1;
	while (true){
		//Create a username
		$username = str_replace(' ', '', $first_name.substr($last_name, 0, $i));
		//Check for availability
		if (get_user_by("login",$username) == ""){
			//available, return the username
			return $username;
		}
		$i += 1;
	}
}

//Change the registration form
add_action( 'register_form', 'register_form' );
function register_form() {
	//Get the default form contents
	$content = ob_get_contents();
	
	//Check if values are defined in post
	$first_name = ( ! empty( $_POST['first_name'] ) ) ? sanitize_text_field( $_POST['first_name'] ) : '';
	$last_name = ( ! empty( $_POST['last_name'] ) ) ? sanitize_text_field( $_POST['last_name'] ) : '';
	
	//Define new content, auto fill username based on first and last name
	$extra_content = '
	<p>
		<label for="first_name">First Name<br />
		<input type="text" name="first_name" id="first_name" class="input" value="'.esc_attr(  $first_name  ).'" size="25" /></label>
	</p>
	<p>
		<label for="last_name">Last Name<br />
		<input type="text" name="last_name" id="last_name" class="input" value="'.esc_attr(  $last_name  ).'" size="25" /></label>
	</p>
	<p style="display:none;">';
	
	//Update the default content with new content
	$content = str_replace ( '<label for="user_login">', $extra_content, $content );	
	ob_get_clean();
	
	//Display content
	echo $content;
}

//Check if first and last name are filled.
add_filter( 'registration_errors', 'registration_errors', 10, 3 );
function registration_errors( $errors, $sanitized_user_login, $user_email ) {
	if ( empty( $_POST['first_name'] ) || ! empty( $_POST['first_name'] ) && trim( $_POST['first_name'] ) == '' ) {
		$errors->add( 'first_name_error', sprintf('<strong>%s</strong>: %s','Error','Please enter your first name.' ) );

	}
	if ( empty( $_POST['last_name'] ) || ! empty( $_POST['last_name'] ) && trim( $_POST['last_name'] ) == '' ) {
		$errors->add( 'last_name_error', sprintf('<strong>%s</strong>: %s','Error','Please enter your last name.' ) );

	}
	
	return $errors;
}

//Save first and last name
add_action( 'user_register', 'user_register' );
function user_register( $user_id ) {
	$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
	$last_name = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
			
	if ( !empty( $first_name ) and !empty( $last_name )) {
		update_user_meta( $user_id, 'first_name', $first_name );
		update_user_meta( $user_id, 'last_name', $last_name );

		$name = $first_name." ".$last_name;
		$args = array(
			'ID'           => $user_id,
			'display_name' => $name,
			'nickname'     => $name
		);
		wp_update_user( $args );
	}
}

/**
 * Custom register email
 */
add_filter( 'wp_new_user_notification_email', 'new_user_notification_email', 10, 3 );
function new_user_notification_email( $wp_new_user_notification_email, $user, $blogname ) {
	global $WebmasterName;
	global $Modules;

	//Set variables
    $user_login = stripslashes( $user->user_login );
    $user_email = stripslashes( $user->user_email );
	
	//If a valid email
	if ($user_email != "" and strpos($user_email, '.empty') === false){
		$user_name	= stripslashes( $user->display_name );
		$reset_key	= get_password_reset_key($user);
		$pageurl	= get_permalink($Modules['login']['pw_reset_page']);
		$login_url	= "$pageurl?key=$reset_key&login=$user_login";
		
		//Create message
		$message  = "Hi $user_name,<br><br>";
		$message .= sprintf( __( "We have created an account for you on " ), $blogname ) . "<br>";
		$message .= "Please set a password and login using this <a href='$login_url'>link</a>.<br>";
		$message .= sprintf( __('Your username is: %s'), $user_login ) . "<br><br>";
		
		$validity = get_user_meta( $user->ID, 'account_validity',true);
		if(is_numeric($validity)){
			$message .= "Your account will be active for $validity months after you first login.<br><br>";
		}
		$message .= 'If you have any problems, please contact me by replying to this e-mail.<br><br>';
		$message .= __( "Regards,<br>$WebmasterName<br>" );
		$message .= __( "Webmaster" );
	 
		$wp_new_user_notification_email['subject'] = sprintf( __( "Your account on %s" ), $blogname );
		$wp_new_user_notification_email['message'] = $message;
	 
		return $wp_new_user_notification_email;
	}else{
		return false;
	}
}

//Only allows certain mail domains
add_action('register_post', 'is_valid_email_domain',10,3 );
function is_valid_email_domain($login, $email, $errors ){
	$invalid_email_domains = array(".ru");// allowed domains
	$valid = true; // sets default validation to false
	foreach( $invalid_email_domains as $invalid_email_domain ){
		$d_length = strlen( $invalid_email_domain );
		$current_email_domain = strtolower( substr( $email, -($d_length), $d_length));
		if( $current_email_domain == strtolower($invalid_email_domain) ){
			$valid = false;
			break;
		}
	}
	// Return error message for invalid domains
	if( $valid === false ){
		$errors->add('domain_whitelist_error',__( '<strong>ERROR</strong>: Registration is only allowed from selected approved domains.' ));
	}
}
