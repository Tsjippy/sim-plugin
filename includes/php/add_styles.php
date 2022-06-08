<?php
namespace SIM;

const StyleVersion		= '7.0.25';

//Add js and css files
add_action( 'wp_enqueue_scripts', __NAMESPACE__.'\enqueueScripts');
add_action( 'admin_enqueue_scripts', __NAMESPACE__.'\registerScripts');

// Style the buttons in the media library
add_action( 'wp_enqueue_media', function(){
    wp_enqueue_style('sim_media_style', plugins_url('css/media.min.css', __DIR__), [], StyleVersion);
});

function registerScripts(){
	//LIBRARIES
	//Nice select https://github.com/bluzky/nice-select2
	wp_register_script('niceselect', plugins_url('js/nice-select2.js', __DIR__), array(),StyleVersion,true);

	//sortable library: https://github.com/SortableJS/Sortable#bs
	wp_register_script('sortable', 'https://SortableJS.github.io/Sortable/Sortable.js', array(),StyleVersion,true);
	
	//Sweet alert https://sweetalert2.github.io/
	wp_register_script('sweetalert', '//cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11.1.4', true);

	//Submit forms
	wp_register_script('sim_user_select_script',plugins_url('js/user_select.min.js', __DIR__), array('sweetalert'), StyleVersion,true);
	wp_register_script('sim_formsubmit_script', plugins_url('js/formsubmit.min.js', __DIR__), array(), StyleVersion,true);

	//table request shortcode
	wp_register_script('sim_table_script',plugins_url('js/table.min.js', __DIR__), array('sortable','sim_formsubmit_script','sim_forms_script'),StyleVersion,true);

	//add main.js
	wp_register_script('sim_script',plugins_url('js/main.min.js', __DIR__),array('niceselect', 'sweetalert'),StyleVersion, true);
	
	//File upload js
	wp_register_script('sim_fileupload_script',plugins_url('js/fileupload.min.js', __DIR__), array('sim_formsubmit_script'),StyleVersion,true);

	wp_register_style('sim_taxonomy_style', plugins_url('css/taxonomy.min.css', __DIR__), array(), StyleVersion);
}

function enqueueScripts($hook){
	$currentUser	= wp_get_current_user();
	$UserID			= $currentUser->id;

	registerScripts();

	if ( is_home() or is_search() or is_category() or is_tax()){
		wp_enqueue_style('sim_taxonomy_style');
	}

	wp_enqueue_script('sim_script');
	//add main css
	add_editor_style(plugins_url('css/sim.min.css', __DIR__));
	//style fo main site
	wp_enqueue_style( 'sim_style', plugins_url('css/sim.min.css', __DIR__), array(),StyleVersion);
	
	//Get current users location
	$location = get_user_meta( $UserID, 'location', true );
	if (isset($location['address'])){
		$address = $location['address'];;
	}else{
		$address = "";
	}

	$locations	= '';
	if(defined('NIGERIASTATES')){
		$locations	= NIGERIASTATES;
	}

	wp_localize_script( 'sim_script', 
		'sim', 
		array( 
			'ajaxUrl' 		=> admin_url( 'admin-ajax.php' ),
			"userId"		=> $UserID,
			'address' 		=> $address,
			'loadingGif' 	=> LOADERIMAGEURL,
			'baseUrl' 		=> get_home_url(),
			'maxFileSize'	=> wp_max_upload_size(),
			'restNonce'		=> wp_create_nonce('wp_rest'),
			'locations'		=> $locations
		) 
	);
};

//add_action('wp_print_scripts', 'SIM\inspect_script_styles');
function inspect_script_styles() {
	
	global $wp_scripts, $wp_styles;
	
	echo "\n" .'<!--'. "\n\n";
	printArray('\n SCRIPT IDs:');
	echo 'SCRIPT IDs:'. "\n";
	
	foreach($wp_scripts->queue as $handle){
		echo $handle . "\n";
		printArray($handle);
	}
	
	echo '\n STYLE IDs:';
	printArray( "\n" .'STYLE IDs:'. "\n");
	foreach($wp_styles->queue as $handle){
		echo $handle . "\n";
		printArray($handle);
	}
	
	echo "\n" .'-->'. "\n\n";
	
}

add_action('wp_enqueue_scripts', function() {		
	//Do no load these css files
	$dequeueStyles = [];
	//Do no load these js files
	$dequeueScripts = [];
	
	$dequeueScripts[] = 'featherlight';
	$dequeueScripts[] = 'jquery';
	$dequeueScripts[] = 'jquery-ui-datepicker';
	$dequeueScripts[] = 'jquery-ui-autocomplete';
	
	//Dequeue the css files
	foreach ($dequeueStyles as $dequeue_style){
		wp_dequeue_style($dequeue_style);
	}
	
	//dequeue the js files
	foreach ($dequeueScripts as $dequeue_script){
		wp_dequeue_script($dequeue_script);
	}
}, 99999);

add_action( 'wp_default_scripts', function( $scripts ) {
	if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
		$script = $scripts->registered['jquery'];
		if ( $script->deps ) { 
			// Check whether the script has any dependencies
			$script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
		}
	}
});
