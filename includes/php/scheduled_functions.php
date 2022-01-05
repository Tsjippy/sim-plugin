<?php
namespace SIM;

add_action('admin_init', function() {
    if ( is_admin() && get_option( 'Activated_Plugin' ) == 'SIMNIGERIA' ) {
		delete_option( 'Activated_Plugin' );

		add_cron_schedules();
    }
});

//Add action to scan for old pages reminder
add_action('init','SIM\activate_schedules');
function activate_schedules() {
	global $ScheduledFunctions;
	//if (strpos(get_site_url(), 'localhost') === false and get_option("wpstg_is_staging_site") != true) {
		//Add action to run the function 
		foreach($ScheduledFunctions as $func){
			add_action( $func['hookname'].'_action', "SIM\\".$func['hookname'] );
		}
	//}
}

function add_cron_schedules(){
	global $ScheduledFunctions;
	
	print_array("Adding cron schedules");

	//schedule the actions if needed
	foreach($ScheduledFunctions as $func){
		//Not yet activated
		if (! wp_next_scheduled ( $func['hookname'].'_action' )) {
			switch ($func['recurrence']) {
				case 'weekly':
					$time	= strtotime('next Monday');
					break;
				case 'monthly':
					$time	= strtotime('first day of next month');
					break;
				case 'threemonthly':
					//calculate start of next quarter
					$monthcount = 0;
					$month		= 0;
					while(!in_array($month, [1,4,7,10])){
						$monthcount++;
						$time	= strtotime("first day of +$monthcount month");
						$month = date('n',$time);
					}
					break;
				case 'yearly':
					$time	= strtotime('first day of next year');
					break;
				default:
					$time	= time();
			} 

			//schedule
			if(wp_schedule_event( $time, $func['recurrence'], $func['hookname'].'_action' )){
				print_array("Succesfully scheduled ".$func['hookname']." to run ".$func['recurrence']);
			}else{
				print_array("Scheduling of ".$func['hookname']." unsuccesfull");
			}
		}
	}
	
	schedule_auto_archive();
}

//Function to check for old ministry pages
//Whoever checked the box for that ministry on their account gets an email to remind them about the update
function page_age_warning(){
	global $PostOutOfDataWarning;
	global $PublishPostPage;
	global $WebmasterName;
 
	//Get all pages without the static content meta key
	$pages = get_posts(array(
		'numberposts'      => -1,
		'post_type'        => 'page',
		'meta_query' => array(
			array(
			 'key' => 'static_content',
			 'compare' => 'NOT EXISTS'
			),
		)
	));
	
	//Months converted to seconds (days*hours*minutes*seconds)
	$max_age_in_seconds		= $PostOutOfDataWarning * 30.4375 *24 *60 * 60;
	
	//Loop over all the pages
	foreach ( $pages as $page ) {
		//Get the ID of the current page
		$post_id 				= $page->ID;
		$post_title 			= $page->post_title;
		//Get the last modified date
		$seconds_since_updated 	= time()-get_post_modified_time('U',true,$page);
		$page_age				= round($seconds_since_updated/60/60/24);

		//If it is X days since last modified
		if ($seconds_since_updated > $max_age_in_seconds){
			//Get the edit page url
			$url = add_query_arg( ['post_id' => $post_id], get_permalink( $PublishPostPage ) );
			
			//Send an e-mail
			$recipients = get_page_recipients($post_title);
			foreach($recipients as $recipient){
				//Only email if valid email
				if(strpos($recipient->user_email,'.empty') === false){
					$subject = "Please update the contents of ".$post_title;
					$message = 'Dear '.$recipient->display_name.',<br><br>';
					$message .= 'It has been '.$page_age.' days since the page with title "'.$post_title.'" on <a href="https://simnigeria.org">SIM Nigeria</a> has been updated.<br>';
					$message .= 'Please follow this link to update it: <a href="'.$url.'">the link</a>.<br><br>';
					$message .= 'Kind regards,<br><br>'.$WebmasterName;
					$headers = array('Content-Type: text/html; charset=UTF-8');
				
					wp_mail( $recipient->user_email, $subject, $message, $headers );
				}
				
				//Send Signal message
				send_signal_message("Hi ".$recipient->first_name.",\nPlease update the page '$post_title' here:\n\n$url",$recipient->ID);
			}
		}
	}
}

