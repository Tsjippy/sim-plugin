<?php
namespace SIM\FORMS;
use SIM;

add_action( 'save_post', function($postId, $post){
    global $Modules;

    $hasFormbuilderShortcode    = has_shortcode($post->post_content, 'formbuilder');

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

                    // Check if we should adjust the name
                    if($formName != $simForms->formName){
                        $newShortcode  = str_replace($formName, $simForms->formName, $shortcode[0]);

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

    if($hasFormbuilderShortcode || has_shortcode($post->post_content, 'formselector')){
        if(!is_array($Modules['forms']['formbuilder_pages'])){
            $Modules['forms']['formbuilder_pages']    = [$postId];
        }elseif(!in_array($postId, $Modules['forms']['formbuilder_pages'])){
            $Modules['forms']['formbuilder_pages'][]  = $postId;
        }

        update_option('sim_modules', $Modules);
    }

    if(has_shortcode($post->post_content, 'formresults') || has_shortcode($post->post_content, 'formselector')){
        if(!is_array($Modules['forms']['formtable_pages'])){
            $Modules['forms']['formtable_pages']    = [$postId];
        }elseif(!in_array($postId, $Modules['forms']['formtable_pages'])){
            $Modules['forms']['formtable_pages'][]  = $postId;
        }

        update_option('sim_modules', $Modules);
    }
}, 10, 2);

add_action( 'wp_enqueue_scripts', function(){
    wp_register_style( 'sim_forms_style', plugins_url('css/forms.min.css', __DIR__), array(), MODULE_VERSION);
    wp_register_style( 'sim_formtable_style', plugins_url('css/formtable.min.css', __DIR__), array(), MODULE_VERSION);

    wp_register_script('sim_forms_script', plugins_url('js/forms.min.js', __DIR__), array('sweetalert', 'sim_formsubmit_script'), MODULE_VERSION,true);

    wp_register_script( 'sim_formbuilderjs', plugins_url('js/formbuilder.min.js', __DIR__), array('sim_forms_script','sortable','sweetalert', 'sim_formsubmit_script'), MODULE_VERSION,true);
    
    wp_register_script('sim_forms_table_script', plugins_url('js/forms_table.js', __DIR__), array('sim_forms_script', 'sim_table_script', 'sim_formsubmit_script'), MODULE_VERSION,true);

    $formBuilderPages   = SIM\getModuleOption(MODULE_SLUG, 'formbuilder_pages');
    if(in_array(get_the_ID(), $formBuilderPages)){
        wp_enqueue_style('sim_forms_style');
    }

    $formtablePages   = SIM\getModuleOption(MODULE_SLUG, 'formtable_pages');
    if(in_array(get_the_ID(), $formtablePages)){
        wp_enqueue_style('sim_formtable_style');
    }
    
});