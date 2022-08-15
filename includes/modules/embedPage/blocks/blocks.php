<?php
namespace SIM\EMBEDPAGE;
use SIM;

add_action('init', function () {
	register_block_type(
		__DIR__ . '/embedPage/build',
		array(
			'render_callback' => __NAMESPACE__.'\displayEmbedBlock',
		)
	);
});

function displayEmbedBlock($value){
	echo displayPageContents([$value['page']['id']]);
}