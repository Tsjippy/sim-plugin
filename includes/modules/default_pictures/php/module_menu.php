<?php
namespace SIM\DEFAULTPICTURE;
use SIM;

const MODULE_VERSION		= '7.0.0';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', basename(dirname(dirname(__FILE__))));

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG){
		return $description;
	}

	ob_start();
	?>
	<p>
		This module allows you to set a default picture for each category on the website.<br>
		This picture will be used in case content gets created with this category set and no featured image is set.
	</p>
	<?php

	return ob_get_clean();
}, 10, 2);

add_filter('sim_submenu_options', function($optionsHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();

	//Get all post types
	$args = array(
		'public'   => true,
		'_builtin' => false
	 );	   
	$postTypes = array_merge(get_post_types( $args, 'names', 'and' ), ['post']);

	foreach($postTypes as $postType){
		if($postType == 'post'){
			$tax	= 'category';
		}else{
			$tax	= $postType.'type';
		}
		echo "<h3>Default pictures for {$postType}s</h3>";
		echo "<h4>Default picture for $postType</h4>";
		SIM\pictureSelector($postType, ucfirst($postType), $settings);
		echo "<h4>Default pictures per category for {$postType}s</h4>";
		$categories	= get_terms(['hide_empty' => false, 'taxonomy'=>$tax]);
		foreach($categories as $category){
			SIM\pictureSelector($category->slug, $category->name, $settings);
		}
		echo '<br><br>';
	}

	return ob_get_clean();
}, 10, 3);