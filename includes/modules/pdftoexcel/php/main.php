<?php
namespace SIM\PDFTOEXCEL;
use SIM;
use Smalot\PdfParser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

/**
 * Reads a pdf and creates a csv or excel
 *
 * @param   string  $filePath       The path of the pdf
 * @param   string  $destination    The desired output location, specify 'download' to export to screen
 * @param   boolean $keepName       Whether or not to use the filename of the pdf as the filename for the exported file. Default true.     
 * @param   string  $type           If $destination is download you have to specify the desired output type csv or xlsx
 *
 * @return  string                  The path to the file  
 */
function readPdf($filePath, $destination, $keepName=true, $type='csv'){
    require_once( MODULE_PATH  . 'lib/vendor/autoload.php');

    if($keepName || $destination == 'download'){

        if($destination == 'download'){
            $ext        = $type;
        }else{
            $ext        = pathinfo($destination, PATHINFO_EXTENSION);
        }

        $fileName   = pathinfo($filePath)['filename'].'.'.$ext;
    }

    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($filePath);

    $pages = $pdf->getPages();
    $rows           = [];
    $header         = [];
    $columnXes      = []; // horizontal position of each column

    foreach($pages as $index=>$page){
        // skip front page
        if($index == 0){
            continue;
        }

        $data = $page->getDataTm();

        $skip           = true;
        $doingHeaders   = true;
        $row            = [];
        $prevY          = 0;

        foreach($data as $d){
            $value  = $d[1];
            $x      = $d[0][4];
            $y      = $d[0][5];

            // we have processed all transactions break the upper foreach
            if($value == 'Total'){
                // also add the last row
                if(!empty($row)){
                    $rows[] = $row;
                }

                break 2;
            }

            // First value of the header, start parsing
            if($value == "Date"){
                $skip   = false;
                $prevY  = $y;
            }

            if($skip){
                continue;
            }

            if($doingHeaders){
                if($prevY != $y){
                    $doingHeaders   = false;
                }else{
                    $header[]       = $value;
                    $columnXes[]    = $x;
                    continue;
                }
            }

            // check if new y value, if so its the first value of a new row
            if($prevY != $y){
                if(!empty($row)){
                    $rows[] = $row;
                }
                $row    = [$value];
            }else{
                // check if we need to add an empty value
                $nextColNr  = count($row)+1;

                // current x value is bigger than the x of the next coulumn
                while(isset($columnXes[$nextColNr]) && $x > $columnXes[$nextColNr]){
                    $row[]  = '';
                    $nextColNr++;
                }
                $row[]  = $value;
            }

            $prevY  = $y;
        }

        // also add the last row
        if(!empty($row)){
            $rows[] = $row;
        }
    }
    
    //echo $text;

    //SIM\printArray( $rows, true);

    return [
        'header'    => $header, 
        'rows'      => $rows,
        'filepath'  => createExcel($header, $rows, $fileName, $destination=='download')
    ];
}

/**
 * Reads a pdf and creates a csv or excel
 *
 * @param   array   $header         An array containg the data for the header
 * @param   array   $rows           An array of arrays contaning the row data
 * @param   string  $destination    The desired output location
 * @param   boolean $download       Whether to output to screen as download
 *
 * @return  string                  The path to the file  
 */
function createExcel($header, $rows, $destination, $download=false){
    $ext = pathinfo($destination, PATHINFO_EXTENSION);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->fromArray([$header], NULL, 'A1');

    foreach($rows as $index=>$row){
        $sheet->fromArray([$row], '', 'A'.$index+2);
    }

    //Create Styles Array
    $styleArrayFirstRow		= ['font' => ['bold' => true,]];

    //Retrieve Highest Column (e.g AE)
    $highestColumn			= $sheet->getHighestColumn();

    //set first row bold
    $sheet->getStyle('A1:' . $highestColumn . '1' )->applyFromArray($styleArrayFirstRow);
    
    if($ext == 'csv'){
        $writer					= new Csv($spreadsheet);
    }elseif($ext == 'xlsx'){
        $writer					= new Xlsx($spreadsheet);
    }else{
        $ext                    = 'csv';
        $writer					= new Csv($spreadsheet);
    }

    //Download excel file here
    if($download){
        SIM\clearOutput();
        ob_start();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=$destination");
        $writer->save('php://output');
        ob_end_flush();
        exit;
        die();
    }else{
        //Store xlsx file on server and return the path
        $file = get_temp_dir().$destination;
        $writer->save($file);
        return $file;
    }
}
