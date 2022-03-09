<?php
namespace SIM;

add_action('admin_init', function() {
    if ( is_admin() && get_option( 'Activated_Plugin' ) == 'SIM' ) {
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
		schedule_task($func['hookname'].'_action', $func['recurrence']);
	}
}

function schedule_task($taskname, $recurrence){
	//Not yet activated
	if (! wp_next_scheduled($taskname)) {
		switch ($recurrence) {
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
		if(wp_schedule_event( $time, $recurrence, $taskname )){
			print_array("Succesfully scheduled $taskname to run $recurrence");
		}else{
			print_array("Scheduling of $taskname unsuccesfull");
		}
	}
}

function get_child_title($user_id){
	$gender = get_user_meta( $user_id, 'gender', true );
	if($gender == 'male'){
		$title = "son";
	}elseif($gender == 'female'){
		$title = "daughter";
	}else{
		$title = "child";
	}
	
	return $title;
}

//Send reminder to people to login
function check_last_login_date(){
	$users = get_user_accounts();
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
			try_send_signal(
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
	$users = get_user_accounts($return_family=false,$adults=true,$local_nigerians=true,$fields=[],$extra_args=$args);
	
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
	$message .= 'Kind regards,<br><br>';
	
	//Send the mail
	wp_mail( get_option( 'admin_email' ), $subject, $message, $email_headers, $attachments);
}

//Export data in an PDF
function create_contactlist_pdf($header, $data) {
	// Column headings
	$widths = array(30, 45, 28, 47,45);
	
	//Built frontpage
	$pdf = new \PDF_HTML();
	$pdf->frontpage('SIM Nigeria Contact List',date('F'));
	
	//Write the table headers
	$pdf->table_headers($header,$widths);
	
    // Data
    $fill = false;
	//Loop over all the rows
    foreach($data as $row){
		$pdf->WriteTableRow($widths, $row, $fill,$header);		
        $fill = !$fill;
    }
    // Closing line
    $pdf->Cell(array_sum($widths),0,'','T');
	
	$contact_list = get_temp_dir()."/SIM Nigeria Contactlist - ".date('F').".pdf";
	$pdf->Output( $contact_list , "F");
    return $contact_list; 
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
