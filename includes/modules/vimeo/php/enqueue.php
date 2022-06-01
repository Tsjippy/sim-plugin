<?php
namespace SIM\VIMEO;
use SIM;

// admin js
add_action( 'admin_enqueue_scripts', function(){
	wp_register_script('sim_vimeo_admin_script', plugins_url('js/admin.js', __DIR__), ['sim_formsubmit_script', 'sim_script'], ModuleVersion);
	wp_localize_script( 'sim_vimeo_admin_script', 
		'sim', 
		array(
			'loadingGif' 	=> LOADERIMAGEURL,
			'baseUrl' 		=> get_home_url(),
			'restnonce'		=> wp_create_nonce('wp_rest')
		) 
	);
});


add_action( 'wp_enqueue_scripts', function(){
    // Load css
    wp_register_style( 'vimeo_style', plugins_url('css/style.css', __DIR__), array(), ModuleVersion);

	wp_register_script('sim_vimeo_script', plugins_url('js/vimeo.min.js', __DIR__), ['media-audiovideo', 'sweetalert'], ModuleVersion);

	wp_register_script('sim_vimeo_uploader_script', plugins_url('js/vimeo_upload.min.js', __DIR__), [], ModuleVersion);

	wp_register_script('sim_vimeo_shortcode_script', plugins_url('js/vimeo_shortcode.js', __DIR__), [], ModuleVersion);
});

//auto upload via js if enabled
if(SIM\getModuleOption('vimeo', 'upload')){
	//load js script to change media screen
	add_action( 'wp_enqueue_media', function(){
		wp_enqueue_script('sim_vimeo_script');
		wp_localize_script('sim_vimeo_script', 
			'media_vars', 
			array( 
				'loadingGif' 	=> LOADERIMAGEURL
			) 
		);
	});
}