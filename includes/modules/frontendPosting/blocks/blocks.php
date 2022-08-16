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

	register_block_type(
		__DIR__ . '/pending_pages/build',
		array(
			'render_callback' => __NAMESPACE__.'\pendingPages',
		)
	);
});

add_action( 'enqueue_block_editor_assets', function(){
	wp_enqueue_script( 'sim_table_script');
} );