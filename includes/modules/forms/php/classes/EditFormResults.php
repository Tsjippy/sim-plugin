<?php
namespace SIM\FORMS;
use SIM;

class EditFormResults extends DisplayFormResults{
	public $submissionId;
	
	 /**
	 * Update an existing form submission
	 *
	 * @param	bool	$archive	Whether we should archive the submission. Default false
	 *
	 *  @return	true|WP_Error		The result or error on failure
	 */
	public function updateSubmission($archive=false){
		global $wpdb;

		$submissionId	= $this->submission->id;
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
				'archived'			=> $archive,
				'archivedsubs'		=> maybe_serialize($this->submission->archivedsubs)
			),
			array(
				'id'				=> $submissionId,
			),
			array(
				'%s',
				'%s',
				'%d',
				'%s'
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
		global $wpdb;

		//get all the forms
		$this->getForms();
		
		//loop over all the forms
		foreach($this->forms as $form){
			$this->formData	= $form;
			$this->formId	= $form->id;
			$this->formName	= $form->name;
			$this->getForm($form->id);
			
			//check if auto archive is turned on for this form
			if(!isset($form->autoarchive) || !$form->autoarchive){
				continue;
			}

			$splitElementName	= '';
			if(isset($form->split)){
				$form->split				= maybe_unserialize($form->split);
				$splitElementName			= $this->getElementById($form->split[0], 'name');
				$result						= preg_match('/(.*?)\[[0-9]\]\[.*?\]/', $splitElementName, $matches);
				if($result){
					$splitElementName		= $matches[1];
				}
			}

			//Get all submissions of this form
			$this->parseSubmissions(null, null, true, true);
			
			$triggerName	= $this->getElementById($form->autoarchive_el, 'name');
			$triggerValue	= $form->autoarchive_value;

			if(!$triggerName || empty($triggerValue)){
				continue;
			}

			$pattern		= "/.*?\[[0-9]+\]\[([^\]]+)\]/i";
			if(preg_match($pattern, $triggerName, $matches)){
				$triggerName	= $matches[1];
			}
			
			//check if we need to transform a keyword to a date
			$pattern = '/%([^%;]*)%/i';
			//Execute the regex
			preg_match_all($pattern, $triggerValue, $matches);
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
				if(
					!empty($splitElementName) &&								// we should split
					empty($this->submission->formresults[$triggerName]) && 		// we don't have a triggerfield in the results
					isset($this->submission->formresults[$splitElementName])	// but we do have the splitted field
				){
					$archivedCounter	= 0;

					//loop over all multi values
					foreach((array)$this->submission->formresults[$splitElementName] as $subId=>$sub){
						if(
							!is_array($sub)		||
							(isset($sub['archived']) && 	// Archive entry exists
							$sub['archived'])				// sub is already archived
						){
							$archivedCounter++;
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

							// add
							if(empty($this->submission->archivedsubs)){
								$this->submission->archivedsubs	= [$subId];
							}elseif(!in_array($subId, $this->submission->archivedsubs)){
								// only add if not yet there
								$this->submission->archivedsubs[]	= $subId;
							}
							
							//update in db
							$this->checkIfAllArchived();
						}
					}

					if(count((array)$this->submission->formresults[$splitElementName]) == $archivedCounter && !$this->submission->archived){
						// Something went wrong in the past, mark submission as archived
						$result = $wpdb->update(
							$this->submissionTableName,
							array(
								'timelastedited'	=> date("Y-m-d H:i:s"),
								'archived'			=> true
							),
							array(
								'id'				=> $this->submission->id,
							),
							array(
								'%s',
								'%d'
							)
						);
					}
				}else{
					//if the form value is equal to the trigger value it needs to be to be archived
					if(isset($this->submission->formresults[$triggerName]) && $this->submission->formresults[$triggerName] == $triggerValue){
						$this->updateSubmission(true);
					}
				}
			}
		}
	}

	 /**
	 * Checks if all sub entries are archived, if so archives the whole
	 */
	public function checkIfAllArchived(){
		//check if all subfields are archived or empty
		$allArchived = true;

		$splitIds	= $this->formData->split;

		if(!is_array($this->submission->archivedsubs)){
			$this->submission->archivedsubs	= [];
		}

		foreach($splitIds as $id){
			$elementName			= $this->getElementById($id, 'name');

			preg_match('/(.*?)\[[0-9]\]\[.*?\]/', $elementName, $matches);

			if(isset($matches[1])){
				$elementName	= $matches[1];
			}

			if(count($this->submission->formresults[$elementName]) > count($this->submission->archivedsubs)){
				$allArchived = false;
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
		if($this->submission->id != $id){
			// Get the submission
			$this->parseSubmissions(null, $id);
		}

		$this->submission->archivedsubs	= [];
		
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
			$this->getForm($this->submission->form_id);
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
