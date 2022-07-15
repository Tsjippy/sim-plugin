<?php
namespace SIM\ADMIN;
use SIM;

const MODULE_VERSION		= '7.0.2';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

/**
 * Gets the module name based on the slug
 */
function getModuleName($slug){
	$pieces 	= preg_split('/(?=[A-Z])/', ucwords($slug));
	return trim(implode(' ', $pieces));
}

/**
 * Register a custom menu page.
 */
add_action( 'admin_menu', function() {
	global $moduleDirs;

	if(isset($_POST['module'])){
		if(isset($_POST['emails'])){
			saveEmails();
		}else{
			saveSettings();
		}
	}

	if(isset($_GET['update'])){
		$updates	= SIM\checkForUpdate( new \stdClass() );

		if(!empty($updates->response) && isset($updates->response[PLUGINNAME.'/'.PLUGINNAME.'.php'])){
			include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			include_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
			$plugin_Upgrader	= new \Plugin_Upgrader();
			$plugin_Upgrader->upgrade(PLUGINNAME.'/'.PLUGINNAME.'.php');
		}
	}

	add_menu_page("SIM Plugin Settings", "SIM Settings", 'edit_others_posts', "sim", __NAMESPACE__."\mainMenu");	

	foreach($moduleDirs as $moduleSlug=>$folderName){
		//do not load admin and template menu
		if(in_array($moduleSlug, ['__template', 'admin'])){
			continue;
		}

		$moduleName	= getModuleName($folderName);
		
		//check module page exists
		if(!file_exists(MODULESPATH.$folderName.'/php/__module_menu.php')){
			SIM\printArray("Module page does not exist for module $moduleName");
			SIM\printArray("File: ".MODULESPATH.$folderName.'/php/__module_menu.php');
			continue;
		}

		//load the menu page php file
		require_once(MODULESPATH.$folderName.'/php/__module_menu.php');

		add_submenu_page('sim', "$moduleName module", $moduleName, "edit_others_posts", "sim_$moduleSlug", __NAMESPACE__."\buildSubMenu");
	}
});

/**
 *Builds the submenu for each module
 */
function buildSubMenu(){
	global $plugin_page;
	global $Modules;

	$moduleSlug	= str_replace('sim_', '', $plugin_page);
	$moduleName	= str_replace(' module', '', get_admin_page_title());

	if(isset($Modules[$moduleSlug]) && is_array($Modules[$moduleSlug])){
		$settings	= $Modules[$moduleSlug];
	}else{
		$settings	= [];
	}

	echo '<div class="module-settings">';

		if(isset($_POST['module'])){
			$message	= "Settings succesfully saved";
			if(isset($_POST['emails'])){
				$message	= "E-mail settings succesfully saved";
			}
			echo "<div class='success'>$message</div>";
		}
		?>
		<h1><?php echo $moduleName;?> module</h1>

		<?php
		$description = apply_filters('sim_submenu_description', '', $moduleSlug, $moduleName);

		if(!empty($description)){
			echo "<h2>Description</h2>";
			echo $description;
		}
		
		$settingsTab		= settingsTab($moduleSlug, $moduleName, $settings);
		$emailSettingsTab	= emailSettingsTab($moduleSlug, $moduleName, $settings);

		if(!empty($emailSettingsTab)){
			?>
			<div class='tablink-wrapper <?php if(!isset($settings['enable'])){echo 'hidden';}?>'>
				<button class="tablink active" id="show_settings" data-target="settings">Settings</button>
				<button class="tablink" id="show_emails" data-target="emails">E-mail settings</button>
			</div>
			<?php
		}

		echo $settingsTab;
		if(!empty($emailSettingsTab)){
			echo $emailSettingsTab;
		}
}

