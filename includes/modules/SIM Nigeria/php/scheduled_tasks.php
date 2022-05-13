<?php
namespace SIM\SIMNIGERIA;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'send_reimbursement_requests_action', __NAMESPACE__.'\send_reimbursement_requests' );
	add_action( 'send_missonary_detail_action', __NAMESPACE__.'\send_missonary_detail' );
});

function schedule_tasks(){
    SIM\schedule_task('send_reimbursement_requests_action', 'monthly');

	$freq	= SIM\get_module_option('SIM Nigeria', 'freq');
	if($freq) SIM\schedule_task('send_missonary_detail_action', $freq);
}

function send_reimbursement_requests(){
	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);
	
	//Export the excel file to temp
	$formtable = new SIM\FORMS\FormTable();

	//make sure we have permission on the data
	$formtable->table_edit_permissions = true;

	//fill the excel data
	$formtable->show_formresults_table(['id'=>'6','datatype'=>'reimbursement']);

	//if there are reimbursements
	if(empty($formtable->submission_data )){
		SIM\print_array('No reimbursement requests found');
	}else{
		//Get all files in the reimbursement dir as they are the receipts
		$recieptsDir	= wp_upload_dir()['path']."/form_uploads/Reimbursement";
		$attachments	= glob("$recieptsDir/*.*");

		//Create the excel
		$excel	= $formtable->export_excel("Reimbursement requests - ".date("F Y", strtotime("previous month")).'.xlsx',false);

		//mark all entries as archived
		foreach($formtable->submission_data as $id=>$sub_data){
			$formtable->formresults		= maybe_unserialize($sub_data->formresults);
			$formtable->submission_id	= $sub_data->id;
			$formtable->update_submission_data(true);
		}

		//If there are any attachements
		if(!empty($attachments)){
			// Check if we need to make a folder
			if (!is_dir("$recieptsDir/old")) {
                mkdir("$recieptsDir/old", 0777, true);
            }

			//Send e-mail
			$subject		 = "Reimbursements for ".date("F Y", strtotime("previous month"));
			$message		 = 'Dear finance team,<br><br>';
			$message		.= 'Attached you can find all reimbursement requests of this month<br>';
			$message		.= 'Please use the links below to get the reciepts:<br>';

			// Add attachments as urls so to not exceed the appendix limit of outlook
			foreach($attachments as $attachment){
				$message		.= SIM\path_to_url(str_replace($recieptsDir, "$recieptsDir/old", $attachment));
				$message		.= '<br><br>';
			}
			$email_headers	 = ["Bcc:enharmsen@gmail.com"];
			
			//Send the mail
			$to				= SIM\get_module_option('mail_posting', 'finance_email');
			wp_mail($to, $subject, $message, $email_headers, $excel);

			//Loop over the attachements and delete them from the server
			foreach($attachments as $attachment){
				//Remove the upload attached to the form
				rename($attachment, str_replace($recieptsDir, "$recieptsDir/old", $attachment));
			}
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
	$users = SIM\get_user_accounts($return_family=false,$adults=true,$fields=[],$extra_args=$args);
	
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
	$contactList    = new ContactList($user);
	$contactList->filterMail();
						
	wp_mail( get_option( 'admin_email' ), $contactList->subject, $contactList->message, $email_headers, $attachments);
}

//Export data in an PDF
function create_contactlist_pdf($header, $data) {
	// Column headings
	$widths = array(30, 45, 28, 47,45);
	
	//Built frontpage
	$pdf = new SIM\PDF\PDF_HTML();
	$pdf->frontpage(SITENAME.' Contact List',date('F'));
	
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
	
	$contact_list = get_temp_dir().SITENAME." Contactlist - ".date('F').".pdf";
	$pdf->Output( $contact_list , "F");
    return $contact_list; 
}

// Remove scheduled tasks upon module deactivatio
add_action('sim_module_deactivated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != basename(dirname(dirname(__FILE__))))	return;

	wp_clear_scheduled_hook( 'send_reimbursement_requests_action' );
	wp_clear_scheduled_hook( 'send_missonary_detail_action' );
}, 10, 2);