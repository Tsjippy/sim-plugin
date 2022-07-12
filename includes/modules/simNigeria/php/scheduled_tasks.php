<?php
namespace SIM\SIMNIGERIA;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'send_reimbursement_requests_action', __NAMESPACE__.'\sendReimbursementRequests' );
	add_action( 'send_missonary_detail_action', __NAMESPACE__.'\sendMissonaryDetail' );
});

function scheduleTasks(){
    SIM\scheduleTask('send_reimbursement_requests_action', 'monthly');

	$freq	= SIM\getModuleOption(MODULE_SLUG, 'freq');
	if($freq){
		SIM\scheduleTask('send_missonary_detail_action', $freq);
	}
}

function sendReimbursementRequests(){
	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);
	
	//Export the excel file to temp
	$formTable = new SIM\FORMS\EditFormResults();

	//make sure we have permission on the data
	$formTable->tableEditPermissions = true;

	//fill the excel data
	
    $formTable->processAtts(['id'=>'6','formname'=>'reimbursement']);
	$formTable->showFormresultsTable();

	//if there are reimbursements
	if(empty($formTable->submissionData )){
		SIM\printArray('No reimbursement requests found');
	}else{
		//Get all files in the reimbursement dir as they are the receipts
		$recieptsDir	= wp_upload_dir()['path']."/form_uploads/Reimbursement";
		$attachments	= glob("$recieptsDir/*.*");

		//Create the excel
		$excel	= $formTable->exportExcel("Reimbursement requests - ".date("F Y", strtotime("previous month")).'.xlsx',false);

		//mark all entries as archived
		foreach($formTable->submissionData as $sub_data){
			$formTable->formResults		= maybe_unserialize($sub_data->formresults);
			$formTable->submissionId	= $sub_data->id;
			$formTable->updateSubmissionData(true);
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
				$message		.= SIM\pathToUrl(str_replace($recieptsDir, "$recieptsDir/old", $attachment));
				$message		.= '<br><br>';
			}
			$email_headers	 = ["Bcc:enharmsen@gmail.com"];
			
			//Send the mail
			$to				= SIM\getModuleOption('mailposting', 'finance_email');
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
function sendMissonaryDetail(){
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
	$users = SIM\getUserAccounts(false, true, [], $args);
	
	//Loop over all users to add a row with their data to the table
	$userDetails 	= [];
	$emailHeaders 	= array('Content-Type: text/html; charset=UTF-8');

	foreach($users as $user){
		//skip admin
		if ($user->ID != 1 && $user->display_name != 'Signal Bot'){
			$privacyPreference = get_user_meta( $user->ID, 'privacy_preference', true );
			if(!is_array($privacyPreference)){
				$privacyPreference = [];
			}
			
			$name		= $user->display_name; //Real name
			$nickname	= get_user_meta($user->ID, 'nickname', true); //persons name in case of a office account
			if($name != $nickname && $nickname != ''){
				$name .= "\n ($nickname)";
			}
			
			$email	= $user->user_email;
			
			//Add to recipients
			if (strpos($user->user_email,'.empty') === false){
				$emailheaders[] = "Bcc:".$email;
			}else{
				$email	= '';
			}
			
			$phonenumbers = "";
			if(empty($privacyPreference['hide_phone'])){
				$userPhonenumbers = (array)get_user_meta ( $user->ID,"phonenumbers",true);
				foreach($userPhonenumbers as $key=>$phonenumber){
					if ($key > 0){
						$phonenumbers .= "\n";
					}
					$phonenumbers .= $phonenumber;
				}
			}
			
			$ministries = "";
			if(empty($privacyPreference['hide_ministry'])){
				$userMinistries = (array)get_user_meta( $user->ID, "user_ministries", true);
				$i = 0;
				foreach ($userMinistries as $key=>$userMinistry) {
					if ($i > 0){
						$ministries .= "\n";
					}
					$ministries  .= str_replace("_"," ",$key);
					$i++;
				}
			}
			
			$compound = "";
			if(empty($privacyPreference['hide_location'])){
				$location = (array)get_user_meta( $user->ID, 'location', true );
				if(isset($location['compound'])){
					$compound = $location['compound'];
				}
			}
			$userDetails[] 	= [$name, $email, $phonenumbers, $ministries, $compound];
		}
	}
	
	//Headers of the table
	$tableHeaders = ["Name"," E-mail"," Phone"," Ministries"," State"];
	//Create a pdf and add it to the mail
	$attachments = array( createContactlistPdf($tableHeaders, $userDetails));
	
	//Send e-mail
	$contactList    = new ContactList($user);
	$contactList->filterMail();
						
	wp_mail( get_option( 'admin_email' ), $contactList->subject, $contactList->message, $emailHeaders, $attachments);
}

//Export data in an PDF
function createContactlistPdf($header, $data) {
	// Column headings
	$widths = array(30, 45, 28, 47,45);
	
	//Built frontpage
	$pdf = new SIM\PDF\PdfHtml();
	$pdf->frontpage(SITENAME.' Contact List',date('F'));
	
	//Write the table headers
	$pdf->tableHeaders($header, $widths);
	
    // Data
    $fill = false;
	//Loop over all the rows
    foreach($data as $row){
		$pdf->writeTableRow($widths, $row, $fill,$header);		
        $fill = !$fill;
    }
    // Closing line
    $pdf->Cell(array_sum($widths),0,'','T');
	
	$contactList = get_temp_dir().SITENAME." Contactlist - ".date('F').".pdf";
	$pdf->Output( $contactList , "F");
    return $contactList; 
}

// Remove scheduled tasks upon module deactivatio
add_action('sim_module_deactivated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

	wp_clear_scheduled_hook( 'send_reimbursement_requests_action' );
	wp_clear_scheduled_hook( 'send_missonary_detail_action' );
});