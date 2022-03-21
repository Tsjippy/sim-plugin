<?php
namespace SIM;

const StyleVersion		= '7.0.5';

//Add js and css files
add_action( 'wp_enqueue_scripts', __NAMESPACE__.'\enqueueScripts');
add_action( 'admin_enqueue_scripts', __NAMESPACE__.'\enqueueLibraries');

add_filter( 'body_class', function( $classes ) {
	$newclass = [];
	
	if ( is_home() or is_search() or is_category() or is_tax()){
		$newclass[] = 'categorypage';
	}
	
	return array_merge( $classes, $newclass );
} );

// Style the buttons in the media library
add_action( 'wp_enqueue_media', function(){
    wp_enqueue_style('sim_media_style', plugins_url('css/media.min.css', __DIR__), [], StyleVersion);
});

function enqueueLibraries(){
	//LIBRARIES
	//Nice select https://github.com/bluzky/nice-select2
	wp_register_script('niceselect', plugins_url('js/nice-select2.js', __DIR__), array(),StyleVersion,true);

	//sortable library: https://github.com/SortableJS/Sortable#bs
	wp_register_script('sortable', 'https://SortableJS.github.io/Sortable/Sortable.js', array(),StyleVersion,true);
	
	//Sweet alert https://sweetalert2.github.io/
	wp_register_script('sweetalert', '//cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11.1.4', true);

	//Submit forms
	wp_register_script('sim_other_script',plugins_url('js/other.js', __DIR__), array('sweetalert'),StyleVersion,true);

	//table request shortcode
	wp_register_script('sim_table_script',plugins_url('js/table.js', __DIR__), array('sortable','sim_other_script'),StyleVersion,true);
}

function enqueueScripts($hook){
	$current_user	= wp_get_current_user();
	$UserID			= $current_user->id;

	enqueueLibraries();

	//add main.js
	wp_enqueue_script('sim_script',plugins_url('js/main.js', __DIR__),array('niceselect', 'sweetalert'),StyleVersion, true);
	
	//File upload js
	wp_register_script('sim_fileupload_script',plugins_url('js/fileupload.js', __DIR__), array('sim_other_script'),StyleVersion,true);

	//add main css, but only on non-admin pages
	//if ($hook == ""){
		//style for tinymce
		add_editor_style(plugins_url('css/sim.min.css', __DIR__));
		//style fo main site
		wp_enqueue_style( 'sim_style', plugins_url('css/sim.min.css', __DIR__), array(),StyleVersion);
	//}
	
	//Get current users location
	$location = get_user_meta( $UserID, 'location', true );
	if (isset($location['address'])){
		$address = $location['address'];;
	}else{
		$address = "";
	}

	wp_localize_script( 'sim_script', 
		'sim', 
		array( 
			'ajax_url' 		=> admin_url( 'admin-ajax.php' ),
			"userid"		=> $UserID,
			'address' 		=> $address,
			'loading_gif' 	=> LOADERIMAGEURL,
			'base_url' 		=> get_home_url(),
			'max_file_size'	=> wp_max_upload_size(),
			'restnonce'		=> wp_create_nonce('wp_rest')
		) 
	);
};

//add_action('wp_print_scripts', 'SIM\inspect_script_styles');
function inspect_script_styles() {
	
	global $wp_scripts, $wp_styles;
	
	echo "\n" .'<!--'. "\n\n";
	print_array('\n SCRIPT IDs:');
	echo 'SCRIPT IDs:'. "\n";
	
	foreach($wp_scripts->queue as $handle){
		echo $handle . "\n";
		print_array($handle);
	}
	
	echo '\n STYLE IDs:';
	print_array( "\n" .'STYLE IDs:'. "\n");
	foreach($wp_styles->queue as $handle){
		echo $handle . "\n";
		print_array($handle);
	}
	
	echo "\n" .'-->'. "\n\n";
	
}

add_action('wp_enqueue_scripts', 'SIM\disable_scripts_styles', 99999);
function disable_scripts_styles() {
	global $post;
	global $Modules;
		
	//Do no load these css files
	$dequeue_styles = [];
	//Do no load these js files
	$dequeue_scripts = [];
	
	$dequeue_scripts[] = 'featherlight';
	$dequeue_scripts[] = 'jquery';
	$dequeue_scripts[] = 'jquery-ui-datepicker';
	$dequeue_scripts[] = 'jquery-ui-autocomplete';
	
	//Dequeue the css files
	foreach ($dequeue_styles as $dequeue_style){
		wp_dequeue_style($dequeue_style);
	}
	
	//dequeue the js files
	foreach ($dequeue_scripts as $dequeue_script){
		wp_dequeue_script($dequeue_script);
	}
}

add_action( 'wp_default_scripts', function( $scripts ) {
	if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
		$script = $scripts->registered['jquery'];
		if ( $script->deps ) { 
			// Check whether the script has any dependencies
			$script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
		}
	}
});
