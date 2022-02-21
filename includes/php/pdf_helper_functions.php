<?php
/**
 * Modified from http://www.fpdf.org/en/script/script42.php
 */

//function hex2dec
//returns an associative array (keys: R,G,B) from
//a hex html code (e.g. #3FE5AA)
function hex2dec($color = "#000000"){
    $R = substr($color, 1, 2);
    $rouge = hexdec($R);
    $V = substr($color, 3, 2);
    $vert = hexdec($V);
    $B = substr($color, 5, 2);
    $bleu = hexdec($B);
    $tbl_color = array();
    $tbl_color['R']='red';
    $tbl_color['G']='green';
    $tbl_color['B']='blue';
    return $tbl_color;
}

//conversion pixel -> millimeter at 72 dpi
function px2mm($px){
    return $px*25.4/72;
}

function txtentities($html){
    $trans = get_html_translation_table(HTML_ENTITIES);
    $trans = array_flip($trans);
    return strtr($html, $trans);
}
////////////////////////////////////
class PDF_HTML extends FPDF{
	//variables of html parser
	protected $B;
	protected $I;
	protected $U;
	protected $HREF;
	protected $fontList;
	protected $issetfont;
	protected $issetcolor;
	
	const DPI = 96;
    const MM_IN_INCH = 25.4;
    const A4_HEIGHT = 297;
    const A4_WIDTH = 210;
    // tweak these values (in pixels)
    const MAX_WIDTH = 800;
    const MAX_HEIGHT = 500;

	function __construct($orientation='P', $unit='mm', $format='A4'){
		//Call parent constructor
		parent::__construct($orientation,$unit,$format);
		//Initialization
		$this->B=0;
		$this->I=0;
		$this->U=0;
		$this->HREF='';
		$this->fontlist=array('arial', 'times', 'courier', 'helvetica', 'symbol');
		$this->issetfont=false;
		$this->issetcolor=false;
		$this->headertitle = "";
		$this->skipfirstpage = true;
		
	}

	function WriteHTML($html,&$parsed = null){
		//HTML parser
		$html	= do_shortcode($html);
		$html	= strip_tags($html,"<b><li><u><i><a><img><p><br><strong><em><font><tr><blockquote>");
		$html	= str_replace("\n",' ',$html);
		$a		= preg_split('/<(.*)>/U',$html,-1,PREG_SPLIT_DELIM_CAPTURE);
		
		if($parsed !== null and count($a) <2){
			return false;
		}

		foreach($a as $i=>$e){
			//every even index is the text, odd is the tag
			if($i%2==0){
				//Text
				if($this->HREF){
					$this->PutLink($this->HREF,$e);
				}elseif($parsed !== null){
					$parsed.=stripslashes(txtentities($e));
				}else{
					$this->Write(5,utf8_decode(stripslashes(txtentities($e))));
				}
			}else{
				//Tag
				if($e[0]=='/')
					$this->CloseTag(strtoupper(substr($e,1)));
				else
				{
					//Extract attributes
					$a2=explode(' ',$e);
					$tag=strtoupper(array_shift($a2));
					$attr=array();
					foreach($a2 as $v){
						if(preg_match('/([^=]*)=["\']?([^"\']*)/',$v,$a3))
							$attr[strtoupper($a3[1])]=$a3[2];
					}
					$this->OpenTag($tag,$attr);
				}
			}
		}
	}

	function OpenTag($tag, $attr){
		//Opening tag
		switch($tag){
			case 'STRONG':
				$this->SetStyle('B',true);
				break;
			case 'EM':
				$this->SetStyle('I',true);
				break;
			case 'B':
			case 'I':
			case 'U':
				$this->SetStyle($tag,true);
				break;
			case 'A':
				$this->HREF=$attr['HREF'];
				break;
			case 'IMG':
				if(isset($attr['SRC']) && (isset($attr['WIDTH']) || isset($attr['HEIGHT']))) {
					if(!isset($attr['WIDTH']))
						$attr['WIDTH'] = 0;
					if(!isset($attr['HEIGHT']))
						$attr['HEIGHT'] = 0;
					
					try{
						$ext = strtoupper(pathinfo($attr['SRC'])['extension']);
						if($ext == 'JPE') $ext = 'JPG';
						$this->Image($attr['SRC'],$this->GetX(),$this->GetY(),px2mm($attr['WIDTH']), px2mm($attr['HEIGHT']),$ext);
						$this->SetY($this->GetY()+px2mm($attr['HEIGHT'])+2);
					}catch (Exception $e) {
						SIM\print_array("PDF_HELPER_Functions.php: {$attr['SRC']} is not a valid image");
					}
				}
				break;
			case 'TR':
			case 'BLOCKQUOTE':
			case 'BR':
				$this->Ln(5);
				break;
			case 'P':
				$this->Ln(10);
				break;
			case 'FONT':
				if (isset($attr['COLOR']) && $attr['COLOR']!='') {
					$colour=hex2dec($attr['COLOR']);
					$this->SetTextColor($colour['R'],$colour['G'],$colour['B']);
					$this->issetcolor=true;
				}
				if (isset($attr['FACE']) && in_array(strtolower($attr['FACE']), $this->fontlist)) {
					$this->SetFont(strtolower($attr['FACE']));
					$this->issetfont=true;
				}
				break;
			case 'LI':
				$this->Ln(5);
				$this->Write(5,chr(127).'   ');
				break;
				
		}
	}

