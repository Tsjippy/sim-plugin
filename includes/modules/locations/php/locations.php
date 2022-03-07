<?php
namespace SIM\LOCATIONS;
use SIM;

/*
	In this file we define a new post type: location
	We also define a new taxonomy (category): locationtype
	We make sure post of this type get an url according to their taxonomy
*/

add_action('init', function(){
	SIM\register_post_type_and_tax('location', 'locations');
}, 999);


//Add location type via AJAX
add_action('wp_ajax_add_location_type',function(){
	global $CustomSimSettings;
	global $Maps;
	
	SIM\verify_nonce('add_location_type_nonce');
	
	$name		= ucfirst(sanitize_text_field($_POST['location_type_name']));
	
	$args		= ['slug' => strtolower($name)];
	
	$parent		= $_POST['location_type_parent'];
	if(is_numeric($parent)) $args['parent'] = $parent;
	
	$result		= wp_insert_term( $name, 'locationtype',$args);
	
	//Something went wront
	if(is_wp_error($result)){
		wp_die($result->get_error_message(),500);
	//Succes
	}else{
		$map_id		= $Maps->add_map($name);
		$term_id	= $result['term_id'];
		SIM\print_array("Created locationtype category with name $name and id $term_id, created a new map with id $map_id");
		
		//Attach the map id to the term
		update_term_meta($term_id,'map_id',$map_id);
		
		//Update the admin options
		$CustomSimSettings[strtolower($name).'_map']	= $map_id;
		update_option("customsimsettings",$CustomSimSettings);
		
		//Return the id and  message
		wp_die(json_encode(
			[
				'id'		=> $term_id,
				'name'		=> $name,
				'type'		=> 'location',
				'message'	=> "Added $name succesfully as location category",
				'callback'	=> 'cat_type_added'
			]
		));
	}
});

add_filter(
	'widget_categories_args',
	function ( $cat_args, $instance  ) {
		global $MinistryCategoryID;
		//if we are on a locationtype page, change to display the location types
		if(is_tax('locationtype') or is_page('location') or get_post_type()=='location'){
			$cat_args['taxonomy'] 		= 'locationtype';
			$cat_args['hierarchical']	= true;
			$cat_args['hide_empty'] 	= false;
			
			if(!is_user_logged_in()){
				$cat_args['parent'] 	= $MinistryCategoryID; //only show subcategories of the ministries if not logged in
			}
		}
		
		return $cat_args;
	},
	10, 
	2 
);

add_filter('widget_title', function ($title, $widget_id=null){
	//Change the title of the location category widget if not logged in
	if(is_tax('locationtype') and $widget_id == 'categories' and !is_user_logged_in()){
		$url = get_site_url().'/locations/ministry/';
		return "<a href='$url'>Ministries</a>";
	}
    return $title;
}, 999, 2);

	//Add to frontend form
add_action('frontend_post_modal', 'SIM\add_location_modal');
add_action('frontend_post_before_content','SIM\show_location_categories');
add_action('frontend_post_after_content','SIM\location_specific_fields',10,2);
add_action('frontend_post_content_title','SIM\location_title');
add_action('sim_after_post_save','SIM\save_location_meta',10,2);
	
function add_location_modal(){
	global $FrontEndContent;
	$FrontEndContent->add_modal('location');
}

function show_location_categories(){
	global $FrontEndContent;
	
	$categories = get_categories( array(
		'orderby' 	=> 'name',
		'order'   	=> 'ASC',
		'taxonomy'	=> 'locationtype',
		'hide_empty'=> false,
	) );
	
	$FrontEndContent->show_categories('location', $categories);
}

function location_title($post_type){
	//Location content title
	$class = 'location';
	if($post_type != 'location')	$class .= ' hidden';
	
	echo "<h4 class='$class' name='location_content_label'>";
		echo 'Please describe the location';
	echo "</h4>";
}

function save_location_meta($post, $post_type){
	//store locationtype
	$locationtypes = [];
	if(is_array($_POST['locationtype'])){
		foreach($_POST['locationtype'] as $key=>$locationtype) {
			if($locationtype != '') $locationtypes[] = $locationtype;
		}
		
		//Store types
		$locationtypes = array_map( 'intval', $locationtypes );
		
		wp_set_post_terms($post->ID,$locationtypes,'locationtype');
	}
	
	//tel
	if(isset($_POST['tel'])){
		//Store serves
		update_post_meta($post->ID,'tel',$_POST['tel']);
	}
	
	//url
	if(isset($_POST['url'])){
		//Store serves
		update_post_meta($post->ID,'url',$_POST['url']);
	}
	
	location_address($locationtypes, $post->ID);
}

