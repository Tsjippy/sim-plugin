<?php
namespace SIM;

/**
 * Register a custom menu page.
 */
add_action( 'admin_menu', 'SIM\register_admin_menu_page' );
function register_admin_menu_page() {
	add_menu_page("Custom SIM Nigeria", "SIM Nigeria", 'manage_options', "custom_simnigeria", "SIM\admin_menu");
	add_submenu_page('custom_simnigeria', "Functions", "Functions", "manage_options", "custom_simnigeria_functions", "SIM\admin_menu_functions");
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

function admin_menu(){
	global $CustomSimSettings;
	global $Trello;
	
	ob_start();
	?>
	<div class="wrap">
        <h1>Define custom settings</h1>
		<?php		
		if (isset($_POST["save_custom_sim_settings"])){
			//Check if we need to update the trello webhook
			if(!empty($_POST['trello_list']) and $_POST['trello_list'] != $CustomSimSettings['trello_list']){
				$Trello->change_webhook_id($Trello->get_webhooks()[0]->id, $_POST['trello_list']);
				print_array("Updated webhook modelid");
			}
			
			//Trello token has changed
			if($_POST['trellopapitoken'] != $CustomSimSettings['trellopapitoken']){
				$new_trello_token = true;
				
				//remove all webhooks from the old token
				$Trello->delete_all_webhooks();
				
				print_array("Removed all webhooks");
				
				//remove trello info belonging to the old token
				unset($_POST['trello_list']);
				unset($_POST['trello_board']);
				unset($_POST['trello_destination_list']);
			}else{
				$new_trello_token = false;
			}
			
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
			
			//Now that the new trello token is set, lets create new webhooks
			if($new_trello_token == true){
				$new_trello = new trello();
				
				//remove all webhooks from the new token
				$new_trello->delete_all_webhooks();
				
				//Get the userid belonging to the new token
				$trello_user_id = $new_trello->get_token_info()->id;
				
				//Create a webhook listening to the userid
				print_array('Created new webhook');
				
				$new_trello->create_webhook('https://simnigeria.org/trello_webhook', $trello_user_id, "Listens to all actions related to the user with id $trello_user_id");
			}
		}
		?>
		<form action="" method="post" id="set_settings" name="set_settings">
			<table class="form-table">
				<tr>
					<th><label for="logged_in_home_page">Page for logged in users</label></th>
					<td>
						<?php echo page_select("logged_in_home_page",$CustomSimSettings["logged_in_home_page"]); ?>
					</td>
				</tr>
				<tr>
					<th><label for="missionaries_page">Missionaries page</label></th>
					<td>
						<?php echo page_select("missionaries_page",$CustomSimSettings["missionaries_page"]); ?>
					</td>
				</tr>
				<tr>
					<th><label for="welcome_page">Welcome page</label></th>
					<td>
						<?php echo page_select("welcome_page",$CustomSimSettings["welcome_page"]); ?>
					</td>
				</tr>
				<tr>
					<th><label for="publish_post_page">Page with the post edit form</label></th>
					<td>
						<?php echo page_select("publish_post_page",$CustomSimSettings["publish_post_page"]); ?>
					</td>
				</tr>
				<tr>
					<th><label for="profile_page">Page with the profile data</label></th>
					<td>
						<?php echo page_select("profile_page",$CustomSimSettings["profile_page"]); ?>
					</td>
				</tr>
				<tr>
					<th><label for="pw_reset_page">Page with the password reset form</label></th>
					<td>
						<?php echo page_select("pw_reset_page",$CustomSimSettings["pw_reset_page"]); ?>
					</td>
				</tr>
				<tr>
					<th><label for="register_page">Page with the registration form for new users</label></th>
					<td>
						<?php echo page_select("register_page",$CustomSimSettings["register_page"]); ?>
					</td>
				</tr>
				<tr>
					<th><label for="signal_group_link">Link to join the Signal group</label></th>
					<td>
						<input type='url' name='signal_group_link' value=<?php echo $CustomSimSettings["signal_group_link"]; ?> style='width:100%'>
					</td>
				</tr>
				<tr>
					<th><label for="placesmapid">Map showing all markers</label></th>
					<td>
						<select name="placesmapid" id="placesmapid">
							<option value="">---</option>
							<?php echo get_maps("placesmapid"); ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="missionariesmapid">Map showing all missionaries</label></th>
					<td>
						<select name="missionariesmapid" id="missionariesmapid">
							<option value="">---</option>
							<?php echo get_maps("missionariesmapid"); ?>
						</select>
					</td>
				</tr>
				
				<?php
				
				$categories = get_categories( array(
					'orderby' 	=> 'name',
					'order'   	=> 'ASC',
					'taxonomy'	=> 'locationtype',
					'hide_empty'=> false,
				) );
				foreach($categories as $locationtype){
					$name 				= $locationtype->slug;
					$map_name			= $name."_map";
					$icon_name			= $name."_icon";
					?>
					<tr>
						<th><label for="<?php echo $map_name;?>">Map showing <?php echo strtolower($name);?></label></th>
						<td>
							<select name="<?php echo $map_name;?>" id="<?php echo $map_name;?>">
								<option value="">---</option>
								<?php echo get_maps($map_name); ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="<?php echo $icon_name;?>">Id of the icon on the map used for <?php echo $name;?></label></th>
						<td>
							<input type="text" name="<?php echo $icon_name;?>" id="<?php echo $icon_name;?>" value="<?php echo $CustomSimSettings[$icon_name]; ?>">
						</td>
					</tr>
					<?php
				}
				?>

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
					<th><label for="mailchimpapi">Mailchimp API key</label></th>
					<td>
						<input type="text" name="mailchimpapi" id="mailchimpapi" value="<?php echo $CustomSimSettings["mailchimpapi"]; ?>" style="width:100%;">
					</td>
				</tr>
				<tr>
					<th><label>Mailchimp audience(s) you want new users added to</label></th>
				<?php
				$Mailchimp = new Mailchimp();
				$lists = (array)$Mailchimp->get_lists();
				foreach ($lists as $key=>$list){
					if($CustomSimSettings["mailchimp_audienceids"][$key]==$list->id){
						$checked = 'checked="checked"';
					}else{
						$checked = '';
					}
					echo '<td>
						<input type="checkbox" id="mailchimp_audienceids['.$key.']" name="mailchimp_audienceids['.$key.']" value="'.$list->id.'" '.$checked.'>
						<label for="mailchimp_audienceids['.$key.']">'.$list->name.'</label>
					</td>';
				}				
				?>
				</tr>
				<tr>
					<th><label>Mailchimp TAGs you want to add to new users</label></th>
					<td>
						<input type="text" id="mailchimp_user_tags" name="mailchimp_user_tags" value="<?php echo $CustomSimSettings["mailchimp_user_tags"]; ?>">
					</td>
				</tr>
				<tr>
					<th><label>Mailchimp TAGs you want to add to missionaries</label></th>
					<td>
						<input type="text" id="mailchimp_missionary_tags" name="mailchimp_missionary_tags" value="<?php echo $CustomSimSettings["mailchimp_missionary_tags"]; ?>">
					</td>
				</tr>
				<tr>
					<th><label>Mailchimp TAGs you want to add to office staff</label></th>
					<td>
						<input type="text" id="mailchimp_office_staff_tags" name="mailchimp_office_staff_tags" value="<?php echo $CustomSimSettings["mailchimp_office_staff_tags"]; ?>">
					</td>
				</tr>
				<tr>
					<th><label for="mailchimptemplateid">Mailchimp template id</label></th>
					<td>
						<input type="text" name="mailchimptemplateid" id="mailchimptemplateid" value="<?php echo $CustomSimSettings["mailchimptemplateid"]; ?>" style="width:100%;">
					</td>
				</tr>
				<tr>
					<th><label for="trellopapikey">Trello API key</label></th>
					<td>
						<input type="text" name="trellopapikey" id="trellopapikey" value="<?php echo $CustomSimSettings["trellopapikey"]; ?>" style="width:100%;">
					</td>
				</tr>
				<tr>
					<th><label for="trellopapitoken">Trello API token</label></th>
					<td>
						<input type="text" name="trellopapitoken" id="trellopapitoken" value="<?php echo $CustomSimSettings["trellopapitoken"]; ?>" style="width:100%;">
					</td>
				</tr>
				<?php
				if(isset($CustomSimSettings["trellopapikey"]) and isset($CustomSimSettings["trellopapitoken"]) and strpos(get_site_url(), 'localhost') === false){
					?>
					<tr>
						<th><label>Trello board you want listen to</label></th>
						<td>
							<select name='trello_board' id='trello_board'>
							<option value="">---</option>
					<?php
					$Trello->get_boards();
					foreach ($Trello->get_boards() as $name=>$id){
						if($CustomSimSettings["trello_board"] == $id){
							$selected = 'selected="selected"';
						}else{
							$selected = '';
						}
						echo "<option value='$id' $selected>$name</option>";

					}
					?>
							</select>
						</td>
					</tr>
					<?php
				}
				?>
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

//Location maps
function get_maps($option_key){
	global $wpdb;
	global $CustomSimSettings;
	$map_options = "";
	$option_value = $CustomSimSettings[$option_key];

	$query = 'SELECT  `id`,`title` FROM `'.$wpdb->prefix .'ums_maps` WHERE 1';
	$result = $wpdb->get_results($query,ARRAY_A);
	foreach ( $result as $map ) {
		if ($option_value == $map['id']){
			$selected='selected=selected';
		}else{
			$selected="";
		}
		$option = '<option value="' . $map['id'] . '" '.$selected.'>';
		$option .= $map['title'];
		$option .= '</option>';
		$map_options .= $option;
	}
	return $map_options;
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

//Add expiry data column to users screen
add_filter( 'manage_users_columns', 'SIM\add_expiry_date_to_user_table' );
function add_expiry_date_to_user_table( $column ) {
    $column['expiry_date'] = 'Expiry Date';
    return $column;
}

//Add content to the expiry data column
add_filter( 'manage_users_custom_column', 'SIM\add_expiry_date_to_user_table_row', 10, 3 );
function add_expiry_date_to_user_table_row( $val, $column_name, $user_id ) {
    switch ($column_name) {
        case 'expiry_date' :
            return get_user_meta( $user_id, 'account_validity',true);
        default:
    }
    return $val;
}

add_filter( 'manage_users_sortable_columns', 'SIM\make_expiry_date_sortable' );
function make_expiry_date_sortable( $columns ) {
    $columns['expiry_date'] = 'Expiry Date';

    return $columns;
}