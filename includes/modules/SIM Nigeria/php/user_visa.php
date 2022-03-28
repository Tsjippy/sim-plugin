<?php
namespace SIM\SIMNIGERIA;
use SIM;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

//Multi default values used to prefil the compound dropdown
add_filter( 'add_form_multi_defaults', function($default_array_values, $user_id, $formname){
	if($formname != 'user_visa') return $default_array_values;
	
	$default_array_values['quotanames'] 			= QUOTANAMES;
	
	return $default_array_values;
},10,3);

function visa_page($user_id, $message=false, $readonly=false){
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
	$users = SIM\get_user_accounts();
	foreach($users as $user){
		//skip non-valid users
		if(empty($user->display_name)){
			SIM\print_array("User with id {$user->ID} has not a valid display name");
			continue;
		}
		
		$display_name = get_userdata( $user->ID)->display_name;
		$visa_info = get_user_meta( $user->ID, "visa_info",true);
		
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
			$sheet->setCellValueByColumnAndRow($key+8 , $row, $visa_info['understudies'][1][$field]);
			$sheet->setCellValueByColumnAndRow($key+19, $row, $visa_info['understudies'][2][$field]);
		}

		$row++;
	}
	
	//Remove the default sheet
	//$spreadsheet->removeSheetByIndex(0);
	
	//Now write it all to an excel file
	$writer = new Xlsx($spreadsheet);
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header("Content-Disposition: attachment; filename=$filename");
	ob_clean();
	ob_start();
	$writer->save('php://output');
	ob_end_flush();
	exit;
}