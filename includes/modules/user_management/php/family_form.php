<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Add availbale partners as default
add_filter( 'add_form_multi_defaults', function($default_array_values, $user_id, $formname){
	if($formname != 'user_family') return $default_array_values;
	
	$dropdowns = fill_family_dropdowns($user_id);

	$default_array_values['Potential fathers'] 	= $dropdowns['father'];
	$default_array_values['Potential mothers'] 	= $dropdowns['mother'];
	$default_array_values['Potential spouses']	= $dropdowns['spouse'];
	$default_array_values['Potential children']	= $dropdowns['children'];
	
	return $default_array_values;
},10,3);

//Function used in the backend and frontend (family.php)
function fill_family_dropdowns($user_id){
	$birthday	= get_user_meta( $user_id, 'birthday', true );
	$gender		= get_user_meta( $user_id, 'gender', true );
	$family		= (array)get_user_meta( $user_id, 'family', true );

	if(empty($family['children'])){
		$children	= [];
	}else{
		$children	= $family['children'];
	}
	
	$dropdowns['spouse']	= [];
	$dropdowns['father']	= [];
	$dropdowns['mother']	= [];
	$dropdowns['children']	= [];

	//Get the id and the displayname of all users
	$users = get_users( array( 'fields' => array( 'ID','display_name' ) ,'orderby'=>'meta_value','meta_key'=>'last_name' ));
	$exists_array = array();

	//Loop over all users to find dublicate displaynames
	foreach($users as $key=>$user){
		//Get the displayname
		$display_name = strtolower($user->display_name);
		
		//If the display name is already found
		if (isset($exists_array[$display_name])){
			//Change current users displayname
			$user->display_name = $user->display_name." (".get_userdata($user->ID)->data->user_email.")";
			//Change previous found users displayname
			$user = $users[$exists_array[$display_name]];
			$user->display_name = $user->display_name." (".get_userdata($user->ID)->data->user_email.")";
		}else{
			//User has a so far unique displayname, add to array
			$exists_array[$display_name] = $key;
		}
	}
	
	//Loop over all users to fill the dropdowns
	foreach($users as $key=>$user){
		//do not process the current user
		if ($user->ID != $user_id){
			//Get the current gender
			$current_user_gender	= get_user_meta( $user->ID, 'gender', true );
			$current_user_birthday	= get_user_meta($user->ID, "birthday", true);
			$age_difference = null;
			$current_user_age = null;
			if($current_user_birthday != ""){
				$current_user_age = date_diff(date_create(date("Y-m-d")),date_create($current_user_birthday))->y;
				if ($birthday != ""){
					$age_difference = date("Y",strtotime($current_user_birthday)) - date("Y",strtotime($birthday));
				}
			}

			/*
				Fill the parent dropdowns
			*/			
			//Add the displayname as potential father if not younger then 18 and not part of the family
			if(($current_user_age == null or $current_user_age > 18) and !in_array($user->ID, $family)) {
				if ($current_user_gender == "" or $current_user_gender == 'male'){
					$dropdowns['father'][$user->ID] = $user->display_name;
				}
				if ($current_user_gender == "" or $current_user_gender == 'female'){
					$dropdowns['mother'][$user->ID]	= $user->display_name;
				}
			}
			
			/*
				Fill the spouse dropdown
			*/
			$hidden = '';
			//Check if current processing user already has a spouse
			$spouse = SIM\has_partner($user->ID);

			//if this is the spouse
			if( $spouse == $user_id){
				$dropdowns['spouse'][$user->ID] = $user->display_name;
			//this user does not have a spouse         
			}elseif (!is_numeric($spouse)){
				//If not the same gender
				if (empty($current_user_gender) or empty($gender) or $current_user_gender != $gender){
					// If not in the family
					if (!in_array($user->ID, $family) and !in_array($user->ID, $children)){
						if(!is_numeric($current_user_age) or $current_user_age > 18){
							//Add the displayname as potential spouse
							$dropdowns['spouse'][$user->ID] = $user->display_name;
						}
					}
				}
			}
			
			/*
				Fill the child dropdowns
			*/
			$parent_objects	= SIM\get_parents($user->ID);
			$parents 		= [];
			foreach($parent_objects as $par){
				$parents[]	= $par->ID;
			}
			if(
				in_array($user_id,$parents)		or		// or is the current users child
				(empty($parents)				and		//is not a child
				($age_difference == null		or		//there is no age diff
				$age_difference >16))					//the age diff is at least 16 years
			){
				$dropdowns['children'][$user->ID]	= $user->display_name;
			}
		}
	}
	return $dropdowns;
}

//Save family
add_filter('before_saving_formdata',function($formresults, $formname, $user_id){
	if($formname != 'user_family') return $formresults;
	
	global $Events;
	global $Maps;
	
	$family = $formresults["family"];
	
	$old_family = (array)get_user_meta( $user_id, 'family', true );

	$marker_id 	= get_user_meta($user_id,"marker_id",true);
	
	//Don't do anything if the current and the last family is equal
	if($family != $old_family){

		if ($family['weddingdate'] != $old_family['weddingdate']) {
			//save wedding date to partner as well
			if (isset($family['partner'])){
				//Get the partners family
				$partner_family = (array)get_user_meta( $family['partner'], 'family', true );
				$partner_family['weddingdate']	= $family['weddingdate'];
				update_user_meta($family['partner'], 'family', $partner_family);
			}

			$Events->create_celebration_event('Wedding anniversary', $user_id, 'family[weddingdate]', $family['weddingdate']);
		}

		$user_gender = get_user_meta( $user_id, 'gender', true );
		if(empty($user_gender)) $user_gender = 'male';
		
		if (isset($old_family['partner'])){
			$old_partner = $old_family['partner'];
		}else{
			$old_partner = null;
		}
		
		//Update the partner if needed
		if (isset($family['partner'])){
			//Get the partners family
			$partner_family = (array)get_user_meta( $family['partner'], 'family', true );
			
			if($family['partner'] != $old_partner){
				//Store curent user as partner of the partner
				$partner_family['partner'] = $user_id;
				
				//If I am updating this user to have a partner and that partner has children adds them to the current user as well
				if (isset($partner_family['children']) and !isset($family['children']) and !isset($old_family['children'])){
					//Add the children of the partner to this user as well.
					$formresults["family"]['children'] = $partner_family['children'];
				}
			}
		}
		
		//Update the previous partner if needed
		if ($old_partner != null and (!isset($family['partner']) or $family['partner'] != $old_partner)){
			$old_partner_family = get_user_meta( $old_partner, 'family', true );
			if (is_array($old_partner_family)){
				unset($old_partner_family["partner"]);
				save_family_in_db($old_partner, $old_partner_family);
			}
		}
		
		//Remove the parents from the old children if needed
		if(isset($old_family["children"])){
			if(!is_array($family["children"])) $family["children"] = [];
			
			//get the removed children
			$child_diff	= array_diff($old_family["children"],$family["children"]);
			
			//Loop over the removed children
			foreach($child_diff as $child){
				//Get the childs family array
				$child_family = get_user_meta( $child, 'family', true );
						
				//Remove the parents for this child
				if (is_array($child_family)){
					unset($child_family["father"]);
					unset($child_family["mother"]);
					
					//Save in DB
					if(count($child_family) == 0){
						//delete the family entry if its empty
						delete_user_meta( $child, 'family');
					}else{
						update_user_meta( $child, 'family', $child_family);
					}
				}
			}
		}
		
		//If there are currently kids
		if (isset($family["children"])){
			//get the added children
			$child_diff	= array_diff((array)$family["children"],(array)$old_family["children"]);
			
			//Loop over the added children
			foreach($child_diff as $child){
				//Get the childs family array
				$child_family = (array)get_user_meta( $child, 'family', true );
				
				//Store current user as parent of the child
				if($user_gender == 'male'){
					$child_family["father"] = $user_id;
					if (isset($family['partner'])){
						//store current partner as parent of the child
						$child_family["mother"] = $family['partner'];
					}
				}else{
					$child_family["mother"] = $user_id;
					if (isset($family['partner'])){
						//store current partner as parent of the child
						$child_family["father"] = $family['partner'];
					}
				}
				//Save in DB
				update_user_meta( $child, 'family', $child_family);
			}
				
			//Store child - for current users partner as well
			if (isset($family['partner'])){
				$partner_family["children"] = $family["children"];
			}

		//No children anymore, update the children and partner
		}elseif(isset($old_family["children"])){
			if (isset($family['partner'])){
				//Remove children - for the partner as well
				unset($partner_family["children"]);
			}		
		}

		//update marker icon if needed
		if (!empty($family['picture'] and $family['picture'] != $old_family['picture']) ){
			// Hide profile picture by default from media galery
			$picture_id	=  $family['picture'][0];
			if(is_numeric($picture_id)) update_post_meta($picture_id, 'gallery_visibility', 'hide' );

			$url = wp_get_attachment_url($family['picture'][0]);

			$icon_title	= get_userdata($user_id)->last_name.' family';

			$Maps->create_icon($marker_id, $icon_title, $url, 1);

			if (isset($family['partner'])){
				$partner_family 			= (array)get_user_meta( $family['partner'], 'family', true );
				
				$partner_family['picture']	= $family['picture'];

				//Save the partners family array
				update_user_meta( $family['partner'], 'family', $partner_family);
			}
		}
		
		//Save the family array
		save_family_in_db($user_id, $family);
		if (isset($family['partner'])){
			//Save the partners family array
			update_user_meta( $family['partner'], 'family', $partner_family);
		}
		
		//Save the marker id for all family members
		if(empty($marker_id) and isset($family['partner']))	$marker_id = get_user_meta($family['partner'],"marker_id",true);
		SIM\update_family_meta( $user_id, "marker_id", $marker_id);
		
		//update missionary page if needed
		SIM\USERPAGE\create_user_page($user_id);
	}
	
	return $formresults;
},10,3);

