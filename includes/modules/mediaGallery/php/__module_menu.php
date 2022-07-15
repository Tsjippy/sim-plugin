<?php
namespace SIM\MEDIAGALLERY;
use SIM;

const MODULE_VERSION		= '7.0.22';
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
	$pageId	= SIM\getModuleOption($moduleSlug, 'mediagallery_pages')[0];
	if(is_numeric($pageId) && get_post_status($pageId) == 'publish'){
		?>
		<p>
			<strong>Auto created page:</strong><br>
			<a href='<?php echo get_permalink($pageId);?>'>Media gallery</a><br>
		</p>
		<?php
	}

	return ob_get_clean();
}, 10, 2);

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
