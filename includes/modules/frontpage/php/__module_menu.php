<?php
namespace SIM\FRONTPAGE;
use SIM;

const MODULE_VERSION		= '7.0.4';
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
		This module add a news gallery to the homepage as well as an gallery of the last added content.<br>
		It also adds a homepage for logged in users, where users will be redirected on login.
	</p>

	<?php
	$url		= SIM\ADMIN\getDefaultPageLink($moduleSlug, 'home_page');
	if(!empty($url)){
		?>
		<p>
			<strong>Auto created page:</strong><br>
			<a href='<?php echo $url;?>'>Home page for logged in users</a>
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
	<h4>Homepage buttons</h4>
	<label>
		Hook used in your template for the header<br>
		Used to insert two buttons in the homepage header.<br>
		<input type="text" name="header_hook" value="<?php echo $settings['header_hook'];?>">
	</label>
	<br>

	<br>
	<?php
	SIM\pictureSelector('header_image',  'Header image frontpage', $settings);
	?>
	
	<h5> First button</h5>
	Select the page you want to connect to the first button.<br>
	<?php
	echo SIM\pageSelect("first_button", $settings["first_button"]);
	?>

	<h5> Second button</h5>
	Select the page you want to connect to the second button.<br>
	<?php
	echo SIM\pageSelect("second_button", $settings["second_button"]);
	?>

	<h4>News feed</h4>
	<label>
		Hook used in your template after the main content.<br>
		Used to display a news feed on the homepage.<br>
		<input type="text" name="after_main_content_hook" value="<?php echo $settings['after_main_content_hook'];?>">
	</label>
	<br>

	<label>Post types to be included in the news gallery</label><br>
		<?php
		foreach(get_post_types(['public' => true]) as $type){
			if(in_array($type, $settings['news_post_types'])){
				$checked	= 'checked';
			}else{
				$checked	= '';
			}
			echo "<label>";
				echo "<input type='checkbox' name='news_post_types[]' value='$type' $checked> ".ucfirst($type)."<br>";
			echo "</label>";
		}
		?>
	<br>

	<label>Max news age of news items.</label>
	<select name="max_news_age">
		<option value="1 day" <?php if($settings['max_news_age'] == '1 day'){echo 'selected';}?>>One day</option>
		<option value="1 week" <?php if($settings['max_news_age'] == '1 week'){echo 'selected';}?>>One week</option>
		<option value="2 weeks" <?php if($settings['max_news_age'] == '2 weeks'){echo 'selected';}?>>Two weeks</option>
		<option value="1 month" <?php if($settings['max_news_age'] == '1 month'){echo 'selected';}?>>One month</option>
		<option value="2 months" <?php if($settings['max_news_age'] == '2 months'){echo 'selected';}?>>Two months</option>
	</select>
	<br>

	<h4>Highlighted pages gallery</h4>
	<label>
		Hook used in your template before the footer.<br>
		Used to display a galery of pages you want to highlight.<br>
		<input type="text" name="before_footer_hook" value="<?php echo $settings['before_footer_hook'];?>">
	</label>
	<br>
	<label>
		Select three different pages below. Optionally you can give cutom titles and summaries.<br>
		If these fields are empty the page title and content will be used.
	</label>
	<?php
	for ($x = 1; $x <= 3; $x++) {
		?>
		<h5> Highlight page <?php echo $x;?></h5>
		Select the page you want to show on frontpage.<br>
		<?php
		echo SIM\pageSelect("page$x", $settings["page$x"]);
		?>
		<label>
			Type a short title (optional).<br>
			<input type="text" name="title<?php echo $x;?>" value="<?php echo $settings["title$x"];?>">
		</label>
		<br>
		<label>
			Type a short description (optional).<br>
			<textarea name="description<?php echo $x;?>">
				<?php echo $settings["description$x"];?>
			</textarea>
		</label>
		<br>

		<?php
	}
	?>
	<br>

	<label>
		Welcome message on homepage
		<?php
		$tinyMceSettings = array(
			'wpautop' 					=> false,
			'media_buttons' 			=> false,
			'forced_root_block' 		=> true,
			'convert_newlines_to_brs'	=> true,
			'textarea_name' 			=> "welcome_message",
			'textarea_rows' 			=> 10
		);

		echo wp_editor(
			$settings["welcome_message"],
			"welcome_message",
			$tinyMceSettings
		);
		?>
	</label>
	<?php
	return ob_get_clean();
}, 10, 3);

add_filter('sim_module_updated', function($options, $moduleSlug, $oldOptions){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG){
		return $options;
	}

	// Create frontend posting page
	$content	= 'Hi [displayname],<br><br>I hope you have a great day!<br><br>[logged_home_page]<br><br>[welcome]';
	$options	= SIM\ADMIN\createDefaultPage($options, 'home_page', 'Home', $content, $oldOptions, ['post_name'=>'lhome']);

	return $options;
}, 10, 3);

add_filter('display_post_states', function ( $states, $post ) { 
    
    if(in_array($post->ID, SIM\getModuleOption(MODULE_SLUG, 'home_page')) ) {
        $states[] = __('Home page for logged in users'); 
    } 

    return $states;
}, 10, 2);

add_action('sim_module_deactivated', function($moduleSlug, $options){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{
		return;
	}

	foreach($options['home_page'] as $page){
		// Remove the auto created page
		wp_delete_post($page, true);
	}
}, 10, 2);