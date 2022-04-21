<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Shortcode for adding user accounts
add_shortcode('create_user_account', function ($atts){
	$user = wp_get_current_user();
	if ( in_array('usermanagement',$user->roles)){		
		ob_start();
		?>
		<div class="tabcontent">
			<form class='sim_form' data-reset="true">
				<p>Please fill in the form to create an user account</p>
				
				<label>
					<h4>First name<span class="required">*</span></h4>
					<input type="text" name="first_name" value="" required>
				</label>
				
				<label>
					<h4>Last name<span class="required">*</span></h4>
					<input type="text" class="" name="last_name" required>
				</label>
				
				<label>
					<h4>E-mail<span class="required">*</span></h4>
					<input class="" type="email" name="email" required>
				</label>
				
				<label>
					<h4>Valid for<span class="required">*</span></h4>
				</label>
				<select name="validity" class="form-control relation" required>
					<option value="">---</option>
					<option value="1">1 month</option>
					<option value="3">3 months</option>
					<option value="6">6 months</option>
					<option value="12">1 year</option>
					<option value="24">2 years</option>
					<option value="unlimited">Always</option>
				</select>
				<?php 
				echo SIM\add_save_button('adduseraccount', 'Add user account');
			echo '</form>';
		echo '</div>';
		
		return ob_get_clean();
	}else{
		return "You have no permission to see this";
	}
});

//Shortcode to display the pending user accounts
add_shortcode('pending_user',function ($atts){
	if ( current_user_can( 'edit_others_pages' ) ) {
		//Delete user account if there is an url parameter for it
		if(isset($_GET['delete_pending_user'])){
			//Get user id from url parameter
			$UserId = $_GET['delete_pending_user'];
			//Check if the user account is still pending
			if(get_user_meta($UserId,'disabled',true) == 'pending'){
				//Load delete function
				require_once(ABSPATH.'wp-admin/includes/user.php');
				
				//Delete the account
				$result = wp_delete_user($UserId);
				if ($result == true){
					//show succesmessage
					echo '<div class="success">User succesfully removed</div>';
				}
			}
		}
		
		//Activate useraccount
		if(isset($_GET['activate_pending_user'])){
			//Get user id from url parameter
			$UserId = $_GET['activate_pending_user'];
			//Check if the user account is still pending
			if(get_user_meta($UserId,'disabled',true) == 'pending'){
				//Send welcome-email
				wp_new_user_notification($UserId, null, 'user');

				//Make approved
				delete_user_meta( $UserId, 'disabled');

				// 
				do_action('sim_after_user_approval', $userId);
				
				echo '<div class="success">Useraccount succesfully activated</div>';
			}
		}
		
		//Display pening user accounts
		$initial_html = "";
		$html = $initial_html;
		//Get all the users who need approval
		$pending_users = get_users(array(
			'meta_key'     => 'disabled',
			'meta_value'   => 'pending',
			'meta_compare' => '=',
		));
		
		// Array of WP_User objects.
		if ( $pending_users ) {
			$html .= "<p><strong>Pending user accounts:</strong><br><ul>";
			foreach ( $pending_users as $pending_user ) {
				$userdata = get_userdata($pending_user->ID);
				$approve_url = add_query_arg( 'activate_pending_user', $pending_user->ID);
				$delete_url = add_query_arg( 'delete_pending_user', $pending_user->ID);
				$html .= '<li>'.$pending_user->display_name.'  ('.$userdata->user_email.') <a href="'.$approve_url.'">Approve</a>   <a href="'.$delete_url.'">Delete</a></li>';
			}
		}else{
			return "<p>There are no pending user accounts.</p>";
		}
		
		if ($html != $initial_html){
			$html.="</ul></p>";
			return $html;
		}
	}
});

//Shortcode to display number of pending user accounts
add_shortcode('pending_user_icon',function ($atts){
	$pending_users = get_users(array(
		'meta_key'     => 'disabled',
		'meta_value'   => 'pending',
		'meta_compare' => '=',
	));
	
	if (count($pending_users) > 0){
		return '<span class="numberCircle">'.count($pending_users).'</span>';
	}
});

//Shortcode for the dashboard
add_action('sim_dashboard_warnings', function($user_id){
	$personnelCoordinatorEmail	= SIM\get_module_option('user_managment', 'personnel_email');

	if(is_numeric($_GET["userid"]) and in_array('usermanagement', wp_get_current_user()->roles )){
		$user_id	= $_GET["userid"];
	}else{
		$user_id = get_current_user_id();
	}
	$remindercount = 0;
	$reminder_html = "";
	
	$visa_info = get_user_meta( $user_id, "visa_info",true);
	if (is_array($visa_info) and isset($visa_info['greencard_expiry'])){
		$reminder_html .= check_expiry_date($visa_info['greencard_expiry'],'greencard');
		if($reminder_html != ""){
			$remindercount = 1;
			$reminder_html .= '<br>';
		}
	}
		
	$vaccination_reminder_html = vaccination_reminders($user_id);
	
	if ($vaccination_reminder_html != ""){
		$remindercount += 1;
		$reminder_html .= $vaccination_reminder_html ;
	}
	
	//Check for children
	$family = get_user_meta($user_id,"family",true);
	//User has children
	if (isset($family["children"])){
		$child_vaccination_reminder_html = "";
		foreach($family["children"] as $key=>$child){
			$result = vaccination_reminders($child);
			if ($result != ""){
				$remindercount += 1;
				$userdata = get_userdata($child);
				$reminder_html .= str_replace("Your",$userdata->first_name."'s",$result);
			}
		}
	}
	
	//Check for upcoming reviews, but only if not set to be hidden for this year
	if(get_user_meta($user_id,'hide_annual_review',true) != date('Y')){
		$personnel_info 				= get_user_meta($user_id,"personnel",true);
		if(is_array($personnel_info) and !empty($personnel_info['review_date'])){
			//Hide annual review warning
			if(isset($_GET['hide_annual_review']) and $_GET['hide_annual_review'] == date('Y')){
				//Save in the db
				update_user_meta($user_id,'hide_annual_review',date('Y'));
				
				//Get the current url withouth the get params
				$url = str_replace('hide_annual_review='.date('Y'),'', SIM\current_url());
				//redirect to same page without params
				header ("Location: $url");
			}
			
			$reviewdate	= date('F', strtotime($personnel_info['review_date']));
			//If this month is the review month or the month before the review month
			if($reviewdate == date('F') or date('F', strtotime('-1 month',strtotime($reviewdate))) == date('F')){			
				$generic_documents = get_option('personnel_documents');
				if(is_array($generic_documents) and !empty($generic_documents['Annual review form'])){
					$reminder_html .= "Please fill in the annual review questionary.<br>";
					$reminder_html .= 'Find it <a href="'.SITEURL.'/'.$generic_documents['Annual review form'].'">here</a>.<br>';
					$reminder_html .= 'Then send it to the <a href="mailto:'.$personnelCoordinatorEmail.'?subject=Annual review questionary">Personnel coordinator</a><br>';
					$url = add_query_arg( 'hide_annual_review', date('Y'), SIM\current_url() );
					$reminder_html .= '<a class="button sim" href="'.$url.'" style="margin-top:10px;">I already send it!</a><br>';
				}
			}
		}
	}
	
	if ($reminder_html != ""){
		$html = '<h3 class="frontpage">';
		if($remindercount > 1){
			$html .= 'Reminders</h3><p>'.$reminder_html;
		}else{
			$reminder_html = str_replace('</li>','',str_replace('<li>',"",$reminder_html));
			$html .= 'Reminder</h3><p>'.$reminder_html;
		}
		
		$html =  '<div id=reminders>'.$html.'</p></div>';
	}
	
	echo $html;
});

