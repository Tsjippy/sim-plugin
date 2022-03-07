<?php
namespace SIM\FORMS;
use SIM;

add_action( 'save_post', function($post_ID, $post){
    if(has_shortcode($post->post_content, 'formbuilder')){
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
    wp_register_script('sim_forms_table_script', plugins_url('js/forms_table.min.js', __DIR__), array('sim_table_script','sim_other_script'), ModuleVersion,true);

    wp_register_style( 'sim_forms_style', plugins_url('css/forms.min.css', __DIR__), array(), ModuleVersion);

    // We cannot use the minified version as the dynamic js files depend on the function names
    wp_register_script('sim_forms_script',plugins_url('js/forms.js', __DIR__), array('sweetalert', 'sim_other_script'), ModuleVersion,true);

    wp_register_script( 'sim_formbuilderjs', plugins_url('js/formbuilder.min.js', __DIR__), array('sim_forms_script','sortable','sweetalert'), ModuleVersion,true);

    $formbuilder_pages   = SIM\get_module_option('forms', 'formbuilder_pages');
    if(in_array(get_the_ID(), $formbuilder_pages)){
        wp_enqueue_style('sim_forms_style');
    }
});