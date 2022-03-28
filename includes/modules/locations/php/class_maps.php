<?php
namespace SIM\LOCATIONS;
use SIM;

class Maps{
	function __construct(){
		global $wpdb;
		
		$this->map_table	= $wpdb->prefix .'ums_maps';
		$this->marker_table	= $wpdb->prefix .'ums_markers';
		
		//Remove marker when post is sent to trash
		add_action('wp_trash_post',array($this,'remove_post_markers'));
	}
	
	function add_map($name, $lattitude='9.910260', $longitude='8.889170', $address='',$height='400', $zoom=6){
		global $wpdb;
		
		//Add a map
		$params 									= [];
		$params['width_units']						= '%'; 
		$params['membershipEnable']					= 0;
		$params['adapt_map_to_screen_height']		= 0;
		$params['map_type']							= 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}';
		$params['map_center'] 						= Array(
			'address' => $address,
			'coord_x' => $lattitude,
			'coord_y' => $longitude
		);
		$params['mouse_wheel_zoom']					= 1;
		$params['zoom']								= $zoom;
		$params['zoom_mobile']						= $zoom+2;
		$params['zoom_min']							= 1;
		$params['zoom_max']							= 21;
		$params['navigation_bar_mode']				= 'full';
		$params['dbl_click_zoom']					= 1;
		$params['draggable']						= 'enable';
		$params['marker_title_color']				= '#000000';
		$params['marker_title_size']				= 19; 
		$params['marker_title_size_units']			= 'px';
		$params['marker_desc_size']					= 13;
		$params['marker_desc_size_units']			= 'px';
		$params['marker_infownd_width']				= 200;
		$params['marker_infownd_width_units']		= 'px';
		$params['marker_infownd_height']			= 400;
		$params['marker_infownd_height_units']		= 'auto';
		$params['marker_infownd_bg_color']			= '#FFFFFF';
		$params['marker_clasterer']					= 'MarkerClusterer';
		$params['marker_clasterer_grid_size']		= 10;
		$params['marker_clasterer_background_color']= '#bd2919';
		$params['marker_clasterer_border_color']	= '#bc1907';
		$params['marker_clasterer_text_color']		= 'white';
		$params['marker_hover']						= 0;
		$params['markers_list_color']				= '#55BA68';
		
		$html_options = [
			'width'		=> '100',
			'height'	=> $height
		];
		
		//Insert the map in the db
		$wpdb->insert(
			$this->map_table,
			array(
				'title'			=> $name,
				'params'		=> serialize($params),
				'html_options'	=> serialize($html_options),
				'create_date'	=> date("Y-m-d G:i:s")
			)
		);
		