function settingsTab($moduleSlug, $moduleName, $settings){
	ob_start();
	?>
	<div class='tabcontent' id='settings'>
		<h2>Settings</h2>
			
		<form action="" method="post">
			<input type='hidden' name='module' value='<?php echo $moduleSlug;?>'>
			Enable <?php echo $moduleName;?> module 
			<label class="switch">
				<input type="checkbox" name="enable" <?php if(isset($settings['enable'])){echo 'checked';}?>>
				<span class="slider round"></span>
			</label>
			<br>
			<br>
			<div class='options' <?php if(!isset($settings['enable'])){echo "style='display:none'";}?>>
				<?php
				$html	= apply_filters('sim_submenu_options', '', $moduleSlug, $settings, $moduleName);
				if(empty($html)){
					$html = '<div>No special settings needed for this module</div>';
				}
				echo $html;
				?>
			</div>
			<br>
			<br>
			<input type="submit" value="Save <?php echo $moduleName;?> settings">
		</form> 
		<br>
	</div>
	<?php

	return ob_get_clean();
}

function emailSettingsTab($moduleSlug, $moduleName, $settings){
	$html	= apply_filters('sim_email_settings', '', $moduleSlug, $settings, $moduleName);

	if(empty($html)){
		return '';
	}

	ob_start();

	?>
	<div class='tabcontent hidden' id='emails'>
		<h2>E-mail settings</h2>
			
		<form action="" method="post">
			<input type='hidden' name='module' value='<?php echo $moduleSlug;?>'>
			<?php 
			echo $html;
			?>
			<br>
			<br>
			<input type="submit" name="save-email-settings" value="Save <?php echo $moduleName;?> e-mail settings">
		</form> 
		<br>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * The main plugin menu
 */
function mainMenu(){
	global $Modules;
	global $moduleDirs;

	unset($Modules['extra_post_types']);
	unset($Modules['template_specific']);
	unset($Modules['pdf']);
	unset($Modules['celebrations']);
	unset($Modules['schedules']);

	foreach(['frontend_posting',
	'user_management',
	'user_pages',
	'SIM Nigeria',
	'PDF',
	'default_pictures',
	'mail_posting',
	'fancy_email',
	'content_filter',
	'media_gallery',
	'embed_page'] as $key){
		if(isset($Modules[$key])){
			$newkey	= str_replace(['_',' '], '', strtolower($key));

			$Modules[$newkey]	= $Modules[$key];
			unset($Modules[$key]);
		}
	}

	if(isset($Modules['bulk_meta_update'])){
		$Modules['bulkchange']	= $Modules['bulk_meta_update'];
		unset($Modules['bulk_meta_update']);
	}

	if(isset($Modules['mandatory_content'])){
		$Modules['mandatory']	= $Modules['mandatory_content'];
		unset($Modules['mandatory_content']);
	}

	update_option('sim_modules', $Modules);

	$active		= [];
	$inactive	= [];
	foreach($moduleDirs as $moduleSlug=>$moduleName){
		$moduleName	= getModuleName($moduleName);
		
		if(in_array($moduleSlug, array_keys($Modules))){
			$active[$moduleSlug]	= $moduleName;
		}elseif($moduleSlug != "__template"){
			$inactive[$moduleSlug]	= $moduleName;
		}
	}
	
	ob_start();
	$url	= add_query_arg(['update' => 'yes'], SIM\currentUrl());
	?>
	<div>
		<a href='<?php echo $url;?>' class='button'>Check for update</a><br><br>
		<strong>Current active modules</strong><br>
		<ul class="sim-list">
		<?php
		foreach($active as $slug=>$name){
			$url	= admin_url("admin.php?page=sim_$slug");
			echo "<li><a href='$url'>$name</a></li>";
		}
		?>
		</ul>
		<strong>Current inactive modules</strong><br>
		<ul class="sim-list">
		<?php
		if(empty($inactive)){
			echo "All modules are activated";
		}
		foreach($inactive as $slug=>$name){
			$url	= admin_url("admin.php?page=sim_$slug");
			echo "<li><a href='$url'>$name</a></li>";
		}
		?>
		</ul>
	</div>
	<?php
	echo ob_get_clean();
}