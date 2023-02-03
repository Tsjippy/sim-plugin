<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//check if account is disabled
add_filter( 'wp_authenticate_user', function($user, $password){
	if (
		!is_wp_error( $user ) && 									// there is no error
		get_user_meta( $user->ID, 'disabled', true ) && 			// the account is disabled
		get_bloginfo( 'admin_email' ) !== $user->user_email			// this is not the main admin
	) {
		$user = new \WP_Error(
			'account_disabled_error',
			'Your account has been disabled for safety reasons.<br>Contact the office if you want to have it enabled again.'
		);
	}

	return $user;
}, 10, 2);

function changePasswordForm($userId = null){
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
	if(
		isset($_REQUEST['action']) 			&&
		isset($_REQUEST['wp_2fa_nonce'])	&&
		isset($_REQUEST['user_id'])			&&
		wp_verify_nonce( $_REQUEST['wp_2fa_nonce'], "wp-2fa-reset-nonce_".$_REQUEST['user_id'])
	){
		if($_REQUEST['action'] == 'Reset 2FA' && function_exists('SIM\LOGIN\reset2fa')){
			SIM\LOGIN\reset2fa($userId);
			echo "<div class='success'>Succesfully turned off 2fa for $name</div>";
		}elseif($_REQUEST['action'] == 'Change to e-mail'){
			update_user_meta($userId, '2fa_methods', ['email']);
			echo "<div class='success'>Succesfully changed the 2fa factor for $name to e-mail</div>";
		}elseif($_REQUEST['action'] == 'Change account type'){
			update_user_meta($userId, 'account-type', $_REQUEST['type']);
			echo "<div class='success'>Succesfully changed the account type for $name to {$_REQUEST['type']}</div>";
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
		$nonce	= wp_create_nonce( "wp-2fa-reset-nonce_$userId" );
		if(is_array($methods)){

			?>
			<div class="2FA" style="margin-top:30px;">
				<?php
				if(!in_array('email', $methods)){
					?>
					<form method='post'>
						<input type='hidden' name='user_id' value='<?php echo $userId;?>'>
						<input type='hidden' name='wp_2fa_nonce' value='<?php echo $nonce;?>'>

						Use the button below to change the 2fa factor for <?php echo $name;?> to e-mail<br>
						<input type='submit' name='action' value='Change to e-mail' class='button small'>
					</form>
					<br>
					<?php
				}

				?>
				<form method='post'>
					<input type='hidden' name='user_id' value='<?php echo $userId;?>'>
					<input type='hidden' name='wp_2fa_nonce' value='<?php echo $nonce;?>'>

					Use the button below to turn off Two Factor Authentication for <?php echo $name;?><br>
					<input type='submit' name='action' value='Reset 2FA' class='button small'>
			</form>
			</div>
		<?php
		}

		$type			= 'positional';
		if(get_user_meta($userId, 'account-type', true) == 'positional'){
			$type		= 'normal';
		}
		?>
		<form method='post'>
			<input type='hidden' name='user_id' value='<?php echo $userId;?>'>
			<input type='hidden' name='wp_2fa_nonce' value='<?php echo $nonce;?>'>
			<input type='hidden' name='type' value='<?php echo $type;?>'>

			Use the button below to switch this account to a <?php echo $type;?> account<br>
			<input type='submit' name='action' value='Change account type' class='button small'>
		</form>
	</div>
	<?php
	return ob_get_clean();
}