//Shortcode for expiry warnings
add_shortcode("expiry_warnings",function (){
	$personnelCoordinatorEmail	= SIM\get_module_option('user_managment', 'personnel_email');

	if(is_numeric($_GET["userid"]) and in_array('usermanagement', wp_get_current_user()->roles )){
		$user_id	= $_GET["userid"];
	}else{
		$user_id = get_current_user_id();
	}
	$remindercount = 0;
	$reminder_html = "";
	
	$visa_info = get_user_meta( $user_id, "visa_info",true);
	if (is_array($visa_info) and isset($visa_info['greencard_expiry'])){
		$reminder_html .= check_expiry_date($visa_info['greencard_expiry'],'greencard');
		if($reminder_html != ""){
			$remindercount = 1;
			$reminder_html .= '<br>';
		}
	}
		
	$vaccination_reminder_html = vaccination_reminders($user_id);
	
	if ($vaccination_reminder_html != ""){
		$remindercount += 1;
		$reminder_html .= $vaccination_reminder_html ;
	}
	
	//Check for children
	$family = get_user_meta($user_id,"family",true);
	//User has children
	if (isset($family["children"])){
		$child_vaccination_reminder_html = "";
		foreach($family["children"] as $key=>$child){
			$result = vaccination_reminders($child);
			if ($result != ""){
				$remindercount += 1;
				$userdata = get_userdata($child);
				$reminder_html .= str_replace("Your",$userdata->first_name."'s",$result);
			}
		}
	}
	
	//Check for upcoming reviews, but only if not set to be hidden for this year
	if(get_user_meta($user_id,'hide_annual_review',true) != date('Y')){
		$personnel_info 				= get_user_meta($user_id,"personnel",true);
		if(is_array($personnel_info) and !empty($personnel_info['review_date'])){
			//Hide annual review warning
			if(isset($_GET['hide_annual_review']) and $_GET['hide_annual_review'] == date('Y')){
				//Save in the db
				update_user_meta($user_id,'hide_annual_review',date('Y'));
				
				//Get the current url withouth the get params
				$url = str_replace('hide_annual_review='.date('Y'),'', SIM\current_url());
				//redirect to same page without params
				header ("Location: $url");
			}
			
			$reviewdate	= date('F', strtotime($personnel_info['review_date']));
			//If this month is the review month or the month before the review month
			if($reviewdate == date('F') or date('F', strtotime('-1 month',strtotime($reviewdate))) == date('F')){			
				$generic_documents = get_option('personnel_documents');
				if(is_array($generic_documents) and !empty($generic_documents['Annual review form'])){
					$reminder_html .= "Please fill in the annual review questionary.<br>";
					$reminder_html .= 'Find it <a href="'.SITEURL.'/'.$generic_documents['Annual review form'].'">here</a>.<br>';
					$reminder_html .= 'Then send it to the <a href="mailto:'.$personnelCoordinatorEmail.'?subject=Annual review questionary">Personnel coordinator</a><br>';
					$url = add_query_arg( 'hide_annual_review', date('Y'), SIM\current_url() );
					$reminder_html .= '<a class="button sim" href="'.$url.'" style="margin-top:10px;">I already send it!</a><br>';
				}
			}
		}
	}
	
	if ($reminder_html != ""){
		$html = '<h3 class="frontpage">';
		if($remindercount > 1){
			$html .= 'Reminders</h3><p>'.$reminder_html;
		}else{
			$reminder_html = str_replace('</li>','',str_replace('<li>',"",$reminder_html));
			$html .= 'Reminder</h3><p>'.$reminder_html;
		}
		
		$html =  '<div id=reminders>'.$html.'</p></div>';
	}
	
	return $html;
});

