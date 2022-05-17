<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_filter('before_saving_formdata',function($formresults, $formname, $userId){
	if($formname != 'profile_picture') return $formresults;	
	
	// Hide profile picture by default from media galery
	$picture_id	=  $formresults['profile_picture'][0];
	if(is_numeric($picture_id)) update_post_meta($picture_id, 'gallery_visibility', 'hide' );

	return $formresults;
},10,3);

function get_profile_picture_url($userId,$size=[50,50]){
	$attachment_id	= get_user_meta($userId,'profile_picture',true);
	$url			= false;
	if(is_numeric($attachment_id)){
		$url = wp_get_attachment_image_url($attachment_id,$size);
	}
	
	return $url;
}

function getProfilePicturePath($userId){
	$attachment_id	= get_user_meta($userId,'profile_picture',true);
	$path			= false;
	if(is_numeric($attachment_id)){
		$path = get_attached_file($attachment_id);
	}
	
	return $path;
}

// Apply filter
add_filter( 'get_avatar' , function ( $avatar, $id_or_email, $size, $default, $alt ) {
    $user = false;
 
	//CHeck if an, id, email or user is given
    if ( is_numeric( $id_or_email ) ) {
        $id = (int) $id_or_email;
        $user = get_user_by( 'id' , $id );
 
    } elseif ( is_object( $id_or_email ) ) {
        if ( ! empty( $id_or_email->user_id ) ) {
            $id = (int) $id_or_email->user_id;
            $user = get_user_by( 'id' , $id );
        }
    } else {
        $user = get_user_by( 'email', $id_or_email );   
    }
	
	//If we have valid user, return there profile picture or the default
    if ( $user && is_object( $user ) ) {
		//Get profile picture id from db
		$url = get_profile_picture_url($user->ID);
		if ( $url == '' ) $url = plugins_url('',__DIR__).'/../pictures/usericon.png';
		$avatar = "<img alt='$alt' src='$url' class='avatar avatar-{$size} photo' height='$size' width='$size' />";
    }
    return $avatar;
}, 1 , 5 );
