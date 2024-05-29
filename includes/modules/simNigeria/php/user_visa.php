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
		$sheet->setCellValue([$key+2, 2], ucfirst(str_replace('_',' ',$field)));
	}

	foreach($understudyData as $key=>$field){
		$sheet->setCellValue([$key+8, 2], ucfirst(str_replace('_',' ',$field)));
		$sheet->setCellValue([$key+19, 2], ucfirst(str_replace('_',' ',$field)));
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

		$sheet->setCellValue("A$row", $displayName);

		//Skip if there is no data
		if(!is_array($visaInfo) || empty($visaInfo)){
			$sheet->setCellValue("B$row", 'No data');
			continue;
		}

		//Loop over the greencard values and write them to excel
		foreach($greencardData as $key=>$field){
			$sheet->setCellValue([$key+2, $row], $visaInfo[$field]);
		}

		//Loop over the understudy values and write them to excel
		foreach($understudyData as $key=>$field){
			$sheet->setCellValue([$key+8 , $row], $understudy1[$field]);
			$sheet->setCellValue([$key+19, $row], $understudy2[$field]);
		}

		$row++;
	}

	//Now write it all to an excel file
	$writer = new Xlsx($spreadsheet);
	SIM\clearOutput();
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header("Content-Disposition: attachment; filename=$filename");
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
			if(SIM\isChild($user->ID)){
				return;
			}

			writeVisaPages($user, $pdf);
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
 * @param	object|int		$user		WP_User or user id
 * @param	object			$pdf		PDF instance
 */
function writeVisaPages($user, $pdf){
	if(is_numeric($user)){
		$user	= get_user_by('ID', $user);
	}

	$visaInfo = get_user_meta( $user->ID, "visa_info", true);

	// Nigerian
	if(
		is_array($visaInfo) &&
		isset($visaInfo['permit_type']) &&
		(
			is_array($visaInfo['permit_type'])	&& $visaInfo['permit_type'][0] != 'greencard' || // Nigerian passport or no green card needed
			$visaInfo['permit_type'] != 'greencard'
		) ||
		get_user_meta($user->ID, 'account-type', true) == 'positional'						// positional account
	){
		return $pdf;
	}

	$pdf->setHeaderTitle('Greencard information for '.$user->display_name);
	$pdf->PageTitle($pdf->headertitle);

	
	if(!is_array($visaInfo) || empty($visaInfo)){
		if(!empty($visaInfo)){
			delete_user_meta( $user->ID, "visa_info");
		}

		$pdf->Write(10, "No greencard information found.");
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
	$info1				= get_user_meta( $user->ID, "understudy_1", true);
	$info2				= get_user_meta( $user->ID, "understudy_2", true);
	if(!empty($info1)){
		$understudiesArray[1]	= $info1;
	}
	if(!empty($info2)){
		$understudiesArray[2]	= $info2;
	}

	foreach($understudiesArray as $key=>$understudy){
		if(isset($understudy['documents'])){
			$understudiesDocuments[$key] = $understudy['documents'];
			unset($understudiesArray[$key]['documents']);
		}
	}

	if(!empty($visaInfo)){
		$cleanArray	= [];

		// shot files as urls and nice index names
		foreach($visaInfo as $index=>&$value){
			if(is_array($value)){
				foreach($value as &$v){
					if(is_file($v)){
						$v	= SIM\pathToUrl($v);
					}
				}
			}
			$cleanArray[ucfirst(str_replace('_', ' ', $index))]	= $value;
		}

		$pdf->WriteArray($cleanArray);
	}else{
		$pdf->Write(10, 'No greencard details found');
		$pdf->Ln(10);
	}

	if(!empty($understudiesArray)){
		$pdf->PageTitle('Understudy information');

		$cleanArray	= [];

		// shot files as urls and nice index names
		foreach($understudiesArray as $index=>$value){
			if(is_array($value)){
				$cleanArray[$index]	= [];

				foreach($value as $i=>$v){

					$cleanArray[$index][ucfirst(str_replace('_', ' ', $i))]	= $v;
					if(is_string($v) && is_file($v)){
						$v	= SIM\pathToUrl($v);
					}
				}
			}
		}

		$pdf->WriteArray($cleanArray);
	}else{
		$pdf->Write(10, 'No understudy information found');
		$pdf->Ln(10);
	}

	if(is_array($qualificationsArray) && count($qualificationsArray)>0){
		$pdf->PageTitle('Qualifications');

		foreach($qualificationsArray as &$file){
			if(is_string($file) && is_file($file)){
				$file	= SIM\pathToUrl($file);
			}
		}
		$pdf->WriteImageArray($qualificationsArray);
	}else{
		$pdf->Write(10, 'No qualifications found');
		$pdf->Ln(10);
	}

	if(is_array($understudiesDocuments) && !empty($understudiesDocuments)){
		$pdf->AddPage();

		foreach($understudiesDocuments as $key=>$understudiesDocument){
			if(count($understudiesDocument) > 0){
				if($key>1){
					$pdf->Ln(10);
				}
				$pdf->PageTitle('Understudy documents for understudy '.$key, false);

				$pdf->WriteImageArray($understudiesDocument);
			}else{
				$pdf->Write(10, 'No understudy documents found for understudy '.$key);
				$pdf->Ln(10);
			}
		}
	}else{
		$pdf->Write(10, 'No understudy documents found');
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
	if(
		get_user_meta($user->ID, 'account-type', true) == 'positional'	||
		(
			!array_intersect(["visainfo"], wp_get_current_user()->roles ) &&
			!$showCurrentUserData
		) ||
		$userAge < 18
	){
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

	//Add an extra tab button on position 3
	$filteredHtml['tabs']['Immigration']	= "<li class='tablink' id='show_visa_info' data-target='visa_info'>Immigration</li>";

	//Content
	ob_start();
	?>
	<div id='visa_info' class='tabcontent hidden'>
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