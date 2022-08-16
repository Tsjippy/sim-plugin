<?php
namespace SIM\FORMS;
use SIM;

add_action('init', function () {
	register_block_type(
		__DIR__ . '/formselector/build',
		array(
			'render_callback' => __NAMESPACE__.'\showFormSelector',
		)
	);

	register_block_type(
		__DIR__ . '/formbuilder/build',
		array(
			'render_callback' => __NAMESPACE__.'\showFormBuilder',
		)
	);
});

add_action( 'enqueue_block_editor_assets', function(){
	wp_enqueue_script( 'sim_formbuilderjs');
} );