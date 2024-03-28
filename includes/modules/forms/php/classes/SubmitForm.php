<?php
namespace SIM\FORMS;
use SIM;
use WP_Embed;
use WP_Error;

class SubmitForm extends SimForms{
	public $submission;
	
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
	 * Replaces the url with form url
	 *
	 * @param	array	$footer		The footer array
	 *
	 * @return	array				The filtered footer array
	 */
	public function emailFooter($footer){
		$footer['url']		= $_POST['formurl'];
		$footer['text']		= $_POST['formurl'];

		return $footer;
	}

	/**
	 * check if an e-mail should be send
	 */
	private function checkEmailConditions($email, $trigger){
		if(
			$email['emailtrigger']	!= $trigger && 					// trigger of the e-mail does not match the trigger exactly
			(
				$email['emailtrigger']	!= 'submittedcond' ||		// trigger of the e-mail is not submittedcond
				(
					$email['emailtrigger']	== 'submittedcond' &&	// trigger of the e-mail is submittedcond
					$trigger				!= 'submitted'			// the trigger is not submitted
				)
			)
		){
			return false;
		}

		$changedElementId	= $_POST['elementid'];

		//SIM\printArray($email);
		//SIM\printArray($trigger);
		
		// check if a certain element is changed to a certain value
		if( $trigger == 'fieldchanged' ){

			// the changed element is not the conditional element)
			if($changedElementId != $email['conditionalfield']){
				return false;
			}

			// get the element value
			$elementName	= str_replace('[]', '', $this->getElementById($changedElementId, 'name'));

			$formValue 		= $this->submission->formresults[$elementName];
			if(is_array($formValue)){
				$formValue	= $formValue[0];
			}
			$formValue 		= strtolower($formValue);

			// get the compare value
			$compareValue	= strtolower($email['conditionalvalue']);

			//do not proceed if there is no match
			if($formValue != $compareValue && $formValue != str_replace(' ', '_', $compareValue)){
				return false;
			}
		}elseif(
			$trigger == 'fieldschanged'									&&		// an element has been changed
			!in_array($changedElementId, $email['conditionalfields'])			// and the element is not in the conditional fields array
		){
			return false;
		}elseif($trigger == 'submitted' && $email['emailtrigger'] == 'submittedcond'){	// check if the submit condition is matched
			if(!is_array($email['submittedtrigger'])){
				return false;
			}

			// get element and the form result of that element
			$element	= $this->getElementById($email['submittedtrigger']['element']);
			if(empty($this->submission->formresults[$element->name])){
				$elValue	= '';
			}else{
				$elValue	= $this->submission->formresults[$element->name];
			}
			
			// get the value to compare with
			if(is_numeric($email['submittedtrigger']['valueelement'])){
				$compareElement	= $this->getElementById($email['submittedtrigger']['valueelement']);
				$compareElValue	= $this->submission->formresults[$compareElement->name];
			}else{
				$compareElValue	= $email['submittedtrigger']['value'];
			}

			if(is_array($elValue)){
				$elValue	= $elValue[0];
			}

			if(is_array($compareElValue)){
				$compareElValue	= $compareElValue[0];
			}

			// Do the comparisson, do not proceed if no match
			if(!version_compare($elValue, $compareElValue, $email['submittedtrigger']['equation'])){
				return false;
			}
		}

		return true;
	}

