<?php
namespace SIM\CONTENTFILTER;
use SIM;

//function to redirect user to login page if they are not allowed to see it
add_action('loop_start',function(){
	ob_start();
});

add_action('wp_footer', function(){
	$user				= wp_get_current_user();
	$publicCategoryId	= get_cat_ID('Public');

	//If this page or post does not have the public category and the user is not logged in, redirect them to the login page
	if(
		http_response_code() != 404			and		//we try to visit an existing page
		!is_tax()							and		
		!is_user_logged_in()				and
		!has_category($publicCategoryId)	and 
		!is_search()						and
		!is_home()							or 
		(
			is_tax()							and 
			!is_user_logged_in()
		)
	){
		//prevent the output 
		$output	= ob_get_clean();

		if(!isset($_SESSION)) session_start();
		$_SESSION['showpage']   = 'true';

		// Set message in the session to be used in the login page
		$message = 'This content is restricted. <br>You will be able to see this page as soon as you login.';

		//show login modal
		if(function_exists('SIM\LOGIN\loginModal')){
			echo SIM\LOGIN\loginModal($message, true);
		}
		return;
	}
	
	//If not a valid e-mail then only allow the account page to reset the email
	if(strpos($user->user_email, ".empty") !== false and !has_category($publicCategoryId) and !is_search() and !is_home() and strpos($_SERVER['REQUEST_URI'],'account') === false ){
		wp_die("Your e-mail address is not valid please change it <a href='".SITEURL."/account/?section=generic'>here</a>.");
	}
	
	//block access to confidential pages
	if(is_page() and has_category('Confidential') and in_array('nigerianstaff',$user->roles)){
		wp_die("You do not have the permission to see this.");
	}

	//we are good, print everything to screen
	ob_end_flush();
});

//Make sure is_user_logged_in function is available by only running this when init
add_action('init', function (){
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
	if ($query->is_search) {
		if ( !is_user_logged_in() ) {
			$query->set('cat', get_cat_ID('Public'));
		}
	}
	return $query;
});