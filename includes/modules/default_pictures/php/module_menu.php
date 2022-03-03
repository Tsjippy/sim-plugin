<?php
namespace SIM\DEFAULTPICTURE;
use SIM;

const ModuleVersion		= '7.0.0';

add_action('sim_submenu_description', function($module_slug, $module_name){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<p>
		This module allows you to set a default picture for each category on the website.<br>
		This picture will be used in case content gets created with this category set and no featured image is set.
	</p>
	<?php
},10,2);

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
	wp_enqueue_media();
	wp_enqueue_script('sim_default_pictures',plugins_url('js/select_picture.js', __DIR__), array(), ModuleVersion,true);
	wp_enqueue_style( 'sim_default_pictures_style', plugins_url('css/default_pictures.min.css', __DIR__), array(), ModuleVersion);

	//Get all post types
	$args = array(
		'public'   => true,
		'_builtin' => false
	 );	   
	$post_types = array_merge(get_post_types( $args, 'names', 'and' ), ['post']);

	foreach($post_types as $post_type){
		if($post_type == 'post'){
			$tax	= 'category';
		}else{
			$tax	= $post_type.'type';
		}
		echo "<h3>Default pictures for {$post_type}s</h3>";
		echo "<h4>Default picture for $post_type</h4>";
		default_picture_selector($post_type, ucfirst($post_type), $settings);
		echo "<h4>Default pictures per category for {$post_type}s</h4>";
		$categories	= get_terms(['hide_empty' => false, 'taxonomy'=>$tax]);
		foreach($categories as $category){
			default_picture_selector($category->slug, $category->name, $settings);
		}
		echo '<br><br>';
	}	
}, 10, 3);

function default_picture_selector($key, $name, $settings){
	if(empty($settings['picture_ids'][$key])){
		$hidden		= 'hidden';
		$src		= '';
		$id			= '';
	}else{
		$id			= $settings['picture_ids'][$key];
		$src		= wp_get_attachment_image_url($id);
		$hidden		= '';
	}
	?>
	<div class='picture_selector_wrapper'>
		<div class='image-preview-wrapper <?php echo $hidden;?>'>
			<img class='image-preview' src='<?php echo $src;?>'>
		</div>
		<input type="button" class="button select_image_button" value="Select picture for <?php echo strtolower($name);?>" />
		<input type='hidden' class="image_attachment_id" name='picture_ids[<?php echo $key;?>]' value='<?php echo $id;?>'>
	</div>
	<?php
}