//Function to check who the recipients should be for the page update mail
function get_page_recipients($page_title){
	$recipients = [];
	
	//Get all the users with a ministry set
	$users = get_users( 
		array( 
			'meta_key'     => 'user_ministries'
		)	
	);
	
	//Loop over the users to see if they have this ministry set
	foreach($users as $user){
		if (isset(get_user_meta( $user->ID, 'user_ministries', true)[$page_title])){
			$recipients[] = $user;
		}
	}
	
	//If no one is responsible for this page
	if(count($recipients) == 0){
		$recipients = get_users( array(
			'role'    => 'contentmanager',
		));
	}
	
	return $recipients;
}

//loop over all users and scan for missing info
function personal_info_reminder(){
	global $WebmasterName;
	
	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);
	
	//Retrieve all users
	
	$users = get_missionary_accounts(false,true,true);
	//Loop over the users
 	foreach($users as $user){
		//get the reminders for this user
		$reminder_html = get_required_fields($user->ID);
		//If there are reminders, send an e-mail
		if ($reminder_html != ""){
			$userdata 	= get_userdata($user->ID);
			$recipients = '';
			if($userdata != null){
				$parents = get_parents($user->ID);
				//Is child
				if(count($parents)>0){
					$child_title = get_child_title($user->ID);
					
					$subject = "Please update the personal information of {$userdata->first_name} on the simnigeria website";
					$message = 'Dear '.$userdata->last_name.' family,<br><br>';
					$message .= "Some of the personal information of {$userdata->first_name} on simnigeria.org needs to be updated.<br>";
					$reminder_html = str_replace("Your",$userdata->first_name."'s",$reminder_html);
					
					foreach($parents as $parent){
						if(strpos($parent->user_email,'.empty') === false){
							if($recipients != '') $recipients .= ', ';
							$recipients .= $parent->user_email;
						}
									
						//Send Signal message
						send_signal_message(
							"Hi ".$parent->first_name.",\nPlease update the personal information of your $child_title ".$userdata->first_name." here:\n\n".get_site_url()."/account",
							$user->ID
						);
					}				
				//not a child
				}else{			
					//Send Signal message
					send_signal_message(
						"Hi ".$userdata->first_name.",\nPlease update your personal information here:\n\n".get_site_url()."/account",
						$user->ID
					);
					
					//If this not a valid email skip this email
					if(strpos($userdata->user_email,'.empty') === false) continue;
						
					$subject 	= "Please update your personal information on the simnigeria website";
					$message 	= 'Hi '.$userdata->first_name.',<br><br>';
					$message   .= 'Some of your personal information on simnigeria.org needs to be updated.<br>';
					$recipients	= $userdata->user_email;
				}
				
				//If there is an email set
				if($recipients != ''){					
					//Send e-mail
					$message .= 'Please click on the items below to update the data:';
					$message .= $reminder_html;
					$message .= '<br><br>';
					$message .= 'Kind regards,<br><br>'.$WebmasterName;
					$headers = array('Content-Type: text/html; charset=UTF-8');
					
					wp_mail( $recipients, $subject, $message, $headers );
				}
			}
		}
	} 
}

//send an e-mail with an overview of an users details for them to check
function check_details_mail(){
	wp_set_current_user(1);
	global $WebmasterName;

	$subject	= 'Please review your website profile';
	
	//Retrieve all uses
	$users = get_missionary_accounts($return_family=false,$adults=true,$local_nigerians=true);
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
		remove_from_nested_array($phonenumbers);
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
		remove_from_nested_array($user_ministries);
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
		remove_from_nested_array($location);
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
		$message .= 'Kind regards,<br><br>'.$WebmasterName;
		$headers = [];
		$headers[]	='Content-Type: text/html; charset=UTF-8';
		//$user->user_email
		wp_mail( $to, $subject, $message, $headers, $attachments);
	}
}

