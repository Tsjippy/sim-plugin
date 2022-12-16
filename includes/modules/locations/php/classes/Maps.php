<?php
namespace SIM\LOCATIONS;
use SIM;

class Maps{
	public function __construct(){
		global $wpdb;

		$this->mapTable		= $wpdb->prefix .'ums_maps';
		$this->markerTable	= $wpdb->prefix .'ums_markers';
	}

	/**
	 * Function to retrive all maps from the db
	 *
	 * @return	array		array of map objects
	 */
	public function getMaps($where=1){
		global $wpdb;

		$query = "SELECT  `id`,`title` FROM `$this->mapTable` WHERE $where ORDER BY `title` ASC";
		return $wpdb->get_results($query);
	}

	/**
	 *
	 * Add a new map to the db
	 *
	 * @param	string	$name		The map name
	 * @param	string	$lattitude	The lattitude of the map center
	 * @param	string	$longitude	The longitude of the map center
	 * @param	string	$address	The address of the map
	 * @param	int		$height		The height of the map. Default 400px.
	 * @param	int		$zoom		The zoom level of the map. Default 6
	 *
	 * @return	int					The new map id
	 */
	public function addMap($name, $lattitude='9.910260', $longitude='8.889170', $address='', $height='400', $zoom=6){
		global $wpdb;

		//Add a map
		$params 									= [];
		$params['width_units']						= '%';
		$params['membershipEnable']					= 0;
		$params['adapt_map_to_screen_height']		= 0;
		$params['map_type']							= 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}';
		$params['map_center'] 						= array(
			'address' => $address,
			'coord_x' => $lattitude,
			'coord_y' => $longitude
		);
		$params['mouse_wheel_zoom']					= 1;
		$params['zoom']								= $zoom;
		$params['zoom_mobile']						= $zoom + 2;
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

		$htmlOptions = [
			'width'		=> '100',
			'height'	=> $height
		];

		//Insert the map in the db
		$wpdb->insert(
			$this->mapTable,
			array(
				'title'			=> $name,
				'params'		=> serialize($params),
				'html_options'	=> serialize($htmlOptions),
				'create_date'	=> date("Y-m-d G:i:s")
			)
		);

