<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Add availbale partners as default
add_filter( 'sim_add_form_multi_defaults', function($defaultArrayValues, $userId, $formName){
	if($formName != 'user_family') return $defaultArrayValues;
	
	$dropdowns = fill_family_dropdowns($userId);

	$defaultArrayValues['Potential fathers'] 	= $dropdowns['father'];
	$defaultArrayValues['Potential mothers'] 	= $dropdowns['mother'];
	$defaultArrayValues['Potential spouses']	= $dropdowns['spouse'];
	$defaultArrayValues['Potential children']	= $dropdowns['children'];
	
	return $defaultArrayValues;
},10,3);

//Function used in the backend and frontend (family.php)
function fill_family_dropdowns($userId){
	$birthday	= get_user_meta( $userId, 'birthday', true );
	$gender		= get_user_meta( $userId, 'gender', true );
	$family		= (array)get_user_meta( $userId, 'family', true );

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
		if ($user->ID != $userId){
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
			$spouse = SIM\hasPartner($user->ID);

			//if this is the spouse
			if( $spouse == $userId){
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
			$parent_objects	= SIM\getParents($user->ID);
			$parents 		= [];

			if($parent_objects){
				foreach($parent_objects as $par){
					$parents[]	= $par->ID;
				}
			}
			
			if(
				in_array($userId,$parents)		or		// or is the current users child
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
add_filter('sim_before_saving_formdata',function($formResults, $formName, $userId){
	if($formName != 'user_family') return $formResults;

	$events	= new SIM\EVENTS\Events();
	
	$family = $formResults["family"];
	
	$old_family = (array)get_user_meta( $userId, 'family', true );
	
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

			$events->createCelebrationEvent('Wedding anniversary', $userId, 'family[weddingdate]', $family['weddingdate']);
		}

		$user_gender = get_user_meta( $userId, 'gender', true );
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
				$partner_family['partner'] = $userId;
				
				//If I am updating this user to have a partner and that partner has children adds them to the current user as well
				if (isset($partner_family['children']) and !isset($family['children']) and !isset($old_family['children'])){
					//Add the children of the partner to this user as well.
					$formResults["family"]['children'] = $partner_family['children'];
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
					$child_family["father"] = $userId;
					if (isset($family['partner'])){
						//store current partner as parent of the child
						$child_family["mother"] = $family['partner'];
					}
				}else{
					$child_family["mother"] = $userId;
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

		// Update family picture
		if (!empty($family['picture'] and $family['picture'] != $old_family['picture']) ){
			// Hide profile picture by default from media galery
			$picture_id	=  $family['picture'][0];
			if(is_numeric($picture_id)) update_post_meta($picture_id, 'gallery_visibility', 'hide' );

			do_action('sim_update_family_picture', $userId, $family['picture'][0]);

			if (isset($family['partner'])){
				$partner_family 			= (array)get_user_meta( $family['partner'], 'family', true );
				
				$partner_family['picture']	= $family['picture'];

				//Save the partners family array
				update_user_meta( $family['partner'], 'family', $partner_family);
			}
		}
		
		//Save the family array
		save_family_in_db($userId, $family);
		if (isset($family['partner'])){
			//Save the partners family array
			update_user_meta( $family['partner'], 'family', $partner_family);
		}
		
		//update user page if needed
		if(function_exists('SIM\USERPAGE\createUserPage')){
			SIM\USERPAGE\createUserPage($userId);
		}
	}
	
	return $formResults;
},10,3);

//Save in db
function save_family_in_db($userId, $family){
	if(is_array($family)){
		SIM\cleanUpNestedArray($family);
	}

	if (count($family)==0){
		//remove from db, there is no family anymore
		delete_user_meta($userId, "family");
	}else{
		//Store in db
		update_user_meta($userId,"family",$family);
	}

	do_action('sim_family_safe', $userId);
}

// add a family member modal
add_action('sim_before_form',function ($formName){
	if($formName != 'user_family') return;
	
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
				
				<?php echo SIM\addSaveButton('adduseraccount', 'Add family member');?>
			</form>
		</div>
	</div>
	<?php
});