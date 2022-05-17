<?php
namespace SIM\SIMNIGERIA;
use SIM;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

//Multi default values used to prefil the compound dropdown
add_filter( 'add_form_multi_defaults', function($default_array_values, $userId, $formname){
	if($formname != 'user_visa') return $default_array_values;
	
	$default_array_values['quotanames'] 			= QUOTANAMES;
	
	return $default_array_values;
},10,3);

function visa_page($userId, $message=false, $readonly=false){
	ob_start();
	?>
	<div>		
		<h3>Greencard information</h3>
		
		<?php if($message == false){?>
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
			echo do_shortcode('[formbuilder datatype=user_visa]');
			?>
		</div>
		
		<div id="understudy_1_info" class="tabcontent hidden">
			<?php
			echo do_shortcode('[formbuilder datatype=understudy_1]');
			?>
		</div>
		
		<div id="understudy_2_info" class="tabcontent hidden">
			<?php
			echo do_shortcode('[formbuilder datatype=understudy_2]');
			?>
		</div>
	</div>
	<?php 

	return ob_get_clean();
}

function export_visa_excel(){
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
	
	$greencard_data		= ['greencard_expiry','name','nin','quota_position','accompanying'];
	$understudy_data	= ['name','email','employing_institution','position','grade_level','salary','phonenumber1','phonenumber2','taxid','nin'];

	foreach($greencard_data as $key=>$field){
		$sheet->setCellValueByColumnAndRow($key+2, 2, ucfirst(str_replace('_',' ',$field)));
	}

	foreach($understudy_data as $key=>$field){
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
		
		$display_name 	= get_userdata( $user->ID)->display_name;
		$visa_info 		= get_user_meta( $user->ID, "visa_info",true);
		$understudy_1	= get_user_meta( $user->ID, "understudy_1",true);
		$understudy_2	= get_user_meta( $user->ID, "understudy_2",true);
		
		$sheet->setCellValueByColumnAndRow(1, $row, $display_name);
		
		//Skip if there is no data
		if(!is_array($visa_info) or count($visa_info)==0){
			$sheet->setCellValueByColumnAndRow(2, $row, 'No data');
			continue;
		}
		
		//Loop over the greencard values and write them to excel
		foreach($greencard_data as $key=>$field){
			$sheet->setCellValueByColumnAndRow($key+2, $row, $visa_info[$field]);
		}
		
		//Loop over the understudy values and write them to excel
		foreach($understudy_data as $key=>$field){
			$sheet->setCellValueByColumnAndRow($key+8 , $row, $understudy_1[$field]);
			$sheet->setCellValueByColumnAndRow($key+19, $row, $understudy_2[$field]);
		}

		$row++;
	}
	
	//Remove the default sheet
	//$spreadsheet->removeSheetByIndex(0);
	
	//Now write it all to an excel file
	$writer = new Xlsx($spreadsheet);
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header("Content-Disposition: attachment; filename=$filename");
	while(true){
		//ob_get_clean only returns false when there is absolutely nothing anymore
		$result	= ob_get_clean();
		if($result === false) break;
	}
	ob_start();
	$writer->save('php://output');
	ob_end_flush();
	exit;
}

function exportVisaInfoPdf($userId=0, $all=false) {
	if($all == true){
		//Build the frontpage
		$pdf = new SIM\PDF\PDF_HTML();
		$pdf->frontpage("Visa user info","");
		
		//Get all adult missionaries
		$users = SIM\getUserAccounts();
		foreach($users as $user){
			$pdf->setHeaderTitle('Greencard information for '.$user->display_name);
			$pdf->PageTitle($pdf->headertitle);
			writeVisaPages($user->ID, $pdf);
		}
	}else{
		//Build the frontpage
		$pdf = new SIM\PDF\PDF_HTML();
		$pdf->frontpage("Visa user info for:",get_userdata($userId)->display_name);
		writeVisaPages($userId, $pdf);
	}
	
	$pdf->printpdf();
}

function writeVisaPages($userId, $pdf){
	$visa_info = get_user_meta( $userId, "visa_info",true);
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
	$info_1				= get_user_meta( $userId, "understudy_1",true);
	$info_2				= get_user_meta( $userId, "understudy_2",true);
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


// allow people with the visainfo role to see the user select
add_filter('sim_user_page_dropdown', function($genericInfoRoles){
	$genericInfoRoles[]	= "visainfo";
	return $genericInfoRoles;
});

/*
	Add a visa Info page to user management screen
*/
add_filter('sim_user_info_page', function($filteredHtml, $showCurrentUserData, $user, $userAge){
	if((array_intersect(["visainfo"], $user->roles ) or $showCurrentUserData)){
		if($userAge > 18){
			if( isset($_POST['print_visa_info'])){
				if(isset($_POST['userid']) and is_numeric($_POST['userid'])){
					exportVisaInfoPdf($_POST['userid']);
				}else{
					exportVisaInfoPdf($_POST['userid'], true);//export for all people
				}
			}
			
			if( isset($_POST['export_visa_info'])){
				export_visa_excel();
			}
		
			//only active if not own data and has not the user management role
			if(!array_intersect(["usermanagement"], $user->roles ) and !$showCurrentUserData){
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
				echo visa_page($user->ID, true);
			
				if(!$showCurrentUserData){
					?>
					<div class='export_button_wrapper' style='margin-top:50px;'>
						<form  method='post'>
							<input type='hidden' name='userid' id='userid' value='$userId'>
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
		}
	}

	return $filteredHtml;
}, 10, 4);