<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_filter('sim_before_saving_formdata',function($formResults, $formName, $userId){
	if($formName != 'profile_picture') return $formResults;	
	
	// Hide profile picture by default from media galery
	$pictureId	=  $formResults['profile_picture'][0];
	if(is_numeric($pictureId)) update_post_meta($pictureId, 'gallery_visibility', 'hide' );

	return $formResults;
},10,3);

/**
 * Get the url of the profile picture of a particular size
 * 
 * @param	int		$userId		The WP_User id
 * @param	array	$size		length and width of the picture
 * 
 * @return	string				The url
 */
function getProfilePictureUrl($userId, $size=[50,50]){
	$attachment_id	= get_user_meta($userId,'profile_picture',true);
	$url			= false;
	if(is_numeric($attachment_id)){
		$url = wp_get_attachment_image_url($attachment_id,$size);
	}
	
	return $url;
}

/**
 * Get the url of the profile picture of a particular size
 * 
 * @param	int		$userId		The WP_User id
 * 
 * @return	string|false		The path or false if no picture
 */
function getProfilePicturePath($userId){
	$attachmentId	= get_user_meta($userId, 'profile_picture', true);
	$path			= false;
	if(is_numeric($attachmentId)){
		$path = get_attached_file($attachmentId);
	}
	
	return $path;
}

// Apply filter
add_filter( 'get_avatar' , function ( $avatar, $idOrEmail, $size, $default, $alt ) {
    $user = false;
 
	//CHeck if an, id, email or user is given
    if ( is_numeric( $idOrEmail ) ) {
        $id = (int) $idOrEmail;
        $user = get_user_by( 'id' , $id );
 
    } elseif ( is_object( $idOrEmail ) ) {
        if ( ! empty( $idOrEmail->user_id ) ) {
            $id = (int) $idOrEmail->user_id;
            $user = get_user_by( 'id' , $id );
        }
    } else {
        $user = get_user_by( 'email', $idOrEmail );   
    }
	
	//If we have valid user, return there profile picture or the default
    if ( $user && is_object( $user ) ) {
		//Get profile picture id from db
		$url = getProfilePictureUrl($user->ID);
		if ( empty($url )){
			$url = plugins_url('', __DIR__).'/pictures/usericon.png';
		}
		$avatar = "<img alt='$alt' src='$url' class='avatar avatar-{$size} photo' height='$size' width='$size' />";
    }
    return $avatar;
}, 1 , 5 );