//Shortcode for userdata forms
add_shortcode("user-info", __NAMESPACE__.'\user_info_page');
function user_info_page($atts){
	if(is_user_logged_in()){

		wp_enqueue_style('sim_forms_style');
		
		$a = shortcode_atts( array(
			'currentuser' => false,
		), $atts );
		$show_current_user_data = $a['currentuser'];
		
		//Variables
		$medical_roles		= ["medicalinfo"];
		$generic_info_roles = array_merge(['usermanagement'],$medical_roles,['administrator']);
		$visa_roles 		= ["visainfo"];
		$user 				= wp_get_current_user();
		$user_roles 		= $user->roles;
		$tab_html 			= "<nav id='profile_menu'><ul id='profile_menu_list'>";
		$select_user_html	= '';
		$html				= '';
		$user_age 			= 19;
	
		//Showing data for current user
		if($show_current_user_data){
			$user_id = get_current_user_id();
		//Display a select to choose which users data should be shown
		}else{
			//Show the select user to allowed user only
			if(array_intersect(array_merge($generic_info_roles,$visa_roles), $user_roles )){
				$a = shortcode_atts( 
					array('id' => '', ), 
					$atts 
				);
				$user_id = $a['id'];
				
				if(isset($_GET["userid"]) and get_userdata($_GET["userid"])){
					$user_id = $_GET["userid"];
				}else{
					echo SIM\user_select("Select an user to show the data of:");
				}

				$user_birthday = get_user_meta($user_id, "birthday", true);
				if($user_birthday != "")	$user_age = date_diff(date_create(date("Y-m-d")),date_create($user_birthday))->y;
				
			}else{
				return "<p>You do not have permission to see this, sorry.</p>";
			}
		}

		$local_nigerian		= get_user_meta( $user_id, 'local_nigerian', true );
	
		//Continue only if there is a selected user
		if(is_numeric($user_id)){
			$html .= "<div id='profile_forms'>";
				$html .= '<input type="hidden" class="input-text" name="userid" id="userid" value="'.$user_id.'">';
			
			/*
				Dashboard
			*/
			if(in_array('usermanagement', $user_roles ) or $show_current_user_data){
				if($show_current_user_data){
					$admin 		= false;
				}else{
					$admin 		= true;
				}
				
				//Add a tab button
				$tab_html .= "<li class='tablink active' id='show_dashboard' data-target='dashboard'>Dashboard</li> ";
				$html .= "<div id='dashboard'>".show_dashboard($user_id, $admin).'</div>';
			}
			
			/*
				LOGIN Info
			*/
			if(in_array('usermanagement', $user_roles )){				
				//Add a tab button
				$tab_html .= '<li class="tablink" id="show_login_info" data-target="login_info">Login info</li> ';
				
				$html .= change_password_form($user_id);
			}
			
			/*
				GENERIC Info
			*/
			if(array_intersect($generic_info_roles, $user_roles ) or $show_current_user_data){
				$account_validity = get_user_meta( $user_id, 'account_validity',true);
				
				//Add a tab button
				$tab_html .= '<li class="tablink" id="show_generic_info" data-target="generic_info">Generic info</li>';
				
				//Content
				$result	= ob_get_clean();

				ob_start();
				?>
				<div id="generic_info" class="tabcontent hidden">
					<?php
					if($account_validity != '' and $account_validity != 'unlimited' and !is_numeric($account_validity)){
						$removal_date 	= date_create($account_validity);
						?>
					
						<div id='validity_warning' style='border: 3px solid #bd2919; padding: 10px;'>
							<?php
							if(array_intersect($generic_info_roles, $user_roles )){
								wp_enqueue_script( 'sim_user_management');
								?>
								<form>
									<input type="hidden" name="userid" value="<?php echo $user_id = $_GET['userid'];?>">
									This user account is only valid till <?php echo date_format($removal_date,"d F Y");?>
									<br>
									<br>
									Change expiry date to
									<input type='date' name='new_expiry_date' min='<?php echo $account_validity;?>' style='width:auto; display: initial; padding:0px; margin:0px;'>
									<br>
									<input type='checkbox' name='unlimited' value='unlimited' style='width:auto; display: initial; padding:0px; margin:0px;'>
									<label for='unlimited'> Check if the useraccount should never expire.</label>
									<br>
									<?php
								
									echo SIM\add_save_button('extend_validity', 'Change validity');
									?>
								</form>
								<?php
							}else{
								?>
								<p>
									Your user account will be automatically deactivated on <?php echo date_format($removal_date,"d F Y");?>.
								</p>
								<?php
							}
							?>
						</div>
						<?php
					}

					echo do_shortcode('[formbuilder datatype=user_generics]');
					?>
				</div>
				<?php

				$result	= ob_get_clean();
				
				//SIM\print_array($result);
				$html	.= $result;
			}
			
			/*
				Location Info
			*/
			if(array_intersect($generic_info_roles, $user_roles ) or $show_current_user_data){
				//Add tab button
				$tab_html .= '<li class="tablink" id="show_location_info" data-target="location_info">Location</li> ';
				
				//Content
				$location_html = '<div id="location_info" class="tabcontent hidden">';
				$location_html .= do_shortcode('[formbuilder datatype=user_location]');
				$location_html .= '</div>';

				//SIM\print_array($location_html);
				$html	.= $location_html;
			}
			
			/*
				Family Info
			*/
			if(array_intersect($generic_info_roles, $user_roles ) or $show_current_user_data){
				if($user_age > 18){
					//Tab button
					$tab_html .= '<li class="tablink" id="show_family_info" data-target="family_info">Family</li> ';
					
					//Content
					$family_html = '<div id="family_info" class="tabcontent hidden">';

						$family_html .= do_shortcode('[formbuilder datatype=user_family]');
						
					$family_html .= '</div>';
				}
				//SIM\print_array($family_html);

				$html.= $family_html;
			}
			
			/*
				Roles
			*/
			if(in_array('rolemanagement', $user_roles ) or in_array('administrator', $user_roles )){
				//Add a tab button
				$tab_html .= '<li class="tablink" id="show_roles" data-target="role_info">Roles</li> ';
				
				//Content
				$role_html = '<div id="role_info" class="tabcontent hidden">'; 
				$role_html .= display_roles($user_id);
				$role_html .= '</div>';

				//SIM\print_array($role_html);

				$html	.= $role_html;
			}
				
			/*
				Visa Info
			*/
			if((array_intersect($visa_roles, $user_roles ) or $show_current_user_data) and empty($local_nigerian)){
				if($user_age > 18){
					if( isset($_POST['print_visa_info'])){
						if(isset($_POST['userid']) and is_numeric($_POST['userid'])){
							export_visa_info_pdf($_POST['userid']);
						}else{
							export_visa_info_pdf($_POST['userid'], true);//export for all people
						}
					}
					
					if( isset($_POST['export_visa_info'])){
						SIM\SIMNIGERIA\export_visa_excel();
					}
				
					//only active if not own data and has not the user management role
					if(!array_intersect(["usermanagement"], $user_roles ) and !$show_current_user_data){
						$active = "active";
						$class = '';
						$tabclass = 'hidden';
					}else{
						$active = "";
						$class = 'hidden';
						$tabclass = '';
					}
					
					//Tab button
					$tab_html .= "<li class='tablink $active $tabclass' id='show_visa_info' data-target='visa_info'>Immigration</li>";
					
					//Content
					ob_start();
					?>
					<div id='visa_info' class='tabcontent <?php echo $class;?>'>
						<?php
						echo SIM\SIMNIGERIA\visa_page($user_id,true);
					
						if(array_intersect($visa_roles, $user_roles )){
							?>
							<div class='export_button_wrapper' style='margin-top:50px;'>
								<form  method='post'>
									<input type='hidden' name='userid' id='userid' value='$user_id'>
									<button class='button button-primary' type='submit' name='print_visa_info' value='generate'>Export user data as PDF</button>
								</form>
								<form method='post'>
									<button class='button button-primary' type='submit' name='print_visa_info' value='generate'>Export ALL data as PDF</button>
								</form>
								<form method='post'>
									<button class='button button-primary' type='submit' name='export_visa_info' value='generate'>Export ALL data to excel</button>
								</form>
							</div>
							<?php
						}
					?>
					</div>
					<?php

					$result	= ob_get_clean();
					//SIM\print_array($result);

					$html	.= $result;
				}
			}

			/*
				SECURITY INFO
			*/
			if((array_intersect($generic_info_roles, $user_roles ) or $show_current_user_data)){				
				//Tab button
				$tab_html .= "<li class='tablink' id='show_security_info' data-target='security_info'>Security</li>";
				
				//Content
				$security = "<div id='security_info' class='tabcontent hidden'>";
					$security .= do_shortcode('[formbuilder datatype=security_questions]');
				$security .= '</div>';

				$html	.= $security;
			}
	
			
			/*
				Medical Info
			*/
			if((array_intersect($medical_roles, $user_roles) or $show_current_user_data) and empty($local_nigerian)){
				if($show_current_user_data){
					$active = '';
					$class = 'class="hidden"';
				}else{
					$active = 'active';
					$class = '';
				}
				
				//Add tab button
				$tab_html .= "<li class='tablink $active' id='show_medical_info' data-target='medical_info'>Vaccinations</li> ";
				
				//Content
				ob_start();
				?>
				<div id='medical_info' <?php echo $class;?>>
					<?php echo do_shortcode('[formbuilder datatype=user_medical]');?>
					<form method="post" id="print_medicals-form">
						<input type="hidden" name="userid" id="userid" value="'.$user_id.'">
						<button class="button button-primary" type="submit" name="print_medicals" value="generate">Export data as PDF</button>
					</form>
				</div>

				<?php

				$result	= ob_get_clean();

				//SIM\print_array($result);

				$html	.= $result;
			}
			
			
			/*
				Two FA Info
			*/
			if($show_current_user_data){
				//Add tab button
				$tab_html .= '<li class="tablink" id="show_2fa_info" data-target="twofa_info">Two factor</li>';
				
				//Content
				$twofa_html = '<div id="twofa_info" class="tabcontent hidden">';
					$twofa_html .= SIM\LOGIN\twofa_settings_form($user_id);
				$twofa_html .= '</div>';
				
				//SIM\print_array($twofa_html);	

				$html	.= $twofa_html;	
			}		
			
			/*
				PROFILE PICTURE Info
			*/
			if(in_array('usermanagement',$user_roles ) or $show_current_user_data){
				//Add tab button
				$tab_html .= '<li class="tablink" id="show_profile_picture_info" data-target="profile_picture_info">Profile picture</li>';
				
				//Content
				$picture_html = '<div id="profile_picture_info" class="tabcontent hidden">';
					$picture_html .= do_shortcode('[formbuilder datatype=profile_picture]');
				$picture_html .= '</div>';

				$html	.= $picture_html;
			}
			
			/*
				CHILDREN TABS
			*/
			if($show_current_user_data){
				$family = get_user_meta($user_id,'family',true);
				if(is_array($family) and isset($family['children']) and is_array($family['children'])){
					foreach($family['children'] as $child_id){
						$first_name = get_userdata($child_id)->first_name;
						//Add tab button
						$tab_html .= "<li class='tablink' id='show_child_info_$child_id' data-target='child_info_$child_id'>$first_name</li>";
						
						//Content
						$child_html = "<div id='child_info_$child_id' class='tabcontent hidden'>";
							$child_html .= show_children_fields($child_id);
						$child_html .= '</div>';
						
						$html	.= $child_html;
					}
				}
			}
		}
		return $select_user_html.$tab_html."</ul></nav>$html</div>";
	}elseif(function_exists('SIM\LOGIN\login_modal')){
		echo SIM\LOGIN\login_modal("You do not have permission to see this, sorry.");
	}
}

