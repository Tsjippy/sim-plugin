<?php
namespace SIM\FORMS;
use ParseSplittedFormResults;
use SIM;
use WP_Embed;
use WP_Error;

class SaveFormSettings extends SimForms{
	use CreateJs;

	/**
	 * Change an existing form element in the db
	 *
	 * @param	object|array	$element	The new element data
	 *
	 * @return	true|WP_Error				The result or error on failure
	 */
	public function updateFormElement($element){
		global $wpdb;
		
		//Update element
		$wpdb->update(
			$this->elTableName,
			(array)$element,
			array(
				'id'		=> $element->id,
			),
		);

		if($wpdb->last_error !== ''){
			return new WP_Error('forms', $wpdb->last_error);
		}

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
			return new WP_Error('forms', $wpdb->last_error);
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
	public function insertElement($element){
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
	public function updatePriority($element){
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
	public function reorderElements($oldPriority, $newPriority, $element) {
		if ($oldPriority == $newPriority){
			return;
		}

		if(!isset($this->formId) && !empty($element) && isset($element->form_id)){
			$this->formId	= $element->form_id;
		}

		// Get all elements of this form
		$this->getAllFormElements('priority', $this->formId, true);

		// find the element
		if(empty($element)){
			foreach($this->formElements as $el){
				if($el->priority == $oldPriority){
					$element = $el;
					break;
				}
			}
		}

		//No need to reorder if we are adding a new element at the end
		if($element->priority == $newPriority && count($this->formElements) == $element->priority){
			return;
		}

		//No need to reorder if we are adding a new element at the end
		if(count($this->formElements) == $newPriority && $this->formElements[0]->priority != 1){
			// First element is the element without priority
			$el				= $this->formElements[0];
			$el->priority	= $newPriority;
			$this->updatePriority($el);
			return;
		}

		// only move if element is not alreay on the right location
		if($this->formElements[$newPriority-1]->name != $element->name){
			// Move the element to the new position priority should be index+1
			if($oldPriority == -1){
				$out				= [$element];
			}else{
				$out				= array_splice($this->formElements, $oldPriority-1, 1);
			}
			array_splice($this->formElements, $newPriority-1, 0, $out);
		}

 		//Loop over all elements and give them the new priority
		foreach($this->formElements as $index=>$el){
			if($index+1 != $el->priority ){
				$el->priority	= $index+1;
				$this->updatePriority($el);
			}
		}
	}

	/**
	 * Checks if the current form exists in the db. If not, inserts it
	 */
	public function maybeInsertForm($formId=''){
		global $wpdb;

		if(!isset($this->formName)){
			return new WP_ERROR('forms', 'No formname given');
		}
		
		$query	= "SELECT * FROM {$this->tableName} WHERE `name` = '{$this->formName}'";
		if(is_numeric($formId)){
			$query	.= " OR id=$formId";
		}
		//check if form row already exists
		if(!$wpdb->get_var($query)){
			//Create a new form row
			$this->insertForm();
		}
	}

	/**
	 * Deletes a form
	 *
	 * @param	int		$formId	The id of the form to be deleted
	 * @param	int		$pageId	The id of a page with a formbuilder shortcode
	 *
	 * @return	string			The deletion result
	*/
	public  function deleteForm($formId){
		global $wpdb;

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

		$query		= "SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '%[formbuilder formname={$this->formData->name}]%'";
		$results	= $wpdb->get_results ($query);

		// remove the shortcode from the page
		foreach($results as $postId){
			$post	= get_post($postId);

			$post->post_content	= str_replace("[formbuilder formname={$this->formData->name}]", '', $post->post_content);

			// delete post
			if(empty($post->post_content)){
				wp_delete_post($post->ID);
			}else{
				wp_update_post( $post );
			}
		}
	}

	/**
	 * Update form settings
	 */
	public function updateFormSettings($formId='', $formSettings=''){
		global $wpdb;

		if(empty($formId)){
			if(!empty($this->formData->id)){
				$formId	= $this->formData->id;
			}else{
				return new \WP_Error('Error', 'Please supply a form id');
			}
		}

		if(empty($formSettings)){
			if(!empty($this->formData->settings)){
				$formSettings	= $this->formData->settings;
			}else{
				return new \WP_Error('Error', 'Please supply a the form settings');
			}
		}

		$wpdb->update($this->tableName,
			array(
				'settings' 	=> maybe_serialize($formSettings)
			),
			array(
				'id'		=> $formId,
			),
		);
		
		if($wpdb->last_error !== ''){
			return new \WP_Error('Error', $wpdb->print_error());
		}

		return true;
	}
}
