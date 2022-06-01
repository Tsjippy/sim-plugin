<?php
namespace SIM\LOCATIONS;
use SIM;
    
add_filter('sim_frontend_posting_modals', function($types){
    $types[]	= 'location';
    return $types;
});

add_action('sim_frontend_post_before_content', function($frontEndContent){
    $categories = get_categories( array(
        'orderby' 	=> 'name',
        'order'   	=> 'ASC',
        'taxonomy'	=> 'locations',
        'hide_empty'=> false,
    ) );
    
    $frontEndContent->showCategories('location', $categories);
});

add_action('sim_frontend_post_content_title', function ($postType){
    //Location content title
    $class = 'location';
    if($postType != 'location')	$class .= ' hidden';
    
    echo "<h4 class='$class' name='location_content_label'>";
        echo 'Please describe the location';
    echo "</h4>";
});

add_action('sim_after_post_save', function($post){
    //store locations
    $locationTypes = [];
    if(is_array($_POST['locationtype'])){
        foreach($_POST['locationtype'] as $key=>$locationType) {
            if($locationType != '') $locationTypes[] = $locationType;
        }
        
        //Store types
        $locationTypes = array_map( 'intval', $locationTypes );
        
        wp_set_post_terms($post->ID, $locationTypes, 'locations');
    }
    
    //tel
    if(isset($_POST['tel'])){
        //Store serves
        update_metadata( 'post', $post->ID, 'tel', $_POST['tel']);
    }
    
    //url
    if(isset($_POST['url'])){
        //Store serves
        update_metadata( 'post', $post->ID, 'url', $_POST['url']);
    }
    
    locationAddress($locationTypes, $post->ID);
});

