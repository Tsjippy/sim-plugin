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

	register_block_type(
		__DIR__ . '/gallery/build',
		array(
			'render_callback' => function($attributes){
				$categories	= json_decode($attributes['categories'], true);
				return pageGallery($attributes['title'], $attributes['postTypes'], $attributes['amount'], $categories, $attributes['speed']);
			},
			"attributes"	=>  [
				'postTypes'	=> [
					'type'		=> 'array',
					'default'	=> []
				],
				'amount'	=> [
					'type'		=> 'integer',
					'default'	=> 3
				],
				'categories'	=> [
					'type'		=> 'string',
					'default'	=> '{}'
				],
				'speed'	=> [
					'type'		=> 'integer',
					'default'	=> 60
				],
				'title'	=> [
					'type'		=> 'string',
					'default'	=> ''
				]
			]
		)
	);
});

add_action( 'enqueue_block_editor_assets', function(){
	wp_enqueue_script('sim_home_script');
} );