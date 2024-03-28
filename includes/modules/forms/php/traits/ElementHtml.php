<?php
namespace SIM\FORMS;
use SIM;

trait ElementHtml{
	 /**
	 * Builds the array with default values for the current user
	 */
	function buildDefaultsArray(){
		//Only create one time
		if(empty($this->defaultValues)){
			if(empty($this->formName)){
				$this->formName	= $this->formData->name;
			}

			$this->defaultValues		= (array)$this->user->data;
			//Change ID to userid because its a confusing name
			$this->defaultValues['user_id']	= $this->defaultValues['ID'];
			unset($this->defaultValues['ID']);
			
			$this->defaultArrayValues	= [];
			
			foreach(['user_pass', 'user_activation_key', 'user_status', 'user_level'] as $field){
				unset($this->defaultValues[$field]);
			}
			
			//get defaults from filters
			$this->defaultValues		= apply_filters('sim_add_form_defaults', $this->defaultValues, $this->userId, $this->formName);
			$this->defaultArrayValues	= apply_filters('sim_add_form_multi_defaults', $this->defaultArrayValues, $this->userId, $this->formName);
			
			ksort($this->defaultValues);
			ksort($this->defaultArrayValues);
		}
	}
    
	/**
	 * Get the values of an element
	 *
	 * @param	object	$element		The element
	 *
	 * @return	array					The array of values
	 */
	function getElementValues($element){
		$values	= [];

		// Do not return default values when requesting the html over rest api
		if(defined('REST_REQUEST')){
			//return $values;
		}
		
		if(in_array($element->type, $this->nonInputs)){
			return [];
		}

		$this->buildDefaultsArray();

		//get the elementName, remove [] and split on remaining [
		$elementName	= explode('[', trim($element->name, '[]'));
		
		//retrieve meta values if needed
		if(!empty($this->formData->settings['save_in_meta'])){
			//only load usermeta once
			if(!is_array($this->usermeta)){
				//usermeta comes as arrays, only keep the first
				$this->usermeta	= [];
				foreach(get_user_meta($this->userId) as $key=>$meta){
					$this->usermeta[$key]	= $meta[0];
				}
				$this->usermeta	= apply_filters('sim_forms_load_userdata', $this->usermeta, $this->userId);
			}
		
			if(count($elementName) == 1){
				//non array name
				$elementName			= $elementName[0];
				$values['metavalue']	= [];
				if(isset($this->usermeta[$elementName])){
					$values['metavalue']	= (array)maybe_unserialize($this->usermeta[$elementName]);
				}
			}elseif(!empty($this->usermeta[$elementName[0]])){
				//an array of values, we only want a specific one
				$values['metavalue']	= (array)maybe_unserialize($this->usermeta[$elementName[0]]);
				

				unset($elementName[0]);
				//loop over all the subkeys, and store the value until we have our final result
				$resultFound	= false;
				foreach($elementName as $v){
					if(isset($values['metavalue'][$v])){
						$values['metavalue'] = (array)$values['metavalue'][$v];
						$resultFound	= true;
					}
				}

				// somehow it does not exist, return an empty value
				if(!$resultFound){
					$values['metavalue']	= '';
				}
			}
		}
		
		//add default values
		if(empty($element->multiple) || in_array($element->type, ['select','checkbox'])){
			$defaultKey					= $element->default_value;
			if(!empty($defaultKey) && !empty($this->defaultValues[$defaultKey])){
				$values['defaults']		= $this->defaultValues[$defaultKey];
			}
		}else{
			$defaultKey					= $element->default_array_value;
			if(!empty($defaultKey) && !empty($this->defaultValues[$defaultKey])){
				$values['defaults']		= $this->defaultArrayValues[$defaultKey];
			}
		}

		return $values;
	}

