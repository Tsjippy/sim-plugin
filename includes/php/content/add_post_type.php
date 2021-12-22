<?php
namespace SIM;

/*
	In this file we define a new post type: recipe
	We also define a new taxonomy (category): recipetype
	We make sure post of this type get an url according to their taxonomy
*/
$taxnames=[];
function register_post_type_and_tax($single, $plural){
	global $taxnames;
	$taxnames[$single]	= $plural;

	$Plural				= ucfirst($plural);
	$Single				= ucfirst($single);
	
	/*
		CREATE POST TYPE
	*/
	//Text to show for buttons
	$labels = array(
		'name' 					=> $Single,
		'singular_name' 		=> $Single,
		'menu_name' 			=> $Plural,
		'add_new' 				=> "Add $Single",
		'add_new_item' 			=> "Add New $Single",
		'edit' 					=> 'Edit',
		'edit_item' 			=> "Edit $Single",
		'new_item' 				=> "New $Single",
		'view' 					=> "View $Single",
		'view_item' 			=> "View $Single",
		'search_items' 			=> "Search $Plural",
		'not_found' 			=> "No $Plural Found",
		'not_found_in_trash' 	=> "No $Plural Found in Trash",
		'parent' 				=> "Parent $Plural",
	);
	
	$args = array(
		'labels' 				=> $labels,
		'description' 			=> 'Post to display $plural',
		'public' 				=> true,
		'show_ui' 				=> true,
		'show_in_menu' 			=> true,
		'capability_type' 		=> 'post',
		'rewrite' 				=> false,
		'query_var' 			=> true,
		'supports' 				=> array('title','editor','author','excerpt','custom-fields','thumbnail','revisions','comments'),
		'menu_position' 		=> 5,
		'has_archive' 			=> true,
		'show_in_rest'			=> true,
		'delete_with_user'		=> false,
		'taxonomies'  			=> array( $single."type" ),
	);
	
	//Create the custom post type
	register_post_type( $single, $args );
	
	/*
		CREATE CATEGORIES
	*/
	$taxonomy_category_labels = array(
		'name' 							=> "$Plural Types",
		'singular_name' 				=> "$Plural Types",
		'search_items' 					=> "Search $Plural Types",
		'popular_items' 				=> "Popular $Plural Types",
		'all_items' 					=> "All $Plural Types",
		'parent_item' 					=> "Parent $Single Type",
		'parent_item_colon' 			=> "Parent $Single Type:",
		'edit_item' 					=> "Edit $Single Type",
		'update_item' 					=> "Update $Single Type",
		'add_new_item' 					=> "Add New $Single Type",
		'new_item_name' 				=> "New $Single Type Name",
		'separate_items_with_commas' 	=> "Separate $single type with commas",
		'add_or_remove_items' 			=> "Add or remove $single type",
		'choose_from_most_used' 		=> "Choose from the most used $single types",
		'menu_name' 					=> "$Single Types",
	);
	
	$taxonomy_category_args = array(
		'labels' 			=> $taxonomy_category_labels,
		'public' 			=> true,
		'show_ui' 			=> true,
		'show_in_rest' 		=> true,
		'hierarchical' 		=> true,
		'rewrite' 			=> array( 
			'slug' 			=> $plural,
			'with_front' 	=> false,
			'hierarchical' 	=> true,
		),
		'query_var' 		=> true,
		'singular_label' 	=> "$Plural Type",
		'show_admin_column' => true,
	);
	
	//register taxonomy category
	register_taxonomy( $single.'type', $single, $taxonomy_category_args );
}

/*
	BUILD CUSTOM URLS FOR custom post type
*/
add_filter('post_type_link', function ( $permalink, $post ) {
	global $taxnames;
	
	if( in_array($post->post_type, array_keys($taxnames)) and $post->post_status == 'publish') {
		$cat_type_name	= $post->post_type.'type' ;
		$plural			= $taxnames[$post->post_type];
		
		$resource_terms = get_the_terms( $post,$cat_type_name);
		
		$term_slug = '';
		if( ! empty( $resource_terms ) ) {
			foreach ( $resource_terms as $term ) {
				// The featured resource will have another category which is the main one
				if( $term->slug == 'featured' ) {
					continue;
				}
				
				$term_slug = $term->slug.'/';
				
				//if this term has a parent, prepent its slug
				if($term->parent != 0){
					$term_slug = get_term($term->parent, $cat_type_name)->slug.'/'.$term_slug;
				}
				break;
			}
		}
		//We add a _ to the name so we know its a post and not a category
		$permalink = get_home_url() ."/$plural/{$term_slug}_" . $post->post_name;
		
	}
	return $permalink;
} ,10,2);

/*
	TRANSLATE NEW URL TO WP URL
*/

add_filter('generate_rewrite_rules', function($wp_rewrite) {
	global $taxnames;
	
	$org_rules = $wp_rewrite->rules;
	
	//add at the top of the array
	$rules=[];
	
	foreach($taxnames as $single=>$plural){
		//Remove generic rules who break everything
		unset($org_rules[$plural.'/(.+?)/?$']); 
		unset($org_rules[$plural.'/(.+?)/page/?([0-9]{1,})/?$']);

		$terms = get_terms( array(
			'taxonomy' => $single.'type',
			'hide_empty' => false,
		) );
		
		//Also show items without category
		$rules["$plural/_([^/]*)$"] = "index.php?post_type=$single&$single=\$matches[1]&name=\$matches[1]";

		foreach ($terms as $term) {
			
			$term_slug = $term->slug;
			
			//Prepent parent if available
			if($term->parent != 0){
				$term_slug = get_term($term->parent, $single.'type')->slug.'/'.$term_slug;
			}
			
			//Code below translates to: if the url is build like recipes/recipe type then get the content from blablabla
			//We add a _ to the name so we know its a post and not a category
			$rules["$plural/$term_slug/_([^/]*)$"] = "index.php?post_type=$single&$single=\$matches[1]&name=\$matches[1]";
			
			//Also make a rule for subcategory pages
			$rules["$plural/($term_slug)/?$"]='index.php?locationtype=$matches[1]';

			//add a rule for the main page
			//$rules["$plural/([^/]*)$"] = "index.php?post_type=$single";
		}
	}
	// merge with global rules
	$wp_rewrite->rules = $rules+$org_rules;
		
	return $wp_rewrite->rules;
});