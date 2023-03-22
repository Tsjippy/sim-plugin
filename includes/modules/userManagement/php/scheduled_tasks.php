<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'birthday_check_action', __NAMESPACE__.'\birthdayCheck' );
    add_action( 'vaccination_reminder_action', __NAMESPACE__.'\vaccinationReminder' );
    add_action( 'greencard_reminder_action', __NAMESPACE__.'\greencardReminder' );
    add_action( 'check_details_mail_action', __NAMESPACE__.'\checkDetailsMail' );
    add_action( 'account_expiry_check_action', __NAMESPACE__.'\accountExpiryCheck' );
	add_action( 'review_reminders_action', __NAMESPACE__.'\reviewReminders' );
	add_action( 'check_last_login_date_action', __NAMESPACE__.'\checkLastLoginDate' );
});

function scheduleTasks(){
    SIM\scheduleTask('birthday_check_action', 'daily');
    SIM\scheduleTask('account_expiry_check_action', 'daily');
    SIM\scheduleTask('vaccination_reminder_action', 'monthly');
	//SIM\scheduleTask('review_reminders_action', 'monthly');
	SIM\scheduleTask('check_last_login_date_action', 'monthly');

	$freq	= SIM\getModuleOption(MODULE_SLUG, 'greencard_reminder_freq');
	if($freq){
		SIM\scheduleTask('greencard_reminder_action', $freq);
	}
	$freq	= SIM\getModuleOption(MODULE_SLUG, 'check_details_mail_freq');
	if($freq){
		SIM\scheduleTask('check_details_mail_action', $freq);
	}
}

function birthdayCheck(){
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
		$userId 	= $user->ID;
		$firstName 	= $user->first_name;
		
		$family = get_user_meta( $userId, 'family', true );
		if ($family == ""){
			$family = [];
		}
	
		//Send birthday wish to the user
		SIM\trySendSignal("Hi $firstName,\nCongratulations with your birthday!", $userId);

		//Send to parents
		if (isset($family["father"]) || isset($family["mother"])){
			$childTitle = SIM\getChildTitle($user->ID);
			
			$message = "Congratulations with the birthday of your $childTitle ".get_userdata($user->ID)->first_name;
		}
		
		if (isset($family["father"])){
			SIM\trySendSignal(
				"Hi ".get_userdata($family["father"])->first_name.",\n$message",
				$family["father"]
			);
		}
		if (isset($family["mother"])){
			SIM\trySendSignal(
				"Hi ".get_userdata($family["mother"])->first_name.",\n$message",
				$family["mother"]
			);
		}
	}
}

/**
 * loop over all users and scan for expiry vaccinations
 */
function vaccinationReminder(){
	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);
	
	//Retrieve all users
	$users = get_users( array( 'fields' => array( 'ID','user_login','display_name' ) ) );
	
	//loop over the users
	foreach($users as $user){
		$reminderHtml = vaccinationReminders($user->ID);

		//If there are reminders, send an e-mail
		if (!empty($reminderHtml)){
			$userdata = get_userdata($user->ID);
			if($userdata != null){
				$parents 	= SIM\getParents($user->ID);
				$recipients = '';
				
				//Is child
				if($parents){
					
					$reminderHtml = str_replace("Your", $userdata->first_name."'s", $reminderHtml);

					$vaccinationWarningMail    	= new AdultVaccinationWarningMail($userdata);
					$vaccinationWarningMail->filterMail();
					$subject					= $vaccinationWarningMail->subject;
					$message					= $vaccinationWarningMail->message;
					
					$childTitle = SIM\getChildTitle($user->ID);
					foreach($parents as $parent){
						if(strpos($parent->user_email,'.empty') === false){
							if(!empty($recipients)){
								$recipients .= ', ';
							}
							$recipients .= $parent->user_email;
						}
									
						//Send OneSignal message
						SIM\trySendSignal(
							"Hi $parent->first_name,\nPlease renew the vaccinations  of your $childTitle $userdata->first_name!\n\n".SITEURL,
							$user->ID
						);
					}
				//not a child
				}else{
					//If this not a valid email skip this email
					if(strpos($userdata->user_email,'.empty') === false){
						continue;
					}

					$vaccinationWarningMail    	= new AdultVaccinationWarningMail($userdata);
					$vaccinationWarningMail->filterMail();
					$subject					= $vaccinationWarningMail->subject;
					$message					= $vaccinationWarningMail->message;
					
					//Send Signal message
					SIM\trySendSignal("Hi $userdata->first_name,\nPlease renew your vaccinations!\n\n".SITEURL, $user->ID);
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

					$headers = ['Reply-To: '.$healtCoordinator->display_name.' <'.SIM\getModuleOption(MODULE_SLUG, 'health_email').'>'];
					
					//Send the mail
					wp_mail($recipients , $subject, $message, $headers );
				}
			}
		}
	}
}

