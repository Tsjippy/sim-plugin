<?php
namespace SIM;

//Shortcode for testing
add_shortcode("test",function ($atts){
	$formTable = new FORMS\EditFormResults();
	$formTable->processAtts(['id'=>'6','formname'=>'reimbursement', 'archived'=>'true']);
	$formTable->showFormresultsTable();

	$recieptsDir	= "/form_uploads/Reimbursement";
	$dir			= wp_upload_dir()['basedir'].$recieptsDir;
	$oldDir			= $dir.'/old';
	$privateDir		= wp_upload_dir()['basedir'].'/private'.$recieptsDir;

	if (!is_dir($privateDir)) {
		mkdir($privateDir, 0777, true);
	}

	foreach($formTable->submissionData as $data){
		$formTable->formResults		= maybe_unserialize($data->formresults);
		$formTable->submissionId	= $data->id;

		// update the reciept url
		if(isset($formTable->formResults['receipts'])){
			foreach($formTable->formResults['receipts'] as &$receipt){
				$receipt	= str_replace($recieptsDir, '/private'.$recieptsDir, $receipt);
			}
		}
		$formTable->updateSubmissionData(true);
	}
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );