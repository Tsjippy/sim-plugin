<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'birthday_check_action', __NAMESPACE__.'\birthday_check' );
    add_action( 'vaccination_reminder_action', __NAMESPACE__.'\vaccination_reminder' );
    add_action( 'greencard_reminder_action', __NAMESPACE__.'\greencard_reminder' );
    add_action( 'check_details_mail_action', __NAMESPACE__.'\check_details_mail' );
    add_action( 'account_expiry_check_action', __NAMESPACE__.'\account_expiry_check' );
	add_action( 'review_reminders_action', __NAMESPACE__.'\review_reminders' );
	add_action( 'check_last_login_date_action', __NAMESPACE__.'\check_last_login_date' );
	
});

function schedule_tasks(){
    SIM\schedule_task('birthday_check_action', 'daily');
    SIM\schedule_task('account_expiry_check_action', 'daily');
    SIM\schedule_task('vaccination_reminder_action', 'monthly');
    //SIM\schedule_task('greencard_reminder_action', 'monthly');
    SIM\schedule_task('check_details_mail_action', 'yearly');
	//SIM\schedule_task('review_reminders_action', 'monthly');
	SIM\schedule_task('check_last_login_date_action', 'monthly');
}

function birthday_check(){
	//Change the user to the admin account otherwise get_users will not work
	wp_set_current_user(1);

	//Current date time
	$date   = new \DateTime(); 
	
	//Get all the birthday users of today
	$users = get_users(array(
		'meta_key'     => 'birthday',
		'meta_value'   => $date->format('-m-d'),
		'meta_compare' => 'LIKE',
	));
	
	foreach($users as $user){
		$user_id 	= $user->ID;
		$first_name = $user->first_name;
		
		$family = get_user_meta( $user_id, 'family', true );
		if ($family == ""){
			$family = [];
		}
	
		//Send birthday wish to the user
		SIM\try_send_signal("Hi ".$first_name.",\nCongratulations with your birthday!",$user_id);

		//Send to parents
		if (isset($family["father"]) or isset($family["mother"])){
			$child_title = SIM\get_child_title($user->ID);
			
			$message = "Congratulations with the birthday of your $child_title ".get_userdata($user->ID)->first_name;
		}
		
		if (isset($family["father"])){	
			SIM\try_send_signal(
				"Hi ".get_userdata($family["father"])->first_name.",\n$message",
				$family["father"]
			);
		}
		if (isset($family["mother"])){
			SIM\try_send_signal(
				"Hi ".get_userdata($family["mother"])->first_name.",\n$message",
				$family["mother"]
			);
		}
	}
}

//loop over all users and scan for expiry vaccinations
function vaccination_reminder(){	
	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);
	
	//Retrieve all users
	$users = get_users( array( 'fields' => array( 'ID','user_login','display_name' ) ) );
	
	//loop over the users
	foreach($users as $user){
		$reminder_html = vaccination_reminders($user->ID);

		//If there are reminders, send an e-mail
		if (!empty($reminder_html)){
			update_user_meta($user->ID,"required_fields_status","");
			$userdata = get_userdata($user->ID);
			if($userdata != null){
				$parents 	= SIM\get_parents($user->ID);
				$recipients = '';
				
				//Is child
				if(count($parents)>0){
					
					$reminder_html = str_replace("Your",$userdata->first_name."'s",$reminder_html);

					$vaccinationWarningMail    	= new AdultVaccinationWarningMail($userdata);
					$vaccinationWarningMail->filterMail();
					$subject					= $vaccinationWarningMail->subject; 
					$message					= $vaccinationWarningMail->message;
					
					$child_title = SIM\get_child_title($user->ID);
					foreach($parents as $parent){
						if(strpos($parent->user_email,'.empty') === false){
							if($recipients != '') $recipients .= ', ';
							$recipients .= $parent->user_email;
						}
									
						//Send OneSignal message
						SIM\try_send_signal(
							"Hi $parent->first_name,\nPlease renew the vaccinations  of your $child_title $userdata->first_name!\n\n".SITEURL,
							$user->ID
						);
					}				
				//not a child
				}else{	
					//If this not a valid email skip this email
					if(strpos($userdata->user_email,'.empty') === false) continue;

					$vaccinationWarningMail    	= new AdultVaccinationWarningMail($userdata);
					$vaccinationWarningMail->filterMail();
					$subject					= $vaccinationWarningMail->subject; 
					$message					= $vaccinationWarningMail->message;
					
					//Send Signal message
					SIM\try_send_signal("Hi $userdata->first_name,\nPlease renew your vaccinations!\n\n".SITEURL, $user->ID);
				}
				
				
				if(!empty($recipients)){
					//Get the current health coordinator
					$healtCoordinators 			= get_users( array( 'fields' => array( 'ID','display_name' ),'role' => 'medicalinfo' ));
					if($healtCoordinators != null){
						$healtCoordinator = (object)$healtCoordinators[0];
					}else{
						$healtCoordinator = new \stdClass();
						$healtCoordinator->display_name = '';
						error_log("Please assign someone the health coorodinator role!");
					}

					$headers = ['Reply-To: '.$healtCoordinator->display_name.' <'.SIM\get_module_option('user_management', 'health_email').'>'];
					
					//Send the mail
					wp_mail($recipients , $subject, $message, $headers );
				}
			}
		}
	}
}

