<?php
namespace SIM;

//Single default values used to prefil the travel form
add_filter( 'add_form_defaults', function($default_values,$user_id){
	$usermeta = get_user_meta($user_id);
	
	//fields to retrieve from usermeta
	$fields = [
		'nickname',
		'first_name',
		'last_name',
		'login_count',
		'gender',
		'sending_office',
		'arrival_date',
		'location',
		'birthday',
		'family',
		'financial_account_id',
		'phonenumbers',
		'last_login_date',
		'profile_picture',
	];
	
	//loop over the fields
	foreach($fields as $field){
		//if the field value is an array
		$values = unserialize($usermeta[$field][0]);
		$default_values[$field] = '';
		
		if(is_array($values)){
			//if we are dealing with muliples without nested values
			if(is_numeric(array_key_first($values)) and !is_array(array_values($values)[0])){
				$default_values[$field] = implode("\n",$values);
				//also add the count
				$default_values[$field.'_count'] = count($values);
			}else{
				$lastvalue	= array_key_last($values);
				//Loop over the values
				foreach($values as $key=>$value){
					//If this too is an array
					if(is_array($value)){
						//convert array to string
						if(is_numeric(array_key_first($value))){
							$default_values[$field.'_'.$key] = implode("\n",$value);
							//also add the count
							$default_values[$field.'_'.$key.'_count'] = count($value);
						}else{
							foreach($value as $k=>$val){
								$default_values[$field.'_'.$key.'_'.$k] = $value;
							}
						}
					//this is not an array
					}elseif(!is_numeric($key)){
						$default_values[$field.'_'.$key] = $value;
					}else{
						$default_values[$field] .= $value;
						if($key != $lastvalue)	$default_values[$field] .= "\n";
					}
				}
			}
		}else{
			$default_values[$field] = $usermeta[$field][0];
		}
	}

	return $default_values;
},10,2);

//Multi default values used to prefil the travel form elements with multivalues like checkboxes ans dropdowns
add_filter( 'add_form_multi_defaults', function($default_array_values,$user_id){
	$usermeta = get_user_meta($user_id);
	
	//fields to retrieve from usermeta
	$fields = [
		'location',
		'family',
		'phonenumbers'
	];
	
	//loop over the fields
	foreach($fields as $field){
		//if the field value is an array
		$values = unserialize($usermeta[$field][0]);
		if(is_array($values)){
			//check if not dictionary
			if(is_numeric(array_keys($values)[0])){
				//numeric key so add the array as a whole
				$default_array_values[$field] = $values;
			}else{
				//Loop over the values
				foreach($values as $key=>$value){
					//If this too is an array
					if(is_array($value)){
						//check if not dictionary
						if(is_numeric(array_keys($value)[0])){
							//numeric key so add the array as a whole
							$default_array_values[$field.'_'.$key] = $value;
						}else{
							print_array('I have not added fieldvalue: "');
							print_array($value);
						}
					}
				}
			}
		}
	}
	
	foreach(get_missionary_accounts($return_family=false,$adults=true,$local_nigerians=true) as $user){
		$default_array_values['all_users'][$user->ID] = $user->display_name;
	}

	foreach(get_missionary_accounts() as $user){
		$default_array_values['missionaries'][$user->ID] = $user->display_name;
	}

	foreach(get_missionary_accounts($return_family=true) as $user){
		$default_array_values['families'][$user->ID] = $user->display_name;
	}

	//emails
	//$all_users = get_users();
	foreach (get_users() as $user) {
		$default_array_values['emails'][$user->ID]				= $user->user_email;
		$phonenumbers											= (array)get_user_meta($user->ID,'phonenumbers',true);
		$default_array_values['All phonenumbers'][$user->ID]	= implode("\n",$phonenumbers);
	}

	return $default_array_values;
},1,2);

//Transform table data from travelform
add_filter('transform_formtable_data',function($string,$field_name){
	//print_array($field_name);
	if(in_array($field_name,['name','driver','passengers'])){
		if($field_name == 'passengers'){
			$output	= '';
			$string = explode(',',$string);
			$last_key = array_key_last($string);
			foreach($string as $key=>$value){
				//assume its a userid if it is a number, then transform it to a clickable link
				if(is_numeric($value)){
					$missionary_page_id	= get_user_meta($value,"missionary_page_id",true);
					if(is_numeric($missionary_page_id)){
						$page_url 			= get_post_permalink($missionary_page_id);
						$output 				.= "<a class='missionary_link' href='$page_url'>".get_userdata($value)->display_name."</a>";
						if($key != $last_key) $output .= ", ";
					}else{
						$output .= $value;
					}
				}else{
					$output .= $value;
				}
			}
		}elseif(is_numeric($string)){
			$missionary_page_id	= get_user_meta($string,"missionary_page_id",true);
			if(is_numeric($missionary_page_id)){
				$page_url 			= get_post_permalink($missionary_page_id);
				$output 			= "<a class='missionary_link' href='$page_url'>".get_userdata($string)->display_name."</a>";
			}else{
				$output	= $string;
			}
		}else{
			$output = $string;
		}
	}else{
		$output = $string;
	}
	return $output;
},10,2);

//first make sure we request all the data
add_filter('formdata_retrieval_query', function($query, $user_id, $datatype){
	if($datatype == 'travel' and $_GET['onlyown'] == 'true'){
		//remove userid from query
		$query	= str_replace(" userid='$user_id' and", '', $query);
	}

	return $query;
},10,3);

//then remove all unwanted data
add_filter('retrieved_formdata', function($formdata, $user_id, $datatype){
	if($datatype == 'travel' and $_GET['onlyown'] == 'true'){
		//remove userid from query
		foreach($formdata as $key=>$entry){
			$passengers	= (array)unserialize($entry->formresults)['passengers'];
			//if this entry does not belong to this user and the user is no passenger
			if($entry->userid != $user_id and !in_array($user_id, $passengers)){
				//remove
				unset($formdata[$key]);
			}
		}
	}

	return $formdata;
},10,3);

//Add a print button
add_filter('form_actions', function($buttons_html,$field_values=null, $index=-1){
	if(!in_array($index,[1,4])) return $buttons_html;

	if($field_values == null){
		$buttons_html['print']	= '';
	}else{
		$tripdetails		= $field_values['travel'];

		//if this is a roundtrip check if it is longer than 7 days
		if($field_values['roundtrip'][0] == 'Yes'){
			$start = $tripdetails[1]['date'];

			if(is_array($tripdetails[6])){
				$end = $tripdetails[6]['date'];
			}elseif(is_array($tripdetails[5])){
				$end = $tripdetails[5]['date'];
			}else{
				$end = $tripdetails[4]['date'];
			}

			$day_diff	= (strtotime($end)-strtotime($start))/ 86400;
			if($day_diff<7) return $buttons_html;
		}
		
		//Add the current user to the passengers
		$passengers			= implode(',',(array)$field_values['passengers']);
		if(!empty($passengers))	$passengers .= ',';
		$passengers		   .= $field_values['userid'];
		
		$destination		= str_replace('_',' ',$tripdetails[$index]['to']);
		if($field_values['traveltype'][0] == 'International'){
			$transporttype	= 'air';
		}else{
			$transporttype	= 'road';
			if(!empty($tripdetails[$index+2])){
				$destination		= str_replace('_',' ',$tripdetails[$index+2]['to']);
			}elseif(!empty($tripdetails[$index+1])){
				$destination		= str_replace('_',' ',$tripdetails[$index+1]['to']);
			}
		}

		if($index == 1){
			$traveltype			= 'Departure';
		}else{
			$traveltype			= 'Arrival';
		}
		
		$travel_date		= $tripdetails[$index]['date'];
		
		$origin				= str_replace('_',' ',$tripdetails[$index]['from']);

		ob_start();
		?>
		<form action="" method="post">
			<input type="hidden" name="action" value="printtravelletter">
			<input type="hidden" name="submissionid"	value="<?php echo $field_values['id'];?>">
			<input type="hidden" name="passengers"		value="<?php echo $passengers;?>">
			<input type="hidden" name="origin"			value="<?php echo $origin;?>">
			<input type="hidden" name="destination"		value="<?php echo $destination;?>">
			<input type="hidden" name="transporttype"	value="<?php echo $transporttype;?>">
			<input type="hidden" name="traveltype"		value="<?php echo $traveltype;?>">
			<input type="hidden" name="travel_date"		value="<?php echo $travel_date;?>">
			<input type="submit" class="button" value="Print">
		</form> 
		<?php
		$buttons_html['print']	= ob_get_clean();
	}
	return $buttons_html;
},10,3);

//print travel letters if post
add_action('formtable_POST_actions',function(){
	//print_array($_POST);
	
	if($_POST['action'] == 'printtravelletter'){
		generate_immigration_letters();
		wp_die();
	}
});

function generate_immigration_letters(){
	$user_ids		= explode(',',$_POST['passengers']);
	$origin			= sanitize_text_field($_POST['origin']);
	$destination	= str_replace('_',' ',sanitize_text_field($_POST['destination']));
	$transporttype	= $_POST['transporttype'];
	$travelttype	= $_POST['travelttype'];
	$travel_date	= $_POST['travel_date'];
	
	$pdf = new IMMIGRATION_LETTER();
	
	try{
		$pdf->AddFont('NSimSun','');
		$pdf->SetFont('NSimSun','',12);
	}catch (\Exception $e){
		print_array('Loading NSimSun font failed.');
		$pdf->SetFont('Arial','B',12);
	}
	
	foreach($user_ids as $user_id){
		$visa_info 			= get_user_meta( $user_id, "visa_info",true);
		$gender				= get_user_meta($user_id,'gender',true);
			
		$pdf->generate_travel_letter($travelttype,$visa_info,$gender,$travel_date,$origin,$destination,$transporttype);
	}
	
	$pdf->printpdf();
}