		//return the new map ID
		return $wpdb->insert_id;
	}
	
	function remove_map($map_id){
		global $wpdb;
		
		//remove the map
		$result = $wpdb->delete( $this->map_table, array( 'id' => $map_id ) );
		
		//Remove all markers on this map
		$query = $wpdb->prepare("SELECT id FROM {$this->marker_table} WHERE map_id = %d ", $map_id);
		$markers = $wpdb->get_results($query);
		
		foreach($markers as $marker){
			//Delete the marker
			$this->remove_marker($marker->id);
		}
	}

	function marker_exists($marker_id){
		global $wpdb;
		
		$query	= $wpdb->prepare("SELECT id FROM {$this->marker_table} WHERE id = %d ", $marker_id);
		$result = $wpdb->get_var($query);
		
		if($result == null){
			return false;
		}else{
			return true;
		}
	}
	
	function create_marker($user_id, $location){
		global $wpdb;
		
		if(!empty($location['latitude']) and !empty($location['longitude'])){
			$userdata = get_userdata($user_id);
			
			$privacy_preference = (array)get_user_meta( $user_id, 'privacy_preference', true );
			
			//Do not continue if privacy dictates so
			if($privacy_preference == "show_none") return;
			
			$family = SIM\family_flat_array($user_id);
			if (count($family)>0){
				$title = $userdata->last_name . " family";
			}else{
				$title = $userdata->display_name;
			}
			
			//Add the profile picture to the marker description if it is set, and allowed by privacy
			if (empty($privacy_preference['hide_profile_picture'])){
				$login_name = $userdata->user_login;
				if ( is_numeric(get_user_meta($user_id,'profile_picture',true)) ) {
					$icon_url = SIM\USERMANAGEMENT\get_profile_picture_url($user_id, 'thumbnail');
				}else{
					$icon_url = "";
				}
				
				//Save picture as icon and get the icon id
				$Icon_ID = $this->create_icon('', $login_name, $icon_url, 1);
			}else{
				$Icon_ID = 1;
			}

			//Insert it all in the database
			$wpdb->insert($this->marker_table, array(
				'title'			=> $title,
				'description'	=> "[markerdescription userid='$user_id']",
				'coord_x'		=> $location['latitude'],
				'coord_y'		=> $location['longitude'],
				'icon'			=> $Icon_ID,
				'map_id'		=> SIM\get_module_option('locations', 'missionariesmapid')
			));
			
			//Get the marker id
			$marker_id = $wpdb->insert_id;
			//Add the marker id to the user database
			update_user_meta($user_id,"marker_id",$marker_id);
			
			if (count($family)>0){
				foreach($family as $relative){
					//Update the marker for the relative as well
					update_user_meta($relative,"marker_id",$marker_id);
				}
			}
			
			SIM\print_array("Created the marker with id $marker_id and title $title");
		}
	}

	function update_marker_location($marker_id, $location){
		global $wpdb;
		
		if(is_numeric($marker_id) and is_array($location) and !empty($location['latitude']) and !empty($location['longitude'])){
			//Update the marker
			$wpdb->update($this->marker_table, 
				array(
					'coord_x' => $location['latitude'],
					'coord_y' => $location['longitude'],
				), 
				array( 'ID' => $marker_id)
			);
			SIM\print_array ("Updated the location of marker with id $marker_id");
		}
	}

	function update_marker_title($marker_id, $title){
		global $wpdb;

		if(is_numeric($marker_id)){
			//Update the marker
			$wpdb->update($this->marker_table, 
				array(
					'title' => $title,
				), 
				array( 'ID' => $marker_id),
			);
			SIM\print_array ("Updated the title of marker with id $marker_id to $title");
		}
	}

	function remove_marker($marker_id){
		global $wpdb;
		
		//First delete any custom icon
		$this->remove_icon($marker_id);
		
		$result = $wpdb->delete( $this->marker_table, array( 'id' => $marker_id ) );
		if($result != false){
			SIM\print_array("Removed the marker with id $marker_id");
		}
	}
	
	function remove_personal_marker($user_id){
		global $wpdb;	
		//Check if a previous marker exists for this user_id
		$marker_id = get_user_meta($user_id,"marker_id",true);
		
		//There exists a previous marker for this person, remove it
		if (is_numeric($marker_id)){
			//Delete the personal marker
			$this->remove_marker($marker_id);
			delete_user_meta( $user_id, 'marker_id');
		}
	}

	function remove_post_markers($postid){
		$marker_ids = get_post_meta($postid,"marker_ids",true);

		//Marker exist, remove it
		if (is_array($marker_ids)){
			foreach($marker_ids as $marker_id){
				//Delete the marker
				$this->remove_marker($marker_id);
			}
		}
	}

	function remove_icon($marker_id){
		global $wpdb;
		
		if(is_numeric($marker_id)){
			$query = $wpdb->prepare("SELECT icon FROM {$this->marker_table} WHERE id = %d ", $marker_id);
			$marker_icon_id = $wpdb->get_var($query);
			
			//Icons with id 47 and lower belong to the map plugin
			if ($marker_icon_id != null and $marker_icon_id > 47){
				//Remove the icon
				$wpdb->delete( $wpdb->prefix .'ums_icons', array( 'id' => $marker_icon_id ));
				SIM\print_array("Deleted the personal icon with id $marker_icon_id");
				
				//Reset the marker id to the default icon
				$wpdb->update($this->marker_table, 
					array('icon' => 1,), 
					array( 'id' => $marker_id),
				);
			}elseif($marker_icon_id < 48){
				SIM\print_array("Icon is already the default");
			}else{
				SIM\print_array("Marker id is $marker_id but this marker is not found in the db, marker_icon_id is $marker_icon_id");
			}
		}else{
			SIM\print_array("No marker to remove");
		}
	}

	function create_icon($marker_id, $icon_title, $icon_url, $default_icon_id){
		global $wpdb;

		$icon_query 	= $wpdb->prepare("SELECT * FROM {$wpdb->prefix}ums_icons WHERE title = %s ", $icon_title);
		//Check if marker id is given
		if (is_numeric($marker_id)){
			
			//Get the icon id of this marker
			$query = $wpdb->prepare("SELECT icon FROM {$this->marker_table} WHERE id = %d ", $marker_id);
			$current_marker_icon_id = $wpdb->get_var($query);
			
			//check if marker icon id exists
			if(is_numeric($current_marker_icon_id)){
				$icon_query 	= $wpdb->prepare("SELECT * FROM {$wpdb->prefix}ums_icons WHERE id = %d ", $current_marker_icon_id);
			//Marker not found in the db
			}else{
				$current_marker_icon_id = $default_icon_id;
			}
		}else{
			$current_marker_icon_id = $default_icon_id;
		}
		
		//check if an icon with this title exist
		$icon	 	= $wpdb->get_results($icon_query);

		if(!empty($icon) and $icon[0]->path == $icon_url){
			//no update needed
			return $icon->id;
		}
		
		//Marker icon is still the default and there is no icon with this id
		//Potentially the profile image of the partner is used
		if($current_marker_icon_id == $default_icon_id and empty($icon)){
			//Create new icon if there is an icon url
			if ( $icon_url != "") {
				//Insert picture as icon in the database
				$wpdb->insert($wpdb->prefix . 'ums_icons', array(
					'title' => $icon_title,
					'description' => 'custom icon',
					'path' => $icon_url,
					'width' => '44',
					'height' => '44',
					'is_def' => '0'
				));
				
				//Get the new icon ID
				$icon_id = $wpdb->insert_id;
				
				//Update the marker to use the new icon
				$updated = $wpdb->update($this->marker_table, 
					array( 'icon' => $icon_id, ), 
					array( 'id' => $marker_id),
				);
				SIM\print_array("Created new icon with title $icon_title");
			}else{
				//No icon url, use default icon
				$icon_id = $default_icon_id;
			}
		//Only update if there is an icon url
		}elseif(!empty($icon_url)){
			//Update marker icon
			$wpdb->update($wpdb->prefix . 'ums_icons', 
				array(
					'path' 	=> $icon_url,
					'title' => $icon_title,
				), 
				array( 'id' => $icon->id),
			);
			SIM\print_array("Updated icon with title $icon_title and id $icon->id");
			
			//Reset the marker id to the custom icon
			if (is_numeric($marker_id)){
				$wpdb->update(
					$this->marker_table, 
					array('icon' => $icon->id,), 
					array( 'id' => $marker_id),
				);
			}

			$icon_id	= $icon->id;
		}else{
			$icon_id = $current_marker_icon_id;
		}

		return $icon_id;
	}
}

$Maps = new Maps();




