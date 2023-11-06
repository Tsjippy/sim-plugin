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
    $class = 'property location';
    if($postType != 'location'){
        $class .= ' hidden';
    }

    echo "<h4 class='$class' name='location_content_label'>";
        echo 'Please describe the location';
    echo "</h4>";
});

add_action('sim_after_post_save', function($post, $frontEndPost){
    if($post->post_type != 'location'){
        return;
    }

    //store categories
    $frontEndPost->storeCustomCategories($post, 'locations');

    //parent
    if(isset($_POST['parent_location'])){
        if(empty($_POST['parent_location'])){
            $parent = 0;
        }else{
            $parent = $_POST['parent_location'];
        }

        wp_update_post(
            array(
                'ID'            => $post->ID,
                'post_parent'   => $parent
            )
        );
    }

    //tel
    if(isset($_POST['tel'])){
        if(empty($_POST['tel'])){
            delete_post_meta($post->ID, 'tel');
        }else{
            //Store serves
            update_metadata( 'post', $post->ID, 'tel', $_POST['tel']);
        }
    }

    //url
    if(isset($_POST['url'])){
        if(empty($_POST['url'])){
            delete_post_meta($post->ID, 'url');
        }else{
            //Store serves
            update_metadata( 'post', $post->ID, 'url', $_POST['url']);
        }
    }

    setLocationAddress($post->ID);
}, 10, 2);

add_action('sim_ministry_added', __NAMESPACE__.'\setLocationAddress', 10, 2);

/**
 * Store location details in meta
 */
function setLocationAddress($postId){
    if(
        isset($_POST['location'])				&&
        isset($_POST['location']['latitude'])	&&
        isset($_POST['location']['longitude'])  &&
        !empty($_POST['location']['latitude'])  &&
        !empty($_POST['location']['longitude'])
    ){
        update_metadata( 'post', $postId, 'location', json_encode($_POST['location']));
    }

    if(empty($_POST['location']['latitude']) && empty($_POST['location']['longitude']) && empty($_POST['location']['address'])){
        //Delete the custom map for this post
        delete_metadata('post', $postId, 'location');
    }
}

/**
 * Creates a location map and marker if the metvalue is updated
 */
