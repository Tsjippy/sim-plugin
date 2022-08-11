<?php
namespace SIM\BANKING;
use SIM;

add_action('init', function () {
	register_block_type(
		__DIR__ . '/../blocks/statements/build',
		array(
			'render_callback' => __NAMESPACE__.'\showStatements',
		)
	);
});