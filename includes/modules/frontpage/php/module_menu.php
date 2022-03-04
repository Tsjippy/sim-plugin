<?php
namespace SIM\FRONTPAGE;
use SIM;

add_action('sim_submenu_options', function($module_slug, $module_name, $settings){
	//module slug should be the same as grandparent folder name
	if($module_slug != basename(dirname(dirname(__FILE__))))	return;

	?>
	<h4>Homepage buttons</h4>
	<label>
		Hook used in your template for the header<br>
		Used to insert two buttons in the homepage header.<br>
		<input type="text" name="header_hook" value="<?php echo $settings['header_hook'];?>">
	</label>
	<br>
	
    <label>
		First button text.<br>
		<input type="text" name="first_button_text" value="<?php echo $settings['first_button_text'];?>">
	</label>
	<br>
	<label>
		First button url.<br>
		<input type="text" name="first_button_url" value="<?php echo $settings['first_button_url'];?>">
	</label>
	<br>

	<label>
		Second button text.<br>
		<input type="text" name="second_button_text" value="<?php echo $settings['second_button_text'];?>">
	</label>
	<br>
	<label>
		Second button url.<br>
		<input type="text" name="second_button_url" value="<?php echo $settings['second_button_url'];?>">
	</label>
	<br>
	<br>

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
		<option value="1 day" <?php if($settings['max_news_age'] == '1 day') echo 'selected';?>>One day</option>
		<option value="1 week" <?php if($settings['max_news_age'] == '1 week') echo 'selected';?>>One week</option>
		<option value="2 weeks" <?php if($settings['max_news_age'] == '2 weeks') echo 'selected';?>>Two weeks</option>
		<option value="1 month" <?php if($settings['max_news_age'] == '1 month') echo 'selected';?>>One month</option>
		<option value="2 months" <?php if($settings['max_news_age'] == '2 months') echo 'selected';?>>Two months</option>
	</select>
	<br>

	<h4>Highlighted pages gallery</h4>
	<label>
		Hook used in your template before the footer.<br>
		Used to display a galery of pages you want to highlight.<br>
		<input type="text" name="before_footer_hook" value="<?php echo $settings['before_footer_hook'];?>">
	</label>
	
	<?php
}, 10, 3);