add_action('sim_ministry_added', __NAMESPACE__.'\locationAddress', 10, 2);
function locationAddress($locationTypes, $postId){
    global $wpdb;

    $maps   = new Maps();
    
    if(
        isset($_POST['location'])				and
        isset($_POST['location']['latitude'])	and
        isset($_POST['location']['longitude'])
    ){
        $title			= sanitize_text_field($_POST['post_title']);
        $oldLocation 	= get_post_meta($postId,'location',true);
        $newLocation	= $_POST['location'];
        
        $address	= $newLocation["address"]		= sanitize_text_field($newLocation["address"]);
        $latitude	= $newLocation["latitude"]		= sanitize_text_field($newLocation["latitude"]);
        $longitude	= $newLocation["longitude"]	    = sanitize_text_field($newLocation["longitude"]);
        
        $mapId		= get_post_meta($postId,'map_id',true);

        //Get marker array
        $markerIds = get_post_meta($postId,"marker_ids",true);
        if(!is_array($markerIds)) $markerIds = [];
        
        //Only update if needed
        if($oldLocation != $newLocation and $latitude != '' and $longitude != ''){
            update_metadata( 'post', $postId, 'location', $newLocation);

            //Add the profile picture to the marker content
            $postThumbnail = get_the_post_thumbnail($postId, 'thumbnail', array( 'class' => 'aligncenter markerpicture' , 'style' => 'max-height:100px;',));
            
            //Add a directions button to the marker content
            $directionsForm = "<p><a class='button' onclick='zgetRoute(this,$latitude,$longitude)'>Get directions</a></p>";
            
            //Add the post excerpt to the marker content
            $description    = $postThumbnail.wp_trim_words(wp_trim_excerpt("", $postId), 25);
            //Add the post link to the marker content
            $url            = get_permalink($postId);
            $description    .= "<a href='$url' style='display:block;' class='page_link'>Show full descripion</a><br>$directionsForm";
            
            //Get url of the featured image
            $iconUrl        = get_the_post_thumbnail_url($postId);
            
            //Get the first category name
            $name = get_term( $locationTypes[0], 'locations' )->slug.'_icon';
            
            //If there is a location category set and an custom icon for this category is set
            if(count($locationTypes) > 0 and !empty(SIM\getModuleOption('locations', $name))){
                $iconId = SIM\getModuleOption('locations', $name);
            }else{
                $iconId = 1;
            }
                
            /* 		
                GENERIC MARKER
            */	
            //Update generic marker
            if(isset($markerIds['generic'])){
                //Generic map, always update
                $result = $wpdb->update($wpdb->prefix . 'ums_markers', 
                    array(
                        'description'	=> $description,
                        'coord_x'		=> $latitude,
                        'coord_y'		=> $longitude,
                        'address'		=> $address,
                    ), 
                    array( 'ID'			=> $markerIds['generic']),
                );
                
                //Failed
                if($result == false){
                    //Check if marker exist, if not delete the metakey
                    if(!$maps->markerExists($markerIds['generic'])){
                        unset($markerIds['generic']);
                    }
                }
            }
            
            //Marker does not exist, create it
            if(!isset($markerIds['generic'])){			
                $mapId	=  SIM\getModuleOption('locations', 'placesmapid');		
                //First create the marker on the generic map
                $wpdb->insert($wpdb->prefix . 'ums_markers', array(
                    'title' 		=> $title,
                    'description'	=> $description,
                    'coord_x'		=> $latitude,
                    'coord_y'		=> $longitude,
                    'icon'			=> $iconId,
                    'map_id'		=> $mapId,		//Generic map with all places
                    'address'		=> $address,
                ));
                
                //Get the marker id
                $markerIds['generic'] = $wpdb->insert_id;
            }
            
            /* 
                Specifc map
            */
                
            $mapId = get_post_meta($postId, 'map_id', true);
            
            //First try to update
            if(isset($markerIds['page_marker'])){
                //Create an icon for this marker
                $customIconId = $maps->createIcon($markerId=$markerIds['page_marker'], $title=$title, $iconUrl, $defaultIconId=$iconId);
                    
                $result = $wpdb->update($wpdb->prefix . 'ums_markers', 
                    array(
                        'title' 		=> $title,
                        'description'	=> $postThumbnail.$directionsForm,
                        'coord_x'		=> $latitude,
                        'coord_y'		=> $longitude,
                        'address'		=> $address,
                    ), 
                    array( 'ID'			=> $markerIds['page_marker']),
                );
                
                //Failed
                if($result == false){
                    //Check if marker exist, if not delete the metakey
                    if(!$maps->markerExists($markerIds['page_marker'])){
                        unset($markerIds['page_marker']);
                    }
                }else{
                    SIM\printArray("Updated marker with id {$markerIds['page_marker']} and title $title on map with id $mapId");
                }
            }
            
            //Create if it does not exist anymore
            if(!isset($markerIds['page_marker'])){
                
                if(!is_numeric($mapId)){
                    //Create a custom map for this location
                    $mapId = $maps->addMap($title, $latitude, $longitude, $address, $height='300', $zoom=10);
                    
                    //Save the map id in db
                    update_metadata( 'post', $postId,'map_id', $mapId);
                }					
                
                //Create an icon for this marker
                $customIconId = $maps->createIcon($markerId=null, $title=$title, $iconUrl, $defaultIconId=$iconId);
                
                //Add the marker to this map
                $wpdb->insert($wpdb->prefix . 'ums_markers', array(
                    'title' 		=> $title,
                    'description'	=> $postThumbnail.$directionsForm,
                    'coord_x'		=> $latitude,
                    'coord_y'		=> $longitude,
                    'icon' 			=> $customIconId,
                    'map_id'		=> $mapId,
                    'address'		=> $address,
                ));
                $markerIds['page_marker'] = $wpdb->insert_id;
            }
            
            /* 
                Category maps
            */
            $categories = get_categories( array(
                'orderby' 	=> 'name',
                'order'   	=> 'ASC',
                'taxonomy'	=> 'locations',
                'hide_empty'=> false,
            ) );		
        
            //loop over all available the categories
            foreach($categories as $locationType){
                //If the current cat is set for this post
                if(in_array($locationType->cat_ID, $locationTypes)){
                    $name 				= $locationType->slug;
                    $mapName			= $name."_map";
                    $mapId				= SIM\getModuleOption('locations', $mapName);
                    $iconName			= $name."_icon";
                    $iconId			    = SIM\getModuleOption('locations', $iconName);
                    
                    //Checking if this marker exists
                    if(is_numeric($markerIds[$name])){
                        //Create an icon for this marker
                        $customIconId = $maps->createIcon($markerId=$markerIds[$name], $title=$title, $iconUrl, $defaultIconId=$iconId);
                        
                        //Update the marker in db
                        $result = $wpdb->update($wpdb->prefix . 'ums_markers', 
                            array(
                                'description'	=> $description,
                                'coord_x'		=> $latitude,
                                'coord_y'		=> $longitude,
                                'map_id'		=> $mapId,
                                'address'		=> $address,
                            ), 
                            array( 'ID' => $markerIds[$name]),
                        );
                        
                        //Failed
                        if($result == false){
                            //Check if marker exist, if not delete the metakey
                            if(!$maps->markerExists($markerIds[$name])){
                                unset($markerIds[$name]);
                            }
                        }else{
                            SIM\printArray("Updated marker with id {$markerIds[$name]} and title $title on map with id $mapId");
                        }
                    }
                    
                    if(!is_numeric($markerIds[$name])){
                        //Create an icon for this marker
                        $customIconId = $maps->createIcon($markerId=null, $title=$title, $iconUrl, $defaultIconId=$iconId);
                        
                        //Add marker for this map
                        $wpdb->insert($wpdb->prefix . 'ums_markers', array(
                            'title' 		=> $title,
                            'description'	=> $description,
                            'coord_x'		=> $latitude,
                            'coord_y'		=> $longitude,
                            'icon' 			=> $customIconId,
                            'map_id'		=> $mapId,
                            'address'		=> $address,
                        ));
                        
                        //Get the marker id
                        $markerIds[$name] = $wpdb->insert_id;
                        
                        SIM\printArray("Created marker with id {$wpdb->insert_id} and title $title on map with id $mapId");
                    }
                }
            }
            
            //Store marker ids in db
            update_metadata( 'post', $postId,"marker_ids", $markerIds);
        }elseif($latitude == '' and $longitude == '' and is_numeric($mapId)){
            //Delete the custom map for this post
            delete_post_meta($postId, 'map_id');
            $maps->removeMap($mapId);
            
            //Remove all markers related to this post
            foreach($markerIds as $marker_id){
                $maps->removeMarker($marker_id);
            }
            
            //Store marker ids in db
            delete_post_meta($postId,"marker_ids");
        }
    }
}

