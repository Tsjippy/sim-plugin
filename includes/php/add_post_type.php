<?php
namespace SIM;

/*
	In this file we define a new post type: recipe
	We also define a new taxonomy (category): recipetype
	We make sure post of this type get an url according to their taxonomy
*/
$taxnames=[];

/**
 * Adds a new post type and taxonomy
 * Make sure to also register a template with the name sim/{$single}meta
 * 
 * @param  string 	$single		the single name of the posttype
 * @param  string	$plural     the plural name of the post type         
*/
function registerPostTypeAndTax($single, $plural){
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
		'hierarchical' 			=> true,
		'labels' 				=> $labels,
		'description' 			=> "Post to display $plural",
		'public' 				=> true,
		'show_ui' 				=> true,
		'show_in_menu' 			=> true,
		'capability_type' 		=> 'post',
		'has_archive' 			=> true,
		'rewrite' 				=> true,	//archive page on /single
		'query_var' 			=> true,
		'supports' 				=> array('title','editor','author','excerpt','custom-fields','thumbnail','revisions','comments','page-attributes'),
		'menu_position' 		=> 5,
		'show_in_rest'			=> true,
		'delete_with_user'		=> false,
		'taxonomies'  			=> array( $plural, 'post_tag'),
		'template' => array(
            array( 'core/paragraph', array(
                'placeholder' => 'Add a Description...',
            ) ),
            array( "sim/{$single}meta" )
        ),
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
	$Plural				= ucfirst($plural);
	/*
		CREATE CATEGORIES
	*/
	$labels = array(
		'name' 							=> "$Plural Types",
		'singular_name' 				=> "$Plural Types",
		'search_items' 					=> "Search $Plural Types",
		'popular_items' 				=> "Popular $Plural Types",
		'all_items' 					=> "All $Plural Types",
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
			'slug' 			=> $plural,	//archive pages on /plural/
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

	// Clear the permalinks after the post type has been registered.
    flush_rewrite_rules(); 
}

add_filter( 'single_template', __NAMESPACE__.'\getTemplateFile', 10, 2 );
add_filter( 'page_template', __NAMESPACE__.'\getTemplateFile', 10, 2 );
add_filter( 'taxonomy_template', __NAMESPACE__.'\getTemplateFile', 10, 2 );
add_filter( 'part_template', __NAMESPACE__.'\getTemplateFile', 10, 2 );
add_filter( 'archive_template', __NAMESPACE__.'\getTemplateFile', 10, 2 );
add_filter( 'category_template', __NAMESPACE__.'\getTemplateFile', 10, 2 );
add_filter( 'singular_template', __NAMESPACE__.'\getTemplateFile', 10, 2 );
add_filter( 'content_template', __NAMESPACE__.'\getTemplateFile', 10, 2 );

/**
 * Finds a template file for a custom post type
 * Checks the theme folder, then the plugin folder
 *
 * @param  string 	$template	the current template file
 * @param  string	$type     	the requested page type
 * @param  string	$name     	the requested page name
 *
 * @return string				the template file
*/
function getTemplateFile($template, $type, $name=''){
	global $post;

	$baseDir		= __DIR__."/../modules";

	//check what we are dealing with
	switch ($type) {
		case 'single':
			if(empty($name)){
				$name	= $post->post_type;
			}
			$templateFile	= "$baseDir/{$name}s/templates/$type-$name.php";
			break;
		case 'content':
			if(empty($name)){
				$name	= $post->post_type;
			}
			$templateFile	= "$baseDir/{$name}s/templates/$type.php";
			break;
		case 'archive':
			if(empty($name)){
				$name	= get_queried_object()->name.'s';
			}
			$templateFile	= "$baseDir/{$name}/templates/$type-$name.php";
			break;
		case 'taxonomy':
			if(empty($name)){
				$name	= get_queried_object()->taxonomy;
			}
			$templateFile	= "$baseDir/$name/templates/$type-$name.php";
			break;
		case 'page';
			break;
		default:
			printArray("Not sure which template to load for $type");
	}

	$templateFile	= apply_filters('sim-template-filter', $templateFile);

	if ( 
		file_exists($templateFile)										&&		// template file exists
		(empty($template)												||
		(
			!empty($name)												&&		// current posttype is an enabled post type
			locate_template( array( "$type-$name.php" ) ) !== $template			// and template is not found in theme folder
		))															
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