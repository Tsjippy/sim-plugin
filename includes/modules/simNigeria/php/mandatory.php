<?php

add_filter('sim_mandatory_audience_param', function($keys){
    $keys['nolocal']    = "Not mandatory for Nigerians";
    $keys['noshort']    = "Not mandatory for people coming for a vision trip";

    return  $keys;
});

add_action('sim_mandatory_save_audience_param', function($audiences, $post){
    if(isset($audiences['nolocal'])){
        //Get all users
        $users = get_users();
        
        //Loop over the users
        foreach($users as $user){
            //check if they are local
            $visaInfo          = get_user_meta($user->ID, 'visa_info', true);
            
            if(isset($visaInfo['permit_type']) && ($visaInfo['permit_type'][0] == 'no' || $visaInfo['permit_type'][0] == 'visa')){
                //get current already read pages
                $readPages		= (array)get_user_meta( $user->ID, 'read_pages', true );
        
                //add current page
                $readPages[]	= $post->ID;

                //update
                update_user_meta( $user->ID, 'read_pages', $readPages);
            }
        }
    }
}, 10, 2);

add_filter('sim_should_read_mandatory_page', function($mustRead, $audience, $userId){
    // Only check if must read is true
    if(!$mustRead){
        return $mustRead;
    }

    // Check if local Nigerian
    $visaInfo   = get_user_meta($userId, 'visa_info', true);

    if(
        isset($visaInfo['permit_type'])         &&
        $visaInfo['permit_type'][0] == 'no'     &&
        isset($audience['nolocal'])
    ){
        return false;
    }

    return $mustRead;
}, 10, 3);
