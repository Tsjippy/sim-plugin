<?php
namespace SIM\FORMS;
use SIM;
use WP_Embed;
use WP_Error;

class SubmitForm extends SimForms{
	/**
	 * Returns conditional e-mails with a valid condition
	 *
	 * @param	array	$conditions		The conditions of a conditional e-mail
	 *
	 * @return	string|false			The e-mail adres or false if none found
	 */
	public function findConditionalEmail($conditions){
		//loop over all conditions
		foreach($conditions as $condition){

			$elementName	= $this->getElementById($condition['fieldid'], 'name');

			//get the submitted form value
			$formValue = $this->submission->formresults[$elementName];
					
			//if the value matches the conditional value
			if(strtolower($formValue) == strtolower($condition['value'])){
				return $condition['email'];
			}
		}

		return false;
	}

	/**
	 * Filters the e-mail footer url and text
	 *
	 * @param	array	$footer		The footer array
	 *
	 * @return	array				The filtered footer array
	 */
	protected function emailFooter($footer){
		$footer['url']		= $_POST['formurl'];
		$footer['text']		= $_POST['formurl'];
		return $footer;
	}

	/**
	 * Send an e-mail
	 *
	 * @param	string	$trigger	One of 'submitted' or 'fieldchanged'. Default submitted
	 */
	public function sendEmail($trigger='submitted'){
		$emails = $this->formData->emails;
		
		foreach($emails as $key=>$email){
			if($email['emailtrigger'] == $trigger){
				if($trigger == 'fieldchanged'){
					$elementName	= $this->getElementById($email['conditionalfield'], 'nicename');
					$formValue 		= strtolower($this->submission->formresults[$elementName]);
					$compareValue	= strtolower($email['conditionalvalue']);

					//do not proceed if there is no match
					if($formValue != $compareValue && $formValue != str_replace(' ', '_', $compareValue)){
						continue;
					}
				}
				
				$from	= '';
				//Send e-mail from conditional e-mail adress
				if($email['fromemail'] == 'conditional'){
					$from 	= $this->findConditionalEmail($email['conditionalfromemail']);

					if(!$from){
						$from	= $email['elsefrom'];
					}
				}elseif($email['fromemail'] == 'fixed'){
					$from	= $this->processPlaceholders($email['from']);
				}

				if(empty($from)){
					SIM\printArray("No from email found for email $key");
				}
								
				$to		= '';
				if($email['emailto'] == 'conditional'){
					$to = $this->findConditionalEmail($email['conditionalemailto']);

					if(!$to){
						$to	= $email['elseto'];
					}
				}elseif($email['emailto'] == 'fixed'){
					$to		= $this->processPlaceholders($email['to']);
				}
				
				if(empty($to)){
					SIM\printArray("No to email found for email $key");
					continue;
				}

				$subject	= $this->processPlaceholders($email['subject']);
				$message	= $this->processPlaceholders($email['message']);

				$headers	= [];
				if(!empty(trim($email['headers']))){
					$headers	= explode("\n", trim($email['headers']));
				}

				if(!empty($from)){
					$headers[]	= "Reply-To: $from";
				}
				
				$files		= $this->processPlaceholders($email['files']);

				//Send the mail
				if($_SERVER['HTTP_HOST'] != 'localhost'){
					add_filter('sim_email_footer_url', [$this, 'emailFooter']);
					$result = wp_mail($to , $subject, $message, $headers, $files);
					if($result === false){
						SIM\printArray("Sending the e-mail failed");
					}

					remove_filter('sim_email_footer_url', [$this, 'emailFooter']);
				}
			}
		}
	}
	
	/**
	 * Replaces placeholder with the value
	 *
	 * @param	string	$string		THe string to check for placeholders
	 *
	 * @return	string				The filtered string
	 */
	protected function processPlaceholders($string){
		if(empty($string)){
			return $string;
		}

		if(empty($this->submission->formresults['submissiondate'])){
			$this->submission->formresults['submissiondate']	= date('d F y', strtotime($this->submission->formresults['submissiontime']));
			$this->submission->formresults['editdate']			= date('d F y', strtotime($this->submission->formresults['edittime']));
		}

		$pattern = '/%([^%;]*)%/i';
		//Execute the regex
		preg_match_all($pattern, $string, $matches);
		
		//loop over the results
		foreach($matches[1] as $match){
			if(empty($this->submission->formresults[$match])){
				//remove the placeholder, there is no value
				$string = str_replace("%$match%", '', $string);
			}elseif(is_array($this->submission->formresults[$match])){
				$files	= $this->submission->formresults[$match];
				$string = array_map(function($value){
					return ABSPATH.$value;
				}, $files);
			}else{
				//replace the placeholder with the value
				$replaceValue	= str_replace('_', ' ', $this->submission->formresults[$match]);
				$string 		= str_replace("%$match%", $replaceValue, $string);
			}
		}
		
		return $string;
	}