	function CloseTag($tag){
		//Closing tag
		if($tag=='STRONG')
			$tag='B';
		if($tag=='EM')
			$tag='I';
		if($tag=='B' || $tag=='I' || $tag=='U')
			$this->SetStyle($tag,false);
		if($tag=='A')
			$this->HREF='';
		if($tag=='FONT'){
			if ($this->issetcolor==true) {
				$this->SetTextColor(0);
			}
			if ($this->issetfont) {
				$this->SetFont('arial');
				$this->issetfont=false;
			}
		}
	}

	function SetStyle($tag, $enable){
		//Modify style and select corresponding font
		$this->$tag+=($enable ? 1 : -1);
		$style='';
		foreach(array('B','I','U') as $s)
		{
			if($this->$s>0)
				$style.=$s;
		}
		$this->SetFont('',$style);
	}

	function PutLink($URL, $txt){
		//Put a hyperlink
		$this->SetTextColor(0,0,255);
		$this->SetStyle('U',true);
		$this->Write(5,$txt,$URL);
		$this->SetStyle('U',false);
		$this->SetTextColor(0);
	}

	function frontpage($title, $subtitle=''){
		global $PDF_Logo_path;
		
		//Set the title of the document
		$this->SetTitle($title. " " .$subtitle);
		
		$this->AddPage();

		//Add the logo to the page
		try{
			$this->Image($PDF_Logo_path, 70, 30, 70,0);
		}catch (\Exception $e) {
			SIM\print_array("PDF_export.php: $PDF_Logo_path is not a valid image");
		}
		$this->SetFont( 'Arial', '', 42 );
		
		//Set the cursor position to 80
		$this->SetXY(0,80);
		//Write the title
		$this->Cell(0,0,$title,0,1,'C');
		
		//Set the cursor position to 100
		$this->SetXY(0,100);
		//Write the subtitle
		$this->Cell(0,0,$subtitle,0,1,'C');

		//add new page
		$this->AddPage();
	}
	
	function setHeaderTitle($title){
        $this->headertitle = $title;
        return true; 
	}
	
	// Page header
	function Header(){
		global $PDF_Logo_path;
		if($this->skipfirstpage == false or $this->PageNo() != 1){
			try{
				// Logo
				$this->Image($PDF_Logo_path,10,6,30,0,'JPG');
			}catch (Exception $e) {
				SIM\print_array("PDF_HELPER_Functions.php: $PDF_Logo_path is not a valid image");
			}
			// Arial bold 15
			$this->SetFont('Arial','B',15);
			// Move to the right
			$this->Cell(80);
			
			// Title
			if($this->headertitle == ""){
				$this->Cell(30,10,$this->metadata['Title'],0,0,'C');
			}else{
				$this->Cell(30,10,$this->headertitle,0,0,'C');
			}
			
			// Line break
			$this->Ln(20);
		}
	}

	// Page footer
	function Footer(){
		if($this->PageNo() != 1){
			// Position at 1.5 cm from bottom
			$this->SetY(-15);
			// Arial italic 8
			$this->SetFont('Arial','I',8);
			// Page number, dont count the first page
			$pagenumber = ($this->PageNo())-1;
			$this->Cell(0,10,'Page '.$pagenumber,0,0,'C');
		}
	}
	
	function PageTitle($title, $new = true, $height = 10){
		if ($new == true) $this->AddPage();
		
		$this->SetFont( 'Arial', '', 22 );
		$this->Write($height, $title);

		// Add a line break
		$this->Ln($height*1.5);
		$this->SetFont( 'Arial', '', 12 );
	}
	
