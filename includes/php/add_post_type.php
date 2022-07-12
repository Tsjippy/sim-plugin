<?php
namespace SIM;

/*
	In this file we define a new post type: recipe
	We also define a new taxonomy (category): recipetype
	We make sure post of this type get an url according to their taxonomy
*/
$taxnames=[];

/**
 * Addm a new post type and taxonomy
 * @param  string 	$single		the single name of the posttype
 * @param  string	$plural     the plural name of the post type         
*/
function registerPostTypeAndTax($single, $plural){
	global $taxnames;
	$taxnames[$single]	= $plural;

	$plural				= ucfirst($plural);
	$single				= ucfirst($single);
	
	/*
		CREATE POST TYPE
	*/
	//Text to show for buttons
	$labels = array(
		'name' 					=> $single,
		'singular_name' 		=> $single,
		'menu_name' 			=> $plural,
		'add_new' 				=> "Add $single",
		'add_new_item' 			=> "Add New $single",
		'edit' 					=> 'Edit',
		'edit_item' 			=> "Edit $single",
		'new_item' 				=> "New $single",
		'view' 					=> "View $single",
		'view_item' 			=> "View $single",
		'search_items' 			=> "Search $plural",
		'not_found' 			=> "No $plural Found",
		'not_found_in_trash' 	=> "No $plural Found in Trash",
		'parent' 				=> "Parent $plural",
	);
	
	$args = array(
		'labels' 				=> $labels,
		'description' 			=> "Post to display $plural",
		'public' 				=> true,
		'show_ui' 				=> true,
		'show_in_menu' 			=> true,
		'capability_type' 		=> 'post',
		'has_archive' 			=> true,
		'rewrite' 				=> true,	//archive page on /single
		'query_var' 			=> true,
		'supports' 				=> array('title','editor','author','excerpt','custom-fields','thumbnail','revisions','comments'),
		'menu_position' 		=> 5,
		'show_in_rest'			=> true,
		'delete_with_user'		=> false,
		'taxonomies'  			=> array( $single."type" ),
	);
	
	//Create the custom post type
	register_post_type( $single, $args );
	
	//create categories
	createTaxonomies($plural, $single, $plural);
}

/**
 * Register taxonomy for an existing posttype
 * 
 * @param  string 	$taxonomyName	the name of the taxonomy
 * @param  string 	$postType		the single name of the posttype
 * @param  string	$plural     	the plural name of the post type  
 */
function createTaxonomies($taxonomyName, $postType, $plural){
	$taxonomyName		= strtolower($taxonomyName);
	$postType			= strtolower($postType);
	$plural				= strtolower($plural);
	$lowerPlural		= $plural;
	$plural				= ucfirst($plural);
	/*
		CREATE CATEGORIES
	*/
	$labels = array(
		'name' 							=> "$plural Types",
		'singular_name' 				=> "$plural Types",
		'search_items' 					=> "Search $plural Types",
		'popular_items' 				=> "Popular $plural Types",
		'all_items' 					=> "All $plural Types",
		'parent_item' 					=> "Parent $postType Type",
		'parent_item_colon' 			=> "Parent $postType Type:",
		'edit_item' 					=> "Edit $postType Type",
		'update_item' 					=> "Update $postType Type",
		'add_new_item' 					=> "Add New $postType Type",
		'new_item_name' 				=> "New $postType Type Name",
		'separate_items_with_commas' 	=> "Separate $postType type with commas",
		'add_or_remove_items' 			=> "Add or remove $postType type",
		'choose_from_most_used' 		=> "Choose from the most used $postType types",
		'menu_name' 					=> ucfirst($postType)." Categories",
	);
	
	$args = array(
		'labels' 			=> $labels,
		'public' 			=> true,
		'show_ui' 			=> true,
		'show_in_rest' 		=> true,
		'hierarchical' 		=> true,
		'rewrite' 			=> array( 
			'slug' 			=> $lowerPlural,	//archive pages on /plural/
			'hierarchical' 	=> true,
			'has_archive'	=> true
		),
		'query_var' 		=> true,
		'singular_label' 	=> "$plural Type",
		'show_admin_column' => true,
	);
	
	//register taxonomy category
	register_taxonomy( $taxonomyName, $postType, $args );

	//redirect plural to archive page as well
	add_rewrite_rule($taxonomyName.'/?$','index.php?post_type='.$postType,'top');
}

add_filter( 'single_template', __NAMESPACE__.'\getTemplateFile', 10, 2 );
add_filter( 'page_template', __NAMESPACE__.'\getTemplateFile', 10, 2 );
add_filter( 'taxonomy_template', __NAMESPACE__.'\getTemplateFile', 10, 2 );
add_filter( 'part_template', __NAMESPACE__.'\getTemplateFile', 10, 2 );
add_filter( 'archive_template', __NAMESPACE__.'\getTemplateFile', 10, 2 );
add_filter( 'category_template', __NAMESPACE__.'\getTemplateFile', 10, 2 );
add_filter( 'singular_template', __NAMESPACE__.'\getTemplateFile', 10, 2 );

/**
 * Finds a template file for a custom post type
 * Checks the theme folder, then the plugin folder
 * @param  string 	$template	the current template file
 * @param  string	$type     	the requested page type
 * @param  string	$name     	the requested page name 
 * @return string				the template file 
*/
function getTemplateFile($template, $type, $name=''){
	global $post;

	$baseDir		= __DIR__."/../modules";	

	//check what we are dealing with
	switch ($type) {
		case 'single':
			if(empty($name))	$name	= $post->post_type;
			$templateFile	= "$baseDir/{$name}s/templates/$type-$name.php";
			break;
		case 'archive':
			if(empty($name))	$name	= get_queried_object()->name.'s';
			$templateFile	= "$baseDir/{$name}/templates/$type-$name.php";
			break;
		case 'taxonomy':
			if(empty($name))	$name	= get_queried_object()->taxonomy;
			$templateFile	= "$baseDir/$name/templates/$type-$name.php";
			break;
		case 'page';
			break;
		default:
			printArray("Not sure which template to load for $type");
	}

	if ( 
		empty($template)												or
		(!empty($name)													and		// current posttype is an enabled post type
		locate_template( array( "$type-$name.php" ) ) !== $template)	and		// and template is not found in theme folder
		file_exists($templateFile)												// template file exists
	) {
		return $templateFile;
	}
	
	return $template;
}

/**
 * Shows comments if allowed
*/
function showComments() {
	// If comments are open or we have at least one comment, load up the comment template.
	// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- Intentionally loose.
	if ( comments_open() || '0' != get_comments_number() ) :
		?>

		<div class="comments-area">
			<?php comments_template(); ?>
		</div>

		<?php
	endif;
}