<?php
namespace SIM\FORMS;
use SIM;

class EditFormResults extends DisplayFormResults{
	 /**
	 * Update an existing form submission
	 *
	 * @param	bool	$archive	Whether we should archive the submission. Default false
	 *
	 *  @return	true|WP_Error		The result or error on failure
	 */
	public function updateSubmission($archive=false){
		global $wpdb;

		$submissionId	= $this->submission->formresults['id'];
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

		$this->submission->formresults['edittime']	= date("Y-m-d H:i:s");

		//Update the database
		$result = $wpdb->update(
			$this->submissionTableName,
			array(
				'timelastedited'	=> date("Y-m-d H:i:s"),
				'formresults'		=> maybe_serialize($this->submission->formresults),
				'archived'			=> $archive
			),
			array(
				'id'				=> $submissionId,
			),
			array(
				'%s',
				'%s',
				'%d'
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
				SIM\printArray($this->submission->formresults);
			}
		}

		if($archive){
			$this->sendEmail('removed');

			do_action('sim-forms-entry-archived', $this, $submissionId);
		}
		
		return $result;
	}
	
	/**
	 * Auto archive form submission based on the form settings
	 */
	public function autoArchive(){
		//get all the forms
		$this->getForms();
		
		//loop over all the forms
		foreach($this->forms as $form){
			$settings = (array)maybe_unserialize($form->settings);
			
			//check if auto archive is turned on for this form
			if(!isset($settings['autoarchive']) || $settings['autoarchive'] != 'true'){
				continue;
			}

			$this->formName				= $form->name;
			$this->formData				= $form;
			//$this->formData->settings 	= maybe_unserialize(utf8_encode($this->formData->settings));
			$this->formData->settings 	= $settings;

			$splitElementName			= $settings['split'];

			//Get all submissions of this form
			$this->parseSubmissions(null, null, true);
			
			$triggerName	= $this->getElementById($settings['autoarchivefield'], 'name');
			$triggerValue	= $settings['autoarchivevalue'];

			if(!$triggerName || empty($triggerValue)){
				continue;
			}

			$pattern		= '/'.$splitElementName."\[[0-9]+\]\[([^\]]+)\]/i";
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
			
			//loop over all submissions to see if we need to archive them
			foreach($this->submissions as &$this->submission){
				
				$this->submissionId		= $this->submission->id;

				//there is no trigger value found in the results, check multi value array
				if(empty($this->submission->formresults[$triggerName])){
					//loop over all multi values
					foreach($this->submission->formresults[$splitElementName] as $subId=>$sub){
						if(
							isset($sub['archived']) && 		// Archive entry exists
							$sub['archived']		||		// sub is already archived
							(
								empty($sub['from'])	||		// from is empty
								empty($sub['to'])	||		// to is empty
								empty($sub['date'])			// date is empty
							)
						){
							continue;
						}

						//we found a match for a sub entry, archive it
						$val	= $sub[$triggerName];
						if(
							$val == $triggerValue || 						//value is the same as the trigger value or
							(
								strtotime($triggerValue) 	&& 				// trigger value is a date
								strtotime($val) 			&&				// this value is a date
								strtotime($val) < strtotime($triggerValue)	// value is smaller than the trigger value
							)
						){
							$this->submission->formresults[$splitElementName][$subId]['archived'] = true;
							
							//update in db
							$this->checkIfAllArchived($this->submission->formresults[$splitElementName]);
						}
					}
				}else{
					//if the form value is equal to the trigger value it needs to be to be archived
					if($this->submission->formresults[$triggerName] == $triggerValue){
						$this->updateSubmission(true);
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
	public function checkIfAllArchived($data){
		//check if all subfields are archived or empty
		$allArchived = true;

		foreach($data as $d){
			if(
				!isset($d['archived']) 	||	// Archived key does not exist
				!$d['archived']			&&	// Or the value is false
				(
					!empty($d['from'])	&&	// the from field is not empty
					!empty($d['date'])	&&	// the data field is not empty
					!empty($d['to'])		// the to field is not empty
				)
			){
				$allArchived = false;
				break;
			}
		}
		
		//update and mark as archived if all entries are empty or archived
		$this->updateSubmission($allArchived);
	}

	/**
	 * Checks if all sub entries are archived, if so archives the whole
	 *
	 * @param	object	$data	the data to check
	 */
	public function unArchiveAll($id){
		// Get the original submission
		$this->parseSubmissions(null, $id);

		// Update the original
		foreach($this->submissions->formresults[$this->formData->settings['split']] as &$sub){
			$sub['archived']	= false;
		}
		
		//update and mark as archived if all entries are empty or archived
		$this->updateSubmission(false);
	}

	/**
	 * Removes an existing submission from the database
	 *
	 * @param	int	$submissionId		The id of the submission to delete
	 *
	 * @return	int|WP_Error			The number of rows updated, or an WP_Error on error.
	 */
	public function deleteSubmission($submissionId){
		global $wpdb;

		if(!isset($this->formData) || $this->formData == null){
			$this->parseSubmissions(null, $submissionId);
			$this->getForm($this->submissions->form_id);
		}

		$result = $wpdb->delete(
			$this->submissionTableName,
			array(
				'id'		=> $submissionId
			)
		);
		
		if($result === false){
			return new \WP_Error('sim forms', "Submission removal failed");
		}

		$this->sendEmail('removed');

		return $result;
	}
}
