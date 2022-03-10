<?php
namespace SIM\ADMIN;
use SIM;

const ModuleVersion		= '7.0.0';

/**
 * Register a custom menu page.
 */
add_action( 'admin_menu', function() {
	global $Modules;

	if(isset($_POST['module'])){
		$module_slug	= $_POST['module'];
		$options		= $_POST;
		unset($options['module']);

		//module was already activated
		if(isset($Modules[$module_slug])){
			//deactivate the module
			if(!isset($options['enable'])){
				unset($Modules[$module_slug]);
				do_action('sim_module_deactivated', $module_slug, $options);
			}elseif(!empty($options)){
				$Modules[$module_slug]	= $options;
				do_action('sim_module_updated', $module_slug, $options);
			}
		//module needs to be activated
		}else{
			if(!empty($options)){
				$Modules[$module_slug]	= $options;
				do_action('sim_module_activated', $module_slug, $options);
				do_action('sim_module_updated', $module_slug, $options);
			}
		}
		update_option('sim_modules', $Modules);
	}

	add_menu_page("SIM Plugin Settings", "SIM Settings", 'edit_others_posts', "sim", "SIM\ADMIN\main_menu");
	add_submenu_page('sim', "Functions", "Functions", "edit_others_posts", "sim_functions", "SIM\ADMIN\admin_menu_functions");

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

		add_submenu_page('sim', $module_name." module", $module_name, "edit_others_posts", "sim_$module_slug", "SIM\ADMIN\build_submenu");
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
		<form action="" method="post">
			<input type='hidden' name='module' value='<?php echo $module_slug;?>'>

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

function admin_menu_functions(){
	if (isset($_POST['add_cronschedules'])){
		SIM\add_cron_schedules();
	}
	
	?>
	<h3>Available custom functions</h3>
	<form action="" method="post">
		<label>Click to reactivate schedules</label>
		<br>
		<input type='hidden' name='add_cronschedules'>
		<input type="submit" value="Reactivate schedules">
	</form> 
	<br>
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

//Add link to the user menu to resend the confirmation e-mail
add_filter( 'user_row_actions', 'SIM\ADMIN\add_send_welcome_mail_action', 10, 2 );
function add_send_welcome_mail_action( $actions, $user ) {
    $actions['Resend welcome mail'] = "<a href='".SITEURL."/wp-admin/users.php?send_activation_email=$user->ID'>Resend email</a>";
    return $actions;
}

add_action('init', function() {
	//Process the request
	if(is_numeric($_GET['send_activation_email'])){
		$email = get_userdata($_GET['send_activation_email'])->user_email;
		SIM\print_array("Sending welcome email to $email");
		wp_new_user_notification($_GET['send_activation_email'],null,'both');
	}
});

function recurrenceSelector($curFreq){
	?>
	<option value=''>---</option>
	<option value='daily' <?php if($curFreq == 'daily') echo 'selected';?>>Daily</option>
	<option value='weekly' <?php if($curFreq == 'weekly') echo 'selected';?>>Weekly</option>
	<option value='monthly' <?php if($curFreq == 'monthly') echo 'selected';?>>Monthly</option>
	<option value='threemonthly' <?php if($curFreq == 'threemonthly') echo 'selected';?>>Every quarter</option>
	<option value='sixmonthly' <?php if($curFreq == 'sixmonthly') echo 'selected';?>>Every half a year</option>
	<option value='yearly' <?php if($curFreq == 'yearly') echo 'selected';?>>Yearly</option>
	<?php
}