<?php
namespace SIM\LOCATIONS;
use SIM;

add_action('sim_submenu_description', function($module_slug, $module_name){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<p>
		This module adds a custom post type 'locations'.<br>
		Locations can be used to share shops, hotels ministries etc.<br>
		They will bevisible on a map.				
	</p>
	<?php
},10,2);

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	global $wpdb;
	global $StyleVersion;

	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;
	
	$query = 'SELECT * FROM `'.$wpdb->prefix .'ums_icons` WHERE 1';
	$icons = $wpdb->get_results($query);

	wp_enqueue_style('sim_locations_admin_style', plugins_url('css/admin.min.css', __DIR__), array(), $StyleVersion);
	wp_enqueue_script('sim_locations_admin_script', plugins_url('js/locations_admin.min.js', __DIR__), array(), $StyleVersion, true);
	?>
    <p>
		Below you can select the map and icon id for each location category.
	</p>
	<label for="placesmapid">Map showing all markers</label>
	<select name="placesmapid" id="placesmapid">
		<option value="">---</option>
		<?php echo get_maps($settings["placesmapid"]); ?>
	</select>
	<br>
	<br>

	<label for="missionariesmapid">Map showing all missionaries</label>
	<select name="missionariesmapid" id="missionariesmapid">
		<option value="">---</option>
		<?php echo get_maps($settings["missionariesmapid"]); ?>
	</select>
	<br>
	<br>
				
	<?php
	
	$categories = get_categories( array(
		'orderby' 	=> 'name',
		'order'   	=> 'ASC',
		'taxonomy'	=> 'locationtype',
		'hide_empty'=> false,
	) );
	foreach($categories as $locationtype){
		$name 				= $locationtype->slug;
		$map_name			= $name."_map";
		$icon_name			= $name."_icon";
		?>
		<label for="<?php echo $map_name;?>">Map showing <?php echo strtolower($name);?></label>
		<select name="<?php echo $map_name;?>" id="<?php echo $map_name;?>">
			<option value="">---</option>
			<?php echo get_maps($settings[$map_name]); ?>
		</select>
		
		<label>Icon on the map used for <?php echo $name;?></label>
		<div class='icon_select_wrapper'>
			<input type='hidden' class='icon_url' name='<?php echo $icon_name;?>' value='<?php echo $settings[$icon_name];?>'>
			<br>
			<div class="dropdown">
				<?php
				if($settings[$icon_name]){
					$img			= "<img src='{$settings[$icon_name]}' class='icon'>";
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
						if($icon->description == 'custom icon') continue;
						$url = plugins_url('ultimate-maps-by-supsystic/modules/icons/icons_files/def_icons/'.$icon->path);
						echo "<div class='icon'><img src='$url' class='icon'> $icon->description</div><br>";
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
}, 10, 3);

//Location maps
function get_maps($option_value){
	global $wpdb;
	$map_options = "";

	$query = 'SELECT  `id`,`title` FROM `'.$wpdb->prefix .'ums_maps` WHERE 1  ORDER BY `title` ASC';
	$result = $wpdb->get_results($query);
	foreach ( $result as $map ) {
		if ($option_value == $map->id){
			$selected='selected=selected';
		}else{
			$selected="";
		}
		$map_options .= "<option value='$map->id' $selected>$map->title</option>";
	}
	return $map_options;
}