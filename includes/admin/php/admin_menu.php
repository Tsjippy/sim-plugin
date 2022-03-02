<?php
namespace SIM;

//load js and css
add_action( 'admin_enqueue_scripts', function () {
	$module_version		= '7.0.0';

	//Only load on sim settings pages
	if(strpos(get_current_screen()->base, 'sim-settings') === false) return;

	wp_enqueue_style('sim_admin_css', plugins_url('css/admin.min.css', __DIR__), array(), $module_version);
	wp_enqueue_script('sim_admin_js', plugins_url('js/admin.js', __DIR__), array('niceselect') ,$module_version, true);
});

/**
 * Register a custom menu page.
 */
add_action( 'admin_menu', 'SIM\register_admin_menu_page' );
function register_admin_menu_page() {
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
			}
		}
		update_option('sim_modules', $Modules);
	}

	add_menu_page("SIM Plugin Settings", "SIM Settings", 'manage_options', "sim", "SIM\main_menu");
	add_submenu_page('sim', "Functions", "Functions", "manage_options", "sim_functions", "SIM\admin_menu_functions");

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
			print_array("Module page does not exist for module $module_name");
			continue;
		}

		//load the menu page php file
		require_once($module.'/php/module_menu.php');

		add_submenu_page('sim', $module_name." module", $module_name, "manage_options", "sim_$module_slug", "SIM\build_submenu");
	}
}

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
		add_cron_schedules();
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
	global $CustomSimSettings;
	global $Modules;

	//get all modules based on folder name
	$modules	= glob(__DIR__ . '/../../modules/*' , GLOB_ONLYDIR);

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
		foreach($inactive as $slug=>$name){
			$url	= admin_url("admin.php?page=sim_$slug");
			echo "<li><a href='$url'>$name</a></li>";
		}
		?>
		</ul>
	</div>
	<div class="wrap">
        <h1>Define custom settings</h1>
		<?php		
		if (isset($_POST["save_custom_sim_settings"])){
			//Save everything
			foreach($_POST as $key => $value){
				if ($key != "save_custom_sim_settings"){
					if(is_array($value)){
						$CustomSimSettings[$key] = $value;
					}else{
						$CustomSimSettings[$key] = str_replace('\\',"",$value);
					}
				}
			}

			update_option("customsimsettings",$CustomSimSettings);
		}
		?>
		<form action="" method="post" id="set_settings" name="set_settings">
			<table class="form-table">
				<tr>
					<th><label for="welcome_page">Welcome page</label></th>
					<td>
						<?php echo page_select("welcome_page",$CustomSimSettings["welcome_page"]); ?>
					</td>
				</tr>

				<tr>
					<th><label for="profile_page">Page with the profile data</label></th>
					<td>
						<?php echo page_select("profile_page",$CustomSimSettings["profile_page"]); ?>
					</td>
				</tr>
				
				<tr>
					<th><label for="publiccategory">Category used for public news and pages</label></th>
					<td>
						<select name="publiccategory" id="publiccategory">
							<option value="">---</option>
							<?php echo get_tax_categories("publiccategory"); ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="confcategory">Category used for confidential news and pages</label></th>
					<td>
						<select name="confcategory" id="confcategory">
							<option value="">---</option>
							<?php echo get_tax_categories("confcategory"); ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="prayercategory">Category used for prayer news</label></th>
					<td>
						<select name="prayercategory" id="prayercategory">
							<option value="">---</option>
							<?php echo get_tax_categories("prayercategory"); ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="financecategory">Category used for financial news</label></th>
					<td>
						<select name="financecategory" id="financecategory">
							<option value="">---</option>
							<?php echo get_tax_categories("financecategory"); ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="ministrycategory">Category used for ministries</label></th>
					<td>
						<select name="ministrycategory" id="ministrycategory">
							<option value="">---</option>
							<?php echo get_tax_categories("ministrycategory",'locationtype'); ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="postoutofdatawarning">Age in months after which users will be requested to update page contents</label></th>
					<td>
						<input type="text" name="postoutofdatawarning" id="postoutofdatawarning" value="<?php echo $CustomSimSettings["postoutofdatawarning"]; ?>">
					</td>
				</tr>
				<tr>
					<th><label for="vaccinationoutofdatawarning">Months before the expiry date of a vaccination people should get a warning</label></th>
					<td>
						<input type="text" name="vaccinationoutofdatawarning" id="vaccinationoutofdatawarning" value="<?php echo $CustomSimSettings["vaccinationoutofdatawarning"]; ?>">
					</td>
				</tr>
				<tr>
					<th><label for="webmastername">Name of the webmaster</label></th>
					<td>
						<input type="text" name="webmastername" id="webmastername" value="<?php echo $CustomSimSettings["webmastername"]; ?>">
					</td>
				</tr>
				
				<tr>
					<th><label for="personnel_email">Personnel e-mail</label></th>
					<td>
						<input type="email" name="personnel_email" id="personnel_email" value="<?php echo $CustomSimSettings["personnel_email"]; ?>">
					</td>
				</tr>
				<tr>
					<th><label for="sta_email">Short Term Coordinator e-mail</label></th>
					<td>
						<input type="email" name="sta_email" id="sta_email" value="<?php echo $CustomSimSettings["sta_email"]; ?>">
					</td>
				</tr>
				<tr>
					<th><label for="finance_email">Finance e-mail</label></th>
					<td>
						<input type="email" name="finance_email" id="finance_email" value="<?php echo $CustomSimSettings["finance_email"]; ?>">
					</td>
				</tr>
				<tr>
					<th><label for="medical_email">Health Coordinator e-mail</label></th>
					<td>
						<input type="email" name="medical_email" id="medical_email" value="<?php echo $CustomSimSettings["medical_email"]; ?>">
					</td>
				</tr>
				<tr>
					<th><label for="required_account_fields">Required account fields</label></th>
					<td>
						<textarea style="width: 100%;" rows="20" name="required_account_fields" id="required_account_fields"><?php echo $CustomSimSettings["required_account_fields"]; ?></textarea>
					</td>
				</tr>
				<tr>
					<th><label for="recommended_fields">Recommendeded fields</label></th>
					<td>
						<textarea style="width: 100%;" rows="5" name="recommended_fields" id="recommended_fields"><?php echo $CustomSimSettings["recommended_fields"]; ?></textarea>
					</td>
				</tr>
			</table>
			<button type="submit" class="button" name="save_custom_sim_settings" id="save_custom_sim_settings">Save settings</button>
		</form>
    </div>
	<?php
	echo ob_get_clean();
}