add_action( 'added_post_meta', __NAMESPACE__.'\createLocationMarker', 10, 4);
add_action( 'updated_postmeta', __NAMESPACE__.'\createLocationMarker', 10, 4);
function createLocationMarker($metaId, $postId,  $metaKey,  $location){
    if($metaKey != 'location' || empty($location)){
        return;
    }

    global $wpdb;

    $maps   = new Maps();

    if(!is_array($location)){
        $location   = json_decode($location, true);
    }

    $address	= $location["address"]		= sanitize_text_field($location["address"]);
    $latitude	= $location["latitude"]	    = sanitize_text_field($location["latitude"]);
    $longitude	= $location["longitude"]	= sanitize_text_field($location["longitude"]);

    //Only update if needed
    if(empty($latitude) || empty($longitude)){
        return;
    }

    $check	= $maps->checkCoordinates($latitude, $longitude);
    if(is_wp_error($check)){
        return $check;
    }

    //Get marker array
    $markerIds = get_post_meta($postId, "marker_ids", true);
    if(!is_array($markerIds)){
        $markerIds = [];
    }

    $title			= get_the_title($postId);

    $categories     = wp_get_post_terms(
        $postId,
        'locations',
        array(
            'orderby'   => 'name',
            'order'     => 'ASC'
        )
    );

    $description    = "[location_description id=$postId]";

    //Get url of the featured image
    $iconUrl        = get_the_post_thumbnail_url($postId);

    //Get the first category name
    $name           = get_term( $categories[0], 'locations' )->slug."_icon";

    //If there is a location category set and an custom icon for this category is set
    if(!empty($categories) && !empty(SIM\getModuleOption(MODULE_SLUG, $name))){
        $iconId = SIM\getModuleOption(MODULE_SLUG, $name);
    }else{
        $iconId = 1;
    }

    /*
        GENERIC MARKER
    */
    //Update existing marker
    if(isset($markerIds['generic']) && $maps->markerExists($markerIds['generic'])){
        //Generic map, always update
        $wpdb->update($wpdb->prefix . 'ums_markers',
            array(
                'description'	=> $description,
                'coord_x'		=> $latitude,
                'coord_y'		=> $longitude,
                'address'		=> $address,
            ),
            array( 'ID'			=> $markerIds['generic']),
        );
    //Marker does not exist, create it
    }else{
        $mapId	=  SIM\getModuleOption(MODULE_SLUG, 'directions_map_id');

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
        Location map
    */
    $mapId = get_post_meta($postId, 'map_id', true);

    // map no longer exists create a new one
    if(is_numeric($mapId) && empty($maps->getMap($mapId))){
        //Create a map for this location
        $mapId = $maps->addMap($title, $latitude, $longitude, $address, '300', 10);

        //Save the map id in db
        update_metadata( 'post', $postId,'map_id', $mapId);
    }

    //Update existing
    if(isset($markerIds['page_marker']) && $maps->markerExists($markerIds['page_marker'])){
        //Create an icon for this marker
        $maps->createIcon($markerIds['page_marker'], $title, $iconUrl, $iconId);

        $wpdb->update($wpdb->prefix . 'ums_markers',
            array(
                'title' 		=> $title,
                'description'	=> "[location_description id=$postId basic=true]",
                'coord_x'		=> $latitude,
                'coord_y'		=> $longitude,
                'address'		=> $address,
            ),
            array( 'ID'			=> $markerIds['page_marker']),
        );
    // Create new
    }else{
        if(!is_numeric($mapId)){
            //Create a map for this location
            $mapId = $maps->addMap($title, $latitude, $longitude, $address, '300', 10);

            //Save the map id in db
            update_metadata( 'post', $postId,'map_id', $mapId);
        }

        //Create an icon for this marker
        $customIconId = $maps->createIcon(null, $title, $iconUrl, $iconId);

        //Add the marker to this map
        $wpdb->insert($wpdb->prefix . 'ums_markers', array(
            'title' 		=> $title,
            'description'	=> "[location_description id=$postId basic=true]",
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
    foreach($categories as $category){
        $name 				= $category->slug;
        $mapName			= $name."_map";
        $mapId				= SIM\getModuleOption(MODULE_SLUG, $mapName);
        $iconName			= $name."_icon";
        $iconId			    = SIM\getModuleOption(MODULE_SLUG, $iconName);

        //Update existing
        if(is_numeric($markerIds[$name]) && $maps->markerExists($markerIds[$name])){
            //Create an icon for this marker
            $maps->createIcon($markerIds[$name], $title, $iconUrl, $iconId);

            //Update the marker in db
            $wpdb->update($wpdb->prefix . 'ums_markers',
                array(
                    'description'	=> $description,
                    'coord_x'		=> $latitude,
                    'coord_y'		=> $longitude,
                    'map_id'		=> $mapId,
                    'address'		=> $address,
                ),
                array( 'ID' => $markerIds[$name]),
            );
        }else{
            //Create an icon for this marker
            $customIconId = $maps->createIcon(null, $title, $iconUrl, $iconId);

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

    //Store marker ids in db
    update_metadata( 'post', $postId, "marker_ids", $markerIds);
}

// Removes a map when post data is deleted
add_action( 'delete_post_meta', function($metaIds, $postId, $metaKey, $metaValue ){
    if($metaKey != 'location'){
        return;
    }

    $markerIds  = get_metadata('post', $postId, "marker_ids", true);
    $mapId      = get_metadata('post', $postId, 'map_id', true);

    delete_metadata('post', $postId, 'map_id');
    delete_metadata('post', $postId, 'marker_ids');

    $maps   = new Maps();

    //Remove all markers related to this post
    foreach($markerIds as $markerId){
        $maps->removeMarker($markerId);
    }

    // Remove the location map
    $maps->removeMap($mapId);
}, 10, 4);

//add meta data fields
add_action('sim_frontend_post_after_content', function ($frontendcontend){

    if(!empty($frontendcontend->post) && $frontendcontend->post->post_type != 'location'){
        return;
    }

    //Load js
    wp_enqueue_script('sim_location_script');

    $postId     = $frontendcontend->postId;
    $postName   = $frontendcontend->postName;
    $location   = get_post_meta($postId, 'location', true);
    if(!is_array($location) && !empty($location)){
        $location  = json_decode($location, true);
    }

    $address = '';
    if(isset($location['address'])){
        $address = $location['address'];
    }elseif(get_post_meta($postId, 'geo_address', true) != ''){
        $address = get_post_meta($postId, 'geo_address', true);
    }

    $latitude = '';
    if(isset($location['latitude'])){
        $latitude = $location['latitude'];
    }elseif(get_post_meta($postId, 'geo_latitude', true) != ''){
        $latitude = get_post_meta($postId, 'geo_latitude', true);
    }

    $longitude  = '';
    if(isset($location['longitude'])){
        $longitude = $location['longitude'];
    }elseif(get_post_meta($postId, 'geo_longitude', true) != ''){
        $longitude = get_post_meta($postId, 'geo_longitude', true);
    }

    $url = get_post_meta($postId, 'url', true);
    ?>
    <style>
        .form-table, .form-table th, .form-table, td{
            border: none;
        }
        .form-table{
            text-align: left;
        }
    </style>
    <div id="location-attributes" class="property location<?php if($postName != 'location'){echo ' hidden';} ?>">
        <div id="parentpage" class="frontendform">
            <h4>Select a parent location</h4>
            <?php
            echo SIM\pageSelect('parent_location', $frontendcontend->postParent, '', ['location'], false);
            ?>
        </div>
        <div class="frontendform">
            <h4>Update warnings</h4>
            <label>
                <input type='checkbox' name='static_content' value='static_content' <?php if(get_post_meta($postId, 'static_content', true)){echo 'checked';}?>>
                Do not send update warnings for this location
            </label>
        </div>

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
                    </td>
                </tr>

                <tr>
                    <th>
                        <button class='current-location button small' type='button'>Use current location</button>
                    </th>
                    <td></td>
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

// Update marker icon
add_action( 'wp_after_insert_post', function( $postId, $post, $update ){
    if($post->post_type != 'location'){
        return;
    }

    $url        = get_the_post_thumbnail_url($postId);
    $markerIds  = get_post_meta($postId, "marker_ids", true);

    if(!is_array($markerIds) || !isset($markerIds['page_marker'])){
        return;
    }

    $maps   = new Maps();

    // Update the url
    $maps->createIcon($markerIds['page_marker'], $post->post_title, $url, 1);
}, 10,3 );