<?php
namespace SIM\FORMS;
use SIM;

/**
 * Gets all mandatory or recomended form fields as html links
 *
 * @param int    	$user_id 	WP user id
 * @param string   	$type		One of 'mandatory' or 'recommended'
 *
 * @return string 	Returns html links to forms with empty mandatory fields
 */
function get_all_fields($user_id, $type){
	global $wpdb;

	$formbuilder		= new Formbuilder();

	$query				= "SELECT * FROM {$formbuilder->el_table_name} WHERE ";
	if($type == 'all'){
		$query			.= "`recommended`=1 OR `mandatory`=1";
	}else{
		$query			.= "`$type`=1";
	}

	$fields				= $wpdb->get_results($query);

	$fields				= apply_filters("sim_{$type}_fields_filter", $fields, $user_id);

	$html				= '';
	if(!empty($fields)){
		$html				= '';
		$form_urls			= [];

		//check which of the fields are not yet filled in
		foreach($fields as $field){
			$metakey 	= explode('[',$field->name)[0];
			$value		= get_user_meta($user_id, $metakey, true);

			$name		= str_replace('[]', '', $field->name);
			if (strpos($name, '[') !== false){
				$value = SIM\get_meta_array_value($user_id, $name, $value);
			}

			if(empty($value)){
				//get form url
				if(isset($form_urls[$field->form_id])){
					$form_url		= $form_urls[$field->form_id];
				}else{
					$query				= "SELECT * FROM {$formbuilder->table_name} WHERE `id`={$field->form_id}";
					$form				= $wpdb->get_results($query)[0];
					$form_url			= maybe_unserialize($form->settings)['formnurl'];

					//save in cache
					$form_urls[$field->form_id]	= $form_url;
				}

				// Do not add if no form url given
				if(empty($form_url)) continue;

				parse_str(parse_url($form_url, PHP_URL_QUERY), $params);

				$name	= ucfirst(str_replace(']','',end(explode('[', str_replace(['[]', '_'], ['', ' '], $field->nicename)))));
				// If the url has no hash or we are not on the same url
				if(empty($params['main_tab']) or strpos($form_url, explode('main_tab=', $_SERVER['REQUEST_URI'])[0]) === false){
					$html .= "<li><a href='$form_url#{$field->name}'>$name</a></li>";
				//We are on the same page, just change the hash
				}else{
					$html .= "<li><a onclick='change_url(this)' data-param_val='{$params['main_tab']}' data-hash={$field->name} style='cursor:grabbing'>$name</a></li>";
				}
			}
		}

		if(!empty($html)){
			$html	= "<ul>$html</ul>";
		}
	}

	$html	= apply_filters("sim_{$type}_html_filter", $html, $user_id);

	return $html;
}

add_filter('sim_mandatory_fields_filter', function($fields, $user_id){
	// Local nigerian do not have mandatory fields
	if(!empty(get_user_meta( $user_id, 'local_nigerian', true ))){
		foreach($fields as $key=>$field){
			if(strpos($field->name, 'understudy') !== false){
				unset($fields[$key]);
			}
		}
	}

	// Unset visa fields if accompanying spouse
	$visa_info = get_user_meta($user_id, 'visa_info', true );
	if(isset($visa_info['accompanying']) or SIM\is_child($user_id)){
		foreach($fields as $key=>$field){
			if(strpos($field->name, 'understudy') !== false or strpos($field->name, 'visa_info') !== false){
				unset($fields[$key]);
			}
		}
	}else{
		// If no visa fields are set at all
		$understudy1 = get_user_meta($user_id, 'understudy1', true );
		$understudy2 = get_user_meta($user_id, 'understudy2', true );
		if(empty($visa_info) and empty($understudy1) and empty($understudy2)){
			foreach($fields as $key=>$field){
				if(strpos($field->name, 'understudy') !== false){
					unset($fields[$key]);
				}
			}
		}
	}

	$privacy_preference = (array)get_user_meta( $user_id, 'privacy_preference', true );

	// Location is not mandatory when hidden in privacy
	if (!empty($privacy_preference['hide_location'])){
		foreach($fields as $key=>$field){
			if($field->name == 'location'){
				unset($fields[$key]);
			}
		}
	}

	//Check for account picture, only if the privacy settings allow it
	if (!empty($privacy_preference['hide_profile_picture']))	$fields = $fields;

	// Filter mandatory fields for children
	if(SIM\is_child($user_id)){
		foreach($fields as $key=>$field){
			if(in_array($field->name, ['location[compound]', 'sending_office', 'arrival_date'])){
				unset($fields[$key]);
			}
		}
	}

	if (empty($fields)){
		//Set the value to done
		update_user_meta($user_id,"required_fields_status","done");
	//If there are no required fields to be filled.
	}else{
		update_user_meta($user_id,"required_fields_status","");
	}

	return $fields;
}, 10, 2);

add_filter('sim_mandatory_html_filter', 'SIM\FORMS\add_child_fields', 10, 2);
add_filter('sim_recommended_html_filter', 'SIM\FORMS\add_child_fields', 10, 2);
function add_child_fields($html, $user_id){
	// Add warnings for child fields
	$family = get_user_meta($user_id, "family", true);
	//User has children
	if (isset($family["children"])){
		// Loop over children
		foreach($family["children"] as $key=>$child){
			$userdata = get_userdata($child);
			// Valid user account
			if ($userdata){
				// Add html for each field as well
				$html	.= get_all_fields($child, 'mandatory');
			}
		}
	}

	return $html;
}

add_action('sim_dashboard_warnings', function($user_id){		
	$html	 = get_all_fields($user_id, 'recommended');
	
	if (empty($html)){
		echo "<p>All your data is up to date, well done.</p>";
	}else{
		echo "<h3>Please finish your account:</h3>";
	}
		
	if (!empty($html)){
		echo "<p>Please fill in these fields:<br></p>$html";
	}
});