//loop over all users and scan for expiry vaccinations
function vaccination_reminder(){
	global $WebmasterName;
	global $VaccinationExpiryWarning;
	global $HealthCoordinatorEmail;
	global $HealtCoordinator;
	
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
				$parents 	= get_parents($user->ID);
				$recipients = '';
				
				//Is child
				if(count($parents)>0){
					$subject = "Please renew the vaccinations of ".$userdata->first_name;
					$message = 'Dear '.$userdata->last_name.' family,<br><br>';
					$reminder_html = str_replace("Your",$userdata->first_name."'s",$reminder_html);
					
					$child_title = get_child_title($user->ID);
					foreach($parents as $parent){
						if(strpos($parent->user_email,'.empty') === false){
							if($recipients != '') $recipients .= ', ';
							$recipients .= $parent->user_email;
						}
									
						//Send OneSignal message
						//send_custom_onesignal_message("Hi ".$parent->first_name.",\nPlease renew the vaccinations of your $child_title ".$userdata->first_name,$user->ID,get_site_url());
						send_signal_message(
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
					send_signal_message("Hi ".$userdata->first_name.",\nPlease renew your vaccinations!\n\n".get_site_url(),$user->ID);
				}
				
				
				if($recipients != ''){
					//Send e-mail
					$message .= $reminder_html;
					$message .= '<br>';
					$message .= 'Please renew them as soon as possible.<br>';
					$message .= 'If you have any questions, just reply to this e-mail.<br><br>';
					$message .= 'Kind regards,<br><br>'.$HealtCoordinator->display_name;
					$headers = array('Content-Type: text/html; charset=UTF-8');
					$headers[] = 'Reply-To: '.$HealtCoordinator->display_name.' <'.$HealthCoordinatorEmail.'>';
					
					//Send the mail
					wp_mail($recipients , $subject, $message, $headers );
				}
			}
		}
	}
}

//loop over all users and scan for expiry greencards
function greencard_reminder(){
	global $VaccinationExpiryWarning;
	global $TravelCoordinator;
	
	/* 	//Change the user to the adminaccount otherwise get_users will not work
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
				send_custom_onesignal_message("Hi ".$user->first_name.",\nPlease renew your greencard!",$user->ID,get_site_url(null, '/account/'));
				send_signal_message("Hi ".$user->first_name.",\nPlease renew your greencard!\n\n".get_site_url(),$user->ID);
				
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
	} */
}

//send reminders about annual review
function review_reminders(){
	global $PersonnelCoordinatorEmail;
	
	$generic_documents = get_option('personnel_documents');
	/* if(is_array($generic_documents) and !empty($generic_documents['Annual review form'])){
		//Change the user to the adminaccount otherwise get_users will not work
		wp_set_current_user(1);
		
		//Retrieve all users
		$users = get_missionary_accounts();
		//print_array($users);
		
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
						send_signal_message(
							"Hi ".$user->first_name.",\n\nIt is time for your annual review.\nPlease fill in the annual review questionary:\n\n".get_site_url().'/'.$generic_documents['Annual review form']."\n\nThen send it to $PersonnelCoordinatorEmail",
							$user->ID
						);
						
						//Send e-mail
						$message 	.= 'It is time for your annual review.<br>';
						$message 	.= 'Please fill in the <a href="'.get_site_url().'/'.$generic_documents['Annual review form'].'">review questionaire</a> to prepare for the talk.<br>';
						$message 	.= 'When filled it in send it to me by replying to this e-mail<br><br>';
						$message	.= 'Kind regards,<br><br>the personnel coordinator';
						$headers 	 = array(
							'Content-Type: text/html; charset=UTF-8',
							"Reply-To: $PersonnelCoordinatorEmail",
							"Bcc: $PersonnelCoordinatorEmail"
						);
						
						//Send the mail
						wp_mail($to , $subject, $message, $headers );
					}
				}
			}	
		}
	} */
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
	global $VaccinationExpiryWarning;
	
	if (!empty($VaccinationExpiryWarning) and !empty($date)){
		$reminder_html = "";
		
		//Date of first warning
		$now = new \DateTime();
		$interval = new \DateInterval('P'.$VaccinationExpiryWarning.'M');
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
		send_signal_message("Hi ".$first_name.",\nCongratulations with your birthday!",$user_id);

		//Send to parents
		if (isset($family["father"]) or isset($family["mother"])){
			$child_title = get_child_title($user->ID);
			
			$message = "Congratulations with the birthday of your $child_title ".get_userdata($user->ID)->first_name;
		}
		
		if (isset($family["father"])){	
			send_signal_message(
				"Hi ".get_userdata($family["father"])->first_name.",\n$message",
				$family["father"]
			);
		}
		if (isset($family["mother"])){
			send_signal_message(
				"Hi ".get_userdata($family["mother"])->first_name.",\n$message",
				$family["mother"]
			);
		}
	}
}

