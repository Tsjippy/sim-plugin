<?php
namespace SIM\USERMANAGEMENT;
use SIM;

/**
 * Displays the forms for children
 */
function showChildrenFields($child_id){
	$availableForms		= (array)SIM\getModuleOption(MODULE_SLUG, 'enabled-forms');

	ob_start();
	if(in_array('generic', $availableForms)){
		?>					
		<button class="button tablink active"	id="show_generic_child_info_<?php echo $child_id;?>"			data-target="generic_child_info_<?php echo $child_id;?>">Generic info</button>
		<?php
	}

	if(in_array('vaccinations', $availableForms)){
		?>
		<button class="button tablink" 			id="show_medical_child_info_<?php echo $child_id;?>"			data-target="medical_child_info_<?php echo $child_id;?>">Vaccinations</button>
		<?php
	}

	if(in_array('profile picture', $availableForms)){
		?>
		<button class="button tablink"			id="show_profile_picture_child_info_<?php echo $child_id;?>" 	data-target="profile_picture_child_info_<?php echo $child_id;?>">Profile picture</button>
		<?php
	}

	if(in_array('profile picture', $availableForms)){
		?>
		<div id="profile_picture_child_info_<?php echo $child_id;?>" class="tabcontent hidden">
			<?php 
			//profile_picture
			echo do_shortcode("[formbuilder formname=profile_picture userid='$child_id']");
			?>
		</div>
		<?php
	}
	
	if(in_array('vaccinations', $availableForms)){
		?>
		<div id="medical_child_info_<?php echo $child_id;?>" class="tabcontent hidden">
			<?php echo do_shortcode("[formbuilder formname=user_medical userid=$child_id]"); ?>
		</div>
		<?php
	}
	
	if(in_array('generic', $availableForms)){
		?>
		<div id="generic_child_info_<?php echo $child_id;?>" class="tabcontent">
			<?php echo do_shortcode("[formbuilder formname=child_generic userid=$child_id]"); ?>
		</div>
		<?php
	}

	return ob_get_clean();
}