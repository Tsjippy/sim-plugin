<?php
namespace SIM\VIMEO;
use SIM;
use Vimeo\Vimeo;

const MODULE_VERSION		= '7.0.12';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();
	//display url form
	if(is_numeric($_GET['vimeoid'])){
		wp_enqueue_script('sim_vimeo_admin_script');
		?>
		<style>
			.loadergif{
				width: 30px;
			}
		</style>
		<form>
			<label>Enter download url (get it from <a href='https://vimeo.com/manage/<?php echo $_GET['vimeoid'];?>/advanced' target="_blank">this page</a>)
				<input type="url" name="download_url" style='width:100%;'><br><br>
			</label>
			<?php
			echo SIM\addSaveButton('download_video', 'Submit download url');
			?>
		</form> 
		<?php
	}
	?>
	<p>
		This module will upload all video's to Vimeo. It support resumable uploads, meaning that if the page gets reloaded or internet connection is lost the video upload can be restarted and will continue where it was left.<br>
		A video title or description  update is also synced to Vimeo.<br>
		You can enable the option to sync your media library with Vimeo, so that enay videos added to Vimeo will also be added to your websites library<br>
		You can enable the option to delete a video from Vimeo if you delete the video from your media library.<br>
	</p>
	<?php

	return ob_get_clean();

},10,2);

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
		<div id='set_vimeo_id'>
			<h2>Connect to vimeo</h2>
			<p>
				It seems you are not connected to vimeo.<br>
				You need a Vimeo app to connect.<br>
				You can create such an app on <a href="https://developer.vimeo.com/apps/new">developer.vimeo.com/apps</a>.<br>
				For more information see this <a href="https://vimeo.zendesk.com/hc/en-us/articles/360042445632-How-do-I-create-an-API-app-">link</a>.<br>
				<br>
				Once you are done you will be redirected to a page containing the app details.<br>
				Copy the "Client identifier" and the "Client secret" in the fields below.<br>
				Now click "Save Vimeo options".<br>
			</p>
		</div>
		<?php
	}elseif(empty($accessToken)){
		if(!empty($_GET['error'])){
			?>
			<div class='error'>
				<p>
					Did you just deny me?
				</p>
			</div>
			<?php
		}elseif(!empty($_GET['code']) && !empty($_GET['state'])){
			$VimeoApi		= new VimeoApi();
			if(get_option('vimeo_state') != $_GET['state']){
				?>
				<div class='error'>
					<p>
						Something went wrong <a href="<?php echo $VimeoApi->getAuthorizeUrl($clientId, $clientSecret);?>">try again</a>.
					</p>
				</div>
				<?php
			}else{
				$accessToken = $VimeoApi->storeAccessToken($clientId, $clientSecret, $_GET['code'], admin_url( "admin.php?page=".$_GET["page"] ));
				?>
				<div id='set_vimeo_token'>
					<h2>Succesfully connect to vimeo</h2>
					<p>
						We are all done<br>
						Just click the Save Vimeo options" button to save your token.<br>
					</p>
				</div>
				<?php
			}
		}else{
			$VimeoApi		= new VimeoApi();
			$link	= $VimeoApi->getAuthorizeUrl($clientId, $clientSecret);
			?>
			<div id='set_vimeo_token'>
				<h2>Connect to vimeo</h2>
				<p>
					We are almost done.<br>
					Go back to the vimeo page and click on "OAuth Redirect Authentication"<br>
					Click on the "Add URL +" button.<br>
					Insert his url: <code><?php echo admin_url( "admin.php?page=".$_GET["page"] );?></code><br>
					<br>
					Once you have added the url you can click this <a href='<?php echo $link;?>'>link</a> to authorize the app.<br>
					<br>
					You can also create an access token yourself at the "Generate an access token" section.<br>
					Click the "Authenticated (you)" radio, select all scopes and click the "Generate" button.<br>
					Copy the generated token in the Access token field below.<br>
					Save your changed.<br>
				</p>
			</div>
			<?php
		}
	}else{
		$VimeoApi		= new VimeoApi();
		$VimeoApi->isConnected();
	}
	?>
	<div class="settings-section">
		<h2>API Settings</h2>
		<label>
			Client ID<br>
			<input type="text" name="client_id" value="<?php echo $clientId;?>">
		</label>
		<br>

		<label>
			Client Secret<br>
			<input type="text" name="client_secret" value="<?php echo $clientSecret;?>">
		</label>
		<br>

		<label <?php if(empty($clientSecret)){echo 'style="display:none;"';}?>>
			Access Token<br>
			<input type="text" name="access_token" value="<?php echo $accessToken;?>">
		</label>
		
	</div>

	<div class="settings-section" <?php if(empty($accessToken)){echo 'style="display:none;"';}?>>
		<h2>Vimeo Settings</h2>

		<label>
			<input type="checkbox" name="upload" <?php if($settings['upload']){echo 'checked';}?>>
			Automatically upload all video's to Vimeo
		</label>
		<br>

		<label>
			<input type="checkbox" name="remove" <?php if($settings['remove']){echo 'checked';}?>>
			Automatically remove video from Vimeo when deleted in library
		</label>
		<br>

		<label>
			<input type="checkbox" name="sync" <?php if($settings['sync']){echo 'checked';}?>>
			Automatically sync local video's with video's on Vimeo
		</label>
	</div>
	<br>
	<?php

	return ob_get_clean();
}, 10, 3);

add_filter('sim_module_updated', function($options, $moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $options;
	}

	scheduleTasks();

	return $options;
}, 10, 2);