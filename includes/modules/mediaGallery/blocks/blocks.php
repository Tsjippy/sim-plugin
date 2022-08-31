<?php
namespace SIM\MEDIAGALLERY;
use SIM;

add_action('init', function () {
	register_block_type(
		__DIR__ . '/media-gallery/build',
		array(
			'render_callback' => __NAMESPACE__.'\showMediaGallery',
			'attributes'      => [
				'categories' => [
					'type' 		=> 'array',
					'default'	=> []
				]
			]
		)
	);
});

add_action( 'enqueue_block_editor_assets', function(){
	do_action( 'wp_enqueue_scripts');
	wp_enqueue_script( 'sim_gallery_script');
	wp_enqueue_script('sim_vimeo_shortcode_script');
} );
