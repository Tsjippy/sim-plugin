<?php
namespace SIM\FORMS;
use SIM;

class EditFormResults extends DisplayFormResults{

	function __construct(){
		parent::__construct();
	}

	/**
	 * Update an existing form submission
	 * 
	 * @param	bool	$archive	Whether we should archive the submission. Default false
	 * 
	 *  @return	true|WP_Error		The result or error on failure
	 */
	function updateSubmissionData($archive=false){
		global $wpdb;

		$submissionId	= $this->formResults['id'];
		if(!is_numeric($submissionId)){
			if(is_numeric($this->submissionId)){
				$submissionId	= $this->submissionId;
			}elseif(is_numeric($_POST['submissionid'])){
				$submissionId	= $_POST['submissionid'];
			}else{
				SIM\printArray('No submission id found');
				return false;
			}
		}	

		$this->formResults['edittime']	= date("Y-m-d H:i:s");

		//Update the database
		$result = $wpdb->update(
			$this->submissionTableName, 
			array(
				'timelastedited'	=> date("Y-m-d H:i:s"),
				'formresults'		=> maybe_serialize($this->formResults),
				'archived'			=> $archive
			),
			array(
				'id'				=> $submissionId,
			)
		);
		
		if($wpdb->last_error !== ''){
			$message	= $wpdb->print_error();
			if(defined('REST_REQUEST')){
				return new \WP_Error('form error', $message);
			}else{
				SIM\printArray($message);
			}
		}elseif(!$result){
			$message	= "No row with id $submissionId found";
			if(defined('REST_REQUEST')){
				return new \WP_Error('form error', $message);
			}else{
				SIM\printArray($message);
				SIM\printArray($this->formResults);
			}
		}
		
		return $result;
	}
	
	/**
	 * Auto archive form submission based on the form settings
	 */
	function autoArchive(){
		//get all the forms
		$this->getForms();
		
		//loop over all the forms
		foreach($this->forms as $form){
			$settings = maybe_unserialize($form->settings);
			
			//check if auto archive is turned on for this form
			if($settings['autoarchive'] != 'true'){
				continue;
			}

			$this->formName				= $form->name;
			$this->formData				= $form;
			$this->formData->settings 	= maybe_unserialize(utf8_encode($this->formData->settings));

			$fieldMainName		= $settings['split'];
			
			//Get all submissions of this form
			$this->getSubmissionData(null, null, true);
			
			$triggerName	= $this->getElementById($settings['autoarchivefield'], 'name');
			$triggerValue	= $settings['autoarchivevalue'];
			$pattern		= '/'.$fieldMainName."\[[0-9]+\]\[([^\]]+)\]/i";
			if(preg_match($pattern, $triggerName,$matches)){
				$triggerName	= $matches[1];
			}
			
			//check if we need to transform a keyword to a date 
			$pattern = '/%([^%;]*)%/i';
			//Execute the regex
			preg_match_all($pattern, $triggerValue,$matches);
			if(!is_array($matches[1])){
				SIM\printArray($matches[1]);
			}else{
				foreach((array)$matches[1] as $keyword){
					//If the keyword is a valid date keyword
					if(strtotime($keyword)){
						//convert to date
						$triggerValue = date("Y-m-d", strtotime(str_replace('%', '', $triggerValue)));
					}
				}
			}
			
			//loop over all submissions to see if we need to archive
			foreach($this->submissionData as &$subData){
				$this->formResults		= $subData->formresults;
				
				$this->submissionId		= $subData->id;

				//there is no trigger value found in the results, check multi value array
				if(empty($this->formResults[$triggerName])){
					//loop over all multi values
					foreach($this->formResults[$fieldMainName] as $subId=>$sub){
						//we found a match for a sub entry, archive it
						$val	= $sub[$triggerName];						
						if(
							$val == $triggerValue || 						//value is the same as the trigger value or
							(
								strtotime($triggerValue) && 				// trigger value is a date
								strtotime($val) &&							// this value is a date
								strtotime($val) < strtotime($triggerValue)	// value is smaller than the trigger value
							)
						){
							$this->formResults[$fieldMainName][$subId]['archived'] = true;
							
							//update in db
							$this->checkIfAllArchived($this->formResults[$fieldMainName]);
						}
					}
				}else{
					//if the form value is equal to the trigger value it needs to be to be archived
					if($this->formResults[$triggerName] == $triggerValue){
						//Check if we are dealing with an subvalue
						if($subData->sub_id){
							$subData->archived	= true;
							$this->checkIfAllArchived($subData);
						}else{
							$this->updateSubmissionData(true);
						}
					}
				}
			}
		}
	}

	/**
	 * Checks if all sub entries are archived, if so archives the whole
	 * 
	 * @param	object	$data	the data to check
	 */
	function checkIfAllArchived($data){
		//check if all subfields are archived or empty
		$allArchived = true;

		// check if we have a subsubmission who is not yet archived
		foreach($this->submissionData as $submission){
			// If this submission belomgs to the same as the given submission and it is not archived
			if($submission->id == $data->id && !$submission->archived){
				$allArchived = false;
				break;
			}
		}

		// Get the original submission
		$this->getSubmissionData(null, $submission->id);

		// Update the original
		$this->formResults[$this->formData->settings['split']][$data->sub_id]['archived']	= true;
		
		//update and mark as archived if all entries are empty or archived
		$this->updateSubmissionData($allArchived);
	}
}