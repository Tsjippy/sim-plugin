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
			'slug' 			=> $plural,	//archive pages on /plural/
			'hierarchical' 	=> true,
			'has_archive'	=> true
		),
		'query_var' 		=> true,
		'singular_label' 	=> "$Plural Type",
		'show_admin_column' => true,
	);
	
	//register taxonomy category
	register_taxonomy( $single.'type', $single, $taxonomy_category_args );

	//redirect plural to archive page as well
	add_rewrite_rule($plural.'/?$','index.php?post_type='.$single,'top');
}

add_filter( 'single_template', 'SIM\get_template_file', 10, 3 );
add_filter( 'page_template', 'SIM\get_template_file', 10, 3 );
add_filter( 'taxonomy_template', 'SIM\get_template_file', 10, 3 );
add_filter( 'part_template', 'SIM\get_template_file', 10, 3 );
add_filter( 'archive_template', 'SIM\get_template_file', 10, 3 );
add_filter( 'category_template', 'SIM\get_template_file', 10, 3 );
add_filter( 'singular_template', 'SIM\get_template_file', 10, 3 );

function get_template_file($template, $type, $templates){
	global $post;

	$base_dir		= __DIR__."/../modules";	

	//check what we are dealing with
	$name	= '';

	switch ($type) {
		case 'single':
			$name			= $post->post_type;
			$template_file	= "$base_dir/{$name}s/templates/$type-$name.php";
			break;
		case 'archive':
			$name			= get_queried_object()->name;
			$template_file	= "$base_dir/{$name}s/templates/$type-$name.php";
			break;
		case 'taxonomy':
			$name			= get_queried_object()->taxonomy;
			$dirname		= str_replace('type', '', $name);
			$template_file	= "$base_dir/{$dirname}s/templates/$type-$name.php";
			break;
		case 'page';
			break;
		default:
			print_array("Not sure which template to load for $type");
	}

	if ( 
		empty($template)											or
		(!empty($name)												and		// current posttype is an enabled post type
		locate_template( array( "$type-$name.php" ) ) !== $template)	and	// and template is not found in theme folder
		file_exists($template_file)											// template file exists
	) {
		//return plugin post template
		//return $template;
		return $template_file;
	}
	
	return $template;
}

function show_comments() {
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