	function WriteArray($array, $depth=0, $prevkey=""){
		//Determine the bullet symbol
		switch ($depth) {
			case 0:
				$bullet = chr(149);
				break;
			case 1:
				$bullet = chr(186);
				break;
			case 2:
				$bullet = chr(183);
				break;
			case 3:
				$bullet = chr(155);
				break;
			case 4:
				$bullet = chr(176);
				break;
			default:
				$bullet = chr(149);
				break;
		} 

		//Loop over the data
		foreach($array as $key=>$field){
			if(is_numeric($key)) $bullet = $key.'.  ';
			if (is_array($field)){
				if($depth==0) $this->Ln(5);
				if(is_numeric($key)){
					$this->WriteHTML(str_repeat("   ",$depth)."<strong>$prevkey $key.</strong><br>");
				}else{
					$this->WriteHTML(str_repeat("   ",$depth)."<strong>$key:</strong><br>");
				}
				
				//Display this array as well
				$this->WriteArray($field, $depth+1,$key);
			}else{
				if(is_numeric($key)){
					$show_key = "";
				}else{
					$show_key = $key.": ";
				}
				
				$this->Write(5, str_repeat("   ",$depth).$bullet.' '.$show_key.$field);
				$this->Ln(5);
			}
		}
	}
	
	function WriteImageArray($array, $depth=0, $prevkey=""){
		//DO nothing if not an array
		if(is_array($array)){
			//Loop over all the pictures
			foreach($array as $key=>$image){
				//If the picture is an array of pictures run the function again
				if (is_array($image)){
					$this->WriteImageArray($image,$depth+1,$key);
				}else{
					//Get the extension
					$extension = strtoupper(explode('.',$image)[1]);
					if($extension != ""){
						if($extension == 'JPE') $extension = 'JPG';
						//Write the title of the image above the image
						if(!is_numeric($prevkey) and $prevkey != "" and $key == 0){
							$this->Ln(5);
							$this->WriteHTML("<strong>".ucfirst($prevkey)."</strong><br>");
						}
						
						//Check the extension to see if it is printable and check if the file exists
						if(in_array($extension,['JPG','JPEG','PNG','GIF'])){
							if(file_exists(ABSPATH.$image))
							//Print the picture
							try{
								$this->Image(ABSPATH.$image, null, null, 100, 0,  $extension);
							}catch (Exception $e) {
								SIM\print_array(ABSPATH.$image." is not a valid image");
							}
						}else{
							//Write down the url as link
							$this->Write(10,chr(149).' '.explode('-',end(explode('/',$image)),2)[1],site_url().'/'.$image);
							$this->Ln(5);
						}
					}
				}
			}
		}
	}
	
	function pixelsToMM($val) {
        return $val * self::MM_IN_INCH / self::DPI;
    }

    function resizeToFit($imgFilename,$max_height=self::MAX_HEIGHT) {
        list($width, $height) = getimagesize($imgFilename);
		
		$max_height = min(self::MAX_HEIGHT, $max_height);

        $widthScale = self::MAX_WIDTH / $width;
        $heightScale = $max_height / $height;

        $scale = min($widthScale, $heightScale);

        return array(
            round($this->pixelsToMM($scale * $width)),
            round($this->pixelsToMM($scale * $height))
        );
    }

    function centreImage($path, $y=0,$max_height,$ext) {
        list($width, $height) = $this->resizeToFit($path,$max_height);

        // you will probably want to swap the width/height
        // around depending on the page's orientation
        $this->Image(
            $path, 
			(self::A4_WIDTH - $width) / 2,
            $y,
            $width,
            $height,
			$ext
        );
		
		return array($width, $height);
    }
	
	function print_image($url, $x=-1, $y=-1, $width=100, $height=200,$centre=false, $adjustY = false){
		try{
			$path = SIM\url_to_path($url);
			
			if($x == -1) $x = $this->getX();
			if($y == -1) $y = $this->getY();
			if($width == -1) $width = 100;
			if($height == -1) $height = 200;
			
			$ext = strtoupper(pathinfo($path)['extension']);
			if($ext == 'JPE') $ext = 'JPG';
			
 			if($centre){
				list($width, $height) = $this->centreImage($path,$y,$height,$ext);
			}else{
				$this->Image($path, $x, $y, $width, $height,$ext);
			}

			if($adjustY) $y += $height;
			
			$this->setXY($x+$width,$y);
		}catch (Exception $e) {
			SIM\print_array("PDF_export.php: $path is not a valid image");
		}
	}
		