	/**
	 * Rename any existing files to include the form id.
	 */
	public function processFiles($uploadedFiles, $inputName){
		//loop over all files uploaded in this fileinput
		foreach ($uploadedFiles as $key => $url){
			$urlParts 	= explode('/',$url);
			$fileName	= end($urlParts);
			$path		= SIM\urlToPath($url);
			$targetDir	= str_replace($fileName,'',$path);
			
			//add input name to filename
			$fileName	= "{$inputName}_$fileName";
			
			//also add submission id if not saving to meta
			if(empty($this->formData->settings['save_in_meta'])){
				$fileName	= $this->submission->formresults['id']."_$fileName";
			}
			
			//Create the filename
			$i = 0;
			$targetFile = $targetDir.$fileName;
			//add a number if the file already exists
			while (file_exists($targetFile)) {
				$i++;
				$targetFile = "$targetDir.$fileName($i)";
			}
	
			//if rename is succesfull
			if (rename($path, $targetFile)) {
				//update in formdata
				$this->submission->formresults[$inputName][$key]	= str_replace(ABSPATH, '', $targetFile);
			}else {
				//update in formdata
				$this->submission->formresults[$inputName][$key]	= str_replace(ABSPATH, '', $path);
			}
		}
	}

	/**
	 * Save a form submission to the db
	 */
	function formSubmit(){
		global $wpdb;

		$this->submission					= new \stdClass();

		$this->submission->form_id			= $_POST['formid'];
		
		$this->getForm($this->submission->form_id);
		
		$this->userId	= 0;
		if(is_numeric($_POST['userid'])){
			//If we are submitting for someone else and we do not have the right to save the form for someone else
			if(
				array_intersect($this->userRoles, $this->submitRoles) === false &&
				$this->user->ID != $_POST['userid']
			){
				return new \WP_Error('Error', 'You do not have permission to save data for user with id '.$_POST['userid']);
			}else{
				$this->userId = $_POST['userid'];
			}
		}

		$this->submission->timecreated		= date("Y-m-d H:i:s");

		$this->submission->timelastedited	= date("Y-m-d H:i:s");
		
		$this->submission->userid			= $this->userId;

		$this->submission->formresults 		= $_POST;

		$this->submission->archived 		= false;
			
		//remove the action and the formname
		unset($this->submission->formresults['formname']);
		unset($this->submission->formresults['fileupload']);
		unset($this->submission->formresults['userid']);

		$this->submission->formresults['submissiontime']	= $this->submission->timecreated;
		$this->submission->formresults['edittime']			= $this->submission->timelastedited;
			
		$this->submission->formresults['formurl']			= $_POST['formurl'];

		// remove empty splitted entries
		if(isset($this->formData->settings['split'])){
			foreach($this->formData->settings['split'] as $id){
				$name	= $this->getElementById($id, 'name');
				// Check if we are dealing with an split element with form name[X]name
				preg_match('/(.*?)\[[0-9]\]\[.*?\]/', $name, $matches);

				if($matches && isset($matches[1]) && is_array($this->submission->formresults[$matches[1]])){
					// loop over all the sub entries of the split field to see if they are empty
					foreach($this->submission->formresults[$matches[1]] as $index=>$sub){
						$empty	= true;
						if(is_array($sub)){
							foreach($sub as $s){
								if(!empty($s)){
									$empty	= false;
									break;
								}
							}
						}

						if($empty){
							// remove from results
							unset($this->submission->formresults[$matches[1]][$index]);
						}
					}

					// reindex
					$this->submission->formresults[$matches[1]]	= array_values(	$this->submission->formresults[$matches[1]]);
				}
			}
		}
		
		$this->submission->formresults 					= apply_filters('sim_before_saving_formdata', $this->submission->formresults, $this->formData->name, $this->userId);

		$message = $this->formData->settings['succesmessage'];
		if(empty($message)){
			$message = 'succes';
		}
		
		//save to submission table
		if(empty($this->formData->settings['save_in_meta'])){
			//Get the id from db before insert so we can use it in emails and file uploads
			$this->submission->formresults['id']	= $wpdb->get_var( "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE (TABLE_NAME = '{$this->submissionTableName}') AND table_schema='$wpdb->dbname'");
			
			//sort arrays
			foreach($this->submission->formresults as $key=>$result){
				if(is_array($result)){
					//check if this a aray of uploaded files
					if(!is_array(array_values($result)[0]) && strpos(array_values($result)[0],'wp-content/uploads/') !== false){
						//rename the file
						$this->processFiles($result, $key);
					}else{
						//sort the array
						ksort($result);
						$this->submission->formresults[$key] = $result;
					}
				}
			}

			$submission = (array) $this->submission;
			$submission['formresults']	= serialize($this->submission->formresults);

			$wpdb->insert(
				$this->submissionTableName,
				$submission
			);
			
			$this->sendEmail();
				
			if($wpdb->last_error !== ''){
				$message	=  new \WP_Error('error', $wpdb->last_error);
			}else{
				$message	= "$message  \nYour id is {$this->submission->formresults['id']}";
			}
		//save to user meta
		}else{
			unset($this->submission->formresults['formurl']);
			
			//get user data as array
			$userData		= (array)get_userdata($this->userId)->data;
			foreach($this->submission->formresults as $key=>$result){
				//remove empty elements from the array
				if(is_array($result)){
					SIM\cleanUpNestedArray($result);
					$this->submission->formresults[$key]	= $result;
				}
				//update in the _users table
				if(isset($userData[$key])){
					if($userData[$key]		!= $result){
						$userData[$key]		= $result;
						$updateuserData		= true;
					}
				//update user meta
				}else{
					if(empty($result)){
						delete_user_meta($this->userId, $key);
					}else{
						update_user_meta($this->userId, $key, $result);
					}
				}
			}

			if($updateuserData){
				wp_update_user($userData);
			}
		}

		$message	= apply_filters('sim_after_saving_formdata', $message, $this);

		return $message;
	}
}
