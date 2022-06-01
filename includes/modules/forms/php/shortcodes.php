<?php
namespace SIM\FORMS;
use SIM;

add_shortcode('formselector', function($atts){
    global $wpdb;

    ob_start();

    $a = shortcode_atts( array(
        'exclude'   => [],
        'no_meta'   => true
    ), $atts );

    $FormTable	= new FormTable();
    $FormTable->getForms();

    $forms          = $FormTable->forms;

    // Remove any unwanted forms
    if(!empty($a['exclude']) or $a['no_meta']){
        $exclusions = explode(',', $a['exclude']);
        foreach($forms as $key=>$form){
            if(in_array($form->name, $exclusions) or empty($form->name)){
                unset($forms[$key]);
            }

            // Remove any form that saves its data in the usermeta
            if($a['no_meta']){
                if(unserialize($form->settings)['save_in_meta']){
                    unset($forms[$key]);
                }
            }
        }
    }

    //Sort form names by alphabeth
    usort($forms, function($a, $b) {
        return strcasecmp($a->name, $b->name);
    });

    ?>
    <div id="forms-wrapper">
        <?php
        //only show selector if not queried
        if(!isset($_REQUEST['form'])){
        ?>
        <div id="form-selector-wrapper">
            <label>Select the form you want to submit or view the results of</label>
            <br>
            <select id="sim-forms-selector">
            <?php
            foreach($forms as $form){
                $name   = ucfirst(str_replace('_', ' ', $form->name));
                if($_REQUEST['form'] == $form->name or $_REQUEST['form'] == $form->id){
                    $selected = 'selected';
                }else{
                    $selected = '';
                }
                echo "<option value='$form->name' $selected>$name</option>";
            }
            ?>
            </select>
        </div>

        <?php
        }
        if($_REQUEST['display'] == 'results'){
            $formVis       = ' hidden';
            $resultVis     = '';
            $formActive    = ' active';
            $resultActive  = '';
        }else{
            $formVis       = '';
            $resultVis     = ' hidden';
            $formActive    = ' active';
            $resultActive  = '';
        }
        // Loop over the forms to add the to the page
        foreach($forms as $form){
		    $query			= "SELECT * FROM {$FormTable->shortcodeTable} WHERE form_id= '{$form->id}'";
		    $shortcodeData 	= $wpdb->get_results($query);

            //Create shortcode data if not existing
            if(empty($shortcodeData)){
                $shortcodeId   = $FormTable->insertInDb($form->id);
            }else{
                $shortcodeId   = $shortcodeData[0]->id;
            }

            //Check if this form should be displayed
            if($_REQUEST['form'] == $form->name or $_REQUEST['form'] == $form->id){
                $hidden = '';
            }else{
                $hidden = ' hidden';
            }

            echo "<div id='{$form->name}' class='main_form_wrapper$hidden'>";
                //only show button if not queried
                if(!isset($_REQUEST['display'])){
                    echo "<button class='button tablink$formActive' id='show_{$form->name}_form' data-target='{$form->name}_form'>Show form</button>";
                    echo "<button class='button formresults tablink$resultActive' id='show_{$form->name}_results' data-target='{$form->name}_results'>Show form results</button>";
                }

                echo "<div id='{$form->name}_form' class='form_wrapper$formVis'>";
                    echo do_shortcode("[formbuilder formname=$form->name]");
                echo "</div>";

                echo "<div id='{$form->name}_results' class='form_results_wrapper$resultVis'>";
                    echo do_shortcode("[formresults id=$shortcodeId formname=$form->name]");
                echo "</div>";
            echo "</div>";
        }
        ?>
    </div>
    <?php

    return ob_get_clean();
});

//shortcode to make forms
add_shortcode( 'formbuilder', function($atts){
    $formBuilder = new Formbuilder();
    return $formBuilder->formBuilder($atts);
});

add_shortcode( 'formresults', function($atts){
	$formTable = new FormTable();
	return $formTable->showFormresultsTable($atts);
});

add_filter('sim_loggedin_homepage',  function($content){
	$content	.= missingFormFields(['type'=>"all"]);
	return $content;
});

//Shortcode for recommended fields
add_shortcode("missing_form_fields", __NAMESPACE__.'\missingFormFields');

function missingFormFields($atts){
    $a = shortcode_atts( array(
        'type'   => 'mandatory'
    ), $atts );

	$html	= '';

    $fieldHtml = getAllFields(get_current_user_id(), $a['type']);
	
	if (!empty($fieldHtml)){
		$html .=  '<div id=recommendations style="margin-top:20px;">';
            $html .=  '<h3 class="frontpage">Recommendations</h3>';
            $html .=  '<p>It would be very helpfull if you could fill in the fields below:</p>';
            $html .=  $fieldHtml;
        $html .=  '</div>';
	}
	
	return $html;
}