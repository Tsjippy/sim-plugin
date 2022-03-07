<?php
namespace SIM\USERMANAGEMENT;
use SIM;

function show_children_fields($child_id){
	ob_start();
	?>					
	<button class="button tablink active"	id="show_generic_child_info_<?php echo $child_id;?>"			data-target="generic_child_info_<?php echo $child_id;?>">Generic info</button>
	<button class="button tablink" 			id="show_medical_child_info_<?php echo $child_id;?>"			data-target="medical_child_info_<?php echo $child_id;?>">Vaccinations</button>
	<button class="button tablink"			id="show_profile_picture_child_info_<?php echo $child_id;?>" 	data-target="profile_picture_child_info_<?php echo $child_id;?>">Profile picture</button>

	<div id="profile_picture_child_info_<?php echo $child_id;?>" class="tabcontent hidden">
		<?php 
		//Upload button for profile_picture
		echo SIM\document_upload($child_id, $documentname='Profile image',$targetdir='profile_pictures',$multiple=false,$metakey='profile_picture',$library=true);
		?>
	</div>
	
	<div id="medical_child_info_<?php echo $child_id;?>" class="tabcontent hidden">
		<?php echo do_shortcode("[formbuilder datatype=user_medical userid=$child_id]"); ?>
	</div>
	
	<div id="generic_child_info_<?php echo $child_id;?>" class="tabcontent">
		<?php echo do_shortcode("[formbuilder datatype=child_generic userid=$child_id]"); ?>
	</div>

	<?php
	return ob_get_clean();
}