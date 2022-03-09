<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'birthday_check_action', 'SIM\USERMANAGEMENT\birthday_check' );
    add_action( 'vaccination_reminder_action', 'SIM\USERMANAGEMENT\vaccination_reminder' );
    add_action( 'greencard_reminder_action', 'SIM\USERMANAGEMENT\greencard_reminder' );
    add_action( 'check_details_mail_action', 'SIM\USERMANAGEMENT\check_details_mail' );
    add_action( 'account_expiry_check_action', 'SIM\USERMANAGEMENT\account_expiry_check' );
	add_action( 'review_reminders_action', 'SIM\USERMANAGEMENT\review_reminders' );
});

function schedule_tasks(){
    SIM\schedule_task('birthday_check_action', 'daily');
    SIM\schedule_task('account_expiry_check_action', 'daily');
    SIM\schedule_task('vaccination_reminder_action', 'monthly');
    //SIM\schedule_task('greencard_reminder_action', 'monthly');
    SIM\schedule_task('check_details_mail_action', 'yearly');
	//SIM\schedule_task('review_reminders_action', 'monthly');
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
		if ($reminder_html != ""){
			update_user_meta($user->ID,"required_fields_status","");
			$userdata = get_userdata($user->ID);
			if($userdata != null){
				$parents 	= SIM\get_parents($user->ID);
				$recipients = '';
				
				//Is child
				if(count($parents)>0){
					$subject = "Please renew the vaccinations of ".$userdata->first_name;
					$message = 'Dear '.$userdata->last_name.' family,<br><br>';
					$reminder_html = str_replace("Your",$userdata->first_name."'s",$reminder_html);
					
					$child_title = SIM\get_child_title($user->ID);
					foreach($parents as $parent){
						if(strpos($parent->user_email,'.empty') === false){
							if($recipients != '') $recipients .= ', ';
							$recipients .= $parent->user_email;
						}
									
						//Send OneSignal message
						SIM\try_send_signal(
							"Hi ".$parent->first_name.",\nPlease renew the vaccinations  of your $child_title ".$userdata->first_name."!\n\n".get_site_url(),
							$user->ID
						);
					}				
				//not a child
				}else{	
					//If this not a valid email skip this email
					if(strpos($userdata->user_email,'.empty') === false) continue;
					
					$subject = "Please renew your vaccinations";
					$message = 'Hi '.$userdata->first_name.',<br><br>';
					
					//Send Signal message
					SIM\try_send_signal("Hi ".$userdata->first_name.",\nPlease renew your vaccinations!\n\n".get_site_url(),$user->ID);
				}
				
				
				if($recipients != ''){
					//Get the current health coordinator
					$healtCoordinators 			= get_users( array( 'fields' => array( 'ID','display_name' ),'role' => 'medicalinfo' ));
					if($healtCoordinators != null){
						$healtCoordinator = (object)$healtCoordinators[0];
					}else{
						$healtCoordinator = new \stdClass();
						$healtCoordinator->display_name = '';
						error_log("Please assign someone the health coorodinator role!");
					}

					//Send e-mail
					$message .= $reminder_html;
					$message .= '<br>';
					$message .= 'Please renew them as soon as possible.<br>';
					$message .= 'If you have any questions, just reply to this e-mail.<br><br>';
					$message .= 'Kind regards,<br><br>'.$healtCoordinator->display_name;
					$headers = array('Content-Type: text/html; charset=UTF-8');
					$headers[] = 'Reply-To: '.$healtCoordinator->display_name.' <'.SIM\get_module_option('user_management', 'health_email').'>';
					
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
			$reminder_html = check_expiry_date($visa_info['greencard_expiry'],'greencard');
			
			if($reminder_html != ""){		
				$to = $user->user_email;
				
				//Skip if not valid email
				if(strpos($to,'.empty') !== false) continue;
				
				$subject = "Please renew your greencard";
				$message = 'Hi '.$user->first_name.',<br><br>';

				//Send OneSignal message
				SIM\try_send_signal("Hi ".$user->first_name.",\nPlease renew your greencard!\n\n".get_site_url(),$user->ID);
				
				//Send e-mail
				$message .= str_replace('</li>','',str_replace('<li>',"",$reminder_html));
				$message .= '<br>';
				$message .= 'Please renew it as soon as possible.<br>';
				$message .= 'If you have any questions, just reply to this e-mail.<br><br>';
				$message .= 'Kind regards,<br><br>'.$TravelCoordinator->display_name;
				$headers = array('Content-Type: text/html; charset=UTF-8');
				$headers[] = 'Reply-To: '.$TravelCoordinator->display_name.' <'.$TravelCoordinator->user_email.'>';
				
				//Send the mail
				wp_mail($to , $subject, $message, $headers );
			}
		}
	}
}

//send an e-mail with an overview of an users details for them to check
function check_details_mail(){
	wp_set_current_user(1);
	$subject	= 'Please review your website profile';
	
	//Retrieve all uses
	$users = SIM\get_user_accounts($return_family=false,$adults=true,$local_nigerians=true);
	//Loop over the users
	foreach($users as $user){
		$attachments = [];
		$to='enharmsen@gmail.com';

		//Send e-mail
		$message  = "Hi {$user->first_name},<br><br>";
		$message .= 'Once a year we would like to remind you to keep your information on the website up to date.<br>';
		$message .= 'Please check the information below to see if it is still valid, if not update it.<br><br>';
		
		$message .= "<b>Profile picture</b><br>";
		$attachment_id	= get_user_meta($user->ID,'profile_picture',true);
		if(is_numeric($attachment_id)){
			$file = get_attached_file($attachment_id);

			if($file){
				if(strpos($user->user_email, 'sim.org') !== false){
					$to			 ='ewald.harmsen@sim.org';
					$ext		 = pathinfo($file, PATHINFO_EXTENSION);
					$contents	 = file_get_contents($file);
					$image		 = base64_encode($contents);

					$message 	.= "<img src='data:image/$ext;base64,$image'  alt='profilepicture' width='50' height='50'/><br>";
					
				}else{
					$filename 		 = basename($file);
					$attachments[]	 = $file;
					$message 		.= "You have setup a profile picture, see attached $filename<br>";
					/* 
					add_action( 'phpmailer_init', function($phpmailer)use($file){
						$phpmailer->clearAttachments();
						$ext = pathinfo($file, PATHINFO_EXTENSION);
						$phpmailer->AddEmbeddedImage($file, 'ii_ky00j83h0', $filename, 'base64', 'image/'.$ext);
					});

					$message .= "You have uploaded a picture, see attachment     <img src='cid:ii_ky00j83h0' alt='testpicture' width='458' height='523'><br>"; */
				}
			}
		}else{
			continue;
			$message .= "You have not uploaded a picture.<br>";
		}
		$message .= "<br>";

		$message .= "<b>Personal details</b><br>";
		$message .= "Name: $user->display_name<br>";

		$birthday = get_user_meta($user->ID,'birthday',true);
		if(empty($birthday)){
			$birthday = 'no birthday specified.';
		}else{
			$birthday = date('d  F Y', strtotime($birthday));
		}
		$message .= "Birthday: $birthday<br>";

		$local_nigerian = get_user_meta( $user->ID, 'local_nigerian', true );
		if(empty($local_nigerian)){
			$sending_office = get_user_meta($user->ID,'sending_office',true);
			if(empty($sending_office)) $sending_office = 'no sending office specified.';
			$message .= "Sending office: $sending_office<br>";

			$arrivaldate = get_user_meta($user->ID,'arrival_date',true);
			if(empty($arrivaldate)){
				$arrivaldate = 'no arrival date specified.';
			}else{
				$arrivaldate = date('d F Y', strtotime($arrivaldate));
			}
			$message .= "Arrival date in Nigeria: $arrivaldate<br>";
			$message .= "<br>";
		}

		$message .= "<b>Phonenumber";
		$phonenumbers = (array)get_user_meta($user->ID,'phonenumbers',true);
		SIM\clean_up_nested_array($phonenumbers);
		if(count($phonenumbers)>1) $message .= 's';
		$message .= "</b><br>";
		if(empty($phonenumbers)){
			$message .= "No phonenumbers provided.<br>";
		}elseif(count($phonenumbers) == 1){
			$message .= array_values($phonenumbers)[0]."<br>";
		}else{
			$message .= "<ul style='padding-left: 0px;'>";
			foreach($phonenumbers as $number){
				$message .= "<li>$number</li>";
			}
			$message .= "</ul>";
		}

		$user_ministries = (array)get_user_meta($user->ID,'user_ministries',true);
		if(count($user_ministries)>1){
			$message .= "<br><b>Ministries</b><br>";
		}else{
			$message .= "<br><b>Ministry</b><br>";
		}
		SIM\clean_up_nested_array($user_ministries);
		if(empty($user_ministries)){
			$message .= "No ministry provided.<br>";
		}elseif(count($user_ministries) == 1){
			foreach($user_ministries as $ministry=>$job){
				$ministry = str_replace('_',' ',$ministry);
				$message .= "$job at $ministry<br>";
			}
		}else{
			$message .= "<ul style='padding-left: 0px;'>";
			foreach($user_ministries as $ministry=>$job){
				$ministry = str_replace('_',' ',$ministry);
				$message .= "<li>$job at $ministry</li>";
			}
			$message .= "</ul>";
		}
		$message .= "<br>";

		$message .= "<b>State</b><br>";
		$location= (array)get_user_meta($user->ID,'location',true);
		SIM\clean_up_nested_array($location);
		if(empty($location['address'])){
			$message .= "No location provided.<br>";
		}else{
			$message .= $location['address']."<br>";
		}
		$message .= "<br>";

		$family = get_user_meta( $user->ID, 'family', true );
		if(!empty($family)){
			$message .= "<b>Family details</b><br>";
			if(empty($family['partner'])){
				$message .= 'You have no partner<br>';
			}else{
				$message .= 'Spouse: '.get_userdata($family['partner'])->display_name.'<br>';
			}

			if(is_array($family['children'])){
				if(count($family['children']) == 1){
					$child = array_values($family['children'])[0];
					$message .= "Child: ".get_userdata($child)->display_name;
				}else{
					$message .= "Children:";
					$message .= "<ul style='padding-left: 0px;'>";
					foreach($family['children'] as $child){
						$message .= "<li>".get_userdata($child)->display_name.'</li>';
					}
					$message .= "</ul>";
				}
			}
		}

		$message .= '<br><br>';
		$message .= "If any information is not correct, please correct it on <a href='https://simnigeria.org/account/'>simnigeria.org/account</a><br>";

		$message .= '<br><br>';
		$message .= 'Kind regards,<br><br>';
		$headers = [];
		$headers[]	='Content-Type: text/html; charset=UTF-8';
		//$user->user_email
		wp_mail( $to, $subject, $message, $headers, $attachments);
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
		//Send warning e-mail
		$subject 	= "Your account will expire on ".date("d-m-Y", strtotime(" +1 months"));
		$message 	= 'Hi '.$user->first_name.',<br><br>';
		$message 	.= 'This is to inform you that your account on simnigeria will expire on '.date("d F Y", strtotime(" +1 months")).'.<br>';
		$message 	.= 'If you think this should be extended you can contact the STA coordinator (cc).<br><br>';
		$message 	.= 'Kind regards,<br><br>';
		$headers 	= array('Content-Type: text/html; charset=UTF-8');
		$headers[] 	= "cc:".$personnelCoordinatorEmail;
		$headers[] 	= "cc:".$staEmail;
		
		//Send the mail if valid email
		if(strpos($user->user_email,'.empty') === false){
			$recipient = $user->user_email;
		}else{
			$recipient = $staEmail;
		}
		
		SIM\print_array("Sending email"); 
		wp_mail($recipient , $subject, $message, $headers );
		
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
		//Send OneSignal message
		//send_custom_onesignal_message("Hi ".$user->first_name.",\nYour account is expired",$user->ID,get_site_url());
		SIM\try_send_signal(
			"Hi ".$user->first_name.",\nYour account is expired, as you are no longer in Nigeria.",
			$user->ID
		);
		//Send e-mail
		$subject 	= "Your account is expired";
		$message 	= 'Hi '.$user->first_name.',<br><br>';
		$message 	.= 'This is to inform you that your account on simnigeria is expired.<br>';
		$message 	.= 'If you have any questions, just reply to this e-mail.<br><br>';
		$message 	.= 'Kind regards,<br><br>';
		$headers 	= array('Content-Type: text/html; charset=UTF-8');
		$headers[] 	= "cc:".$personnelCoordinatorEmail;
		$headers[] 	= "cc:".$staEmail;
		
		//Send the mail if valid email
		if(strpos($user->user_email,'.empty') === false){
			SIM\print_array("Sending email"); 
			wp_mail( $user->user_email, $subject, $message, $headers );
		}
		
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

						//Send OneSignal message
						//send_custom_onesignal_message("Hi ".$user->first_name.",\nPlease fill in the annual review questionary.",$user->ID,get_site_url());
						SIM\try_send_signal(
							"Hi ".$user->first_name.",\n\nIt is time for your annual review.\nPlease fill in the annual review questionary:\n\n".get_site_url().'/'.$generic_documents['Annual review form']."\n\nThen send it to $personnelCoordinatorEmail",
							$user->ID
						);
						
						//Send e-mail
						$message 	.= 'It is time for your annual review.<br>';
						$message 	.= 'Please fill in the <a href="'.get_site_url().'/'.$generic_documents['Annual review form'].'">review questionaire</a> to prepare for the talk.<br>';
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