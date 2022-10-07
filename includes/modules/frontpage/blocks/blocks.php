<?php
namespace SIM\FRONTPAGE;
use SIM;

add_action('init', function () {
	global $post;

	register_block_type(
		__DIR__ . '/displayname/build',
		array(
			'render_callback' => __NAMESPACE__.'\displayName',
		)
	);

	register_block_type(
		__DIR__ . '/login_count/build',
		array(
			'render_callback' => __NAMESPACE__.'\loginCount',
		)
	);

	register_block_type(
		__DIR__ . '/welcome/build',
		array(
			'render_callback' => __NAMESPACE__.'\welcomeMessage',
		)
	);
});

add_action( 'enqueue_block_editor_assets', function(){
	wp_enqueue_script('sim_home_script');
} );