<?php
namespace SIM\USERMANAGEMENT;
use SIM;

use function SIM\get_module_option;

//check if account is disabled
add_filter( 'wp_authenticate_user', function($user, $password){
	if ( 
		!is_wp_error( $user ) and 									// there is no error
		get_user_meta( $user->ID, 'disabled', true ) and 			// the account is disabled
		get_bloginfo( 'admin_email' ) !== $user->user_email			// this is not the main admin
	) {
		$user = new \WP_Error(
			'account_disabled_error',
			'Your account has been disabled for safety reasons.<br>Contact the office if you want to have it enabled again.' 
		);
	}

	return $user;
},10,2);

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

	$pageurl	 = get_permalink(SIM\get_module_option('login', 'pw_reset_page'));
	$url		 = "$pageurl?key=$key&login=$user->user_login";

	$message	 = "Hi $user->first_name<br>";
	$message	.= "<br>";
	$message	.= "Someone requested a password reset for you.<br>";
	$message	.= "If that was not you, please ignore this e-mail.<br>";
	$message	.= "Otherwise, follow this <a href='$url'>link</a> to reset your password.<br>";
	$message	.= "<br><br>";
	$message	.= 'Kind regards,<br><br>';

	if(!wp_mail($user->user_email, 'Password reset requested', $message)){
		return new \WP_Error('sending email failed', 'Sending e-mail failed');
	}
}

function password_reset_form($user){
	//Load js
	wp_enqueue_script('sim_password_strength_script');

	if(get_current_user_id() == $user->id or !is_user_logged_in()){
		$message		 = "Change your password using the fields below.<br>";
		$message		.= "<br>Your username is $user->user_login.";
	}else{
		$message		 = "Change the password for $user->display_name using the fields below.<br>";
		$message		.= "<br>Username is $user->user_login.";
	}

	ob_start();
	?>
	<style>
		.bad{
			background: red;
			border: none;
			color: #000;
		}
		.short{
			background: #f1adad;
			border: none;
			color: #707071;
		}
		.good{
			background: #ffe399;
			border: none;
			color: #000;
		}
		.strong{
			background: #c1e1b9;
			border: none;
			color: #000;
		}
	</style>

	<form data-reset='true' class='sim_form'>
		<div class="login_info">
			<input type="hidden" name="password_reset_nonce"	value="<?php echo wp_create_nonce("password_reset_nonce");?>">
			<input type="hidden" name="userid"					value="<?php echo $user->ID; ?>">
			<input type="hidden" name="action"					value="update_password">
			
			<p style="margin-top:30px;">
				<?php echo $message;?>
			</p>
			<label>
				New Password<br>
				<input type="password" class='changepass' name="pass1" size="16" autocomplete="off" required/>
			</label>
			<br>
			<span style="text-align: center;" class="pass-strength-result hidden" id="pass-strength-result1">Strength indicator</span>
			<br>
			<br>
			<label>
				Confirm New Password<br>
				<input type="password" class='changepass' name="pass2" size="16" autocomplete="off" required/>
			</label>
			<br>
			<span style="text-align: center;" class="pass-strength-result hidden" id="pass-strength-result2">Strength indicator</span>
		</div>
		<?php echo SIM\add_save_button('update_password', 'Change password');?>
	</form>
	
	<?php
	return ob_get_clean();
}

