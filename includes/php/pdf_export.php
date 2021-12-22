<?php
namespace SIM;

/*
* Plugin Name: Atomic Smash FPDF Tutorial
* Description: A plugin created to demonstrate how to build a PDF document from WordPress posts.
* Version: 1.0
* Author: Anthony Hartnell
* Author URI: https://www.atomicsmash.co.uk/blog/author/anthony/
https://www.atomicsmash.co.uk/blog/create-pdf-documents-wordpress-fpdf/
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function export_visa_info_pdf($user_id=0, $all=false) {
	global $PDF_Logo_path;
	$title_line_height = 10;
	
	if($all == true){
		//Build the frontpage
		$pdf = new \PDF_HTML();
		$pdf->frontpage("Visa user info","");
		
		//Get all adult missionaries
		$users = get_missionary_accounts();
		foreach($users as $user){
			$pdf->setHeaderTitle('Greencard information for '.$user->display_name);
			$pdf->PageTitle($pdf->headertitle);
			write_visa_pages($user->ID, $pdf);
		}
	}else{
		//Build the frontpage
		$pdf = new \PDF_HTML();
		$pdf->frontpage("Visa user info for:",get_userdata($user_id)->display_name);
		write_visa_pages($user_id, $pdf);
	}
	
	$pdf->printpdf();
}

function write_visa_pages($user_id, $pdf){
	$display_name = get_userdata( $user_id )->display_name;
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
	$understudiesarray = $visa_info['understudies'];
	//Visa info without understudies
	unset($visa_info['understudies']);
	
	$understudies_documents = [];
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

	if(is_array($understudiesarray) and count($understudiesarray)>0){
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
/* 
function medicalData($user_id){
	$medical_user_info = get_user_meta( $user_id, "medical_info",true);
	if(!is_array($medical_user_info)){
		$medical_user_info = [];
	}
	$html = "";
	$pdf->WriteArray($medical_user_info);
	return $html;
} */

function genericData($user_id){
	global $Ministries;
	global $GenericUserInfoFields;
	
	$user_ministries 	= (array)get_user_meta( $user_id, "user_ministries", true);
			
	$html = "<strong>Works at: </strong><br>";
	foreach ($Ministries as $ministry) {
		$ministry_name = str_replace(" ","_",$ministry);
		if (isset($user_ministries[$ministry_name])){
			$html .= "* $ministry_name<br>";
		}
	}
	$html .= "<br>";
	
	foreach($GenericUserInfoFields as $field){
		$field_value = get_user_meta( $user_id, $field, true );
		if(is_array($field_value)){
			$html .= "<strong>".ucfirst($field).":</strong><br>";
			foreach($field_value as $value){
				$html .= "* $value<br>";
			}
		}else{
			$html .= "<strong>".ucfirst($field).":</strong> ".$field_value."<br>";
		}
	}
	return $html;
}

//Export data in an PDF
function create_contactlist_pdf($header, $data) {
	// Column headings
	$widths = array(30, 45, 28, 47,45);
	
	//Built frontpage
	$pdf = new \PDF_HTML();
	$pdf->frontpage('Missionary Contact List',date('F'));
	
	//Write the table headers
	$pdf->table_headers($header,$widths);
	
    // Data
    $fill = false;
	$height = 0;
	$total_height = 0;
	//Loop over all the rows
    foreach($data as $row){
		$pdf->WriteTableRow($widths, $row, $fill,$header);		
        $fill = !$fill;
    }
    // Closing line
    $pdf->Cell(array_sum($widths),0,'','T');
	
	$contact_list = get_temp_dir()."/Missionary contactlist - ".date('F').".pdf";
	$pdf->Output( $contact_list , "F");
    return $contact_list; 
}

function create_page_pdf(){
	global $post;
	
	$pdf = new \PDF_HTML();
	$pdf->SetFont('Arial','B',15);
	
	$pdf->skipfirstpage = false;
	
	//Set the title of the document
	$pdf->SetTitle($post->post_title);
	$pdf->AddPage();

	//If recipe
	if($post->post_type == 'recipe'){
		$pdf->print_image(get_the_post_thumbnail_url($post),-1,20,-1,-1,true,true);
		
		//Duration
		$url = plugins_url().'/custom-simnigeria-plugin/includes/pictures/time.png';
		$pdf->print_image($url,10,-1,10,10);
		$pdf->write(10,get_post_meta($post->ID,'time_needed',true).' minutes');
		
		//Serves
		$url = plugins_url().'/custom-simnigeria-plugin/includes/pictures/recipe_serves.png';
		$pdf->print_image($url,55,-1,10,10);
		
		$persons = get_post_meta(get_the_ID(),'serves',true);
		if($persons == 1){
			$person_text = 'person';
		}else{
			$person_text = 'people';
		}
		
		$pdf->write(10,"$persons $person_text");
		
		$pdf->Ln(15);
		$pdf->writeHTML('<b>Ingredients:</b>');
		$pdf->Ln(5);
		$ingredients = explode("\n", trim(get_post_meta(get_the_ID(),'ingredients',true)));
		foreach($ingredients as $ingredient){
			$pdf->write(10,chr(127).' '.$ingredient);
			$pdf->Ln(5);
		}
		
		$pdf->Ln(10);
		$pdf->writeHTML('<b>Instructions:</b>');
	}
	
	$pdf->WriteHTML($post->post_content);
	
	$pdf->printpdf();
}