	function table_headers($headers,$widths=""){
		// Header colors, line width and bold font
		$this->SetFillColor(189, 41, 25);
		$this->SetTextColor(255);
		$this->SetDrawColor(128,0,0);
		$this->SetLineWidth(.3);
		$this->SetFont('Arial','B',12);
		
		// Header content
		foreach($headers as $key=>$header_text){
			if(!is_array($widths) or !isset($widths[$key])){
				$width = 30;
			}else{
				$width = $widths[$key];
			}
			$this->Cell($width,7,$header_text,1,0,'C',true);
		}
		$this->Ln();
		
		// Color and font restoration
		$this->SetFillColor(224,235,255);
		$this->SetTextColor(0);
		$this->SetFont( 'Arial', '', 8 );
	}

	function split_on_width($string, $max_width){
		// Get width of a string in the current font
		$cw = &$this->CurrentFont['cw'];
		$w = 0;
		$l = strlen($string);
		$new_string	= '';
		for($i=0;$i<$l;$i++){
			if((($w + $cw[$string[$i]]) * $this->FontSize/1000) > $max_width){
				//add a space, as fpdf will use that to continue on a new line
				$new_string .= " ";
				//reset length
				$w	= 0;
			}
			
			//reset on a space
			if(empty(trim($string[$i]))) $w = 0;

			$w += $cw[$string[$i]];
			$new_string .= $string[$i];
		}
			
		return $new_string;
	}
	
	function WriteTableRow($col_widths, $row, $fill, $header){
		$row_height = 1;
		$y = $this->GetY();

		$cell_heights	= [];
		
		//Calculate the height of this row
		foreach ($row as $col_nr => $celltext){
			if(is_array($celltext)){
				$org_lines = $celltext;
			}else{
				$org_lines = explode("\n",$celltext);
			}
			SIM\clean_up_nested_array($org_lines);

			$celltext	= trim(implode("\n",$org_lines));

			$cell_width = $col_widths[$col_nr];

			//create a temp pdf to measure the heigth of the cell
			$temp = new $this();
			$temp->SetFont( $this->FontFamily, '', $this->FontSizePt );
			$temp->AddPage();
			$before = $temp->getY();
			$temp->Multicell($cell_width,6,$celltext,'LR','C');
			$after = $temp->getY();
			$line_count = ($after-$before)/6;
			
			$cell_heights[$col_nr]	= $line_count;
		}
		$row_height = max($cell_heights);
		
		//Check if we need to continue on a new page
		if($this->y + $row_height*6 > $this->h - 20){
			//Draw the bottom line over the full width of the table
			$this->Cell(array_sum($col_widths),0,'','T');
			
			//Add new page
			$this->AddPage();
			
			//Add table headers again
			$this->table_headers($header, $col_widths);
			
			$y = $this->GetY();
		}
		
		//now that we have the row width, loop over the data again to write it
		$last_key = array_key_last($row);
		foreach ($row as $col_nr => $celltext){
			if(is_array($celltext)){
				$org_lines	= $celltext;
			}else{
				$org_lines	= explode("\n",$celltext);
			}
			SIM\clean_up_nested_array($org_lines);

			$cell_width = $col_widths[$col_nr];
			foreach($org_lines as $l_nr=>$line){
				//Stringlength
				$length = $this->GetStringWidth($line)+2;
				
				//if we span multiple lines, makes sure each line has a space
				if($length > $cell_width){
					$org_lines[$l_nr]	= $this->split_on_width($line, $cell_width-5);
				}
			}
			
			$celltext	= trim(implode("\n",$org_lines));

			$line_count = $cell_heights[$col_nr];

			if($line_count < $row_height) $celltext = $celltext.str_repeat("\n ",$row_height-$line_count);
			
			//Get current position
			$x = $this->GetX();

			//Write the cell
			$this->Multicell($cell_width,6,$celltext,'LR','C',$fill);
			
			//Move cursor to the next cell if not the last cell
			if($col_nr != $last_key){
				$x = $x+$cell_width;
				$this->SetXY($x,$y);
			}
		}
	}

	function printpdf(){
		// CLear the complete queue
		while(true){
			//ob_get_clean only returns false when there is absolutely nothing anymore
			$result	= ob_get_clean();
			if($result === false) break;
		}

		ob_start();
		$this->Output();
		ob_end_flush();
		exit;
		die();
	}
	
}//end of class