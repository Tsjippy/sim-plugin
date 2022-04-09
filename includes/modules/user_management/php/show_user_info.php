<?php
namespace SIM\USERMANAGEMENT;
use SIM;

/* HELPER FUNCTIONS */
//add special js to the dynamic form js
add_filter('form_extra_js', function($js, $formname, $minimized){
	$path	= plugin_dir_path( __DIR__)."js/$formname.min.js";
	if(!$minimized or !file_exists($path)){
		$path	= plugin_dir_path( __DIR__)."js/$formname.js";
	}

	if(file_exists($path)){
		$js		= file_get_contents($path);
	}

	return $js;
}, 10, 3);

function export_visa_info_pdf($user_id=0, $all=false) {
	if($all == true){
		//Build the frontpage
		$pdf = new SIM\PDF\PDF_HTML();
		$pdf->frontpage("Visa user info","");
		
		//Get all adult missionaries
		$users = SIM\get_user_accounts();
		foreach($users as $user){
			$pdf->setHeaderTitle('Greencard information for '.$user->display_name);
			$pdf->PageTitle($pdf->headertitle);
			write_visa_pages($user->ID, $pdf);
		}
	}else{
		//Build the frontpage
		$pdf = new SIM\PDF\PDF_HTML();
		$pdf->frontpage("Visa user info for:",get_userdata($user_id)->display_name);
		write_visa_pages($user_id, $pdf);
	}
	
	$pdf->printpdf();
}

function write_visa_pages($user_id, $pdf){
	$visa_info = get_user_meta( $user_id, "visa_info",true);
	if(!is_array($visa_info)){
		$pdf->Write(10,"No greencard information found.");
		return;
	}
	
	// Post Content	
	$pdf->SetFont( 'Arial', '', 12 );
	
	$qualificationsarray = $visa_info['qualifications'];
	//Visa info without qualifications
	unset($visa_info['qualifications']);
	
	//Understudy info	
	$understudies_documents = [];

	$understudiesarray	= [];
	$info_1				= get_user_meta( $user_id, "understudy_1",true);
	$info_2				= get_user_meta( $user_id, "understudy_2",true);
	if(!empty($info_1)) $understudiesarray[1]	= $info_1;
	if(!empty($info_2)) $understudiesarray[2]	= $info_2;

	foreach($understudiesarray as $key=>$understudy){
		$understudies_documents[$key] = $understudy['documents'];
		unset($understudiesarray[$key]['documents']);
	}
	
	if(count($visa_info)>0){
		$pdf->WriteArray($visa_info);
	}else{
		$pdf->Write(10,'No greencard details found');
		$pdf->Ln(10);
	}

	if(count($understudiesarray)>0){
		$pdf->PageTitle('Understudy information');
		$pdf->WriteArray($understudiesarray);
	}else{
		$pdf->Write(10,'No understudy information found');
		$pdf->Ln(10);
	}
	
	if(is_array($qualificationsarray) and count($qualificationsarray)>0){
		$pdf->PageTitle('Qualifications');
		$pdf->WriteImageArray($qualificationsarray);
	}else{
		$pdf->Write(10,'No qualifications found');
		$pdf->Ln(10);
	}
	
	if(is_array($understudies_documents) and count($understudies_documents)>0){
		$pdf->AddPage();
		
		foreach($understudies_documents as $key=>$understudies_document){
			if(count($understudies_document)>0){
				if($key>1) $pdf->Ln(10);
				$pdf->PageTitle('Understudy documents for understudy '.$key,false);
				$pdf->WriteImageArray($understudies_document);
			}else{
				$pdf->Write(10,'No understudy documents found for understudy '.$key);
				$pdf->Ln(10);
			}
		}
	}else{
		$pdf->Write(10,'No understudy documents found');
		$pdf->Ln(10);
	}
	
	return $pdf;
}

add_action ( 'wp_ajax_extend_validity', function(){
	//print_array($_POST,true);
	if(isset($_POST['new_expiry_date']) and isset($_POST['userid']) and is_numeric($_POST['userid'])){
		SIM\verify_nonce('extend_validity_reset_nonce');
		
		$user_id = $_POST['userid'];
		if(isset($_POST['unlimited']) and $_POST['unlimited'] == 'unlimited'){
			$date = 'unlimited';
			echo "Marked the useraccount for ".get_userdata($user_id)->first_name." to never expire.";
		}else{
			$date = sanitize_text_field($_POST['new_expiry_date']);
			echo "Extended valitidy for ".get_userdata($user_id)->first_name." till $date";
		}
		update_user_meta( $user_id, 'account_validity',$date);
	}
	wp_die();
	
});