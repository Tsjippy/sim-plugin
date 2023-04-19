<?php
namespace SIM\VIMEO;
use SIM;

// admin js
add_action( 'admin_enqueue_scripts', function(){
	wp_register_script('sim_vimeo_admin_script', plugins_url('js/admin.min.js', __DIR__), ['sim_formsubmit_script', 'sim_script'], MODULE_VERSION);
	wp_localize_script( 'sim_vimeo_admin_script',
		'sim',
		array(
			'loadingGif' 	=> LOADERIMAGEURL,
			'baseUrl' 		=> get_home_url(),
			'restNonce'		=> wp_create_nonce('wp_rest')
		)
	);
});


add_action( 'wp_enqueue_scripts', __NAMESPACE__.'\enqueueVimeoScripts');
add_action( 'admin_enqueue_scripts', __NAMESPACE__.'\enqueueVimeoScripts');
function enqueueVimeoScripts(){
    // Load css
    wp_register_style( 'vimeo_style', plugins_url('css/style.css', __DIR__), array(), MODULE_VERSION);

	wp_register_script('sim_vimeo_player', 'https://player.vimeo.com/api/player.js', [], false, true);

	wp_register_script('sim_vimeo_script', plugins_url('js/vimeo.min.js', __DIR__), ['sim_vimeo_player','media-audiovideo', 'sweetalert', 'sim_script'], MODULE_VERSION, true);
	wp_localize_script('sim_vimeo_script',
		'media_vars',
		array(
			'loadingGif' 	=> LOADERIMAGEURL
		)
	);

	wp_register_script('sim_vimeo_uploader_script', plugins_url('js/vimeo_upload.min.js', __DIR__), ['sim_script', 'sim_formsubmit_script'], MODULE_VERSION, true);

	if($_SERVER['PHP_SELF'] == "/simnigeria/wp-admin/upload.php"){
		wp_enqueue_script('sim_vimeo_script');
	}
}

//auto upload via js if enabled
if(SIM\getModuleOption(MODULE_SLUG, 'upload')){
	//load js script to change media screen
	add_action( 'wp_enqueue_media', function(){
		wp_enqueue_script('sim_vimeo_script');
	});
}