<?php
namespace SIM\CLOUD;
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
		This module makes it possible to upload files from the website to a OneDrive folder.<br>
	</p>
	<?php

	return ob_get_clean();
}, 10, 3);

add_filter('sim_submenu_options', function($optionsHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();

	$clientId		= $settings['client_id'];
	$clientSecret	= $settings['client_secret'];
	$accessToken	= $settings['access_token'];

	if(empty($clientId) || empty($clientSecret)){
		?>
		<div id='set_onedrive_id'>
			<h2>Connect to Onedrive</h2>
			<p>
				It seems you are not connected to OneDrive.<br>
				You need a OneDrive app to connect.<br>
				You can create such an app on <a href="https://portal.azure.com/#view/Microsoft_AAD_RegisteredApps/ApplicationsListBlade">Azure</a>.<br>
				For more information see this <a href="https://github.com/krizalys/onedrive-php-sdk">link</a>.<br>
					Make sure it has the following permissions:<br>
					'files.read',<br>
					'files.readwrite',<br>
				<br>
				Once you are done you will be redirected to a page containing the app details.<br>
				Copy the "Client identifier" and the "Client secret" in the fields below.<br>
				Now click "Save OneDrive options".<br>
			</p>
		</div>
		<?php
	}elseif(!empty($_GET['error'])){
		?>
			<div class='error'>
				<?php
				echo $_GET['error_description'];
				?>
			</div>
		<?php
	}elseif(empty($accessToken)){
		$onedriveApi		= new OnedriveConnector();

		$onedriveApi->login();
	}else{
		$onedriveApi		= new OnedriveConnector();

		$onedriveApi->getToken();
	}
	?>
	<div class="settings-section">
		<h2>API Settings</h2>
		<label>
			Client ID<br>
			<input type="text" name="client_id" value="<?php echo $clientId;?>" style='width: 500px;'>
		</label>
		<br>

		<label>
			Client Secret<br>
			<input type="text" name="client_secret" value="<?php echo $clientSecret;?>" style='width: 500px;'>
		</label>
		<br>

		<label <?php if(empty($clientSecret)){echo 'style="display:none;"';}?>>
			Access Token<br>
			<input type="text" name="access_token" value="<?php echo $accessToken;?>" style='width: 500px;'>
		</label>
		
	</div>
	<br>
	<?php

	return ob_get_clean();
}, 10, 3);

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