/**
 * Get the vaccination reminder html of an user
 *
 * @param	int		$userId	WP_User id
 *
 * @return	string	The html
 */
function vaccinationReminders($userId){
	//Get the current users medical data
	$medicalUserInfo = (array)get_user_meta( $userId, "medical",true);

	$reminderHtml = "";
	foreach($medicalUserInfo as $key=>$info){
		if (strpos($key, 'expiry_date') !== false) {
			//Its an array, so another vaccination
			if(is_array($info)){
				foreach($info as $date_key=>$date){
					//Get the vaccination name of this other vaccination
					$vaccinationName = $medicalUserInfo['other_vaccination'][$date_key];
					if($date != ""){
						$reminderHtml .= checkExpiryDate($date, "$vaccinationName vaccination");
					}
				}
			}else{
				//Get the clean vaccination name
				$vaccinationName = str_replace('expiry_date_of_your_','', $key);
				$vaccinationName = str_replace('_vaccination', '', $vaccinationName);
				$vaccinationName = ucwords(str_replace('_', ' ', $vaccinationName));
				$reminderHtml .= checkExpiryDate($info, "$vaccinationName vaccination");
			}
		}
	}
	
	return $reminderHtml;
}

/**
 * Check expiry date of all vacination
 *
 * @param	string	$date			The expiry date
 * @param	string	$expiryName		The name of the vaccination
 *
 * @return	string					Html listing all vaccination who are expired
 */
function checkExpiryDate($date, $expiryName){
	$vaccinationWarningTime	= SIM\getModuleOption(MODULE_SLUG, 'vaccination_warning_time');
	if ($vaccinationWarningTime && !empty($date)){
		$reminderHtml 	= "";

		//Vaccination expiry date
		try{
			$expiryDate = new \DateTime($date);
		}catch (\Exception $e) {
			return;
		}

		//Date of first warning
		$warningDate	= new \DateTime($date);
		$interval		= new \DateInterval('P'.$vaccinationWarningTime.'M');
		date_sub($warningDate, $interval);

		$now			= new \DateTime();
		
		$niceExpiryDate = $expiryDate->format('j F Y');
		
		//Expires today
		if($niceExpiryDate == $now->format('j F Y')){
			$reminderHtml .= "<li>Your $expiryName expires today.</li><br>";
		//In the past
		}elseif($expiryDate < $now){
			$reminderHtml .= "<li>Your $expiryName is expired on $niceExpiryDate. </li><br>";
		//In the near future
		}elseif($now >= $warningDate){
			$diff=date_diff(date_create(date("Y-m-d")), $expiryDate)->format("%a");
			if($diff == 1){
				$text = "tomorrow";
			}else{
				$text = "in $diff days on $niceExpiryDate";
			}
			$reminderHtml .= "<li>Your $expiryName will expire $text.</li><br>";
		}
		
		return $reminderHtml;
	}
}

/**
 * loop over all users and scan for expiry greencards, if so contact them
 */
function greencardReminder(){
	//Get the current travel coordinator
	$travelCoordinator 			= get_users( array( 'role' => 'visainfo' ));
	if($travelCoordinator != null){
		$travelCoordinator = $travelCoordinator[0];
	}else{
		$travelCoordinator = new \stdClass();
		$travelCoordinator->display_name = '';
		error_log("Please assign someone the travelcoorodinator role!");
	}
	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);
	
	//Retrieve all users
	$users = get_users( array( 'fields' => array( 'ID','user_login','display_name' ) ) );
	
	//loop over the users
	foreach($users as $user){
		$visaInfo = get_user_meta( $user->ID, "visa_info", true);

		//If there are reminders, send an e-mail
		if (is_array($visaInfo) && isset($visaInfo['greencard_expiry'])){
			$reminder = checkExpiryDate($visaInfo['greencard_expiry'], 'greencard');
			$reminder = str_replace(['</li>', '<li>'], "", $reminder);
			
			if(!empty($reminder)){
				$to = $user->user_email;
				
				//Skip if not valid email
				if(strpos($to,'.empty') !== false){
					continue;
				}

				//Send e-mail
				$greenCardReminderMail    = new GreenCardReminderMail($user, $reminder);
				$greenCardReminderMail->filterMail();
				$headers = ['Reply-To: '.$travelCoordinator->display_name.' <'.$travelCoordinator->user_email.'>'];
									
				wp_mail( $to, $greenCardReminderMail->subject, $greenCardReminderMail->message, $headers);
				
				//Send OneSignal message
				SIM\trySendSignal("Hi $user->first_name,\nPlease renew your greencard!\n\n".SITEURL, $user->ID);
			}
		}
	}
}

