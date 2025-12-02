<?php
namespace SIM\ADMIN;
use SIM;

const MODULE_VERSION		= '8.0.0';

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

/**
 * Register a custom menu page.
 */
add_action( 'admin_menu', __NAMESPACE__.'\adminMenu');
function adminMenu() {
	
	global $moduleDirs;
	global $Modules;
	
	if($_GET['page'] == 'sim'){
		$message = handlePost();

		?>
		<div class='success'>
			<?php echo esc_html($message);?>
		</div>
		<?php
	}
 
	do_action('sim_module_actions');

	add_menu_page("SIM Plugin Settings", "SIM Settings", 'edit_others_posts', "sim", __NAMESPACE__."\mainMenu");

	$active		= [];
	foreach($moduleDirs as $moduleSlug=>$modulePath){
		$moduleName	= SIM\getModuleName($modulePath);
		if(!in_array($moduleSlug, ["__template", "admin", "__defaults"])){
			if(in_array($moduleSlug, array_keys($Modules))){
				$active[$moduleSlug]	= $moduleName;
			}
		}
	}

	foreach($moduleDirs as $moduleSlug => $path){
		//do not load admin and template menu
		if(in_array($moduleSlug, ['__template', 'admin'])){
			continue;
		}

		$moduleName	= SIM\getModuleName($path, ' ');
		
		//check module page exists
		if(!file_exists($path.'/php/__module_menu.php')){
			if(!str_contains($path, 'node_modules') && is_dir($path)){
				SIM\printArray("Module page does not exist for module $moduleName");
				SIM\printArray("File: $path/php/__module_menu.php" );
			}
			continue;
		}

		//load the menu page php file
		require_once($path.'/php/__module_menu.php');

		if(in_array($moduleSlug, $active)){
			$slug	= 'sim';
		}else{
			$slug	= '';
		}

		add_submenu_page(
			$slug, 
			"$moduleName module", 
			$moduleName, 
			"edit_others_posts", 
			"sim_$moduleSlug", 
			function() use ( $message ) {
				buildSubMenu($message);
			}
		);
	}
}

function handlePost(){
	do_action('sim-admin-settings-post');
	
	// do some checks
	if(
		!isset($_POST['module']) ||
		!isset($_POST['nonce']) ||
		!wp_verify_nonce(wp_unslash($_POST['nonce']), 'module-settings' )
	){
		return '';
	}

	if(isset($_POST['emails'])){
		$message	= "E-mail settings succesfully saved";
		saveEmails();
	}else{
		$message	= "Settings succesfully saved";
		saveSettings();
	}
	
	// Build the message
	$plugin	= SIM\getFromTransient('plugin');
	if(isset($plugin)){
		if(isset($plugin['installed'])){
			$name		 = ucfirst($plugin['installed']);
			$message	.= "<br><br>Dependend plugin '$name' succesfully installed and activated";
		}elseif(isset($plugin['activated'])){
			$name		 = ucfirst($plugin['activated']);
			$message	.= "<br><br>Dependend plugin '$name' succesfully activated";
		}
		SIM\deleteFromTransient('plugin');
	}
	
	return $message;
}

/**
 * Builds the submenu for each module
 */
function buildSubMenu($message=''){
	global $Modules;
	global $moduleDirs;

	if(empty($_GET['page'])){
		return '';
	}
	
	$requestedModule	= sanitize_key($_GET['page']);

	$moduleSlug			= str_replace('sim_', '', $requestedModule); 
	$moduleName			= SIM\getModuleName($moduleDirs[$moduleSlug], ' ');

	if(empty($moduleName)){
		$moduleName	= ucfirst(str_replace('_', ' ', $moduleSlug));
	}

	if(isset($Modules[$moduleSlug]) && is_array($Modules[$moduleSlug])){
		$settings	= $Modules[$moduleSlug];
	}else{
		$settings	= [];
	}

	?>
	<div class="module-settings">	
		<h1>
			<?php echo esc_html($moduleName);?> module
		</h1>

		<?php
		
		$tab	= 'description';
		if(isset($_GET['tab'])){
			$tab	= sanitize_key($_GET['tab']);
		}elseif(!empty($settings['enable'])){
			$tab	= 'settings';
		}

		$emailSettingsTab	= '';
		$dataTab			= '';
		$functionsTab		= '';

		// Only load if the module is enabled
		if(isset($settings['enable'])){
			$emailSettingsTab	= emailSettingsTab($moduleSlug, $moduleName, $settings, $tab);
			$dataTab			= dataTab($moduleSlug, $moduleName, $settings, $tab);
			$functionsTab		= functionsTab($moduleSlug, $moduleName, $settings, $tab);
		}

		?>
		<div class='tablink-wrapper'>
			<button class="tablink <?php if($tab == 'description'){echo 'active';}?>" id="show-description" data-target="description" >Description</button>
			<button class="tablink <?php if($tab == 'settings'){echo 'active';}?>" id="show-settings" data-target="settings">Settings</button>
			<?php
			if(!empty($emailSettingsTab)){
				?>
				<button class="tablink <?php if($tab == 'emails'){echo 'active';} if(!isset($settings['enable'])){echo 'hidden';}?>" id="show-emails" data-target="emails">E-mail settings</button>
				<?php
			}
			if(!empty($dataTab)){
				?>
				<button class="tablink <?php if($tab == 'data'){echo 'active';} if(!isset($settings['enable'])){echo 'hidden';}?>" id="show-data" data-target="data">Module data</button>
				<?php
			}
			if(!empty($functionsTab)){
				?>
				<button class="tablink <?php if($tab == 'functions'){echo 'active';} if(!isset($settings['enable'])){echo 'hidden';}?>" id="show-functions" data-target="functions">Functions</button>
				<?php
			}
			?>
		</div>
		<?php

		echo $message;

		descriptionsTab($moduleSlug, $moduleName, $tab);
		settingsTab($moduleSlug, $moduleName, $settings, $tab);

		if(!empty($emailSettingsTab)){
			echo $emailSettingsTab;
		}
		if(!empty($dataTab)){
			echo $dataTab;
		}
		if(!empty($functionsTab)){
			echo $functionsTab;
		}
}