function vaccination_reminders($UserID){	
	//Get the current users medical data
	$medical_user_info = (array)get_user_meta( $UserID, "medical",true);

	$reminder_html = "";
	foreach($medical_user_info as $key=>$info){
		if (strpos($key, 'expiry_date') !== false) {
			//Its an array, so another vaccination
			if(is_array($info)){
				foreach($info as $date_key=>$date){
					//Get the vaccination name of this other vaccination
					$vaccination_name = $medical_user_info['other_vaccination'][$date_key];
					if($date != ""){
						$reminder_html .= check_expiry_date($date, "$vaccination_name vaccination");
					}
				}
			}else{
				//Get the clean vaccination name
				$vaccination_name = str_replace('expiry_date_of_your_','',$key);
				$vaccination_name = str_replace('_vaccination','',$vaccination_name);
				$vaccination_name = ucwords(str_replace('_',' ',$vaccination_name));
				$reminder_html .= check_expiry_date($info, "$vaccination_name vaccination");
			}	
		}
	}
	
	return $reminder_html;
}

function check_expiry_date($date, $expiry_name){	
	$vaccinationWarningTime	= SIM\get_module_option('user_management', 'vaccination_warning_time');
	if ($vaccinationWarningTime and !empty($date)){
		$reminder_html = "";
		
		//Date of first warning
		$now = new \DateTime();
		$interval = new \DateInterval('P'.$vaccinationWarningTime.'M');
		$warning_date = $now->add($interval);
		
		//todays date
		//$now = new \DateTime();

		//Vaccination expiry date
		try{
			$expiry_date = new \DateTime($date);
		}catch (\Exception $e) {
			return;
		}
		
		$nice_expiry_date = $expiry_date->format('j F Y');
		
		//Expires today
		if($nice_expiry_date == $now->format('j F Y')){
			$reminder_html .= "<li>Your $expiry_name expires today.</li><br>";
		//In the past
		}elseif($expiry_date < $now){
			$reminder_html .= "<li>Your $expiry_name is expired on $nice_expiry_date. </li><br>";
		//In the near future
		}elseif($expiry_date < $warning_date){
			$diff=date_diff(date_create(date("Y-m-d")),$expiry_date)->format("%a");
			if($diff == 1){
				$text = "tomorrow";
			}else{
				$text = "in $diff days on $nice_expiry_date";
			}
			$reminder_html .= "<li>Your $expiry_name will expire $text.</li><br>";
		}
		
		return $reminder_html;
	}
}

