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
				'ingredients'	=> [
					'type'		=> 'string',
					'default'	=> ''
				],
				'time_needed'	=> [
					'type'		=> 'integer',
					'default'	=> 60
				],
				'serves'	=> [
					'type'		=> 'integer',
					'default'	=> 4
				],
			]
		)
	);
});

// register custom meta tag field
add_action( 'init', function(){
	register_post_meta( 'recipe', 'ingredients', array(
        'show_in_rest' 	=> true,
        'single' 		=> true,
        'type' 			=> 'string',
		'sanitize_callback' => 'sanitize_text_field'
    ) );

	register_post_meta( 'recipe', 'time_needed', array(
        'show_in_rest' 	=> true,
        'single' 		=> true,
        'type' 			=> 'integer',
		'sanitize_callback' => 'sanitize_text_field'
    ) );

	register_post_meta( 'recipe', 'serves', array(
        'show_in_rest' 	=> true,
        'single' 		=> true,
        'type' 			=> 'integer',
		'sanitize_callback' => 'sanitize_text_field'
    ) );
} );