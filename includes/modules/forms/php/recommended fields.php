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
function getAllFields($userId, $type){
	global $wpdb;

	$child				= SIM\isChild($userId);
	if($child){
		$childName	= get_userdata($userId)->first_name;
	}

	$simForms		= new SimForms();

	$query				= "SELECT * FROM {$simForms->elTableName} WHERE ";
	if($type == 'all'){
		$query			.= "`recommended`=1 OR `mandatory`=1";
	}else{
		$query			.= "`$type`=1";
	}

	$fields				= $wpdb->get_results($query);

	$fields				= apply_filters("sim_{$type}_fields_filter", $fields, $userId);

	$html				= '';
	if(!empty($fields)){
		$html				= '';
		$formUrls			= [];

		//check which of the fields are not yet filled in
		foreach($fields as $field){
			//check if this field applies to this user
			$warningCondition	= maybe_unserialize($field->warning_conditions);
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

					if(isset($check['conditional_value'])){
						$checkValue	= $check['conditional_value'];
					}else{
						$checkValue	= '';
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

			$metakey 	= explode('[',$field->name)[0];
			$value		= get_user_meta($userId, $metakey, true);

			$name		= str_replace('[]', '', $field->name);
			if (strpos($name, '[') !== false){
				$value = SIM\getMetaArrayValue($userId, $name, $value);
			}

			if(empty($value)){
				//get form url
				if(isset($formUrls[$field->form_id])){
					$formUrl			= $formUrls[$field->form_id];
				}else{
					$query				= "SELECT * FROM {$simForms->tableName} WHERE `id`={$field->form_id}";
					$form				= $wpdb->get_results($query)[0];
					$formUrl			= maybe_unserialize($form->settings)['formurl'];

					//save in cache
					$formUrls[$field->form_id]	= $formUrl;
				}

				// Do not add if no form url given
				if(empty($formUrl)){
					continue;
				}

				parse_str(parse_url($formUrl, PHP_URL_QUERY), $params);

				//Show a nice name
				$name	= str_replace(['[]', '_'], ['', ' '], $field->nicename);
				$name	= ucfirst(str_replace(['[', ']'], [': ',''], $name));

				$baseUrl	= explode('main_tab=', $_SERVER['REQUEST_URI'])[0];
				$mainTab	= $params['main_tab'];
				if($child){
					$name 		.= " for $childName";
					$mainTab	 = strtolower($childName);
					$formUrl	 = str_replace($params['main_tab'], $mainTab, $formUrl);
				}
				
				// If the url has no hash or we are not on the same url
				if(empty($params['main_tab']) || strpos($formUrl, $baseUrl) === false){
					$html .= "<li><a href='$formUrl#{$field->name}'>$name</a></li>";
				//We are on the same page, just change the hash
				}else{
					$secondTab	= '';
					$names		= explode('[', $field->name);
					if(count($names) > 1){
						$secondTab	= $names[0];
					}
					$html .= "<li><a onclick='Main.changeUrl(this, `$secondTab`)' data-param_val='$mainTab' data-hash={$field->name} style='cursor:grabbing'>$name</a></li>";
				}
			}
		}

		if(!empty($html)){
			$html	= "<ul>$html</ul>";
		}
	}

	$html	= apply_filters("sim_{$type}_html_filter", $html, $userId);

	return $html;
}

add_filter('sim_mandatory_html_filter', __NAMESPACE__.'\add_child_fields', 10, 2);
add_filter('sim_recommended_html_filter', __NAMESPACE__.'\add_child_fields', 10, 2);
function add_child_fields($html, $userId){
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
				$html	.= getAllFields($child, 'mandatory');
			}
		}
	}

	return $html;
}

add_action('sim_dashboard_warnings', function($userId){		
	$html	 = getAllFields($userId, 'recommended');
	
	if (empty($html)){
		echo "<p>All your data is up to date, well done.</p>";
	}else{
		echo "<h3>Please finish your account:</h3>";
	}
		
	if (!empty($html)){
		echo "<p>Please fill in these fields:<br></p>$html";
	}
});