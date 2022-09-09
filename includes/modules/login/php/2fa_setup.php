<?php 
namespace SIM\LOGIN;
use SIM;

add_shortcode('twofa_setup', __NAMESPACE__.'\twoFaSettingsForm');
function twoFaSettingsForm($userId=''){
	//Load js
	wp_enqueue_script('sim_2fa_script');

	if(!is_numeric($userId)){
		$userId = get_current_user_id();
	}

	$secondFactor	= setupTimeCode();

	if(!isset($_SESSION)){
		session_start();
	}

	if(!empty($_SESSION["webautn_id"])){
		$publicKeyCredentialId	= $_SESSION["webautn_id"];
	}else{
		$publicKeyCredentialId	= null;
	}

	ob_start();
	$twoFaMethods	= (array)get_user_meta($userId, '2fa_methods', true);
	SIM\cleanUpNestedArray($twoFaMethods);

	if(!empty($_GET['redirected'])){
		?>
		<div class='error'>
			<p style='border-left: 4px solid #bd2919;padding: 5px;'>
				You have been redirected to this page because you need to setup a second login factor before you can visit other pages.
			</p>
		</div>
		<?php
	}
	?>
	<form id="2fa-setup-wrapper">
		<input type='hidden' name='secretkey' value='<?php echo $secondFactor->secretKey;?>'>

		<div id='2fa-options-wrapper' style='margin-bottom:20px;'>
			<h4>Second login factor</h4>
			<?php
			if(empty($twoFaMethods) || in_array('webauthn', $twoFaMethods) && count($twoFaMethods)==1){
				?>
				<p>
					Please setup an second login factor to keep this website safe.<br>
					Choose one of the options below.
				</p>
				<?php
			}else{
				?>
				<p>
					Your active second login factor is:
				</p>
				<?php
			}
			?>
			<label>
				<input type="radio" class="twofa_option_checkbox" name="2fa_methods[]" value="authenticator" <?php if(array_search('authenticator', $twoFaMethods) !== false){echo "checked";}?>> 
				<span class="optionlabel">Authenticator app</span>
			</label>
			<br>
			<label>
				<input type="radio" class="twofa_option_checkbox" name="2fa_methods[]" value="email" <?php if(array_search('email', $twoFaMethods) !== false){echo "checked";}?>> 
				<span class="optionlabel">E-mail</span>
			</label>
			<br>
		</div>
		<div id='setup-authenticator' class='twofa_option hidden'>
			<p>
				You need an authenticator app as a second login factor.<br>
				Both "Google Authenticator" and "Microsoft Authenticator" are good options.<br>
				For iOS you can use the built-in password manager.
				Make sure you have one of them available on your phone. <br>
			</p>
			<div id="authenticatorlinks" class='hidden mobile'>
				<p>
					You can use one of the links below to download an app<br>
					<a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2">Download for Android</a><br>
					<a href="https://apps.apple.com/us/app/google-authenticator/id388497605">Download for iPhone</a><br>
					<br>
					Click the button below when you have an app installed.<br>
					This will open the app and create a code.<br>
					You can also manually add an entry using this code: <code><?php echo $secondFactor->secretKey;?></code>.
					Copy the code created by the authenticator app in the field below.<br>
					<?php echo $secondFactor->appLink;?><br>
				</p>
			</div>
			<div class='hidden desktop'>
				<p>
					Scan the qr code displayed below to open up your authenticator app.<br>
					You can also manually add an entry using this code: <code><?php echo $secondFactor->secretKey;?></code>
					Copy the code created by the authenticator app in the field below.<br>
					<?php echo $secondFactor->imageHtml;?>
				</p>
			</div>
			<label>
				Insert the created code here.<br>
				<input type='text' name='auth_secret' required>
			</label>
			<p>Not sure what to do? Check the <a href="<?php echo SITEURL;?>'/manuals/">manuals!</a></p>
		</div>
		<div id='setup-email' class='twofa_option hidden'>
			<input type='hidden' id='username' value='<?php echo $userId;?>'>
			<p>
				Click the button below to enable e-mail verification <br>
				You will receive an e-mail on <code><?php echo get_userdata($userId)->user_email;?></code>.<br>
				Copy the code from that e-mail.<br>
				<button type='button' class='button small' id='email-code-button'>E-mail the code</button>
				<div id='email-message'></div>
			</p>
			<label id='email-code-validation' class='hidden'>
				Insert the code e-mailed to you here.<br>
				<input type='text' name='email_code' required>
			</label>
		</div>
		<?php
		echo SIM\addSaveButton('save2fa',"Save 2fa settings", 'hidden');
		?>
	</form>

	<div id='webauthn_wrapper' class='hidden'>
		<?php
		if(empty($publicKeyCredentialId)){
		?>
		<div id='add_webauthn'>
			<h4>Additional second login factor</h4>
			<div class="infobox" name="traveltype_info">
				<div style="float:right">
					<p class="info_icon" style='margin-top: -30px;padding-top: 0px;'>
						<img draggable="false" role="img" class="emoji" alt="ℹ" src="<?php echo PICTURESURL;?>/info.png" loading='lazy' style='max-height:25px;'>
					</p>
				</div>
				<span class="info_text">
					Additionally you may add a biometric login (fingerprint or facial recognition).<br>
					This is setup per device, meaning that if you setup a biometric on your phone, it will not work on your laptop.<br>
					If you want it on another device you have to add that device as well.<br>
					<br>
					If you have setup biometric on a device, you don't need to fill in a e-mail or authenticator code.<br>
					<br>
					To set it up you just have to fill in a name for this device and click the button.
				</span>
			</div>
			<label>
				Device name
				<input type="text" name="identifier">
			</label>
			<button type='button' id='add_fingerprint' class='button' style='margin:10px 0px;'>Add biometric</button>
		</div>
		<?php
		}
		echo authTable($publicKeyCredentialId);
		?>
	</div>
	<?php
	return ob_get_clean();
}