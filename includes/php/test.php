<?php
namespace SIM;

use Exception;
use mikehaertl\shellcommand\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;
use SIM\FORMS\SimForms;

//Shortcode for testing
add_shortcode("test", function ($atts){
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

    global $wpdb;
    global $Modules;


    /* $posts = get_posts(
		array(
			'post_type'		=> 'any',
			//'author'		=> 137,
			'numberposts'	=> -1,
		)
	);

    foreach($posts as $post){
       
    }  */

    foreach(getUserAccounts(true) as $user){
        $family = get_user_meta($user->ID, 'family', true);

        if(isset($family['children']) && is_array($family['children'])){
            foreach($family['children'] as $child){
                $childFamily = (array)get_user_meta( $child, 'family', true );

                $childFamily['siblings']    = $family["children"];
    
                foreach($family['children'] as $index=>$c){
                    if($c == $child){
                        unset($childFamily['siblings'][$index]);
                    }
                }
    
                //Save in DB
                update_user_meta( $child, 'family', $childFamily);
            }
        }
    }
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );