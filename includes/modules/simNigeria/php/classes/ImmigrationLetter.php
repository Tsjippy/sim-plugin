<?php
namespace SIM\SIMNIGERIA;
use SIM;

class ImmigrationLetter extends SIM\PDF\PdfHtml{
	public $lineHeight;
	public $brakeHeight;

	public function __construct($orientation='P', $unit='mm', $format='A4'){
		//Call parent constructor
		parent::__construct($orientation,$unit,$format);
		//Initialization
		$this->lineHeight 		= 5;
		$this->brakeHeight 		= 10;
	}
	
	//Add departure or arrival pages to an existing pdf
	public function generateTravelLetter($type, $visaInfo, $gender, $date, $origin, $destination, $transportType){
		//Check if values are available
		if(!is_array($visaInfo)){
			$passportName 		= "UNKNOWN";
			$quotaPosition 		= "UNKNOWN";
			$greencardExpiry	= "UNKNOWN";
		}else{
			if(isset($visaInfo['passport_name'])){
				$passportName 		= $visaInfo['passport_name'];
			}else{
				$passportName 		= "UNKNOWN";
			}
			if(isset($visaInfo['quota_position'])){
				$quotaPosition 	= $visaInfo['quota_position'];
			}else{
				$quotaPosition 	= "UNKNOWN";
			}
			if(isset($visaInfo['greencard_expiry'])){
				$greencardExpiry	= date('F Y', strtotime($visaInfo['greencard_expiry']));
			}else{
				$greencardExpiry	= "UNKNOWN";
			}
		}
		
		if ($origin == ''){
			$origin = "UNKNOWN";
		}else{
			$origin = ucfirst(trim($origin));
		}
		if ($destination == ''){
			$destination 	= "UNKNOWN";
		}else{
			$destination 	= ucfirst(trim($destination));
		}
		
		if ($transportType == ''){
			$transportType	= "UNKNOWN";
		}
		if ($date == ''){
			$date 			= "UNKNOWN";
		}else{
			//Convert the date to the right format
			$date 			= strtotime($date);
			$travelDate 	= date('d-F-Y', $date);
		}
		
		if(strtolower($gender) == 'male'){
			$genderWord 	= 'he';
			$genderWord2	= 'his';
		}else{
			$genderWord 	= 'she';
			$genderWord2	= 'her';
		}
		
		//Letterdate for departure dates
		if($type == "Departure"){
			//Use today as the date
			$now 			= new \DateTime();
			$letterDate		= $now->format('j-F-Y');
		//Letterdate for arrivals
		}else{
			//If arrival is on Friday or Saturday take the next Monday
			$weekday = date('D', $date);
			if($weekday == 'Fri' || $weekday == 'Sat'){
				$letterDate = date('d-F-Y', strtotime('next Monday', $date));
			//Else take the next day
			}else{
				$letterDate = date('d-F-Y', strtotime('+1 Day', $date));
			}
		}
		
		//Start writing the pdf pages
		$this->AddPage();
		
		$this->SetY(50);

		$lines = [
			$letterDate,
			'',
			'The Comptroller,',
			'Nigerian Immigration Services',
			'Plateau State Command.',
			'',
			'Dear Sir,'
		];
		
		foreach($lines as $line){
			$this->Write($this->lineHeight, $line);
			$this->Ln($this->lineHeight);
		}
		
		$this->SetFont('','U');
		$this->Ln($this->brakeHeight);
		
		$this->Cell(0,0,strtoupper($type)." NOTICE",0,1,'C');
		$this->Ln($this->brakeHeight);
		
		$this->SetFont('','');
		$lines = [
			"We submit documents on behalf of $passportName, occupying the position of '$quotaPosition' with CERPAC until $greencardExpiry, requesting for departure endorsement as $genderWord travels by $transportType from $origin to $destination.",
			"",
			"$type on ... $travelDate ...",
			"",
			"We shall be grateful if $genderWord2 travels are recorded as customarily done.",
			"We accept full immigration responsibilities for $passportName while $genderWord is here in Nigeria.",
			"",
			"Yours faithfully,",
		];
		
		foreach($lines as $line){
			$this->Write($this->lineHeight, $line);
			$this->Ln($this->lineHeight);
		}
		
		//Add the signature
		$signature	= get_attached_file(SIM\getModuleOption(MODULE_SLUG, 'picture_ids')['tc_signature']);
		try{
			$this->Image($signature, null, null, 30);
		}catch (\Exception $e) {
			SIM\printArray("PDF_export.php: $signature is not a valid image");
		}
		
		$lines = [
			"Ibrahim Nathan Aghily Esq.",
			"Travel Coordinator"
		];
		foreach($lines as $line){
			$this->Write($this->lineHeight, $line);
			$this->Ln($this->lineHeight);
		}
		
		//Add passport picture
		if(isset($visaInfo['passport'])){
			$this->AddPage();
			foreach($visaInfo['passport'] as $path){
				$this->printImage($path);
			}
		}
		
		//Quota document
		if($quotaPosition 	!= "UNKNOWN"){
			$quotaDocuments = get_option('quota_documents');
			if(isset($quotaDocuments[$quotaPosition])){
				$quotaDocumentNumber = $quotaDocuments[$quotaPosition];
				
				if(isset($quotaDocuments['quotafiles'][$quotaDocumentNumber])){
					foreach($quotaDocuments['quotafiles'][$quotaDocumentNumber] as $path){
						$this->AddPage();
						$this->printImage($path,-1,-1,200);
					}
				}
			}
		}
	}
}