function location_address($locationtypes, $post_id){
	global $wpdb;
	global $Maps;
	
	if(
		isset($_POST['location'])				and
		isset($_POST['location']['latitude'])	and
		isset($_POST['location']['longitude'])
	){
		$title			= sanitize_text_field($_POST['post_title']);
		$old_location 	= get_post_meta($post_id,'location',true);
		$new_location	= $_POST['location'];
		
		$address	= $new_location["address"]		= sanitize_text_field($new_location["address"]);
		$latitude	= $new_location["latitude"]		= sanitize_text_field($new_location["latitude"]);
		$longitude	= $new_location["longitude"]	= sanitize_text_field($new_location["longitude"]);
		
		$map_id		= get_post_meta($post_id,'map_id',true);

		//Get marker array
		$marker_ids = get_post_meta($post_id,"marker_ids",true);
		if(!is_array($marker_ids)) $marker_ids = [];
		
		//Only update if needed
		if($old_location != $new_location and $latitude != '' and $longitude != ''){
			update_post_meta($post_id,'location',$new_location);

			//Add the profile picture to the marker content
			$post_thumbnail = get_the_post_thumbnail($post_id, 'thumbnail', array( 'class' => 'aligncenter markerpicture' , 'style' => 'max-height:100px;',));
			
			//Add a directions button to the marker content
			$directions_form = "<p><a class='button' onclick='getRoute(this,$latitude,$longitude)'>Get directions</a></p>";
			
			//Add the post excerpt to the marker content
			$description = $post_thumbnail.wp_trim_words(wp_trim_excerpt("",$post_id),25);
			//Add the post link to the marker content
			$url = get_permalink($post_id);
			$description .= '<a href="'.$url.'" style="display:block;" class="page_link">Show full descripion</a><br>'.$directions_form;
			
			//Get url of the featured image
			$icon_url = get_the_post_thumbnail_url($post_id);
			
			//Get the first category name
			$name = get_term( $locationtypes[0], 'locationtype' )->slug.'_icon';
			//If there is a location category set and an custom icon for this category is set
			if(count($locationtypes)>0 and !empty($CustomSimSettings[$name])){
				$icon_id = $CustomSimSettings[$name];
			}else{
				$icon_id = 1;
			}
				
			/* 		
				GENERIC MARKER
			*/	
			//Update generic marker
			if(isset($marker_ids['generic'])){
				//Generic map, always update
				$result = $wpdb->update($wpdb->prefix . 'ums_markers', 
					array(
						'description'	=> $description,
						'coord_x'		=> $latitude,
						'coord_y'		=> $longitude,
						'address'		=> $address,
					), 
					array( 'ID'			=> $marker_ids['generic']),
				);
				
				//Failed
				if($result == false){
					//Check if marker exist, if not delete the metakey
					if(!$Maps->marker_exists($marker_ids['generic'])){
						SIM\print_array("Updating marker with id {$marker_ids['generic']} failed as it does not exist");
						unset($marker_ids['generic']);
					}
				}else{
					SIM\print_array("Updating marker with id {$marker_ids['generic']} was succesfull");
				}
			}
			
			//Marker does not exist, create it
			if(!isset($marker_ids['generic'])){			
				$map_id	=  SIM\get_module_option('locations', 'placesmapid');		
				//First create the marker on the generic map
				$wpdb->insert($wpdb->prefix . 'ums_markers', array(
					'title' 		=> $title,
					'description'	=> $description,
					'coord_x'		=> $latitude,
					'coord_y'		=> $longitude,
					'icon'			=> $icon_id,
					'map_id'		=> $map_id,		//Generic map with all places
					'address'		=> $address,
				));
				
				//Get the marker id
				$marker_ids['generic'] = $wpdb->insert_id;
				SIM\print_array("Created marker with id {$wpdb->insert_id} and title $title om map with id $map_id");
				
			}
			
			/* 
				Specifc map
			*/
			 
			$map_id = get_post_meta($post_id,'map_id',true);
			
			//First try to update
			if(isset($marker_ids['page_marker'])){
				//Create an icon for this marker
				$custom_icon_id = $Maps->create_icon($marker_id=$marker_ids['page_marker'], $icon_title=$title, $icon_url, $default_icon_id=$icon_id);
				
					
				$result = $wpdb->update($wpdb->prefix . 'ums_markers', 
					array(
						'title' 		=> $title,
						'description'	=> $post_thumbnail.$directions_form,
						'coord_x'		=> $latitude,
						'coord_y'		=> $longitude,
						'address'		=> $address,
					), 
					array( 'ID'			=> $marker_ids['page_marker']),
				);
				
				//Failed
				if($result == false){
					//Check if marker exist, if not delete the metakey
					if(!$Maps->marker_exists($marker_ids['page_marker'])){
						SIM\print_array("Updating marker with id {$marker_ids['page_marker']} failed as it does not exist");
						unset($marker_ids['page_marker']);
					}
				}else{
					SIM\print_array("Updated marker with id {$marker_ids['page_marker']} and title $title on map with id $map_id");
				}
			}
			
			//Create if it does not exist anymore
			if(!isset($marker_ids['page_marker'])){
				
				if(!is_numeric($map_id)){
					//Create a custom map for this location
					$map_id = $Maps->add_map($title, $latitude, $longitude, $address,$height='300',$zoom=10);
					
					//Save the map id in db
					update_post_meta($post_id,'map_id',$map_id);
				}					
				
				//Create an icon for this marker
				$custom_icon_id = $Maps->create_icon($marker_id=null, $icon_title=$title, $icon_url, $default_icon_id=$icon_id);
				
				//Add the marker to this map
				$wpdb->insert($wpdb->prefix . 'ums_markers', array(
					'title' 		=> $title,
					'description'	=> $post_thumbnail.$directions_form,
					'coord_x'		=> $latitude,
					'coord_y'		=> $longitude,
					'icon' 			=> $custom_icon_id,
					'map_id'		=> $map_id,
					'address'		=> $address,
				));
				$marker_ids['page_marker'] = $wpdb->insert_id;
				
				SIM\print_array("Created marker with id {$wpdb->insert_id} and title $title on map with id $map_id");
			}
			
			/* 
				Category maps
			*/
			$categories = get_categories( array(
				'orderby' 	=> 'name',
				'order'   	=> 'ASC',
				'taxonomy'	=> 'locationtype',
				'hide_empty'=> false,
			) );		
		
			//loop over all available the categories
			foreach($categories as $locationtype){
				//If the current cat is set for this post
				if(in_array($locationtype->cat_ID, $locationtypes)){
					$name 				= $locationtype->slug;
					$map_name			= $name."_map";
					$map_id				= SIM\get_module_option('locations', $map_name);
					$icon_name			= $name."_icon";
					$icon_id			= SIM\get_module_option('locations', $icon_name);
					
					//Checking if this marker exists
					if(is_numeric($marker_ids[$name])){
						//Create an icon for this marker
						$custom_icon_id = $Maps->create_icon($marker_id=$marker_ids[$name], $icon_title=$title, $icon_url, $default_icon_id=$icon_id);
						
						//Update the marker in db
						$result = $wpdb->update($wpdb->prefix . 'ums_markers', 
							array(
								'description'	=> $description,
								'coord_x'		=> $latitude,
								'coord_y'		=> $longitude,
								'map_id'		=> $map_id,
								'address'		=> $address,
							), 
							array( 'ID' => $marker_ids[$name]),
						);
						
						//Failed
						if($result == false){
							//Check if marker exist, if not delete the metakey
							if(!$Maps->marker_exists($marker_ids[$name])){
								SIM\print_array("Updating marker with id {$marker_ids[$name]} failed as it does not exist");
								unset($marker_ids[$name]);
							}
						}else{
							SIM\print_array("Updated marker with id {$marker_ids[$name]} and title $title on map with id $map_id");
						}
					}
					
					if(!is_numeric($marker_ids[$name])){
						//Create an icon for this marker
						$custom_icon_id = $Maps->create_icon($marker_id=null, $icon_title=$title, $icon_url, $default_icon_id=$icon_id);
						
						//Add marker for this map
						$wpdb->insert($wpdb->prefix . 'ums_markers', array(
							'title' 		=> $title,
							'description'	=> $description,
							'coord_x'		=> $latitude,
							'coord_y'		=> $longitude,
							'icon' 			=> $custom_icon_id,
							'map_id'		=> $map_id,
							'address'		=> $address,
						));
						
						//Get the marker id
						$marker_ids[$name] = $wpdb->insert_id;
						
						SIM\print_array("Created marker with id {$wpdb->insert_id} and title $title on map with id $map_id");
					}
				}
			}
			
			//Store marker ids in db
			update_post_meta($post_id,"marker_ids",$marker_ids);
		}elseif($latitude == '' and $longitude == '' and is_numeric($map_id)){
			//Delete the custom map for this post
			delete_post_meta($post_id,'map_id');
			$Maps->remove_map($map_id);
			
			//Remove all markers related to this post
			foreach($marker_ids as $marker_id){
				$Maps->remove_marker($marker_id);
			}
			
			//Store marker ids in db
			delete_post_meta($post_id,"marker_ids");
		}
	}
}

