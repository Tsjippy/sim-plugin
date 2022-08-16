<?php
namespace SIM\EVENTS;
use SIM;

add_action('init', function () {
	register_block_type(
		__DIR__ . '/upcomingEvents/build',
		array(
			'render_callback' => __NAMESPACE__.'\displayUpcomingEvents',
			'attributes'      => [
				'formid' => [
					'type' => 'string'
				],
				'onlyOwn'  => [
					'type'  => 'boolean',
					'default' => false,
				],
				'archived'  => [
					'type'  => 'boolean',
					'default' => false,
				],
				'tableid'  => [
					'type'  => 'integer'
				],
			]
		)
	);
});

add_action( 'enqueue_block_editor_assets', function(){
	wp_enqueue_script( 'sim_formbuilderjs');
} );