//loop over all users and scan for expiry greencards
function greencard_reminder(){
	//Get the current travel coordinator
	$TravelCoordinator 			= get_users( array( 'role' => 'visainfo' ));
	if($TravelCoordinator != null){
		$TravelCoordinator = $TravelCoordinator[0];
	}else{
		$TravelCoordinator = new \stdClass();
		$TravelCoordinator->display_name = '';
		error_log("Please assign someone the travelcoorodinator role!");
	}
	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);
	
	//Retrieve all users
	$users = get_users( array( 'fields' => array( 'ID','user_login','display_name' ) ) );
	
	//loop over the users
	foreach($users as $user){
		$visa_info = get_user_meta( $user->ID, "visa_info",true);

		//If there are reminders, send an e-mail
		if (is_array($visa_info) and isset($visa_info['greencard_expiry'])){
			$reminder = check_expiry_date($visa_info['greencard_expiry'],'greencard');
			$reminder = str_replace(['</li>','<li>'], "", $reminder);
			
			if(!empty($reminder_html)){		
				$to = $user->user_email;
				
				//Skip if not valid email
				if(strpos($to,'.empty') !== false) continue;

				//Send e-mail
				$greenCardReminderMail    = new GreenCardReminderMail($user, $reminder);
				$greenCardReminderMail->filterMail();
				$headers = ['Reply-To: '.$TravelCoordinator->display_name.' <'.$TravelCoordinator->user_email.'>'];
									
				wp_mail( $to, $greenCardReminderMail->subject, $greenCardReminderMail->message, $headers);
				
				//Send OneSignal message
				SIM\try_send_signal("Hi $user->first_name,\nPlease renew your greencard!\n\n".SITEURL, $user->ID);
			}
		}
	}
}

//Store page with user-info shortcode
add_action( 'save_post', function($post_ID, $post){
    if(has_shortcode($post->post_content, 'user-info')){
        global $Modules;

        $Modules['forms']['account_page']    = $post_ID;

        update_option('sim_modules', $Modules);
    }
}, 10, 2);

