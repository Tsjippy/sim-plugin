<?php
namespace SIM\CONTENTFILTER;
use SIM;

//function to redirect user to login page if they are not allowed to see it
add_action('loop_start', function($query){
	// Only run when this is the main query
	if($query->is_main_query()){
		ob_start();
	}
});

add_action('wp_footer', function(){
	global	$post;
	$user				= wp_get_current_user();
	$taxonomy			= get_post_taxonomies()[0];

	$public				= false;
	foreach((array)get_the_terms($post, $taxonomy) as $term){
		if($term->slug	== 'public'){
			$public	= true;
			break;
		}
	}

	//If this page or post does not have the public category and the user is not logged in, redirect them to the login page
	if(
		http_response_code() != 404			&&		//we try to visit an existing page
		!is_user_logged_in()				&&
		!$public							&& 
		!is_search()						&&
		!is_home()							
	){
		//prevent the output 
		ob_get_clean();

		if(!isset($_SESSION)){
			session_start();
		}
		$_SESSION['showpage']   = 'true';

		// Set message in the session to be used in the login page
		$message = 'This content is restricted. <br>You will be able to see this page as soon as you login.';

		//show login modal
		if(function_exists('SIM\LOGIN\loginModal')){
			SIM\LOGIN\loginModal($message, true);
		}
		return;
	}
	
	// If not a valid e-mail then only allow the account page to reset the email
	if(strpos($user->user_email, ".empty") !== false && !$public && !is_search() && !is_home() && strpos($_SERVER['REQUEST_URI'],'account') === false ){
		wp_die("Your e-mail address is not valid please change it <a href='".SITEURL."/account/?section=generic'>here</a>.");
	}
	
	//block access to confidential pages
	if(is_page() && has_category('Confidential') && in_array('nigerianstaff',$user->roles)){
		wp_die("You do not have the permission to see this.");
	}

	//we are good, print everything to screen
	ob_end_flush();
});

//Make sure is_user_logged_in function is available by only running this when init
add_action('init', function (){
	// do not run during rest request
    if(SIM\isRestApiRequest()){
        return;
    }
	
	//Function to only show newsittems on the news page the user is allowed to see
	add_action( 'pre_get_posts', function ( $query ) {
		if ( $query->is_home() && $query->is_main_query() ) {
			if ( !is_user_logged_in() ) {
				//Only show items with the public category
				$query->set( 'cat', get_cat_ID('Public') );
				//Only show the items without a password
				$query->set( 'has_password', false );
			}else{
				$user = wp_get_current_user();
				if(in_array('nigerianstaff',$user->roles)){
					//Hide confidential items
					$query->set( 'category__not_in', [get_cat_ID('Confidential')] );
				}
			}
		}
	});
});

//Only show public search results for non-loggedin users
add_filter('pre_get_posts', function ($query) {
	if ($query->is_search &&  !is_user_logged_in() ) {
		$query->set('cat', get_cat_ID('Public'));
	}
	return $query;
});