function anniversary_check(){
	global $Events;

	$Events->retrieve_events(date('Y-m-d'),date('Y-m-d'));

	foreach($Events->events as $event){
		$start_year	= get_post_meta($event->ID,'celebrationdate',true);
		if(!empty($start_year)){
			$userdata		= get_userdata($event->post_author);
			$first_name		= $userdata->first_name;
			$event_title	= $event->post_title;
			$partner_id		= has_partner($event->post_author);

			if($partner_id){
				$partnerdata	= get_userdata($partner_id);
				$couple_string	= $first_name.' & '.$partnerdata->display_name;
				$event_title	= trim(str_replace($couple_string,"", $event_title));
			}
			
			$event_title	= trim(str_replace($userdata->display_name,"", $event_title));

			$age	= get_age_in_words($start_year);

			send_signal_message("Hi $first_name,\nCongratulations with your $age $event_title!", $event->post_author);

			//If the author has a partner and this events applies to both of them
			if($partner_id and strpos($event->post_title, $couple_string)){
				send_signal_message("Hi {$partnerdata->first_name},\nCongratulations with your $event_title!", $partner_id);
			}
		}
	}
}

function account_expiry_check(){
	global $WebmasterName;
	global $PersonnelCoordinatorEmail;
	global $STAEmail;
	require_once(ABSPATH.'wp-admin/includes/user.php');
	
	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);
	
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
		$message 	.= 'Kind regards,<br><br>'.$WebmasterName;
		$headers 	= array('Content-Type: text/html; charset=UTF-8');
		$headers[] 	= "cc:".$PersonnelCoordinatorEmail;
		$headers[] 	= "cc:".$STAEmail;
		
		//Send the mail if valid email
		if(strpos($user->user_email,'.empty') === false){
			$recipient = $user->user_email;
		}else{
			$recipient = $STAEmail;
		}
		
		print_array("Sending email"); 
		wp_mail($recipient , $subject, $message, $headers );
		
		//Send OneSignal message
		send_signal_message("Hi ".$user->first_name.",\nThis is just a reminder that your account on simnigeria.org will be deleted on ".date("d F Y", strtotime(" +1 months")),$user->ID);
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
		send_signal_message(
			"Hi ".$user->first_name.",\nYour account is expired, as you are no longer in Nigeria.",
			$user->ID
		);
		//Send e-mail
		$subject 	= "Your account is expired";
		$message 	= 'Hi '.$user->first_name.',<br><br>';
		$message 	.= 'This is to inform you that your account on simnigeria is expired.<br>';
		$message 	.= 'If you have any questions, just reply to this e-mail.<br><br>';
		$message 	.= 'Kind regards,<br><br>'.$WebmasterName;
		$headers 	= array('Content-Type: text/html; charset=UTF-8');
		$headers[] 	= "cc:".$PersonnelCoordinatorEmail;
		$headers[] 	= "cc:".$STAEmail;
		
		//Send the mail if valid email
		if(strpos($user->user_email,'.empty') === false){
			print_array("Sending email"); 
			wp_mail( $user->user_email, $subject, $message, $headers );
		}
		
		//Delete the account
		print_array("Deleting user with id ".$user->ID." and name ".$user->display_name." as it was a temporary account.");
		wp_delete_user($user->ID);
	}
}

//Send reminder to people to login
function check_last_login_date(){
	$users = get_missionary_accounts();
	foreach($users as $user){
		$lastlogin = get_user_meta( $user->ID, 'last_login_date',true);
		$lastlogin_date	= date_create($lastlogin);
		$now 	= new \DateTime();
		$years_since_last_login = date_diff($lastlogin_date,$now)->format("%y");
		
		//User has not logged in in the last year
		if($years_since_last_login > 0){
			//Send e-mail
			$to = $user->user_email;
			//Skip if not valid email
			if(strpos($to,'.empty') !== false) continue;

			//Send Signal message
			send_signal_message(
				"Hi ".$user->first_name.",\n\nWe miss you! We haven't seen you since $lastlogin\n\nPlease pay us a visit on\n".get_site_url(),
				$user->ID
			);
			
			//Send e-mail
			$subject 	 = "We miss you!";
			$message 	 = 'Hi '.$user->first_name.',<br><br>';
			$message 	.= "We miss you! We haven't seen you since $lastlogin<br>";
			$message 	.= 'Please pay us a visit on <a href="'.get_site_url().'">simnigeria.org</a><br>';
			$message	.= 'Kind regards,<br><br>SIM Nigeria';
			$headers 	 = array(
				'Content-Type: text/html; charset=UTF-8',
			);
			
			//Send the mail
			wp_mail($to , $subject, $message, $headers );
		}
	}
	
}