	/**
	 * Gets the options of an element like styling etc.
	 *
	 * @param	object	$element		The element
	 *
	 * @return	array					Array of options
	 */
	function getElementOptions($element){
		$options	= [];
		//add element values
		$elValues	= explode("\n", trim($element->valuelist));
		
		if(!empty($elValues[0])){
			foreach($elValues as $value){
				$split = explode('|', $value);
				
				//Remove starting or ending spaces and make it lowercase
				$value 			= trim($split[0]);
				$escapedValue 	= str_replace([' ', ',', '(', ')'], ['_', '', '', ''], $value);
				
				if(!empty($split[1])){
					$displayName	= $split[1];
				}else{
					$displayName	= $value;
				}
				
				$options[$escapedValue]	= $displayName;
			}

			asort($options);
		}
		
		$defaultArrayKey				= $element->default_array_value;
		if(!empty($defaultArrayKey)){
			$this->buildDefaultsArray();
			$options	= (array)$this->defaultArrayValues[$defaultArrayKey]+$options;
		}
		
		return $options;
	}

	function prepareElementHtml($element, $index, $elementHtml, $value){
		if($value === null){
			$value = '';
		}
		//Add the key to the fields name
		$elementHtml	= preg_replace("/(name='.*?)'/i", "\${1}[$index]'", $elementHtml);
					
		//we are dealing with a select, add options
		if($element->type == 'select'){
			$elementHtml .= "<option value=''>---</option>";
			$options	= $this->getElementOptions($element);
			foreach($options as $option_key=>$option){
				if(strtolower($value) == strtolower($option_key) || strtolower($value) == strtolower($option)){
					$selected	= 'selected="selected"';
				}else{
					$selected	= '';
				}
				$elementHtml .= "<option value='$option_key' $selected>$option</option>";
			}

			$elementHtml  .= "</select>";
		}elseif(in_array($element->type, ['radio','checkbox'])){
			$options	= $this->getElementOptions($element);

			//make key and value lowercase
			array_walk_recursive($options, function(&$item, &$key){
				$item 	= strtolower($item);
				$key 	= strtolower($key);
			});
			foreach($options as $option_key=>$option){
				$found = false;

				if(is_array($value)){
					foreach($value as $v){
						if(strtolower($v) == $option_key || strtolower($v) == $option){
							$found 	= true;
						}
					}
				}elseif(strtolower($value) == $option_key || strtolower($value) == $option){
					$found 	= true;
				}
				
				if($found){
					//remove the % to leave the selected
					$elementHtml	= str_replace('%', '', $elementHtml);
				}else{
					//remove the %checked% keyword
					$elementHtml	= str_replace('%checked%', '', $elementHtml);
				}
			}
		}elseif(is_array($value)){
			$elementHtml	= str_replace('%value%', $value[$index], $elementHtml);
		}else{
			$elementHtml	= str_replace('%value%', $value, $elementHtml);
		}

		if($element->type == 'label'){
			$nr				= $index+1;
			$elementHtml	= str_replace('</h4>'," $nr</h4>", $elementHtml);
		}

		return  $elementHtml;
	}

	/**
	 * Renders the html for element who can have multiple inputs
	 *
	 * @param	object	$element		The element
	 * @param	array	$values			The values for this element
	 * @param	string	$elementHtml	The html of a single element
	 */
	function multiInput($element, $values=null, $elementHtml=''){
		//add label to each entry if prev element is a label and wrapped with this one
		if($this->prevElement->type	== 'label' && !empty($this->prevElement->wrap) && $this->prevElement != $element){
			$this->prevElement->text = $this->prevElement->text.' %key%';
			$prevLabel = $this->getElementHtml($this->prevElement).'</label>';
		}else{
			$prevLabel	= '';
		}

		if(empty($this->formData->settings['save_in_meta']) && !empty($values['defaults'])){
			$values		= array_values((array)$values['defaults']);
		}elseif(!empty($values['metavalue'])){
			$values		= array_values((array)$values['metavalue']);
		}

		//check how many elements we should render
		$this->multiWrapValueCount	= max(1, count((array)$values));

		//create as many inputs as the maximum value found
		for ($index = 0; $index < $this->multiWrapValueCount; $index++) {
			$val	= '';
			if(!empty($values[$index])){
				$val	= $values[$index];
			}

			$elementItemHtml	= $this->prepareElementHtml($element, $index, $elementHtml, $val);
			
			//open the clone div
			$html	= "<div class='clone_div' data-divid='$index'>";
				//add flex to single multi items, re-add the label for each value
				$html .= str_replace('%key%', $index+1, $prevLabel);
				
				//wrap input AND buttons in a flex div
				$html .= "<div class='buttonwrapper' style='width:100%; display: flex;'>";
			
					//write the element
					$html .= $elementItemHtml;
			
					//close any label first before adding the buttons
					if($this->wrap == 'label'){
						$html .= "</label>";
					}
			
					//close select
					if($element->type == 'select'){
						$html .= "</select>";
					}
			
					$html .= "<button type='button' class='add button' style='flex: 1;'>+</button>";
					$html .= "<button type='button' class='remove button' style='flex: 1;'>-</button>";
				$html .= "</div>";
			$html .= "</div>";//close clone_div

			$this->multiInputsHtml[$index]	= $html;
		}
	}

