<?php
namespace SIM\SIMNIGERIA;
use SIM;

//add js special to the travelform
add_filter('sim_form_extra_js', function($js, $formName, $minimized){

	if($formName != 'travel'){
		return $js;
	}

	$path	= plugin_dir_path( __DIR__)."js/travelform.min.js";
	if(!$minimized || !file_exists($path)){
		$path	= plugin_dir_path( __DIR__)."js/travelform.js";
	}

	if(file_exists($path)){
		$js		= file_get_contents($path);
	}

	return $js;
}, 10, 3);

//Single default values used to prefil the travel form
add_filter( 'sim_add_form_defaults', function($defaultValues, $userId){
	$usermeta = get_user_meta($userId);
	
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
		$values 				= unserialize($usermeta[$field][0]);
		$defaultValues[$field] 	= '';
		
		if(is_array($values)){
			//if we are dealing with muliples without nested values
			if(is_numeric(array_key_first($values)) && !is_array(array_values($values)[0])){
				$defaultValues[$field] = implode("\n", $values);
				//also add the count
				$defaultValues[$field.'_count'] = count($values);
			}else{
				$lastValue	= array_key_last($values);
				//Loop over the values
				foreach($values as $key=>$value){
					//If this too is an array
					if(is_array($value)){
						//convert array to string
						if(is_numeric(array_key_first($value))){
							$defaultValues[$field.'_'.$key] = implode("\n",$value);
							//also add the count
							$defaultValues[$field.'_'.$key.'_count'] = count($value);
						}else{
							foreach($value as $k=>$val){
								$defaultValues[$field.'_'.$key.'_'.$k] = $value;
							}
						}
					//this is not an array
					}elseif(!is_numeric($key)){
						$defaultValues[$field.'_'.$key] = $value;
					}else{
						$defaultValues[$field] .= $value;
						if($key != $lastValue){
							$defaultValues[$field] .= "\n";
						}
					}
				}
			}
		}else{
			$defaultValues[$field] = $usermeta[$field][0];
		}
	}

	return $defaultValues;
},10,2);

//Multi default values used to prefil the travel form elements with multivalues like checkboxes ans dropdowns
add_filter( 'sim_add_form_multi_defaults', function($defaultArrayValues, $userId){
	$usermeta = get_user_meta($userId);
	
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
				$defaultArrayValues[$field] = $values;
			}else{
				//Loop over the values
				foreach($values as $key=>$value){
					//If this too is an array
					if(is_array($value) && !empty($value)){
						//check if not dictionary
						if(is_numeric(array_keys($value)[0])){
							//numeric key so add the array as a whole
							$defaultArrayValues[$field.'_'.$key] = $value;
						}else{
							SIM\printArray('I have not added fieldvalue: "');
							SIM\printArray($value);
						}
					}
				}
			}
		}
	}
	
	foreach(SIM\getUserAccounts(false, true) as $user){
		$defaultArrayValues['all_users'][$user->ID] = $user->display_name;
	}

	foreach(SIM\getUserAccounts() as $user){
		$defaultArrayValues['missionaries'][$user->ID] = $user->display_name;
	}

	foreach(SIM\getUserAccounts(true) as $user){
		$defaultArrayValues['families'][$user->ID] = $user->display_name;
	}

	//emails
	//$all_users = get_users();
	foreach (get_users() as $user) {
		$defaultArrayValues['emails'][$user->ID]				= $user->user_email;
		$phonenumbers											= (array)get_user_meta($user->ID,'phonenumbers',true);
		$defaultArrayValues['All phonenumbers'][$user->ID]	= implode(";", $phonenumbers);
	}

	return $defaultArrayValues;
},1,2);

