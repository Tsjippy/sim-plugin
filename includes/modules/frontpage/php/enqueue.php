<?php
namespace SIM\FRONTPAGE;
use SIM;

add_action( 'wp_enqueue_scripts', function($hook){
	if(is_page(SIM\get_module_option('frontpage','home_page')) or is_front_page()){
		wp_enqueue_style( 'sim_frontpage_style', plugins_url('css/frontpage.min.css', __DIR__), array(), ModuleVersion);

		//Add header image selected in customizer to homepage using inline css
		$header_image_id	= SIM\get_module_option('frontpage', 'picture_ids')['header_image'];
		$header_image_url	= wp_get_attachment_url($header_image_id);
		if($header_image_url){
			$extra_css			= ".home:not(.sticky) #masthead{background-image: url($header_image_url);";
			wp_add_inline_style('sim_frontpage_style', $extra_css);
		}
		
		//home.js
		wp_enqueue_script('sim_home_script',plugins_url('js/home.min.js', __DIR__), array('sweetalert'), ModuleVersion, true);
	}
});