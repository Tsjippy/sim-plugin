<?php
namespace SIM\LOCATIONS;
use SIM;
    
add_filter('sim_frontend_posting_modals', function($types){
    $types[]	= 'location';
    return $types;
});

add_action('frontend_post_before_content', function($frontEndContent){
    $categories = get_categories( array(
        'orderby' 	=> 'name',
        'order'   	=> 'ASC',
        'taxonomy'	=> 'locations',
        'hide_empty'=> false,
    ) );
    
    $frontEndContent->showCategories('location', $categories);
});

add_action('frontend_post_content_title', function ($post_type){
    //Location content title
    $class = 'location';
    if($post_type != 'location')	$class .= ' hidden';
    
    echo "<h4 class='$class' name='location_content_label'>";
        echo 'Please describe the location';
    echo "</h4>";
});

add_action('sim_after_post_save', __NAMESPACE__.'\save_location_meta');
function save_location_meta($post){
    //store locations
    $locationtypes = [];
    if(is_array($_POST['locationtype'])){
        foreach($_POST['locationtype'] as $key=>$locationtype) {
            if($locationtype != '') $locationtypes[] = $locationtype;
        }
        
        //Store types
        $locationtypes = array_map( 'intval', $locationtypes );
        
        wp_set_post_terms($post->ID,$locationtypes,'locations');
    }
    
    //tel
    if(isset($_POST['tel'])){
        //Store serves
        update_metadata( 'post', $post->ID,'tel',$_POST['tel']);
    }
    
    //url
    if(isset($_POST['url'])){
        //Store serves
        update_metadata( 'post', $post->ID,'url',$_POST['url']);
    }
    
    location_address($locationtypes, $post->ID);
}


add_action('sim_ministry_added', __NAMESPACE__.'\location_address', 10, 2);
function location_address($locationtypes, $post_id){
    global $wpdb;

    $maps   = new Maps();
    
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
            update_metadata( 'post', $post_id,'location',$new_location);

            //Add the profile picture to the marker content
            $post_thumbnail = get_the_post_thumbnail($post_id, 'thumbnail', array( 'class' => 'aligncenter markerpicture' , 'style' => 'max-height:100px;',));
            
            //Add a directions button to the marker content
            $directions_form = "<p><a class='button' onclick='zgetRoute(this,$latitude,$longitude)'>Get directions</a></p>";
            
            //Add the post excerpt to the marker content
            $description = $post_thumbnail.wp_trim_words(wp_trim_excerpt("",$post_id),25);
            //Add the post link to the marker content
            $url = get_permalink($post_id);
            $description .= '<a href="'.$url.'" style="display:block;" class="page_link">Show full descripion</a><br>'.$directions_form;
            
            //Get url of the featured image
            $icon_url = get_the_post_thumbnail_url($post_id);
            
            //Get the first category name
            $name = get_term( $locationtypes[0], 'locations' )->slug.'_icon';
            
            //If there is a location category set and an custom icon for this category is set
            if(count($locationtypes)>0 and !empty(SIM\get_module_option('locations', $name))){
                $icon_id = SIM\get_module_option('locations', $name);
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
                    if(!$maps->marker_exists($marker_ids['generic'])){
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
                $custom_icon_id = $maps->create_icon($marker_id=$marker_ids['page_marker'], $icon_title=$title, $icon_url, $default_icon_id=$icon_id);
                    
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
                    if(!$maps->marker_exists($marker_ids['page_marker'])){
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
                    $map_id = $maps->add_map($title, $latitude, $longitude, $address,$height='300',$zoom=10);
                    
                    //Save the map id in db
                    update_metadata( 'post', $post_id,'map_id',$map_id);
                }					
                
                //Create an icon for this marker
                $custom_icon_id = $maps->create_icon($marker_id=null, $icon_title=$title, $icon_url, $default_icon_id=$icon_id);
                
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
                'taxonomy'	=> 'locations',
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
                        $custom_icon_id = $maps->create_icon($marker_id=$marker_ids[$name], $icon_title=$title, $icon_url, $default_icon_id=$icon_id);
                        
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
                            if(!$maps->marker_exists($marker_ids[$name])){
                                SIM\print_array("Updating marker with id {$marker_ids[$name]} failed as it does not exist");
                                unset($marker_ids[$name]);
                            }
                        }else{
                            SIM\print_array("Updated marker with id {$marker_ids[$name]} and title $title on map with id $map_id");
                        }
                    }
                    
                    if(!is_numeric($marker_ids[$name])){
                        //Create an icon for this marker
                        $custom_icon_id = $maps->create_icon($marker_id=null, $icon_title=$title, $icon_url, $default_icon_id=$icon_id);
                        
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
            update_metadata( 'post', $post_id,"marker_ids",$marker_ids);
        }elseif($latitude == '' and $longitude == '' and is_numeric($map_id)){
            //Delete the custom map for this post
            delete_post_meta($post_id,'map_id');
            $maps->remove_map($map_id);
            
            //Remove all markers related to this post
            foreach($marker_ids as $marker_id){
                $maps->remove_marker($marker_id);
            }
            
            //Store marker ids in db
            delete_post_meta($post_id,"marker_ids");
        }
    }
}

//add meta data fields
add_action('frontend_post_after_content', function ($frontendcontend){
    //Load js
    wp_enqueue_script('sim_location_script');

    $post_id    = $frontendcontend->postId;
    $post_name  = $frontendcontend->postName;
    
    $location   = get_post_meta($post_id, 'location', true);
    
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
}, 10, 2);