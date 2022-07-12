<?php
namespace SIM\FORMS;
use SIM;
use WP_Embed;
use WP_Error;

class SaveFormSettings extends SimForms{
	use CreateJs;

	function __construct(){
		parent::__construct();
	}

	/**
	 * Change an existing form element
	 * 
	 * @param	object|array	$element	The new element data
	 * 
	 * @return	true|WP_Error				The result or error on failure
	 */
	function updateFormElement($element){
		global $wpdb;
		
		//Update element
		$wpdb->update(
			$this->elTableName, 
			(array)$element, 
			array(
				'id'		=> $element->id,
			),
		);

		if($this->formData == null){
			$this->getForm($_POST['formid']);
		}

		//Update form version
		$result = $wpdb->update(
			$this->tableName, 
			['version'	=> $this->formData->version+1], 
			['id'		=> $this->formData->id],
		);
		
		if($wpdb->last_error !== ''){
			return new WP_Error('forms', $wpdb->last_error());
		}

		return $result;
	}

	/**
	 * Inserts a new element in the db
	 * 
	 * @param	object|array	$element	The new element to insert
	 * 
	 * @return	int							The new element id
	 */
	function insertElement($element){
		global $wpdb;
		
		$wpdb->insert(
			$this->elTableName, 
			(array)$element
		);

		return $wpdb->insert_id;
	}

	/**
	 * Change the priority of an element
	 * 
	 * @param	object|array	$element	The element to change the priority of
	 * 
	 * @return	array|WP_Error				The result or error on failure
	 */
	function updatePriority($element){
		global $wpdb;

		//Update the database
		$result = $wpdb->update($this->elTableName, 
			array(
				'priority'	=> $element->priority
			), 
			array(
				'id'		=> $element->id
			),
		);
		
		if($wpdb->last_error !== ''){
			return new WP_Error('forms', $wpdb->print_error());
		}

		return $result;
	}

	/**
	 * Change the order of form elements
	 * 
	 * @param	int				$oldPriority	The old priority of the element
	 * @param	int				$newPriority	The new priority of the element
	 * @param	object|array	$element		The element to change the priority of
	 */
	function reorderElements($oldPriority, $newPriority, $element, $formId='') {
		if ($oldPriority == $newPriority){
			return;
		}

		// Get all elements of this form
		$this->getAllFormElements('priority', $formId);

		if(empty($element)){
			foreach($this->formElements as $el){
				if($el->priority == $oldPriority){
					$element = $el;
					break;
				}
			}
		}

		//No need to reorder if we are adding a new element at the end
		if(count($this->formElements) == $newPriority){
			// First element is the element without priority
			$el				= $this->formElements[0];
			$el->priority	= $newPriority;
			$this->updatePriority($el);
			return;
		}
		
		//Loop over all elements and give them the new priority
		foreach($this->formElements as $el){
			if($el->name == $element->name){
				$el->priority	= $newPriority;
				$this->updatePriority($el);
			}elseif($oldPriority == -1){
				if($el->priority >= $newPriority){
					$el->priority++;
					$this->updatePriority($el);
				}
			}elseif(
				$oldPriority > $newPriority		&& 	//we are moving an element upward
				$el->priority >= $newPriority	&&		// current priority is bigger then the new prio
				$el->priority < $oldPriority			// current priority is smaller than the old prio
			){
				$el->priority++;
				$this->updatePriority($el);
			}elseif(
				$oldPriority < $newPriority		&& 	//we are moving an element downward
				$el->priority > $oldPriority	&&		// current priority is bigger then the old prio
				$el->priority < $newPriority			// current priority is smaller than the new prio
			){
				$el->priority--;
				$this->updatePriority($el);
			}
		}
	}
	
