<?php
namespace SIM\FORMS;
use SIM;

/**
 * Gets all mandatory or recomended form fields as html links
 *
 * @param int    	$userId 	WP user id
 * @param string   	$type		One of 'mandatory' or 'recommended'
 *
 * @return string 	Returns html links to forms with empty mandatory fields
 */
function getAllEmptyRequiredElements($userId, $type){
	global $wpdb;

	$child				= SIM\isChild($userId);
	if($child){
		$childName		= get_userdata($userId)->first_name;
	}

	$simForms			= new SimForms();

	$query				= "SELECT * FROM {$simForms->elTableName} WHERE form_id IN(SELECT id FROM {$simForms->tableName} WHERE save_in_meta=1) AND ";
	if($type == 'all'){
		$query			.= "(`recommended`=1 OR `mandatory`=1)";
	}else{
		$query			.= "`$type`=1";
	}

	$elements				= $wpdb->get_results($query);

	// Filters the list of elements
	$elements				= apply_filters("sim_{$type}_elements_filter", $elements, $userId);

	$html				= '';
	if(!empty($elements)){
		$html				= '';
		$formUrls			= [];

		//check which of the fields are not yet filled in
		foreach($elements as $index=>$element){
			//check if this element applies to this user
			$warningCondition	= maybe_unserialize($element->warning_conditions);
			if(is_array($warningCondition)){
				SIM\cleanUpNestedArray($warningCondition, true);
				$skip	= false;

				foreach($warningCondition as $check){
					$value		= get_user_meta($userId, $check['meta_key'], true);

					if(!empty($check['meta_key_index'])){
						if(isset($value[$check['meta_key_index']])){
							$value		= $value[$check['meta_key_index']];
						}else{
							$value		= '';
						}
					}

					if(is_array($value) && isset($value[0])){
						$value	= $value[0];
					}

					$checkValue	= '';
					if(isset($check['conditional_value'])){
						$checkValue			= $check['conditional_value'];
						$conditionalValue	= strtotime($check['conditional_value']);
						if($conditionalValue && Date('Y', $conditionalValue) < 2200){
							$checkValue	= Date('Y-m-d', $conditionalValue);
						}
					}

					switch($check['equation']){
						case '==':
							$result	= $value == $checkValue;
							break;
						case '!=':
							$result	= $value != $checkValue;
							break;
						case '>':
							$result	= $value > $checkValue;
							break;
						case '<':
							$result	= $value < $checkValue;
							break;
						default:
							$result = false;
					}

					// Check the result
					if($result){
						$skip = true;
						//break this loop when when already know we should skip this field
						if(!empty($check['combinator']) && $check['combinator'] == 'or'){
							break;
						}
					}else{
						$skip = false;
					}
				}
				
				//if we should check the next one
				if($skip ){
					continue;
				}
			}

			$metakey 	= explode('[', $element->name)[0];
			$value		= get_user_meta($userId, $metakey, true);

			$name		= $element->name;
			if (str_contains($name, '[')){
				$value = SIM\getMetaArrayValue($userId, $name, $value);
			}

			if(empty($value)){
				//get form url
				if(isset($formUrls[$element->form_id])){
					$formUrl			= $formUrls[$element->form_id];
				}else{
					$query				= "SELECT * FROM {$simForms->tableName} WHERE `id`={$element->form_id}";
					$form				= $wpdb->get_results($query)[0];
					$formUrl			= $form->form_url;

					//save in cache
					$formUrls[$element->form_id]	= $formUrl;
				}

				// Do not add if no form url given
				if(empty($formUrl)){
					continue;
				}

				parse_str(parse_url($formUrl, PHP_URL_QUERY), $params);

				//Show a nice name
				$name	= str_replace(['[]', '_'], ['', ' '], $element->nicename);
				$name	= ucfirst(str_replace(['[', ']'], [': ',''], $name));

				$baseUrl	= explode('main_tab=', $_SERVER['REQUEST_URI'])[0];
				$mainTab	= $params['main_tab'];
				if($child){
					$name 		.= " for $childName";
					$mainTab	 = strtolower($childName);
					$formUrl	 = str_replace($params['main_tab'], $mainTab, $formUrl);
				}
				
				// If the url has no hash or we are not on the same url
				if(
					!isset($_GET['userid']) && (
						empty($params['main_tab']) || 
						!str_contains($formUrl, $baseUrl)
					)
				){
					$html .= "<li><a href='$formUrl#{$element->name}'>$name</a></li>";
				//We are on the same page, just change the hash
				}else{
					$secondTab	= '';
					$names		= explode('[', $element->name);
					if(count($names) > 1){
						$secondTab	= $names[0];
					}
					$html .= "<li><a onclick='Main.changeUrl(this, `$secondTab`)' data-param_val='$mainTab' data-hash={$element->name} style='cursor:pointer'>$name</a></li>";
				}
			}
		}
	}

	if($type == 'mandatory' || $type == 'all'){
		$html	.= getAllRequiredForms($userId);
	}

	if(!empty($html)){
		$html	= "<ul>$html</ul>";
	}

	$html	= apply_filters("sim_{$type}_html_filter", $html, $userId);

	return $html;
}

