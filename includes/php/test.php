<?php
namespace SIM;

use Exception;
use mikehaertl\shellcommand\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;

//Shortcode for testing
add_shortcode("test", function ($atts){
    global $wpdb;
    global $Modules;

	$signal	= new SIGNAL\SignalJsonRpc();

	//printArray($signal->isRegistered("+2349045252526"), true);

	/* printArray($signal->send("+2349045252526", "hi"), true);

	printArray($signal->listGroups(), true);


	$groupPaths		= getModuleOption('signal', 'invgroups');

    $link			= '';
    if(is_array($groupPaths)){
        foreach($groupPaths as $path){
            $result	= 	$signal->getGroupInvitationLink($path);
			printArray($result, true);

            if(empty($signal->error)){
                $link	.= $result;
            }
        }
    } */
	

    /* $posts = get_posts(
		array(
			'post_type'		=> 'any',
			//'author'		=> 137,
			'numberposts'	=> -1,
		)
	);

    foreach($posts as $post){
       
    }  */
});

// turn off incorrect error on localhost
add_filter( 'wp_mail_smtp_core_wp_mail_function_incorrect_location_notice', '__return_false' );