	/**
	 * Get formresults of the current form
	 * 
	 * @param	int		$userId			Optional the user id to get the results of. Default null
	 * @param	int		$submissionId	Optional a specific id. Default null
	 * 
	 * 
	 */
	function getSubmissionData($userId=null, $submissionId=null, $all=false){
		global $wpdb;
		
		$query				= "SELECT * FROM {$this->submissionTableName} WHERE form_id={$this->formData->id}";
		if(is_numeric($submissionId)){
			$query .= " and id='$submissionId'";
		}elseif(is_numeric($userId)){
			$query .= " and userid='$userId'";
		}
		
		if(!$this->showArchived && $submissionId == null){
			$query .= " and archived=0";
		}

		// Limit the amount to 100
		if(isset($_POST['pagenumber']) && is_numeric($_POST['pagenumber'])){
			$this->currentPage	= $_POST['pagenumber'];

			if(isset($_POST['prev'])){
				$this->currentPage--;
			}
			if(isset($_POST['next'])){
				$this->currentPage++;
			}
			$start	= $this->currentPage * $this->pageSize;
		}else{
			$start				= 0;
			$this->currentPage	= 0;
		}

		$query	= apply_filters('sim_formdata_retrieval_query', $query, $userId, $this->formName);

		// Get the total
		$result	= $wpdb->get_results(str_replace('*', 'count(*) as total', $query));
		if(empty($result)){
			$this->total	= 0;
		}else{
			$this->total	= $result[0]->total;
		}

		if(!$all){
			$query	.= " LIMIT $start, $this->pageSize";
		}

		// Get results
		$result	= $wpdb->get_results($query);
		$result	= apply_filters('sim_retrieved_formdata', $result, $userId, $this->formName);

		$this->submissionData		= $result;
		
		if(is_numeric($submissionId)){
			$this->submissionData	= $this->submissionData[0];
			$this->formResults 		= maybe_unserialize($this->submissionData->formresults);
		}else{
			// unserialize
			foreach($this->submissionData as &$data){
				$data->formresults	= unserialize($data->formresults);
			}

			$this->processSplittedData();
		}
		
		if($wpdb->last_error !== ''){
			SIM\printArray($wpdb->print_error());
		}
	}

	/**
	 * This function creates seperated entries from entries with an splitted value
	 */
	function processSplittedData(){
		if(!empty($this->formData->settings['split'])){
			$fieldMainName	= $this->formData->settings['split'];
			
			//loop over all submissions
			foreach($this->submissionData as $key=>$entry){
				// loop over all entries of the split key
				foreach($entry->formresults[$fieldMainName] as $subKey=>$array){
					// Should always be an array
					if(is_array($array)){
						// Check if it has data
						$hasData	= false;
						foreach($array as $value){
							if(!empty($value)){
								$hasData = true;
								break;
							}
						}

						// If it has data add as a seperate item to the submission data
						if($hasData){
							$newSubmission	= clone $entry;
							// Mark this submission as archived if needed
							if(isset($array['archived'])){
								if($this->showArchived){
									$newSubmission->archived	= true;
									unset($array['archived']);
								}else{
									continue;
								}
							}
							// Add the array to the formresults array
							$newSubmission->formresults = array_merge($entry->formresults, $array);

							// remove the index value from the copy
							unset($newSubmission->formresults[$fieldMainName]);

							// Add the subkey
							$newSubmission->sub_id	= $subKey;

							// Copy the entry
							$this->submissionData[]	= $newSubmission;
						}
					}
				}

				// remove the original entry
				unset($this->submissionData[$key]);
			}
		}
	}

	/**
	 * Removes any unneeded slashes
	 * 
	 * @param	string	$content	The string to deslash
	 * 
	 * @return	string				The cleaned string
	 */
	function deslash( $content ) {
		$content = preg_replace( "/\\\+'/", "'", $content );
		$content = preg_replace( '/\\\+"/', '"', $content );
		$content = preg_replace( '/https?:\/\/https?:\/\//i', 'https://', $content );
	
		return $content;
	}

	/**
	 * Checks if the current form exists in the db. If not, inserts it
	 */
	function maybeInsertForm(){
		global $wpdb;
		
		//check if form row already exists
		if(!$wpdb->get_var("SELECT * FROM {$this->tableName} WHERE `name` = '{$this->formName}'")){
			//Create a new form row			
			$this->insertForm();
		}
	}

	/** 
	 * Deletes a form
	 * 
	 * @param	int		$formId	The id of the form to be deleted
	 * 
	 * @return	string			The deletion result
	*/
	function deleteForm($formId, $pageId=''){
		global $wpdb;

		if(!isset($this->formData)){
			$this->getForm($formId);
		}

		// Remove the form
		$wpdb->delete(
			$this->tableName,
			['id' => $formId],
			['%d']
		);

		// remove the form elements
		$wpdb->delete(
			$this->elTableName,
			['form_id' => $formId],
			['%d']
		);

		// remove the form submissions
		$wpdb->delete(
			$this->submissionTableName,
			['form_id' => $formId],
			['%d']
		);

		// remove the shortcode from the page
		if(is_numeric($pageId)){
			$post	= get_post($pageId);

			$post->post_content	= str_replace("[formbuilder formname={$this->formData->name}]", '', $post->post_content);

			// delete post
			if(empty($post->post_content)){
				wp_delete_post($post->ID);
			}else{
				wp_update_post( $post );
			}
		}
	}
}