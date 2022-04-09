<?php
namespace SIM\ADMIN;
use SIM;

const ModuleVersion		= '7.0.1';

/**
 * Register a custom menu page.
 */
add_action( 'admin_menu', function() {
	if(isset($_POST['module'])){
		save_settings();
	}

	add_menu_page("SIM Plugin Settings", "SIM Settings", 'edit_others_posts', "sim", __NAMESPACE__."\main_menu");

	//get all modules based on folder name
	$modules	= glob(__DIR__ . '/../../modules/*' , GLOB_ONLYDIR);
	//Sort alphabeticalyy, ignore case
	sort($modules, SORT_STRING | SORT_FLAG_CASE);

	foreach($modules as $module){
		$module_slug	= basename($module);
		$module_name	= ucwords(str_replace(['_', '-'], ' ', $module_slug));
		
		//do not load admin and template menu
		if(in_array($module_slug, ['__template', 'admin'])) continue;
		
		//check module page exists
		if(!file_exists($module.'/php/module_menu.php')){
			SIM\print_array("Module page does not exist for module $module_name");
			continue;
		}

		//load the menu page php file
		require_once($module.'/php/module_menu.php');
		if(file_exists($module.'/php/class_emails.php'))	include_once($module.'/php/class_emails.php');

		add_submenu_page('sim', $module_name." module", $module_name, "edit_others_posts", "sim_$module_slug", __NAMESPACE__."\build_submenu");
	}
});

function build_submenu(){
	global $plugin_page;
	global $Modules;

	$module_slug	= str_replace('sim_','',$plugin_page);
	$module_name	= ucwords(str_replace(['_', '-'], ' ', $module_slug));
	$settings		= $Modules[$module_slug];

	echo '<div class="module-settings">';

		if(isset($_POST['module'])){
			?>
			<div class='success'>
				Settings succesfully saved
			</div>
			<?php
		}
		?>
		<h1><?php echo $module_name;?> module</h1>
		<?php 
		ob_start();
		do_action('sim_submenu_description', $module_slug, $module_name);
		$description = ob_get_clean();

		if(!empty($description)){
			echo "<h2>Description</h2>";
			echo $description;
		}
		?>
		<h2>Settings</h2>
		
		<form action="" method="post">
			<input type='hidden' name='module' value='<?php echo $module_slug;?>'>
			Enable <?php echo $module_name;?> module 
			<label class="switch">
				<input type="checkbox" name="enable" <?php if($settings['enable']) echo 'checked';?>>
				<span class="slider round"></span>
			</label>
			<br>
			<br>
			<div class='options' <?php if(!$settings['enable']) echo "style='display:none'";?>>
				<?php 
				ob_start();
				do_action('sim_submenu_options', $module_slug, $module_name, $settings);
				$html	= ob_get_clean();
				if(empty($html)){
					$html = '<div>No special settings needed for this module</div>';
				}

				echo $html;
				?>
			</div>
			<br>
			<br>
			<input type="submit" value="Save <?php echo $module_name;?> options">
		</form> 
		<br>
	</div>
	<?php
}

function main_menu(){
	global $Modules;

	//get all modules based on folder name
	$modules	= glob(__DIR__ . '/../../modules/*' , GLOB_ONLYDIR);
	sort($modules, SORT_STRING | SORT_FLAG_CASE);

	$active		= [];
	$inactive	= [];
	foreach($modules as $module){
		$module_slug	= basename($module);
		$module_name	= ucwords(str_replace(['_', '-'], ' ', $module_slug));
		
		if(in_array($module_slug, array_keys($Modules))){
			$active[$module_slug]	= $module_name;
		}elseif($module_slug != "__template"){
			$inactive[$module_slug]	= $module_name;
		}
	}
	
	ob_start();
	?>
	<div>
		<b>Current active modules</b><br>
		<ul class="sim-list">
		<?php
		foreach($active as $slug=>$name){
			$url	= admin_url("admin.php?page=sim_$slug");
			echo "<li><a href='$url'>$name</a></li>";
		}
		?>
		</ul>
		<b>Current inactive modules</b><br>
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