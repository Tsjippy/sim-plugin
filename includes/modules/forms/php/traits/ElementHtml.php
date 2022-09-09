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
	function getFieldValues($element){
		$values	= [];

		// Do not return default values when requesting the html over rest api
		if(defined('REST_REQUEST')){
			return $values;
		}
		
		if(in_array($element->type,$this->nonInputs)){
			return [];
		}

		$this->buildDefaultsArray();

		//get the fieldname, remove [] and split on remaining [
		$fieldName	= str_replace(']', '', explode('[', str_replace('[]', '', $element->name)));
		
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
		
			if(count($fieldName) == 1){
				//non array name
				$fieldName					= $fieldName[0];
				$values['metavalue']		= (array)maybe_unserialize($this->usermeta[$fieldName]);
			}else{
				//an array of values, we only want a specific one
				$values['metavalue']		= (array)maybe_unserialize($this->usermeta[$fieldName[0]]);
				unset($fieldName[0]);
				//loop over all the subkeys, and store the value until we have our final result
				foreach($fieldName as $v){
					$values['metavalue'] = (array)$values['metavalue'][$v];
				}
			}
		}
		
		//add default values
		if(empty($element->multiple) || in_array($element->type, ['select','checkbox'])){
			$defaultKey					= $element->default_value;
			if(!empty($defaultKey)){
				$values['defaults']		= $this->defaultValues[$defaultKey];
			}
		}else{
			$defaultKey					= $element->default_array_value;
			if(!empty($defaultKey)){
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
	function getFieldOptions($element){
		$options	= [];
		//add element values
		$elValues	= explode("\n", $element->valuelist);
		
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
		//Add the key to the fields name
		$elementHtml	= preg_replace("/(name='.*?)'/i", "\${1}[$index]'", $elementHtml);
					
		//we are dealing with a select, add options
		if($element->type == 'select'){
			$elementHtml .= "<option value=''>---</option>";
			$options	= $this->getFieldOptions($element);
			foreach($options as $option_key=>$option){
				if(strtolower($value) == strtolower($option_key) || strtolower($value) == strtolower($option)){
					$selected	= 'selected';
				}else{
					$selected	= '';
				}
				$elementHtml .= "<option value='$option_key' $selected>$option</option>";
			}

			$elementHtml  .= "</select>";
		}elseif(in_array($element->type, ['radio','checkbox'])){
			$options	= $this->getFieldOptions($element);

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

		if(empty($this->formData->settings['save_in_meta'])){
			$values		= array_values((array)$values['defaults']);
		}else{
			$values		= array_values((array)$values['metavalue']);
		}

		//check how many elements we should render
		$this->multiWrapValueCount	= max(1, count((array)$values));

		//create as many inputs as the maximum value found
		for ($index = 0; $index < $this->multiWrapValueCount; $index++) {
			$elementItemHtml	= $this->prepareElementHtml($element, $index, $elementHtml, $values[$index]);
			
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
	 * @param	object	$element	The element
	 * @param	int		$width		The width of the element. Default 100
	 * 
	 * @return	string				The html
	 */
	function getElementHtml($element){
		$html		= '';
		$elClass	= '';
		$elOptions	= '';

		/*
			ELEMENT OPTIONS
		*/
		if(!empty($element->options)){
			//Store options in an array
			$options	= explode("\n", $element->options);
			
			//Loop over the options array
			foreach($options as $option){
				//Remove starting or ending spaces and make it lowercase
				$option 		= trim($option);

				$optionType		= explode('=',$option)[0];
				$optionValue	= str_replace('\\\\', '\\', explode('=',$option)[1]);

				if($optionType == 'class'){
					$elClass .= " $optionValue";
				}else{
					if(!in_array($optionType, ['pattern', 'title', 'accept'])){
						$optionValue = str_replace([' ', ',', '(', ')'],['_', '', '', ''], $optionValue);
					}

					//remove any leading "
					$optionValue	= trim($optionValue, '\'"');

					//Write the corrected option as html
					$elOptions	.= " $optionType='$optionValue'";
				}
			}
		}
		
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
			
			$html = $uploader->getUploadHtml($name, $targetDir, $element->multiple, $elOptions);
		}else{
			/*
				ELEMENT TAG NAME
			*/
			if(in_array($element->type, ['formstep','info'])){
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
			$values	= $this->getFieldValues($element);
			if($this->multiwrap || !empty($element->multiple)){
				if(strpos($elType,'input') !== false){
					$elValue	= "value='%value%'";
				}
			}elseif(!empty($values)){				
				//this is an input and there is a value for it
				if(empty($this->formData->settings['save_in_meta'])){
					$val		= array_values((array)$values['defaults'])[0];
				}else{
					$val		= array_values((array)$values['metavalue'])[0];
				}
				
				if(strpos($elType,'input') !== false && !empty($val)){
					$elValue	= "value='$val'";
				}else{
					$elValue	= "";
				}
			}
			
			/*
				ELEMENT TAG CONTENTS
			*/
			$elContent = "";
			if($element->type == 'textarea'){
				$elContent = $values['metavalue'][0];
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
					$elContent .= "<option value=''>---</option>";
					$options	= $this->getFieldOptions($element);

					$selValues	= '';
					if(!empty($values['metavalue'])){
						$selValues	= array_map('strtolower', (array)$values['metavalue']);
					}
		
					foreach($options as $key=>$option){
						if(in_array(strtolower($option), $selValues) || in_array(strtolower($key), $selValues)){
							$selected	= 'selected';
						}else{
							$selected	= '';
						}
						$elContent .= "<option value='$key' $selected>$option</option>";
					}
					break;
				case 'datalist':
					$options	= $this->getFieldOptions($element);
					foreach($options as $key=>$option){
						$elContent .= "<option data-value='$key' value='$option'>";
					}
					break;
				case 'radio':
				case 'checkbox':
					$options	= $this->getFieldOptions($element);
					$html		= "<div class='checkbox_options_group formfield'>";
					
					$lowValues	= [];
					
					$default_key				= $element->default_value;
					if(!empty($default_key)){
						$lowValues[]	= strtolower($this->defaultValues[$default_key]);
					}
					foreach((array)$values['metavalue'] as $key=>$val){
						if(is_array($val)){
							foreach($val as $v){
								$lowValues[] = strtolower($v);
							}
						}else{
							$lowValues[] = strtolower($val);
						}
					}

					foreach($options as $key=>$option){
						if($this->multiwrap){
							$checked	= '%checked%';
						}elseif(in_array(strtolower($option), $lowValues) || in_array(strtolower($key), $lowValues)){
							$checked	= 'checked';
						}else{
							$checked	= '';
						}
						
						$html .= "<label>";
							$html .= "<$elType name='$elName' class='$elClass' $elOptions value='$key' $checked>";
							$html .= "<span class='optionlabel'>$option</span>";
						$html .= "</label><br>";
					}

					$html .= "</div>";
					break;
				default:
					$elContent .= "";
			}
			
			//close the field
			if(in_array($element->type,['select','textarea','button','datalist'])){
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
				}else{
					$html	= "<$elType name='$elName' $elId class='$elClass' $elOptions value='%value%'>$elContent$elClose";
				}
					
				$this->multiInput($element, $values, $html);
				$html	= "<div class='clone_divs_wrapper'>";
					foreach($this->multiInputsHtml as $h){
						$html	.= $h;
					}
				$html	.= '</div>';
			}elseif(empty($html)){
				$html	= "<$elType  name='$elName' $elId class='$elClass' $elOptions $elValue>$elContent$elClose";
			}
		}
		
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

		return $html;
	}
}