//Send contact info
function send_missonary_detail(){
	global $WebmasterName;
	
	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);
	
	//Sort users on last name, then on first name
	$args = array(
		'meta_query' => array(
			'relation' => 'AND',
			'query_one' => array(
				'key' => 'last_name'
			),
			'query_two' => array(
				'key' => 'first_name'
			), 
		),
		'orderby' => array( 
			'query_one' => 'ASC',
			'query_two' => 'ASC',
		),
	);
	$users = get_missionary_accounts($return_family=false,$adults=true,$local_nigerians=true,$fields=[],$extra_args=$args);
	
	//Loop over all users to add a row with their data to the table
	$user_details = [];
	$email_headers = array('Content-Type: text/html; charset=UTF-8');

	foreach($users as $user){
		//skip admin
		if ($user->ID != 1 and $user->display_name != 'Signal Bot'){
			$privacy_preference = get_user_meta( $user->ID, 'privacy_preference', true );
			if(!is_array($privacy_preference)) $privacy_preference = [];
			
			$name		= $user->display_name; //Real name
			$nickname	= get_user_meta($user->ID,'nickname',true); //persons name in case of a office account
			if($name != $nickname and $nickname != '') $name .= "\n ($nickname)";
			
			$email	= $user->user_email;
			
			//Add to recipients
			if (strpos($user->user_email,'.empty') === false){
				$email_headers[] = "Bcc:".$email;
			}else{
				$email	= '';
			}
			
			$phonenumbers = "";
			if(empty($privacy_preference['hide_phone'])){
				$user_phonenumbers = (array)get_user_meta ( $user->ID,"phonenumbers",true);
				foreach($user_phonenumbers as $key=>$phonenumber){
					if ($key > 0) $phonenumbers .= "\n";
					$phonenumbers .= $phonenumber;
				}
			}
			
			$ministries = "";
			if(empty($privacy_preference['hide_ministry'])){
				$user_ministries = (array)get_user_meta( $user->ID, "user_ministries", true);
				$i = 0;
				foreach ($user_ministries as $key=>$user_ministry) {
					if ($i > 0) $ministries .= "\n";
					$ministries  .= str_replace("_"," ",$key);
					$i++;
				}
			}
			
			$compound = "";
			if(empty($privacy_preference['hide_location'])){
				$location = (array)get_user_meta( $user->ID, 'location', true );
				if(isset($location['compound'])){
					$compound = $location['compound'];
				}
			}
			$height = max(count($user_phonenumbers),count($user_ministries));
			$user_details[] = [$name,$email,$phonenumbers,$ministries,$compound];
		}
	}
	
	//Headers of the table
	$table_headers = ["Name"," E-mail"," Phone"," Ministries"," State"];
	//Create a pdf and add it to the mail
	$attachments = array( create_contactlist_pdf($table_headers,$user_details));
	
	//Send e-mail
	$subject = "Contact list";
	$message = 'Dear all,<br><br>';
	$message .= 'Attached you can find a list off all missionary contact info.<br>';
	$message .= 'This information is for SIM use only. Do not share this informations with others.<br>';
	$message .= 'Visit <a href="'.get_site_url(null,'/account/').'"> the SIM Nigeria website</a> if your contactinfo is not listed or not up to date.<br><br>';
	$message .= 'Kind regards,<br><br>'.$WebmasterName.'<br><br>';
	
	//Send the mail
	wp_mail( get_option( 'admin_email' ), $subject, $message, $email_headers, $attachments);
}

