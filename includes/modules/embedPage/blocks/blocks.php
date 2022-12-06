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

	register_block_type(
		__DIR__ . '/embedExternalPage/build',
		array(
			'render_callback' => __NAMESPACE__.'\externalblock',
		)
	);
});

function displayEmbedBlock($value){
	echo displayPageContents([$value['page']['id']]);
}

function externalblock($attributes){
	if(!empty($attributes['url'])){
		echo "<iframe src='{$attributes['url']}' sandbox=''></iframe>";
	}
}