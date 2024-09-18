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

add_action( 'enqueue_block_assets', function(){
	if(is_admin()){
		SIM\enqueueScripts();

		SIM\registerScripts();
		
		wp_enqueue_script( 'sim_formbuilderjs');
	}
} );

add_action( 'enqueue_block_editor_assets', function() {
    wp_enqueue_script(
        'sim-signal-block',
        plugins_url('blocks/signal_options/build/index.js', __DIR__),
        [ 'wp-blocks', 'wp-dom', 'wp-dom-ready', 'wp-edit-post' ],
        STYLE_VERSION
    );
});