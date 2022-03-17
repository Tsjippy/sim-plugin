<?php
namespace SIM\FORMS;
use SIM;
use MatthiasMullie\Minify;

trait create_js{
    function create_js(){
        $this->datatype = $this->formdata->name;

        $checks = [];

        //Loop over all elements to find any conditions
        foreach($this->form_elements as $element_index=>$element){
            $conditions	= unserialize($element->conditions);
            //if there are conditions
            if(is_array($conditions)){
                //Loop over the conditions
                foreach($conditions as $condition_index=>$condition){
                    //if there are rules build some javascript
                    if(is_array($condition['rules'])){
                        //Open the if statemenet
                        $lastrule_key			= array_key_last($condition['rules']);
                        $field_check_if 		= "";
                        $condition_variables	= [];
                        $condition_if			= '';
                        
                        $check_for_change = false;
                        
                        //Loop over the rules
                        foreach($condition['rules'] as $rule_index=>$rule){
                            $fieldnumber_1	= $rule_index*2  + 1;
                            $fieldnumber_2	= $fieldnumber_1 + 1;
                            $equation		= str_replace(' value','',$rule['equation']);
                            
                            //Get field names of the fields who's value we are checking
                            $conditional_element		= $this->getelementbyid($rule['conditional_field']);
                            $conditional_field_name		= $conditional_element->name;
                            if(in_array($conditional_element->type,['radio','checkbox']) and strpos($conditional_field_name, '[]') === false) {
                                $conditional_field_name .= '[]';
                            }
                            $conditional_field_type		= $conditional_element->type;

                            if(is_numeric($rule['conditional_field_2'])){
                                $conditional_element_2		= $this->getelementbyid($rule['conditional_field_2']);
                                $conditional_field_2_name	= $conditional_element_2->name;
                                if(in_array($conditional_element_2->type,['radio','checkbox']) and strpos($conditional_field_2_name, '[]') === false) {
                                    $conditional_field_2_name .= '[]';
                                }
                                $conditional_field_2_type	= $conditional_element_2->type;
                            }
                            
                            //Check if we are calculating a value based on two field values
                            if(($equation == '+' or $equation == '-') and is_numeric($rule['conditional_field_2']) and !empty($rule['equation_2'])){
                                $calc = true;
                            }else{
                                $calc = false;
                            }
                            
                            //make sure we do not include other fields in changed or click rules
                            if(in_array($equation, ['changed','clicked'])){
                                $field_check_if		= "el_name == '$conditional_field_name'";
                                $check_for_change	= true;
                            }
                            
                            //Only allow or statements
                            if(!$check_for_change or $condition['rules'][$rule_index-1]['combinator'] == 'OR'){
                                //Write the if statement to check if the current clicked field belongs to this condition
                                if(!empty($field_check_if)) $field_check_if .= " || ";
                                $field_check_if .= "el_name == '$conditional_field_name'";
                                
                                //If there is an extra field to check
                                if(is_numeric($rule['conditional_field_2'])){
                                    $field_check_if .= " || el_name == '$conditional_field_2_name'";
                                }
                            }
            
                            //We calculate the sum or difference of two field values if needed.
                            if($calc){
                                if($conditional_field_type == 'date'){
                                    //Convert date strings to date values then miliseconds to days
                                    $condition_variables[]  = "var calculated_value_$rule_index = (Date.parse(value_$fieldnumber_1) $equation Date.parse(value_$fieldnumber_2))/ (1000 * 60 * 60 * 24);";
                                }else{
                                    $condition_variables[]  = "var calculated_value_$rule_index = value_$fieldnumber_1 $equation value_$fieldnumber_2;";
                                }
                                $equation = $rule['equation_2'];
                            }
                            
                            //compare with calculated value
                            if($calc){
                                $comparevalue1 = "calculated_value_$rule_index";
                            //compare with a field value
                            }else{
                                $comparevalue1 = "value_$fieldnumber_1";
                            }
                                
                            //compare with the value of another field
                            if(strpos($rule['equation'], 'value') !== false){
                                $comparevalue2 = "value_$fieldnumber_2";
                            //compare with a number
                            }elseif(is_numeric($rule['conditional_value'])){
                                $comparevalue2 = trim($rule['conditional_value']);
                            //compare with text
                            }else{
                                $comparevalue2 = "'".strtolower(trim($rule['conditional_value']))."'";
                            }
                            
                            /* 
                                NOW WE KNOW THAT THE CHANGED FIELD BELONGS TO THIS CONDITION
                                LETS CHECK IF ALL THE VALUES ARE MET AS WELL 
                            */
                            if(!in_array($equation, ['changed','clicked','checked','!checked'])){
                                $condition_variables[]      = "var value_$fieldnumber_1 = get_field_value('$conditional_field_name',true,$comparevalue2);";
                                
                                if(is_numeric($rule['conditional_field_2'])){
                                    $condition_variables[]  = "var value_$fieldnumber_2 = get_field_value('$conditional_field_2_name',true,$comparevalue2);";
                                }
                            }
                            
                            if($equation == 'checked'){
                                if(count($condition['rules'])==1){
                                    $condition_if .= "el.checked == true";
                                }else{
                                    $condition_if .= "form.querySelector('[name=\"$conditional_field_name\"]').checked == true";
                                }
                            }elseif($equation == '!checked'){
                                if(count($condition['rules'])==1){
                                    $condition_if .= "el.checked == false";
                                }else{
                                    $condition_if .= "form.querySelector('[name=\"$conditional_field_name\"]').checked == false";
                                }
                            }elseif($equation != 'changed' and $equation != 'clicked'){
                                $condition_if .= "$comparevalue1 $equation $comparevalue2";
                            }
                            
                            //If there is another rule, add or or and
                            if($lastrule_key != $rule_index){
                                if(
                                    !empty($rule['combinator'])														and //there is a next rule
                                    !empty($condition_if) 															and	//there is already preceding code
                                    !in_array($condition['rules'][$rule_index+1]['equation'],['changed','clicked'])		//The next element will also be included in the if statement
                                ){
                                    if($rule['combinator'] == 'AND'){
                                        $condition_if .= " && ";
                                    }else{
                                        $condition_if .= " || ";
                                    }
                                }
                            }
                        }

                        $action                             = $condition['action'];

                        //store if statment
                        $field_check_if = "if($field_check_if){";
                        if(!isset($checks[$field_check_if])){
                            $checks[$field_check_if]                                            = [];
                            $checks[$field_check_if]['variables']                               = [];
                            $checks[$field_check_if]['actions']                                 = ['querystrings'=>[$action=>[]]];
                            $checks[$field_check_if]['condition_ifs']                           = [];
                        }
                            
                        //no need for variable in case of a 'changed' condition
                        if(empty($condition_if)){
                            $action_array   =&  $checks[$field_check_if]['actions'];
                        }else{
                            $condition_if       = "if($condition_if){";
                            if(empty($checks[$field_check_if]['condition_ifs'][$condition_if])){
                                $array              = [
                                    'actions'       => ['querystrings'=>[$action=>[]]],
                                    'variables'     => [],
                                ];
                                $checks[$field_check_if]['condition_ifs'][$condition_if]    = $array;
                            }

                            foreach($condition_variables as $variable){
                                if(!in_array($variable, $checks[$field_check_if]['condition_ifs'][$condition_if]['variables'])){
                                    $checks[$field_check_if]['condition_ifs'][$condition_if]['variables'][]    = $variable;
                                }
                            }
                            
                            $action_array   =&  $checks[$field_check_if]['condition_ifs'][$condition_if]['actions'];
                        }
                        
                        //show, toggle or hide action for this field
                        if($action == 'show' or $action == 'hide' or $action == 'toggle'){
                            if($action == 'show'){
                                $action = 'remove';
                            }elseif($action == 'hide'){
                                $action = 'add';
                            }

                            if(!is_array($action_array['querystrings'][$action]))   $action_array['querystrings'][$action] = [];
                            
                            $name	= $element->name;

                            //formstep do not have an inputwrapper
                            if($element->type == 'formstep'){
                                $action_code    = "form.querySelector('[name=\"$name\"]').classList.$action('hidden');";
                                if(!in_array($action_code, $action_array)){
                                    $action_array[] = $action_code;
                                }
                            }else{
                                if(in_array($element->type,['radio','checkbox']) and strpos($name, '[]') === false) {
                                    $name .= '[]';
                                }

                                //only add if there is no wrapping element with the same condition.
                                $prev_element = $this->form_elements[$element_index];
                                if(!$prev_element->wrap or !in_array("[name=\"$prev_element->name\"]",$action_array['querystrings'][$action])){
                                    if(empty($element->multiple)){
                                        $action_array['querystrings'][$action][]    = "[name=\"$name\"]";
                                    }else{
                                        $action_array['querystrings'][$action][]    = "[name^=\"$name\"]";
                                    }
                                }
                            }
                            foreach($conditions['copyto'] as $field_index){
                                if(!is_numeric($field_index))	continue;
                                //find the element with the right id
                                $copy_to_element	= $this->getelementbyid($field_index);
                                $name				= $copy_to_element->name;
                                if(in_array($copy_to_element->type,['radio','checkbox']) and strpos($name, '[]') === false) {
                                    $name .= '[]';
                                }
                                
                                //formstep do not have an inputwrapper
                                if($copy_to_element->type == 'formstep'){
                                    $action_code    = "form.querySelector('[name=\"$name\"]').classList.$action('hidden');";
                                    if(!in_array($action_code, $action_array)){
                                        $action_array[] = $action_code;
                                    }
                                }else{
                                    if(empty($copy_to_element->multiple)){
                                        $action_array['querystrings'][$action][]    = "[name=\"$name\"]";
                                    }else{
                                        $action_array['querystrings'][$action][]    = "[name^=\"$name\"]";
                                    }
                                }
                            }
                        //set property value
                        }elseif($action == 'property' or $action == 'value'){
                            //set the attribute value of one field to the value of another field
                            $fieldname		= $element->name;
                            if($element->type == 'checkbox') $fieldname .= '[]';
                            
                            //fixed prop value
                            if($action == 'value'){
                                $propertyname	                        = $condition['propertyname1'];
                                if(isset($condition['action_value'])){
                                    $var_name   = '"'.$condition['action_value'].'"';
                                }
                            //retrieve value from another field
                            }else{
                                $propertyname	= $condition['propertyname'];
                            
                                $copyfieldid	= $condition['property_value'];
                                
                                //find the element with the right id
                                $copy_element = $this->getelementbyid($copyfieldid);
                                $copyfieldname	= $copy_element->name;
                                if($copy_element->type == 'checkbox') $copyfieldname .= '[]';
                                
                                $var_name = str_replace(['[]','[',']'],['','_',''],$copyfieldname);

                                $var_code = "var $var_name = get_field_value('$copyfieldname');";
                                if(!in_array($var_code, $checks[$field_check_if]['variables'])){
                                    $checks[$field_check_if]['variables'][] = $var_code;
                                }
                            }
                            
                            if($propertyname == 'value'){
                                $action_code    = "change_field_value('$fieldname', $var_name, {$this->datatype}.process_fields);";
                                if(!in_array($action_code, $action_array)){
                                    $action_array[] = $action_code;
                                }
                            }else{
                                //$action_code    = "change_field_property('$fieldname', '$propertyname', replacement_value);";
                                $action_code    = "change_field_property('$fieldname', '$propertyname', $var_name, {$this->datatype}.process_fields);";
                                if(!in_array($action_code, $action_array)){
                                    $action_array[] = $action_code;
                                }
                            }
                        }else{
                            SIM\print_array("formbuilder.php writing js: missing action: '$action' for condition $condition_index of field {$element->name}");
                        }
                    }
                }
            }
        }
        
        ob_start(); 

        $js         = "";
        $minifiedJs = "";
        
        /* 
        ** DOMContentLoaded JS
        */
        $newJs   = "\n\tdocument.addEventListener('DOMContentLoaded', function() {";
            $newJs.= "\n\t\tconsole.log('Dynamic $this->datatype forms js loaded');";
            $newJs.= "\n\t\ttidy_multi_inputs();";
            $newJs.= "\n\t\tform = document.getElementById('sim_form_$this->datatype');";
            if($this->is_multi_step()){
                $newJs.= "\n\t\t//show first tab";
                $newJs.= "\n\t\t// Display the current tab// Current tab is set to be the first tab (0)";
                $newJs.= "\n\t\tcurrentTab = 0; ";
                $newJs.= "\n\t\t// Display the current tab";
                $newJs.= "\n\t\tshowTab(currentTab,form); ";
            }
            if(!empty($this->formdata->settings['save_in_meta'])){
                $newJs.= "\n\t\tform.querySelectorAll('select, input, textarea').forEach(";
                    $newJs.= "\n\t\t\tel=>{$this->datatype}.process_fields(el)";
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
                $newJs  .= "\n\t\t\tnextPrev(1);";
            $newJs  .= "\n\t\t}else if(el_name == 'prevBtn'){";
                $newJs  .= "\n\t\t\tnextPrev(-1);";
            $newJs  .= "\n\t\t}";

            $newJs  .= "\n\n\t\t{$this->datatype}.process_fields(el);";
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
                $prev_var   = [];
                $newJs  .= "\t\t$if\n";
                foreach($check['variables'] as $variable){
                    //Only write same var definition once
                    $var_parts  = explode(' = ',$variable);
                    if($prev_var[$var_parts[0]] != $var_parts[1]){
                        $newJs  .= "\t\t\t$variable\n";
                        $prev_var[$var_parts[0]] = $var_parts[1];
                    }
                }

                foreach($check['actions'] as $index=>$action){
                    if($index === 'querystrings'){
                        $newJs  .= build_query_selector($action);
                    }else{
                        $newJs  .= "\t\t\t$action\n";
                    }
                }

                $prev_var   = [];
                foreach($check['condition_ifs'] as $if=>$prop){
                    foreach($prop['variables'] as $variable){
                        //Only write same var definition once
                        $var_parts  = explode(' = ',$variable);
                        if($prev_var[$var_parts[0]] != $var_parts[1]){
                            $newJs  .= "\t\t\t$variable\n";
                            $prev_var[$var_parts[0]] = $var_parts[1];
                        }
                    }

                    if(!empty($prop['actions'])){
                        $newJs  .= "\n\t\t\t$if\n";
                        foreach($prop['actions'] as $index=>$action){
                            if($index === 'querystrings'){
                                $newJs  .= build_query_selector($action);
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
        $js         = "var {$this->datatype} = new function(){".$js."\n}";  
        $minifiedJs = "var {$this->datatype} = new function(){".$minifiedJs."}";

        /* 
        ** EXTERNAL JS
        */
        $extraJs   = apply_filters('form_extra_js', $this->datatype, false);
        if(!empty($extraJs)){
            $js.= "\n\n";
            $js.= $extraJs;
        }
        $minifiedJs .= apply_filters('form_extra_js', $this->datatype, true);


        //write it all to a file
        //$js			= ob_get_clean();
        if(empty($checks)){
            //create empty files
            file_put_contents($this->jsfilename.'.js', '');
            file_put_contents($this->jsfilename.'.min.js', '');
        }else{
            //Create js file
            file_put_contents($this->jsfilename.'.js', $js);

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
            // Create minified version
            //$minifier = new Minify\CSS($js);
            //$minifier->minify($this->jsfilename.'.min.js');
            file_put_contents($this->jsfilename.'.min.js', $minifiedJs);
        }         
    }
}

function build_query_selector($querystrings){
    $action_code    = '';
    foreach($querystrings as $action=>$names){
        //multiple
        if(count($names)>1){
            $action_code    .= "\t\t\tform.querySelectorAll('";
            $last           = array_key_last($names);
            foreach($names as $key=>$name){
                $action_code    .= $name;
                if($key != $last)   $action_code    .= ', ';
            }
            $action_code    .= "').forEach(el=>{\n";
                $action_code    .= "\t\t\t\ttry{\n";
                    $action_code    .= "\t\t\t\t\t//Make sure we only do each wrapper once by adding a temp class\n";
                    $action_code    .= "\t\t\t\t\tif(!el.closest('.inputwrapper').matches('.action-processed')){\n";
                        $action_code    .= "\t\t\t\t\t\tel.closest('.inputwrapper').classList.add('action-processed');\n";
                        $action_code    .= "\t\t\t\t\t\tel.closest('.inputwrapper').classList.$action('hidden');\n";
                    $action_code    .= "\t\t\t\t\t}\n";
                $action_code    .= "\t\t\t\t}catch(e){\n";
                    $action_code    .= "\t\t\t\t\tel.classList.$action('hidden');\n";
                $action_code    .= "\t\t\t\t}\n";
            $action_code    .= "\t\t\t});\n";
            $action_code    .= "\t\t\tdocument.querySelectorAll('.action-processed').forEach(el=>{el.classList.remove('action-processed')});\n";
        //just one
        }elseif(count($names)==1){
            $action_code    .= "\t\t\tform.querySelector('{$names[0]}')";
            $action_code    .= ".closest('.inputwrapper').classList.$action('hidden');\n";
        }
    }

    return $action_code;
}