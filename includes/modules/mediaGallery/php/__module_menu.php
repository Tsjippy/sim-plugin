<?php
namespace SIM\MEDIAGALLERY;
use SIM;

const MODULE_VERSION		= '7.0.27';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();
	?>
	<p>
		This module adds a media gallery of downloadable pictures, video's and audio files.
	</p>
	<?php
	$url		= SIM\ADMIN\getDefaultPageLink($moduleSlug, 'mediagallery_pages');
	if(!empty($url)){
		?>
		<p>
			<strong>Auto created page:</strong><br>
			<a href='<?php echo $url;?>'>Media gallery</a><br>
		</p>
		<?php
	}

	return ob_get_clean();
}, 10, 2);

add_filter('sim_submenu_options', function($optionsHtml, $moduleSlug, $settings){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();
    ?>
	<label>Select the categories you do not want to be selectable</label>
	<br>
	<?php
	$categories	= get_categories( array(
		'orderby' 		=> 'name',
		'order'   		=> 'ASC',
		'taxonomy'		=> 'attachment_cat',
		'hide_empty' 	=> false,
	) );

	foreach($categories as $category){
		$checked	= '';
		if(is_array($settings['categories']) && in_array($category->slug, $settings['categories'])){
			$checked	= 'checked';
		}
		echo "<label>";
			echo "<input type='checkbox' name='categories[]' value='$category->slug' $checked>";
			echo $category->name;
		echo "</label><br>";
	}
	?>
	<?php

	return ob_get_clean();
}, 10, 3);

add_filter('sim_module_updated', function($options, $moduleSlug, $oldOptions){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $options;
	}

	// Create account page
	$options	= SIM\ADMIN\createDefaultPage($options, 'mediagallery_pages', 'Media Gallery', '[mediagallery]', $oldOptions);

	return $options;
}, 10, 3);

add_filter('display_post_states', function ( $states, $post ) {
    
	if ( in_array($post->ID, SIM\getModuleOption(MODULE_SLUG, 'mediagallery_pages')) ) {
		$states[] = __('Media gallery page');
	}

	return $states;
}, 10, 2);

add_action('sim_module_deactivated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{
		return;
	}

	foreach($options['mediagallery_pages'] as $page){
		// Remove the auto created page
		wp_delete_post($page, true);
	}
}, 10, 2);
