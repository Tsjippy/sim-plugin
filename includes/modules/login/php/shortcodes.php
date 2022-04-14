<?php 
namespace SIM\LOGIN;
use SIM;

#####
# PASSWORD RESET #
#####
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

// Display password reset
add_shortcode("change_password", function(){
	if(!is_user_logged_in()){
		$user	= check_password_reset_key($_GET['key'], $_GET['login']);
		
		if(is_wp_error($user)){
			if($user->get_error_message() == "Invalid key."){
				return "This link has expired, please request a new password using the login menu.";
			}

			return $user->get_error_message(). "<br>Please try again.";
		}
	}else{
		$user	= wp_get_current_user();
	}

	return password_reset_form($user);
});

#####
# ACCOUNT REQUEST #
#####
function get_available_username($first_name, $last_name){
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

//Shortcode for people to register themselves
add_shortcode('request_account', function ($atts){
	ob_start();
	?>
	<form class='sim_form' data-reset="true">
		<p>Please fill in the form to create an user account</p>
		
		<input type="hidden" name="action" value="requestuseraccount">
		
		<label>
			<h4>First name<span class="required">*</span></h4>
			<input type="text" name="first_name" value="" required>
		</label>
		
		<label>
			<h4>Last name<span class="required">*</span></h4>
			<input type="text" class="" name="last_name" required>
		</label>

		<label>
			<h4>Desired Password</h4>
			<input type="password" class='changepass' name="pass1" size="16" autocomplete="off"/>
		</label>
		<br>
		<span style="text-align: center;" class="pass-strength-result hidden" id="pass-strength-result1">Strength indicator</span>
		<br>
		<label>
			<h4>Confirm Password</h4>
			<input type="password" class='changepass' name="pass2" size="16" autocomplete="off"/>
		</label>
		<br>
		<span style="text-align: center;" class="pass-strength-result hidden" id="pass-strength-result2">Strength indicator</span>
		
		<label>
			<h4>E-mail<span class="required">*</span></h4>
			<input class="" type="email" name="email" required>
		</label>
		<?php 
		echo SIM\add_save_button('request_account', 'Request an account');
		?>
	</form>
	<?php
	
	return ob_get_clean();
});