//send an e-mail with an overview of an users details for them to check
function check_details_mail(){
	wp_set_current_user(1);
	$subject	= 'Please review your website profile';
	
	//Retrieve all users
	$users = SIM\get_user_accounts($return_family=false,$adults=true,$local_nigerians=true);

	$accountPage	= SIM\get_module_option('user_management', 'account_page');
	$accountPageUrl	= get_permalink($accountPage);

	if(!$accountPageUrl)	return;
	$baseUrl		= "$accountPageUrl?main_tab=";

	$style_string	= "style='text-decoration:none; color:#444;'";

	//Loop over the users
	foreach($users as $user){
		//Send e-mail
		$message  = "Hi {$user->first_name},<br><br>";
		$message .= 'Once a year we would like to remind you to keep your information on the website up to date.<br>';
		$message .= 'Please check the information below to see if it is still valid, if not update it.<br><br>';
		
		/* 
		** PROFILE PICTURE
 		*/
		$message .= "<a href='{$baseUrl}profile_picture' $style_string><b>Profile picture</b></a><br>";
		$profile_picture	= get_profile_picture_url($user->ID);
		if($profile_picture){
			$message 		.= "This is your profile picture:<br>";
			$message 		.= "<img src='$profile_picture' alt='$profile_picture' width='100px' height='100px'";
			$message 		.= "<br><br>";
		}else{
			$message .= "<table>";
				$message .= "<tr>";
					$message .= "<td>";
						$message .= "<a href='{$baseUrl}profile_picture' $style_string>You have not uploaded a picture</a>";
					$message .= "</td>";
				$message .= "</tr>";
			$message .= "</table>";
		}
		$message .= "<br>";

		/* 
		** PERSONAL DETAILS
 		*/
		$message .= "<a href='{$baseUrl}generic_info' $style_string><b>Personal details</b></a><br>";
		$message .= "<table>";
			$message .= "<tr>";
				$message .= "<td>";
					$message .= "Name:";
				$message .= "</td>";
				$message .= "<td>";
					$message .= "$user->display_name";
				$message .= "</td>";
			$message .= "</tr>";

			$birthday = get_user_meta($user->ID,'birthday',true);
			if(empty($birthday)){
				$birthday = 'No birthday specified.';
			}else{
				$birthday = date('d  F Y', strtotime($birthday));
			}
			$message .= "<tr>";
				$message .= "<td>";
					$message .= "Birthday:";
				$message .= "</td>";
				$message .= "<td>";
					$message .= "<a href='{$baseUrl}generic_info#birthday' $style_string>$birthday</a>";
				$message .= "</td>";
			$message .= "</tr>";

			$local_nigerian = get_user_meta( $user->ID, 'local_nigerian', true );
			if(empty($local_nigerian)){
				$sendingOffice = get_user_meta($user->ID,'sending_office',true);
				if(empty($sendingOffice)) $sendingOffice = 'No sending office specified';
				$message .= "<tr>";
					$message .= "<td>";
						$message .= "Sending office:";
					$message .= "</td>";
					$message .= "<td>";
						$message .= "<a href='{$baseUrl}generic_info#sending_office' $style_string>$sendingOffice</a>";
					$message .= "</td>";
				$message .= "</tr>";

				$arrivaldate = get_user_meta($user->ID,'arrival_date',true);
				if(empty($arrivaldate)){
					$arrivaldate = 'No arrival date specified';
				}else{
					$arrivaldate = date('d F Y', strtotime($arrivaldate));
				}
				$message .= "<tr>";
					$message .= "<td>";
						$message .= "Arrival date:";
					$message .= "</td>";
					$message .= "<td>";
						$message .= "<a href='{$baseUrl}generic_info#arrival_date' $style_string>$arrivaldate</a>";
					$message .= "</td>";
				$message .= "</tr>";
			}
		$message .= "</table>";
		$message .= "<br>";

		/* 
		** PHONENUMBERS
 		*/
		$phonenumbers = (array)get_user_meta($user->ID,'phonenumbers',true);
		SIM\clean_up_nested_array($phonenumbers);
		$title	= 'Phonenumber';
		if(count($phonenumbers)>1) $title .= 's';

		$message .= "<a href='{$baseUrl}generic_info' $style_string><b>$title</b></a><br>";
		$message .= "<table>";
		if(empty($phonenumbers)){
			$message .= "<tr>";
				$message .= "<td>";
					$message .= "<a href='{$baseUrl}generic_info#phonenumbers[0]' $style_string>No phonenumbers provided</a>";
				$message .= "</td>";
			$message .= "</tr>";
		}elseif(count($phonenumbers) == 1){
			$message .= "<tr>";
				$message .= "<td>";
					$message .= "<a href='{$baseUrl}generic_info#phonenumbers[0]' $style_string>".array_values($phonenumbers)[0].'</a>';
				$message .= "</td>";
			$message .= "</tr>";
		}else{
			foreach($phonenumbers as $key=>$number){
				$nr	= $key+1;
				$message .= "<tr>";
					$message .= "<td>";
						$message .= "Phonenumber $nr:";
					$message .= "</td>";
					$message .= "<td>";
						$message .= "<a href='{$baseUrl}generic_info#phonenumbers[$key]' $style_string>$number</a>";
					$message .= "</td>";
				$message .= "</tr>";
			}
		}
		$message .= "</table>";
		$message .= "<br>";

		/* 
		** MINISTRIES
 		*/
		$user_ministries = (array)get_user_meta($user->ID,'user_ministries',true);
		if(count($user_ministries)>1){
			$title	= 'Ministries';
		}else{
			$title	= 'Ministry';
		}
		$message .= "<a href='{$baseUrl}generic_info' $style_string><b>$title</b></a><br>";

		$message .= "<table>";
			SIM\clean_up_nested_array($user_ministries);
			if(empty($user_ministries)){
				$message .= "<tr>";
					$message .= "<td>";
						$message .= "<a href='{$baseUrl}generic_info#ministries[]' $style_string>No ministry provided</a>";
					$message .= "</td>";
				$message .= "</tr>";
			}else{
				foreach($user_ministries as $ministry=>$job){
					$ministry = str_replace('_',' ',$ministry);
					$message .= "<tr>";
						$message .= "<td>";
							$message .= "$ministry:";
						$message .= "</td>";
						$message .= "<td>";
							$message .= "<a href='{$baseUrl}generic_info#ministries[]' $style_string>$job</a>";
						$message .= "</td>";
					$message .= "</tr>";
				}
				
			}
		$message .= "</table>";
		$message .= "<br>";

		/* 
		** LOCATION
 		*/
		$message .= "<a href='{$baseUrl}location' $style_string><b>Location</b></a><br>";
		$location= (array)get_user_meta($user->ID,'location',true);
		SIM\clean_up_nested_array($location);
		if(empty($location['address'])){
			$location = "No location provided";
		}else{
			$location = $location['address'];
		}

		$message .= "<table>";
			$message .= "<tr>";
				$message .= "<td>";
					$message .= "<a href='{$baseUrl}location#location[compound]' $style_string>$location</a>" ;
				$message .= "</td>";
			$message .= "</tr>";
		$message .= "</table>";
		$message .= "<br>";

		$family = get_user_meta( $user->ID, 'family', true );
		if(!empty($family)){
			if(empty($family['partner'])){
				$partner = 'You have no spouse';
			}else{
				$partner = get_userdata($family['partner'])->display_name;
			}

			$message .= "<a href='{$baseUrl}family' $style_string><b>Family details</b></a><br>";
			$message .= "<table>";
				$message .= "<tr>";
					$message .= "<td>";
						$message .= "Spouse:";
					$message .= "</td>";
					$message .= "<td>";
						$message .= "<a href='{$baseUrl}family#family[partner]' $style_string>$partner</a>";
					$message .= "</td>";
				$message .= "</tr>";

				foreach($family['children'] as $key=>$child){
					$nr=$key+1;
					$message .= "<tr>";
						$message .= "<td>";
							$message .= "Child $nr:";
						$message .= "</td>";
						$message .= "<td>";
							$message .= "<a href='{$baseUrl}family#familyfamily[children][$key]' $style_string>".get_userdata($child)->display_name."</a>";
						$message .= "</td>";
					$message .= "</tr>";
				}
			$message .= "</table>";
		}

		$message .= '<br>';
		$message .= "If any information is not correct, please correct it on <a href='".SITEURL."/account/'>".str_replace(['https://www.','https://'], '', $accountPageUrl)."</a>.<br>Or just click on any details listed above.";

		wp_mail( $user->user_email, $subject, $message);
	}
}

