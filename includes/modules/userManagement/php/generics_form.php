<?php
namespace SIM\USERMANAGEMENT;
use SIM;

add_filter('sim_forms_load_userdata',function($usermeta,$userId){
	$userdata	= (array)get_userdata($userId)->data;

	//Change ID to userid because its a confusing name
	$userdata['user_id']	= $userdata['ID'];
	unset($userdata['ID']);
	
	return array_merge($usermeta, $userdata);
}, 10, 2);

// phonenumbers and more
add_filter('sim_before_saving_formdata', function($formResults, $object){
	if($object->formdata->name != 'user_generics'){
		return $formResults;
	}

	// check if age is correct
	$family	= SIM\getUserFamily($object->userId);
	
	if(!empty($family['children'])){
		$ownAge		= strtotime(get_user_meta($object->userId, 'birthday', true));

		foreach($family['children']	as $child){
			$ageDiff	= strtotime(get_user_meta($child, 'birthday', true)) - $ownAge;

			if($ageDiff / YEAR_IN_SECONDS < 14){
				return new \WP_ERROR('forms', "Please don't lie");
			}
		}
	}

	//check if phonenumber has changed
	$oldPhonenumbers	= (array)get_user_meta($object->userId, 'phonenumbers', true);
	$newPhonenumbers	= $_POST['phonenumbers'];
	$changedNumbers		= array_diff($newPhonenumbers, $oldPhonenumbers);
	foreach($changedNumbers as $key=>$changedNumber){
		// Make sure the phonenumber is in the right format
		# = should be +
		if($changedNumber[0] == '='){
			$changedNumber = $formResults['phonenumbers'][$key]	= str_replace('=', '+', $changedNumber);
		}

		# 00 should be +
		if(substr($changedNumber, 0, 2) == '00'){
			$changedNumber = $formResults['phonenumbers'][$key]	= '+'.substr($changedNumber, 2);
		}

		# 0 should be +234
		if($changedNumber[0] == '0'){
			$changedNumber = $formResults['phonenumbers'][$key]	= '+234'.substr($changedNumber, 1);
		}

		# Should start with + by now
		if($changedNumber[0] != '+'){
			$changedNumber = $formResults['phonenumbers'][$key]	= '+234'.$changedNumber;
		}

		do_action('sim-phonenumber-updated', $changedNumber, $object->userId);
	}
	
	// store changed date
	if(!empty($changedNumbers)){
		update_user_meta($object->userId, 'phone-last-changed', time());
	}
	
	return $formResults;
}, 10, 2);

//Add ministry modal
add_filter('sim_before_form', function ($html, $formName){
	if($formName != 'user_generics'){
		return $html;
	}

	ob_start();
	?>
	<div id="add_ministry_modal" class="modal hidden">
		<div class="modal-content">
			<span id="modal_close" class="close">&times;</span>
			<form action="" method="post" id="add_ministry_form">
				<p>Please fill in the form to create a page describing your ministry and list it as an option</p>
				
				<label>
					<h4>Ministry name<span class="required">*</span></h4>
					<input type="text" name="location_name" class='wide' required>
				</label>
				
				<label>
					<h4>Address</h4>
					<input type="text" class="address wide" name="location[address]">
				</label>
				
				<label>
					<h4>Latitude</h4>
					<input type="text" class="latitude wide" name="location[latitude]">
				</label>
				
				<label>
					<h4>Longitude</h4>
					<input type="text" class="longitude wide" name="location[longitude]">
				</label>
				
				<?php echo SIM\addSaveButton('add_ministry','Add ministry page'); ?>
			</form>
		</div>
	</div>
	<?php

	return $html.ob_get_clean();
}, 10, 2);

/**
 * Get all locations with the ministries category
 *
 * @return	array	Ministries list
 */
function getMinistries(){
	$categories	= get_categories( array(
		'taxonomy'	=> 'locations',
		'parent'  	=> get_term_by('name', 'Ministries', 'locations')->term_id
	) );

	$ministries = [];

	foreach($categories as $category){
		$url	= "<a href='".get_category_link($category)."'>$category->name</a>";

		//Get all pages describing a ministry of this category
		$ministryPages = get_posts([
			'post_type'			=> 'location',
			'posts_per_page'	=> -1,
			'post_status'		=> 'publish',
			'orderby'			=> 'title',
			'tax_query' => array(
				array(
					'taxonomy'	=> 'locations',
					'field' 	=> 'term_id',
					'terms' 	=> $category->term_id
				)
			)
		]);

		foreach ( $ministryPages as $ministryPage ) {
			// do not show the main page of the category in th elist
			if($ministryPage->post_title == $category->name){
				continue;
			}
			$ministries[$url][$ministryPage->ID] = $ministryPage->post_title;
		}
	}

	$ministries['Other'][-1] 			= "Other";
	
	return $ministries;
}

/**
 * display ministries defined as php function in generics form
 *
 * @param	int		$userId		WP_User id
 *
 * @return	srtring				html
 */
function displayMinistryPositions($userId){
	$userMinistries 	= (array)get_user_meta( $userId, "jobs", true);
	
	ob_start();
	?>
	<div id="ministries_list" name='displayministrypositions_php'>
		<ul style='margin-left:0px;'>
			<?php
			//Retrieve all the ministries from the database
			foreach (getMinistries() as $url=>$ministries) {
				?>
				<li style="list-style-type: none" class="page_item page-item-204 page_item_has_children">
					<?php echo $url;?>
					<button class="button small expand-children" type='button' style='font-size: 8px;'>▼</button>
					<ul class='children'>
						<?php
						foreach ($ministries as $pageId=>$ministry) {
							//Check which option should be a checked ministry
							if (!empty($userMinistries[$pageId])){
								$checked	= 'checked';
								$class		= '';
								$position	= $userMinistries[$pageId];
							}else{
								$checked	= '';
								$class		= 'hidden';
								$position	= "";
							}
							//Add the ministries as options to the checkbox
							?>
							<li style="list-style-type: none">
								<label>
									<input type='checkbox' class='ministry_option_checkbox' name='ministries[]' value='<?php echo $pageId;?>' <?php echo $checked;?>>
									<span class='optionlabel'><?php echo $ministry;?></span>
								</label>
								<label class='ministryposition <?php echo $class;?>' style='display:block;'>
									<h4 class='labeltext'>Position at <?php echo $ministry;?>:</h4>
									<input type='text' name='jobs[<?php echo $pageId;?>]' value='<?php echo $position;?>'>
									<?php
									if ($ministry == "Other"){
										?>
										<p>Is your ministry not listed? Just add it! <button type='button' class='button' id='add-ministry-button'>Add Ministry</button></p>
										<?php
									}
									?>
								</label>
							</li>
							<?php
						}
					?>
					</ul>
				</li>
				<?php
			}
			?>
		</ul>
	</div>

	<script>
		document.addEventListener('click', ev=>{
			if(ev.target.matches('.expand-children')){
				ev.target.closest('li').querySelector('.children').classList.toggle('hidden');
			}
		});
	</script>
	<?php
	
	return ob_get_clean();
}