function send_reimbursement_requests(){
	global $WebmasterName;
	global $FinanceEmail;

	print_array('Sending reimbursement requests');

	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);
	
	//Export the excel file to temp
	$formtable = new FormTable();

	//make sure we have permission on the data
	$formtable->table_edit_permissions = true;

	//fill the excel data
	$formtable->show_formresults_table(['id'=>'6','datatype'=>'reimbursement']);

	//if there are reimbursements
	if(empty($formtable->submission_data )){
		print_array('No reimbursement requests found');
	}else{
		//Get all files in the reimbursement dir as they are the receipts
		$attachments	= glob(wp_upload_dir()['path']."/form_uploads/Reimbursement/*.*");
		//Create the excel
		$attachments[]	= $formtable->export_excel("Reimbursement requests - ".date("F Y", strtotime("previous month")).'.xlsx',false);

		//mark all entries as archived
		foreach($formtable->submission_data as $id=>$sub_data){
			$formtable->formresults		= maybe_unserialize($sub_data->formresults);
			$formtable->submission_id	= $sub_data->id;
			$formtable->update_submission_data(true);
		}

		//If there are any attachements
		if(!empty($attachments)){
			//Send e-mail
			$subject = "Reimbursements for ".date("F Y", strtotime("previous month"));
			$message = 'Dear finance team,<br><br>';
			$message .= 'Attached you can find all reimbursement requests of this month<br><br>';
			$message .= 'Kind regards,<br><br>'.$WebmasterName.'<br><br>';
			$email_headers = array('Content-Type: text/html; charset=UTF-8');
			$email_headers[] = "Bcc:enharmsen@gmail.com";
			
			//Send the mail
			wp_mail($FinanceEmail , $subject, $message,$email_headers, $attachments);

			//Loop over the attachements and delete them from the server
			foreach($attachments as $attachment){
				//Remove the upload attached to the form
				unlink($attachment);
			}
		}
	}
}

function expired_posts_check(){
	//Get all posts with the expirydate meta key with a value equal or before today
	$posts = get_posts(array(
		'numberposts'      => -1,
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key' => 'expirydate',
				'compare' => 'EXISTS'
			),
			array(
				'key' => 'expirydate',
				'value' => date("Y-m-d"), 
				'compare' => '<=',
				'type' => 'DATE'
			),
		)
	));
	
	foreach($posts as $post){
		print_array("Moving '{$post->post_title}' to trash as it has expired");
		wp_trash_post($post->ID);
	}
}

function read_reminder(){
	global $WebmasterName;
	
	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);
	
	$users = get_missionary_accounts();
	foreach($users as $user){
		$html = get_must_read_documents($user->ID);
		
		//Only continue if there are documents to read
		if($html != ''){
			$to = $user->user_email;
				
			//Skip if not valid email
			if(strpos($to,'.empty') !== false) continue;
			
			$subject = "Please read this website content:";
			$message = 'Hi '.$user->first_name.',<br>';

			//Send Signal message
			send_signal_message("Hi ".$user->first_name.",\nPlease read some mandatory content.\n\nVisit ".get_site_url()." to see the content",$user->ID);
			
			//Send e-mail
			$message .= $html;
			$message .= '<br>';
			$message .= 'Please read it as soon as possible.<br>';
			$message .= 'Mark as read by clicking on the button on the bottom of each page<br><br>';
			$message .= "Kind regards,<br><br>$WebmasterName<br>Webmaster simnigeria.org<br><br><br>";
			$message .= "This message is automatically generated";
			$headers = array('Content-Type: text/html; charset=UTF-8');
			
			//Send the mail
			wp_mail($to , $subject, $message, $headers );
		}
	}
}

//Creates subimages
function process_images(){
	include_once( ABSPATH . 'wp-admin/includes/image.php' );
	$images = get_posts(array(
		'numberposts'      => -1,
		'post_type'        => 'attachment',
	));
	
	foreach($images as $image){
		wp_maybe_generate_attachment_metadata($image);
	}
}

//clean up events, in events table. Not the post
function remove_old_events(){
	global $Events;
	global $wpdb;

	$query	= "DELETE FROM {$Events->table_name} WHERE startdate<'".date('Y-m-d',strtotime('-2 years'))."'";

	$wpdb->get_results( $query);
}

//Add three monthly schedule
add_filter( 'cron_schedules', 'SIM\cron_add_schedules' );
function cron_add_schedules( $schedules ) {
   // Adds once monthly to the existing schedules.
   $schedules['monthly'] = array(
       'interval' => 2628000,
       'display' => __( 'Once every month' )
   );
   
   // Adds threemonthly to the existing schedules.
   $schedules['threemonthly'] = array(
       'interval' => 7884000,
       'display' => __( 'Once every 3 months' )
   );

   // Adds threemonthly to the existing schedules.
	$schedules['yearly'] = array(
		'interval' => 31557600,
		'display' => __( 'Once every year' )
	);
   return $schedules;
}
