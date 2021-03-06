<?php
namespace SIM\SIMNIGERIA;
use SIM;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

//Multi default values used to prefil the compound dropdown
add_filter( 'sim_add_form_multi_defaults', function($defaultArrayValues, $userId, $formName){
	if($formName != 'user_visa'){
		return $defaultArrayValues;
	}
	
	$defaultArrayValues['quotanames'] 			= QUOTANAMES;
	
	return $defaultArrayValues;
}, 10, 3);

/**
 * Creates the user visa page
 * 
 * @param	bool	$message	Whether to include the message
 * 
 * @return	string				html
 */
function visaPage($message=false){
	ob_start();
	?>
	<div>		
		<h3>Greencard information</h3>
		
		<?php if(!$message){?>
		<p class="message">
			Below you can fill in all information required for your greencard.<br>
			Please add your own diploma copies as well as information for at least two understudies.
		</p>		
		<?php } ?>
		
		<button class="button tablink active" id="show_greencard_info" data-target="greencard_info">Greencard info</button>
		<button class="button tablink" id="show_understudy_1_info" data-target="understudy_1_info">Understudy 1</button>
		<button class="button tablink" id="show_understudy_2_info" data-target="understudy_2_info">Understudy 2</button>
		
		<div id="greencard_info" class="tabcontent">
			<?php
			echo do_shortcode('[formbuilder formname=user_visa]');
			?>
		</div>
		
		<div id="understudy_1_info" class="tabcontent hidden">
			<?php
			echo do_shortcode('[formbuilder formname=understudy_1]');
			?>
		</div>
		
		<div id="understudy_2_info" class="tabcontent hidden">
			<?php
			echo do_shortcode('[formbuilder formname=understudy_2]');
			?>
		</div>
	</div>
	<?php 

	return ob_get_clean();
}

/**
 * Export visa info to excel
 */
function exportVisaExcel(){
	$filename = "VisaInfo.xlsx";
	
	$spreadsheet = new Spreadsheet();
	//Store the sheet in variable
	$sheet	= $spreadsheet->getActiveSheet();
	
	//Write the column headers
	$sheet->setCellValue('A1', 'Name');
	
	$sheet->setCellValue('B1', 'Greencard');
	$sheet->mergeCells('B1:F1');
	
	$sheet->setCellValue('H1', 'Understudy 1');
	$sheet->mergeCells('H1:R1');
	
	$sheet->setCellValue('T1', 'Understudy 2');
	$sheet->mergeCells('T1:AD1');
	
	//set first two rows bold
	$sheet->getStyle('A1:AD2' )->applyFromArray(
		array(
			'font' => array(
				'bold' => true,
			),
			'alignment' => array(
				'horizontal' => Alignment::HORIZONTAL_CENTER,
			)
		)
	);
	
	$greencardData		= ['greencard_expiry','name','nin','quota_position','accompanying'];
	$understudyData		= ['name','email','employing_institution','position','grade_level','salary','phonenumber1','phonenumber2','taxid','nin'];

	foreach($greencardData as $key=>$field){
		$sheet->setCellValueByColumnAndRow($key+2, 2, ucfirst(str_replace('_',' ',$field)));
	}

	foreach($understudyData as $key=>$field){
		$sheet->setCellValueByColumnAndRow($key+8, 2, ucfirst(str_replace('_',' ',$field)));
		$sheet->setCellValueByColumnAndRow($key+19, 2, ucfirst(str_replace('_',' ',$field)));
	}
	
	//Autowidth of colums
	foreach ($sheet->getColumnIterator() as $column) {
		$sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
	}
	
	$row = 3;
	//Get all adult missionaries
	$users = SIM\getUserAccounts();
	foreach($users as $user){
		//skip non-valid users
		if(empty($user->display_name)){
			SIM\printArray("User with id {$user->ID} has not a valid display name");
			continue;
		}
		
		$displayName 	= get_userdata( $user->ID)->display_name;
		$visaInfo 		= get_user_meta( $user->ID, "visa_info",true);
		$understudy1	= get_user_meta( $user->ID, "understudy_1",true);
		$understudy2	= get_user_meta( $user->ID, "understudy_2",true);
		
		$sheet->setCellValueByColumnAndRow(1, $row, $displayName);
		
		//Skip if there is no data
		if(!is_array($visaInfo) || empty($visaInfo)){
			$sheet->setCellValueByColumnAndRow(2, $row, 'No data');
			continue;
		}
		
		//Loop over the greencard values and write them to excel
		foreach($greencardData as $key=>$field){
			$sheet->setCellValueByColumnAndRow($key+2, $row, $visaInfo[$field]);
		}
		
		//Loop over the understudy values and write them to excel
		foreach($understudyData as $key=>$field){
			$sheet->setCellValueByColumnAndRow($key+8 , $row, $understudy1[$field]);
			$sheet->setCellValueByColumnAndRow($key+19, $row, $understudy2[$field]);
		}

		$row++;
	}
	
	//Now write it all to an excel file
	$writer = new Xlsx($spreadsheet);
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header("Content-Disposition: attachment; filename=$filename");
	SIM\clearOutput();
	ob_start();
	$writer->save('php://output');
	ob_end_flush();
	exit;
}

/**
 * Exports the visa info to pdf
 * 
 * @param	int		$userId		WP_user id
 * @param	bool	$all		Whether to export all data or only for the give userId
 * 
 */
