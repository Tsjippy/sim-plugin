<?php
namespace SIM;

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

function change_password_field($user_id){
	//Load js
	wp_enqueue_script('simnigeria_password_strength_script');
	
	$user		= get_userdata($user_id);
	$name		= $user->display_name;
	$username	= $user->user_login;
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
			 reset_2fa($user_id);
			 echo "<div class='success'>Succesfully turned off 2fa for $name</div>";
		}
	}
	
	//Build the reset url
	$url = add_query_arg(
		array(
			'action'       => 'reset2fa',
			'user_id'      => $user_id,
			'wp_2fa_nonce' => wp_create_nonce( "wp-2fa-reset-nonce_$user_id" ),
		)
	);
				
	//Content
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
	
	<div id="login_info" class="tabcontent hidden">
		<h3>User login</h3>

		<?php
		if(in_array('usermanagement', wp_get_current_user()->roles)){
		?>
		<form data-reset='true' class='simnigeria_form'>
			<input type="hidden" name="disable_useraccount"		value="<?php echo wp_create_nonce("disable_useraccount");?>">
			<input type="hidden" name="userid"					value="<?php echo $user_id; ?>">
			<input type="hidden" name="action"					value="<?php echo $action_text;?>_useraccount">

			<p style="margin:30px 0px 0px;">
				Click the button below if you want to <?php echo $action_text;?> the useraccount for <?php echo $name;?>.
			</p>

			<?php echo add_save_button('disable_useraccount', ucfirst($action_text)." useraccount for $name");?>
		</form>
		<?php
		}
		?>

		<form data-reset='true' class='simnigeria_form'>
			<div class="login_info">
				<input type="hidden" name="password_reset_nonce"	value="<?php echo wp_create_nonce("password_reset_nonce");?>">
				<input type="hidden" name="userid"					value="<?php echo $user_id; ?>">
				<input type="hidden" name="action"					value="update_password">
				
				<p style="margin-top:30px;">
					Change the password for <?php echo $name;?> using the fields below.<br>
					<br>
					Username is <?php echo $username;?>.
				</p>
				<label>
					New Password<br>
					<input type="password" class='changepass' name="pass1" size="16" autocomplete="off" />
				</label>
				<br>
				<span style="text-align: center;" class="pass-strength-result hidden" id="pass-strength-result1">Strength indicator</span>
				<br>
				<br>
				<label>
					Confirm New Password<br>
					<input type="password" class='changepass' name="pass2" size="16" autocomplete="off" />
				</label>
				<br>
				<span style="text-align: center;" class="pass-strength-result hidden" id="pass-strength-result2">Strength indicator</span>
			</div>
			<?php echo add_save_button('update_password', 'Change password');?>
		</form>

		<?php
		
		//echo twofa_settings_form($user_id);

		if(get_user_meta($user_id,'2fa_methods',true) != ''){
		?>
		<div class="2FA" style="margin-top:30px;">
			<p>
				Use the button below to turn off Two Factor Authentication for  <?php echo $name;?><br><br>
				<a href="<?php echo esc_url( $url );?>#Login%20info" class="button sim">Reset 2FA</a>
			</p>
		</div>
		<?php }?>
	</div>
	<?php	
	return ob_get_clean();
}


//Save the password....
add_action ( 'wp_ajax_update_password', function(){
	print_array("updating password");

	if (!empty($_POST['userid']) and is_numeric($_POST['userid']) and !empty($_POST['pass1'])){
		verify_nonce('password_reset_nonce');
		
		if($_POST['pass1'] != $_POST['pass2']){
			wp_die("Passwords do not match, try again.",500);
		}
		
		wp_set_password( $_POST['pass1'], $_POST['userid'] );
		wp_die('Changed password succesfully');
	}
});

//Disable or enable an useraccount
add_action ( 'wp_ajax_disable_useraccount', 'SIM\disable_useraccount');
add_action ( 'wp_ajax_enable_useraccount', 'SIM\disable_useraccount');
function disable_useraccount(){
	if(!in_array('usermanagement', wp_get_current_user()->roles)){
		wp_die("You do not have permission to disable useraccounts.",500);
	}

	if (!is_numeric($_POST['userid'])){
		wp_die('Invalid userid given',500);
	}

	verify_nonce('disable_useraccount');
	
	if($_POST['action'] == 'disable_useraccount'){
		update_user_meta( $_POST['userid'], 'disabled', true );
		wp_die('Disabled useraccount succesfully');
	}else{
		delete_user_meta( $_POST['userid'], 'disabled');
		wp_die('Enabled useraccount succesfully');
	}
}