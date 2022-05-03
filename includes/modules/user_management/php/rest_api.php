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

//add new ministry location via AJAX
function addMinistry(){	
    //Get the post data
    $name = sanitize_text_field($_POST["location_name"]);
    
    //Build the ministry page
    $ministry_page = array(
        'post_title'    => ucfirst($name),
        'post_content'  => '',
        'post_status'   => 'publish',
        'post_type'	    => 'location',
        'post_author'	=> get_current_user_id(),
    );
        
    //Insert the page
    $post_id = wp_insert_post( $ministry_page );
    
    //Add the ministry cat
    wp_set_post_terms($post_id ,27,'locationtype');
    
    //Store the ministry location
    if ($post_id != 0){
        //Add the location to the page
        do_action('sim_ministry_added', [27], $post_id);
    }

    $url = get_permalink($post_id);

    return "Succesfully created new ministry page, see it <a href='$url'>here</a>";
}

// Update the users roles
function updateRoles(){
	$user 		= get_userdata($_POST['userid']);
    $userRoles 	= $user->roles;
    $newRoles	= (array)$_POST['roles'];

    if(empty(array_diff($userRoles, array_keys($newRoles)) ) and empty(array_diff(array_keys($newRoles), $userRoles))){
        return "Nothing to update";
    }

    do_action('sim_roles_changed', $user, $newRoles);
    
    //add new roles
    foreach($newRoles as $key=>$role){
        //If the role is set, and the user does not have the role currently
        if(!in_array($key,$userRoles)){
            $user->add_role( $key );
        }
    }
    
    foreach($userRoles as $role){
        //If the role is not set, but the user has the role currently
        if(!in_array($role,array_keys($newRoles))){
            $user->remove_role( $role );
        }
    }
    
    return "Updated roles succesfully";
}

function createUserAccount(){
    // Check if the current user has the right to create approved user accounts
    $user 		= wp_get_current_user();
	$user_roles = $user->roles;
	if(in_array('usermanagement', $user_roles)){
		$approved = true;
	}

	$last_name	= sanitize_text_field($_POST["last_name"]);
	$first_name = sanitize_text_field($_POST["first_name"]);
	
	if (empty($_POST["email"])){
		$username = SIM\getAvailableUsername($first_name, $last_name);
		
		//Make up a non-existing emailaddress
		$email = sanitize_email($username."@".$last_name.".empty");
	}else{
        $email = sanitize_email($_POST["email"]);
    }
	
	if(empty($_POST["validity"])){
		$validity = "unlimited";
	}else{
        $validity = $_POST["validity"];
	}
	
	//Create the account
	$user_id = SIM\addUserAccount($first_name, $last_name, $email, $approved, $validity);
	if(is_wp_error($user_id))   return $user_id;
	
    if(in_array('usermanagement', $user_roles)){
        $url = SITEURL."/update-personal-info/?userid=$user_id";
        $message = "Succesfully created an useraccount for $first_name<br>You can edit the deails <a href='$url'>here</a>";
    }else{
        $message = "Succesfully created useraccount for $first_name<br>You can now select $first_name in the dropdowns";
    }

	do_action('sim_after_user_account_creation', $user_id);
		
	return [
        'message'	=> $message,
        'user_id'		=> $user_id
    ];
}

function extendValidity(){
	$user_id = $_POST['userid'];
    if(isset($_POST['unlimited']) and $_POST['unlimited'] == 'unlimited'){
        $date       = 'unlimited';
        $message    = "Marked the useraccount for ".get_userdata($user_id)->first_name." to never expire.";
    }else{
        $date       = sanitize_text_field($_POST['new_expiry_date']);
        $date_str   = date('d-m-Y', strtotime($date));
        $message    = "Extended valitidy for ".get_userdata($user_id)->first_name." till $date_str";
    }
    update_user_meta( $user_id, 'account_validity',$date);
	
    return $message;
}