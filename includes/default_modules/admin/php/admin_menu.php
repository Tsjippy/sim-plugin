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

	if(isset($_POST['module'])){
		if(isset($_POST['emails'])){
			saveEmails();
		}else{
			saveSettings();
		}
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
			add_submenu_page('sim', "$moduleName module", $moduleName, "edit_others_posts", "sim_$moduleSlug", __NAMESPACE__."\buildSubMenu");
		}else{
			add_submenu_page(null, "$moduleName module", $moduleName, "edit_others_posts", "sim_$moduleSlug", __NAMESPACE__."\buildSubMenu");
		}
	}
}

function handlePost(){
	do_action('sim-admin-settings-post');

	if(!isset($_POST['module'])){
		return;
	}
	
	$message	= "Settings succesfully saved";
	if(isset($_POST['emails'])){
		$message	= "E-mail settings succesfully saved";
	}

	if(isset($_SESSION['plugin'])){
		if(isset($_SESSION['plugin']['installed'])){
			$name	= ucfirst($_SESSION['plugin']['installed']);
			$message	.= "<br><br>Dependend plugin '$name' succesfully installed and activated";
		}elseif(isset($_SESSION['plugin']['activated'])){
			$name	= ucfirst($_SESSION['plugin']['activated']);
			$message	.= "<br><br>Dependend plugin '$name' succesfully activated";
		}
		unset($_SESSION['plugin']);
	}
	?>
	<div class='success'>
		<?php echo esc_html($message);?>
	</div>
	<?php
}

/**
 *Builds the submenu for each module
 */
function buildSubMenu(){
	global $Modules;
	global $moduleDirs;

	$moduleSlug	= str_replace('sim_', '', $_GET['page']); 
	$moduleName	= SIM\getModuleName($moduleDirs[$moduleSlug], ' ');
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
		<h1><?php echo esc_html($moduleName);?> module</h1>

		<?php
		
		$tab	= 'description';
		if(isset($_GET['tab'])){
			$tab	= $_GET['tab'];
		}elseif(!empty($settings['enable'])){
			$tab	= 'settings';
		}

		$descriptionsTab	= descriptionsTab($moduleSlug, $moduleName, $tab);

		$emailSettingsTab	= '';
		$dataTab			= '';
		$functionsTab		= '';
		
		$settingsTab		= settingsTab($moduleSlug, $moduleName, $settings, $tab);

		// Only load if the module is enabled
		if(isset($settings['enable'])){
			$emailSettingsTab	= emailSettingsTab($moduleSlug, $moduleName, $settings, $tab);
			$dataTab			= dataTab($moduleSlug, $moduleName, $settings, $tab);
			$functionsTab		= functionsTab($moduleSlug, $moduleName, $settings, $tab);
		}

		?>
		<div class='tablink-wrapper'>
			<button class="tablink <?php if($tab == 'description'){echo 'active';}?>" id="show_description" data-target="description" >Description</button>
			<button class="tablink <?php if($tab == 'settings'){echo 'active';}?>" id="show_settings" data-target="settings">Settings</button>
			<?php
			if(!empty($emailSettingsTab)){
				?>
				<button class="tablink <?php if($tab == 'emails'){echo 'active';} if(!isset($settings['enable'])){echo 'hidden';}?>" id="show_emails" data-target="emails">E-mail settings</button>
				<?php
			}
			if(!empty($dataTab)){
				?>
				<button class="tablink <?php if($tab == 'data'){echo 'active';} if(!isset($settings['enable'])){echo 'hidden';}?>" id="show_data" data-target="data">Module data</button>
				<?php
			}
			if(!empty($functionsTab)){
				?>
				<button class="tablink <?php if($tab == 'functions'){echo 'active';} if(!isset($settings['enable'])){echo 'hidden';}?>" id="show_functions" data-target="functions">Functions</button>
				<?php
			}
			?>
		</div>
		<?php
		
		handlePost();

		echo $descriptionsTab;
		echo $settingsTab;
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

	ob_start();
	if(!empty($description)){
		?>
		<div class='tabcontent <?php if($tab != 'description'){echo 'hidden';}?>' id='description'>
			<h2>Description</h2>
			
			<?php echo wp_kses($description, 'post'); ?>
			
			<br>
		</div>
		<?php
	}
	
	return ob_get_clean();
}