//add meta data fields
add_action('sim_frontend_post_after_content', function ($frontendcontend){
    //Load js
    wp_enqueue_script('sim_location_script');

    $postId    = $frontendcontend->postId;
    $postName  = $frontendcontend->postName;
    $location   = get_post_meta($postId, 'location', true);
    
    if(isset($location['address'])){
        $address = $location['address'];
    }elseif(get_post_meta($postId, 'geo_address', true) != ''){
        $address = get_post_meta($postId, 'geo_address', true);
    }
    
    if(isset($location['latitude'])){
        $latitude = $location['latitude'];
    }elseif(get_post_meta($postId, 'geo_latitude', true) != ''){
        $latitude = get_post_meta($postId, 'geo_latitude', true);
    }
    
    if(isset($location['longitude'])){
        $longitude = $location['longitude'];
    }elseif(get_post_meta($postId, 'geo_longitude', true) != ''){
        $longitude = get_post_meta($postId, 'geo_longitude', true);
    }
    
    $url = get_post_meta($postId,'url',true);
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
    <div id="location-attributes" class="location<?php if($postName != 'location') echo ' hidden'; ?>">
        <fieldset id="location" class="frontendform">
            <legend>
                <h4>Location details</h4>
            </legend>					
        
            <table class="form-table">
                <tr>
                    <th><label for="tel">Phone number</label></th>
                    <td>
                        <input type='tel' class='formbuilder' name='tel' value='<?php echo get_post_meta($postId, 'tel', true); ?>'>
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
}, 10, 2);