<?php
/**
 * The layout specific for the page with the slug 'event' i.e. simnigeria.org/event.
 * Displays all the post of the event type
 * 
 */

use function SIM\enqueue_scripts;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $Modules;
global $Events;

$view = $_GET['view'];
if(empty($view)){
	$view = 'month';
}

global $wp_query;
$cat	= $wp_query->queried_object_id;

get_header(); 
?>
	<div id="primary" style="width:100%;">
		<main id="main" class='inside-article'<?php generate_do_element_classes( 'main' ); ?>>
			<div class="calendar-wrap">
				<button id='add_calendar' class='button'>Add this calendar to your personal agenda</button>
				<div id='calendaraddingoptions' class='hidden'>
					<p>
						To add the calendar to your sim.org calendar go to <a href='https://outlook.office.com/calendar/addcalendar' target="_blank">email.sim.org</a>.<br>
						Then add <code class='calendarurl'>webcal://simnigeria.org/public_calendar?id=<?php echo get_current_user_id();?></code> in the 'subscribe via internet' screen
					</p>
					<p>
						To add the calendar to your gmail calendar go to <a href='https://calendar.google.com/calendar/u/1/r/settings/addbyurl' target="_blank">calendar.google.com</a>.<br>
						Then paste this url in the agenda-url field <code class='calendarurl'>webcal://simnigeria.org/public_calendar?id=<?php echo get_current_user_id();?></code>.
					</p>
					<p>
						To add the calendar to outlook click on 'open agenda'->'from the internet'.<br>
						Then paste this url in the agenda field <code class='calendarurl'>webcal://simnigeria.org/public_calendar?id=<?php echo get_current_user_id();?></code>.
					</p>
				</div>
				<div class="search-form">	
					<div class="date-selector">
						<div class="date-search">
							<input type="hidden" value="Select">
							<?php
							echo "<img src='$picturesurl/date.png' alt='time' class='event_icon'>";
							?>
							<select class='month_selector<?php if($view=='week') echo ' hidden';?>' placeholder="Select month">
								<?php
								for ($m=1;$m<13;$m++){
									$monthname	= date("F", mktime(0, 0, 0, $m, 10));
									$monthnum	= sprintf("%02d", $m);
									if($_GET['month'] == $m){
										$selected	= ' selected';
									}else{
										$selected	= '';
									}
									echo "<option value='$monthnum'$selected>$monthname</option>";
								}
								?>
							</select>
							<select class='week_selector<?php if($view!='week') echo ' hidden';?>' placeholder="Select week">
								<?php
								for ($w=1;$w<=53;$w++){
									if($_GET['week'] == $w){
										$selected	= ' selected';
									}else{
										$selected	= '';
									}
									echo "<option value='$w'$selected>Week $w</option>";
								}
								?>
							</select>
							<select class="year_selector" placeholder="Select year">
								<?php
								$start 	= date('Y');
								$end	= date("Y",strtotime('+10 year'));
								for ($y=$start;$y<$end;$y++){
									$monthname = date("F", mktime(0, 0, 0, $m, 10));
									if($y == $_GET['year']){
										$selected = 'selected';
									}else{
										$selected = '';
									}
									echo "<option value='$y' $selected>$y</option>";
								}
								?>
							</select>
						</div>
					</div>
					<div class="view-style">
						<span class="viewselector<?php if($view=='month') echo ' selected';?>" data-type="monthview">Monthly</span>
						<span class="viewselector<?php if($view=='week') echo ' selected';?>" data-type="weekview">Weekly</span>
						<span class="viewselector<?php if($view=='list') echo ' selected';?>" data-type="listview">List</span>
					</div>
				</div>

				<div id='monthview' class='calendarview<?php if($view!='month') echo ' hidden';?>'>
					<?php
					echo $Events->month_calendar($cat);
					?>
				</div>
				<div id='weekview' class='calendarview<?php if($view!='week') echo ' hidden';?>'>
					<?php
					echo $Events->week_calendar($cat);
					?>
				</div>
				<div id='listview' class='calendarview<?php if($view!='list') echo ' hidden';?>'>
					<?php
					echo $Events->list_calendar($cat);
					?>
				</div>		
			</div>
		</div>
	</main>
</div>

<?php

	generate_construct_sidebars();

	get_footer();