//Store page with user-info shortcode
add_action( 'wp_after_insert_post', function($postId, $post){
    if(has_shortcode($post->post_content, 'user-info')){
        global $Modules;

        $Modules[MODULE_SLUG]['account_page'][]    = $postId;

        update_option('sim_modules', $Modules);
    }
}, 10, 2);

add_action( 'wp_trash_post', function($postId){
    global $Modules;
    $index  = array_search($postId, $Modules[MODULE_SLUG]['account_page']);
    if($index){
        unset($Modules[MODULE_SLUG]['account_page'][$index]);
        $Modules[MODULE_SLUG]['account_page']   = array_values($Modules[MODULE_SLUG]['account_page']);
        update_option('sim_modules', $Modules);
    }
} );

/**
 * send an e-mail with an overview of an users details for them to check
 */
function checkDetailsMail(){
	wp_set_current_user(1);
	$subject	= 'Please review your website profile';
	
	//Retrieve all users
	$users 			= SIM\getUserAccounts(false, true);

	$accountPage	= SIM\getModuleOption(MODULE_SLUG, 'account_page');
	$accountPageUrl	= get_permalink($accountPage);

	if(!$accountPageUrl){
		SIM\printArray('No account page defined');
		return;
	}
	$baseUrl		= "$accountPageUrl?main_tab=";

	$styleString	= "style='text-decoration:none; color:#444;'";

	//Loop over the users
	foreach($users as $user){
		//Send e-mail
		$message  = "Hi {$user->first_name},<br><br>";
		$message .= 'Once a year we would like to remind you to keep your information on the website up to date.<br>';
		$message .= 'Please check the information below to see if it is still valid, if not update it.<br><br>';
		
		/*
		** PROFILE PICTURE
 		*/
		$message .= "<a href='{$baseUrl}profilePicture' $styleString><b>Profile picture</b></a><br>";
		$profilePicture	= getProfilePictureUrl($user->ID);
		if($profilePicture){
			$message 		.= "This is your profile picture:<br>";
			$message 		.= "<img src='$profilePicture' alt='$profilePicture' width='100px' height='100px'";
			$message 		.= "<br><br>";
		}else{
			$message .= "<table>";
				$message .= "<tr>";
					$message .= "<td>";
						$message .= "<a href='{$baseUrl}profilePicture' $styleString>You have not uploaded a picture</a>";
					$message .= "</td>";
				$message .= "</tr>";
			$message .= "</table>";
		}
		$message .= "<br>";

		/*
		** PERSONAL DETAILS
 		*/
		$message .= "<a href='{$baseUrl}generic_info' $styleString><b>Personal details</b></a><br>";
		$message .= "<table>";
			$message .= "<tr>";
				$message .= "<td>";
					$message .= "Name:";
				$message .= "</td>";
				$message .= "<td>";
					$message .= "$user->display_name";
				$message .= "</td>";
			$message .= "</tr>";

			$birthday = get_user_meta($user->ID, 'birthday', true);
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
					$message .= "<a href='{$baseUrl}generic_info#birthday' $styleString>$birthday</a>";
				$message .= "</td>";
			$message .= "</tr>";

			$visaInfo = get_user_meta( $user->ID, 'visa_info', true );
			if(!isset($visaInfo['permit_type']) || $visaInfo['permit_type'] == 'greencard'){
				$sendingOffice = get_user_meta($user->ID, 'sending_office', true);
				if(empty($sendingOffice)){
					$sendingOffice = 'No sending office specified';
				}
				$message .= "<tr>";
					$message .= "<td>";
						$message .= "Sending office:";
					$message .= "</td>";
					$message .= "<td>";
						$message .= "<a href='{$baseUrl}generic_info#sending_office' $styleString>$sendingOffice</a>";
					$message .= "</td>";
				$message .= "</tr>";

				$arrivalDate = get_user_meta($user->ID, 'arrival_date', true);
				if(empty($arrivalDate)){
					$arrivalDate = 'No arrival date specified';
				}else{
					$arrivalDate = date('d F Y', strtotime($arrivalDate));
				}
				$message .= "<tr>";
					$message .= "<td>";
						$message .= "Arrival date:";
					$message .= "</td>";
					$message .= "<td>";
						$message .= "<a href='{$baseUrl}generic_info#arrivalDate' $styleString>$arrivalDate</a>";
					$message .= "</td>";
				$message .= "</tr>";
			}
		$message .= "</table>";
		$message .= "<br>";

		/*
		** PHONENUMBERS
 		*/
		$phonenumbers = (array)get_user_meta($user->ID, 'phonenumbers', true);
		SIM\cleanUpNestedArray($phonenumbers);
		$title	= 'Phonenumber';
		if(count($phonenumbers)>1){
			$title .= 's';
		}

		$message .= "<a href='{$baseUrl}generic_info' $styleString><b>$title</b></a><br>";
		$message .= "<table>";
		if(empty($phonenumbers)){
			$message .= "<tr>";
				$message .= "<td>";
					$message .= "<a href='{$baseUrl}generic_info#phonenumbers[0]' $styleString>No phonenumbers provided</a>";
				$message .= "</td>";
			$message .= "</tr>";
		}elseif(count($phonenumbers) == 1){
			$message .= "<tr>";
				$message .= "<td>";
					$message .= "<a href='{$baseUrl}generic_info#phonenumbers[0]' $styleString>".array_values($phonenumbers)[0].'</a>';
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
						$message .= "<a href='{$baseUrl}generic_info#phonenumbers[$key]' $styleString>$number</a>";
					$message .= "</td>";
				$message .= "</tr>";
			}
		}
		$message .= "</table>";
		$message .= "<br>";

		/*
		** MINISTRIES
 		*/
		$userMinistries = (array)get_user_meta($user->ID, 'jobs', true);
		if(count($userMinistries)>1){
			$title	= 'Ministries';
		}else{
			$title	= 'Ministry';
		}
		$message .= "<a href='{$baseUrl}generic_info' $styleString><b>$title</b></a><br>";

		$message .= "<table>";
			SIM\cleanUpNestedArray($userMinistries);
			if(empty($userMinistries)){
				$message .= "<tr>";
					$message .= "<td>";
						$message .= "<a href='{$baseUrl}generic_info#ministries[]' $styleString>No ministry provided</a>";
					$message .= "</td>";
				$message .= "</tr>";
			}else{
				foreach($userMinistries as $ministry=>$job){
					$message .= "<tr>";
						$message .= "<td>";
							$message .= get_the_title($ministry).":";
						$message .= "</td>";
						$message .= "<td>";
							$message .= "<a href='{$baseUrl}generic_info#ministries[]' $styleString>$job</a>";
						$message .= "</td>";
					$message .= "</tr>";
				}
				
			}
		$message .= "</table>";
		$message .= "<br>";

		/*
		** LOCATION
 		*/
		$message	.= "<a href='{$baseUrl}location' $styleString><b>Location</b></a><br>";
		$location	= (array)get_user_meta($user->ID, 'location', true);
		SIM\cleanUpNestedArray($location);
		if(empty($location['address'])){
			$location = "No location provided";
		}else{
			$location = $location['address'];
		}

		$message .= "<table>";
			$message .= "<tr>";
				$message .= "<td>";
					$message .= "<a href='{$baseUrl}location#location[compound]' $styleString>$location</a>" ;
				$message .= "</td>";
			$message .= "</tr>";
		$message .= "</table>";
		$message .= "<br>";

		/*
		** FAMILY
 		*/
		$family = get_user_meta( $user->ID, 'family', true );
		if(!empty($family)){
			if(empty($family['picture'])){
				$picture	= "You have not uploaded a picture";
			}else{
				$url		= wp_get_attachment_url($family['picture'][0]);
				$picture	= "<img src='$url' width=100 height=100>";
			}

			$weddingDate	= '';
			if(empty($family['partner'])){
				$partner 		= 'You have no spouse';
			}else{
				$partner = get_userdata($family['partner'])->display_name;

				if(empty($family['weddingdate'])){
					$text	= "No weddingdate provided";
				}else{
					$text	= date('d F Y', strtotime($family['weddingdate']));
				}

				$weddingDate = "<tr>";
					$weddingDate .= "<td>";
						$weddingDate .= "Wedding date:";
					$weddingDate .= "</td>";
					$weddingDate .= "<td>";
						$weddingDate .= "<a href='{$baseUrl}family#family[weddingdate]' $styleString>$text</a>";
					$weddingDate .= "</td>";
				$weddingDate .= "</tr>";
			}

			$message .= "<a href='{$baseUrl}family' $styleString><b>Family details</b></a><br>";
			$message .= "<table>";
				$message .= "<tr>";
					$message .= "<td>";
						$message .= "Family picture:";
					$message .= "</td>";
					$message .= "<td>";
						$message .= "<a href='{$baseUrl}family#family[picture]' $styleString>$picture</a>";
					$message .= "</td>";
				$message .= "</tr>";

				$message .= "<tr>";
					$message .= "<td>";
						$message .= "Spouse:";
					$message .= "</td>";
					$message .= "<td>";
						$message .= "<a href='{$baseUrl}family#family[partner]' $styleString>$partner</a>";
					$message .= "</td>";
				$message .= "</tr>";

				$message .= $weddingDate;

				foreach($family['children'] as $key=>$child){
					$nr=$key+1;
					$message .= "<tr>";
						$message .= "<td>";
							$message .= "Child $nr:";
						$message .= "</td>";
						$message .= "<td>";
							$message .= "<a href='{$baseUrl}family#family[children][$key]' $styleString>".get_userdata($child)->display_name."</a>";
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

/**
 * Notifies people that their account is about to expire or has been deleted
 */
function accountExpiryCheck(){
	require_once(ABSPATH.'wp-admin/includes/user.php');
	
	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);

	$personnelCoordinatorEmail	= SIM\getModuleOption(MODULE_SLUG, 'personnel_email');
	$staEmail					= SIM\getModuleOption(MODULE_SLUG, 'sta_email');
	
	//Get the users who will expire in 1 month
	$users = get_users(
		array(
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' 		=> 'account_validity',
					'compare' 	=> 'EXISTS'
				),
				array(
					'key' 		=> 'account_validity',
					'value' 	=> 'unlimited',
					'compare' 	=> '!='
				),
				array(
					'key' 		=> 'account_validity',
					'value' 	=> date("Y-m-d", strtotime(" +1 months")),
					'compare' 	=> '=',
					'type' 		=> 'DATE'
				),
				
			),
		)
	);
	
	foreach($users as $user){
		//Send e-mail
		$accountExpiryMail    = new AccountExpiryMail($user);
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
		SIM\trySendSignal("Hi ".$user->first_name.",\nThis is just a reminder that your account on ".SITEURLWITHOUTSCHEME." will be deleted on ".date("d F Y", strtotime(" +1 months")),$user->ID);
	}
	
	//Get the users who are expired
	$expiredUsers = get_users(
		array(
			'meta_query'	=> array(
				'relation' 		=> 'AND',
				array(
					'key' 		=> 'account_validity',
					'compare' 	=> 'EXISTS'
				),
				array(
					'key' 		=> 'account_validity',
					'value' 	=> 'unlimited',
					'compare'	=> '!='
				),
				array(
					'key'		=> 'account_validity',
					'value' 	=> date("Y-m-d"),
					'compare' 	=> '<=',
					'type' 		=> 'DATE'
				),
				
			),
		)
	);
	
	foreach($expiredUsers as $user){
		//Send Signal message
		SIM\trySendSignal(
			"Hi ".$user->first_name.",\nYour account is expired, as you are no longer in country.",
			$user->ID
		);
		
		//Delete the account
		SIM\printArray("Deleting user with id $user->ID and name $user->display_name as it was a temporary account.");
		wp_delete_user($user->ID);
	}
}

