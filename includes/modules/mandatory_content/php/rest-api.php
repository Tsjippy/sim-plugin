<?php
namespace SIM\MANDATORY;
use SIM;

// Make mark as read rest api publicy available
add_filter('sim_allowed_rest_api_urls', function($urls){
	$urls[]	= 'sim/v1/markasread';

	return $urls;
});

add_action( 'rest_api_init', function () {
	//Route to update mark as read from mailchimp
	register_rest_route( 'sim/v1/mandatory_content', '/markasread', array(
		'methods' => 'GET',
		'callback' => __NAMESPACE__.'\markAsReadFromEmail',
		'permission_callback' => '__return_true',
		'args'					=> array(
			'postid'		=> array(
				'required'	=> true,
				'validate_callback' => 'is_numeric'
			),
			'email'		=> array(
				'required'	=> true
			)
		)
		)
	);

	// Mark as read from website
	register_rest_route( 'sim/v1/mandatory_content', '/markasread', array(
		'methods' => 'POST',
		'callback' => __NAMESPACE__.'\markAsRead',
		'permission_callback' => '__return_true',
		'args'					=> array(
			'postid'		=> array(
				'required'	=> true,
				'validate_callback' => 'is_numeric'
			),
			'userid'		=> array(
				'required'	=> true,
				'validate_callback' => 'is_numeric'
			)
		)
		)
	);
});

add_filter('sim_before_mailchimp_send', function($mail_content, $post){
    ///add button if mandatory message
    if(!empty($_POST['pagetype']['everyone'])){
        $url			= SITEURL."/wp-json/sim/v1/mandatory_content/markasread?email=*|EMAIL|*&postid={$post->ID}";
        $style			= "color: white; background-color: #bd2919; border-radius: 3px; text-align: center; margin-right: 10px; padding: 5px 10px;";
        $mail_content	.= "<br><a href='$url' style='$style'>I have read this</a>";
    }

    return $mail_content;
}, 10, 2);

//for use in external communication like e-mail
function markAsReadFromEmail(\WP_REST_Request $request){
	$email		= $request['email'];
	$post_id	= $request['postid'];

	//only continue if valid email and numeric postid
	if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
		//set the admin as the user so we can query the db
		wp_set_current_user(1);

		$user_id		= get_user_by( 'email', $email )->ID;

		//no user, check secundairy email
		if(!is_numeric($user_id)){
			$user_id = get_users(['meta_key' => 'email','meta_value' => $email])[0]->ID;
		}

		$title	= get_the_title($post_id);

		if(!is_numeric($user_id)){
			$message	= "We could not find an user with the e-mail '$email'";
			$type		= 'Error';
		}elseif(empty($title)){
			$message	= "We could not find the page";
			$type		= 'Error';
		}else{
			//get current alread read pages
			$read_pages		= (array)get_user_meta( $user_id, 'read_pages', true );
				
			//add current page
			$read_pages[]	= $post_id;

			//update
			update_user_meta( $user_id, 'read_pages', $read_pages);

			$message	= "Succesfully marked '".get_the_title($post_id)."' as read.";
			$type		= 'Success';
		}

		wp_redirect( home_url("?message=$message&type=$type") );
		exit();
	}
}

//Process button click
function markAsRead(){
	$user_id = $_POST['userid'];
	$post_id = $_POST['postid'];
	
	//get current alread read pages
	$read_pages		= (array)get_user_meta( $user_id, 'read_pages', true );
	
	//add current page
	$read_pages[]	= $post_id;
	//update
	update_user_meta( $user_id, 'read_pages', $read_pages);
	
	return "Succesfully marked this page as read";
}