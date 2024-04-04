<?php
namespace SIM\CAPTCHA;
use SIM;

const MODULE_VERSION		= '7.0.0';
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

add_filter('sim_submenu_description', function($description, $moduleSlug, $moduleName){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();
	?>
	<p>
		This module makes it possible to enable and use captcha on forms made with the formbuilder or on the wordpress default forms (login, register, reset password, comment)
	</p>
	<?php

	return ob_get_clean();
}, 10, 3);

add_filter('sim_submenu_options', function($optionsHtml, $moduleSlug, $settings, $moduleName){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();
	?>
    <br>
	<br>
	Do you want to use Google's reCaptcha? (<a href='https://www.google.com/recaptcha/admin/create'>See here</a>)
	<label class="switch">
		<input type="checkbox" name="recaptcha" <?php if(isset($settings['recaptcha'])){echo 'checked';}?>>
		<span class="slider round"></span>
	</label>

	<?php
	if(isset($settings['recaptcha'])){
		?>
		<br>
		<br>
		<label>
			Your API key<br>
			<input type='text' name='recaptcha[key]' value='<?php if(!empty($settings['recaptcha']["key"])){echo $settings['recaptcha']["key"];}?>' style='width:350px'>
		</label>
		<br>
		<br>
		<label>
			API key type<br>
			<label>
				<input type='radio' name='recaptcha[keytype]' value='v2' <?php if(!empty($settings['recaptcha']["keytype"]) && $settings['recaptcha']["keytype"] == 'v2'){echo 'checked';}?>>
				v2
			</label>
			<label>
				<input type='radio' name='recaptcha[keytype]' value='v3' <?php if(!empty($settings['recaptcha']["keytype"]) && $settings['recaptcha']["keytype"] == 'v3'){echo 'checked';}?>>
				v3 / Enterprise
			</label>
		</label>
		<br>
		<br>
		<label>
			Your secret key<br>
			<input type='text' name='recaptcha[secret]' value='<?php if(!empty($settings['recaptcha']['secret'])){echo $settings['recaptcha']['secret'];}?>' style='width:350px'>
		</label>
		<br>
		<br>
		<table style='border:none'>
			<tr>
				<td>
					Use on login form
				</td>
				<td>
					<label class="switch">
						<input type="checkbox" name="recaptcha[login]" <?php if(isset($settings['recaptcha']['login'])){echo 'checked';}?>>
						<span class="slider round"></span>
					</label>
				</td>
			</tr>
			<tr>
				<td>
					Use on password reset form
				</td>
				<td>
					<label class="switch">
						<input type="checkbox" name="recaptcha[password]" <?php if(isset($settings['recaptcha']['password'])){echo 'checked';}?>>
						<span class="slider round"></span>
					</label>
				</td>
			</tr>
			<tr>
				<td>
					Use on new user form
				</td>
				<td>
					<label class="switch">
						<input type="checkbox" name="recaptcha[newuser]" <?php if(isset($settings['recaptcha']['newuser'])){echo 'checked';}?>>
						<span class="slider round"></span>
					</label>			
				</td>
			</tr>
			<tr>
				<td>
					Use on comment form
				</td>
				<td>
					<label class="switch">
						<input type="checkbox" name="recaptcha[comment]" <?php if(isset($settings['recaptcha']['comment'])){echo 'checked';}?>>
						<span class="slider round"></span>
					</label>			
				</td>
			</tr>
		</table>	
		<?php	
	}

	?>
	<br>
	<br>
	Do you want to use Cloudflare's Turnstile? (<a href='https://www.cloudflare.com/en-gb/products/turnstile/#Page-Pricing-AS'>See here</a>)
	<label class="switch">
		<input type="checkbox" name="turnstile" <?php if(isset($settings['turnstile'])){echo 'checked';}?>>
		<span class="slider round"></span>
	</label>

	<?php
	if(isset($settings['turnstile'])){
		?>
		<br>
		<br>
		<label>
			Your API key<br>
			<input type='text' name='turnstile[key]' value='<?php if(!empty($settings['turnstile']['key'])){echo $settings['turnstile']['key'];}?>' style='width:350px'>
		</label>
		<br>
		<label>
			Your secret key<br>
			<input type='text' name='turnstile[secretkey]' value='<?php if(!empty($settings['turnstile']['secretkey'])){echo $settings['turnstile']['secretkey'];}?>' style='width:350px'>
		</label>
		<br>
		<br>
		<table style='border:none'>
			<tr>
				<td>
					Use on login form
				</td>
				<td>
					<label class="switch">
						<input type="checkbox" name="turnstile[login]" <?php if(isset($settings['turnstile']['login'])){echo 'checked';}?>>
						<span class="slider round"></span>
					</label>
				</td>
			</tr>
			<tr>
				<td>
					Use on password reset form
				</td>
				<td>
					<label class="switch">
						<input type="checkbox" name="turnstile[password]" <?php if(isset($settings['turnstile']['password'])){echo 'checked';}?>>
						<span class="slider round"></span>
					</label>
				</td>
			</tr>
			<tr>
				<td>
					Use on new user form
				</td>
				<td>
					<label class="switch">
						<input type="checkbox" name="turnstile[newuser]" <?php if(isset($settings['turnstile']['newuser'])){echo 'checked';}?>>
						<span class="slider round"></span>
					</label>			
				</td>
			</tr>
			<tr>
				<td>
					Use on comment form
				</td>
				<td>
					<label class="switch">
						<input type="checkbox" name="turnstile[comment]" <?php if(isset($settings['turnstile']['comment'])){echo 'checked';}?>>
						<span class="slider round"></span>
					</label>			
				</td>
			</tr>
		</table>

		<?php
	}

	return ob_get_clean();
}, 10, 4);

add_filter('sim_module_data', function($dataHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $dataHtml;
	}

	return $dataHtml;
}, 10, 3);

add_filter('sim_email_settings', function($optionsHtml, $moduleSlug, $settings, $moduleName){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();
	
    ?>

	<?php

	return ob_get_clean();
}, 10, 4);