class IMMIGRATION_LETTER extends \PDF_HTML{
	function __construct($orientation='P', $unit='mm', $format='A4'){
		//Call parent constructor
		parent::__construct($orientation,$unit,$format);
		//Initialization
		$this->line_height 		= 5;
		$this->brake_height 	= 10;
	}
	
	//Add departure or arrival pages to an existing pdf
	function generate_travel_letter($type,$visa_info,$gender,$date,$origin,$destination,$transporttype){
		global $TravelCoordinatorSignature;
		
		//Check if values are available
		if(!is_array($visa_info)){
			$passport_name 		= "UNKNOWN";
			$quota_position 	= "UNKNOWN";
			$greencard_expiry	= "UNKNOWN";
		}else{
			if(isset($visa_info['passport_name'])){
				$passport_name 		= $visa_info['passport_name'];
			}else{
				$passport_name 		= "UNKNOWN";
			}
			if(isset($visa_info['quota_position'])){
				$quota_position 	= $visa_info['quota_position'];
			}else{
				$quota_position 	= "UNKNOWN";
			}
			if(isset($visa_info['greencard_expiry'])){
				$greencard_expiry	= date('F Y', strtotime($visa_info['greencard_expiry']));
			}else{
				$greencard_expiry	= "UNKNOWN";
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
		
		if ($transporttype == '')	$transporttype	= "UNKNOWN";
		if ($date == ''){
			$date 			= "UNKNOWN";
		}else{
			//Convert the date to the right format
			$date 			= strtotime($date);
			$travel_date 	= date('d-F-Y', $date);
		}
		
		if($gender == 'male'){
			$gender_word 	= 'he';
			$gender_word2	= 'his';
		}else{
			$gender_word 	= 'she';
			$gender_word2	= 'her';
		}
		
		//Letterdate for departure dates
		if($type == "Departure"){
			//Use today as the date
			$now 			= new \DateTime();
			$letterdate		= $now->format('j-F-Y');
		//Letterdate for arrivals
		}else{
			//If arrival is on Friday or Saturday take the next Monday
			$weekday = date('D', $date);
			if($weekday == 'Fri' or $weekday == 'Sat'){
				$letterdate = date('d-F-Y', strtotime('next Monday', $date));
			//Else take the next day
			}else{
				$letterdate = date('d-F-Y', strtotime('+1 Day', $date));
			}
		}
		
		//Start writing the pdf pages
		$this->AddPage();
		
		$this->SetY(50);

		$lines = [
			$letterdate,
			'',
			'The Comptroller,',
			'Nigerian Immigration Services',
			'Plateau State Command.',
			'',
			'Dear Sir,'
		];
		
		foreach($lines as $line){
			$this->Write($this->line_height,$line);
			$this->Ln($this->line_height);
		}
		
		$this->SetFont('','U');
		$this->Ln($this->brake_height);
		
		$this->Cell(0,0,strtoupper($type)." NOTICE",0,1,'C');
		$this->Ln($this->brake_height);
		
		$this->SetFont('','');
		$lines = [
			"We submit documents on behalf of $passport_name, occupying the position of '$quota_position' with CERPAC until $greencard_expiry, requesting for departure endorsement as $gender_word travels by $transporttype from $origin to $destination.",
			"",
			"$type on ... $travel_date ...",
			"",
			"We shall be grateful if $gender_word2 travels are recorded as customarily done.",
			"We accept full immigration responsibilities for $passport_name while $gender_word is here in Nigeria.",
			"",
			"Yours faithfully,",
		];
		
		foreach($lines as $line){
			$this->Write($this->line_height,$line);
			$this->Ln($this->line_height);
		}
		
		//Add the signature
		try{
			$this->Image($TravelCoordinatorSignature, null, null, 30);
		}catch (\Exception $e) {
			print_array("PDF_export.php: $TravelCoordinatorSignature is not a valid image");
		}
		
		$lines = [
			"Ibrahim Nathan Aghily Esq.",
			"Travel Coordinator"
		];
		foreach($lines as $line){
			$this->Write($this->line_height,$line);
			$this->Ln($this->line_height);
		}
		
		//Add passport picture
		if(isset($visa_info['passport'])){
			foreach($visa_info['passport'] as $path){
				$this->print_image($path);
			}
		}
		
		//Quota document
		if($quota_position 	!= "UNKNOWN"){
			$quota_documents = get_option('quota_documents');
			//print_array($quota_documents);
			if(isset($quota_documents[$quota_position])){
				$quota_document_number = $quota_documents[$quota_position];
				//print_array("quota_document_number is $quota_document_number");
				
				if(isset($quota_documents['quotafiles'][$quota_document_number])){
					//print_array($quotadocuments);
					foreach($quota_documents['quotafiles'][$quota_document_number] as $path){
						$this->print_image($path,-1,-1,200);
					}
				}
			}				
		}
	}
}