//Transform table data from travelform
add_filter('sim_transform_formtable_data',function($string,$field_name){
	if(in_array($field_name,['name','driver','passengers'])){
		if($field_name == 'passengers'){
			$output		= '';
			$string 	= explode(',',$string);
			$lastKey 	= array_key_last($string);
			foreach($string as $key=>$value){
				//assume its a userid if it is a number, then transform it to a clickable link
				if(is_numeric($value)){
					$output 			 = SIM\USERPAGE\getUserPageLink($value);
					if($output){
						if($key != $lastKey){
							$output .= ", ";
						}
					}else{
						$output .= $value;
					}
				}else{
					$output .= $value;
				}
			}
		}elseif(is_numeric($string)){
			$output				= SIM\USERPAGE\getUserPageLink($string);
			if(!$output){
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
add_filter('sim_formdata_retrieval_query', function($query, $userId, $formName){
	if($formName == 'travel' && $_GET['onlyown'] == 'true'){
		//remove userid from query
		$query	= str_replace(" userid='$userId' and", '', $query);
	}

	return $query;
},10,3);

//then remove all unwanted data
add_filter('sim_retrieved_formdata', function($formdata, $userId, $formName){
	if($formName == 'travel' && $_GET['onlyown'] == 'true'){
		//remove userid from query
		foreach($formdata as $key=>$entry){
			$passengers	= (array)unserialize($entry->formresults)['passengers'];
			//if this entry does not belong to this user and the user is no passenger
			if($entry->userid != $userId && !in_array($userId, $passengers)){
				//remove
				unset($formdata[$key]);
			}
		}
	}

	return $formdata;
}, 10, 3);

//Add a print button action
add_filter('sim_form_actions', function($actions){
	$actions[]	= 'print';

	return $actions;
});

//Add a print button html
add_filter('sim_form_actions_html', function($buttonsHtml, $fieldValues=null, $index=-1, $displayFormResults){
	if(!in_array($index, [1,4])){
		return $buttonsHtml;
	}

	if($fieldValues == null || !is_numeric($fieldValues['user_id'])){
		$buttonsHtml['print']	= '';
	}else{
		$tripDetails		= $displayFormResults->getSubmissionData(null, $fieldValues['id']);
		if(empty($tripDetails)){
			return $buttonsHtml;
		}

		$tripDetails	= maybe_unserialize($tripDetails[0]->formresults)['travel'];

		//if this is a roundtrip check if it is longer than 7 days
		if($fieldValues['roundtrip'][0] == 'Yes'){
			$start = $tripDetails[1]['date'];

			if(is_array($tripDetails[6]) && !empty($tripDetails[6]['date'])){
				$end = $tripDetails[6]['date'];
			}elseif(is_array($tripDetails[5]) && !empty($tripDetails[5]['date'])){
				$end = $tripDetails[5]['date'];
			}else{
				$end = $tripDetails[4]['date'];
			}

			$dayDiff	= (strtotime($end)-strtotime($start))/ 86400;
			if($dayDiff < 7){
				return $buttonsHtml;
			}
		}
		
		//Add the current user to the passengers
		$passengers			= implode(',', (array)$fieldValues['passengers']);
		if(!empty($passengers)){
			$passengers .= ',';
		}
		$passengers		   .= $fieldValues['user_id'];
		
		$destination		= str_replace('_', ' ', $tripDetails[$index]['to']);
		if($fieldValues['traveltype'][0] == 'International'){
			$transportType	= 'air';
		}else{
			$transportType	= 'road';
			if(!empty($tripDetails[$index+2]['to'])){
				$destination		= str_replace('_', ' ', $tripDetails[$index+2]['to']);
			}elseif(!empty($tripDetails[$index+1]['to'])){
				$destination		= str_replace('_', ' ', $tripDetails[$index+1]['to']);
			}else{
				$destination		= str_replace('_', ' ', $tripDetails[$index]['to']);
			}
		}

		if($index == 1){
			$travelType			= 'Departure';
		}else{
			$travelType			= 'Arrival';
		}
		
		$travelDate		= $tripDetails[$index]['date'];
		
		$origin				= str_replace('_', ' ', $tripDetails[$index]['from']);

		ob_start();
		?>
		<form action="" method="post">
			<input type="hidden" name="action" value="printtravelletter">
			<input type="hidden" name="submissionid"	value="<?php echo $fieldValues['id'];?>">
			<input type="hidden" name="passengers"		value="<?php echo $passengers;?>">
			<input type="hidden" name="origin"			value="<?php echo $origin;?>">
			<input type="hidden" name="destination"		value="<?php echo $destination;?>">
			<input type="hidden" name="transporttype"	value="<?php echo $transportType;?>">
			<input type="hidden" name="traveltype"		value="<?php echo $travelType;?>">
			<input type="hidden" name="travel_date"		value="<?php echo $travelDate;?>">
			<input type="submit" class="button" value="Print">
		</form>
		<?php
		$buttonsHtml['print']	= trim(ob_get_clean());
	}
	return $buttonsHtml;
}, 10, 4);

//print travel letters if post
add_action('sim_formtable_POST_actions',function(){
	if($_POST['action'] == 'printtravelletter'){
		generateImmigrationLetters();
		wp_die();
	}
});

function generateImmigrationLetters(){
	$userIds		= explode(',',$_POST['passengers']);
	$origin			= sanitize_text_field($_POST['origin']);
	$destination	= str_replace('_', ' ', sanitize_text_field($_POST['destination']));
	$transportType	= $_POST['transporttype'];
	$traveltType	= $_POST['traveltype'];
	$travelDate		= $_POST['travel_date'];
	
	$pdf = new ImmigrationLetter();
	
	try{
		$pdf->AddFont('NSimSun','');
		$pdf->SetFont('NSimSun','',12);
	}catch (\Exception $e){
		SIM\printArray('Loading NSimSun font failed.');
		$pdf->SetFont('Arial','B',12);
	}
	
	foreach($userIds as $userId){
		$visaInfo 			= get_user_meta( $userId, "visa_info",true);
		$gender				= get_user_meta($userId, 'gender', true)[0];
			
		$pdf->generateTravelLetter($traveltType, $visaInfo, $gender, $travelDate, $origin, $destination, $transportType);
	}
	
	$pdf->printPdf();
}

add_shortcode( 'quotadocuments', function (){
	wp_enqueue_script( 'sim_quotajs');
	
	//Only show if not editing a user
	if(!isset($_GET['id'])){
		$quotaDocuments = (array)get_option('quota_documents');
		
		if(!isset($quotaDocuments['quotafiles']) || !is_array($quotaDocuments['quotafiles'])){
			$quotaDocuments['quotafiles'] = [1 => ""];
		}
		
		ob_start();
		?>
		<div id="quota_documents" style="margin-top:30px;">
			<button class="button tablink active" 	id="show_quota_document_upload" data-target="quota_document_upload">Show quota upload</button>
			<button class="button tablink" 			id="show_quota_mapping" data-target="quota_mapping">Quoto mapping</button>
			<div style="max-width: 400px;">
				<h3>Quota documents</h3>
				<div id="quota_document_upload">
					<p style='margin:20px 0px 0px 0px'>Upload the document quota</p>
					<?php
					echo quoataDocumentUpload($quotaDocuments);
					?>
				</div>
				<div id="quota_mapping" class='hidden'>
					<?php
					//Add a select box for each quota position, containing the available quota documents
					foreach(QUOTANAMES as $document){
						?>
						<div style="margin-top:20px;">
							<label><?php echo $document;?></label>
							<select name='quota_documents[<?php echo $document;?>]'>
								<option value=''>---</option>
								<?php
								foreach($quotaDocuments['quotafiles'] as $key=>$quotaDocument){
									if($quotaDocuments[$document] == $key){
										$selected	= " selected";
									}else{
										$selected	= "";
									}
									echo "<option value='$key'$selected>Document $key</option>";
								}
								?>
							</select>
						</div>
						<?php
					}
					echo SIM\addSaveButton('update_visa_documents', 'Update visa documents');
					?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
});

function quoataDocumentUpload($quotaDocuments){
	//Load js
	wp_enqueue_script( 'sim_quotajs');
	
	ob_start();
	?>
	<form>
		<input type="hidden" name='action' value='update_visa_documents'>
		<div class="clone_divs_wrapper">
		<?php
		//Loop over quota_documents array
		foreach($quotaDocuments['quotafiles'] as $key=>$quotaDocument){
			if(count($quotaDocuments['quotafiles'])==1){
				//No remove button if there is only one quota_document
				$button = '';
			}else{
				$button = '<button type="button" class="button remove" style="flex: 1;">-</button>';
			}
				
			if ($key == array_key_last ($quotaDocuments['quotafiles'])){
				//Only add plus button to last quota_document
				$button .= '<button type="button" class="button add" style="flex: 1;">+</button>';
			}
			
			?>
			<div id="quota_document_div_<?php echo $key;?>" class="quota_document_div clone_div" data-divid="<?php echo $key;?>" data-type="quota_documents" style="margin-top:20px;">
				<label class="quotalabel">Quota document <?php echo $key;?></label><br>
				<label>
					Amount of quotas
					<input type="number" class="quota_count" value="<?php echo $key;?>">
				</label>
				<div class="buttonwrapper" style="width:100%; display: flex;">
					<?php
					$name	= "quota_documents[quotafiles][$key]";

					$uploader = new SIM\FILEUPLOAD\FileUpload('', $name);
					echo $uploader->getUploadHtml($name, 'visa_uploads', true);
					echo $button;
					?>
				</div>
			</div>
			<?php
		}
		?>
		</div>
		<?php
		echo SIM\addSaveButton('update_visa_documents', 'Update quota documents');
		?>
	</form>
	<?php
	
	return ob_get_clean();
}