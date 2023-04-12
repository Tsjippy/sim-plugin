<?php
namespace SIM\PAGEGALLERY;
use SIM;

add_action('init', function () {
	global $post;

	register_block_type(
		__DIR__ . '/gallery/build',
		array(
			'render_callback' => function($attributes){
				$categories	= json_decode($attributes['categories'], true);
				return pageGallery($attributes['title'], $attributes['postTypes'], $attributes['amount'], $categories, $attributes['speed'], true, $attributes['color']);
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
				],
				'color' => [
					'type' 		=> 'string',
					'default'	=> '#FFFFFF'
				],
			]
		)
	);
});