	/**
	 * Gets the html of form element
	 *
	 * @param	object	$element		The element
	 * @param	mixed	$value			The value of the element
	 * @param	bool	$removeMinMax	Wheter to ignore min and max values for date elements
	 *
	 * @return	string				The html
	 */
	function getElementHtml($element, $value ='', $removeMinMax=false){
		$html		= '';
		$extraHtml	= '';
		$elClass	= '';
		$elOptions	= '';

		/*
			ELEMENT OPTIONS
		*/
		if(!empty($element->options)){
			//Store options in an array
			$options	= explode("\n", trim($element->options));
			
			//Loop over the options array
			foreach($options as $option){
				//Remove starting or ending spaces and make it lowercase
				$option 		= trim($option);

				$optionType		= explode('=', $option)[0];
				$optionValue	= str_replace('\\\\', '\\', explode('=',$option)[1]);

				
				if($removeMinMax && in_array($optionType, ['min', 'max'])){
					continue;
				}

				if($optionType == 'class'){
					$elClass .= " $optionValue";
				}else{
					//remove any leading "
					$optionValue	= trim($optionValue, '\'"');

					if($element->type == 'date' && in_array($optionType, ['min', 'max', 'value']) && strtotime($optionValue)){
						$optionValue	= Date('Y-m-d', strtotime($optionValue));
					}

					//Write the corrected option as html
					$elOptions	.= " $optionType=\"$optionValue\"";
				}
				
				// we are getting the html for an input and that input depends on a datalist
				if($optionType == 'list'){
					$datalist	= $this->getElementByName($optionValue);
					$extraHtml	= $this->getElementHtml($datalist);
				}
			}
		}

		$elValue	= "";
		
		if($element->type == 'p'){
			$html = wp_kses_post($element->text);
			$html = "<div name='{$element->name}'>".SIM\deslash($html)."</div>";
		}elseif($element->type == 'php'){
			//we store the functionname in the html variable replace any double \ with a single \
			$functionName 						= str_replace('\\\\','\\',$element->functionname);
			//only continue if the function exists
			if (function_exists( $functionName ) ){
				$html	= $functionName($this->userId);
			}else{
				$html	= "php function '$functionName' not found";
			}
		}elseif($element->type == 'div_start'){
			$class		= '';
			if($element->hidden){
				$class	= "class='hidden'";
			}
			$html 		= "<div name='$element->name' $class>";
		}elseif($element->type == 'div_end'){
			$html 		= "</div>";
		}elseif(in_array($element->type, ['multi_start','multi_end'])){
			$html 		= "";
		}elseif(in_array($element->type, ['info'])){
			//remove any paragraphs
			$content = str_replace('<p>','',$element->text);
			$content = str_replace('</p>','',$content);
			$content = SIM\deslash($content);
			
			$html = "<div class='infobox' name='{$element->name}'>";
				$html .= '<div style="float:right">';
					$html .= '<p class="info_icon"><img draggable="false" role="img" class="emoji" alt="ℹ" src="'.PICTURESURL.'/info.png" loading="lazy" ></p>';
				$html .= '</div>';
				$html .= "<span class='info_text'>$content</span>";
			$html .= '</div>';
		}elseif(in_array($element->type, ['file','image'])){
			$name		= $element->name;
			
			// Element setting
			if(!empty($element->foldername)){
				if(strpos($element->foldername, "private/") !== false){
					$targetDir	= $element->foldername;
				}else{
					$targetDir	= "private/".$element->foldername;
				}
			}
			// Form setting
			if(empty($targetDir)){
				$targetDir = $this->formData->settings['upload_path'];
			}
			// Default setting
			if(empty($targetDir)){
				$targetDir = 'form_uploads/'.$this->formData->settings['formname'];
			}

			if(empty($this->formData->settings['save_in_meta'])){
				$library	= false;
				$metakey	= '';
				$userId		= '';
			}else{
				$library	= $element->library;
				$metakey	= $name;
				$userId		= $this->userId;
			}
			//Load js
			$uploader = new SIM\FILEUPLOAD\FileUpload($userId, $metakey, $library, '', false);
			
			$html = $uploader->getUploadHtml($name, $targetDir, $element->multiple, $elOptions, $element->editimage);
		}else{
			/*
				ELEMENT TAG NAME
			*/
			if(in_array($element->type, ['formstep', 'info', 'div_start'])){
				$elType	= "div";
			}elseif(in_array($element->type,array_merge($this->nonInputs,['select','textarea']))){
				$elType	= $element->type;
			}else{
				$elType	= "input type='{$element->type}'";
			}
			
			 /*
				ELEMENT NAME
			 */
			//Get the field name, make it lowercase and replace any spaces with an underscore
			$elName	= $element->name;
			// [] not yet added to name
			if(in_array($element->type, ['radio','checkbox']) && strpos($elName, '[]') === false) {
				$elName .= '[]';
			}
			
			/*
				ELEMENT ID
			*/
			$elId	= "";
			//datalist needs an id to work as well as mandatory elements for use in anchor links
			if($element->type == 'datalist' || $element->mandatory || $element->recommended){
				$elId	= "id='$elName'";
			}

			if(strpos($elName, '[]') !== false){
				$elId	= "id='E$element->id'";
			}
			
			/*
				ELEMENT CLASS
			*/
			$elClass = " formfield";
			switch($element->type){
				case 'label':
					$elClass .= " formfieldlabel";
					break;
				case 'button':
					$elClass .= " button";
					break;
				case 'formstep':
					$elClass .= " formstep stephidden";
					break;
				default:
					$elClass .= " formfieldinput";
			}
			
			/*
				BUTTON TYPE
			*/
			if($element->type == 'button'){
				$elOptions	.= " type='button'";
			}

			/*
				ELEMENT VALUE
			*/
			// Check if we should inlcude previous submitted values
			$prevValues		= '';
			if(str_contains($_SERVER['REDIRECT_URL'], 'get_input_html')){
				$valueIndexes	= explode('[', $element->name);

				foreach($valueIndexes as $i=>$index){
					if($i == 0){
						if(!isset($this->submission->formresults[$index])){
							break;
						}

						$prevValues	= $this->submission->formresults[$index];
					}else{
						$index	= trim($index, ']');

						if(!isset($prevValues[$index])){
							break;
						}

						$prevValues	= $prevValues[$index];
					}					
				}

				if(is_string($prevValues)){
					$result	= json_decode($prevValues);

					if (json_last_error() === JSON_ERROR_NONE) {
						$prevValues		= $result;
					}
				}
			}
				
			if(empty($value) & !empty($prevValues)){
				$value	= $prevValues;
			}

			$values	= $this->getElementValues($element);
			if($this->multiwrap || !empty($element->multiple)){
				if(strpos($elType, 'input') !== false){
					$elValue	= "value='%value%'";
				}
			}elseif(!empty($values) || !empty($value)){
				$val	= $value;
				if(empty($value)){
					//this is an input and there is a value for it
					if(!empty($values['defaults']) && (empty($this->formData->settings['save_in_meta']) || empty($values['metavalue']))){
						$val		= array_values((array)$values['defaults'])[0];
					}elseif(!empty($values['metavalue'])){
						$val		= array_values((array)$values['metavalue'])[0];
					}
				}

				if(
					strpos($elType, 'input') !== false && 
					!empty($val) && 
					!in_array($elType, ['radio', 'checkbox'])
				){
					if(is_array($val)){
						$val	= array_values($val)[0];
					}
					$elValue	= "value='$val'";
				}
			}

			/*
				ELEMENT TAG CONTENTS
			*/
			$elContent = "";
			if($element->type == 'textarea'){
				if(!empty($value)){
					$elContent = $value;
				}elseif(isset($values['metavalue'][0])){
					$elContent = $values['metavalue'][0];
				}
			}elseif(!empty($element->text)){
				switch($element->type){
					case 'formstep':
						$elContent = "<h3>{$element->text}</h3>";
						break;
					case 'label':
						$elContent = "<h4 class='labeltext'>{$element->text}</h4>";
						break;
					case 'button':
						$elContent = $element->text;
						break;
					default:
						$elContent = "<label class='labeltext'>{$element->text}</label>";
				}
			}
			
			switch($element->type){
				case 'select':
					$elContent .= $this->selectOptionsHtml($element, $value, $values);
					break;
				case 'datalist':
					$elContent .= $this->datalistOptionsHtml($element);
					break;
				case 'captcha':
					if(isset($_REQUEST['install-hcaptcha'])){
						ob_start();
						if(SIM\ADMIN\installPlugin('hcaptcha-for-forms-and-more/hcaptcha.php') !== true){
							// check if api is set
							$options	= get_option('hcaptcha_settings');

							if(!$options || empty($options['site_key']) || empty($options['secret_key'])){
								// redirect to the admin page to set an api key
								if(current_user_can( 'manage_options' )){
									wp_redirect(admin_url('options-general.php?page=hcaptcha'));
								}else{
									$html	.= "Installation succesfull.<br>Please make sure the hCaptcha api key is set";
								}
							}
						};
						ob_end_clean();
					}

					$html	.= do_shortcode( '[hcaptcha auto="true" force="true"]' );
					if(str_contains($html, '[hcaptcha ')){
						$message	= 'Please install the hcaptcha plugin before using this element';
						SIM\printArray($message);
						$html	= '';
						if(isset($_REQUEST['formbuilder'])){
							$url	= SIM\currentUrl().'&install-hcaptcha=true';
							$html	= $message." <a href='$url' class='button small'>Install now</a>";
						}
					}else{
						$html	.= '
						<svg 
							xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="6 0 32 32" fill="none">
							<path opacity="0.5" d="M30 28H26V32H30V28Z" fill="#0074BF"/>
							<path opacity="0.7" d="M26 28H22V32H26V28Z" fill="#0074BF"/>
							<path opacity="0.7" d="M22 28H18V32H22V28Z" fill="#0074BF"/>
							<path opacity="0.5" d="M18 28H14V32H18V28Z" fill="#0074BF"/>
							<path opacity="0.7" d="M34 24H30V28H34V24Z" fill="#0082BF"/>
							<path opacity="0.8" d="M30 24H26V28H30V24Z" fill="#0082BF"/>
							<path d="M26 24H22V28H26V24Z" fill="#0082BF"/>
							<path d="M22 24H18V28H22V24Z" fill="#0082BF"/>
							<path opacity="0.8" d="M18 24H14V28H18V24Z" fill="#0082BF"/>
							<path opacity="0.7" d="M14 24H10V28H14V24Z" fill="#0082BF"/>
							<path opacity="0.5" d="M38 20H34V24H38V20Z" fill="#008FBF"/>
							<path opacity="0.8" d="M34 20H30V24H34V20Z" fill="#008FBF"/>
							<path d="M30 20H26V24H30V20Z" fill="#008FBF"/>
							<path d="M26 20H22V24H26V20Z" fill="#008FBF"/>
							<path d="M22 20H18V24H22V20Z" fill="#008FBF"/>
							<path d="M18 20H14V24H18V20Z" fill="#008FBF"/>
							<path opacity="0.8" d="M14 20H10V24H14V20Z" fill="#008FBF"/>
							<path opacity="0.5" d="M10 20H6V24H10V20Z" fill="#008FBF"/>
							<path opacity="0.7" d="M38 16H34V20H38V16Z" fill="#009DBF"/>
							<path d="M34 16H30V20H34V16Z" fill="#009DBF"/>
							<path d="M30 16H26V20H30V16Z" fill="#009DBF"/>
							<path d="M26 16H22V20H26V16Z" fill="#009DBF"/>
							<path d="M22 16H18V20H22V16Z" fill="#009DBF"/>
							<path d="M18 16H14V20H18V16Z" fill="#009DBF"/>
							<path d="M14 16H10V20H14V16Z" fill="#009DBF"/>
							<path opacity="0.7" d="M10 16H6V20H10V16Z" fill="#009DBF"/>
							<path opacity="0.7" d="M38 12H34V16H38V12Z" fill="#00ABBF"/>
							<path d="M34 12H30V16H34V12Z" fill="#00ABBF"/>
							<path d="M30 12H26V16H30V12Z" fill="#00ABBF"/>
							<path d="M26 12H22V16H26V12Z" fill="#00ABBF"/>
							<path d="M22 12H18V16H22V12Z" fill="#00ABBF"/>
							<path d="M18 12H14V16H18V12Z" fill="#00ABBF"/>
							<path d="M14 12H10V16H14V12Z" fill="#00ABBF"/>
							<path opacity="0.7" d="M10 12H6V16H10V12Z" fill="#00ABBF"/>
							<path opacity="0.5" d="M38 8H34V12H38V8Z" fill="#00B9BF"/>
							<path opacity="0.8" d="M34 8H30V12H34V8Z" fill="#00B9BF"/>
							<path d="M30 8H26V12H30V8Z" fill="#00B9BF"/>
							<path d="M26 8H22V12H26V8Z" fill="#00B9BF"/>
							<path d="M22 8H18V12H22V8Z" fill="#00B9BF"/>
							<path d="M18 8H14V12H18V8Z" fill="#00B9BF"/>
							<path opacity="0.8" d="M14 8H10V12H14V8Z" fill="#00B9BF"/>
							<path opacity="0.5" d="M10 8H6V12H10V8Z" fill="#00B9BF"/>
							<path opacity="0.7" d="M34 4H30V8H34V4Z" fill="#00C6BF"/>
							<path opacity="0.8" d="M30 4H26V8H30V4Z" fill="#00C6BF"/>
							<path d="M26 4H22V8H26V4Z" fill="#00C6BF"/>
							<path d="M22 4H18V8H22V4Z" fill="#00C6BF"/>
							<path opacity="0.8" d="M18 4H14V8H18V4Z" fill="#00C6BF"/>
							<path opacity="0.7" d="M14 4H10V8H14V4Z" fill="#00C6BF"/>
							<path opacity="0.5" d="M30 0H26V4H30V0Z" fill="#00D4BF"/>
							<path opacity="0.7" d="M26 0H22V4H26V0Z" fill="#00D4BF"/>
							<path opacity="0.7" d="M22 0H18V4H22V0Z" fill="#00D4BF"/>
							<path opacity="0.5" d="M18 0H14V4H18V0Z" fill="#00D4BF"/>
							<path d="M16.5141 14.9697L17.6379 12.4572C18.0459 11.8129 17.9958 11.0255 17.5449 10.5745C17.4876 10.5173 17.416 10.46 17.3444 10.4171C17.0366 10.2238 16.6572 10.1808 16.3065 10.2954C15.9199 10.4171 15.5835 10.6748 15.3687 11.0184C15.3687 11.0184 13.8297 14.6046 13.2642 16.2153C12.6987 17.8259 12.9206 20.7822 15.1254 22.987C17.4661 25.3277 20.8448 25.8575 23.0066 24.2397C23.0997 24.1967 23.1784 24.1395 23.2572 24.0751L29.9072 18.5202C30.2293 18.2554 30.7089 17.7042 30.2794 17.0743C29.8642 16.4586 29.0697 16.881 28.7404 17.0886L24.9107 19.8731C24.8391 19.9304 24.7318 19.9232 24.6673 19.8517C24.6673 19.8517 24.6673 19.8445 24.6602 19.8445C24.56 19.7228 24.5456 19.4079 24.696 19.2862L30.5657 14.304C31.074 13.8459 31.1456 13.1802 30.7304 12.7292C30.3295 12.2854 29.6924 12.2997 29.1842 12.7578L23.9157 16.881C23.8155 16.9597 23.6652 16.9454 23.5864 16.8452L23.5793 16.838C23.4719 16.7235 23.4361 16.5231 23.5506 16.4014L29.535 10.596C30.0074 10.1522 30.036 9.4149 29.5922 8.94245C29.3775 8.72054 29.084 8.59169 28.7762 8.59169C28.4612 8.59169 28.1606 8.70623 27.9387 8.92813L21.8255 14.6691C21.6823 14.8122 21.396 14.6691 21.3602 14.4973C21.3459 14.4328 21.3674 14.3684 21.4103 14.3255L26.0918 8.99972C26.5571 8.56306 26.5858 7.83292 26.1491 7.36763C25.7124 6.90234 24.9823 6.87371 24.517 7.31036C24.4955 7.32468 24.4812 7.34615 24.4597 7.36763L17.3659 15.2203C17.1082 15.478 16.736 15.4851 16.557 15.342C16.4425 15.2489 16.4282 15.0843 16.5141 14.9697Z"
							fill="white"/>
						</svg>';					
					}
					break;
				case 'radio':
				case 'checkbox':
					$html .= $this->checkboxesHtml($element, $value, $values, $elType, $elName, $elClass, $elOptions);
					break;
				default:
					$elContent .= "";
			}
			
			//close the field
			if(in_array($element->type, ['select', 'textarea', 'button', 'datalist'])){
				$elClose	= "</{$element->type}>";
			}else{
				$elClose	= "";
			}
			
			//only close a label field if it is not wrapped
			if($element->type == 'label' && empty($element->wrap)){
				$elClose	= "</label>";
			}
			
			/*
				ELEMENT MULTIPLE
			*/
			if(!empty($element->multiple)){
				if($element->type == 'select'){
					$html	= "<$elType name='$elName' $elId class='$elClass' $elOptions>";
				}elseif($element->type == 'text'){
					
					$html	= "<div class='optionwrapper'>";
						// container for choices made
						$html	.= "<ul class='listselectionlist'>";
							// add previous made inputs
							foreach($prevValues as $v){
								$transValue		= $this->transformInputData($v, $element->name, $this->submission->formresults);

								$html	.= "<li class='listselection'>";
									$html	.= "<button type='button' class='small remove-list-selection'>";
										$html	.= "<span class='remove-list-selection'>×</span>";
									$html	.= "</button>";
									$html	.= "<input type='hidden' name='$element->name[]' value='$v'>";
									$html	.= "<span class='selectedname'>$transValue</span>";
								$html	.= "</li>";
							}
						$html	.= "</ul>";

						// add the text input
						$html	.= "<div class='multi-text-input-wrapper'>";
							$html	.= "<$elType id='$elName' class='$elClass datalistinput multiple' $elOptions>";
							$html	.= '<button type="button" class="small add-list-selection hidden">Add</button>';
						$html	.= "</div>";
					$html	.= "</div>";
				}else{
					$html	= "<$elType name='$elName' $elId class='$elClass' $elOptions value='%value%'>$elContent$elClose";
				}
				
				if($element->type != 'text'){
					$this->multiInput($element, $values, $html);
					$html	= "<div class='clone_divs_wrapper'>";
						foreach($this->multiInputsHtml as $h){
							$html	.= $h;
						}
					$html	.= '</div>';
				}
			}elseif(empty($html)){
				$html	= "<$elType  name='$elName' $elId class='$elClass' $elOptions $elValue>$elContent$elClose";
			}
		}

		$html	= $extraHtml.$html;

		// filter the element html
		$html	= apply_filters('sim-forms-element-html', $html, $element, $this);
		
		//remove unnessary whitespaces
		$html = preg_replace('/\s+/', ' ', $html);
		
		//check if we need to transform a keyword to date
		preg_match_all('/%([^%;]*)%/i', $html, $matches);
		foreach($matches[1] as $key=>$keyword){
			$keyword = str_replace('_',' ', $keyword);
			
			//If the keyword is a valid date keyword
			if(!empty(strtotime($keyword))){
				//convert to date
				$dateString = date("Y-m-d", strtotime($keyword));
				
				//update form element
				$html = str_replace($matches[0][$key], $dateString, $html);
			}
		}

		return apply_filters('sim-form-element-html', $html, $element, $this);
	}

