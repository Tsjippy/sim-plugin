<?php
namespace SIM\EVENTS;
use SIM;

add_filter('sim_frontend_posting_modals', function($types){
	$types[]	= 'event';
	return $types;
});

add_action('init', function(){
	SIM\register_post_type_and_tax('event', 'events');
	add_action('frontend_post_before_content', __NAMESPACE__.'\event_specific_fields');
	add_action('frontend_post_content_title', __NAMESPACE__.'\event_title');
	
	add_filter(
		'widget_categories_args',
		function ( $cat_args, $instance  ) {
			//if we are on a events page, change to display the event types
			if(is_tax('events') or is_page('event') or get_post_type()=='event'){
				$cat_args['taxonomy'] 		= 'events';
				$cat_args['hierarchical']	= true;
				$cat_args['hide_empty'] 	= false;
			}
			
			return $cat_args;
		},
		10, 
		2 
	);
}, 999);

function event_title($post_type){
	$class = 'event';
	if($post_type != 'event')	$class .= ' hidden';
	
	echo "<label class='$class' name='event_content_label'>";
		echo '<h4>Describe the event</h4>';
	echo "</label>";
}

function event_specific_fields($frontEndContent){
	$categories	= get_categories( array(
		'orderby' => 'name',
		'order'   => 'ASC',
		'taxonomy'=> 'events',
		'hide_empty' => false,
	) );
	
	$frontEndContent->showCategories('event', $categories);
	
	$eventdetails	= (array)get_post_meta($frontEndContent->postId,'eventdetails',true);
	$repeat_param	= $eventdetails['repeat'];
	
	?>
	<br>
	<div class="event <?php if($frontEndContent->postType != 'event') echo 'hidden'; ?>">
		<label>
			<input type='checkbox' name='event[allday]' value='allday' <?php if(!empty($eventdetails['allday'])) echo 'checked'?> ;>
			All day event
		</label>
	
		<label name="startdate_label">
			<h4>Startdate</h4>
			<input type='date'						name='event[startdate]' value='<?php echo $eventdetails['startdate']; ?>' required>
			<input type='time' class='eventtime<?php if(!empty($eventdetails['allday'])) echo " hidden";?>'	name='event[starttime]' value='<?php echo $eventdetails['starttime']; ?>' required>
		</label>
		
		<label name="enddate_label" <?php if(!empty($eventdetails['allday'])) echo "class='hidden'";?>>
			<h4>Enddate</h4>
			<input type='date'						name='event[enddate]' value='<?php echo $eventdetails['enddate']; ?>' required>
			<input type='time' class='eventtime'	name='event[endtime]' value='<?php echo $eventdetails['endtime']; ?>' required>
		</label>

		<label name="location">
			<h4>Location</h4>
			<input type='hidden' class='datalistvalue'	name='event[location_id]' 	value='<?php echo $eventdetails['location_id']; ?>'>
			<input type='text'							name='event[location]' 		value='<?php echo $eventdetails['location']; ?>' list="locations">
			<datalist id="locations">
				<?php
				$locations = get_posts( 
					array(
						'post_type'      	=> 'location', 
						'posts_per_page'	=> -1,
						'orderby'			=> 'title',
						'order' 			=> 'ASC'
					) 
				);
				foreach($locations as $location){
					echo "<option data-value='{$location->ID}' value='{$location->post_title}'></option>";
				}
				?>
			</datalist>
		</label>

		<label name="organizer">
			<h4>Organizer</h4>
			<input type='hidden' class='datalistvalue'	name='event[organizer_id]'	value='<?php echo $eventdetails['organizer_id']; ?>'>
			<input type='text'							name='event[organizer]'		value='<?php echo $eventdetails['organizer']; ?>' list="users">
			<datalist id="users">
				<?php
				foreach(SIM\get_user_accounts(false,true,true) as $user){
					echo "<option data-value='{$user->ID}' value='{$user->display_name}'></option>";
				}
				?>
			</datalist>
		</label>
		
		<label class='block'>
			<button class='button' type='button' name='enable_event_repeat'>
				Repeat this event
			</button>
			<input type='hidden' name='event[repeated]' value='<?php echo $eventdetails['repeated'];?>'>
		</label>
		
		<fieldset class='repeat_wrapper <?php if(empty($eventdetails['repeated'])) echo 'hidden';?>'>
			<legend>Repeat parameters</legend>
			<h4>Select repeat type:</h4>
			<select name="event[repeat][type]">
				<option value="">---</option>
				<option value="daily"		<?php if($repeat_param['type'] == 'daily') echo 'selected';?>>Daily</option>
				<option value="weekly"		<?php if($repeat_param['type'] == 'weekly') echo 'selected';?>>Weekly</option>
				<option value="monthly"		<?php if($repeat_param['type'] == 'monthly') echo 'selected';?>>Monthly</option>
				<option value="yearly"		<?php if($repeat_param['type'] == 'yearly') echo 'selected';?>>Yearly</option>
				<option value="custom_days"	<?php if($repeat_param['type'] == 'custom_days') echo 'selected';?>>Custom Days</option>
			</select>

			<div class='repeatdatetype <?php echo (($repeat_param['type'] == 'monthly' or $repeat_param['type'] == 'yearly') ? 'hide' : 'hidden');?>'>
				<h4>Select repeat pattern:</h4>
				<label class='optionlabel'>
					<input type='radio' name='event[repeat][datetype]' value='samedate' <?php if($repeat_param['datetype'] == 'samedate') echo 'checked';?>>
					<?php
					$monthday	= explode('-',$eventdetails['startdate'])[2];
					if($repeat_param['type'] == 'monthly'){
						$monthtext	= "a month";
					}elseif($repeat_param['type'] == 'yearly'){
						$monthnumber	= explode('-',$eventdetails['startdate'])[1];
						$monthname		= date("F", mktime(0, 0, 0, $monthnumber, 10));
						$monthtext		= "$monthname every year";
					}
					
					echo "On the <span class='monthday'>$monthday</span>th of <span class='monthoryear'>$monthtext</span>";
					?>
				</label>
				<label class='optionlabel'>
					<input type='radio' name='event[repeat][datetype]' value='patterned' <?php if($repeat_param['datetype'] == 'patterned') echo 'checked';?>>
					<?php
					echo "<span class='monthoryeartext'>";
					if($repeat_param['type'] == 'monthly'){
						echo "On a certain day and week of a month";
					}else{
						echo "On a certain day, week and month of a year";
					}
					echo "</span>";
					?>
				</label>
			</div>
						
			<label class='repeatinterval <?php echo ($repeat_param['datetype'] == 'samedate' ? 'hide' : 'hidden');?>'>
				<h4>Repeat every </h4>
				<?php
				if(empty($repeat_param['interval'])){
					$value	= 1;
				}else{
					$value	= $repeat_param['interval'];
				}
				?>
				<input type='number' name='event[repeat][interval]' value='<?php echo $value;?>'>
				<span id='repeattype'></span>
			</label>

			<div class="selector_wrapper months <?php echo ($repeat_param['datetype'] == 'patterned' ? 'hide' : 'hidden');?>">
				<?php
				if($repeat_param['type'] == 'monthly'){
					$type		= 'checkbox';
					$hidden1	= 'hide';
					$hidden2	= 'hidden';
				}else{
					$type		= 'radio';
					$hidden1	= 'hidden';
					$hidden2	= 'hide';
				}
				?>
				<h4 class='checkbox <?php echo $hidden1;?>'>Select month(s) this event should be repeated on</h4>
				<h4 class='radio <?php echo $hidden2;?>'>Select the month this event should be repeated on</h4>
				<label class='selectall_wrapper optionlabel <?php echo $hidden1;?>'><input type='<?php echo $type;?>' class='selectall' name='allmonths' value='all'>Select all months<br></label>
				<?php
				for ($m=1;$m<13;$m++){
					$monthname = date("F", mktime(0, 0, 0, $m, 10));
					if(is_array($eventdetails['repeat']['months']) and in_array($m,$eventdetails['repeat']['months'])){
						$checked = 'checked';
					}else{
						$checked = '';
					}
					echo  "<label class='optionlabel month'><input type='$type' name='event[repeat][months][]' value='$m' $checked>$monthname</label>";
					if($m  % 3 == 0) echo  '<br>';
				}
				?>
			</div>
			
			<div class="selector_wrapper weeks <?php echo ($repeat_param['datetype'] == 'patterned' ? 'hide' : 'hidden');?>">
				<?php
				if($repeat_param['type'] == 'weekly'){
					$type		= 'checkbox';
					$hidden1	= 'hide';
					$hidden2	= 'hidden';
				}else{
					$type		= 'radio';
					$hidden1	= 'hidden';
					$hidden2	= 'hide';
				}

				$weeknames = ['First','Second','Third','Fourth','Fifth','Last'];
				?>
				<h4 class='checkbox <?php echo $hidden1;?>'>Select weeks(s) of the month this event should be repeated on</h4>
				<h4 class='radio <?php echo $hidden2;?>'>Select the week of the month this event should be repeated on</h4>
				<label class='selectall_wrapper optionlabel <?php echo $hidden1;?>'><input type='checkbox' class='selectall' name='allweekss' value='all'>Select all weeks<br></label>
				<?php
				foreach($weeknames as $index=>$weekname){
					if(is_array($eventdetails['repeat']['weeks']) and in_array($weekname,$eventdetails['repeat']['weeks'])){
						$checked = 'checked';
					}else{
						$checked = '';
					}
					echo "<label class='optionlabel'><input type='$type' name='event[repeat][weeks][]' value='$weekname' $checked>$weekname</label>";
				}
				?>
			</div>
			
			<div class="selector_wrapper day_selector <?php echo ($repeat_param['datetype'] == 'patterned' ? 'hide' : 'hidden');?>">
				<?php
				if($repeat_param['type'] == 'weekly'){
					$type		= 'checkbox';
					$hidden1	= 'hide';
					$hidden2	= 'hidden';
				}else{
					$type		= 'radio';
					$hidden1	= 'hidden';
					$hidden2	= 'hide';
				}
				?>
				<h4 class='checkbox <?php echo $hidden1;?>'>Select day(s) this event should be repeated on</h4>
				<h4 class='radio <?php echo $hidden2;?>'>Select the day this event should be repeated on</h4>
				<label class='selectall_wrapper optionlabel <?php echo $hidden1;?>'><input type='checkbox' class='selectall' name='alldays' value='all'>Select all days<br></label>
				<?php 
				foreach(['Sunday','Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $index=>$dayname){
					if(is_array($eventdetails['repeat']['weekdays']) and in_array($dayname,$eventdetails['repeat']['weekdays'])){
						$checked	= 'checked';
					}else{
						$checked	= '';
					}
					echo"<label class='optionlabel'><input type='$type' name='event[repeat][weekdays][]' value='$dayname'$checked>$dayname</label>";
				}
				?>
			</div>
			
			<div class="custom_dates_selector <?php echo ($repeat_param['type'] == 'custom_days' ? 'hide' : 'hidden');?>">
				<h4> Specify repeat days</h4>
				<div class="clone_divs_wrapper">
					<?php
					$includedates	= $eventdetails['repeat']['includedates'];
					if(!is_array($includedates)){
						$includedates	= [''];
					}
					
					foreach($includedates as $index=>$includedate){
						?>
						<div id="includedate_div_<?php echo $index;?>" class="clone_div" data-divid="<?php echo $index;?>">
							<label>Include date <?php echo $index+1;?></label>
							<div class='buttonwrapper'>
								<input type="date" name="event[repeat][includedates][<?php echo $index;?>]" style="flex: 9;" value="<?php echo $includedate;?>">
								<button type="button" class="add button" style="flex: 1;">+</button>
							</div>
						</div>
						<?php
					}
					?>
				</div>
			</div>
			
			<h4>Stop repetition</h4>
			<label>
				<input type='radio' name='event[repeat][stop]' value='never' <?php if(empty($eventdetails['repeat']['stop']) or $eventdetails['repeat']['stop'] == 'never') echo 'checked';?>>
				Never
			</label><br>
			<div class='repeat_type_option'>
				<label>
					<input type='radio' name='event[repeat][stop]' value='date' <?php if($eventdetails['repeat']['stop'] == 'date') echo 'checked';?>>
					On this date:
				</label><br>
				
				<label class='repeat_type_option_specifics hidden'>
					<input type='date' name='event[repeat][enddate]' value='<?php echo $eventdetails['repeat']['enddate'];?>'>
				</label>
			</div>

			<div class='repeat_type_option'>
				<label>
					<input type='radio' name='event[repeat][stop]' value='after' <?php if($eventdetails['repeat']['stop'] == 'after') echo 'checked';?>>
					After this amount of repeats:<br>
				</label>
				
				<label class='repeat_type_option_specifics hidden'>
					<input type='number' name='event[repeat][amount]' value='<?php echo $eventdetails['repeat']['amount'];?>'>
				</label>
			</div>
			
			<div>
				<h4>Exclude dates from this pattern</h4>
				<div class="clone_divs_wrapper">
				<?php
				$excludedates	= (array)$eventdetails['repeat']['excludedates'];
				if(empty($excludedates)){
					$excludedates	= [''];
				}
				
				foreach($excludedates as $index=>$excludedate){
					?>
					<div id="excludedate_div_<?php echo $index;?>" class="clone_div" data-divid="<?php echo $index;?>">
						<label>Exclude date <?php echo $index+1;?></label>
						<div class='buttonwrapper'>
							<input type="date" name="event[repeat][excludedates][<?php echo $index;?>]" style="flex: 9;" value="<?php echo $excludedate;?>">
							<button type="button" class="add button" style="flex: 1;">+</button>
						</div>
					</div>
					<?php
				}
				?>
				</div>
			</div>
		</fieldset>
	</div>
	<?php
}

add_filter('sim_signal_post_notification_message', function($excerpt, $post){
	if($post->post_type == 'event'){
		$events		= new Events();
		$event		= $events->retrieve_single_event($post->ID);
		$startdate	= date('d-m-Y', strtotime($event->startdate));
		if($event->startdate == $event->enddate){
			$excerpt .= "\n\nDate: $startdate";
		}else{
			$enddate	 = date('d-m-Y', strtotime($event->enddate));
			$excerpt 	.= "\n\nStart date: $startdate";
			$excerpt 	.= "\nEnd date: $enddate";
		}
		if(!empty($event->starttime) and !empty($event->endtime)){
			$excerpt .= "\nTime: $event->starttime till $event->endtime";
		}

		if(!empty($event->location)){
			$excerpt .= "\nLocation: $event->location";
		}

		if(!empty($event->organizer)){
			if(is_numeric($event->organizer)){
				$event->organizer	= get_user_by('id', $event->organizer)->display_name;
			}
			$excerpt .= "\nOrganized by : $event->organizer";
		}
	}

	return $excerpt;
}, 10, 2);

add_action('sim_after_post_save', function($post, $update){
    $events = new Events();
    $events->store_event_meta($post, $update);
}, 1, 2);