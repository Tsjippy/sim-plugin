<?php
namespace SIM\FRONTPAGE;
use SIM;

add_action( 'wp_enqueue_scripts', function(){
	wp_register_script('sim_home_script', plugins_url('js/home.min.js', __DIR__), array('sweetalert'), MODULE_VERSION, true);

	if(in_array(get_the_ID(), SIM\getModuleOption(MODULE_SLUG,'home_page', 'array')) || is_front_page()){
		wp_enqueue_style( 'sim_frontpage_style', plugins_url('css/frontpage.min.css', __DIR__), array(), MODULE_VERSION);

		//Add header image selected in customizer to homepage using inline css
		$headerImageId	= SIM\getModuleOption(MODULE_SLUG, 'picture_ids')['header_image'];
		$headerImageUrl	= wp_get_attachment_url($headerImageId);
		if($headerImageUrl){
			$extraCss			= ".home:not(.sticky) #masthead{background-image: url($headerImageUrl);";
			wp_add_inline_style('sim_frontpage_style', $extraCss);
		}
		
		//home.js
		wp_enqueue_script('sim_home_script');
	}
});