	/**
	 * Options html for a select element
	 * Returns all the options of a select element
	 *
	 * @param	object	$element		The element to get the options for
	 * @param	mixed	$value			The value of the element
	 * @param	array	$values			The default values for the element
	 * 
	 * @return	string			The html
	 */
	public function selectOptionsHtml($element, $value, $values){
		$elContent	= "<option value=''>---</option>";
		$options	= $this->getElementOptions($element);

		$selValues	= [];
		if(!empty($values['metavalue'])){
			$selValues	= array_map('strtolower', (array)$values['metavalue']);
		}

		if(!empty($value)){
			if(is_array($value)){
				foreach($value as $v){
					$selValues[] = strtolower($v);
				}
			}else{
				$selValues[] = strtolower($value);
			}
		}

		foreach($options as $key=>$option){
			if(
				in_array(strtolower($option), $selValues) || 
				in_array(strtolower($key), $selValues) || 
				in_array($element->default_value, [$key, $option])
			){
				$selected	= 'selected="selected"';
			}else{
				$selected	= '';
			}
			$elContent .= "<option value='$key' $selected>$option</option>";
		}

		return $elContent;
	}

	/**
	 * Options html for a datalist element
	 * Returns all the options of a datalist
	 *
	 * @param	object	$element		The element to get the options for
	 * 
	 * @return	string			The html
	 */
	public function datalistOptionsHtml($element){
		$options	= $this->getElementOptions($element);
		$elContent	= '';

		foreach($options as $key=>$option){
			if(is_array($option)){
				$value	= $option['value'];
			}else{
				$value	= $option;
			}

			$elContent .= "<option data-value='$key' value='$value'>";

			if(is_array($option)){
				$elContent .= $option['display']."</option>";
			}
		}

		return $elContent;
	}

