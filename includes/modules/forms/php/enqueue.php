<?php
namespace SIM\FORMS;
use SIM;

add_action( 'save_post', function($postId, $post){
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
                $formName       = shortcode_parse_atts($shortcode[3])['formname'];

                $formbuilder    = new Formbuilder();
                $formbuilder->getForms();

                // check if a form with this name already exists
                $found  = false;
                foreach($formbuilder->forms as $form){
                    if($form->name == $formName){
                        $found  = true;
                        break;
                    }
                }

                // Only add a new form if it does not exist yet
                if(!$found) $formbuilder->insertForm($formName);
            }
        }
    }

    if($hasFormbuilderShortcode || has_shortcode($post->post_content, 'formselector')){
        global $Modules;

        if(!is_array($Modules['forms']['formbuilder_pages'])){
            $Modules['forms']['formbuilder_pages']    = [$postId];
        }elseif(!in_array($postId, $Modules['forms']['formbuilder_pages'])){
            $Modules['forms']['formbuilder_pages'][]  = $postId;
        }

        update_option('sim_modules', $Modules);
    }
}, 10, 2);

add_action( 'wp_enqueue_scripts', function(){
    wp_register_style( 'sim_forms_style', plugins_url('css/forms.min.css', __DIR__), array(), MODULE_VERSION);

    wp_register_script('sim_forms_script', plugins_url('js/forms.min.js', __DIR__), array('sweetalert', 'sim_formsubmit_script'), MODULE_VERSION,true);

    wp_register_script( 'sim_formbuilderjs', plugins_url('js/formbuilder.min.js', __DIR__), array('sim_forms_script','sortable','sweetalert', 'sim_formsubmit_script'), MODULE_VERSION,true);
    
    wp_register_script('sim_forms_table_script', plugins_url('js/forms_table.js', __DIR__), array('sim_forms_script', 'sim_table_script', 'sim_formsubmit_script'), MODULE_VERSION,true);

    $formBuilderPages   = SIM\getModuleOption('forms', 'formbuilder_pages');
    if(in_array(get_the_ID(), $formBuilderPages)){
        wp_enqueue_style('sim_forms_style');
    }
});