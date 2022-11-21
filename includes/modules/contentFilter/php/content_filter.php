<?php
namespace SIM\CONTENTFILTER;
use SIM;

use function SIM\ADMIN\getModuleName;
use function SIM\getModuleOption;

//function to redirect user to login page if they are not allowed to see it
add_action('loop_start', function($query){
	if($query->is_main_query() && isProtected()){
		ob_start();
	}
});

// Add meta tag so this page is not indexed by search machines
add_action ( 'wp_head', function(){
	if(isProtected()){
		echo '<meta name="robots" content="noindex, nofollow">';
	}
});

function isProtected(){
	global	$post;
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
		return true;
	}

	return false;
}

add_action('wp_footer', function(){
	$user				= wp_get_current_user();
	global	$post;
	$taxonomy			= get_post_taxonomies()[0];

	$public				= false;
	foreach((array)get_the_terms($post, $taxonomy) as $term){
		if($term->slug	== 'public'){
			$public	= true;
			break;
		}
	}

	//If this page or post does not have the public category and the user is not logged in, redirect them to the login page
	if(	isProtected() ){
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
	if(strpos($user->user_email, ".empty") !== false && !$public && !is_search() && !is_home() && strpos($_SERVER['REQUEST_URI'], 'account') === false ){
		ob_get_clean();
		echo "<div class='error'>Your e-mail address is not valid please change it <a href='".SITEURL."/account/?section=generic'>here</a>.</div>";
		return;
	}
	
	//block access to confidential pages
	$confidentialGroups	= getModuleOption(MODULE_SLUG, 'confidential-roles', true);
	if(is_page() && has_category('Confidential') && array_intersect($confidentialGroups, $user->roles)){
		//prevent the output
		ob_get_clean();
		echo "<div class='error'>You do not have the permission to see this.</div>";
	}
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
				$confidentialGroups	= getModuleOption(MODULE_SLUG, 'confidential-roles');
				if(array_intersect($confidentialGroups, $user->roles)){
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