	/**
	 * Returns all the element of a radio or checkbox element
	 *
	 * @param	object	$element		The element to get the options for
	 * @param	mixed	$value			The value of the element
	 * @param	array	$values			The default values for the element
	 * @param	string	$elType			The element type
	 * @param	string	$elName			The element name
	 * @param	string	$elClass		Classes for the elements
	 * @param	string	$elOptions		The options for the elements
	 * 
	 * @return	string			The html
	 */
	public function checkboxesHtml($element, $value, $values, $elType, $elName, $elClass, $elOptions){
		$options	= $this->getElementOptions($element);
		$html		= "<div class='checkbox_options_group formfield'>";
		
		// get all the default options and make them lowercase
		$lowValues	= [];
		
		$defaultKey				= $element->default_value;
		if(!empty($defaultKey) && !empty($this->defaultValues[$defaultKey])){
			$lowValues[]	= strtolower($this->defaultValues[$defaultKey]);
		}

		if(isset($values['metavalue'] )){
			foreach((array)$values['metavalue'] as $key=>$val){
				if(is_array($val)){
					foreach($val as $v){
						if(is_array($v)){
							foreach($v as $v2){
								$lowValues[] = strtolower($v2);
							}
						}else{
							$lowValues[] = strtolower($v);
						}
					}
				}else{
					$lowValues[] = strtolower($val);
				}
			}
		}

		if(!empty($value)){
			if(is_array($value)){
				foreach($value as $v){
					$lowValues[] = strtolower($v);
				}
			}else{
				$lowValues[] = strtolower($value);
			}
		}

		// check the length of each option
		$maxLength		= 0;
		$totalLength	= 0;
		foreach($options as $option){
			$maxLength		= max($maxLength, strlen($option));
			$totalLength	+= strlen($option);
		}

		// build the options
		foreach($options as $key=>$option){
			if($this->multiwrap){
				$checked	= '%checked%';
			}elseif(
				in_array(strtolower($option), $lowValues) || 
				in_array(strtolower($key), $lowValues) || 
				in_array($element->default_value, [$key, $option])
			){
				$checked	= 'checked';
			}else{
				$checked	= '';
			}

			if(!empty($elId)){
				$id	= trim($elId, "'");
				$id	= "$id-$key'";
			}
			
			$html .= "<label class='checkbox-label'>";
				$html .= "<$elType name='$elName' $id class='$elClass' $elOptions value='$key' $checked>";
				$html .= "<span class='optionlabel'>$option</span>";
			$html .= "</label>";
			if($maxLength > 8 || $totalLength > 30){
				$html .= "</br>";
			}
		}

		$html .= "</div>";

		return $html;
	}
}

