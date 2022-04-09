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
		//if we are on a locationtype page, change to display the location types
		if(is_tax('locationtype') or is_page('location') or get_post_type()=='location'){
			$cat_args['taxonomy'] 		= 'locationtype';
			$cat_args['hierarchical']	= true;
			$cat_args['hide_empty'] 	= false;
		}
		
		return $cat_args;
	},
	10, 
	2 
);

add_filter('widget_title', function ($title, $widget_id=null){
	//Change the title of the location category widget if not logged in
	if(is_tax('locationtype') and $widget_id == 'categories' and !is_user_logged_in()){
		$url = SITEURL.'/locations/ministry/';
		return "<a href='$url'>Ministries</a>";
	}
    return $title;
}, 999, 2);

//Default content for ministry pages
function ministry_description($post_id){
	$html = "";
	//Get the post id of the page the shortcode is on
	$ministry = get_the_title($post_id);
	//$ministry = strtolower($ministry);
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
	
	$html		.= get_location_employees($ministry);
	$latitude 	= get_post_meta($post_id,'geo_latitude',true);
	$longitude 	= get_post_meta($post_id,'geo_longitude',true);
	if ($latitude != "" and $longitude != ""){
		$html .= "<p><a class='button' onclick='getRoute(this,$latitude,$longitude)'>Get directions to $ministry</a></p>";
	}
	
	if(!empty($child_page_html)){
		$html = $child_page_html."<br><br>".$html; 
	}
	return $html;	
}


function get_location_employees($locationName){
	if (is_user_logged_in()){
		$ministry_name 	= str_replace(" ", "_", $locationName);

		//Loop over all users to see if they work here
		$users 			= get_users('orderby=display_name');
		
		$html 			= '';

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
		if(empty($html)){
			$html .= "No one dares to say they are working here!";
		}

		$html 	= "<p><strong>People working at $locationName are:</strong><br><br>$html</p>";
		
		return $html;
	}
}