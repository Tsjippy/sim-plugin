<?php
namespace SIM\FORMS;
use SIM;

add_action( 'save_post', function($post_ID, $post){
    $hasFormbuilderShortcode    = has_shortcode($post->post_content, 'formbuilder');

    // Add the form 
    if($hasFormbuilderShortcode){

        preg_match_all( 
            '/' . get_shortcode_regex() . '/', 
            $post->post_content, 
            $matches, 
            PREG_SET_ORDER
        );

        $formname       = $matches[0];

        $formbuilder    = new Formbuilder();
        $formbuilder->get_forms();

        $formbuilder->forms;

        //if(strpos())
        $formbuilder->insert_form();
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