//Delete user shortcode
add_shortcode( 'delete_user', function(){
	require_once(ABSPATH.'wp-admin/includes/user.php');
	
	$user = wp_get_current_user();
	if ( in_array('usermanagement',$user->roles)){
		//Load js	
		wp_enqueue_script('user_select_script');
	
		$html = "";
		
		if(isset($_GET["userid"])){
			$user_id = $_GET["userid"];
			$userdata = get_userdata($user_id);
			if($userdata != null){
				$family = get_user_meta($user_id,"family",true);
				$nonce_string = 'delete_user_'.$user_id.'_nonce';
				
				if(!isset($_GET["confirm"])){
					echo '<script>
					var remove = confirm("Are you sure you want to remove the useraccount for '.$userdata->display_name.'?");
					if(remove){
						var url=window.location+"&'.$nonce_string.'='.wp_create_nonce($nonce_string).'";';
						if (is_array($family) and count($family)>0){
							echo '
							var family = confirm("Do you want to delete all useraccounts for the familymembers of '.$userdata->display_name.' as well?");
							if(family){
								window.location = url+"&confirm=true&family=true";
							}else{
								window.location = url+"&confirm=true";
							}';
						}else{
							echo 'window.location = url+"&confirm=true"';
						}
					echo '}
					</script>';
				}elseif($_GET["confirm"] == "true"){
					if(!isset($_GET[$nonce_string]) or !wp_create_nonce($_GET[$nonce_string],$nonce_string)){
						$html .='<div class="error">Invalid nonce! Refresh the page</div>';
					}else{
						$deleted_name = $userdata->display_name;
						if(isset($_GET["family"]) and $_GET["family"] == "true"){
							if (is_array($family) and count($family)>0){
								$deleted_name .= " and all the family";
								if (isset($family["children"])){
									$family = array_merge($family["children"],$family);
									unset($family["children"]);
								}
								foreach($family as $relative){
									//Remove user account
									wp_delete_user($relative,1);
								}
							}
						}
						//Remove user account
						wp_delete_user($user_id,1);
						$html .= '<div class="success">Useraccount for '.$deleted_name.' succcesfully deleted.</div>';
						echo "<script>
							setTimeout(function(){
								window.location = window.location.href.replace('/?userid=$user_id&delete_user_{$user_id}_nonce=".$_GET[$nonce_string]."&confirm=true','').replace('&family=true','');
							}, 3000);
						</script>";
					}
				}
				
			}else{
				$html .= '<div class="error">User with id '.$user_id.' does not exist.</div>';
			}
		}
		
		$html .= SIM\user_select("Select an user to delete from the website:");
		
		return $html;
	}
});