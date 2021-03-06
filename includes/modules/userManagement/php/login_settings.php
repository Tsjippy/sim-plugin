<?php
namespace SIM\USERMANAGEMENT;
use SIM;

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

function change_password_form($userId = null){
	if(is_numeric($userId)){
		$user		= get_userdata($userId);
	}else{
		$user		= wp_get_current_user();
	}
	$name		= $user->display_name;
	$disabled	= get_user_meta( $user->ID, 'disabled', true );
	if($disabled){
		$actionText	= 'enable';
	}else{
		$actionText	= 'disable';
	}
	
	ob_start();
	
	//Check if action is needed
	if(isset($_GET['action']) and isset($_GET['wp_2fa_nonce'])){
		if($_GET['action'] == 'reset2fa' and wp_verify_nonce( $_GET['wp_2fa_nonce'], "wp-2fa-reset-nonce_".$_GET['user_id'])){
			if($_GET['do'] == 'off' and function_exists('SIM\LOGIN\reset2fa')){
				SIM\LOGIN\reset2fa($userId);
				echo "<div class='success'>Succesfully turned off 2fa for $name</div>";
			}elseif($_GET['do'] == 'email'){
				update_user_meta($userId, '2fa_methods', ['email']);
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
			wp_enqueue_script( 'sim_user_management');
			?>
			<form data-reset='true' class='sim_form'>
				<input type="hidden" name="disable_useraccount"		value="<?php echo wp_create_nonce("disable_useraccount");?>">
				<input type="hidden" name="userid"					value="<?php echo $userId; ?>">
				<input type="hidden" name="action"					value="<?php echo $actionText;?>_useraccount">

				<p style="margin:30px 0px 0px;">
					Click the button below if you want to <?php echo $actionText;?> the useraccount for <?php echo $name;?>.
				</p>

				<?php echo SIM\addSaveButton('disable_useraccount', ucfirst($actionText)." useraccount for $name");?>
			</form>
			<?php
		}
		
		if(function_exists('SIM\LOGIN\passwordResetForm')){
			echo SIM\LOGIN\passwordResetForm($user);
		}
		
		$methods	= get_user_meta($userId, '2fa_methods', true);
		if(is_array($methods)){

			//base url parameters
			$param	= [
				'action'		=>'reset2fa',
				'user_id'		=> $userId,
				'wp_2fa_nonce'	=> wp_create_nonce( "wp-2fa-reset-nonce_$userId" )
			];

			?>
			<div class="2FA" style="margin-top:30px;">
				<?php
				if(!isset($methods['email'])){
					$param['do']	= 'email';
					$url 			= add_query_arg($param);
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