//add meta data fields
function location_specific_fields($post_name, $post_id){
	//Load js
	wp_enqueue_script('sim_location_script');
	
	$location = get_post_meta($post_id, 'location', true);
	
	if(isset($location['address'])){
		$address = $location['address'];
	}elseif(get_post_meta($post_id, 'geo_address', true) != ''){
		$address = get_post_meta($post_id, 'geo_address', true);
	}
	
	if(isset($location['latitude'])){
		$latitude = $location['latitude'];
	}elseif(get_post_meta($post_id, 'geo_latitude', true) != ''){
		$latitude = get_post_meta($post_id, 'geo_latitude', true);
	}
	
	if(isset($location['longitude'])){
		$longitude = $location['longitude'];
	}elseif(get_post_meta($post_id, 'geo_longitude', true) != ''){
		$longitude = get_post_meta($post_id, 'geo_longitude', true);
	}
	
	$url = get_post_meta($post_id,'url',true);
	if($url == '') $url = 'https://www.';
	
	?>
	<style>
		.form-table, .form-table th, .form-table, td{
			border: none;
		}
		.form-table{
			text-align: left;
		}
	</style>
	<div id="location-attributes" class="location<?php if($post_name != 'location') echo ' hidden'; ?>">
		<fieldset id="location" class="frontendform">
			<legend>
				<h4>Location details</h4>
			</legend>					
		
			<table class="form-table">
				<tr>
					<th><label for="tel">Phone number</label></th>
					<td>
						<input type='tel' class='formbuilder' name='tel' value='<?php echo get_post_meta($post_id,'tel',true); ?>'>
					</td>
				</tr>
				<tr>
					<th><label for="url">Website</label></th>
					<td>
						<input type='url' class='formbuilder' name='url' value='<?php echo $url; ?>'>
					</td>
				</tr>
				<tr>
					<th><label for="address">Address</label></th>
					<td>
						<input type="text" class='formbuilder address' name="location[address]" value="<?php echo $address; ?>">
						<span class="description">Will be filled based on the coordinates</span>
					</td>
				</tr>
				<tr>
					<th><label for="latitude">Latitude</label></th>
					<td>
						<input type="text" class='formbuilder latitude' name="location[latitude]" value="<?php echo $latitude; ?>">
					</td>
				</tr>
				<tr>
					<th><label for="longitude">Longitude</label></th>
					<td>
						<input type="text" class='formbuilder longitude' name="location[longitude]" value="<?php echo $longitude; ?>">
					</td>
				</tr>
			</table> 
		</fieldset>
	</div>
	<?php
}

