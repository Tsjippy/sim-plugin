<?php
namespace SIM\FORMS;
use SIM;

add_action( 'save_post', function($post_ID, $post){
    $hasFormbuilderShortcode    = has_shortcode($post->post_content, 'formbuilder');

    // Add the form if it does not exist yet
    if($hasFormbuilderShortcode){
        preg_match_all( 
            '/' . get_shortcode_regex() . '/', 
            $post->post_content, 
            $shortcodes, 
            PREG_SET_ORDER
        );

        // loop over all the found the shortcodes
        foreach($shortcodes as $shortcode){
            // Only continue if the current shortcode is a formbuilder shortcode
            if($shortcode[2] == 'formbuilder'){
                // Get the formbuilder name from the shortcode
                $formname       = shortcode_parse_atts($shortcode[3])['datatype'];

                $formbuilder    = new Formbuilder();
                $formbuilder->get_forms();

                // check if a form with this name already exists
                $found  = false;
                foreach($formbuilder->forms as $form){
                    if($form->name == $formname){
                        $found  = true;
                        break;
                    }
                }

                // Only add a new form if it does not exist yet
                if(!$found) $formbuilder->insert_form($formname);
            }
        }
    }

    if($hasFormbuilderShortcode or has_shortcode($post->post_content, 'formselector')){
        global $Modules;

        if(!is_array($Modules['forms']['formbuilder_pages'])){
            $Modules['forms']['formbuilder_pages']    = [$post_ID];
        }elseif(!in_array($post_ID, $Modules['forms']['formbuilder_pages'])){
            $Modules['forms']['formbuilder_pages'][]  = $post_ID;
        }

        update_option('sim_modules', $Modules);
    }
}, 10, 2);

add_action( 'wp_enqueue_scripts', function(){
    wp_register_style( 'sim_forms_style', plugins_url('css/forms.min.css', __DIR__), array(), ModuleVersion);

    wp_register_script('sim_forms_script',plugins_url('js/forms.min.js', __DIR__), array('sweetalert', 'sim_formsubmit_script'), ModuleVersion,true);

    wp_register_script( 'sim_formbuilderjs', plugins_url('js/formbuilder.min.js', __DIR__), array('sim_forms_script','sortable','sweetalert', 'sim_formsubmit_script'), ModuleVersion,true);
    
    wp_register_script('sim_forms_table_script', plugins_url('js/forms_table.min.js', __DIR__), array('sim_forms_script', 'sim_table_script', 'sim_formsubmit_script'), ModuleVersion,true);

    $formbuilder_pages   = SIM\get_module_option('forms', 'formbuilder_pages');
    if(in_array(get_the_ID(), $formbuilder_pages)){
        wp_enqueue_style('sim_forms_style');
    }
});