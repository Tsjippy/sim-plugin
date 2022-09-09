<?php
namespace SIM\LOCATIONS;
use SIM;

const MODULE_VERSION		= '7.0.2';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

// check for dependicies
add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();

	?>
	<p>
		This module adds a custom post type 'locations'.<br>
		Locations can be used to share shops, hotels ministries etc.<br>
		They will bevisible on a map.<br>
		<br>
		It adds one shortcode:<br>
		<code>[ministry_description name=SOMENAME]</code>
	</p>
	<p>
		<strong>Auto created page:</strong><br>
		<a href='<?php echo home_url('/locations');?>'>Locations</a><br>
	</p>
	<?php

	return ob_get_clean();
}, 10, 2);

add_filter('sim_submenu_options', function($optionsHtml, $moduleSlug, $settings){
	global $wpdb;

	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $optionsHtml;
	}

	ob_start();
	
	$query = 'SELECT * FROM `'.$wpdb->prefix .'ums_icons` WHERE 1';
	$icons = $wpdb->get_results($query);

	wp_enqueue_style('sim_locations_admin_style', plugins_url('css/admin.min.css', __DIR__), array(), MODULE_VERSION);
	wp_enqueue_script('sim_locations_admin_script', plugins_url('js/locations_admin.min.js', __DIR__), array(), MODULE_VERSION, true);

	echo "<label>";
		echo "Give Google API key for location lookup. See <a href='https://developers.google.com/maps/documentation/javascript/get-api-key'>here</a><br>";
		echo "<input type='text' name='google-maps-api-key' value='{$settings['google-maps-api-key']}' style='width:400px;'>";
	echo "</label><br>";
	
	$categories = get_categories( array(
		'orderby' 	=> 'name',
		'order'   	=> 'ASC',
		'taxonomy'	=> 'locations',
		'hide_empty'=> false,
	) );
	foreach($categories as $locationtype){
		$name 				= $locationtype->slug;
		$mapName			= $name."_map";
		$iconName			= $name."_icon";
		?>
		<label for="<?php echo $mapName;?>">Map showing <?php echo strtolower($name);?></label>
		<select name="<?php echo $mapName;?>" id="<?php echo $mapName;?>">
			<option value="">---</option>
			<?php echo getMaps($settings[$mapName]); ?>
		</select>
		
		<label>Icon on the map used for <?php echo $name;?></label>
		<div class='icon_select_wrapper'>
			<input type='hidden' class='icon_url' name='<?php echo $iconName;?>' value='<?php echo $settings[$iconName];?>'>
			<br>
			<div class="dropdown">
				<?php
				if($settings[$iconName]){
					$img			= "<img src='{$settings[$iconName]}' class='icon' loading='lazy'>";
					$button_text	= "Change";
				}else{
					$img	= "";
					$button_text	= "Select";
				}
				?>
				<div class="icon_preview">
					<?php echo $img;?>
				</div>

				<button type='button' class='dropbtn'><?php echo $button_text;?> Icon</button>";

				<div class="dropdown-content">
					<?php
					foreach($icons as $icon){
						if($icon->description == 'custom icon'){
							continue;
						}
						$url = plugins_url('ultimate-maps-by-supsystic/modules/icons/icons_files/def_icons/'.$icon->path);
						echo "<div class='icon'><img src='$url' class='icon' loading='lazy'> $icon->description</div><br>";
					}
					?>
				</div>
			</div>
		</div>
		<br>
		<br>
		<?php
	}
	?>

	<?php
	return ob_get_clean();
}, 10, 3);

/**
 * Location maps
 */
function getMaps($optionValue){
	$mapOptions = "";

	$maps	= new Maps();
	
	foreach ( $maps->getMaps() as $map ) {
		if ($optionValue == $map->id){
			$selected='selected=selected';
		}else{
			$selected="";
		}
		$mapOptions .= "<option value='$map->id' $selected>$map->title</option>";
	}
	return $mapOptions;
}

//run on module activation
add_filter('sim_module_updated', function($options, $moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $options;
	}

	SIM\ADMIN\installPlugin('ultimate-maps-by-supsystic/ums.php');

	$maps		= new Maps();

	foreach(['Directions', 'Users'] as $title){
		$mapKey	= strtolower($title).'_map_id';

		//Check if defined in settings already
		if($options[$mapKey]){
			// Double check the defined map exists
			$map	= $maps->getMaps("`id`='{$options[$mapKey]}'");

			// map exist
			if(!empty($map)){
				continue;
			}

		}

		// create the map
		$options[$mapKey]	= $maps->addMap($title, '9.910260', '8.889170', '', '400', 6);
	}

	return $options;
}, 10, 2);