<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Shortcode for adding user accounts
add_shortcode('create_user_account', function ($atts){
	$user = wp_get_current_user();
	if ( in_array('usermanagement',$user->roles)){		
		//Load js
		wp_enqueue_script('sim_other_script');
		
		ob_start();
		?>
		<div class="tabcontent">
			<form class='sim_form' data-reset="true">
				<p>Please fill in the form to create an user account</p>
				
				<input type="hidden" name="action" value="adduseraccount">
				<input type="hidden" name="create_user_nonce" value="<?php echo wp_create_nonce("create_user_nonce");?>">
				
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

//Make the adduseraccount function available via AJAX for logged-in users
add_action ( 'wp_ajax_adduseraccount', function(){
	if(!is_user_logged_in()) wp_die('Why do you try to hack me?');

	$last_name	= sanitize_text_field($_POST["last_name"]);
	$first_name = sanitize_text_field($_POST["first_name"]);

	if(empty($last_name)) wp_die( 'Please fill in a last name!',500);
	if(empty($first_name)) wp_die('Please fill in a first name!',500);

	//Check if form to add a family member is submitted
	SIM\verify_nonce('create_user_nonce');
	
	//Get the post data
	$email = !empty($_POST["email"]) ? sanitize_email($_POST["email"]) : null;

	if ($email == null){
		$username = SIM\LOGIN\get_available_username($first_name, $last_name);
		
		//Make up a non-existing emailaddress
		$email = sanitize_email($username."@".$last_name.".empty");
	}
	
	$user 		= wp_get_current_user();
	$user_roles = $user->roles;
	if(in_array('usermanagement', $user_roles)){
		$approved = true;
	}
	
	if(!empty($_POST["validity"])){
		$validity = $_POST["validity"];
	}else{
		$validity = "unlimited";
	}
	
	//Create the account
	$user_id = add_user_account($first_name, $last_name, $email, $approved, $validity);
	
	if(is_numeric($user_id)){	
		//Add to mailchimp
		$Mailchimp = new SIM\MAILCHIMP\Mailchimp($user_id);
		$Mailchimp->add_to_mailchimp();
		
		//Store the validity
		if(!empty($_POST["validity"])){
			update_user_meta($user_id,"validity",$_POST["validity"]);
		}
	
		if(in_array('usermanagement', $user_roles)){
			$url = SITEURL."/update-personal-info/?userid=$user_id";
			$message = "Succesfully created an useraccount for $first_name<br>You can edit the deails <a href='$url'>here</a>";
		}else{
			$message =  "Succesfully created useraccount for $first_name<br>You can now select $first_name in the dropdowns";
		}
		
		wp_die(json_encode(
			[
				'message'	=> $message,
				'id'		=> $user_id,
				'callback'	=> 'add_new_relation_to_select'
			]
		));
	}else{
		wp_die('Creating user account failed',500);
	}
});

//Helper function for the ajax_add_user_account function
function add_user_account($first_name, $last_name, $email, $approved = false, $validity = 'unlimited'){
	//Get the username based on the first and lastname
	$username = SIM\LOGIN\get_available_username($first_name, $last_name);// function in registration_fields.php
	
	//Build the user
	$userdata = array(
		'user_login'    => $username,
		'last_name'     => $last_name,
		'first_name'    => $first_name,
		'user_email'    => $email,
		'display_name'  => "$first_name $last_name",
		'user_pass'     => NULL
	);
	
	//Give it the guest user role
	if($validity != "unlimited"){
		$userdata['role'] = 'subscriber';
	}
	//Insert the user
	$user_id = wp_insert_user( $userdata ) ;
	
	if(is_wp_error($user_id)){
		SIM\print_array($user_id->get_error_message());
		wp_die($user_id->get_error_message(),500);
	}
	
	if($approved == false){
		SIM\print_array('not approved');
		//Make the useraccount inactive
		update_user_meta( $user_id, 'disabled', 'pending');
	}else{
		delete_user_meta( $user_id, 'disabled');
		wp_send_new_user_notifications($user_id);
	}

	//Store the validity
	update_user_meta( $user_id, 'account_validity',$validity);
	
	//Force an account update
	do_action( 'profile_update', $user_id, get_userdata($user_id));
	
	// Return the user id
	if ( ! is_wp_error( $user_id ) ) {
		return $user_id;
	}else{
		return 'error: '.$user_id->get_error_message();
	}
}

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
				$html .= "<div id='dashboard'>".show_dashboard($user_id,$admin).'</div>';
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
				$html .= '<div id="generic_info" class="tabcontent hidden">';
				if($account_validity != '' and $account_validity != 'unlimited' and !is_numeric($account_validity)){
					$removal_date 	= date_create($account_validity);
					$nonce 			= wp_create_nonce("extend_validity_reset_nonce");
					
					$html .= "<div id='validity_warning' style='border: 3px solid #bd2919; padding: 10px;'>";

					if(array_intersect($generic_info_roles, $user_roles )){
						$html .= "<p>";
							$html .= "This user account is only valid till ".date_format($removal_date,"d F Y").".<br>";
							$html .= "<br>";
							$html .= "<input type='hidden' id='extend_validity_reset_nonce' value='$nonce'>";
							$html .= "Change expiry date to ";
							$html .= "<input type='date' id='new_expiry_date' min='$account_validity' style='width:auto; display: initial; padding:0px; margin:0px;'>";
							$html .= "<br>";
							$html .= "<input type='checkbox' id='unlimited' value='unlimited' style='width:auto; display: initial; padding:0px; margin:0px;'>";
							$html .= "<label for='unlimited'> Check if the useraccount should never expire.</label>";
							$html .= "<br>";
						$html .= "</p>";
						$html .= SIM\add_save_button('extend_validity', 'Change validity');
					}else{
						$html .= "<p>";
							$html .= "Your user account will be automatically deactivated on ".date_format($removal_date,"d F Y").".<br>";
						$html .= "</p>";
					}
					$html .= "</div>";
				}
					$html .= do_shortcode('[formbuilder datatype=user_generics]');
				$html .= '</div>';
			}
			
			/*
				Location Info
			*/
			if(array_intersect($generic_info_roles, $user_roles ) or $show_current_user_data){
				//Add tab button
				$tab_html .= '<li class="tablink" id="show_location_info" data-target="location_info">Location</li> ';
				
				//Content
				$html .= '<div id="location_info" class="tabcontent hidden">';
				$html .= do_shortcode('[formbuilder datatype=user_location]');
				$html .= '</div>';
			}
			
			/*
				Family Info
			*/
			if(array_intersect($generic_info_roles, $user_roles ) or $show_current_user_data){
				if($user_age > 18){
					//Tab button
					$tab_html .= '<li class="tablink" id="show_family_info" data-target="family_info">Family</li> ';
					
					//Content
					$html .= '<div id="family_info" class="tabcontent hidden">';

						$html .= do_shortcode('[formbuilder datatype=user_family]');
						
					$html .= '</div>';
				}elseif(!$show_current_user_data){
					$html .= "<p><br>This user has no family page. ($user_age yr)";
				}
			}
			
			/*
				Roles
			*/
			if(in_array('rolemanagement', $user_roles ) or in_array('administrator', $user_roles )){
				//Add a tab button
				$tab_html .= '<li class="tablink" id="show_roles" data-target="role_info">Roles</li> ';
				
				//Content
				$html .= '<div id="role_info" class="tabcontent hidden">'; 
				$html .= display_roles($user_id);
				$html .= '</div>';
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
					$html .= "<div id='visa_info' class='tabcontent $class'>";
					$html .= SIM\SIMNIGERIA\visa_page($user_id,true);
					
					if(array_intersect($visa_roles, $user_roles )){
						$html .= "<div class='export_button_wrapper' style='margin-top:50px;'>
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
						</div>";
					}
					$html .= '</div></div>';
				}elseif(!$show_current_user_data){
					$html .= "<p><br>This user has no visa requirements! ($user_age yr)";
				}
			}

			/*
				SECURITY INFO
			*/
			if((array_intersect($generic_info_roles, $user_roles ) or $show_current_user_data)){				
				//Tab button
				$tab_html .= "<li class='tablink' id='show_security_info' data-target='security_info'>Security</li>";
				
				//Content
				$html .= "<div id='security_info' class='tabcontent hidden'>";
					$html .= do_shortcode('[formbuilder datatype=security_questions]');
				$html .= '</div>';
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
				$html .= "<div id='medical_info' $class><div>";
					$html .= do_shortcode('[formbuilder datatype=user_medical]');
					$html .= '<div>
						<form method="post" id="print_medicals-form">
							<input type="hidden" name="userid" id="userid" value="'.$user_id.'">
							<button class="button button-primary" type="submit" name="print_medicals" value="generate">Export data as PDF</button>
						</form>
					</div>
				</div></div>';
			}
			
			/*
				Two FA Info
			*/
			if($show_current_user_data){
				//Add tab button
				$tab_html .= '<li class="tablink" id="show_2fa_info" data-target="twofa_info">Two factor</li>';
				
				//Content
				$html .= '<div id="twofa_info" class="tabcontent hidden">';
				$html .= SIM\LOGIN\twofa_settings_form($user_id);
				$html .= '</div>';
			}			
			
			/*
				PROFILE PICTURE Info
			*/
			if(in_array('usermanagement',$user_roles ) or $show_current_user_data){
				//Add tab button
				$tab_html .= '<li class="tablink" id="show_profile_picture_info" data-target="profile_picture_info">Profile picture</li>';
				
				//Content
				$html .= '<div id="profile_picture_info" class="tabcontent hidden">';
					$html .= do_shortcode('[formbuilder datatype=profile_picture]');
				$html .= '</div>';
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
						$html .= "<div id='child_info_$child_id' class='tabcontent hidden'>";
							$html .= show_children_fields($child_id);
						$html .= '</div>';
					}
				}
			}
		}
		
		return $select_user_html.$tab_html."</ul></nav>$html</div>";
	}else{
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