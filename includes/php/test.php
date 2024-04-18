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

	//include "src/HeicToJpg.php";

	$path	= wp_upload_dir()['basedir']."/test2.heic";
	$dest	= wp_upload_dir()['basedir']."/test2.jpg";

	if(\Maestroerror\HeicToJpg::isHeic($path)) {
		// 1. save as file
		try{
			$result = \Maestroerror\HeicToJpg::convert($path)->saveAs($dest);
		}catch (\Exception $e) {
			printArray($e, true);
			return explode(':', $e->getMessage())[0];
		}
		
		// Works
		$jpg = file_get_contents($dest);
		$base64=base64_encode($jpg);
		echo "<img src='data:image/jpeg;base64, $base64'/>";
		
		// Works
		$jpg = \Maestroerror\HeicToJpg::convert($path)->get();
		$base64=base64_encode($jpg);
		echo "<img src='data:image/jpeg;base64, $base64'/>";
	}
	
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