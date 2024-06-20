<?php
namespace SIM\USERMANAGEMENT;
use SIM;

//Add availbale partners as default
add_filter( 'sim_add_form_multi_defaults', function($defaultArrayValues, $userId, $formName){
	if($formName != 'user_family'){
		return $defaultArrayValues;
	}
	
	$potentials	= new PotentialFamilyMembers($userId);

	$potentials->potentialParents();
	$defaultArrayValues['Potential fathers'] 	= $potentials->potentialFathers;
	$defaultArrayValues['Potential mothers'] 	= $potentials->potentialMothers;
	$defaultArrayValues['Potential spouses']	= $potentials->potentialSpouses();
	$defaultArrayValues['Potential children']	= $potentials->potentialChildren();
	
	return $defaultArrayValues;
}, 10, 3);

//Save family
add_filter('sim_before_saving_formdata', function($formResults, $object){
	if($object->formData->name != 'user_family'){
		return $formResults;
	}
	
	$family 	= $formResults["family"];
	
	$oldFamily 	= (array)get_user_meta( $object->userId, 'family', true );
	
	//Don't do anything if the current and the last family is equal
	if($family == $oldFamily){
		return $formResults;
	}

	$updateFamily			= new UpdateFamily($object->userId, $family, $oldFamily);
	$formResults["family"]	= $updateFamily->family;
	
	return $formResults;
}, 10, 2);

// add a family member modal
add_filter('sim_before_form', function ($html, $formName){
	if($formName != 'user_family'){
		return $html;
	}
	
	if(isset($_GET['userid'])){
		$lastname = get_userdata($_GET['userid'])->last_name;
	}else{
		$lastname = wp_get_current_user()->last_name;
	}

	ob_start();
		
	?>
	<div id='add_account_modal' class="modal hidden">
		<div class="modal-content">
			<span class="close">&times;</span>
			<form action="" method="post" id="add_member_form">
				<p>Please fill in the form to create a user profile for a family member</p>
								
				<label>
					<h4>First name</h4>
					<input type="text"  class='wide' name="first_name">
				</label>
				
				<label>
					<h4>Last name</h4>
					<input type="text" name="last_name"  class='wide' value="<?php echo $lastname;?>">
				</label>
				
				<label>
					<h4>E-mail</h4>
					<input type="email"  class='wide' name="email">
				</label>
				
				<?php echo SIM\addSaveButton('adduseraccount', 'Add family member');?>
			</form>
		</div>
	</div>
	<?php

	return $html.ob_get_clean();
}, 10, 2);