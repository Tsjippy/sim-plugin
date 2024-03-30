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
		This module makes it possible to enable and use captcha on form made with the formbuilder or on the comment form
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
			<input type='text' name='recaptchakey' value='<?php if(!empty($settings['recaptchakey'])){echo $settings['recaptchakey'];}?>' style='width:350px'>
		</label>
		<br>
		<br>
		<label>
			API key type<br>
			<label>
				<input type='radio' name='recaptchakeytype' value='v2' <?php if(!empty($settings['recaptchakeytype']) && $settings['recaptchakeytype'] == 'v2'){echo 'checked';}?>>
				v2
			</label>
			<label>
				<input type='radio' name='recaptchakeytype' value='v3' <?php if(!empty($settings['recaptchakeytype']) && $settings['recaptchakeytype'] == 'v3'){echo 'checked';}?>>
				v3 / Enterprise
			</label>
		</label>
		<br>
		<br>
		<label>
			Your secret key<br>
			<input type='text' name='recaptchasecret' value='<?php if(!empty($settings['recaptchasecret'])){echo $settings['recaptchasecret'];}?>' style='width:350px'>
		</label>
		<br>
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
			<input type='text' name='turnstilekey' value='<?php if(!empty($settings['turnstilekey'])){echo $settings['turnstilekey'];}?>' style='width:350px'>
		</label>
		<br>
		<label>
			Your secret key<br>
			<input type='text' name='turnstilesecretkey' value='<?php if(!empty($settings['turnstilesecretkey'])){echo $settings['turnstilesecretkey'];}?>' style='width:350px'>
		</label>
		<br>

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