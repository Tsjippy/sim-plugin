<?php
namespace SIM;

use mikehaertl\shellcommand\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;

//Shortcode for testing
add_shortcode("test", function ($atts){
    global $wpdb;

    global $Modules;
	
	$result=PDFTOEXCEL\readPdf("C:/Users/ewald/Downloads/SIM Nigeria_W050526 - Harmsen, Ewald and  Lianne_4_27 February 2024,  144900.pdf", "C:/Users/ewald/Downloads/test.csv");

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