function account_expiry_check(){
	require_once(ABSPATH.'wp-admin/includes/user.php');
	
	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);

	$personnelCoordinatorEmail	= SIM\get_module_option('user_managment', 'personnel_email');
	$staEmail					= SIM\get_module_option('user_managment', 'sta_email');
	
	//Get the users who will expire in 1 month
	$users = get_users(
		array(
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => 'account_validity',
					'compare' => 'EXISTS'
				),
				array(
					'key' => 'account_validity',
					'value' => 'unlimited',
					'compare' => '!='
				),
				array(
					'key' => 'account_validity',
					'value' => date("Y-m-d", strtotime(" +1 months")), 
					'compare' => '=',
					'type' => 'DATE'
				),
				
			),
		)
	);
	
	foreach($users as $user){
		//Send e-mail
		$accountExpiryMail    = new AccountExpiryMail($user, $reminder);
		$accountExpiryMail->filterMail();

		$headers 	= [
			"Reply-To: STA Coordinator <$staEmail>",
			"cc: $personnelCoordinatorEmail",
			"cc: $staEmail"
		];
		
		//Send the mail if valid email
		if(strpos($user->user_email,'.empty') === false){
			$recipient = $user->user_email;
		}else{
			$recipient = $staEmail;
		}
		
		wp_mail( $recipient, $accountExpiryMail->subject, $accountExpiryMail->message, $headers);
		
		//Send OneSignal message
		SIM\try_send_signal("Hi ".$user->first_name.",\nThis is just a reminder that your account on simnigeria.org will be deleted on ".date("d F Y", strtotime(" +1 months")),$user->ID);
	}
	
	//Get the users who are expired
	$expired_users = get_users(
		array(
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => 'account_validity',
					'compare' => 'EXISTS'
				),
				array(
					'key' => 'account_validity',
					'value' => 'unlimited',
					'compare' => '!='
				),
				array(
					'key' => 'account_validity',
					'value' => date("Y-m-d"), 
					'compare' => '<=',
					'type' => 'DATE'
				),
				
			),
		)
	);
	
	foreach($expired_users as $user){
		//Send Signal message
		SIM\try_send_signal(
			"Hi ".$user->first_name.",\nYour account is expired, as you are no longer in Nigeria.",
			$user->ID
		);
		
		//Delete the account
		SIM\print_array("Deleting user with id ".$user->ID." and name ".$user->display_name." as it was a temporary account.");
		wp_delete_user($user->ID);
	}
}

