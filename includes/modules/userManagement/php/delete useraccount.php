<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Delete user shortcode
add_shortcode( 'delete_user', function(){
	require_once(ABSPATH.'wp-admin/includes/user.php');
	
	$user = wp_get_current_user();

	if ( !in_array('usermanagement', $user->roles)){
		return "<div class='error'>You have no permission to delete user accounts!</div>";
	}

	//Load js
	wp_enqueue_script('user_select_script');

	$html = "";
	
	if(isset($_GET["userid"])){
		$userId = $_GET["userid"];
		$userdata = get_userdata($userId);
		if(!$userdata){
			return "<div class='error'>User with id $userId does not exist.</div>";
		}

		$family 		= get_user_meta($userId, "family", true);
		$nonceString 	= 'delete_user_'.$userId.'_nonce';
		
		if(!isset($_GET["confirm"])){
			$html	.="<script>";
				$html	.= "var remove = confirm('Are you sure you want to remove the useraccount for $userdata->display_name?');";
				$html	.= "if(remove){";
					$html	.= "var url=`\${window.location}&$nonceString=".wp_create_nonce($nonceString)."`;";
					if (is_array($family) && !empty($family)){
						$html	.= "var family = confirm('Do you want to delete all useraccounts for the familymembers of $userdata->display_name as well?');";
						$html	.= "if(family){";
							$html	.= "window.location = url+'&confirm=true&family=true'";
						$html	.= "}else{";
							$html	.= "window.location = url+'&confirm=true'";
						$html	.= "}";
					}else{
						$html	.= "window.location = url+'&confirm=true'";
					}
				$html	.= "}";
			$html	.= "</script>";
		}elseif($_GET["confirm"] == "true"){
			if(!isset($_GET[$nonceString]) || !wp_create_nonce($_GET[$nonceString],$nonceString)){
				$html .='<div class="error">Invalid nonce! Refresh the page</div>';
			}else{
				$deletedName = $userdata->display_name;
				if(isset($_GET["family"]) && $_GET["family"] == "true" && is_array($family) && !empty($family)){
					$deletedName .= " and all the family";
					if (isset($family["children"])){
						$family = array_merge($family["children"],$family);
						unset($family["children"]);
					}
					foreach($family as $relative){
						//Remove user account
						wp_delete_user($relative,1);
					}
				}
				//Remove user account
				wp_delete_user($userId,1);
				$html .= "<div class='success'>Useraccount for $deletedName succcesfully deleted.</div>";
				$html .= "<script>";
					$html .= "setTimeout(function(){";
						$html .= "window.location = window.location.href.replace('/?userid=$userId&delete_user_{$userId}_nonce=".$_GET[$nonceString]."&confirm=true','').replace('&family=true','');";
					$html .= "}, 3000);";
				$html .= "</script>";
			}
		}
	}
	
	$html .= SIM\userSelect("Select an user to delete from the website:");
	
	return $html;
});