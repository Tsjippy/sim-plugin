<?php
namespace SIM\USERMANAGEMENT;
use SIM;

/**
 * Displays the forms for children
 */
function showChildrenFields($childId){
	$availableForms		= (array)SIM\getModuleOption(MODULE_SLUG, 'enabled-forms');

	ob_start();
	$active	= 'active';
	$hidden	= '';
	if(in_array('generic', $availableForms)){
		echo "<button class=' button tablink $active' id='show_generic_child_info_$childId' data-target='generic_child_info_$childId'>Generic info</button>";
		$active= '';
	}

	if(in_array('vaccinations', $availableForms)){
		echo "<button class='button tablink $active' id='show_medical_child_info_$childId' data-target='medical_child_info_$childId'>Vaccinations</button>";
		$active= '';
	}

	if(in_array('profile picture', $availableForms)){
		echo "<button class='button tablink $active' id='show_profile_picture_child_info_$childId' data-target='profile_picture_child_info_$childId'>Profile picture</button>";
	}
	
	if(in_array('generic', $availableForms)){
		?>
		<div id='generic_child_info_<?php echo $childId;?>' class='tabcontent'>
			<?php echo do_shortcode("[formbuilder formname=child_generic userid=$childId]"); ?>
		</div>
		<?php

		$hidden	= 'hidden';
	}

	if(in_array('vaccinations', $availableForms)){
		echo	"<div id='medical_child_info_$childId' class='tabcontent $hidden'>";
			echo do_shortcode("[formbuilder formname=user_medical userid=$childId]");
		echo	"</div>";

		$hidden	= 'hidden';
	}

	if(in_array('profile picture', $availableForms)){
		echo	"<div id='profile_picture_child_info_<?php echo $childId;?>' class='tabcontent $hidden'>";
			echo do_shortcode("[formbuilder formname=profile_picture userid='$childId']");
		echo	"</div>";
	}

	return ob_get_clean();
}