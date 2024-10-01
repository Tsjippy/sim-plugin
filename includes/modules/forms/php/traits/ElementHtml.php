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
		if(!empty($this->formData->save_in_meta)){
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
		$this->multiInputsHtml	= [];
		
		//add label to each entry if prev element is a label and wrapped with this one
		if($this->prevElement->type	== 'label' && !empty($this->prevElement->wrap) && $this->prevElement != $element){
			$this->prevElement->text = $this->prevElement->text.' %key%';
			$prevLabel = $this->getElementHtml($this->prevElement).'</label>';
		}else{
			$prevLabel	= '';
		}

		if(empty($this->formData->save_in_meta) && !empty($values['defaults'])){
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
	 * Get the previous values of a element
	 */
	function getPrevValues($element, $returnArray=false){
		// Check if we should inlcude previous submitted values
		$prevValues		= '';

		if($returnArray){
			$prevValues		= [];
		}
		
		if(str_contains($_SERVER['REDIRECT_URL'], 'get_input_html')){
			$valueIndexes	= explode('[', $element->name);

			foreach($valueIndexes as $i=>$index){
				if($i == 0){
					if(!isset($this->submission->formresults[$index])){
						break;
					}

					$prevValues	= $this->submission->formresults[$index];
				}else{
					if($i == 1 && is_numeric($_POST['subid'])){
						$index	= $_POST['subid'];
					}

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

		return $prevValues;
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
					$html .= '<p class="info_icon"><img draggable="false" role="img" class="emoji" alt="ℹ" src="'.SIM\PICTURESURL.'/info.png" loading="lazy" ></p>';
				$html .= '</div>';
				$html .= "<span class='info_text'>$content</span>";
			$html .= '</div>';
		}elseif(in_array($element->type, ['file','image'])){
			$name		= $element->name;
			
			// Element setting
			if(!empty($element->foldername)){
				if(str_contains($element->foldername, "private/")){
					$targetDir	= $element->foldername;
				}else{
					$targetDir	= "private/".$element->foldername;
				}
			}
			// Form setting
			if(empty($targetDir)){
				$targetDir = $this->formData->upload_path;
			}
			// Default setting
			if(empty($targetDir)){
				$targetDir = 'form_uploads/'.$this->formData->form_name;
			}

			if(empty($this->formData->save_in_meta)){
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
			if(in_array($element->type, ['radio','checkbox']) && !str_contains($elName, '[]')) {
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

			if(str_contains($elName, '[]')){
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
			if(empty($value)){
				$value	= $this->getPrevValues($element);
			}

			$values	= $this->getElementValues($element);
			if($this->multiwrap || !empty($element->multiple)){
				if(str_contains($elType, 'input')){
					$elValue	= "value='%value%'";
				}
			}elseif(!empty($values) || !empty($value)){
				$val	= $value;
				if(empty($value)){
					//this is an input and there is a value for it
					if(!empty($values['defaults']) && (empty($this->formData->save_in_meta) || empty($values['metavalue']))){
						$val		= array_values((array)$values['defaults'])[0];
					}elseif(!empty($values['metavalue'])){
						$elIndex	= 0;
						if(str_contains($element->name, '[]')){
							// Check if there are multiple elements with the same name
							$elements	= $this->getElementByName($element->name, '', false);

							foreach($elements as $elIndex=>$el){
								if($el->id == $element->id){
									break;
								}
							}
						}

						$val		= array_values((array)$values['metavalue'])[$elIndex];
					}
				}

				if(
					str_contains($elType, 'input') && 
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
					if(is_array($value)){
						$value	= array_values($value)[0];
					}
					$elContent = $value;
				}elseif(!empty($val)){
					if(is_array($val)){
						$val	= array_values($val)[0];
					}
					$elContent = $val;
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
							foreach($this->getPrevValues($element, true) as $v){
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
					if(is_array($v)){
						foreach($v as $av){
							if(is_array($av)){
								foreach($av as $aav){
									$lowValues[] = strtolower($aav);
								}
							}else{
								$lowValues[] = strtolower($av);
							}
						}
					}else{
						$lowValues[] = strtolower($v);
					}
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

