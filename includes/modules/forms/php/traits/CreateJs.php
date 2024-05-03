<?php
namespace SIM\FORMS;
use SIM;

require( MODULE_PATH  . 'lib/vendor/autoload.php');

trait CreateJs{
    	/**
	 * Checks if the current form is a multi step form
	 *
	 * @return	bool	True if multistep false otherwise
	 */
	function isMultiStep(){
		if(empty($this->isMultiStepForm)){
			foreach($this->formElements as $el){
				if($el->type == 'formstep'){
					$this->isMultiStepForm	= true;
					return true;
				}
			}

			$this->isMultiStepForm	= false;
			return false;
		}else{
			return $this->isMultiStepForm;
		}
	}
    
    /**
     * Builds the js files for the current form
    */
    function createJs(){
        $this->formName     = $this->formData->name;
        $this->varName      = str_replace('-', '', $this->formName);

        $checks = [];
        $errors = [];

        //Loop over all elements to find any conditions
        foreach($this->formElements as $elementIndex=>$element){
            $conditions	= unserialize($element->conditions);
            //if there are conditions
            if(is_array($conditions)){
                //Loop over the conditions
                foreach($conditions as $conditionIndex=>$condition){
                    //if there are rules build some javascript
                    if(is_array($condition['rules'])){
                        //Open the if statemenet
                        $lastRuleKey			= array_key_last($condition['rules']);
                        $fieldCheckIf 		    = "";
                        $conditionVariables	    = [];
                        $conditionIf			= '';
                        $checkForChange         = false;
                        
                        //Loop over the rules
                        foreach($condition['rules'] as $ruleIndex=>$rule){
                            $fieldNumber1	= $ruleIndex * 2  + 1;
                            $fieldNumber2	= $fieldNumber1 + 1;
                            $equation		= str_replace(' value', '', $rule['equation']);
                            
                            //Get field names of the fields who's value we are checking
                            $conditionalElement		= $this->getElementById($rule['conditional_field']);
                            if(!$conditionalElement){
                                $errors[]   = "Element $element->name has an invalid rule";

                                continue;
                            }

                            $conditionalFieldName		= $conditionalElement->name;
                            $propCompare                = 'elName';

                            if(str_contains($conditionalFieldName, '[]')){
                                $propCompare            = 'el.id';
                                $conditionalFieldName	= 'E'.$conditionalElement->id;
                            }elseif(in_array($conditionalElement->type,['radio','checkbox']) && !str_contains($conditionalFieldName, '[]')) {
                                $conditionalFieldName .= '[]';
                            }

                            $conditionalFieldType		= $conditionalElement->type;

                            if(is_numeric($rule['conditional_field_2'])){
                                $conditionalElement2		= $this->getElementById($rule['conditional_field_2']);
                                if(!$conditionalElement2){
                                    $errors[]   = "Element $element->name has an invalid rule";
                                    continue;
                                }

                                $conditionalField2Name	= $conditionalElement2->name;
                                
                                if(str_contains($conditionalField2Name, '[]')){
                                    $propCompare            = 'el.id';
                                    $conditionalField2Name	= 'E'.$conditionalElement2->id;
                                }elseif(in_array($conditionalElement2->type, ['radio','checkbox']) && !str_contains($conditionalField2Name, '[]')) {
                                    $conditionalField2Name .= '[]';
                                }
                            }
                            
                            //Check if we are calculating a value based on two field values
                            if(($equation == '+' || $equation == '-') && is_numeric($rule['conditional_field_2']) && !empty($rule['equation_2'])){
                                $calc = true;
                            }else{
                                $calc = false;
                            }
                            
                            //make sure we do not include other fields in changed or click rules
                            if(in_array($equation, ['changed', 'clicked'])){
                                // do not add the same element name twice
                                if(!str_contains($fieldCheckIf, $conditionalFieldName)){
                                    if(!empty($fieldCheckIf)){
                                        $fieldCheckIf   .= " || ";
                                    }
                                    $fieldCheckIf   .= "$propCompare == '$conditionalFieldName'";
                                }
                                $checkForChange = true;
                            }
                            
                            //Only allow or statements
                            if(!$checkForChange || (isset($condition['rules'][$ruleIndex-1]) && $condition['rules'][$ruleIndex-1]['combinator'] == 'OR')){
                                // do not add the same element name twice
                                if(!str_contains($fieldCheckIf, "$propCompare == '$conditionalFieldName'")){
                                    //Write the if statement to check if the current clicked field belongs to this condition
                                    if(!empty($fieldCheckIf)){
                                        $fieldCheckIf .= " || ";
                                    }
                                    $fieldCheckIf .= "$propCompare == '$conditionalFieldName'";
                                }
                                
                                // do not add the same element name twice
                                if(!str_contains($fieldCheckIf, "$propCompare == '$conditionalField2Name'")){
                                    //If there is an extra field to check
                                    if(is_numeric($rule['conditional_field_2'])){
                                        $fieldCheckIf .= " || $propCompare == '$conditionalField2Name'";
                                    }
                                }
                            }
            
                            //We calculate the sum or difference of two field values if needed.
                            if($calc){
                                if($conditionalFieldType == 'date'){
                                    //Convert date strings to date values then miliseconds to days
                                    $conditionVariables[]  = "var calculated_value_$ruleIndex = (Date.parse(value_$fieldNumber1) $equation Date.parse(value_$fieldNumber2))/ (1000 * 60 * 60 * 24);";
                                }else{
                                    $conditionVariables[]  = "var calculated_value_$ruleIndex = value_$fieldNumber1 $equation value_$fieldNumber2;";
                                }
                                $equation = $rule['equation_2'];

                                //compare with calculated value
                                $compareValue1 = "calculated_value_$ruleIndex";
                            }else{
                                //compare with a field value
                                $compareValue1 = "value_$fieldNumber1";
                            }
                                
                            //compare with the value of another field
                            if(str_contains($rule['equation'], 'value')){
                                $compareValue2 = "value_$fieldNumber2";
                            //compare with a number
                            }elseif(is_numeric($rule['conditional_value'])){
                                $compareValue2 = trim($rule['conditional_value']);
                            //compare with text
                            }else{
                                $compareValue2 = "'".strtolower(trim($rule['conditional_value']))."'";
                            }
                            
                            /*
                                NOW WE KNOW THAT THE CHANGED FIELD BELONGS TO THIS CONDITION
                                LETS CHECK IF ALL THE VALUES ARE MET AS WELL
                            */
                            if(!in_array($equation, ['changed', 'clicked', 'checked', '!checked', 'visible', 'invisible'])){
                                $conditionVariables[]      = "var value_$fieldNumber1 = FormFunctions.getFieldValue('$conditionalFieldName', form, true, $compareValue2, true);";
                                
                                if(is_numeric($rule['conditional_field_2'])){
                                    $conditionVariables[]  = "var value_$fieldNumber2 = FormFunctions.getFieldValue('$conditionalField2Name', form, true, $compareValue2, true);";
                                }
                            }
                            
                            if(empty($equation)){
                                return new \WP_Error('forms', "$element->name has a rule without equation set. Please check");
                            }elseif($equation == 'checked'){
                                if(count($condition['rules'])==1){
                                    $conditionIf .= "el.checked";
                                }else{
                                    $conditionIf .= "form.querySelector('[name=\"$conditionalFieldName\"]').checked";
                                }
                            }elseif($equation == '!checked'){
                                if(count($condition['rules'])==1){
                                    $conditionIf .= "!el.checked";
                                }else{
                                    $conditionIf .= "!form.querySelector('[name=\"$conditionalFieldName\"]').checked";
                                }
                            }elseif($equation == 'visible'){
                                $conditionIf .= "form.querySelector(\"[name='$conditionalFieldName']\").closest('.hidden') == null";
                            }elseif($equation == 'invisible'){
                                $conditionIf .= "form.querySelector(\"[name='$conditionalFieldName']\").closest('.hidden') != null";
                            }elseif($equation != 'changed' && $equation != 'clicked'){
                                $conditionIf .= "$compareValue1 $equation $compareValue2";
                            }elseif($equation == 'changed' || $equation == 'clicked'){
                                $conditionIf .= "$propCompare == '$conditionalFieldName'";
                            }
                            
                            //If there is another rule, add || or &&
                            if(
                                $lastRuleKey != $ruleIndex                                                      &&  // there is a next rule
                                !empty($conditionIf) 																//there is already preceding code
                            ){
                                if(empty($rule['combinator'])){
                                    $rule['combinator'] = 'AND';
                                    SIM\printArray("Condition index $conditionIndex of $element->name is missing a combinator. I have set it to 'AND' for now");
                                }
                                if($rule['combinator'] == 'AND'){
                                    $conditionIf .= " && ";
                                }else{
                                    $conditionIf .= " || ";
                                }
                            }
                        }

                        $action                             = $condition['action'];

                        //store if statment
                        $fieldCheckIf = "if($fieldCheckIf){";
                        if(!isset($checks[$fieldCheckIf])){
                            $checks[$fieldCheckIf]                                            = [];
                            $checks[$fieldCheckIf]['variables']                               = [];
                            $checks[$fieldCheckIf]['actions']                                 = ['querystrings'=>[$action=>[]]];
                            $checks[$fieldCheckIf]['condition_ifs']                           = [];
                        }
                            
                        //no need for variable in case of a 'changed' condition
                        if(empty($conditionIf)){
                            $actionArray   =&  $checks[$fieldCheckIf]['actions'];
                        }else{
                            $conditionIf       = "if($conditionIf){";
                            if(empty($checks[$fieldCheckIf]['condition_ifs'][$conditionIf])){
                                $array              = [
                                    'actions'       => ['querystrings'=>[$action=>[]]],
                                    'variables'     => [],
                                ];
                                $checks[$fieldCheckIf]['condition_ifs'][$conditionIf]    = $array;
                            }

                            foreach($conditionVariables as $variable){
                                if(!in_array($variable, $checks[$fieldCheckIf]['condition_ifs'][$conditionIf]['variables'])){
                                    $checks[$fieldCheckIf]['condition_ifs'][$conditionIf]['variables'][]    = $variable;
                                }
                            }
                            
                            $actionArray   =&  $checks[$fieldCheckIf]['condition_ifs'][$conditionIf]['actions'];
                        }
                        
                        //show, toggle or hide action for this field
                        if($action == 'show' || $action == 'hide' || $action == 'toggle'){
                            if($action == 'show'){
                                $action = 'remove';
                            }elseif($action == 'hide'){
                                $action = 'add';
                            }

                            if(!is_array($actionArray['querystrings'][$action])){
                                $actionArray['querystrings'][$action] = [];
                            }
                            
                            $name	= $element->name;

                            //formstep do not have an inputwrapper
                            if($element->type == 'formstep'){
                                $actionCode    = "form.querySelector('[name=\"$name\"]').classList.$action('hidden');";
                                if(!in_array($actionCode, $actionArray)){
                                    $actionArray[] = $actionCode;
                                }
                            }else{
                                //only add if there is no wrapping element with the same condition.
                                $prevElement = $this->formElements[$elementIndex];
                                if(
                                    !$prevElement->wrap ||                                                              // this element is not wrapped in the previous one
                                    !in_array($prevElement, $actionArray['querystrings'][$action])   // or the previous element is not in the action array
                                ){
                                    $actionArray['querystrings'][$action][]    = $element;
                                }
                            }

                            foreach($conditions['copyto'] as $fieldIndex){
                                if(!is_numeric($fieldIndex)){
                                    continue;
                                }

                                //find the element with the right id
                                $copyToElement	= $this->getElementById($fieldIndex);
                                if(!$copyToElement){
                                    $errors[]   = "Element $element->name has an invalid rule";
                                    continue;
                                }
                                
                                //formstep do not have an inputwrapper
                                if($copyToElement->type == 'formstep'){
                                    $actionCode    = "form.querySelector('[name=\"$copyToElement->name\"]').classList.$action('hidden');";
                                    if(!in_array($actionCode, $actionArray)){
                                        $actionArray[] = $actionCode;
                                    }
                                }else{
                                    $actionArray['querystrings'][$action][]    = $copyToElement;
                                }
                            }
                        //set property value
                        }elseif($action == 'property' || $action == 'value'){
                            //set the attribute value of one field to the value of another field
                            $selector		= $this->getSelector($element);
                            
                            //fixed prop value
                            if($action == 'value'){
                                $propertyName	                        = $condition['propertyname1'];
                                if(isset($condition['action_value'])){
                                    $varName   = '"'.$condition['action_value'].'"';
                                }
                            //retrieve value from another field
                            }else{
                                $propertyName	= $condition['propertyname'];
                            
                                $copyfieldid	= $condition['property_value'];
                                
                                //find the element with the right id
                                $copyElement = $this->getElementById($copyfieldid);
                                if(!$copyElement){
                                    $errors[]   = "Element $element->name has an invalid rule";
                                    continue;
                                }

                                $copyFieldName	= $copyElement->name;
                                if(str_contains($copyFieldName, '[]')){
                                    $propCompare            = 'el.id';
                                    $copyFieldName	= 'E'.$copyElement->id;
                                }elseif(in_array($copyElement->type,['radio','checkbox']) && !str_contains($copyFieldName, '[]')) {
                                    $copyFieldName .= '[]';
                                }
                                
                                $varName = str_replace(['[]', '[', ']'], ['', '_', ''], $copyFieldName);

                                $varCode = "let $varName = FormFunctions.getFieldValue('$copyFieldName', form);";
                                if(!in_array($varCode, $checks[$fieldCheckIf]['variables'])){
                                    $checks[$fieldCheckIf]['variables'][] = $varCode;
                                }
                            }
                            
                            $addition       = '';
                            if(!empty($condition['addition'])){
                                $addition       = $condition['addition'];
                            }
                            if($propertyName == 'value'){
                                $actionCode    = "FormFunctions.changeFieldValue('$selector', $varName, {$this->varName}.processFields, form, $addition);";
                                if(!in_array($actionCode, $actionArray)){
                                    $actionArray[] = $actionCode;
                                }
                            }else{
                                $actionCode    = "FormFunctions.changeFieldProperty('$selector', '$propertyName', $varName, {$this->varName}.processFields, form, $addition);";
                                if(!in_array($actionCode, $actionArray)){
                                    $actionArray[] = $actionCode;
                                }
                            }
                        }else{
                            SIM\printArray("formbuilder.php writing js: missing action: '$action' for condition $conditionIndex of field {$element->name}");
                        }
                    }
                }
            }
        }
        $js         = "";
        $minifiedJs = "";
        
        /*
        ** DOMContentLoaded JS
        */
        
        $newJs   = "\n\t\tconsole.log('Dynamic $this->formName forms js loaded');";
        $newJs  .= "\n\tdocument.addEventListener('DOMContentLoaded', function() {";
            $newJs.= "\n\t\tFormFunctions.tidyMultiInputs();";

            $tabJs  = '';
                if($this->isMultiStep()){
                    $tabJs.= "\n\t\t\t//show first tab";
                    $tabJs.= "\n\t\t\t// Display the current tab// Current tab is set to be the first tab (0)";
                    $tabJs.= "\n\t\t\tcurrentTab = 0; ";
                    $tabJs.= "\n\t\t\t// Display the current tab";
                    $tabJs.= "\n\t\t\tFormFunctions.showTab(currentTab, form); ";
                }
                if(!empty($this->formData->save_in_meta)){
                    $tabJs.= "\n\t\t\tform.querySelectorAll(`select, input, textarea`).forEach(";
                        $tabJs.= "\n\t\t\t\tel=>{$this->varName}.processFields(el)";
                    $tabJs.= "\n\t\t\t);";
                }
            if(!empty($tabJs)){
                $newJs.= "\n\t\tlet forms = document.querySelectorAll(`[data-formid=\"{$this->formData->id}\"]`);";
                $newJs.= "\n\t\tforms.forEach(form=>{";
                    $newJs.= $tabJs;
                $newJs.= "\n\t\t});";
            }
        $newJs.= "\n\t});";

        $js         .= $newJs;
        $minifiedJs .= \Garfix\JsMinify\Minifier::minify($newJs, array('flaggedComments' => false));

        /*
        ** EVENT LISTENER JS
        */
        $newJs   = '';
        $newJs  .= "\n\tvar prevEl = '';";
        $newJs  .= "\n\n\tvar listener = function(event) {";
            $newJs  .= "\n\t\tvar el			= event.target;";
            $newJs  .= "\n\t\tform			= el.closest('form');";
            $newJs  .= "\n\t\tvar elName		= el.getAttribute('name');";
            $newJs  .= "\n\n\t\tif(elName == '' || elName == undefined){";
                $newJs  .= "\n\t\t\t//el is a nice select";
                $newJs  .= "\n\t\t\tif(el.closest('.nice-select-dropdown') != null && el.closest('.inputwrapper') != null){";
                    $newJs  .= "\n\t\t\t\t//find the select element connected to the nice-select";
                    $newJs  .= "\n\t\t\t\tel.closest('.inputwrapper').querySelectorAll('select').forEach(select=>{";
                        $newJs  .= "\n\t\t\t\t\tif(el.dataset.value == select.value){";
                            $newJs  .= "\n\t\t\t\t\t\tel	= select;";
                            $newJs  .= "\n\t\t\t\t\t\telName = select.name;";
                        $newJs  .= "\n\t\t\t\t\t}";
                    $newJs  .= "\n\t\t\t\t});";
                $newJs  .= "\n\t\t\t}else{";
                    $newJs  .= "\n\t\t\t\treturn;";
                $newJs  .= "\n\t\t\t}";
            $newJs  .= "\n\t\t}";

            $newJs  .= "\n\n\t\t//prevent duplicate event handling";
            $newJs  .= "\n\t\tif(el == prevEl){";
                $newJs  .= "\n\t\t\treturn;";
            $newJs  .= "\n\t\t}";
            
            $newJs  .= "\n\t\tprevEl = el;";
            $newJs  .= "\n\n\t\t//clear event prevenion after 100 ms";
            $newJs  .= "\n\t\tsetTimeout(function(){ prevEl = ''; }, 50);";

            $newJs  .= "\n\n\t\tif(elName == 'nextBtn'){";
                $newJs  .= "\n\t\t\tFormFunctions.nextPrev(1);";
            $newJs  .= "\n\t\t}else if(elName == 'prevBtn'){";
                $newJs  .= "\n\t\t\tFormFunctions.nextPrev(-1);";
            $newJs  .= "\n\t\t}";

            $newJs  .= "\n\n\t\t{$this->varName}.processFields(el);";
        $newJs  .= "\n\t};";
        $newJs  .= "\n\n\twindow.addEventListener('click', listener);";
        $newJs  .= "\n\twindow.addEventListener('input', listener);";

        $js         .= $newJs;
        $minifiedJs .= \Garfix\JsMinify\Minifier::minify($newJs, array('flaggedComments' => false));

        /*
        ** MAIN JS
        */
        $newJs   = '';
        $newJs  .= "\n\n\tthis.processFields    = function(el){";
            $newJs  .= "\n\t\tvar elName = el.getAttribute('name');\n";
            $newJs  .= "\n\t\tvar form	= el.closest('form');\n";
            foreach($checks as $if => $check){
                // empty if is not allowed
                if($if == 'if()'){
                    continue;
                }
                
                $prevVar   = [];
                $newJs  .= "\t\t$if\n";
                foreach($check['variables'] as $variable){
                    //Only write same var definition once
                    $varParts  = explode(' = ', $variable);
                    if($prevVar[$varParts[0]] != $varParts[1]){
                        $newJs  .= "\t\t\t$variable\n";
                        $prevVar[$varParts[0]] = $varParts[1];
                    }
                }

                foreach($check['actions'] as $index=>$action){
                    if($index === 'querystrings'){
                        $newJs  .= $this->buildQuerySelector($action, "\t\t\t");
                    }else{
                        $newJs  .= "\t\t\t$action\n";
                    }
                }

                $prevVar   = [];
                foreach($check['condition_ifs'] as $if=>$prop){
                    foreach($prop['variables'] as $variable){
                        //Only write same var definition once
                        $varParts  = explode(' = ', $variable);
                        if(!isset($prevVar[$varParts[0]]) || $prevVar[$varParts[0]] != $varParts[1]){
                            $newJs  .= "\t\t\t$variable\n";
                            $prevVar[$varParts[0]] = $varParts[1];
                        }
                    }

                    if(!empty($prop['actions'])){
                        $newJs  .= "\n\t\t\t$if\n";
                        foreach($prop['actions'] as $index=>$action){
                            if($index === 'querystrings'){
                                $newJs  .= $this->buildQuerySelector($action, "\t\t\t\t");
                            }else{
                                $newJs  .= "\t\t\t\t$action\n";
                                if(str_contains($action, 'formstep')){
                                    $newJs  .= "\t\t\t\tFormFunctions.updateMultiStepControls(form);\n";
                                }
                            }
                        }
                        $newJs  .= "\t\t\t}\n";
                    }
                }
                $newJs  .= "\t\t}\n\n";
            }
        $newJs  .= "\t};";

        $js         .= $newJs;
        $minifiedJs .= \Garfix\JsMinify\Minifier::minify($newJs, array('flaggedComments' => false));
      
        // Put is all in a namespace variable
        $js         = "var $this->varName = new function(){".$js."\n};\n\n";
        $minifiedJs = "var $this->varName  = new function(){".$minifiedJs."};";

        $extraJs    = "// Loop over the element which value is given in the url;\n";
        $extraJs    .= "if(typeof(urlSearchParams) == 'undefined'){\n\twindow.urlSearchParams = new URLSearchParams(window.location.search.replaceAll('&amp;', '&'));\n}\n";
        $extraJs    .= "Array.from(urlSearchParams).forEach(array => document.querySelectorAll(`[name^='\${array[0]}']`).forEach(el => FormFunctions.changeFieldValue(el, array[1], $this->varName.processFields, el.closest('form'), )));\n\n";
        
        $js         .= $extraJs;
        $minifiedJs .= \Garfix\JsMinify\Minifier::minify($extraJs, array('flaggedComments' => false));   

        /*
        ** EXTERNAL JS
        */
        $extraJs   = apply_filters('sim_form_extra_js', '', $this->formName, false);
        if(!empty($extraJs)){
            if(empty($checks)){
                $js = $extraJs;
            }else{
                $js.= "\n\n";
                $js.= $extraJs;
            }
        }

        //write it all to a file
        //$js			= ob_get_clean();
        if(empty($checks) && empty($extraJs)){
            //create empty files
            file_put_contents($this->jsFileName.'.js', '');
            file_put_contents($this->jsFileName.'.min.js', '');
        }else{
            //Create js file
            file_put_contents($this->jsFileName.'.js', $js);

            //replace long strings for shorter ones
            $minifiedJs=str_replace(
                [
                    "listener",
                    "processFields",
                    'value_',
                    'elName',
                    "\n"
                ],
                [
                    'q',
                    'p',
                    'v_',
                    'n',
                    ''
                ],
                $minifiedJs
            );

            $minifiedJs     .= "\n\n".apply_filters('sim_form_extra_js', '', $this->formName, true);
            // Create minified version
            //$minifier = new Minify\CSS($js);
            //$minifier->minify($this->jsFileName.'.min.js');
            file_put_contents($this->jsFileName.'.min.js', $minifiedJs);
        }
        
        if(!empty($errors)){
            SIM\printArray($errors);
        }

        return $errors;
    }