function exportVisaInfoPdf($userId=0, $all=false) {
	if($all){
		//Build the frontpage
		$pdf = new SIM\PDF\PdfHtml();
		$pdf->frontpage("Visa user info", "");
		
		//Get all adult missionaries
		$users = SIM\getUserAccounts();
		foreach($users as $user){
			$pdf->setHeaderTitle('Greencard information for '.$user->display_name);
			$pdf->PageTitle($pdf->headertitle);
			writeVisaPages($user->ID, $pdf);
		}
	}else{
		//Build the frontpage
		$pdf = new SIM\PDF\PdfHtml();
		$pdf->frontpage("Visa user info for:", get_userdata($userId)->display_name);
		writeVisaPages($userId, $pdf);
	}
	
	$pdf->printPdf();
}

/**
 * write visa page to pdf
 * 
 * @param	int		$userId		WP_User id
 * @param	object	$pdf		PDF instance
 */
function writeVisaPages($userId, $pdf){
	$visaInfo = get_user_meta( $userId, "visa_info", true);

	if(!is_array($visaInfo)){
		$pdf->Write(10,"No greencard information found.");
		return $pdf;
	}
	
	// Post Content	
	$pdf->SetFont( 'Arial', '', 12 );
	
	$qualificationsArray = $visaInfo['qualifications'];
	//Visa info without qualifications
	unset($visaInfo['qualifications']);
	
	//Understudy info	
	$understudiesDocuments = [];

	$understudiesArray	= [];
	$info1				= get_user_meta( $userId, "understudy_1",true);
	$info2				= get_user_meta( $userId, "understudy_2",true);
	if(!empty($info1)){
		$understudiesArray[1]	= $info1;
	}
	if(!empty($info2)){
		$understudiesArray[2]	= $info2;
	}

	foreach($understudiesArray as $key=>$understudy){
		$understudiesDocuments[$key] = $understudy['documents'];
		unset($understudiesArray[$key]['documents']);
	}
	
	if(!empty($visaInfo)){
		$pdf->WriteArray($visaInfo);
	}else{
		$pdf->Write(10,'No greencard details found');
		$pdf->Ln(10);
	}

	if(!empty($understudiesArray)){
		$pdf->PageTitle('Understudy information');
		$pdf->WriteArray($understudiesArray);
	}else{
		$pdf->Write(10,'No understudy information found');
		$pdf->Ln(10);
	}
	
	if(is_array($qualificationsArray) && count($qualificationsArray)>0){
		$pdf->PageTitle('Qualifications');
		$pdf->WriteImageArray($qualificationsArray);
	}else{
		$pdf->Write(10,'No qualifications found');
		$pdf->Ln(10);
	}
	
	if(is_array($understudiesDocuments) && !empty($understudiesDocuments)){
		$pdf->AddPage();
		
		foreach($understudiesDocuments as $key=>$understudiesDocument){
			if(count($understudiesDocument)>0){
				if($key>1){
					$pdf->Ln(10);
				}
				$pdf->PageTitle('Understudy documents for understudy '.$key,false);
				$pdf->WriteImageArray($understudiesDocument);
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


// allow people with the visainfo role to see the user select
add_filter('sim_user_page_dropdown', function($genericInfoRoles){
	$genericInfoRoles[]	= "visainfo";
	return $genericInfoRoles;
});

/*
	Add a visa Info page to user management screen
*/
add_filter('sim_user_info_page', function($filteredHtml, $showCurrentUserData, $user, $userAge){
	if((!array_intersect(["visainfo"], $user->roles ) && !$showCurrentUserData) || $userAge < 18){
		return $filteredHtml;
	}

	if( isset($_POST['print_visa_info'])){
		if(isset($_POST['userid']) && is_numeric($_POST['userid'])){
			exportVisaInfoPdf($_POST['userid']);
		}else{
			exportVisaInfoPdf($_POST['userid'], true);//export for all people
		}
	}
	
	if( isset($_POST['export_visa_info'])){
		exportVisaExcel();
	}

	//only active if not own data and has not the user management role
	if(!array_intersect(["usermanagement"], $user->roles ) && !$showCurrentUserData){
		$active = "active";
		$class = '';
		$tabclass = 'hidden';
	}else{
		$active = "";
		$class = 'hidden';
		$tabclass = '';
	}
	
	//Add an extra tab button on position 3
	$filteredHtml['tabs']['Immigration']	= "<li class='tablink $active $tabclass' id='show_visa_info' data-target='visa_info'>Immigration</li>";
	
	//Content
	ob_start();
	?>
	<div id='visa_info' class='tabcontent <?php echo $class;?>'>
		<?php
		echo visaPage(true);
	
		if(!$showCurrentUserData){
			?>
			<div class='export_button_wrapper' style='margin-top:50px;'>
				<form  method='post'>
					<input type='hidden' name='userid' id='userid' value='<?php echo $user->ID;?>'>
					<button class='button button-primary' type='submit' name='print_visa_info' value='generate'>Export user data as PDF</button>
				</form>
				<form method='post'>
					<button class='button button-primary' type='submit' name='print_visa_info' value='generate'>Export ALL data as PDF</button>
				</form>
				<form method='post'>
					<button class='button button-primary' type='submit' name='export_visa_info' value='generate'>Export ALL data to excel</button>
				</form>
			</div>
			<?php
		}
	?>
	</div>
	<?php

	$result	= ob_get_clean();

	$filteredHtml['html']	.= $result;

	return $filteredHtml;
}, 10, 4);