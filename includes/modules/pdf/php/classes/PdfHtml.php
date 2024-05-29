<?php
namespace SIM\PDF;
use SIM;

//load all libraries for this module
require( MODULE_PATH  . 'lib/vendor/autoload.php');

class PdfHtml extends \FPDF{
	//variables of html parser
	protected $b;
	protected $href;
	protected $fontList;
	protected $issetfont;
	protected $issetcolor;
	public $headertitle;
	public $skipFirstPage;
	public $fontlist;

	const DPI = 96;
    const MM_IN_INCH = 25.4;
    const A4_HEIGHT = 297;
    const A4_WIDTH = 210;
    // tweak these values (in pixels)
    const MAX_WIDTH = 800;
    const MAX_HEIGHT = 500;

	public function __construct($orientation='P', $unit='mm', $format='A4'){
		//Call parent constructor
		parent::__construct($orientation,$unit,$format);
		//Initialization
		$this->href='';
		$this->fontlist=array('arial', 'times', 'courier', 'helvetica', 'symbol');
		$this->issetfont=false;
		$this->issetcolor=false;
		$this->headertitle = "";
		$this->skipFirstPage = true;

	}

	/**
	 * returns an associative array (keys: R,G,B) from
	 * a hex html code (e.g. #3FE5AA)
	 *
	 * @param	string	$color	The Color to process
	 *
	 * @return	array			The RGB array
	 */
	public function hex2dec($color = "#000000"){
		$r 				= substr($color, 1, 2);
		$red 			= hexdec($r);
		$v 				= substr($color, 3, 2);
		$green 			= hexdec($v);
		$b 				= substr($color, 5, 2);
		$blue 			= hexdec($b);
		$tblColor 		= array();
		$tblColor['R']	= $red;
		$tblColor['G']	= $green;
		$tblColor['B']	= $blue;
		return $tblColor;
	}

	/**
	 * Get text from html
	 *
	 * @param	string	$html	The html to parse
	 *
	 * @return	string			the text
	 */
	public function txtentities($html){
		$trans = get_html_translation_table(HTML_ENTITIES);
		$trans = array_flip($trans);
		return strtr($html, $trans);
	}

