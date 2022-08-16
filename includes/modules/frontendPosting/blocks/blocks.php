<?php
namespace SIM\FRONTENDPOSTING;
use SIM;

add_action('init', function () {
	register_block_type(
		__DIR__ . '/your_posts/build',
		array(
			'render_callback' => __NAMESPACE__.'\yourPosts',
		)
	);
});

add_action( 'enqueue_block_editor_assets', function(){
	wp_enqueue_script( 'sim_table_script');
} );