	/**
	 * Send an e-mail
	 *
	 * @param	string	$trigger	One of 'submitted' or 'fieldchanged'. Default submitted
	 */
	public function sendEmail($trigger='submitted'){
		$emails = $this->formData->emails;
		
		foreach($emails as $key=>$email){

			if(!$this->checkEmailConditions($email, $trigger)){
				continue;
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

				// if no e-mail found, find any numbers and assume they are user ids
				// than replace the id with the e-mail of that user
				if(strpos($to, '@') === false){
					$pattern 	= '/[0-9\.]+/i';
					$to			= preg_replace_callback(
						$pattern,
						function ($match){
							$user	= get_userdata($match[0]);

							if($user && !str_contains($user->user_email, 'empty')){
								return $user->user_email;
							}
							return $match[0];
						},
						$to
					);
				}
			}

			$recipients	= [];
			foreach(explode(',', $to) as $t){
				if(str_contains($t, '@')){
					$recipients[]	= $t;
				}
			}
			
			if(empty($recipients)){
				SIM\printArray("No to email found for email $key on form {$this->formData->name} with id {$this->formData->id}");
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
				// add the form specific footer filter
				add_filter('sim_email_footer_url', [$this, 'emailFooter']);

				$result = wp_mail($to , $subject, $message, $headers, $files);

				if($result === false){
					SIM\printArray("Sending the e-mail failed");
					SIM\printArray([
						$to,
						$subject,
						$message,
						$headers,
						$files
					]);
				}

				// remove the form specific footer filter
				remove_filter('sim_email_footer_url', [$this, 'emailFooter']);
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
		
		if(isset($_REQUEST['subid']) && empty($this->submission->formresults['subid'])){
			$this->submission->formresults['subid']	= $_REQUEST['subid'];
		}

		$pattern = '/%([^%;]*)%/i';
		//Execute the regex
		preg_match_all($pattern, $string, $matches);
		
		//loop over the results
		foreach($matches[1] as $match){
			$replaceValue	= $this->submission->formresults[$match];
			if(empty($replaceValue)){
				$replaceValue	= apply_filters('sim-forms-transform-empty', $replaceValue, $this, $match);
				if(empty($replaceValue)){
					//remove the placeholder, there is no value
					$string = str_replace("%$match%", '', $string);
				}
				$string 		= str_replace("%$match%", $replaceValue, $string);
			}elseif(
				is_array($replaceValue)									&&	// the form results are an array
				file_exists( ABSPATH.array_values($replaceValue)[0])		// and the first entry is a valid file
			){
				// add the ABSPATH to the file paths
				$string = array_map(function($value){
					return ABSPATH.$value;
				}, $replaceValue);
			}else{
				if(is_array($replaceValue) && count($replaceValue) == 1){
					$replaceValue	= array_values($replaceValue)[0];
				}
				if(is_array($replaceValue)){
					$replaceValue	= apply_filters('sim-forms-transform-array', implode(',', $replaceValue), $replaceValue, $this, $match);
				}elseif(preg_match('/^(\d{4}-\d{2}-\d{2})$/', $replaceValue, $matches)){
					$replaceValue	= date(get_option('date_format'), strtotime((string)$matches[1]));
				}
				//replace the placeholder with the value
				$replaceValue	= str_replace('_', ' ', $replaceValue);
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
	 * Generic function to retrieve token status for captchas
	 */
	public function verifyCaptcha($verifyUrl, $data, $name){
		if (function_exists('curl_init') && function_exists('curl_setopt') && function_exists('curl_exec')){
			// Use cURL to get data 10x faster than using file_get_contents or other methods
			$ch = curl_init($verifyUrl);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-type: application/x-www-form-urlencoded'));
			$response = curl_exec($ch);
			curl_close($ch);
		}else{
			// If server not have active cURL module, use file_get_contents
			$opts = array('http' =>
				array(
					'method' 	=> 'POST',
					'header'	=> 'Content-type: application/x-www-form-urlencoded',
					'content' 	=> $data
				)
			);
			$context 	= stream_context_create($opts);
			$response 	= file_get_contents($verifyUrl, false, $context);
		}

		$json	= json_decode($response);
		if(empty($json->succes)){
			return new WP_Error('forms', "Invalid $name Response!");
		}else{
			return true;
		}
	}

	/**
	 * Verifies a turnstile token from $_REQUEST
	 *
	 * @return	bool			false if no token found
	 */
	public function verifyTurnstile(){
		if(!isset($_REQUEST['cf-turnstile-response'])){
			return false;
		}

		$secret		= SIM\getModuleOption(MODULE_SLUG, 'turnstilesecretkey');
		$verifyUrl 	= "https://challenges.cloudflare.com/turnstile/v0/siteverify";
		$data		= "secret=$secret&response={$_REQUEST['cf-turnstile-response']}";

		return $this->verifyCaptcha($verifyUrl, $data, 'Turnstile');
	}

	/**
	 * Verifies a recaptcha token from $_REQUEST
	 */
	public function verifyRecaptcha(){
		if(!isset($_REQUEST['g-recaptcha-response'])){
			return false;
		}

		$secret		= SIM\getModuleOption(MODULE_SLUG, 'turnstilesecretkey');
		$verifyUrl 	= 'https://www.google.com/recaptcha/api/siteverify';

		$queryData = [
			'secret' 	=> $secret,
			'response' 	=> $_REQUEST['g-recaptcha-response'],
			'remoteip' 	=> (isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ? $_SERVER["HTTP_CF_CONNECTING_IP"] : $_SERVER['REMOTE_ADDR'])
		];
	
		// Collect and build POST data
		$data = http_build_query($queryData, '', '&');

		return $this->verifyCaptcha($verifyUrl, $data, 'reCaptcha');
	}

	/**
	 * Save a form submission to the db
	 */
	public function formSubmit(){
		global $wpdb;

		$this->submission					= new \stdClass();

		$this->submission->form_id			= $_POST['formid'];
		
		$this->getForm($this->submission->form_id);

		$verifcation	= true;
		if($this->getElementByType('turnstile')){
			$verifcation	= $this->verifyTurnstile();
		}

		if($this->getElementByType('recaptcha')){
			$verifcation	= $this->verifyRecaptcha();
		}

		if($verifcation !== true){
			return $verifcation;
		}
		
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

		if(is_wp_error($this->submission->formresults)){
			return $this->submission->formresults;
		}

		$message = $this->formData->settings['succesmessage'];
		if(empty($message)){
			$message = 'succes';
		}
		
		//save to submission table
		if(empty($this->formData->settings['save_in_meta'])){
			$this->submission->formresults['submissiontime']	= $this->submission->timecreated;
			$this->submission->formresults['edittime']			= $this->submission->timelastedited;
			
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

			$submission 				= (array) $this->submission;
			$submission['formresults']	= serialize($this->submission->formresults);

			$wpdb->insert(
				$this->submissionTableName,
				$submission
			);
			
			$this->sendEmail();
				
			if($wpdb->last_error !== ''){
				$message	=  new \WP_Error('error', $wpdb->last_error);
			}elseif(empty($this->formData->settings['includeid']) || $this->formData->settings['includeid'] == 'yes'){
				$message	.= "\nYour id is {$this->submission->formresults['id']}";
			}
		//save to user meta
		}else{
			unset($this->submission->formresults['formurl']);
			unset($this->submission->formresults['formid']);
			unset($this->submission->formresults['_wpnonce']);
			
			//get user data as array
			$userData		= (array)get_userdata($this->userId)->data;
			foreach($this->submission->formresults as $key=>&$result){
				$subKey	= false;

				//remove empty elements from the array
				if(is_array($result)){
					SIM\cleanUpNestedArray($result);

					//check if we should only update one entry of the array
					$el	= $this->getElementByName($key.'['.array_keys($result)[0].']');
					if(count(array_keys($result)) == 1 && $el){
						$subKey	= array_keys($result)[0];
					}
				}

				//update in the _users table
				if(isset($userData[$key])){
					if($subKey){
						$userData[$key][$subKey]		= $result;
						$updateuserData					= true;
					}elseif($userData[$key]	!= $result){
						$userData[$key]		= $result;
						$updateuserData		= true;
					}
				//update user meta
				}else{
					if($subKey){
						$curValue	= get_user_meta($this->userId, $key, true);
						if(empty($result)){
							// remove subkey
							if(isset($curValue[$subKey])){
								unset($curValue[$subKey]);
							}
						}else{
							if(!is_array($curValue)){
								$curValue	= [];
							}

							//update subkey
							$curValue[$subKey]	= $result[$subKey];
						}

						update_user_meta($this->userId, $key, $result);
					}else{
						if(empty($result)){
							delete_user_meta($this->userId, $key);
						}else{
							update_user_meta($this->userId, $key, $result);
						}
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
