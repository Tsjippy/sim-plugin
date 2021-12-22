<?php
namespace SIM;

$StyleVersion = "6.9.302";

//Add js and css files
add_action( 'wp_enqueue_scripts', 'SIM\enqueue_scripts');
//add_action( 'admin_enqueue_scripts', 'SIM\enqueue_scripts' );

add_filter( 'body_class', function( $classes ) {
	$newclass = [];
	
	if ( is_home() or is_search() or is_category() or is_tax()){
		$newclass[] = 'categorypage';
	}
	
	return array_merge( $classes, $newclass );
} );

function enqueue_scripts($hook){
	global $LoggedInHomePage;
	global $StyleVersion;
	global $LoaderImageURL;
	global $NigeriaStates;
	
	$current_user = wp_get_current_user();
	$current_user_first_name = $current_user->first_name;
	$UserID = $current_user->id;

	//Register scripts
	
	//LIBRARIES
	//Nice select https://github.com/bluzky/nice-select2
	wp_register_script('niceselect', plugins_url('js/nice-select2.js', __DIR__), array(),$StyleVersion,true);

	//sortable library: https://github.com/SortableJS/Sortable#bs
	wp_register_script('sortable', 'https://SortableJS.github.io/Sortable/Sortable.js', array(),$StyleVersion,true);
	
	//selectable select table cells https://github.com/Mobius1/Selectable
	wp_register_script('selectable', "https://unpkg.com/selectable.js@latest/selectable.min.js", array(), null, true);
	
	//Sweet alert https://sweetalert2.github.io/
	wp_register_script('sweetalert', '//cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11.1.4', true);
	
	//OWN SCRIPTS
	//add main.js
	wp_enqueue_script('simnigeria_script',plugins_url('js/main.js', __DIR__),array('niceselect', 'sweetalert'),$StyleVersion, true);
	//debug
	//wp_enqueue_script('simnigeria_test_script', '//localhost:8080/target.js');
	
	//Password strength js
	wp_register_script('simnigeria_password_strength_script',plugins_url('js/account/password_strength.js', __DIR__),array('password-strength-meter'),$StyleVersion,true);
	
	//Welcome shortcode
	wp_register_script('simnigeria_message_script',plugins_url('js/hide_welcome.js', __DIR__),array(),$StyleVersion,true);
	
	//account_statements
	wp_register_script('simnigeria_account_statements_script',plugins_url('js/account_statements.js', __DIR__), array(),$StyleVersion,true);
	
	//Submit forms
	wp_register_script('simnigeria_forms_script',plugins_url('js/forms.js', __DIR__), array('sweetalert'),$StyleVersion,true);

	//Recipe
	wp_register_script('simnigeria_plurarize_script',plugins_url('js/recipe.js', __DIR__), array(),$StyleVersion,true);
	
	//login form
	wp_enqueue_script('simnigeria_login_script', plugins_url('js/dist/login.js', __DIR__), array('simnigeria_script'), $StyleVersion, true);

	//events
	wp_register_script('simnigeria_event_script',plugins_url('js/events.js', __DIR__), array('simnigeria_forms_script'),$StyleVersion,true);

	//table request shortcode
	wp_register_script('simnigeria_table_script',plugins_url('js/table.js', __DIR__), array('sortable','simnigeria_forms_script'),$StyleVersion,true);
	
	wp_register_script('simnigeria_fingerprint_script',plugins_url('js/dist/main.js', __DIR__), array('simnigeria_forms_script','simnigeria_table_script'), $StyleVersion, true);

	//File upload js
	wp_register_script('simnigeria_fileupload_script',plugins_url('js/fileupload.js', __DIR__), array('simnigeria_forms_script'),$StyleVersion,true);

	//schedules page
	wp_register_script('simnigeria_schedule_script',plugins_url('js/schedules.js', __DIR__), array('simnigeria_table_script','selectable','simnigeria_forms_script'),$StyleVersion,true);
	
	//2FA page
	wp_register_script('simnigeria_2fa_script',plugins_url('js/account/2fa.js', __DIR__), array('simnigeria_fingerprint_script'),$StyleVersion,true);
	
	//Add compounds as variable to the script
	//$compound_map_id = $CustomSimSettings['compound_map'];
	//$query = $wpdb->prepare("SELECT `title`,`coord_x`,`coord_y`,`address` FROM {$wpdb->prefix}ums_markers WHERE `map_id` = %s",$compound_map_id);
	//$compound_markers = $wpdb->get_results($query,ARRAY_A);
	//wp_localize_script( 'simnigeria_location_script', 'simnigeria_account', array( 'compounds' => $compound_markers) );
	//wp_localize_script( 'simnigeria_location_script', 'simnigeria_account', array( 'compounds' => $NigeriaStates) );

	//Formbuilder js
	wp_register_script( 'simnigeria_formbuilderjs', plugins_url('js/formbuilder.js', __DIR__),array('simnigeria_forms_script','sortable','sweetalert'),$StyleVersion,true);
	
	//Frontend posting page
	wp_register_script('simnigeria_frontend_script',plugins_url('js/frontend_posting.js', __DIR__), array('simnigeria_fileupload_script'),$StyleVersion,true);
	
	//add main css, but only on non-admin pages
	if ($hook == ""){
		//style for tinymce
		add_editor_style(plugins_url('css/sim.min.css', __DIR__));
		//style fo main site
		wp_enqueue_style( 'simnigeria_style', plugins_url('css/sim.min.css', __DIR__), array(),$StyleVersion);
	}
	
    //Check if on the home page
	if (is_front_page() or is_page($LoggedInHomePage)){
		//Add header image selected in customizer to homepage using inline css
		$header_image_id = get_theme_mod( 'simnigeria_header_image');
		$header_image_url = wp_get_attachment_url($header_image_id);
		$extra_css = ".home:not(.sticky) #masthead{background-image: url($header_image_url);";
		wp_add_inline_style('simnigeria_style', $extra_css);
		//home.js
		wp_enqueue_script('simnigeria_home_script',plugins_url('js/home.js', __DIR__), array('sweetalert'),$StyleVersion,true);
	}
	
	//Get current users location
	$location = get_user_meta( $UserID, 'location', true );
	if (isset($location['address'])){
		$address = $location['address'];;
	}else{
		$address = "";
	}

	wp_localize_script( 'simnigeria_script', 
		'simnigeria', 
		array( 
			'ajax_url' 		=> admin_url( 'admin-ajax.php' ), 
			"logged_in"		=> is_user_logged_in(), 
			"firstname"		=> $current_user_first_name, 
			"userid"		=> $UserID,
			'address' 		=> $address,
			'loading_gif' 	=> $LoaderImageURL,
			'base_url' 		=> get_home_url(),
			'compounds' 	=> $NigeriaStates,
			'max_file_size'	=> wp_max_upload_size(),
			'restnonce'		=> wp_create_nonce('wp_rest')
		) 
	);
};

//Add js to registration page
add_action( 'login_enqueue_scripts', 'SIM\login_js' );
function login_js($hook) {
	global $StyleVersion;
	global $LoaderImageURL;

	$action = ( !empty( $_GET['action'] ) ) ? sanitize_text_field( $_GET['action'] ) : '';
	//Only add registration scripts on registration page
	if ($action=="register"){
		wp_enqueue_script('simnigeria_registration_script',plugins_url('js/registration.js', __DIR__), array(),$StyleVersion, true);
		wp_localize_script( 'simnigeria_registration_script', 'simnigeria', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'loading_gif' => $LoaderImageURL ));
	}		
}

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
	global $LoggedInHomePage;
		
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
