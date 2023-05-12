<?php
namespace SIM\USERMANAGEMENT;
use SIM;
use WP_User;

add_action( 'rest_api_init', function () {
	// add element to form
	register_rest_route(
		RESTAPIPREFIX.'/user_management',
		'/add_ministry',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\addMinistry',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'location_name'		=> array(
					'required'	=> true
				)
			)
		)
	);

    // disable or eneable useraccount
	register_rest_route(
		RESTAPIPREFIX.'/user_management',
		'/disable_useraccount',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\disableUserAccount',
			'permission_callback' 	=> function(){
                return in_array('usermanagement', wp_get_current_user()->roles);
            },
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

    // update user roles
	register_rest_route(
		RESTAPIPREFIX.'/user_management',
		'/update_roles',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	function($wp_rest_request){
				return updateRoles($_REQUEST['userid'], $_REQUEST['roles']);
			},
			'permission_callback' 	=> function(){
                return (bool)array_intersect(['usermanagement', 'administrator'], wp_get_current_user()->roles);
            },
			'args'					=> array(
				'userid'		=> array(
					'required'	=> true,
                    'validate_callback' => function($userId){
						return is_numeric($userId);
					}
                ),
                'roles'		=> array(
					'required'	=> true
				)
			)
		)
	);

    // add user account
	register_rest_route(
		RESTAPIPREFIX.'/user_management',
		'/add_useraccount',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\createUserAccount',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'first_name' => array(
					'required'	=> true
                ),
                'last_name'	 => array(
					'required'	=> true
				),
				'roles'		 => array(
					'required'	=> true
				)
			)
		)
	);

    // extend user account validity
	register_rest_route(
		RESTAPIPREFIX.'/user_management',
		'/extend_validity',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\extendValidity',
			'permission_callback' 	=> function(){
                return in_array('usermanagement', wp_get_current_user()->roles);
            },
			'args'					=> array(
				'userid'		=> array(
					'required'	=> true,
                    'validate_callback' => function($userId){
						return is_numeric($userId);
					}
                ),
                'new_expiry_date'		=> array(
					'required'	=> true
				)
			)
		)
	);

	// get userpage tab contents
	register_rest_route(
		RESTAPIPREFIX.'/user_management',
		'/get_userpage_tab',
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\getUserPageTab',
			'permission_callback' 	=> '__return_true',
			'args'					=> array(
				'userid'		=> array(
					'required'	=> true,
                    'validate_callback' => function($userId){
						return is_numeric($userId);
					}
                ),
                'tabname'		=> array(
					'required'	=> true
				)
			)
		)
	);
});

function getUserPageTab($wpRestRequest){
	$params	= $wpRestRequest->get_params();

	$userId	= $params['userid'];

	$genericInfoRoles 	= array_merge(['usermanagement'], ["medicalinfo"], ['administrator']);
	$userSelectRoles	= apply_filters('sim_user_page_dropdown', $genericInfoRoles);
	$user 				= wp_get_current_user();
	$userRoles 			= $user->roles;

	if($userId	!= get_current_user_id() && array_intersect($userSelectRoles, $userRoles )){
		$admin	= true;
	}else{
		$admin	= false;
	}

	switch($params['tabname']){
		case 'generic':
			$html	= getGenericsTab($userId);
			break;
		case 'dashboard':
			$html	= showDashboard($userId, $admin);
			break;
		case 'family':
			$html	= do_shortcode("[formbuilder formname=user_family userid='$userId']");
			break;
		case 'location':
			$html	= do_shortcode("[formbuilder formname=user_location userid='$userId']");
			break;
		case 'location':
			$html	= do_shortcode("[formbuilder formname=user_location userid='$userId']");
			break;
		case 'profile_picture':
			$html	= do_shortcode("[formbuilder formname=profile_picture userid='$userId']");
			break;
		case 'security':
			$html	= do_shortcode("[formbuilder formname=security_questions userid='$userId']");
			break;
		case 'medical':
			$html	= getMedicalTab($userId);
			break;
		default:
			// check if tabname has a number
			$childId	= explode('_', $params['tabname']);
			if($childId[0] == 'child' && isset($childId[1]) && is_numeric($childId[1])){
				$html	= showChildrenFields($childId[1]);
			}else{
				$html	= showDashboard($userId, $admin);
			}
	}

	do_action( 'wp_enqueue_scripts');
	ob_start();
	wp_print_scripts();
	$js	= ob_get_clean();

	do_action('wp_enqueue_style');
	ob_start();
	wp_print_styles();
	$css	= ob_get_clean();

	return [
		'html'	=> $html,
		'js'	=> $js,
		'css'	=> $css
	];
}