/**
 * send reminders about annual review
 */
function reviewReminders(){
	$genericDocuments = get_option('personnel_documents');
	if(is_array($genericDocuments) && !empty($genericDocuments['Annual review form'])){
		$personnelCoordinatorEmail	= SIM\getModuleOption(MODULE_SLUG, 'personnel_email');
		//Change the user to the adminaccount otherwise get_users will not work
		wp_set_current_user(1);
		
		//Retrieve all users
		$users = SIM\getUserAccounts();
		
		//loop over the users
		foreach($users as $user){
			//Check for upcoming reviews, but only if not set to be hidden for this year
			if(get_user_meta($user->ID, 'hide_annual_review', true) != date('Y')){
				$personnelInfo	= get_user_meta($user->ID, "personnel", true);
				$arrivalDate	= get_user_meta($user->ID, 'arrival_date', true);
				//Only do when not arriving this year
				if(is_array($personnelInfo) && !empty($personnelInfo['review_date']) && strpos($arrivalDate, date('Y')) === false){
					$reviewDate	= date('m', strtotime($personnelInfo['review_date']));
					//Start sending the warning 1 month before until it is done.
					if(($reviewDate - 2) < date('m')){
						//Send e-mail
						$to = $user->user_email;
						//Skip if not valid email
						if(strpos($to,'.empty') !== false){
							continue;
						}
						
						$subject 	 = "Please fill in the annual review questionary.";
						$message 	 = 'Hi '.$user->first_name.',<br><br>';

						//Send Signal message
						SIM\trySendSignal(
							"Hi ".$user->first_name.",\n\nIt is time for your annual review.\nPlease fill in the annual review questionary:\n\n".SITEURL.'/'.$genericDocuments['Annual review form']."\n\nThen send it to $personnelCoordinatorEmail",
							$user->ID
						);
						
						//Send e-mail
						$message 	.= 'It is time for your annual review.<br>';
						$message 	.= 'Please fill in the <a href="'.SITEURL.'/'.$genericDocuments['Annual review form'].'">review questionaire</a> to prepare for the talk.<br>';
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

/**
 * Send reminder to people to login
 */
function checkLastLoginDate(){
	wp_set_current_user(1);

	$users = SIM\getUserAccounts();
	foreach($users as $user){
		$lastLogin				= get_user_meta( $user->ID, 'last_login_date',true);

		//user has never logged in
		if(empty($lastLogin)){
			//Send e-mail
			$to = $user->user_email;
			
			//Skip if not valid email
			if(strpos($to,'.empty') !== false){
				continue;
			}

			$key		 = get_password_reset_key($user);
			if(is_wp_error($key)){
				return $key;
			}

			$pageUrl	 = get_permalink(SIM\getModuleOption('login', 'password_reset_page')[0]);
			$url		 = "$pageUrl?key=$key&login=$user->user_login";

			$mail = new AccountCreatedMail($user, $url);
			$mail->filterMail();

			wp_mail( $to, $mail->subject, $mail->message);
		}else{
			$lastLoginDate			= date_create($lastLogin);
			$now 					= new \DateTime();
			$yearsSinceLastLogin 	= date_diff($lastLoginDate, $now)->format("%y");
			
			//User has not logged in in the last year
			if($yearsSinceLastLogin > 0){
				//Send e-mail
				$to = $user->user_email;
				//Skip if not valid email
				if(strpos($to, '.empty') !== false){
					continue;
				}

				//Send Signal message
				SIM\trySendSignal(
					"Hi $user->first_name,\n\nWe miss you! We haven't seen you since $lastLogin\n\nPlease pay us a visit on\n".SITEURL,
					$user->ID
				);
				
				//Send e-mail
				$weMissYouMail    = new WeMissYouMail($user, $lastLogin);
				$weMissYouMail->filterMail();
									
				wp_mail( $to, $weMissYouMail->subject, $weMissYouMail->message);
			}
		}
	}
	
}

// Remove scheduled tasks upon module deactivatio
add_action('sim_module_deactivated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

	wp_clear_scheduled_hook( 'birthday_check_action' );
	wp_clear_scheduled_hook( 'account_expiry_check_action' );
	wp_clear_scheduled_hook( 'vaccination_reminder_action' );
	wp_clear_scheduled_hook( 'greencard_reminder_action' );
	wp_clear_scheduled_hook( 'check_details_mail_action' );
	wp_clear_scheduled_hook( 'review_reminders_action' );
	wp_clear_scheduled_hook( 'check_last_login_date_action' );
});