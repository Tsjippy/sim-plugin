<?php
namespace SIM\VIMEO;
use SIM;

add_action( 'wp_enqueue_scripts', function(){
    // Load css
    wp_register_style( 'vimeo_style', plugins_url('css/style.css', __DIR__), array(), ModuleVersion);
});

//auto upload via js if enabled
if(SIM\get_module_option('vimeo', 'upload')){
	//load js script to change media screen
	add_action( 'wp_enqueue_media', function(){
		global $LoaderImageURL;
		wp_enqueue_script('sim_media_script', IncludesUrl.'/modules/vimeo/js/vimeo.min.js', ['sim_other_script','media-audiovideo', 'sweetalert'], ModuleVersion);
		wp_localize_script('sim_media_script', 
			'media_vars', 
			array( 
				'loading_gif' 	=> $LoaderImageURL,
				'ajax_url' 		=> admin_url( 'admin-ajax.php' ), 
			) 
		);
	});
}