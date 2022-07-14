<?php
namespace SIM\PRAYER;
use SIM;

const MODULE_VERSION		= '7.0.0';
//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

//run on module activation
add_action('sim_module_activated', function($moduleSlug){
	//module slug should be the same as grandparent folder name
	if($moduleSlug != MODULE_SLUG)	{
		return;
	}

	scheduleTasks();
	
	wp_create_category('Prayer');
});

add_filter('sim_submenu_description', function($description, $moduleSlug){
	//module slug should be the same as the constant
	if($moduleSlug != MODULE_SLUG)	{
		return $description;
	}

	ob_start();

	?>
	<p>
		This module adds 1 url to the rest api:<br>
		sim/v1/prayermessage to get the current prayer request<br>
		It also adds 1 post category: 'Prayer'<br>
		You should add a new post with the prayer category each month.
		This post should have a prayer request for each day on seperate lines.<br>
		The lines should have this format: '1(T) – '<br>
		So an example will look like this:<br>
		<code>
			1(M) – Prayer for day 1<br>
			2(T) – Prayer for day 2
		</code>
		<br>
		<br>
		If such a post is available the daily prayerrequest will be displayed on the homepage and will be available via the rest-api.<br>

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
	
	
	if(empty($settings['groups'])){
		$groups	= [''];
	}else{
		$groups	= $settings['groups'];
	}

    ?>
	<div class="">
		<h4>Give optional Signal group name(s) to send a daily prayer message to:</h4>
		<div class="clone_divs_wrapper">
			<?php			
			foreach($groups as $index=>$group){
				?>
				<div class="clone_div" data-divid="<?php echo $index;?>" style="display:flex;border: #dedede solid; padding: 10px; margin-bottom: 10px;">
					<div class="multi_input_wrapper">
						<label>
							<h4 style='margin: 0px;'>Signal groupname <?php echo $index+1;?></h4>
							<input type='text' name="groups[<?php echo $index;?>][name]" value='<?php echo $group['name'];?>'>
						</label>
						<label>
							<h4 style='margin-bottom: 0px;'>Time the message should be send</h4>
							<input type='time' name="groups[<?php echo $index;?>][time]" value='<?php echo $group['time'];?>'>
						</label>
					</div>
					<div class='buttonwrapper' style='margin:auto;'>
						<button type="button" class="add button" style="flex: 1;">+</button>
						<?php
						if(count($groups)> 1){
							?>
							<button type="button" class="remove button" style="flex: 1;">-</button>
							<?php
						}
						?>
					</div>
				</div>
				<?php
			}
			?>
		</div>
	</div>

	<?php

	return ob_get_clean();
}, 10, 3);
