<?php
namespace SIM\CONTENTFILTER;
use SIM;

//function to redirect user to login page if they are not allowed to see it
add_action('loop_start',function(){
	ob_start();
});

add_action('wp_footer', function(){
	global $PublicCategoryID;
	global $ConfCategoryID;
	global $MinistryCategoryID;

	$user		= wp_get_current_user();

	//If this page or post does not have the public category and the user is not logged in, redirect them to the login page
	if(
		http_response_code() != 404			and		//we try to visit an existing page
		!is_tax()							and		
		!is_user_logged_in()				and
		!has_category($PublicCategoryID)	and 
		!is_search()						and
		!is_home()							or 
		(
			is_tax()							and 
			!is_user_logged_in()				and
			get_queried_object()->term_id != $MinistryCategoryID and
			get_queried_object()->parent  != $MinistryCategoryID
		)
	){
		//prevent the output 
		$output	= ob_get_clean();

		if(!isset($_SESSION)) session_start();
		$_SESSION['showpage']   = 'true';

		// Set message in the session to be used in the login page
		$message = 'This content is restricted. <br>You will be able to see this page as soon as you login.';

		//show login modal
		echo SIM\LOGIN\login_modal($message, true);
		return;
	}
	
	//If not a valid e-mail then only allow the account page to reset the email
	if(strpos($user->user_email, ".empty") !== false and !has_category($PublicCategoryID) and !is_search() and !is_home() and strpos($_SERVER['REQUEST_URI'],'account') === false ){
		wp_die("Your e-mail address is not valid please change it <a href='".get_site_url()."/account/?section=generic'>here</a>.");
	}
	
	//block access to confidential pages
	if(is_page() and has_category($ConfCategoryID) and in_array('nigerianstaff',$user->roles)){
		wp_die("You do not have the permission to see this.");
	}

	//we are good, print everything to screen
	ob_end_flush();
});

//Make sure is_user_logged_in function is available by only running this when init
add_action('init', function (){
	//Function to only show newsittems on the news page the user is allowed to see
	add_action( 'pre_get_posts', function ( $query ) {
		global $PublicCategoryID;
		global $ConfCategoryID;
		
		if ( $query->is_home() && $query->is_main_query() ) {
			if ( !is_user_logged_in() ) {
				//Only show items with the public category
				$query->set( 'cat', $PublicCategoryID );
				//Only show the items without a password
				$query->set( 'has_password', false );
			}else{
				$user = wp_get_current_user();
				if(in_array('nigerianstaff',$user->roles)){
					//Hide confidential items
					$query->set( 'category__not_in', [$ConfCategoryID] );
				}
			}
		}
	});
});

//Only show public search results for non-loggedin users
add_filter('pre_get_posts', function ($query) {
	global $PublicCategoryID;
	if ($query->is_search) {
		if ( !is_user_logged_in() ) {
			$query->set('cat', $PublicCategoryID);
		}
	}
	return $query;
});

//Secure the rest api
add_filter( 'rest_authentication_errors', function( $result ) {	
    // If a previous authentication check was applied, pass that result along without modification.
    if ( true === $result || is_wp_error( $result ) ) {
        return $result;
    }

    // No authentication has been performed yet return an error if user is not logged in, exception for wp-mail-smtp
    if ( 
		is_user_logged_in() or 
		strpos($_SERVER['REQUEST_URI'],'wp-json/wp-mail-smtp/v1') !== false	or
		strpos($_SERVER['REQUEST_URI'],'wp-json/sim/v1/markasread') !== false or
		strpos($_SERVER['REQUEST_URI'],'wp-json/sim/v1/auth_finish') !== false or
		strpos($_SERVER['REQUEST_URI'],'wp-json/sim/v1/auth_start') !== false 
	) {
		// Our custom authentication check should have no effect on logged-in requests
		return $result;
    }else{
		wp_die('You do not have permission for this.');
	}
});