//Default content for ministry pages
function ministry_description($post_id){
	$html = "";
	//Get the post id of the page the shortcode is on
	$ministry = get_the_title($post_id);
	$ministry_name = str_replace(" ","_",$ministry);
	$ministry = strtolower($ministry);
	$args = array(
		'post_parent' => $post_id, // The parent id.
		'post_type'   => 'page',
		'post_status' => 'publish',
		'order'          => 'ASC',
	);
	$child_pages = get_children( $args, ARRAY_A);
	$child_page_html = "";
	if ($child_pages  != false){
		$child_page_html .= "<p><strong>Some of our $ministry are:</strong></p><ul>";
		foreach($child_pages as $child_page){
			$child_page_html .= '<li><a href="'.$child_page['guid'].'">'.$child_page['post_title']."</a></li>";
		}
		$child_page_html .= "</ul>";
	}		
	
	if (is_user_logged_in()){
		//Loop over all users to see if they work here
		$users = get_users('orderby=display_name');
		
		$original_html = "<p><strong>People working at $ministry are:</strong><br><br>";
		$html = $original_html;
		foreach($users as $user){
			$user_ministries = (array)get_user_meta( $user->ID, "user_ministries", true);
		
			//If user works for this ministry, echo its name and position
			if (isset($user_ministries[$ministry_name])){
				$user_page_url		= SIM\USERPAGE\get_user_page_url($user->ID);
				$privacy_preference = (array)get_user_meta( $user->ID, 'privacy_preference', true );
				
				if(!isset($privacy_preference['hide_ministry'])){
					if(!isset($privacy_preference['hide_profile_picture'])){
						$html .= SIM\USERMANAGEMENT\display_profile_picture($user->ID);
						$style = "";
					}else{
						$style = ' style="margin-left: 55px; padding-top: 30px; display: block;"';
					}
					
					$page_url = "<a class='missionary_link' href='$user_page_url'>$user->display_name</a>";
					
					$html .= "   <div $style>$page_url ({$user_ministries[$ministry_name]})</div>";
					$html .= '<br>';
				}					
			}
		}
		if($html == $original_html){
			//Check if page has children
			if ($child_pages == false){
				//No children add an message
				$html .= "No one dares to say they are working here!";
			}else{
				$html = "";
			}
		}
		$html .= '</p>';
		
		$latitude = get_post_meta($post_id,'geo_latitude',true);
		$longitude = get_post_meta($post_id,'geo_longitude',true);
		if ($latitude != "" and $longitude != ""){
			$html .= "<p><a class='button' onclick='getRoute(this,$latitude,$longitude)'>Get directions to $ministry</a></p>";
		}
	}
	if($child_page_html != ""){
		$html = $child_page_html."<br><br>".$html; 
	}
	return $html;	
}
