<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Add availbale partners as default
add_filter( 'sim_add_form_multi_defaults', function($defaultArrayValues, $userId, $formName){
	if($formName != 'user_family'){
		return $defaultArrayValues;
	}
	
	$dropdowns = fillFamilyDropdowns($userId);

	$defaultArrayValues['Potential fathers'] 	= $dropdowns['father'];
	$defaultArrayValues['Potential mothers'] 	= $dropdowns['mother'];
	$defaultArrayValues['Potential spouses']	= $dropdowns['spouse'];
	$defaultArrayValues['Potential children']	= $dropdowns['children'];
	
	return $defaultArrayValues;
}, 10, 3);

//Function used in the backend and frontend (family.php)
function fillFamilyDropdowns($userId){
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
	$users 					= get_users( 
		array( 
			'fields' 	=> array( 'ID','display_name' ) ,
			'orderby'	=>'meta_value',
			'meta_key'	=>'last_name' 
		)
	);
	$existsArray = array();

	//Loop over all users to find dublicate displaynames
	foreach($users as $key=>$user){
		//Get the displayname
		$displayName = strtolower($user->display_name);
		
		//If the display name is already found
		if (isset($existsArray[$displayName])){
			//Change current users displayname
			$user->display_name = $user->display_name." (".get_userdata($user->ID)->data->user_email.")";
			//Change previous found users displayname
			$user = $users[$existsArray[$displayName]];
			$user->display_name = $user->display_name." (".get_userdata($user->ID)->data->user_email.")";
		}else{
			//User has a so far unique displayname, add to array
			$existsArray[$displayName] = $key;
		}
	}
	
	//Loop over all users to fill the dropdowns
	foreach($users as $key=>$user){
		//do not process the current user
		if ($user->ID != $userId){
			//Get the current gender
			$currentUserGender		= get_user_meta( $user->ID, 'gender', true );
			$currentUserBirthday	= get_user_meta($user->ID, "birthday", true);
			$ageDifference 			= null;
			$currentUserAge 		= null;
			if(!empty($currentUserBirthday)){
				$currentUserAge = date_diff(date_create(date("Y-m-d")), date_create($currentUserBirthday))->y;
				if (!empty($birthday)){
					$ageDifference = date("Y", strtotime($currentUserBirthday)) - date("Y", strtotime($birthday));
				}
			}

			/*
				Fill the parent dropdowns
			*/			
			//Add the displayname as potential father if not younger then 18 and not part of the family
			if(($currentUserAge == null || $currentUserAge > 18) && !in_array($user->ID, $family)) {
				if (empty($currentUserGender) || $currentUserGender == 'male'){
					$dropdowns['father'][$user->ID] = $user->display_name;
				}
				if (empty($currentUserGender) || $currentUserGender == 'female'){
					$dropdowns['mother'][$user->ID]	= $user->display_name;
				}
			}
			
			/*
				Fill the spouse dropdown
			*/
			//Check if current processing user already has a spouse
			$spouse = SIM\hasPartner($user->ID);

			//if this is the spouse
			if( 
				$spouse == $userId						||	// This is our spouse
				(
					!is_numeric($spouse)				&&	// this user does not have a spouse
					(
						empty($currentUserGender) 		|| 	// Current user has no gender filled in
						empty($gender) 					|| 	// Or the gender has not filled in
						$currentUserGender != $gender		// Or the genders differ
					)									&&
					!in_array($user->ID, $family) 		&& 	// We we are no family
					!in_array($user->ID, $children)		&&	// We are not a child
					(
						!is_numeric($currentUserAge) 	||	// Our age is not filled in
						$currentUserAge > 18				// We are older than 18
					)
				)
			){
				//Add the displayname as potential spouse
				$dropdowns['spouse'][$user->ID] = $user->display_name;
			}
			
			/*
				Fill the child dropdowns
			*/
			$parentObjects	= SIM\getParents($user->ID);
			$parents 		= [];

			if($parentObjects){
				foreach($parentObjects as $par){
					$parents[]	= $par->ID;
				}
			}
			
			if(
				in_array($userId, $parents)		||		// or is the current users child
				(empty($parents)				&&		//is not a child
				($ageDifference == null			||		//there is no age diff
				$ageDifference >16))					//the age diff is at least 16 years
			){
				$dropdowns['children'][$user->ID]	= $user->display_name;
			}
		}
	}
	return $dropdowns;
}



//Save family
add_filter('sim_before_saving_formdata', function($formResults, $formName, $userId){
	if($formName != 'user_family'){
		return $formResults;
	}
	
	$family 	= $formResults["family"];
	
	$oldFamily 	= (array)get_user_meta( $userId, 'family', true );
	
	//Don't do anything if the current and the last family is equal
	if($family == $oldFamily){
		return $formResults;
	}

	$updateFamily	= new UpdateFamily($userId, $family, $oldFamily);
	$formResults["family"]	= $updateFamily->family;
	
	return $formResults;
}, 10, 3);

// add a family member modal
add_action('sim_before_form',function ($formName){
	if($formName != 'user_family'){
		return;
	}
	
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