function change_password_field($user_id = null){
	if(is_numeric($user_id)){
		$user		= get_userdata($user_id);
	}else{
		$user		= wp_get_current_user();
	}
	$name		= $user->display_name;
	$disabled	= get_user_meta( $user->ID, 'disabled', true );
	if($disabled){
		$action_text	= 'enable';
	}else{
		$action_text	= 'disable';
	}
	
	ob_start();
	//Check if action is needed
	if(isset($_GET['action']) and isset($_GET['wp_2fa_nonce'])){
		if($_GET['action'] == 'reset2fa' and wp_verify_nonce( $_GET['wp_2fa_nonce'], "wp-2fa-reset-nonce_".$_GET['user_id'])){
			if($_GET['do'] == 'off'){
				SIM\LOGIN\reset_2fa($user_id);
				echo "<div class='success'>Succesfully turned off 2fa for $name</div>";
			}elseif($_GET['do'] == 'email'){
				update_user_meta($user_id, '2fa_methods', ['email']);
				echo "<div class='success'>Succesfully changed the 2fa factor for $name to e-mail</div>";
			}
		}
	}
				
	//Content
	?>	
	<div id="login_info" class="tabcontent hidden">
		<h3>User login</h3>

		<?php
		if(in_array('usermanagement', wp_get_current_user()->roles)){
		?>
		<form data-reset='true' class='sim_form'>
			<input type="hidden" name="disable_useraccount"		value="<?php echo wp_create_nonce("disable_useraccount");?>">
			<input type="hidden" name="userid"					value="<?php echo $user_id; ?>">
			<input type="hidden" name="action"					value="<?php echo $action_text;?>_useraccount">

			<p style="margin:30px 0px 0px;">
				Click the button below if you want to <?php echo $action_text;?> the useraccount for <?php echo $name;?>.
			</p>

			<?php echo SIM\add_save_button('disable_useraccount', ucfirst($action_text)." useraccount for $name");?>
		</form>
		<?php
		}
		
		echo password_reset_form($user);
		
		$methods	= get_user_meta($user_id, '2fa_methods', true);
		if(is_array($methods)){

			//base url parameters
			$param	= [
				'action'		=>'reset2fa',
				'user_id'		=> $user_id,
				'wp_2fa_nonce'	=> wp_create_nonce( "wp-2fa-reset-nonce_$user_id" )
			];

			?>
			<div class="2FA" style="margin-top:30px;">
				<?php
				if(!isset($methods['email'])){
					$param['do']	= 'email';
					$url = add_query_arg($param);
				?>
				<p>
					Use the button below to change the 2fa factor for <?php echo $name;?> to e-mail<br><br>
					<a href="<?php echo esc_url($url) ;?>#Login%20info" class="button sim">Change to e-mail</a>
				</p>

				<?php
				}
					$param['do']	= 'off';
					$url = add_query_arg($param);
				?>
				<p>
					Use the button below to turn off Two Factor Authentication for <?php echo $name;?><br><br>
					<a href="<?php echo esc_url( $url );?>#Login%20info" class="button sim">Reset 2FA</a>
				</p>
			</div>
		<?php }?>
	</div>
	<?php	
	return ob_get_clean();
}

//Save the password....
add_action ( 'wp_ajax_update_password', __NAMESPACE__.'\process_pw_update');
add_action ( 'wp_ajax_nopriv_update_password', __NAMESPACE__.'\process_pw_update');
function process_pw_update(){
	SIM\print_array("updating password");

	$user_id	= $_POST['userid'];
	if(!is_numeric($user_id))	wp_die('Invalid user id given', 500);

	$userdata	= get_userdata($user_id);	
	if(!$userdata)	wp_die('Invalid user id given', 500);

	if (empty($_POST['pass1']))	wp_die('Password cannot be empty', 500);

	if($_POST['pass1'] != $_POST['pass2'])	wp_die("Passwords do not match, try again.",500);

	SIM\verify_nonce('password_reset_nonce');
	
	wp_set_password( $_POST['pass1'], $user_id );
	wp_die(
		json_encode([
			'message'	=>'Changed password succesfully',
			'redirect'	=> SITEURL."/?showlogin=$userdata->user_login"
		])
	);
}

//Disable or enable an useraccount
add_action ( 'wp_ajax_disable_useraccount', __NAMESPACE__.'\disable_useraccount');
add_action ( 'wp_ajax_enable_useraccount', __NAMESPACE__.'\disable_useraccount');
function disable_useraccount(){
	if(!in_array('usermanagement', wp_get_current_user()->roles)){
		wp_die("You do not have permission to disable useraccounts.",500);
	}

	if (!is_numeric($_POST['userid'])){
		wp_die('Invalid userid given',500);
	}

	SIM\verify_nonce('disable_useraccount');
	
	if($_POST['action'] == 'disable_useraccount'){
		update_user_meta( $_POST['userid'], 'disabled', true );
		wp_die('Disabled useraccount succesfully');
	}else{
		delete_user_meta( $_POST['userid'], 'disabled');
		wp_die('Enabled useraccount succesfully');
	}
}