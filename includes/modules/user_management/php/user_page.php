<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_action('sim_user_description', function($user){
    //Add a useraccount edit button if the user has the usermanagement role
	if (in_array('usermanagement', wp_get_current_user()->roles)){
        $pageId  = SIM\get_module_option('user_management', 'user_edit_page');
        $url     = SIM\getValidPageLink($pageId);
        if(!$url) return;

		$url .= '/?userid=';
		
		$html = "<div class='flex edit_useraccounts'><a href='$url$user->ID' class='button sim'>Edit useraccount for ".$user->first_name."</a>";
        $partner    = SIM\hasPartner($user->ID);
		if($partner){
			$html .= "<a  href='$url$partner' class='button sim'>Edit useraccount for ".get_userdata($partner)->first_name."</a>";
		}

        $family = (array)get_user_meta( $user->ID, 'family', true );
		if(isset($family['children'])){
			foreach($family['children'] as $child){
				$html .= "<a href='$url$child' class='button sim'>Edit useraccount for ".get_userdata($child)->first_name."</a>";
			}
		}
		$html .= '</div>';

        echo $html;
	}
});