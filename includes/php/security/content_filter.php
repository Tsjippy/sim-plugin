<?php
namespace SIM;

//function to redirect user to login page if they are not allowed to see it
add_action('loop_start',function(){
	ob_start();
});

add_action('wp_footer',function(){
	global $PublicCategoryID;
	global $ConfCategoryID;
	global $MinistryCategoryID;
	global $post;

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

		if(!$_SESSION['login_added']){
			// Set message in the session to be used in the login page
			$message = 'This content is restricted. <br>You will be able to see this page as soon as you login.';

			//show login modal
			echo login_modal($message, true);
		}
		return;
	}
	
	//If not a valid e-mail then only allow the account page to reset the email
	if(strpos($user->user_email, ".empty") !== false and !has_category($PublicCategoryID) and !is_search() and !is_home() and strpos($_SERVER['REQUEST_URI'],'account') === false ){
		wp_die("Your e-mail address is not valid please change it <a href='".get_site_url()."/account/?section=generic'>here</a>.");
	}

	//If 2fa not enabled and we are not on the account page
	$methods	= get_user_meta($user->ID,'2fa_methods',true);
	if(!isset($_SESSION)) session_start();
	if (
		is_user_logged_in() and 							// we are logged in
		strpos($user->user_email,'.empty') === false and 	// we have a valid email
		strpos(current_url(),"/account/") === false and		// and we are not currently on the account page
		(
			!$methods or									// and we have no 2fa enabled or
			(
				isset($_SESSION['webauthn']) and
				$_SESSION['webauthn'] == 'failed' and 		// we have a failed webauthn
				count($methods) == 1 and					// and we only have one 2fa method
				in_array('webauthn',$methods)				// and that method is webauthn
			)
		)
	){
		print_array("Redirecting from ".current_url()." to ".TwoFA_page);
		wp_redirect(TwoFA_page);
		exit();
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
	global $MinistriesPageID;
	if ($query->is_search) {
		if ( !is_user_logged_in() ) {
			//Get all children of the ministries page
			$ministry_pages = has_children($MinistriesPageID );
			
			//Loop over all children to see if they have children 
			$ministry_pages_ids = [];
			foreach ($ministry_pages as $ministry_page){
				$children = has_children($ministry_page->ID);
				if(is_array($children)){
					//This page has children, add it to the array
					$ministry_pages_ids[] = $ministry_page->ID;
					//Loop over the children to see if they have children
					foreach ($children as $child){
						$grantchildren = has_children($child->ID);
						if(is_array($grantchildren)){
							//This page has children, add it to the array
							$ministry_pages_ids[] = $child->ID;
						}
					}
				}
			}
	
			//Adjust the search query
			$query->set('post_parent__in', $ministry_pages_ids);
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
		strpos($_SERVER['REQUEST_URI'],'wp-json/simnigeria/v1/markasread') !== false or
		strpos($_SERVER['REQUEST_URI'],'wp-json/simnigeria/v1/auth_finish') !== false or
		strpos($_SERVER['REQUEST_URI'],'wp-json/simnigeria/v1/auth_start') !== false 
	) {
		// Our custom authentication check should have no effect on logged-in requests
		return $result;
    }else{
		wp_die('You do not have permission for this.');
	}
});