<?php
namespace SIM\MANDATORY;
use SIM;

add_action('init', function () {
	register_block_type(
		__DIR__ . '/upcomingEvents/build',
		array(
			'render_callback' => __NAMESPACE__.'\mustReadDocuments',
		)
	);
});

add_action( 'enqueue_block_editor_assets', function(){
	wp_enqueue_script( 'sim_mandatory_script');
} );