    function buildQuerySelector($queryStrings, $prefix){
        $actionCode    = '';
        foreach($queryStrings as $action=>$elements){
            //multiple
            if(count($elements) > 1){
                $actionCode    .= "{$prefix}form.querySelectorAll(`";
                $last           = array_key_last($elements);
                foreach($elements as $key=>$element){
                    $actionCode     .= $this->getSelector($element);

                    if($key != $last){
                        $actionCode    .= ', ';
                    }
                }
                $actionCode    .= "`).forEach(el=>{\n";
                    //$actionCode    .= "{$prefix}\ttry{\n";
                        $actionCode    .= "{$prefix}\t\t//Make sure we only do each wrapper once by adding a temp class\n";
                        $actionCode    .= "{$prefix}\t\tif(!el.closest('.inputwrapper').matches('.action-processed')){\n";
                            $actionCode    .= "{$prefix}\t\t\tel.closest('.inputwrapper').classList.add('action-processed');\n";
                            //$actionCode    .= "{$prefix}\t\t\tel.closest('.inputwrapper').classList.$action('hidden');\n";
                            $actionCode    .= "{$prefix}\t\t\tFormFunctions.changeVisibility('$action', el, {$this->varName}.processFields);\n";
                        $actionCode    .= "{$prefix}\t\t}\n";
                    //$actionCode    .= "{$prefix}\t}catch(e){\n";
                        //$actionCode    .= "{$prefix}\t\tel.classList.$action('hidden');\n";
                    //$actionCode    .= "{$prefix}\t}\n";
                $actionCode    .= "{$prefix}});\n";
                $actionCode    .= "{$prefix}document.querySelectorAll('.action-processed').forEach(el=>{el.classList.remove('action-processed')});\n";
            //just one
            }elseif(count($elements) == 1){
                $selector       = $this->getSelector($elements[0]);
                //$actionCode    .= "{$prefix}form.querySelector('$selector').closest('.inputwrapper').classList.$action('hidden');\n";
                $actionCode    .= "{$prefix}FormFunctions.changeVisibility('$action', form.querySelector('$selector').closest('.inputwrapper'), {$this->varName}.processFields);\n";
            }
        }

        return $actionCode;
    }

    function getSelector($element){
        $queryById          = false;
        $name				= $element->name;

        if(str_contains($name, '[]')){
            $queryById          = true;
        }elseif(in_array($element->type, ['radio', 'checkbox']) && !str_contains($name, '[]')) {
            $name .= '[]';
        }

        if(in_array($element->type, ['file', 'image'])){
            $name .= '_files[]';
        }

        if($queryById){
            return "[id^='E$element->id']";
        }elseif(empty($element->multiple)){
            return "[name=\"$name\"]";
        }else{
            // name is followed by an index [0]
            return "[name^=\"{$name}[\"]";
        }
    }
}