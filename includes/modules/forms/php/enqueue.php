<?php
namespace SIM\FORMS;
use SIM;

function checkFormExistence($formName){
    $simForms    = new SimForms();
    $simForms->getForms();

    // check if a form with this name already exists
    $found  = false;
    foreach($simForms->forms as $form){
        if($form->name == $formName){
            $found  = true;
            break;
        }
    }

    // Only add a new form if it does not exist yet
    if(!$found){
        $simForms->insertForm($formName);

        return $simForms->formName;
    }

    return $formName;
}

add_action( 'wp_after_insert_post', function($postId, $post){
    global $Modules;

    if(has_block('sim/formbuilder', $post)){
        $hasFormbuilderShortcode    = true;

        $blocks                    = parse_blocks($post->post_content);

        foreach($blocks as $block){
            if($block['blockName'] == 'sim/formbuilder' && !empty($block['attrs']['formname'])){
                checkFormExistence($block['attrs']['formname']);
            }
        }
    }else{
        $hasFormbuilderShortcode    = has_shortcode($post->post_content, 'formbuilder') ;

        // Add the form if it does not exist yet
        if($hasFormbuilderShortcode){
            preg_match_all(
                '/' . get_shortcode_regex() . '/',
                $post->post_content,
                $shortcodes,
                PREG_SET_ORDER
            );

            // loop over all the found shortcodes
            foreach($shortcodes as $shortcode){
                // Only continue if the current shortcode is a formbuilder shortcode
                if($shortcode[2] == 'formbuilder'){
                    // Get the formbuilder name from the shortcode
                    $formName       = shortcode_parse_atts($shortcode[3])['formname'];

                    $newFormName    = checkFormExistence($formName);

                    // Check if we should adjust the name
                    if($formName != $newFormName){
                        $newShortcode  = str_replace($formName, $newFormName, $shortcode[0]);

                        $content        = str_replace($shortcode[0], $newShortcode, $post->post_content);
                        wp_update_post(
                            array(
                                'ID'           => $postId,
                                'post_content' => $content,
                            ),
                            false,
                            false
                        );
                    }
                }
            }
        }
    }

    if($hasFormbuilderShortcode || has_shortcode($post->post_content, 'formselector') || has_block('sim/formbuilder', $post)){
        if(!is_array($Modules[MODULE_SLUG]['formbuilder_pages'])){
            $Modules[MODULE_SLUG]['formbuilder_pages']    = [$postId];
        }elseif(!in_array($postId, $Modules[MODULE_SLUG]['formbuilder_pages'])){
            $Modules[MODULE_SLUG]['formbuilder_pages'][]  = $postId;
        }

        update_option('sim_modules', $Modules);
    }

    if(has_shortcode($post->post_content, 'formresults') || has_shortcode($post->post_content, 'formselector')){
        if(!is_array($Modules[MODULE_SLUG]['formtable_pages'])){
            $Modules[MODULE_SLUG]['formtable_pages']    = [$postId];
        }elseif(!in_array($postId, $Modules[MODULE_SLUG]['formtable_pages'])){
            $Modules[MODULE_SLUG]['formtable_pages'][]  = $postId;
        }

        update_option('sim_modules', $Modules);
    }
}, 10, 2);

add_action( 'wp_enqueue_scripts', __NAMESPACE__.'\registerScripts');
add_action( 'admin_enqueue_scripts', __NAMESPACE__.'\registerScripts');

function registerScripts(){
    wp_register_style( 'sim_forms_style', plugins_url('css/forms.min.css', __DIR__), array(), MODULE_VERSION);
    wp_register_style( 'sim_formtable_style', plugins_url('css/formtable.min.css', __DIR__), array(), MODULE_VERSION);

    wp_register_script('sim_forms_script', plugins_url('js/forms.min.js', __DIR__), array('sweetalert', 'sim_formsubmit_script', 'sim_fileupload_script'), MODULE_VERSION, true);

    wp_register_script( 'sim_formbuilderjs', plugins_url('js/formbuilder.min.js', __DIR__), array('sim_forms_script','sortable'), MODULE_VERSION, true);
    
    wp_register_script('sim_forms_table_script', plugins_url('js/forms_table.js', __DIR__), array('sim_forms_script', 'sim_table_script'), MODULE_VERSION, true);

    if(is_numeric(get_the_ID())){
        $formBuilderPages   = SIM\getModuleOption(MODULE_SLUG, 'formbuilder_pages');
        if(is_array($formBuilderPages) && in_array(get_the_ID(), $formBuilderPages)){
            wp_enqueue_style('sim_forms_style');
        }

        /*         $formtablePages   = SIM\getModuleOption(MODULE_SLUG, 'formtable_pages');
        if(is_array($formtablePages) && in_array(get_the_ID(), $formtablePages)){
            wp_enqueue_style('sim_formtable_style');
        } */
    }
}