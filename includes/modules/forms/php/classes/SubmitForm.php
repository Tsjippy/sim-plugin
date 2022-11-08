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
					$elementName	= str_replace('[]', '', $this->getElementById($email['conditionalfield'], 'name'));
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
}
