<?php
namespace SIM\MANDATORY;
use SIM;

// Make mark as read rest api publicy available
add_filter('sim_allowed_rest_api_urls', function($urls){
	$urls[]	= RESTAPIPREFIX.'/mandatory_content';

	return $urls;
});

add_action( 'rest_api_init', function () {
	//Route to update mark as read from mailchimp
	register_rest_route(
		RESTAPIPREFIX.'/mandatory_content',
		'/mark_as_read_public',
		array(
			'methods' => 'GET',
			'callback' => __NAMESPACE__.'\markAsReadFromEmail',
			'permission_callback' => '__return_true',
			'args'					=> array(
				'postid'		=> array(
					'required'	=> true,
					'validate_callback' => function($postId){
						return is_numeric($postId);
					}
				),
				'email'		=> array(
					'required'	=> true
				)
			)
		)
	);

	// Mark as read from website
	register_rest_route(
		RESTAPIPREFIX.'/mandatory_content',
		'/mark_as_read',
		array(
			'methods' => 'POST',
			'callback' => __NAMESPACE__.'\markAsRead',
			'permission_callback' => '__return_true',
			'args'					=> array(
				'postid'		=> array(
					'required'	=> true,
					'validate_callback' => function($postId){
						return is_numeric($postId);
					}
				),
				'userid'		=> array(
					'required'	=> true,
					'validate_callback' => function($userId){
						return is_numeric($userId);
					}
				)
			)
		)
	);

	// Mark all as read
	register_rest_route(
		RESTAPIPREFIX.'/mandatory_content',
		'/mark_all_as_read', array(
			'methods' 	=> 'POST',
			'callback' 	=> function($wpRestRequest){
				$userId = $wpRestRequest->get_param('userid');

				return markAllAsRead($userId);
			},
			'permission_callback' => '__return_true',
			'args'					=> array(
				'userid'		=> array(
					'required'	=> true,
					'validate_callback' => function($userId){
						return is_numeric($userId);
					}
				)
			)
		)
	);
});

add_filter('sim_before_mailchimp_send', function($mailContent, $post){
	$audience   = get_post_meta($post->ID, 'audience', true);
    if(!is_array($audience) && !empty($audience)){
        $audience  = json_decode($audience, true);
    }

    ///add button if mandatory message
    if(!empty($audience['everyone'])){
        $url			= SITEURL."/wp-json/".RESTAPIPREFIX."/mandatory_content/mark_as_read_public?email=*|EMAIL|*&postid={$post->ID}";
        $style			= "color: white; background-color: #bd2919; border-radius: 3px; text-align: center; margin-right: 10px; padding: 5px 10px;";
        $mailContent	.= "<br><a href='$url' style='$style'>I have read this</a>";
    }

    return $mailContent;
}, 10, 2);

/**
 * Rest Request to mark a page as read over e-mail
 * Also add a button to mark the post as read
*/
function markAsReadFromEmail(\WP_REST_Request $request){
	$email		= $request['email'];
	$postId		= $request['postid'];

	//only continue if valid email and numeric postid
	if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
		//set the admin as the user so we can query the db
		wp_set_current_user(1);

		$userId		= get_user_by( 'email', $email )->ID;

		//no user, check secundairy email
		if(!is_numeric($userId)){
			$userId = get_users(['meta_key' => 'email','meta_value' => $email])[0]->ID;
		}

		$title	= get_the_title($postId);

		if(!is_numeric($userId)){
			$message	= "We could not find an user with the e-mail '$email'";
			$type		= 'Error';
		}elseif(empty($title)){
			$message	= "We could not find the page";
			$type		= 'Error';
		}else{
			//get current alread read pages
			$readPages		= (array)get_user_meta( $userId, 'read_pages', true );

			//add current page
			$readPages[]	= $postId;

			//update
			update_user_meta( $userId, 'read_pages', $readPages);

			$message	= "Succesfully marked '".get_the_title($postId)."' as read.";
			$type		= 'Success';
		}

		wp_redirect( home_url("?message=$message&type=$type") );
		exit();
	}
}

/**
 * Rest Request to mark a page as read
*/
function markAsRead(){
	$userId = $_POST['userid'];
	$postId = $_POST['postid'];

	//get current alread read pages
	$readPages		= (array)get_user_meta( $userId, 'read_pages', true );

	//add current page
	$readPages[]	= $postId;
	//update
	update_user_meta( $userId, 'read_pages', $readPages);

	return "Succesfully marked this page as read";
}

/**
 * Rest Request to mark all pages as read
 *
 * @param	int				$userId		the user id to mark as read for
 * @param	array|string	$audience	array of audience targets to mark as read for or 'all' for all. Default 'everyone'
*/
function markAllAsRead($userId, $audience=['everyone']){

	//Get all the pages with an audience meta key
	$pages = get_posts(
		array(
			'post_type' 	=> 'any',
			'post_status' 	=> 'publish',
			'meta_key' 		=> "audience",
			'numberposts'	=> -1,				// all posts
		)
	);

	//get current alread read pages
	$readPages		= (array)get_user_meta( $userId, 'read_pages', true );

	foreach($pages as $page){
		$targetAudience	= get_post_meta($page->ID, 'audience', true);

		if(empty($targetAudience)){
			delete_post_meta($page->ID, 'audience');
			continue;
		}elseif(!is_array($targetAudience)){
			$targetAudience	= json_decode($targetAudience);
		}

		if($audience == 'all' || array_intersect($audience, array_keys((array)$targetAudience))){
			//add current page
			$readPages[]	= $page->ID;
		}
	}

	//update in db
	update_user_meta( $userId, 'read_pages', $readPages);

	return "Succesfully marked all pages as read for ".get_userdata($userId)->display_name;
}