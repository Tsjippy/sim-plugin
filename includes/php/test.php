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

	$mailchimp = new MAILCHIMP\Mailchimp(45);
	$result	= $mailchimp->getCampaigns();

	$templateId	= getModuleOption('mailchimp', 'templateid');

	$post = array(
		'post_type'		=> 'post',
		'post_status'   => "publish",
		'post_author'   => 1
	);

	foreach($result->campaigns as $campaign){
		if( $campaign->settings->template_id != $templateId	){
			$posts = get_posts(array(
				'numberposts'      => -1,
				'meta_query' 	=> array(
					'relation' 		=> 'AND',
					array(
						'key' 		=> 'mailchimp_templateid',
						'compare' 	=> 'EXISTS'
					),
					array(
						'key'	 	=> 'mailchimp_templateid',
						'value' 	=> $campaign->settings->template_id, 
						'compare' 	=> '='
					),
				)
			));

			if(empty($posts)){
				$post['post_title']		= $campaign->settings->title;
				$post['post_content']  	= "[mailchimp id='$campaign->id']";
				$postId 				= wp_insert_post( $post, true, false);

				update_post_meta($postId, 'mailchimp_templateid', $campaign->settings->template_id);
			}

			printArray($campaign, true);
		}
	}
	
	$path	= "C:/xampp/htdocs/simnigeria/wp-content/test.heic";
	$dest	= "C:/xampp/htdocs/simnigeria/wp-content/uploads/test.jpg";
	// 1. save as file
	try{
		$result = \Maestroerror\HeicToJpg::convert($path)->saveAs($dest);
	}catch (\Exception $e) {
		return explode(':', $e->getMessage())[0];
	}
	// 2. get content (binary) of converted JPG
	$jpg = \Maestroerror\HeicToJpg::convert($path)->get();

	$base64=base64_encode($jpg);
	//echo "<img src='data:image/jpeg;base64, $base64' alt='An elephant' />";

	$imageData = base64_encode(file_get_contents($dest));

	// Format the image SRC:  data:{mime};base64,{data};
	$src = 'data: '.mime_content_type($dest).';base64,'.$imageData;

	// Echo out a sample image
	//echo 'trallala<img src="' . $src . '">trallaa';
            $type       = pathinfo($dest, PATHINFO_EXTENSION);
            $contents   = file_get_contents($dest);
            if(!empty($contents)){
                $image = "data:image/$type;base64,".base64_encode($contents);
			}

	return "<img src='$image'/>";
	
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


add_shortcode("mailchimp", function($atss){
	$mailchimp = new MAILCHIMP\Mailchimp(45);

	$dom = new \DomDocument();
	$dom->loadHTML($mailchimp->client->campaigns->getContent($atss['id'])->html);
	$href=$dom->getElementById('templateFooter');
	$href->parentNode->removeChild($href);

	return $dom->saveHTML($dom->getElementsByTagName('head')->item(0)).$dom->saveHTML($dom->getElementsByTagName('body')->item(0));
});