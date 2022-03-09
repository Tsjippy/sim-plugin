<?php
namespace SIM\SIMNIGERIA;
use SIM;

add_action('init', function(){
	//add action for use in scheduled task
	add_action( 'send_reimbursement_requests_action', 'SIM\SIMNIGERIA\send_reimbursement_requests' );
});

function schedule_tasks(){
    SIM\schedule_task('send_reimbursement_requests_action', 'monthly');
}

function send_reimbursement_requests(){
	SIM\print_array('Sending reimbursement requests');

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
			$message .= 'Kind regards,<br><br>';
			$email_headers = ["Bcc:enharmsen@gmail.com"];
			
			//Send the mail
			wp_mail(SIM\get_module_option('mail_posting', 'finance_email') , $subject, $message,$email_headers, $attachments);

			//Loop over the attachements and delete them from the server
			foreach($attachments as $attachment){
				//Remove the upload attached to the form
				unlink($attachment);
			}
		}
	}
}