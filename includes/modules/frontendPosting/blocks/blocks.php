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
	SIM\registerScripts();

	wp_enqueue_script( 'sim_table_script');

    wp_enqueue_script(
        'sim-expiry-date-block',
        plugins_url('blocks/expiry-date/build/index.js', __DIR__),
        [ 'wp-blocks', 'wp-dom', 'wp-dom-ready', 'wp-edit-post' ],
        MODULE_VERSION
    );
});

// register custom meta tag field
add_action( 'init', function(){
	register_post_meta( '', 'expirydate', array(
        'show_in_rest' 	    => true,
        'single' 		    => true,
        'type' 			    => 'string',
		'default'			=> '',
		'sanitize_callback' => 'sanitize_text_field'
    ) );

	register_post_meta( '', 'static_content', array(
        'show_in_rest' 	    => true,
        'single' 		    => true,
        'type' 			    => 'boolean',
		'default'			=> false,
		'sanitize_callback' => 'sanitize_text_field'
    ) );
} );