function disableUserAccount(){
	if(empty(get_user_meta( $_POST['userid'], 'disabled', true ))){
		update_user_meta( $_POST['userid'], 'disabled', true );
		return 'Succesfully disabled the user account';
	}else{
		delete_user_meta( $_POST['userid'], 'disabled');
		return 'Succesfully enabled the user account';
	}
}

/**
 * add new ministry location via rest api
 */
function addMinistry(){
    //Get the post data
    $name = sanitize_text_field($_POST["location_name"]);

	$status	= 'pending';
	if(wp_get_current_user()->has_cap( 'publish_post' )){
		$status	= 'publish';
	}

    //Build the ministry page
    $ministryPage = array(
        'post_title'    => ucfirst($name),
        'post_content'  => '',
        'post_status'   => $status,
        'post_type'	    => 'location',
        'post_author'	=> get_current_user_id(),
    );

	$ministryCatId	= get_term_by('name', 'Ministries', 'locations')->term_id;

    //Insert the page
    $postId = wp_insert_post( $ministryPage );

    //Add the ministry cat
    wp_set_post_terms($postId , $ministryCatId, 'locations');

    //Store the ministry location
    if ($postId != 0){
        //Add the location to the page
        do_action('sim_ministry_added', [$ministryCatId], $postId);
    }

    $url = get_permalink($postId);

    return [
		'html'		=> "Succesfully created new ministry page, see it <a href='$url'>here</a>",
		'postId'	=> $postId
	];
}

/**
 * Update the users roles
 */
function updateRoles($userId='', $newRoles=[]){
	if ( !function_exists( 'populate_roles' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/schema.php' );
	}
	
	populate_roles();

	if(empty($userId)){
		$userId	= $_POST['userid'];
	}
	
	$user 		= get_userdata($userId);
	if(!$user){
		return new \WP_Error('user', 'No user found');
	}

    $userRoles 	= $user->roles;

	if(empty($newRoles)){
		$newRoles	= (array)$_POST['roles'];
	}

    if(empty(array_diff($userRoles, array_keys($newRoles)) ) && empty(array_diff(array_keys($newRoles), $userRoles))){
        return "Nothing to update";
    }

	SIM\saveExtraUserRoles($userId, $newRoles);

    return "Updated roles succesfully";
}

/**
 * Creates a new useraccount from POST values
 */
function createUserAccount(){
    // Check if the current user has the right to create approved user accounts
    $user 		= wp_get_current_user();
	$userRoles	= $user->roles;
	if(in_array('usermanagement', $userRoles)){
		$approved = true;
	}

	$lastName	= ucfirst(sanitize_text_field($_POST["last_name"]));
	$firstName	= ucfirst(sanitize_text_field($_POST["first_name"]));
	
	if (empty($_POST["email"])){
		$username = SIM\getAvailableUsername($firstName, $lastName);
		
		//Make up a non-existing emailaddress
		$email = sanitize_email($username."@".$lastName.".empty");
	}else{
        $email = sanitize_email($_POST["email"]);
    }
	
	if(empty($_POST["validity"])){
		$validity = "unlimited";
	}else{
        $validity = $_POST["validity"];
	}
	
	//Create the account
	$userId = SIM\addUserAccount($firstName, $lastName, $email, $approved, $validity, $_POST['roles']);
	if(is_wp_error($userId)){
		return $userId;
	}
	
    if(in_array('usermanagement', $userRoles)){
        $url		= SIM\ADMIN\getDefaultPageLink(MODULE_SLUG, 'user_edit_page')."?userid=$userId";
        $message = "Succesfully created an useraccount for $firstName<br>You can edit the deails <a href='$url'>here</a>";
    }else{
        $message = "Succesfully created useraccount for $firstName<br>You can now select $firstName in the dropdowns";
    }
		
	return [
        'message'	=> $message,
        'user_id'	=> $userId
    ];
}

/**
 * Extend the validity of an temporary account
 */
function extendValidity(){
	$userId = $_POST['userid'];
    if(isset($_POST['unlimited']) && $_POST['unlimited'] == 'unlimited'){
        $date       = 'unlimited';
        $message    = "Marked the useraccount for ".get_userdata($userId)->first_name." to never expire.";
    }else{
        $date       = sanitize_text_field($_POST['new_expiry_date']);
        $dateStr   = date(DATEFORMAT, strtotime($date));
        $message    = "Extended valitidy for ".get_userdata($userId)->first_name." till $dateStr";
    }
    update_user_meta( $userId, 'account_validity', $date);
	
    return $message;
}