	/**
	 * Write html to pdf
	 *
	 * @param	string	$html	the html
	 * @param	string	$parsed	reference to output var
	 */
	public function WriteHTML($html, &$parsed = null){
		//HTML parser
		$html	= do_shortcode($html);
		$html	= strip_tags($html, "<b><li><u><i><a><img><p><br><strong><em><font><tr><blockquote>");
		$html	= str_replace("\n", ' ', $html);
		$a		= preg_split('/<(.*)>/U', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

		if($parsed !== null && count($a) <2){
			return false;
		}

		foreach($a as $i=>$e){
			//every even index is the text, odd is the tag
			if($i%2==0){
				//Text
				if($this->href){
					$this->PutLink($this->href,$e);
				}elseif($parsed !== null){
					$parsed.=stripslashes($this->txtentities($e));
				}else{
					$this->Write(5,utf8_decode(stripslashes($this->txtentities($e))));
				}
			}else{
				//Tag
				if($e[0]=='/'){
					$this->CloseTag(strtoupper(substr($e,1)));
				}else{
					//Extract attributes
					$a2=explode(' ',$e);
					$tag=strtoupper(array_shift($a2));
					$attr=array();
					foreach($a2 as $v){
						if(preg_match('/([^=]*)=["\']?([^"\']*)/',$v,$a3)){
							$attr[strtoupper($a3[1])]=$a3[2];
						}
					}
					$this->OpenTag($tag,$attr);
				}
			}
		}
	}

	/**
	 * conversion pixel -> millimeter at 72 dpi
	 *
	 * @param	int		$px		The pixel amount
	 *
	 * @return	float			The mm
	 */
	public function px2mm($px){
		return $px*25.4/72;
	}

	/**
	 * Parses HTML opening tags
	 *
	 * @param	string	$tag	THe tag type
	 * @param	array	$attr	The element attributes array
	 */
	public function OpenTag($tag, $attr){
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
				$this->href=$attr['HREF'];
				break;
			case 'IMG':
				if(isset($attr['SRC']) && (isset($attr['WIDTH']) || isset($attr['HEIGHT']))) {
					if(!isset($attr['WIDTH'])){
						$attr['WIDTH'] = 0;
					}
					if(!isset($attr['HEIGHT'])){
						$attr['HEIGHT'] = 0;
					}
					try{
						$ext = strtoupper(pathinfo($attr['SRC'])['extension']);
						if($ext == 'JPE'){
							$ext = 'JPG';
						}
						$this->printImage($attr['SRC'], $this->GetX(), $this->GetY(), $this->px2mm($attr['WIDTH']), $this->px2mm($attr['HEIGHT']));
						$this->SetY($this->GetY()+$this->px2mm($attr['HEIGHT'])+2);
					}catch (\Exception $e) {
						SIM\printArray($e);
						SIM\printArray("{$attr['SRC']} is not a valid image");
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
					$colour=$this->hex2dec($attr['COLOR']);
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

	/**
	 * Parses HTML closing tags
	 *
	 * @param	string	$tag	The tag type
	 */
	public function CloseTag($tag){
		//Closing tag
		if($tag=='STRONG'){
			$tag='B';
		}
		if($tag=='EM'){
			$tag='I';
		}
		if($tag=='B' || $tag=='I' || $tag=='U'){
			$this->SetStyle($tag,false);
		}
		if($tag=='A'){
			$this->href='';
		}
		if($tag=='FONT'){
			if ($this->issetcolor) {
				$this->SetTextColor(0);
			}
			if ($this->issetfont) {
				$this->SetFont('arial');
				$this->issetfont=false;
			}
		}
	}

	public function SetStyle($tag, $enable){
		//Modify style and select corresponding font
		$this->$tag+=($enable ? 1 : -1);
		$style	= '';
		foreach(array('B','I','U') as $s)
		{
			if($this->$s>0){
				$style.=$s;
			}
		}
		$this->SetFont('',$style);
	}

	/**
	 * Write a hyperlink to pdf
	 *
	 * @param 	string	$url	the url
	 * @param	string	$text	the text to display
	 */
	public function PutLink($url, $txt){
		//Put a hyperlink
		$this->SetTextColor(0, 0, 255);
		$this->SetStyle('U', true);
		$this->Write(5, $txt, $url);
		$this->SetStyle('U', false);
		$this->SetTextColor(0);
	}

	/**
	 * Adds a first page with the title
	 *
	 * @param	string	$title
	 * @param	string	$subtitle
	 */
	public function frontpage($title, $subtitle=''){
		//Set the title of the document
		$this->SetTitle($title. " " .$subtitle);

		$this->AddPage();

		//Add the logo to the page
		$logo	= get_attached_file(SIM\getModuleOption(MODULE_SLUG, 'picture_ids')['logo']);
		try{
			$this->Image($logo, 70, 30, 70,0);
		}catch (\Exception $e) {
			SIM\printArray("PDF_export.php: $logo is not a valid image");
		}
		$this->SetFont( 'Arial', '', 42 );

		//Set the cursor position to 80
		$this->SetXY(0, 80);
		//Write the title
		$this->Cell(0, 0, $title, 0, 1, 'C');

		//Set the cursor position to 100
		$this->SetXY(0, 100);

		//Write the subtitle
		$this->Cell(0, 0, $subtitle,0,1,'C');
	}


	public function setHeaderTitle($title){
        $this->headertitle = $title;
        return true;
	}

	/**
	 * Page header
	 */
	public function Header(){
		if(!$this->skipFirstPage || $this->PageNo() != 1){
			$logo	= get_attached_file(SIM\getModuleOption(MODULE_SLUG, 'picture_ids')['logo']);
			try{
				// Logo
				$this->Image($logo, 10, 6, 30, 0, 'JPG');
			}catch (\Exception $e) {
				SIM\printArray("PDF_HELPER_Functions.php: $logo is not a valid image");
			}
			// Arial bold 15
			$this->SetFont('Arial','B',15);
			// Move to the right
			$this->Cell(80);

			// Title
			if($this->headertitle == ""){
				$this->Cell(30, 10, $this->metadata['Title'], 0, 0, 'C');
			}else{
				$this->Cell(30, 10, $this->headertitle, 0, 0, 'C');
			}

			// Line break
			$this->Ln(20);
		}
	}

	/**
	 * Page footer
	 */
	public function Footer(){
		if($this->PageNo() != 1){
			// Position at 1.5 cm from bottom
			$this->SetY(-15);
			// Arial italic 8
			$this->SetFont('Arial','I',8);
			// Page number, dont count the first page
			$pageNumber = ($this->PageNo())-1;
			$this->Cell(0, 10, 'Page '.$pageNumber, 0, 0, 'C');
		}
	}

	/**
	 * Write page title
	 */
	public function PageTitle($title, $new = true, $height = 10){
		if ($new){
			$this->AddPage();
		}

		$this->SetFont( 'Arial', '', 22 );
		$this->Write($height, $title);

		// Add a line break
		$this->Ln($height*1.5);
		$this->SetFont( 'Arial', '', 12 );
	}

	/**
	 * Write an array to pdf as list
	 *
	 * @param	array	$array		The array to write
	 * @param	int		$depth		The nesting level of the list
	 * @param	string	$prevKey	The name of the upper nesting list
	 */
	public function WriteArray($array, $depth=0, $prevKey=""){
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
			if(is_numeric($key)){
				$bullet = $key.'.  ';
			}

			if (is_array($field)){
				if($depth==0){
					$this->Ln(5);
				}

				if(is_numeric($key)){
					$this->WriteHTML(str_repeat("   ", $depth)."<strong>$prevKey $key.</strong><br>");
				}else{
					$this->WriteHTML(str_repeat("   ", $depth)."<strong>$key:</strong><br>");
				}

				//Display this array as well
				$this->WriteArray($field, $depth+1, $key);
			}else{
				if(is_numeric($key)){
					$showKey = "";
				}else{
					$showKey = $key.": ";
				}

				if (filter_var($field, FILTER_VALIDATE_URL)) {
					$url	= $field;
					$field	= basename($field);
				}

				$this->Write(5, str_repeat("   ", $depth).$bullet.' '.$showKey.$field, $url);
				$this->Ln(5);
			}
		}
	}

	/**
	 * Write an array of pictures to pdf as list
	 *
	 * @param	array	$array		The array to write
	 * @param	int		$depth		The nesting level of the list
	 * @param	string	$prevKey	The name of the upper nesting list
	 */
	public function WriteImageArray($array, $depth=0, $prevkey=""){
		//Do nothing if not an array
		if(is_array($array)){
			//Loop over all the pictures
			foreach($array as $key=>$image){
				//If the picture is an array of pictures run the function again
				if (is_array($image)){
					$this->WriteImageArray($image, $depth+1, $key);
				}else{
					//Get the extension
					$extension = strtoupper(explode('.', $image)[1]);
					if(!empty($extension)){
						if($extension == 'JPE'){
							$extension = 'JPG';
						}

						//Write the title of the image above the image
						if(!is_numeric($prevkey) && !empty($prevkey) && $key == 0){
							$this->Ln(5);
							$this->WriteHTML("<strong>".ucfirst($prevkey)."</strong><br>");
						}

						//Check the extension to see if it is printable and check if the file exists
						if(in_array($extension, ['JPG','JPEG','PNG','GIF'])){
							if(file_exists($image)){
								//Print the picture
								try{
									$this->Image($image, null, null, 100, 0,  $extension);
								}catch (\Exception $e) {
									SIM\printArray($image." is not a valid image");
								}
							}
						}else{
							if(is_string($image) && is_file($image)){
								$image	= SIM\pathToUrl($image);
							}

							//Write down the url as link
							$url	= $image;
							if(!str_contains($url, site_url())){
								$url	= site_url().'/'.$url;
							}

							$this->Write(10, chr(149).' '.explode('-', basename($image), 2)[1], $url);
							$this->Ln(5);
						}
					}
				}
			}
		}
	}

	public function pixelsToMM($val) {
        return $val * self::MM_IN_INCH / self::DPI;
    }

	/**
	 * Resize an image so it fits on the page
	 *
	 * @param	string	$imgPath	The path to the image
	 * @param	int		$maxHeight	Max height of the image
	 *
	 * @return	array				Array of width and height
	 */
    public function resizeToFit($imgPath, $maxHeight=self::MAX_HEIGHT) {
		if(!file_exists($imgPath)){
			return false;
		}
		
        list($width, $height) = getimagesize($imgPath);

		$maxHeight 		= min(self::MAX_HEIGHT, $maxHeight);

        $widthScale 	= self::MAX_WIDTH / $width;
        $heightScale 	= $maxHeight / $height;

        $scale 			= min($widthScale, $heightScale);

        return array(
            round($this->pixelsToMM($scale * $width)),
            round($this->pixelsToMM($scale * $height))
        );
    }

	/**
	 * Center an image on a page
	 *
	 * @param	string	$path		The path to the image
	 * @param	int		$y			The vertical position of the image
	 * @param	int		$maxHeight	The maximum height of the image
	 * @param	string	$ext		The extension of the image
	 *
	 * @return	array|boolean		Array of width and height or false in case of error
	 */
    public function centreImage($path, $y, $maxHeight, $ext) {
		if(!file_exists($path)){
			return false;
		}

        list($width, $height) = $this->resizeToFit($path, $maxHeight);

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

	/**
	 * Prints a picture to pdf
	 *
	 * @param	string	$url		The url of the image
	 * @param	int		$x			THe horizontal location
	 * @param	int		$y			The vertical position
	 * @param	int		$width		The withh of the image. Default 100px
	 * @param	int		$height		The height of the image. Default 200 px
	 * @param	bool	$centre		Whether to center the image on the page. Default false
	 * @param	bool	$adjustY	Whether to center vertically. Default false
	 */
	public function printImage($url, $x=-1, $y=-1, $width=100, $height=200, $centre=false, $adjustY = false){
		try{
			$path = SIM\urlToPath($url);

			if($x == -1){
				$x = $this->getX();
			}

			if($y == -1){
				$y = $this->getY();
			}

			if($width == -1){
				$width = 100;
			}

			if($height == -1){
				$height = 200;
			}

			$ext = strtoupper(pathinfo($path)['extension']);
			if($ext == 'JPE'){
				$ext = 'JPG';
			}elseif($ext == 'webp'){
				// create a temporay jpg file as webp is not supported by pdf
				$im = imagecreatefromwebp($path);

				// new source is jpg
				$path	= get_temp_dir().str_replace('.webp', '.jpg', basename($path));
				// Convert it to a jpeg file with 100% quality
				imagejpeg($im, $path, 100);
				imagedestroy($im);
			}

 			if($centre){
				list($width, $height) = $this->centreImage($path,$y,$height,$ext);
			}else{
				$this->Image($path, $x, $y, $width, $height,$ext);
			}

			if($adjustY){
				$y += $height;
			}

			$this->setXY($x+$width, $y);
		}catch (\Exception $e) {
			SIM\printArray("PDF_export.php: $path is not a valid image");
		}
	}

	/**
	 * Prints the table headers
	 *
	 * @param	array	$headers	the headers
	 * @param	array	$widths		The widths of each column
	 */
	public function tableHeaders($headers, $widths=""){
		// Header colors, line width and bold font
		$this->SetFillColor(189, 41, 25);
		$this->SetTextColor(255);
		$this->SetDrawColor(128,0,0);
		$this->SetLineWidth(.3);
		$this->SetFont('Arial','B',12);

		// Header content
		foreach($headers as $key=>$header_text){
			if(!is_array($widths) || !isset($widths[$key])){
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

	/**
	 * Split cell contents when column limit is reached to multiple lines
	 *
	 * @param	string	$string		The string to split
	 * @param	int		$maxWidth	THe maximum string width
	 *
	 * @return	string				The splited string
	 */
	public function splitOnWidth($string, $maxWidth){
		// Get width of a string in the current font
		$cw 		= &$this->CurrentFont['cw'];
		$w 			= 0;
		$l 			= strlen($string);
		$newString	= '';
		for($i=0;$i<$l;$i++){
			if((($w + $cw[$string[$i]]) * $this->FontSize/1000) > $maxWidth){
				//add a space, as fpdf will use that to continue on a new line
				$newString .= " ";
				//reset length
				$w	= 0;
			}

			//reset on a space
			if(empty(trim($string[$i]))){
				$w = 0;
			}

			$w += $cw[$string[$i]];
			$newString .= $string[$i];
		}

		return $newString;
	}

	public function addCellPicture($filePath, $x=null, $y=null, $link='', $width=6, $reset=true){
		if(empty($link)){
			$link	= SIM\pathToUrl($filePath);
		}
				
		//Add the picture to the page
		try{
			if(!$x){
				$x	= $this->getX();
			}

			if(!$y){
				$y	= $this->getY();
			}

			if($reset){
				$x	= $x + 0.15;
				$y	= $y + 0.15;
			}else{
				$y	= null; // needs to be set to null to get an updated Y value
			}

			$this->Image($filePath, $x, $y, $width, 0, '', $link);
		}catch (\Exception $e) {
			SIM\printArray("PDF_export.php: $filePath is not a valid image");
			//SIM\printArray($e);
		}

		if($reset){
			$this->SetXY($x, $y);
		}
	}

	/**
	 * Get the needed height of a row
	 *
	 * @param	array	$row	the row data
	 *
	 * @return	array			an array of cell heights
	 */
	private function getRowHeight($colWidths, $row){
		$cellHeights	= [];

		//Calculate the height of this row
		foreach ($row as $colNr => $cellText){

			//create a temp pdf to measure the heigth of the cell
			$temp 		= new $this();
			$temp->SetFont( $this->FontFamily, '', $this->FontSizePt );
			$temp->AddPage();
			$before 	= $temp->getY();

			$cellText	= apply_filters('sim_before_pdf_text', $cellText, $temp);

			$height		= $temp->getY() - $before;

			$before 	= $temp->getY();

			if(is_array($cellText)){
				$orgLines = $cellText;
			}else{
				$orgLines = explode("\n", $cellText);
			}
			SIM\cleanUpNestedArray($orgLines);

			$cellText	= implode("\n", $orgLines);

			$cellWidth 	= $colWidths[$colNr];
			
			$x			= $temp->GetX();
			$y			= $temp->GetY();

			$temp->Multicell($cellWidth, 6, $cellText, 'LR', 'C');

			$after 		= $temp->getY();

			$height		= max(intval($after - $before), $height);

			$before 	= $temp->getY();

			do_action('sim_after_pdf_text', $row[$colNr], $temp, $x, $y, $cellWidth, false);

			$after 		= $temp->getY();

			// get the maximum of either the picture or text
			$cellHeights[$colNr]	= max(intval($after - $before), $height);
		}
		
		return $cellHeights;
	}

	/**
	 * Write a row of a table
	 *
	 * @param	array	$colWidths	the column widths
	 * @param	array	$row		The row data
	 * @param	bool	$fil		Whether to fill the row
	 * @param	array	$header		The  table headers
	 */
	public function writeTableRow($colWidths, $row, $fill, $header){
		$y 				= $this->GetY();
		
		$cellHeights	= $this->getRowHeight($colWidths, $row);

		$rowHeight		= ceil(max($cellHeights)/6)*6;

		//Check if we need to continue on a new page
		if($this->y + $rowHeight * 6 > $this->h - 20){
			//Draw the bottom line over the full width of the table
			$this->Cell(array_sum($colWidths),0,'','T');

			//Add new page
			$this->AddPage();

			//Add table headers again
			$this->tableHeaders($header, $colWidths);

			$y = $this->GetY();
		}

		//now that we have the row width, loop over the data again to write it
		$lastKey = array_key_last($row);
		foreach ($row as $colNr => $cellText){
			$cellText	= str_replace('\\', '/', $cellText);

			$cellText	= apply_filters('sim_before_pdf_text', $cellText, $this);

			if(is_array($cellText)){
				$orgLines	= $cellText;
			}else{
				$orgLines	= explode("\n", $cellText);
			}
			SIM\cleanUpNestedArray($orgLines);

			$cellWidth = $colWidths[$colNr];
			foreach($orgLines as $l_nr=>$line){
				//Stringlength
				$length = $this->GetStringWidth($line)+2;

				//if we span multiple lines, makes sure each line has a space
				if($length > $cellWidth){
					$orgLines[$l_nr]	= $this->splitOnWidth($line, $cellWidth-5);
				}
			}

			$cellText	= implode("\n", $orgLines);

			$cellHeight = $cellHeights[$colNr];

			// add as many empty lines as needed to make the cell the required height
			if($cellHeight < $rowHeight){
				$cellText = $cellText.str_repeat("\n ", ceil(($rowHeight - $cellHeight) / 6)); // each line is 6 units
			}

			//Get current position
			$x 	= $this->GetX();
			$y	= $this->GetY();

			//Write the cell
			$this->Multicell($cellWidth, 6, $cellText, 'LR', 'C', $fill);

			do_action('sim_after_pdf_text', $row[$colNr], $this, $x, $y, $cellWidth, true);

			//Move cursor to the next cell if not the last cell
			if($colNr != $lastKey){
				$x = $x + $cellWidth;
				$this->SetXY($x, $y);
			}
		}
	}

	/**
	 * Print the pdf to screen
	 */
	public function printPdf($dest='', $name='', $isUTF8=false){
		// CLear the complete queue
		SIM\clearOutput();

		ob_start();
		$this->Output($dest, $name, $isUTF8);
		ob_end_flush();
		exit;
		die();
	}

}
