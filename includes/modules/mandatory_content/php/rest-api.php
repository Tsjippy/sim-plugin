<?php
namespace SIM\MANDATORY;
use SIM;

add_filter('sim_before_mailchimp_send', function($mail_content, $post){
    ///add button if mandatory message
    if(!empty($_POST['pagetype']['everyone'])){
        $url			= SITEURL."/wp-json/sim/v1/markasread?email=*|EMAIL|*&postid={$post->ID}";
        $style			= "color: white; background-color: #bd2919; border-radius: 3px; text-align: center; margin-right: 10px; padding: 5px 10px;";
        $mail_content	.= "<br><a href='$url' style='$style'>I have read this</a>";
    }

    return $mail_content;
}, 10, 2);

//for use in external communication like e-mail
function markasread(\WP_REST_Request $request){
	$email		= $request['email'];
	$post_id	= $request['postid'];

	//only continue if valid email and numeric postid
	if (filter_var($email, FILTER_VALIDATE_EMAIL) and is_numeric($post_id)) {
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

add_action( 'rest_api_init', function () {
	//Route to update mark as read from mailchimp
	register_rest_route( 'sim/v1', '/markasread', array(
		'methods' => 'GET',
		'callback' => __NAMESPACE__.'\markasread',
		'permission_callback' => '__return_true',
		)
	);
});

// Make mark as read rest api publicy available
add_filter('sim_allowed_rest_api_urls', function($urls){
	$urls[]	= 'sim/v1/markasread';

	return $urls;
});