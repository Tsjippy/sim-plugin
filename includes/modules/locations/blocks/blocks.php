<?php
namespace SIM\LOCATIONS;
use SIM;

add_action('init', function () {
	register_block_type(
		__DIR__ . '/metadata/build',
		array(
			"attributes"	=>  [
				"lock"	=> [
					"type"	=> "object",
					"default"	=> [
						"move"		=> true,
						"remove"	=> true
					]
				],
				'tel'	=> [
					'type'	=> 'string',
					'default'	=> ''
				],
				'url'	=> [
					'type'	=> 'string',
					'default'	=> ''
				],
				'location'	=> [
					'type'	=> 'string',
					'default'	=> ''
				],
			]
		)
	);
});

// register custom meta tag field
add_action( 'init', function(){
	register_post_meta( 'location', 'tel', array(
        'show_in_rest' 		=> true,
        'single' 			=> true,
        'type' 				=> 'string',
		'sanitize_callback' => 'sanitize_text_field'
    ) );

	register_post_meta( 'location', 'url', array(
        'show_in_rest' 		=> true,
        'single' 			=> true,
        'type' 				=> 'string',
		'sanitize_callback' => 'sanitize_text_field'
    ) );

	register_post_meta( 'location', 'location', array(
        'show_in_rest' 		=> true,
        'single' 			=> true,
        'type' 				=> 'string',
		'sanitize_callback' => 'sanitize_text_field'
    ) );
} );