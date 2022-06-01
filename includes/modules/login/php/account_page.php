<?php 
namespace SIM\LOGIN;
use SIM;

add_filter('sim_user_info_page', function($html, $showCurrentUserData, $user){
    /*
        Two FA Info

    */
    if($showCurrentUserData){
        //Add tab button
        $html['tabs']['Two factor']	= '<li class="tablink" id="show_2fa_info" data-target="twofa_info">Two factor</li>';
        
        //Content
        $twofaHtml = '<div id="twofa_info" class="tabcontent hidden">';
            $twofaHtml .= twoFaSettingsForm($user->ID);
        $twofaHtml .= '</div>';

        $html['html']	.= $twofaHtml;	
    }

    return $html;
}, 10, 3);