<?php
namespace SIM\MAIL_POSTING;
use SIM;

//Update the post
//http://postieplugin.com/postie_post_before/
add_filter('postie_post_before', __NAMESPACE__.'\processEmail', 10, 2);
function processEmail($post, $headers) {
	if($post != null){

		unset($post['post_category']);

		$user = get_userdata($post['post_author']);

		if($user){
		
			//Get the subject and process shortcodes in it
			$subject = do_shortcode($post['post_title']);
			$subject = str_replace("[SIM-Nigeria]", "", $subject);

			//If mail is forwarded, edit the subject
			$post['post_title'] = trim(str_replace("Fwd:", "", $subject));
		
			//Set the category
			$categoryMapper	= SIM\getModuleOption(MODULE_SLUG, 'category_mapper');
			$email			= trim(strtolower($headers['from']['mailbox'].'@'.$headers['from']['host']));

			foreach($categoryMapper as $mapper){
				if (trim(strtolower($mapper['email'])) == $email){
					$postType			= $mapper['category'][0];
					$post['post_type']	= $postType;
					$taxonomy			= $mapper['category'][$postType][0];
					$post['taxonomy']	= $taxonomy;

					//Set the category to the chosen category
					if(!isset($post['tax_input'][$taxonomy])){
						$post['tax_input'][$taxonomy]	= [];
					}
					$post['tax_input'][$taxonomy] = array_merge($post['tax_input'][$taxonomy], $mapper['category'][$postType][$taxonomy]);
				}
			}

			//Change the post status to pending for all users without the editor role
			if ( !in_array('editor', $user->roles)){
				$post['post_status'] = 'pending';
			}
		}
	}

	return $post;
}

add_filter('postie_post_after', function($post){
	SIM\printArray($post);

	//Only send message if post is published
	if($post['post_status'] == 'publish' && function_exists('SIM\SIGNAL\sendPostNotification')){
		update_post_meta($post['ID'], 'signal_message_type', 'summary');

		SIM\SIGNAL\sendPostNotification($post['ID']);
	}elseif(function_exists('SIM\FRONTENDPOSTING\sendPendingPostWarning')){
		SIM\FRONTENDPOSTING\sendPendingPostWarning(get_post($post['ID']), false);
	}
});

function test(){
	$post	= [
		'post_author'	=> 122,
		'post_status'	=> 'post_status',
		'post_type'		=> 'post',
		'taxonomy'		=> 'category',
		'tax_input'		=> [
			'category'		=> [
				3
			]
		],
		'post_title'	=> 'OCTOBER  2023 EXCHANGE RATE',
		'post_category'	=> [4]
	];

	$headers	= [];
	$headers['from']	= [
		'mailbox'	=> 'jos.treasurer',
		'host'		=> 'sim.org'
	];

	processEmail($post, $headers);

	wp_insert_post($post);
}