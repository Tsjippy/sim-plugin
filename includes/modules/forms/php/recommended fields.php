<?php
namespace SIM;

//Function to return the required fields
function get_required_fields($UserID){
	global $ProfilePage;
	$profile_page_url = get_permalink($ProfilePage);
	$html = '';
	$local_nigerian = get_user_meta( $UserID, 'local_nigerian', true );
	
	//Only show warnings to unlimited accounts of expatriats
	if($local_nigerian == ''){
		//Get the required fields string from the db
		$CustomSimSettings = get_option("customsimsettings");
		//Remove the location from the required fields if the privacy setings are not all
		$privacy_preference = get_user_meta( $UserID, 'privacy_preference', true );
		if(!is_array($privacy_preference)) $privacy_preference = [];
		
		if (!isset($privacy_preference['hide_location'])){
			$CustomSimSettings["required_account_fields"] = str_replace("\nLocation,location,location","",$CustomSimSettings["required_account_fields"]);
		}
		//Convert every line to an array item
		$temp = explode("\n", $CustomSimSettings["required_account_fields"]);
		$required_fields = [];
		//Split each array item into a sub array of options
		foreach($temp as $array){
			$required_fields[] = explode(",",$array);
		}
		
		//Check for children
		$family = get_user_meta($UserID,"family",true);
		//User has children
		if (isset($family["children"])){
			foreach($family["children"] as $key=>$child){
				$userdata = get_userdata($child);
				if ($userdata != null){
					//check if they are missing the field
					foreach ($required_fields as $required_field){
						$hash =  str_replace("\n", "", $required_field[2]);
						$hash = str_replace("\r", "", $hash);
						
						//If this field is also required for children
						if(!isset($required_field[3])){
							//check if field is not set
							if(isset($required_field[1]) and get_user_meta($child, $required_field[1], true ) == ""){
								//required field is not set.
								$first_name = get_userdata($child)->first_name;
								$required_fields[] = [ucfirst($required_field[0]). " for $first_name","","$first_name#$hash"];
							}
						}
					}
				}
			}
		}

		//Check for account picture, only if the privacy settings allow it
		if (empty($privacy_preference['hide_profile_picture'])){
			if(!is_numeric(get_user_meta($UserID,'profile_picture',true))){
				$required_fields[] = ["Profile picture","","Profile%20picture"];
			}
		}
		
		//Build the html with the required fields which are not filled
		$html = "";
		$visa_warning = '';
		foreach ($required_fields as $required_field){
			$display_text = $required_field[0];
			$metakey = $required_field[1];
			$hash =  preg_replace( "/\r|\n/", "", $required_field[2] );
			
			$set = true;
			
			//If we are looking for a specific array index
			if (strpos($metakey, '[') !== false){
				$metakey = explode('[',$metakey)[0];
			}else{
				$metakey = $metakey;
			}
			
			$field_value = get_user_meta($UserID, $metakey, true );
			
			//required field is a visa info field
			if ($metakey == 'visa_info'){
				//Do not make visa fields required if accompanying spouse
				if(isset($field_value['accompanying'])) continue;
				
				//If none of the visa fields are filled only show it once
				if(!is_array($field_value) or count($field_value)==0){
					if($visa_warning == ''){
						$html .= "<li><a href='$profile_page_url#$hash'>Visa information</a></li>";
						$visa_warning = 'done';
					}
					continue;
				}
			}
			
			//Get the array index value
			if (strpos($required_field[1], '[') !== false){
				$field_value = get_meta_array_value($UserID, $required_field[1], $field_value);
			}
			
			if(is_array($field_value)){
				if (count($field_value) == 0){
					$set = false;
				}
			}else{
				if ($field_value == ""){
					$set = false;
				}
			}
			
			//Set is false or it is an empty array
			if($metakey == "" or $set == false){
				if(is_child($UserID)){
					$hash			= "$first_name#$hash";
					$display_text	= "$display_text for $first_name";
				}

				if(strpos($profile_page_url,$_SERVER['REQUEST_URI']) === false){
					$url = "$profile_page_url#$hash";
					$html .= "<li><a href='$url'>$display_text</a></li>";
				}else{
					$html .= "<li><a onclick='change_hash(this)' data-hash='$hash' style='cursor:grabbing'>$display_text</a></li>";
				}
			}
		}
		
		if ($html != ""){
			update_user_meta($UserID,"required_fields_status","");
			//Echo the required fields list
			$html .= "</ul>";
		//If there are no required fields to be filled.
		}else{
			//Set the value to done
			update_user_meta($UserID,"required_fields_status","done");
		}
		
		return $html;
	}
}
		
function get_recommended_fields($user_id){
	$html = get_recommended_fields_html($user_id);
	
	//Check for children
	$family = get_user_meta($user_id,"family",true);
	
	//User has children
	if (isset($family["children"])){
		foreach($family["children"] as $key=>$child){
			$html .= get_recommended_fields_html($child, true);
		}
	}
	
	if($html != '') $html .= '</ul>';
	
	return $html;
}

//Function to return the required fields
function get_recommended_fields_html($user_id, $child = false){
	global $ProfilePage;
	
	$CustomSimSettings 	= get_option("customsimsettings");
	$profile_page_url 	= get_permalink($ProfilePage);
	$html 				= '';
	$local_nigerian 	= get_user_meta( $user_id, 'local_nigerian', true );
	$temp 				= explode("\n", $CustomSimSettings["recommended_fields"]); //Convert every line to an array item
	//Split each array item into a sub array of options
	foreach($temp as $array){
		$recommended_fields[] = explode(",",$array);
	}
	
	//Only show warnings to unlimited accounts of expatriats
	if($local_nigerian == ''){
		//Get the medical fields 
		if($child)	$first_name = get_userdata($user_id)->first_name;
		
		foreach ($recommended_fields as $required_field){
			$display_text = $required_field[0];
			$metakey = trim($required_field[1]);
			$hash =  preg_replace( "/\r|\n/", "", $required_field[2]);

			if($child){
				//Only show if it is meant for kids as well
				if(isset($required_field[3])) continue;
				
				$hash			= "$first_name#$hash";
				$display_text	= "$display_text for $first_name";
			}

			$field_value = get_user_meta($user_id, $metakey, true );
			if(empty($field_value)){
				if(strpos($profile_page_url,$_SERVER['REQUEST_URI']) === false){
					$url = "$profile_page_url#$hash";
					$html .= "<li><a href='$url'>$display_text</a></li>";
				}else{
					$html .= "<li><a onclick='change_hash(this)' data-hash='$hash' style='cursor:grabbing'>$display_text</a></li>";
				}
			}
		}
	}
	
	return $html;
}

//Shortcode for recommended fields
add_shortcode("recommended_fields",function ($atts){
	$UserID = get_current_user_id();
	$html	= '';
	$recommendation_html = get_recommended_fields($UserID);
	if (!empty($recommendation_html)){
		$html .=  '<div id=recommendations style="margin-top:20px;">';
            $html .=  '<h3 class="frontpage">Recommendations</h3>';
            $html .=  '<p>It would be very helpfull if you could fill in the fields below:</p>';
            $html .=  $recommendation_html;
        $html .=  '</div>';
	}
	
	return $html;
});