function descriptionsTab($moduleSlug, $moduleName, $tab){
	$description	= file_get_contents(constant("SIM\\MODULESPATH")."/$moduleSlug/README.md");
	if($description){
		//convert to html
		$parser 		= new \Michelf\MarkdownExtra;
		$description	= $parser->transform($description);
	}

	$description = apply_filters("sim_submenu_{$moduleSlug}_description", $description, $moduleSlug, $moduleName);

	if(!empty($description)){
		?>
		<div class='tabcontent <?php if($tab != 'description'){echo 'hidden';}?>' id='description'>
			<h2>Description</h2>
			
			<?php echo wp_kses($description, 'post'); ?>
			
			<br>
		</div>
		<?php
	}
}

function settingsTab($moduleSlug, $moduleName, $settings, $tab){
	global $defaultModules;

	?>
	<div class='tabcontent <?php if($tab != 'settings'){echo 'hidden';}?>' id='settings'>
		<h2>Settings</h2>
			
		<form action="" method="post">
			<input type='hidden' class='no-reset' name='module' value='<?php echo esc_html($moduleSlug);?>'>
			<input type='hidden' class='no-reset' name='nonce' value='<?php echo esc_html(wp_create_nonce('module-settings'));?>'>

			<?php
			if(in_array($moduleSlug, $defaultModules)){
				?>
				<input type='hidden' class='no-reset' name='enable' value='on'>
				This module is enabled by default<br><br>
				<?php
			}else{
				?>
				Enable <?php echo esc_html($moduleName);?> module
				<label class="switch">
					<input type="checkbox" name="enable" value='on' <?php if(isset($settings['enable'])){echo 'checked';}?> >
					<span class="slider round"></span>
				</label>
				<br>
				<br>
				<?php
			}

			?>
			<div class='options' <?php if(!isset($settings['enable'])){echo "style='display:none'";}?>>
				<?php
				$options	= apply_filters("sim_submenu_{$moduleSlug}_options", '', $settings, $moduleName);
				if(empty($options)){
					?>
					<div>
						No special settings needed for this module
					</div>
					<?php
				}else{
					echo $options;
				}
				
				?>
			</div>

			<?php
			// Only show submit button if there is something to submit
			if(!isset($defaultModules[$moduleSlug]) || !empty($options)){
				?>
				<br>
				<br>
				<input type="submit" value="Save <?php echo esc_html($moduleName);?> settings">
				<?php
			}
			?>
		</form>
		<br>
	</div>
	<?php
}

function emailSettingsTab($moduleSlug, $moduleName, $settings, $tab){
	$html	= apply_filters("sim_email_{$moduleSlug}_settings", '', $settings, $moduleName);

	if(empty($html)){
		return '';
	}

	ob_start();

	?>
	<div class='tabcontent <?php if($tab != 'emails'){echo 'hidden';}?>' id='emails'>
		<h2>E-mail settings</h2>
			
		<form action="" method="post">
			<input type='hidden' class='no-reset' name='module' value='<?php echo esc_html($moduleSlug);?>'>
			<?php
			echo $html;
			?>
			<br>
			<br>
			<input type="submit" name="save-email-settings" value="Save <?php echo esc_html($moduleName);?> e-mail settings">
		</form>
		<br>
	</div>
	<?php

	return ob_get_clean();
}

function dataTab($moduleSlug, $moduleName, $settings, $tab){
	if(!SIM\getModuleOption($moduleSlug, 'enable')){
		return '';
	}

	$html	= apply_filters("sim_module_{$moduleSlug}_data", '', $settings, $moduleName);

	if(empty($html)){
		return '';
	}

	ob_start();

	?>
	<div class='tabcontent <?php if($tab != 'data'){echo 'hidden';}?>' id='data'>
		<?php
		echo $html;
		?>
	</div>
	<?php

	return ob_get_clean();
}

function functionsTab($moduleSlug, $moduleName, $settings, $tab){
	if(!SIM\getModuleOption($moduleSlug, 'enable')){
		return '';
	}

	$html	= apply_filters("sim_module_{$moduleSlug}_functions", '', $settings, $moduleName);

	if(empty($html)){
		return '';
	}

	ob_start();

	?>
	<div class='tabcontent <?php if($tab != 'functions'){echo 'hidden';}?>' id='functions'>
		<?php
		echo $html;
		?>
	</div>
	<?php

	return ob_get_clean();
}


function mainMenu(){
	if(function_exists(__NAMESPACE__.'\mainMenuPro')){
		mainMenuPro();
	}

	return '';
}