<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Remove missionary page and missionary marker on user account deletion
add_action('delete_user', 'SIM\remove_user_data');
function remove_user_data ($user_id){
	global $wpdb;
	global $WebmasterName;
	global $Maps;
	global $Modules;
	
	$userdata		= get_userdata($user_id);
	$displayname	= $userdata->display_name;
	
	SIM\print_array("Deleting userdata for user $displayname");
	
	$attachment_id = get_user_meta($user_id,'profile_picture',true);
	if(is_numeric($attachment_id)){
		//Remove profile picture
		wp_delete_attachment($attachment_id,true);
		SIM\print_array("Removed profile picture for user $displayname");
	}
	
	//remove category from mailchimp
	$tags = array_merge(explode(',', $Modules['mailchimp']['user_tags']),explode(',',$Modules['mailchimp']['missionary_tags']));
	$Mailchimp = new SIM\MAILCHIMP\Mailchimp($user_id);
	
	$Mailchimp->change_tags($tags, 'inactive');

	$family = SIM\family_flat_array($user_id);
	//Only remove if there is no family
	if (count($family) == 0){
		//Remove missionary page
		SIM\remove_user_page($user_id);

		//Check if a personal marker exists for this user
		$marker_id = get_user_meta($user_id,"marker_id",true);
		//There exists a marker for this person, remove it
		if ($marker_id != ""){
			$query = $wpdb->prepare("SELECT icon FROM {$wpdb->prefix}ums_markers WHERE id = %d ", $marker_id);
			$marker_icon_id = $wpdb->get_var($query);
			if ($marker_icon_id != null){
				$icon = $marker_icon_id;
			}else{
				SIM\print_array("Marker id is $marker_id but this marker is not found in the db");
				$icon = 1;
			}
			
			//Delete the personal marker
			$wpdb->delete( $wpdb->prefix .'ums_markers', array( 'id' => $marker_id ) );
			SIM\print_array("Deleted the marker for $displayname with id $marker_id");
			if ($icon != 1){
				//Delete the personal icon
				$wpdb->delete( $wpdb->prefix .'ums_icons', array( 'id' => $icon ));
				SIM\print_array("Deleted the personal icon for $displayname");
			}
		}
		
		//Remove account statements
		$account_statements = get_user_meta($user_id, "account_statements", true);
		if(is_array($account_statements)){
			foreach($account_statements as $key => $account_statement){
				$file_path = str_replace(wp_get_upload_dir()["baseurl"],wp_get_upload_dir()["basedir"],$account_statement);
				unlink($file_path);
				SIM\print_array("Removed $file_path");
			}
		}
	//User has family
	}else{
		/* 
			After removal of this account the current spouse has no children and spouse, 
			so update the Private page, missionary page, and marker
		*/
		if(
			(
				isset($family["partner"]) or 
				isset($family["father"]) or 
				isset($family["mother"])
			) and 
			count($family) == 1
		){
			//Get the partners display name to use as the new title
			if(isset($family["partner"])){
				$title = get_userdata($family["partner"])->display_name;
			}elseif( isset($family["father"])){
				$title = get_userdata($family["father"])->display_name;
			}elseif( isset($family["mother"])){
				$title = get_userdata($family["mother"])->display_name;
			}
			
			//Update
			SIM\update_user_page_title($user_id, $title);
			
			//Check if a personal marker exists
			$marker_id = get_user_meta($user_id,"marker_id",true);
			$Maps->update_marker_title($marker_id, $title);
		}
		
		//Remove user from the family arrays of its relative
		foreach($family as $relative){
			//get the relatives family array
			$relative_family = get_user_meta($relative,"family",true);
			if ($relative_family != ""){
				//Find the familyrelation to $user_id
				$result = array_search($user_id, $relative_family);
				if($result){
					//Remove the relation
					unset($relative_family[$result]);
				}else{
					//Not found, check children
					if(isset($relative_family['children'])){
						$children = $relative_family['children'];
						$result = array_search($user_id, $children);
						if($result!==null){
							//Remove the relation
							unset($children[$result]);
							//This was the only child, remove the whole children entry
							if (count($children)==0){
								unset($relative_family["children"]);
							}else{
								//update the family
								$relative_family['children'] = $children;
							}
						}
					}
				}
				if (count($relative_family)==0){
					//remove from db, there is no family anymore
					delete_user_meta($relative,"family");
				}else{
					//Store in db
					update_user_meta($relative,"family",$relative_family);					
				}
			}
		}
	}

	//Send e-mail
	$headers = array('Content-Type: text/html; charset=UTF-8');

	$message = "Dear $userdata->first_name<br>
	<br>
	This is to inform you that your account on simnigeria.org has been deleted.<br>
	<br>
	Kind regards,<br>
	$WebmasterName";
					
	wp_mail($userdata->user_email, 'Your account on simnigeria.org has been deleted', $message, $headers );
}

//Delete user shortcode
add_shortcode( 'delete_user', 'SIM\delete_user_shortcode' );
function delete_user_shortcode(){
	require_once(ABSPATH.'wp-admin/includes/user.php');
	
	$user = wp_get_current_user();
	if ( in_array('usermanagement',$user->roles)){
		//Load js	
		wp_enqueue_script('user_select_script');
	
		$html = "";
		
		if(isset($_GET["userid"])){
			$user_id = $_GET["userid"];
			$userdata = get_userdata($user_id);
			if($userdata != null){
				$family = get_user_meta($user_id,"family",true);
				$nonce_string = 'delete_user_'.$user_id.'_nonce';
				
				if(!isset($_GET["confirm"])){
					echo '<script>
					var remove = confirm("Are you sure you want to remove the useraccount for '.$userdata->display_name.'?");
					if(remove){
						var url=window.location+"&'.$nonce_string.'='.wp_create_nonce($nonce_string).'";';
						if (is_array($family) and count($family)>0){
							echo '
							var family = confirm("Do you want to delete all useraccounts for the familymembers of '.$userdata->display_name.' as well?");
							if(family){
								window.location = url+"&confirm=true&family=true";
							}else{
								window.location = url+"&confirm=true";
							}';
						}else{
							echo 'window.location = url+"&confirm=true"';
						}
					echo '}
					</script>';
				}elseif($_GET["confirm"] == "true"){
					if(!isset($_GET[$nonce_string]) or !wp_create_nonce($_GET[$nonce_string],$nonce_string)){
						$html .='<div class="error">Invalid nonce! Refresh the page</div>';
					}else{
						$deleted_name = $userdata->display_name;
						if(isset($_GET["family"]) and $_GET["family"] == "true"){
							if (is_array($family) and count($family)>0){
								$deleted_name .= " and all the family";
								if (isset($family["children"])){
									$family = array_merge($family["children"],$family);
									unset($family["children"]);
								}
								foreach($family as $relative){
									//Remove user account
									wp_delete_user($relative,1);
								}
							}
						}
						//Remove user account
						wp_delete_user($user_id,1);
						$html .= '<div class="success">Useraccount for '.$deleted_name.' succcesfully deleted.</div>';
						echo "<script>
							setTimeout(function(){
								window.location = window.location.href.replace('/?userid=$user_id&delete_user_{$user_id}_nonce=".$_GET[$nonce_string]."&confirm=true','').replace('&family=true','');
							}, 3000);
						</script>";
					}
				}
				
			}else{
				$html .= '<div class="error">User with id '.$user_id.' does not exist.</div>';
			}
		}
		
		$html .= SIM\user_select("Select an user to delete from the website:");
		
		return $html;
	}
}