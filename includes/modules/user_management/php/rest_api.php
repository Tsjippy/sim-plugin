<?php
namespace SIM\USERMANAGEMENT;
use SIM;
use WP_User;

add_action( 'rest_api_init', function () {
	// add element to form
	register_rest_route( 
		'sim/v1/user_management', 
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
		'sim/v1/user_management', 
		'/disable_useraccount', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	function(){
                if(empty(get_user_meta( $_POST['userid'], 'disabled', true ))){
                    update_user_meta( $_POST['userid'], 'disabled', true );
                    return 'Succesfully disabled the user account';
                }else{
                    delete_user_meta( $_POST['userid'], 'disabled');
                    return 'Succesfully enabled the user account';
                }
            },
			'permission_callback' 	=> function(){
                return in_array('usermanagement', wp_get_current_user()->roles);
            },
			'args'					=> array(
				'userid'		=> array(
					'required'	=> true,
                    'validate_callback' => 'is_numeric'
				)
			)
		)
	);

    // update user roles
	register_rest_route( 
		'sim/v1/user_management', 
		'/update_roles', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\updateRoles',
			'permission_callback' 	=> function(){
                return in_array('usermanagement', wp_get_current_user()->roles);
            },
			'args'					=> array(
				'userid'		=> array(
					'required'	=> true,
                    'validate_callback' => 'is_numeric'
                ),
                'roles'		=> array(
					'required'	=> true
				)
			)
		)
	);

    // add user account
	register_rest_route( 
		'sim/v1/user_management', 
		'/add_useraccount', 
		array(
			'methods' 				=> 'POST',
			'callback' 				=> 	__NAMESPACE__.'\createUserAccount',
			'permission_callback' 	=> function(){
                return in_array('usermanagement', wp_get_current_user()->roles);
            },
			'args'					=> array(
				'first_name'		=> array(
					'required'	=> true
                ),
                'last_name'		=> array(
					'required'	=> true
				)
			)
		)
	);

    // extend user account validity
	register_rest_route( 
		'sim/v1/user_management', 
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
                    'validate_callback' => 'is_numeric'
                ),
                'new_expiry_date'		=> array(
					'required'	=> true
				)
			)
		)
	);
});

/**
 * add new ministry location via rest api
 */
function addMinistry(){	
    //Get the post data
    $name = sanitize_text_field($_POST["location_name"]);
    
    //Build the ministry page
    $ministryPage = array(
        'post_title'    => ucfirst($name),
        'post_content'  => '',
        'post_status'   => 'publish',
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

    return "Succesfully created new ministry page, see it <a href='$url'>here</a>";
}

/**
 * Update the users roles
 */
function updateRoles(){
	$user 		= get_userdata($_POST['userid']);
    $userRoles 	= $user->roles;
    $newRoles	= (array)$_POST['roles'];

    if(empty(array_diff($userRoles, array_keys($newRoles)) ) and empty(array_diff(array_keys($newRoles), $userRoles))){
        return "Nothing to update";
    }

	SIM\saveExtraUserRoles($_POST['userid']);
    
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
	$userId = SIM\addUserAccount($firstName, $lastName, $email, $approved, $validity);
	if(is_wp_error($userId))   return $userId;
	
    if(in_array('usermanagement', $userRoles)){
        $url = SITEURL."/update-personal-info/?userid=$userId";
        $message = "Succesfully created an useraccount for $firstName<br>You can edit the deails <a href='$url'>here</a>";
    }else{
        $message = "Succesfully created useraccount for $firstName<br>You can now select $firstName in the dropdowns";
    }

	do_action('sim_after_user_account_creation', $userId);
		
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
    if(isset($_POST['unlimited']) and $_POST['unlimited'] == 'unlimited'){
        $date       = 'unlimited';
        $message    = "Marked the useraccount for ".get_userdata($userId)->first_name." to never expire.";
    }else{
        $date       = sanitize_text_field($_POST['new_expiry_date']);
        $dateStr   = date('d-m-Y', strtotime($date));
        $message    = "Extended valitidy for ".get_userdata($userId)->first_name." till $dateStr";
    }
    update_user_meta( $userId, 'account_validity', $date);
	
    return $message;
}