<?php
namespace SIM\VIMEO;
use SIM;

const ModuleVersion		= '7.0.0';

add_action('sim_submenu_description', function($module_slug, $module_name){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<p>
		This module will upload all video's to Vimeo. It support resumable uploads, meaning that if the page gets reloaded or internet connection is lost the video upload can be restarted and will continue where it was left.<br>
		A video title or description  update is also synced to Vimeo.<br>
		You can enable the option to sync your media library with Vimeo, so that enay videos added to Vimeo will also be added to your websites library<br>
		You can enable the option to delete a video from Vimeo if you delete the video from your media library.<br>
	</p>
	<?php

},10,2);

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	if(!class_exists('SIM\VIMEO\VimeoApi')){
		require(__DIR__.'/api_functions.php');
	}

	$client_id		= $settings['client_id'];
	$client_secret	= $settings['client_secret'];
	$access_token	= $settings['access_token'];

	if(empty($client_id) or empty($client_secret)){
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
	}elseif(empty($access_token)){
		if(!empty($_GET['error'])){
			?>
			<div class='error'>
				<p>
					Did you just deny me?
				</p>
			</div>
			<?php
		}elseif(!empty($_GET['code']) and !empty($_GET['state'])){
			$VimeoApi		= new VimeoApi();
			if(get_option('vimeo_state') != $_GET['state']){
				?>
				<div class='error'>
					<p>
						Something went wrong <a href="<?php echo $VimeoApi->get_authorize_url($client_id, $client_secret);?>">try again</a>.
					</p>
				</div>
				<?php
			}else{
				$access_token = $VimeoApi->store_accesstoken($client_id, $client_secret, $_GET['code'], admin_url( "admin.php?page=".$_GET["page"] ));
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
			$link	= $VimeoApi->get_authorize_url($client_id, $client_secret);
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
		$VimeoApi->is_connected();
	}
	?>
	<div class="settings-section">
		<h2>API Settings</h2>
		<label>
			Client ID<br>
			<input type="text" name="client_id" value="<?php echo $client_id;?>">
		</label>
		<br>

		<label>
			Client Secret<br>
			<input type="text" name="client_secret" value="<?php echo $client_secret;?>">
		</label>
		<br>

		<label <?php if(empty($client_secret)) echo 'style="display:none;"';?>>
			Access Token<br>
			<input type="text" name="access_token" value="<?php echo $access_token;?>">
		</label>
		
	</div>

	<div class="settings-section" <?php if(empty($access_token)) echo 'style="display:none;"';?>>
		<h2>Vimeo Settings</h2>

		<label>
			<input type="checkbox" name="upload" <?php if($settings['upload']) echo 'checked';?>>
			Automatically upload all video's to Vimeo
		</label>
		<br>

		<label>
			<input type="checkbox" name="remove" <?php if($settings['remove']) echo 'checked';?>>
			Automatically remove video from Vimeo when deleted in library
		</label>
		<br>

		<!--<label>
			<input type="checkbox" name="recyclebin" <?php if($settings['recyclebin']) echo 'checked';?>>
			Keep deleted video's in local recycle bin
		</label>
		<br> -->

		<label>
			<input type="checkbox" name="sync" <?php if($settings['sync']) echo 'checked';?>>
			Automatically sync local video's with video's on Vimeo
		</label>
	</div>
	<br>
	<?php

	return;
}, 10, 3);

add_action('sim_module_updated', function($module_slug, $options){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	schedule_tasks();
}, 10, 2);