/**
 * Gets all mandatory forms as html links
 *
 * @param int    		$userId 	WP user id
 *
 * @return string|array 			Returns html links to forms who are due for submission if a userid is given, an array of form => [userids] otherwise
 */
function getAllRequiredForms($userId=''){
	global $wpdb;

	$html				= '';
	$formReminders		= [];

	$simForms			= new SimForms();

	$query				= "SELECT * FROM {$simForms->tableName} WHERE reminder_frequency <> '' AND reminder_period <> '' AND reminder_startdate <> '' ";

	$forms				= $wpdb->get_results($query);

	foreach($forms as $form){
		// Check last submission date for this user for this form
		$interval 		= \DateInterval::createFromDateString("$form->reminder_frequency $form->reminder_period");
		$daterange 		= new \DatePeriod(
			date_create($form->reminder_startdate), 
			$interval , 
			new \DateTime('now')
		);

		$threshold		= $daterange->getEndDate()->format('Y-m-d');
		foreach($daterange as $date1){
			$threshold = $date1->format('Y-m-d');
		}

		$query			= "SELECT * FROM {$simForms->submissionTableName} WHERE form_id=$form->id AND timecreated > $threshold";

		$submissions	= $wpdb->get_results($query);

		// get all the users who have submitted the form after the threshold date
		$usersWithSubmission	= [];
		foreach($submissions as $submission){
			$results	= maybe_unserialize($submission->formresults);

			if(isset($results['userid'])){
				$usersWithSubmission[]	= $results['userid'];
			}elseif(isset($results['user_id'])){
				$usersWithSubmission[]	= $results['user_id'];
			}else{
				$usersWithSubmission[]	= $submission->userid;
			}
		}


		// now check which users are missing
		$users 		= get_users( array( 'fields' => array( 'ID' ) ) );
		$userIds	= [];
		foreach($users as $user){
			$userIds[]	= $user->ID;
		}

		$usersWithoutSubmission	= array_diff($userIds, $usersWithSubmission);

		if(is_numeric($userId)){
			if(in_array($userId, $usersWithoutSubmission)){
				$html .= "<li><a href='$form->form_url'>$form->name</a></li>";
			}
		}else{
			$formReminders[$form->id]	= $usersWithoutSubmission;
		}
	}

	if(is_numeric($userId)){
		return $html;
	}else{
		return $formReminders;
	}
}

add_filter('sim_mandatory_html_filter', __NAMESPACE__.'\addChildFields', 10, 2);
add_filter('sim_recommended_html_filter', __NAMESPACE__.'\addChildFields', 10, 2);
function addChildFields($html, $userId){
	// Add warnings for child fields
	$family = get_user_meta($userId, "family", true);
	
	//User has children
	if (isset($family["children"])){
		// Loop over children
		foreach($family["children"] as $child){
			$userData = get_userdata($child);
			// Valid user account
			if ($userData){
				// Add html for each field as well
				$html	.= getAllEmptyRequiredElements($child, 'mandatory');
			}
		}
	}

	return $html;
}

add_action('sim_dashboard_warnings', function($userId){
	$html	 = getAllEmptyRequiredElements($userId, 'recommended');
	
	if (empty($html)){
		echo "<p>All your data is up to date, well done.</p>";
	}else{
		echo "<h3>Please finish your account:</h3>";
	}
		
	if (!empty($html)){
		echo "<p>Please fill in these fields:<br></p>$html";
	}
});