function settingsTab($moduleSlug, $moduleName, $settings, $tab){
	global $defaultModules;

	ob_start();
	?>
	<div class='tabcontent <?php if($tab != 'settings'){echo 'hidden';}?>' id='settings'>
		<h2>Settings</h2>
			
		<form action="" method="post">
			<input type='hidden' name='module' value='<?php echo esc_html($moduleSlug);?>'>
			<?php
			if(in_array($moduleSlug, $defaultModules)){
				?>
				<input type='hidden' name='enable' value='on'>
				This module is enabled by default<br><br>
				<?php
			}else{
				?>
				Enable <?php esc_html_e($moduleName);?> module
				<label class="switch">
					<input type="checkbox" name="enable" <?php if(isset($settings['enable'])){echo 'checked';}?>>
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
					echo '<div>No special settings needed for this module</div>';
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
				<input type="submit" value="Save <?php echo $moduleName;?> settings">
				<?php
			}
			?>
		</form>
		<br>
	</div>
	<?php

	return ob_get_clean();
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

/**
 * Download new modules or delete them
 */
function mainMenuActions(){
	global $Modules;
	global $moduleDirs;

	if(!empty($_GET['update'])){
		if($_GET['update'] == 'all'){
			SIM\GITHUB\checkForModuleUpdates();
	
			?>
			<div class='success'>All modules updated successfully</div>
			<?php

			return;
		}

		$slug		= sanitize_text_field($_GET['update']);

		$github		= new SIM\GITHUB\Github();

		$result		= $github->downloadFromGithub('Tsjippy', $slug, SIM\MODULESPATH.$slug, true);

		if(is_wp_error($result)){
			echo "<div class='error'>".$result->get_error_message()."</div>";
		}elseif($result){
			?>
			<div class="success">
				Module <?php echo $slug;?> succesfully updated
			</div>
			<?php

			$moduleDirs[$slug]	= $slug;
		}else{
			echo '<div class="error">';
				echo "Module $slug not found on github.<br><br>";
				if(!$github->authenticated){
					$url            = admin_url( "admin.php?page=sim_github&main_tab=settings" );
					echo " maybe you <a href='$url'>should supply a github token</a> so I can try again while logged in.";
				}
			echo "</div>";
		}
	}

	if(!empty($_GET['download'])){
		$slug		= sanitize_text_field($_GET['download']);

		$github		= new SIM\GITHUB\Github();

		$result		= $github->downloadFromGithub('Tsjippy', $slug, SIM\MODULESPATH.$slug, true);

		if(is_wp_error($result)){
			echo "<div class='error'>".$result->get_error_message()."</div>";
		}elseif($result){
			?>
			<div class="success">
				Module <?php echo $slug;?> succesfully downloaded
			</div>
			<?php

			$moduleDirs[$slug]	= $slug;
		}else{
			echo '<div class="error">';
				echo "Module $slug not found on github.<br><br>";
				if(!$github->authenticated){
					$url            = admin_url( "admin.php?page=sim_github&main_tab=settings" );
					echo " maybe you <a href='$url'>should supply a github token</a> so I can try again while logged in.";
				}
			echo "</div>";
		}
	}

	if(!empty($_GET['remove'])){
		$slug		= sanitize_text_field($_GET['remove']);

		if(isset($Modules[$slug])){
			unset($Modules[$slug]);

			update_option('sim_modules', $Modules);
		}

		if(isset($moduleDirs[$slug])){
			WP_Filesystem();
			global $wp_filesystem;
			$result				= $wp_filesystem->rmdir($moduleDirs[$slug], true);

			if($result){

				unset($moduleDirs[$slug]);
				
				?>
				<div class="success">
					Module <?php echo $slug;?> succesfully removed
				</div>
				<?php
			}else{
				?>
				<div class="error">
					Module <?php echo $slug;?> removal unsuccesfull
				</div>
				<?php
			}
		}
	}
}

/**
 * The main plugin menu
 */
function mainMenu(){
	global $Modules;
	global $moduleDirs;

	mainMenuActions();

	$active		= [];
	$inactive	= [];
	$missing	= [];

	// merge the available modules and the installed modules to allow custom modules
	$moduleList	= SIM\MODULELIST;
	foreach($moduleDirs as $path){
		$moduleName	= basename($path);

		if(!in_array($moduleName, $moduleList)){
			$moduleList[]	= $moduleName;
		}
	}

	sort($moduleList);

	foreach($moduleList as $moduleName){
		$moduleSlug	= strtolower($moduleName);
		$moduleName	= SIM\getModuleName($moduleName, ' ');

		// activated and files downloaded
		if(isset($Modules[$moduleSlug]['enable'])){
			if(isset($moduleDirs[$moduleSlug])){
				$active[$moduleSlug]	= $moduleName;
			}
		}else{
			$inactive[$moduleSlug]	= $moduleName;
		}
	}
	
	foreach(array_keys($Modules) as $moduleName){
		if(!in_array($moduleName, array_keys($moduleDirs))){
			$missing[]	= $moduleName;
		}
	}

	$github		= new SIM\GITHUB\Github();
	
	ob_start();

	$updatesAvailable	= false;
	?>
	<div id='release_modal' class='modal hidden'>
		<div class="modal-content" style='width:500px;'>
			<span id="modal_close" class="close">&times;</span>
			<div class='loadergif_wrapper'>
				<img class='loadergif' src='<?php echo SIM\LOADERIMAGEURL;?>' width=50 loading='lazy'>
				Loading changelog...
			</div>
			<div class="content"></div>
		</div>
	</div>
	<div>
		<strong>Current active modules</strong><br>
		<table class="table">
			<thead>
				<tr>
					<th>Name</th>
					<th>Version</th>
				</tr>
			</thead>
			
			<?php
			foreach($active as $slug=>$name){
				$url		= admin_url("admin.php?page=".$_GET['page']);
				$release	= '';
				$update		= false;
				$content	= '';
				if( defined("SIM\\$slug\\MODULE_VERSION")){
					$content	= constant("SIM\\$slug\\MODULE_VERSION");
				}

				// Default module version
				if(!empty($moduleDirs[$slug]) && str_contains($moduleDirs[$slug], 'default_modules')){

				}

				// Skip the update check if just updated
				elseif(empty($_GET['update']) || $_GET['update'] != $slug){
					// Check if update available
					$force		= false;
					if(!empty($_GET['force'])){
						$force	= true;
					}
					$release	= $github->getLatestRelease('tsjippy', $slug, $force);

					
					if( 
						!is_wp_error($release) &&													// no error during the getting the release info
						isset($release['tag_name'])													// release has a tagname
					){
						// the module version for this module is set
						if( !empty($content)){
							if( 
								version_compare($release['tag_name'], $content)	// the release version is bigger than the current version
							){
								$update	= true;
							}
						}else{
							$update	= true;
						}
					}
				}

				echo "<tr>";
					echo "<td><a href='{$url}_$slug'>$name</a></td>";
						if($update){
							$updatesAvailable	= true;
							$content 		   .= " <a href='$url&update=$slug' class='button sim small' style='margin-left:15px;margin-right:15px;'>Update to version {$release['tag_name']}</a>";
							$content 		   .= "<button type='button' class='sim small release' data-name='$slug'>Show info</button>";
						}
						echo "<td>$content</td>";
				echo "</tr>";
			}
			?>
		</table>

		<br>
		<strong>Currently not installed modules</strong><br>
		<?php
			if(empty($inactive)){
				echo "All modules are activated";
			}else{
				?>
				<table class="table">
					<?php
					foreach($inactive as $slug=>$name){
						echo "<tr>";
							echo "<td><a href='https://github.com/Tsjippy/$slug' target='_blank'>$name</a></td>";
							// Module is downloaded but inactive
							if(in_array($slug, array_keys($moduleDirs))){
								$url	= admin_url("admin.php?page=sim_$slug");
								$url2	= admin_url("admin.php?page={$_GET['page']}&remove=$slug");
								echo "<td><a href='$url'>Activate</a></td><td><a href='$url2' class='button sim small'>Delete</a></td>";
							}else{
								// Available for download
								$url	= admin_url("admin.php?page={$_GET['page']}&download=$slug");
								echo "<td><a href='$url' class='button sim small'>Download</a></td>";
							}
						echo "</tr>";
					}
					?>
				</table>
				<?php
			}
			?>

		<br>
		<strong>Current uninstalled but active modules</strong><br>
			<?php
			if(empty($missing)){
				echo "No missing modules";
			}else{
				?>
				<table class="table">
					<?php
					foreach($missing as $slug){
						$url	= admin_url("admin.php?page={$_GET['page']}&download=$slug");
						$url2	= admin_url("admin.php?page={$_GET['page']}&remove=$slug");
						?>
						<tr>
							<td><?php echo ucfirst($slug);?></td>
							<td><a href='<?php echo $url; ?>' class='button sim small'>Download</a></td>
							<td><a href='<?php echo $url2; ?>' class='button sim small'>Delete</a></td>
						</tr>
						<?php
					}
					?>
				</table>
				<?php
			}
			?>
	</div>
	<?php

	$tableHtml	= ob_get_clean();
	if($updatesAvailable){
		?>
		<a href='<?php echo SIM\getCurrentUrl();?>&update=all' class='button sim small'>Update all</a>
		<?php
	}
	echo $tableHtml;
}
