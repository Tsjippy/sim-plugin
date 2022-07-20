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