//Save in db
function save_family_in_db($user_id, $family){
	if(is_array($family)){
		SIM\clean_up_nested_array($family);
	}
	
	global $Maps;
	if (count($family)==0){
		//remove from db, there is no family anymore
		delete_user_meta($user_id,"family");
		
		$title = get_userdata($user_id)->display_name;
	}else{
		//Store in db
		update_user_meta($user_id,"family",$family);
		
		$title = get_userdata($user_id)->last_name." family";
	}
	
	//Update the marker title
	$marker_id = get_user_meta($user_id,"marker_id",true);
	$Maps->update_marker_title($marker_id, $title);
}

// add a family member modal
add_action('before_form',function ($formname){
	if($formname != 'user_family') return;
	
	if(isset($_GET['userid'])){
		$lastname = get_userdata($_GET['userid'])->last_name;
	}else{
		$lastname = wp_get_current_user()->last_name;
	}
		
	?>
	<div id='add_account_modal' class="modal hidden">
		<div class="modal-content">
			<span class="close">&times;</span>
			<form action="" method="post" id="add_member_form">
				<p>Please fill in the form to create a user profile for a family member</p>
				<input type="hidden" name="create_user_nonce"	value = "<?php echo wp_create_nonce("create_user_nonce"); ?>">
				<input type="hidden" name="action"				value = "adduseraccount">
				
				<label>
					<h4>First name</h4>
					<input type="text" class="" name="first_name">
				</label>
				
				<label>
					<h4>Last name</h4>
					<input type="text" name="last_name" value="<?php echo $lastname;?>">
				</label>
				
				<label>
					<h4>E-mail</h4>
					<input type="email" name="email">
				</label>
				
				<?php echo SIM\add_save_button('adduseraccount', 'Add family member');?>
			</form>
		</div>
	</div>
	<?php
});