//send reminders about annual review
function review_reminders(){	
	$generic_documents = get_option('personnel_documents');
	if(is_array($generic_documents) and !empty($generic_documents['Annual review form'])){
		$personnelCoordinatorEmail	= SIM\get_module_option('user_managment', 'personnel_email');
		//Change the user to the adminaccount otherwise get_users will not work
		wp_set_current_user(1);
		
		//Retrieve all users
		$users = SIM\get_user_accounts();
		
		//loop over the users
		foreach($users as $user){
			//Check for upcoming reviews, but only if not set to be hidden for this year
			if(get_user_meta($user->ID,'hide_annual_review',true) != date('Y')){
				$personnel_info 				= get_user_meta($user->ID,"personnel",true);
				$arrival_date					= get_user_meta($user->ID,'arrival_date',true);
				//Only do when not arriving this year
				if(is_array($personnel_info) and !empty($personnel_info['review_date']) and strpos($arrival_date,date('Y')) === false){
					$reviewdate	= date('m', strtotime($personnel_info['review_date']));
					//Start sending the warning 1 month before until it is done.
					if(($reviewdate -2)<date('m')){
						//Send e-mail
						$to = $user->user_email;
						//Skip if not valid email
						if(strpos($to,'.empty') !== false) continue;
						
						$subject 	 = "Please fill in the annual review questionary.";
						$message 	 = 'Hi '.$user->first_name.',<br><br>';

						//Send Signal message
						SIM\try_send_signal(
							"Hi ".$user->first_name.",\n\nIt is time for your annual review.\nPlease fill in the annual review questionary:\n\n".SITEURL.'/'.$generic_documents['Annual review form']."\n\nThen send it to $personnelCoordinatorEmail",
							$user->ID
						);
						
						//Send e-mail
						$message 	.= 'It is time for your annual review.<br>';
						$message 	.= 'Please fill in the <a href="'.SITEURL.'/'.$generic_documents['Annual review form'].'">review questionaire</a> to prepare for the talk.<br>';
						$message 	.= 'When filled it in send it to me by replying to this e-mail<br><br>';
						$message	.= 'Kind regards,<br><br>the personnel coordinator';
						$headers 	 = array(
							'Content-Type: text/html; charset=UTF-8',
							"Reply-To: $personnelCoordinatorEmail",
							"Bcc: $personnelCoordinatorEmail"
						);
						
						//Send the mail
						wp_mail($to , $subject, $message, $headers );
					}
				}
			}	
		}
	}
}

//Send reminder to people to login
function check_last_login_date(){
	$users = SIM\get_user_accounts();
	foreach($users as $user){
		$lastlogin				= get_user_meta( $user->ID, 'last_login_date',true);
		$lastlogin_date			= date_create($lastlogin);
		$now 	= new \DateTime();
		$years_since_last_login = date_diff($lastlogin_date, $now)->format("%y");
		
		//User has not logged in in the last year
		if($years_since_last_login > 0){
			//Send e-mail
			$to = $user->user_email;
			//Skip if not valid email
			if(strpos($to,'.empty') !== false) continue;

			//Send Signal message
			SIM\try_send_signal(
				"Hi $user->first_name,\n\nWe miss you! We haven't seen you since $lastlogin\n\nPlease pay us a visit on\n".SITEURL,
				$user->ID
			);
			
			//Send e-mail
			$weMissYouMail    = new WeMissYouMail($user, $lastlogin);
			$weMissYouMail->filterMail();
								
			wp_mail( $to, $weMissYouMail->subject, $weMissYouMail->message);
		}
	}
	
}

// Remove scheduled tasks upon module deactivatio
add_action('sim_module_deactivated', function($module_slug, $options){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	wp_clear_scheduled_hook( 'birthday_check_action' );
	wp_clear_scheduled_hook( 'account_expiry_check_action' );
	wp_clear_scheduled_hook( 'vaccination_reminder_action' );
	wp_clear_scheduled_hook( 'greencard_reminder_action' );
	wp_clear_scheduled_hook( 'check_details_mail_action' );
	wp_clear_scheduled_hook( 'review_reminders_action' );
}, 10, 2);