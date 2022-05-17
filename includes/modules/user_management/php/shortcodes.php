<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Shortcode for adding user accounts
add_shortcode('create_user_account', function ($atts){
	wp_enqueue_script( 'sim_user_management');

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
				do_action('sim_after_user_create_form');
				
				echo SIM\add_save_button('adduseraccount', 'Add user account');
				?>
			</form>
		</div>
		<?php
		
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
add_action('sim_dashboard_warnings', function($userId){
	$personnelCoordinatorEmail	= SIM\get_module_option('user_management', 'personnel_email');

	if(is_numeric($_GET["userid"]) and in_array('usermanagement', wp_get_current_user()->roles )){
		$userId	= $_GET["userid"];
	}else{
		$userId = get_current_user_id();
	}
	$remindercount = 0;
	$reminder_html = "";
	
	$visa_info = get_user_meta( $userId, "visa_info",true);
	if (is_array($visa_info) and isset($visa_info['greencard_expiry'])){
		$reminder_html .= check_expiry_date($visa_info['greencard_expiry'],'greencard');
		if($reminder_html != ""){
			$remindercount = 1;
			$reminder_html .= '<br>';
		}
	}
		
	$vaccination_reminder_html = vaccination_reminders($userId);
	
	if ($vaccination_reminder_html != ""){
		$remindercount += 1;
		$reminder_html .= $vaccination_reminder_html ;
	}
	
	//Check for children
	$family = get_user_meta($userId,"family",true);
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
	if(get_user_meta($userId,'hide_annual_review',true) != date('Y')){
		$personnel_info 				= get_user_meta($userId,"personnel",true);
		if(is_array($personnel_info) and !empty($personnel_info['review_date'])){
			//Hide annual review warning
			if(isset($_GET['hide_annual_review']) and $_GET['hide_annual_review'] == date('Y')){
				//Save in the db
				update_user_meta($userId,'hide_annual_review',date('Y'));
				
				//Get the current url withouth the get params
				$url = str_replace('hide_annual_review='.date('Y'),'', SIM\currentUrl());
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
					$url = add_query_arg( 'hide_annual_review', date('Y'), SIM\currentUrl() );
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


add_filter('sim_loggedin_homepage',  function($content){
	$content	.= expiryWarnings();
	return $content;
});

//Shortcode for expiry warnings
add_shortcode("expiry_warnings", __NAMESPACE__.'\expiryWarnings');
function expiryWarnings(){
	$personnelCoordinatorEmail	= SIM\get_module_option('user_management', 'personnel_email');

	if(is_numeric($_GET["userid"]) and in_array('usermanagement', wp_get_current_user()->roles )){
		$userId	= $_GET["userid"];
	}else{
		$userId = get_current_user_id();
	}
	$remindercount = 0;
	$reminder_html = "";
	
	$visa_info = get_user_meta( $userId, "visa_info",true);
	if (is_array($visa_info) and isset($visa_info['greencard_expiry'])){
		$reminder_html .= check_expiry_date($visa_info['greencard_expiry'],'greencard');
		if($reminder_html != ""){
			$remindercount = 1;
			$reminder_html .= '<br>';
		}
	}
		
	$vaccination_reminder_html = vaccination_reminders($userId);
	
	if ($vaccination_reminder_html != ""){
		$remindercount += 1;
		$reminder_html .= $vaccination_reminder_html ;
	}
	
	//Check for children
	$family = get_user_meta($userId,"family",true);
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
	if(get_user_meta($userId,'hide_annual_review',true) != date('Y')){
		$personnel_info 				= get_user_meta($userId,"personnel",true);
		if(is_array($personnel_info) and !empty($personnel_info['review_date'])){
			//Hide annual review warning
			if(isset($_GET['hide_annual_review']) and $_GET['hide_annual_review'] == date('Y')){
				//Save in the db
				update_user_meta($userId,'hide_annual_review',date('Y'));
				
				//Get the current url withouth the get params
				$url = str_replace('hide_annual_review='.date('Y'),'', SIM\currentUrl());
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
					$url = add_query_arg( 'hide_annual_review', date('Y'), SIM\currentUrl() );
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
}

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

		$user 				= wp_get_current_user();
		$user_roles 		= $user->roles;
		$tabs				= [];
		$select_user_html	= '';
		$html				= '';
		$user_age 			= 19;
	
		//Showing data for current user
		if($show_current_user_data){
			$userId = get_current_user_id();
		//Display a select to choose which users data should be shown
		}else{
			$userSelectRoles	= apply_filters('sim_user_page_dropdown', $generic_info_roles);
			//Show the select user to allowed user only
			if(array_intersect($userSelectRoles, $user_roles )){
				$a = shortcode_atts( 
					array('id' => '', ), 
					$atts 
				);
				$userId = $a['id'];
				
				if(isset($_GET["userid"]) and get_userdata($_GET["userid"])){
					$userId = $_GET["userid"];
				}else{
					echo SIM\userSelect("Select an user to show the data of:");
				}

				$user_birthday = get_user_meta($userId, "birthday", true);
				if($user_birthday != "")	$user_age = date_diff(date_create(date("Y-m-d")),date_create($user_birthday))->y;
				
			}else{
				return "<p>You do not have permission to see this, sorry.</p>";
			}
		}
	
		//Continue only if there is a selected user
		if(is_numeric($userId)){			
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
				$tabs[]	= "<li class='tablink active' id='show_dashboard' data-target='dashboard'>Dashboard</li>";
				$html .= "<div id='dashboard'>".show_dashboard($userId, $admin).'</div>';
			}

			/*
				Family Info
			*/
			if(array_intersect($generic_info_roles, $user_roles ) or $show_current_user_data){
				if($user_age > 18){
					//Tab button
					$tabs[]	= '<li class="tablink" id="show_family_info" data-target="family_info">Family</li>';
					
					//Content
					$family_html = '<div id="family_info" class="tabcontent hidden">';

						$family_html .= do_shortcode('[formbuilder datatype=user_family]');
						
					$family_html .= '</div>';
				}

				$html.= $family_html;
			}
			
			/*
				GENERIC Info
			*/
			if(array_intersect($generic_info_roles, $user_roles ) or $show_current_user_data){
				$account_validity = get_user_meta( $userId, 'account_validity',true);
				
				//Add a tab button
				$tabs[]	= '<li class="tablink" id="show_generic_info" data-target="generic_info">Generic info</li>';
				
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
									<input type="hidden" name="userid" value="<?php echo $userId = $_GET['userid'];?>">
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
				$html	.= $result;
			}
			
			/*
				Location Info
			*/
			if(array_intersect($generic_info_roles, $user_roles ) or $show_current_user_data){
				//Add tab button
				$tabs[]	= '<li class="tablink" id="show_location_info" data-target="location_info">Location</li>';
				
				//Content
				$location_html = '<div id="location_info" class="tabcontent hidden">';
				$location_html .= do_shortcode('[formbuilder datatype=user_location]');
				$location_html .= '</div>';
				$html	.= $location_html;
			}
			
			/*
				LOGIN Info
			*/
			if(in_array('usermanagement', $user_roles )){				
				//Add a tab button
				$tabs[]	= '<li class="tablink" id="show_login_info" data-target="login_info">Login info</li>';
				
				$html .= change_password_form($userId);
			}
						
			/*
				PROFILE PICTURE Info
			*/
			if(in_array('usermanagement',$user_roles ) or $show_current_user_data){
				//Add tab button
				$tabs[]	= '<li class="tablink" id="show_profile_picture_info" data-target="profile_picture_info">Profile picture</li>';
				
				//Content
				$picture_html = '<div id="profile_picture_info" class="tabcontent hidden">';
					$picture_html .= do_shortcode('[formbuilder datatype=profile_picture]');
				$picture_html .= '</div>';

				$html	.= $picture_html;
			}
			
			/*
				Roles
			*/
			if(in_array('rolemanagement', $user_roles ) or in_array('administrator', $user_roles )){
				//Add a tab button
				$tabs[]	= '<li class="tablink" id="show_roles" data-target="role_info">Roles</li>';
				
				//Content
				$role_html = '<div id="role_info" class="tabcontent hidden">'; 
				$role_html .= display_roles($userId);
				$role_html .= '</div>';

				$html	.= $role_html;
			}
				
			/*
				SECURITY INFO
			*/
			if((array_intersect($generic_info_roles, $user_roles ) or $show_current_user_data)){				
				//Tab button
				$tabs[]	= "<li class='tablink' id='show_security_info' data-target='security_info'>Security</li>";
				
				//Content
				$security = "<div id='security_info' class='tabcontent hidden'>";
					$security .= do_shortcode('[formbuilder datatype=security_questions]');
				$security .= '</div>';

				$html	.= $security;
			}

			/*
				Vaccinations Info
			*/
			if((array_intersect($medical_roles, $user_roles) or $show_current_user_data)){
				if($show_current_user_data){
					$active = '';
					$class = 'class="hidden"';
				}else{
					$active = 'active';
					$class = '';
				}
				
				//Add tab button
				$tabs[]	= "<li class='tablink $active' id='show_medical_info' data-target='medical_info'>Vaccinations</li>";
				
				//Content
				ob_start();
				?>
				<div id='medical_info' <?php echo $class;?>>
					<?php echo do_shortcode('[formbuilder datatype=user_medical]');?>
					<form method="post" id="print_medicals-form">
						<input type="hidden" name="userid" id="userid" value="'.$userId.'">
						<button class="button button-primary" type="submit" name="print_medicals" value="generate">Export data as PDF</button>
					</form>
				</div>

				<?php

				$result	= ob_get_clean();

				$html	.= $result;
			}

			//  Add filter to add extra pages, children tabs should always be last
			$filtered_html	= apply_filters('sim_user_info_page', ['tabs'=>$tabs, 'html'=>$html], $show_current_user_data, $user, $user_age);
			$tabs		 	= $filtered_html['tabs'];
			$html	 		= $filtered_html['html'];
			
			/*
				CHILDREN TABS
			*/
			if($show_current_user_data){
				$family = get_user_meta($userId,'family',true);
				if(is_array($family) and isset($family['children']) and is_array($family['children'])){
					foreach($family['children'] as $child_id){
						$first_name = get_userdata($child_id)->first_name;
						//Add tab button
						$tabs[]	= "<li class='tablink' id='show_child_info_$child_id' data-target='child_info_$child_id'>$first_name</li>";
						
						//Content
						$child_html = "<div id='child_info_$child_id' class='tabcontent hidden'>";
							$child_html .= show_children_fields($child_id);
						$child_html .= '</div>';
						
						$html	.= $child_html;
					}
				}
			}
		}

		$result	= $select_user_html;
		$result	.= "<nav id='profile_menu'>";
			$result	.= "<ul id='profile_menu_list'>";
			foreach($tabs as $tab){
				$result	.= $tab;
			}
			$result	.= "</ul>";
		$result	.= "</nav>";

		$result	.= "<div id='profile_forms'>";
			$result .= "<input type='hidden' class='input-text' name='userid' value='$userId'>";
			$result	.= $html;
		$result	.= "</div>";

		return $result;
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
			$userId = $_GET["userid"];
			$userdata = get_userdata($userId);
			if($userdata != null){
				$family = get_user_meta($userId,"family",true);
				$nonce_string = 'delete_user_'.$userId.'_nonce';
				
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
						wp_delete_user($userId,1);
						$html .= '<div class="success">Useraccount for '.$deleted_name.' succcesfully deleted.</div>';
						echo "<script>
							setTimeout(function(){
								window.location = window.location.href.replace('/?userid=$userId&delete_user_{$userId}_nonce=".$_GET[$nonce_string]."&confirm=true','').replace('&family=true','');
							}, 3000);
						</script>";
					}
				}
				
			}else{
				$html .= '<div class="error">User with id '.$userId.' does not exist.</div>';
			}
		}
		
		$html .= SIM\userSelect("Select an user to delete from the website:");
		
		return $html;
	}
});

add_shortcode("userstatistics",function ($atts){
	wp_enqueue_script('sim_table_script');

	ob_start();

	$users 		= SIM\getUserAccounts($return_family=false,$adults=true);

	$baseUrl	= SIM\getValidPageLink(SIM\get_module_option('user_management', 'user_edit_page'));
	?>
	<br>
	<div class='form-table-wrapper'>
		<table class='sim-table' style='max-height:500px;'>
			<thead>
				<tr>
					<th>Name</th>
					<th>Login count</th>
					<th>Last login</th>
					<th>Mandatory pages to read</th>
					<th>User roles</th>
					<th>Account validity</th>
				</tr>
			</thead>

			<tbody>
				<?php
				foreach($users as $user){
					$login_count= get_user_meta($user->ID,'login_count',true);
					if(!is_numeric(($login_count))) $login_count = 0;

					$last_login_date	= get_user_meta($user->ID,'last_login_date',true);
					if(empty($last_login_date)){
						$last_login_date	= 'Never';
					}else{
						$time_string 	= strtotime($last_login_date);
						if($time_string ) $last_login_date = date('d F Y', $time_string);
					}

					$picture = SIM\displayProfilePicture($user->ID);

					echo "<tr class='table-row'>";
						echo "<td>$picture <a href='$baseUrl/?userid=$user->ID'>{$user->display_name}</a></td>";
						echo "<td>$login_count</td>";
						echo "<td>$last_login_date</td>";
						if(function_exists('SIM\MANDATORY\mustReadDocuments')){
							echo "<td>".SIM\MANDATORY\mustReadDocuments($user->ID,true)."</td>";
						}
						echo "<td>";
						foreach($user->roles as $role){
							echo $role.'<br>';
						}
						echo "</td>";
						echo "<td>".get_user_meta($user->ID,'account_validity',true)."</td>";
					echo "</tr>";
				}
				?>
			</tbody>
		</table>
	</div>
	<?php
	return ob_get_clean();
});