		//return the new map ID
		return $wpdb->insert_id;
	}

	/**
	 *
	 * Remove an existing map
	 *
	 * @param	int	$mapId		The id of the map to be removed
	 *
	 */
	public function removeMap($mapId){
		global $wpdb;

		//remove the map
		$wpdb->delete( $this->mapTable, array( 'id' => $mapId ) );

		//Remove all markers on this map
		$query 		= $wpdb->prepare("SELECT id FROM {$this->markerTable} WHERE map_id = %d ", $mapId);
		$markers 	= $wpdb->get_results($query);

		foreach($markers as $marker){
			//Delete the marker
			$this->removeMarker($marker->id);
		}
	}

	/**
	 * Checks is a given marker id exists
	 *
	 * @param	int		$markerId The marker id to check
	 *
	 * @return	bool				True if exists false otherwise
	 */
	public function markerExists($markerId){
		global $wpdb;

		$query	= $wpdb->prepare("SELECT id FROM {$this->markerTable} WHERE id = %d ", $markerId);
		$result = $wpdb->get_var($query);

		if($result == null){
			return false;
		}

		return true;
	}

	public function checkCoordinates($lattitude, $longitude){
		if(strlen($lattitude) < 3 || strlen($longitude) < 3 || strpos($lattitude, '.') === false || strpos($longitude, '.') === false){
			return new \WP_Error('maps', 'Please give valid coordinates');
		}

		return true;
	}

	/**
	 * Creates a marker for a given user
	 *
	 * @param	int		$userId		WP_User id
	 * @param	array	$location	array containing lat and lon
	 *
	 */
	public function createUserMarker($userId, $location){
		global $wpdb;

		if(!empty($location['latitude']) && !empty($location['longitude'])){
			$check	= $this->checkCoordinates($location['latitude'], $location['longitude']);
			if(is_wp_error($check)){
				return $check;
			}

			$userdata = get_userdata($userId);

			$privacyPreference = (array)get_user_meta( $userId, 'privacy_preference', true );

			//Do not continue if privacy dictates so
			if($privacyPreference == "show_none"){
				return;
			}

			$family = SIM\familyFlatArray($userId);
			$title	= SIM\findFamilyName($userId);

			//Add the profile picture to the marker description if it is set, and allowed by privacy
			if (empty($privacyPreference['hide_profile_picture'])){
				$loginName = $userdata->user_login;
				if ( is_numeric(get_user_meta($userId,'profile_picture',true)) && function_exists('SIM\USERMANAGEMENT\getProfilePictureUrl')) {
					$iconUrl = SIM\USERMANAGEMENT\getProfilePictureUrl($userId, 'thumbnail');
				}else{
					$iconUrl = "";
				}

				//Save picture as icon and get the icon id
				$iconId = $this->createIcon('', $loginName, $iconUrl, 1);
			}else{
				$iconId = 1;
			}

			//Insert it all in the database
			$wpdb->insert($this->markerTable, array(
				'title'			=> $title,
				'description'	=> "[markerdescription userid='$userId']",
				'coord_x'		=> $location['latitude'],
				'coord_y'		=> $location['longitude'],
				'icon'			=> $iconId,
				'map_id'		=> SIM\getModuleOption(MODULE_SLUG, 'users_map_id')
			));

			//Get the marker id
			$markerId = $wpdb->insert_id;
			//Add the marker id to the user database
			update_user_meta($userId, "marker_id", $markerId);

			if (count($family)>0){
				foreach($family as $relative){
					//Update the marker for the relative as well
					update_user_meta($relative, "marker_id", $markerId);
				}
			}
		}
	}

	/**
	 * Updates the location of a given marker id
	 *
	 * @param 	int		$markerId	the id of th emarker to update
	 * @param	array	$location	array containing the lat and lon
	 */
	public function updateMarkerLocation($markerId, $location){
		global $wpdb;

		if(is_numeric($markerId) && is_array($location) && !empty($location['latitude']) && !empty($location['longitude'])){
			$check	= $this->checkCoordinates($location['latitude'], $location['longitude']);
			if(is_wp_error($check)){
				return $check;
			}

			//Update the marker
			$wpdb->update($this->markerTable,
				array(
					'coord_x' => $location['latitude'],
					'coord_y' => $location['longitude'],
				),
				array( 'ID' => $markerId)
			);
		}
	}

	/**
	 * Updates the title of a given marker id
	 *
	 * @param 	int		$markerId	the id of th emarker to update
	 * @param	string	$title		the new marker title
	 */
	public function updateMarkerTitle($markerId, $title){
		global $wpdb;

		if(is_numeric($markerId)){
			//Update the marker
			$wpdb->update($this->markerTable,
				array(
					'title' => $title,
				),
				array( 'ID' => $markerId),
			);
		}
	}

	/**
	 * Remove a marker
	 *
	 * @param	int		$markerId	The marker id of the marker to be removed
	 */
	public function removeMarker($markerId){
		global $wpdb;

		//First delete any custom icon
		$this->removeIcon($markerId);

		$result = $wpdb->delete( $this->markerTable, array( 'id' => $markerId ) );
		if($result){
			SIM\printArray("Removed the marker with id $markerId");
		}
	}

	/**
	 * Remove the marker belonging to an user
	 *
	 * @param	int		$userId		the user id
	 */
	public function removePersonalMarker($userId){
		//Check if a previous marker exists for this user_id
		$markerId = get_user_meta($userId, "marker_id", true);

		//There exists a previous marker for this person, remove it
		if (is_numeric($markerId)){
			//Delete the personal marker
			$this->removeMarker($markerId);
			delete_user_meta( $userId, 'marker_id');
		}
	}

	/**
	 * Remove all markers belonging to a location
	 *
	 * @param	int		$postId		The id of the WP_Post
	 */
	public function removePostMarkers($postId){
		$markerIds = get_post_meta($postId, "marker_ids", true);

		//Marker exist, remove it
		if (is_array($markerIds)){
			foreach($markerIds as $markerId){
				//Delete the marker
				$this->removeMarker($markerId);
			}
		}
	}

	/**
	 * Remove the icon of a marker
	 *
	 * @param	int		$markerId	the id of the marker the icon belongs to
	 */
	public function removeIcon($markerId){
		global $wpdb;

		if(!is_numeric($markerId)){
			SIM\printArray("No marker to remove");
			return;
		}

		$query 			= $wpdb->prepare("SELECT icon FROM {$this->markerTable} WHERE id = %d ", $markerId);
		$markerIconId 	= $wpdb->get_var($query);

		if(!is_numeric($markerIconId)){
			SIM\printArray("Marker id is $markerId but this marker is not found in the db, marker_icon_id is $markerIconId");

			return;
		}

		//Icons with id 47 and lower belong to the map plugin
		if ($markerIconId > 47){
			//Remove the icon
			$wpdb->delete( $wpdb->prefix .'ums_icons', array( 'id' => $markerIconId ));

			//Reset the marker id to the default icon
			$wpdb->update($this->markerTable,
				array('icon' => 1,),
				array( 'id' => $markerId),
			);
		}else{
			SIM\printArray("Icon is already the default");
		}
	}

	/**
	 * Create an icon for a marker
	 *
	 * @param	int		$markerId	The marker id the icon should be added to
	 * @param	string	$title		The title for the icon
	 * @param	string	$url		The url for the icon image
	 * @param	int		$defaultId	The id of the default icon
	 *
	 * @return	int					The id of the current or created marker
	 */
	public function createIcon($markerId, $title, $url, $defaultId){
		global $wpdb;

		$currentMarkerIconId 	= $defaultId;
		$iconQuery 				= $wpdb->prepare("SELECT * FROM {$wpdb->prefix}ums_icons WHERE title = %s ", $title);
		//Check if marker id is given
		if (is_numeric($markerId)){

			//Get the icon id of this marker
			$query 					= $wpdb->prepare("SELECT icon FROM {$this->markerTable} WHERE id = %d ", $markerId);
			$currentMarkerIconId 	= $wpdb->get_var($query);

			//check if marker icon id exists
			if(is_numeric($currentMarkerIconId)){
				$iconQuery 	= $wpdb->prepare("SELECT * FROM {$wpdb->prefix}ums_icons WHERE id = %d ", $currentMarkerIconId);
			}else{
				$currentMarkerIconId 	= $defaultId;
			}
		}

		//check if an icon exists
		$icon	 	= $wpdb->get_results($iconQuery);

		if(!empty($icon)){
			$icon	= $icon[0];
		}

		if($icon->path == $url){
			//no update needed
			return $icon->id;
		}

		//Marker icon is still the default and there is no icon with this id
		//Potentially the profile image of the partner is used
		if($currentMarkerIconId == $defaultId){
			//Create new icon if there is an icon url
			if ( !empty($url)) {
				//Insert picture as icon in the database
				$wpdb->insert($wpdb->prefix . 'ums_icons', array(
					'title'			=> $title,
					'description'	=> 'custom icon',
					'path' 			=> $url,
					'width' 		=> '44',
					'height' 		=> '44',
					'is_def' 		=> '0'
				));

				//Get the new icon ID
				$iconId = $wpdb->insert_id;

				//Update the marker to use the new icon
				$wpdb->update($this->markerTable,
					array( 'icon' 	=> $iconId, ),
					array( 'id' 	=> $markerId),
				);
			}else{
				//No icon url, use default icon
				$iconId = $defaultId;
			}
		//Only update if there is an icon url
		}elseif(!empty($url)){
			//Update marker icon
			$wpdb->update($wpdb->prefix . 'ums_icons',
				array(
					'path' 	=> $url,
					'title' => $title,
				),
				array( 'id' => $icon->id),
			);

			//Reset the marker id to the custom icon
			if (is_numeric($markerId)){
				$wpdb->update(
					$this->markerTable,
					array('icon' 	=> $icon->id,),
					array( 'id' 	=> $markerId),
				);
			}

			$iconId	= $icon->id;
		}else{
			$iconId = $currentMarkerIconId;
		}

		return $iconId;
	}
}
