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

	$freq	= SIM\getModuleOption(MODULE_SLUG, 'contactlist_freq');
	if($freq){
		SIM\scheduleTask('send_missonary_detail_action', $freq);
	}
}

function sendReimbursementRequests(){
	//Change the user to the adminaccount otherwise get_users will not work
	wp_set_current_user(1);
	
	//Export the excel file to temp
	$formTable = new SIM\FORMS\EditFormResults(['id'=>'6','formname'=>'reimbursement']);

	//make sure we have permission on the data
	$formTable->tableEditPermissions = true;

	//fill the excel data
    //$formTable->determineForm(['id'=>'6','formname'=>'reimbursement']);
	$formTable->showFormresultsTable();

	//Get all files in the reimbursement dir as they are the receipts
	$recieptsDir	= "/form_uploads/Reimbursement";
	$dir			= wp_upload_dir()['basedir'].$recieptsDir;
	$oldDir			= $dir.'/old';
	$privateDir		= wp_upload_dir()['basedir'].'/private'.$recieptsDir;
	$attachments	= glob("$dir/*.*");
	$oldAttachments	= glob("$oldDir/*.*");

	// Check if we need to make a folder
	if (!is_dir($oldDir)) {
		mkdir($oldDir, 0777, true);
	}

	if (!is_dir($privateDir)) {
		mkdir($privateDir, 0777, true);
	}

	// Move all the files in the old dir to the private dir
	// They belong to last months request
	foreach($oldAttachments as $file){
		//Remove the upload attached to the form
		rename($file, str_replace($oldDir, $privateDir, $file));
	}

	//if there are reimbursements
	if(empty($formTable->submissions )){
		SIM\printArray('No reimbursement requests found');
	}else{
		//Create the excel
		$excel	= $formTable->exportExcel("Reimbursement requests - ".date("F Y", strtotime("previous month")).'.xlsx', false);

		//mark all entries as archived
		foreach($formTable->submissions as &$formTable->submission){
			maybe_unserialize($formTable->submission->formresults);
			$formTable->submissionId				= $formTable->submission->id;

			// update the reciept url
			if(isset($formTable->submission->formresults['receipts'])){
				foreach($formTable->submission->formresults['receipts'] as &$receipt){
					$receipt	= str_replace($recieptsDir, '/private'.$recieptsDir, $receipt);
				}
			}
			$formTable->updateSubmission(true);
		}

		//If there are any attachements
		if(!empty($attachments)){
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
			$emailHeaders	 = ["Bcc:enharmsen@gmail.com"];
			
			//Send the mail
			$to				= SIM\getModuleOption('mailposting', 'finance_email');

			$attach	= array_merge($attachments, [$excel]);
			wp_mail("jos.treasurer@sim.org", $subject, $message, $emailHeaders, $attach);

			//Loop over the attachements and move them
			foreach($attachments as $file){
				//Remove the upload attached to the form
				rename($file, str_replace($dir, $oldDir, $file));
			}
		}
	}
}

//Send contact info
function sendMissonaryDetail(){
	global $wpdb;

	$attachments	= [SIM\USERPAGE\buildUserDetailPdf()];
	
	//Send e-mail
	$contactList    = new ContactList('');
	$contactList->filterMail();

	$emailHeaders	= [];
	$emails			= array_keys($wpdb->get_results("SELECT `user_email` FROM $wpdb->users WHERE `user_email` NOT LIKE '%.empty'", OBJECT_K));
	foreach($emails as $email){
		$emailHeaders[]	= "bcc:$email";
	}

	wp_mail( get_option( 'admin_email' ), $contactList->subject, $contactList->message, $emailHeaders, $attachments);
}

// Remove scheduled tasks upon module deactivatio
add_action('sim_module_deactivated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{return;}

	wp_clear_scheduled_hook( 'send_reimbursement_requests_action' );
	wp_clear_scheduled_hook( 'send_missonary_detail_action' );
});