//categories
function get_tax_categories($option_key,$taxonomy='category'){
	global $CustomSimSettings;
	$category_options = "";
	$option_value = $CustomSimSettings[$option_key];
	$categories = get_categories(
		array("hide_empty" => 0,     
			"orderby"   => "name",
			"order"     => "ASC",
			'taxonomy'	=> $taxonomy
		)
	);
	foreach($categories as $category){
		if ($option_value == $category->term_id){
			$selected='selected=selected';
		}else{
			$selected="";
		}
		$option = '<option value="' . $category->term_id . '" '.$selected.'>';
		$option .= $category->name;
		$option .= '</option>';
		$category_options .= $option;
	}
	
	return $category_options;
}

//Add link to the user menu to resend the confirmation e-mail
add_filter( 'user_row_actions', 'SIM\add_send_welcome_mail_action', 10, 2 );
function add_send_welcome_mail_action( $actions, $user ) {
    $actions['Resend welcome mail'] = '<a href="'.get_site_url().'/wp-admin/users.php?send_activation_email='.$user->ID.'">Resend email</a>';
    return $actions;
}

add_action('init', function() {
	//Process the request
	if(is_numeric($_GET['send_activation_email'])){
		$email = get_userdata($_GET['send_activation_email'])->user_email;
		print_array("Sending welcome email to $email");
		wp_new_user_notification($_GET['send_activation_email'],null,'both');
	}
});

