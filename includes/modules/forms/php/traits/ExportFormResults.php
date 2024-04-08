<?php
namespace SIM\FORMS;
use SIM;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait ExportFormResults{
	/**
	 * Clean excel Content from currenlty hidden columns
	 */
	function cleanExportContent(){
		$hiddenColumns		= get_user_meta($this->user->ID, 'hidden_columns_'.$this->formData->id, true);

		$excludeIndexes	= [];

		$header				= $this->excelContent[0];

		foreach($this->columnSettings as $setting){
			// If the name is found in the hidden columns, add the header index to the exclusion array
			if(!empty($hiddenColumns[$setting['name']])){
				$excludeIndexes[]	= array_search($setting['nice_name'], $header);
			}
		}

		//loop over the content and remove all needed
		foreach($this->excelContent as &$row){
			foreach($excludeIndexes as $i){
				unset($row[$i]);
			}

			$row	= array_values($row);
		}

		// There is a custom sort column defined
		if(is_numeric($this->tableSettings['default_sort'])){
			$sortElementId		= $this->tableSettings['default_sort'];
			$sortElement		= $this->getElementById($sortElementId);
			$sortElementType	= $sortElement->type;
			$sortColumnName		= $this->columnSettings[$sortElementId]['nice_name'];
			$sortCol			= array_search($sortColumnName, $this->excelContent[0]);

			// Sort
			$header				= $this->excelContent[0];
			//remove header, as it should not be sorted
			unset($this->excelContent[0]);
	
			//Sort the array
			usort($this->excelContent, function($a, $b) use ($sortCol, $sortElementType){
				if($sortElementType == 'date'){
					return strtotime($a[$sortCol]) <=> strtotime($b[$sortCol]);
				}
				return $a[$sortCol] > $b[$sortCol];
			});
	
			//Add header again
			$this->excelContent	= array_merge([$header], $this->excelContent);
		}
	}
	
	/**
	 * Export the current table to excel
	 *
	 * @param	string	$fileName	the name of the downloaded file. 	Default the formname
	 * @param	bool	$download	Whether to download the excel or print it to screen
	 */
	function exportExcel($fileName="", $download=true){
		if(empty($fileName)){
			$fileName = get_the_title($this->form_id).".xlsx";
		}

		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();

		//Write the column headers
		$col 		= 0;
		$row		= 1;
		$rowIndex	= 1;

		$this->cleanExportContent();

		//loop over the rows
		foreach($this->excelContent as $row){
			//Start column
			$col	= 1;
			//loop over the cells
			foreach ($row as $cell) {
				if(is_array($cell)){
					SIM\cleanUpNestedArray($cell);
					$cell	= implode(',', $cell);
				} 
				/* 
						Write the content to the cell
				*/
				$sheet->setCellValue([$col, $rowIndex], $cell);

				$col++;
			}
			//Consider new row for each entry here
			$rowIndex++;
		}
		
		//Create Styles Array
		$styleArrayFirstRow		= ['font' => ['bold' => true,]];
		//Retrieve Highest Column (e.g AE)
		$highestColumn			= $sheet->getHighestColumn();
		//set first row bold
		$sheet->getStyle('A1:' . $highestColumn . '1' )->applyFromArray($styleArrayFirstRow);
		
		$writer					= new Xlsx($spreadsheet);
		//Download excel file here
		if($download){
			SIM\clearOutput();
			ob_start();
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header("Content-Disposition: attachment; filename=$fileName");
			$writer->save('php://output');
			ob_end_flush();
			exit;
			die();
		}else{
			//Store xlsx file on server and return the path
			$file = get_temp_dir().$fileName;
			$writer->save($file);
			return $file;
		}
	}

	/**
	 * Export the current table to PDF
	 * 
	 * @param	string	$fileName	the name of the downloaded file. 	Default the formname
	 */
	function exportPdf(){
		$pdf = new SIM\PDF\PdfHtml();
		$pdf->SetFont('Arial','B',15);

		$this->cleanExportContent();
				
		//Determine the column widths
		$colCount	= count($this->excelContent[0]);
		$colWidths	= [];
		
		$header		= array_map('ucfirst', $this->excelContent[0]);

		$title		= $this->formSettings['formname'].' export';
		if(empty($title)){
			$title = 'Form export';
		}

		//loop over the data to check all cells for their length
		foreach($this->excelContent as $rowData){
			//loop over the columns to check how wide they need to be
			for ($x = 0; $x <= $colCount-1; $x++) {
				//Add the length to the array
				if(is_array($rowData[$x])){
					$rowData[$x] = implode("\n", $rowData[$x]);
				}

				$colWidths[$x][] = $pdf->GetStringWidth($rowData[$x]);
			}
		}

		//find the biggest cell per column
		for ($x = 0; $x <= $colCount-1; $x++) {
			$colWidths[$x] = max($colWidths[$x]);
		}

		$smallCollWidth		= 10;
		$mediumCollWidth	= 30;

		//count the columns smaller than 10
		$smallCollCount = count(array_filter(
			$colWidths,
			function ($value) use($smallCollWidth){
				return ($value <= $smallCollWidth);
			}
		));

		//page with minus the total width of all small collums
		$remainingWidth	= $pdf->GetPageWidth() - $smallCollCount * $smallCollWidth;

		//width needed by all non-small columns
		$requiredWidth = array_sum(array_filter(
			$colWidths,
			function ($value) use($smallCollWidth){
				return ($value > $smallCollWidth);
			}
		));

		//columns with a width smaller than 30
		$interColls	= array_filter(
			$colWidths,
			function ($value) use($smallCollWidth, $mediumCollWidth){
				return ($value > $smallCollWidth && $value <= $mediumCollWidth);
			}
		);

		//count the columns with a proportional medium width of 30 or smaller but an actual width bigger than 30
		$mediumCollCount = count(array_filter(
			$colWidths,
			function ($value) use($remainingWidth, $requiredWidth, $mediumCollWidth){
				$propWidth	= $remainingWidth*($value/$requiredWidth);
				return ($value > $mediumCollWidth && $propWidth <= $mediumCollWidth);
			}
		));

		$bigColls = array_filter(
			$colWidths,
			function ($value) use($remainingWidth, $requiredWidth, $mediumCollWidth){
				$propWidth	= $remainingWidth*($value/$requiredWidth);
				return ($propWidth > $mediumCollWidth);
			}
		);

		//Determine the page size
		$minWidth	= $smallCollCount * $smallCollWidth + array_sum($interColls) + $mediumCollCount * $mediumCollWidth + array_sum($bigColls)/6;

		if($minWidth > 180){
			if($minWidth < 297){
				//Landscape A4 pdf
				$pdf = new SIM\PDF\PdfHtml('L');
			}elseif($minWidth < 420){
				//Landscape pdf A3 size
				$pdf = new SIM\PDF\PdfHtml('L','mm','A3');
			}elseif($minWidth < 594){
				//Landscape pdf A2 size
				$pdf = new SIM\PDF\PdfHtml('L','mm',array(594,420));
			}elseif($minWidth < 841){
				//Landscape pdf A1 size
				$pdf = new SIM\PDF\PdfHtml('L','mm',array(841,594));
			}else{
				//Landscape pdf A0 size
				$pdf = new SIM\PDF\PdfHtml('L','mm',array(1189,841));
			}
			
			//Set font again
			$pdf->SetFont('Arial','B',15);
		}

		$availableWidthForBigColumns	= $pdf->GetPageWidth() -20 - $smallCollCount*$smallCollWidth - array_sum($interColls) - $mediumCollCount * $mediumCollWidth;
		$bigCollWidth					= $availableWidthForBigColumns / count($bigColls);

		//now loop over all columns and set their maximum lengths
		for ($x = 0; $x <= $colCount-1; $x++) {
			if($colWidths[$x] < 10){
				$colWidths[$x]	= 10;
			//min width for medium wide columns
			}elseif($colWidths[$x] > $mediumCollWidth && $remainingWidth*($colWidths[$x]/$requiredWidth) <= $mediumCollWidth){
				$colWidths[$x]	= min($colWidths[$x], $mediumCollWidth);
			}elseif($colWidths[$x] > 30){
				//equal spread for big columns
				$colWidths[$x]	= min($bigCollWidth, $colWidths[$x]);
			}
		}
		
		$pdf->frontpage($title);

		$pdf->AddPage();
		
		//Write the table headers
		$pdf->tableHeaders($header, $colWidths);
		
		// Data
		$fill = false;
		
		unset($this->excelContent[0]);
		foreach($this->excelContent as $rowData){
			$pdf->writeTableRow($colWidths, $rowData, $fill, $header);
			
			$fill = !$fill;
		}
		
		// Closing line which is the sum off all the column widths
		$pdf->Cell(array_sum($colWidths),0,'','T');

		$pdf->printPdf();

		exit;
	}
}