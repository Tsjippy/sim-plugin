<?php
namespace SIM\FORMS;
use SIM;
use MatthiasMullie\Minify;

trait CreateJs{
    /** 
     * Builds the js files for the current form
    */
    function createJs(){
        $this->formName = $this->formData->name;

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
                            if(in_array($conditionalElement->type,['radio','checkbox']) and strpos($conditionalFieldName, '[]') === false) {
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
                                if(in_array($conditionalElement2->type, ['radio','checkbox']) and strpos($conditionalField2Name, '[]') === false) {
                                    $conditionalField2Name .= '[]';
                                }
                            }
                            
                            //Check if we are calculating a value based on two field values
                            if(($equation == '+' or $equation == '-') and is_numeric($rule['conditional_field_2']) and !empty($rule['equation_2'])){
                                $calc = true;
                            }else{
                                $calc = false;
                            }
                            
                            //make sure we do not include other fields in changed or click rules
                            if(in_array($equation, ['changed','clicked'])){
                                $fieldCheckIf		= "el_name == '$conditionalFieldName'";
                                $checkForChange	= true;
                            }
                            
                            //Only allow or statements
                            if(!$checkForChange or $condition['rules'][$ruleIndex-1]['combinator'] == 'OR'){
                                //Write the if statement to check if the current clicked field belongs to this condition
                                if(!empty($fieldCheckIf)) $fieldCheckIf .= " || ";
                                $fieldCheckIf .= "el_name == '$conditionalFieldName'";
                                
                                //If there is an extra field to check
                                if(is_numeric($rule['conditional_field_2'])){
                                    $fieldCheckIf .= " || el_name == '$conditionalField2Name'";
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
                            }
                            
                            //compare with calculated value
                            if($calc){
                                $compareValue1 = "calculated_value_$ruleIndex";
                            //compare with a field value
                            }else{
                                $compareValue1 = "value_$fieldNumber1";
                            }
                                
                            //compare with the value of another field
                            if(strpos($rule['equation'], 'value') !== false){
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
                            if(!in_array($equation, ['changed','clicked','checked','!checked'])){
                                $conditionVariables[]      = "var value_$fieldNumber1 = FormFunctions.getFieldValue('$conditionalFieldName',true,$compareValue2,true);";
                                
                                if(is_numeric($rule['conditional_field_2'])){
                                    $conditionVariables[]  = "var value_$fieldNumber2 = FormFunctions.getFieldValue('$conditionalField2Name',true,$compareValue2, true);";
                                }
                            }
                            
                            if($equation == 'checked'){
                                if(count($condition['rules'])==1){
                                    $conditionIf .= "el.checked == true";
                                }else{
                                    $conditionIf .= "form.querySelector('[name=\"$conditionalFieldName\"]').checked == true";
                                }
                            }elseif($equation == '!checked'){
                                if(count($condition['rules'])==1){
                                    $conditionIf .= "el.checked == false";
                                }else{
                                    $conditionIf .= "form.querySelector('[name=\"$conditionalFieldName\"]').checked == false";
                                }
                            }elseif($equation != 'changed' and $equation != 'clicked'){
                                $conditionIf .= "$compareValue1 $equation $compareValue2";
                            }
                            
                            //If there is another rule, add or or and
                            if($lastRuleKey != $ruleIndex){
                                if(
                                    !empty($rule['combinator'])														and //there is a next rule
                                    !empty($conditionIf) 															and	//there is already preceding code
                                    !in_array($condition['rules'][$ruleIndex+1]['equation'],['changed','clicked'])		//The next element will also be included in the if statement
                                ){
                                    if($rule['combinator'] == 'AND'){
                                        $conditionIf .= " && ";
                                    }else{
                                        $conditionIf .= " || ";
                                    }
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
                        if($action == 'show' or $action == 'hide' or $action == 'toggle'){
                            if($action == 'show'){
                                $action = 'remove';
                            }elseif($action == 'hide'){
                                $action = 'add';
                            }

                            if(!is_array($actionArray['querystrings'][$action]))   $actionArray['querystrings'][$action] = [];
                            
                            $name	= $element->name;

                            //formstep do not have an inputwrapper
                            if($element->type == 'formstep'){
                                $actionCode    = "form.querySelector('[name=\"$name\"]').classList.$action('hidden');";
                                if(!in_array($actionCode, $actionArray)){
                                    $actionArray[] = $actionCode;
                                }
                            }else{
                                if(in_array($element->type,['radio','checkbox']) and strpos($name, '[]') === false) {
                                    $name .= '[]';
                                }

                                if(in_array($element->type, ['file', 'image'])){
                                    $name .= '_files[]';
                                }

                                //only add if there is no wrapping element with the same condition.
                                $prevElement = $this->formElements[$elementIndex];
                                if(!$prevElement->wrap or !in_array("[name=\"$prevElement->name\"]", $actionArray['querystrings'][$action])){
                                    if(empty($element->multiple)){
                                        $actionArray['querystrings'][$action][]    = "[name=\"$name\"]";
                                    }else{
                                        $actionArray['querystrings'][$action][]    = "[name^=\"$name\"]";
                                    }
                                }
                            }

                            foreach($conditions['copyto'] as $fieldIndex){
                                if(!is_numeric($fieldIndex))	continue;
                                //find the element with the right id
                                $copyToElement	= $this->getElementById($fieldIndex);
                                if(!$copyToElement){
                                    $errors[]   = "Element $element->name has an invalid rule";
                                    continue;
                                }

                                $name				= $copyToElement->name;
                                if(in_array($copyToElement->type, ['radio', 'checkbox']) and strpos($name, '[]') === false) {
                                    $name .= '[]';
                                }

                                if(in_array($copyToElement->type, ['file', 'image'])){
                                    $name .= '_files[]';
                                }
                                
                                //formstep do not have an inputwrapper
                                if($copyToElement->type == 'formstep'){
                                    $actionCode    = "form.querySelector('[name=\"$name\"]').classList.$action('hidden');";
                                    if(!in_array($actionCode, $actionArray)){
                                        $actionArray[] = $actionCode;
                                    }
                                }else{
                                    if(empty($copyToElement->multiple)){
                                        $actionArray['querystrings'][$action][]    = "[name=\"$name\"]";
                                    }else{
                                        $actionArray['querystrings'][$action][]    = "[name^=\"$name\"]";
                                    }
                                }
                            }
                        //set property value
                        }elseif($action == 'property' or $action == 'value'){
                            //set the attribute value of one field to the value of another field
                            $fieldName		= $element->name;
                            if($element->type == 'checkbox') $fieldName .= '[]';
                            
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
                                $copy_element = $this->getElementById($copyfieldid);
                                if(!$copy_element){
                                    $errors[]   = "Element $element->name has an invalid rule";
                                    continue;
                                }

                                $copyfieldname	= $copy_element->name;
                                if($copy_element->type == 'checkbox') $copyfieldname .= '[]';
                                
                                $varName = str_replace(['[]','[',']'],['','_',''],$copyfieldname);

                                $varCode = "var $varName = FormFunctions.getFieldValue('$copyfieldname');";
                                if(!in_array($varCode, $checks[$fieldCheckIf]['variables'])){
                                    $checks[$fieldCheckIf]['variables'][] = $varCode;
                                }
                            }
                            
                            if($propertyName == 'value'){
                                $actionCode    = "FormFunctions.changeFieldValue('$fieldName', $varName, {$this->formName}.process_fields);";
                                if(!in_array($actionCode, $actionArray)){
                                    $actionArray[] = $actionCode;
                                }
                            }else{
                                $actionCode    = "FormFunctions.changeFieldProperty('$fieldName', '$propertyName', $varName, {$this->formName}.process_fields);";
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
        $newJs   = "\n\tdocument.addEventListener('DOMContentLoaded', function() {";
            $newJs.= "\n\t\tconsole.log('Dynamic $this->formName forms js loaded');";
            $newJs.= "\n\t\tFormFunctions.tidyMultiInputs();";
            $newJs.= "\n\t\tform = document.querySelector('[data-formid=\"{$this->formData->id}\"]');";
            if($this->isMultiStep()){
                $newJs.= "\n\t\t//show first tab";
                $newJs.= "\n\t\t// Display the current tab// Current tab is set to be the first tab (0)";
                $newJs.= "\n\t\tcurrentTab = 0; ";
                $newJs.= "\n\t\t// Display the current tab";
                $newJs.= "\n\t\tFormFunctions.showTab(currentTab,form); ";
            }
            if(!empty($this->formData->settings['save_in_meta'])){
                $newJs.= "\n\t\tform.querySelectorAll('select, input, textarea').forEach(";
                    $newJs.= "\n\t\t\tel=>{$this->formName}.process_fields(el)";
                $newJs.= "\n\t\t);";
            }
        $newJs.= "\n\t});";

        $js         .= $newJs;
        $minifiedJs .= \Garfix\JsMinify\Minifier::minify($newJs, array('flaggedComments' => false));

        /* 
        ** EVENT LISTENER JS
        */
        $newJs   = '';
        $newJs  .= "\n\tvar prev_el = '';";
        $newJs  .= "\n\n\tvar listener = function(event) {";
            $newJs  .= "\n\t\tvar el			= event.target;";
            $newJs  .= "\n\t\tform			= el.closest('form');";
            $newJs  .= "\n\t\tvar el_name		= el.name;";
            $newJs  .= "\n\n\t\tif(el_name == '' || el_name == undefined){";
                $newJs  .= "\n\t\t\t//el is a nice select";
                $newJs  .= "\n\t\t\tif(el.closest('.nice-select-dropdown') != null && el.closest('.inputwrapper') != null){";
                    $newJs  .= "\n\t\t\t\t//find the select element connected to the nice-select";
                    $newJs  .= "\n\t\t\t\tel.closest('.inputwrapper').querySelectorAll('select').forEach(select=>{";
                        $newJs  .= "\n\t\t\t\t\tif(el.dataset.value == select.value){";
                            $newJs  .= "\n\t\t\t\t\t\tel	= select;";
                            $newJs  .= "\n\t\t\t\t\t\tel_name = select.name;";
                        $newJs  .= "\n\t\t\t\t\t}";
                    $newJs  .= "\n\t\t\t\t});";
                $newJs  .= "\n\t\t\t}else{";
                    $newJs  .= "\n\t\t\t\treturn;";
                $newJs  .= "\n\t\t\t}";
            $newJs  .= "\n\t\t}";

            $newJs  .= "\n\n\t\t//prevent duplicate event handling";
            $newJs  .= "\n\t\tif(el == prev_el){";
                $newJs  .= "\n\t\t\treturn;";
            $newJs  .= "\n\t\t}";
            
            $newJs  .= "\n\t\tprev_el = el;";
            $newJs  .= "\n\n\t\t//clear event prevenion after 100 ms";
            $newJs  .= "\n\t\tsetTimeout(function(){ prev_el = ''; }, 100);";

            $newJs  .= "\n\n\t\tif(el_name == 'nextBtn'){";
                $newJs  .= "\n\t\t\tFormFunctions.nextPrev(1);";
            $newJs  .= "\n\t\t}else if(el_name == 'prevBtn'){";
                $newJs  .= "\n\t\t\tFormFunctions.nextPrev(-1);";
            $newJs  .= "\n\t\t}";

            $newJs  .= "\n\n\t\t{$this->formName}.process_fields(el);";
        $newJs  .= "\n\t};";
        $newJs  .= "\n\n\twindow.addEventListener('click', listener);";
        $newJs  .= "\n\twindow.addEventListener('input', listener);";

        $js         .= $newJs;
        $minifiedJs .= \Garfix\JsMinify\Minifier::minify($newJs, array('flaggedComments' => false));

        /* 
        ** MAIN JS
        */
        $newJs   = '';
        $newJs  .= "\n\n\tthis.process_fields    = function(el){";
            $newJs  .= "\n\t\tvar el_name = el.name;\n";
            foreach($checks as $if => $check){
                $prevVar   = [];
                $newJs  .= "\t\t$if\n";
                foreach($check['variables'] as $variable){
                    //Only write same var definition once
                    $varParts  = explode(' = ',$variable);
                    if($prevVar[$varParts[0]] != $varParts[1]){
                        $newJs  .= "\t\t\t$variable\n";
                        $prevVar[$varParts[0]] = $varParts[1];
                    }
                }

                foreach($check['actions'] as $index=>$action){
                    if($index === 'querystrings'){
                        $newJs  .= buildQuerySelector($action);
                    }else{
                        $newJs  .= "\t\t\t$action\n";
                    }
                }

                $prevVar   = [];
                foreach($check['condition_ifs'] as $if=>$prop){
                    foreach($prop['variables'] as $variable){
                        //Only write same var definition once
                        $varParts  = explode(' = ', $variable);
                        if($prevVar[$varParts[0]] != $varParts[1]){
                            $newJs  .= "\t\t\t$variable\n";
                            $prevVar[$varParts[0]] = $varParts[1];
                        }
                    }

                    if(!empty($prop['actions'])){
                        $newJs  .= "\n\t\t\t$if\n";
                        foreach($prop['actions'] as $index=>$action){
                            if($index === 'querystrings'){
                                $newJs  .= buildQuerySelector($action);
                            }else{
                                $newJs  .= "\t\t\t\t$action\n";
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
        $js         = "var {$this->formName} = new function(){".$js."\n};";  
        $minifiedJs = "var {$this->formName} = new function(){".$minifiedJs."};";

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
        if(empty($checks) and empty($extraJs)){
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
                    "process_fields",
                    'value_',
                    'el_name',
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
        
        if(!empty($errors)) SIM\printArray($errors);

        return $errors;
    }
}

function buildQuerySelector($queryStrings){
    $actionCode    = '';
    foreach($queryStrings as $action=>$names){
        //multiple
        if(count($names)>1){
            $actionCode    .= "\t\t\tform.querySelectorAll('";
            $last           = array_key_last($names);
            foreach($names as $key=>$name){
                $actionCode    .= $name;
                if($key != $last)   $actionCode    .= ', ';
            }
            $actionCode    .= "').forEach(el=>{\n";
                $actionCode    .= "\t\t\t\ttry{\n";
                    $actionCode    .= "\t\t\t\t\t//Make sure we only do each wrapper once by adding a temp class\n";
                    $actionCode    .= "\t\t\t\t\tif(!el.closest('.inputwrapper').matches('.action-processed')){\n";
                        $actionCode    .= "\t\t\t\t\t\tel.closest('.inputwrapper').classList.add('action-processed');\n";
                        $actionCode    .= "\t\t\t\t\t\tel.closest('.inputwrapper').classList.$action('hidden');\n";
                    $actionCode    .= "\t\t\t\t\t}\n";
                $actionCode    .= "\t\t\t\t}catch(e){\n";
                    $actionCode    .= "\t\t\t\t\tel.classList.$action('hidden');\n";
                $actionCode    .= "\t\t\t\t}\n";
            $actionCode    .= "\t\t\t});\n";
            $actionCode    .= "\t\t\tdocument.querySelectorAll('.action-processed').forEach(el=>{el.classList.remove('action-processed')});\n";
        //just one
        }elseif(count($names)==1){
            $actionCode    .= "\t\t\tform.querySelector('{$names[0]}')";
            $actionCode    .= ".closest('.inputwrapper').classList.$action('hidden');\n";
        }
    }

    return $actionCode;
}