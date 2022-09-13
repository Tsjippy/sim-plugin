<?php
namespace SIM\EVENTS;
use SIM;
use WP_Error;

class Schedules{
	function __construct(){
		global $wpdb;

		$this->tableName		= $wpdb->prefix . 'sim_schedules';

		$this->getSchedules();
		
		$this->user 			= wp_get_current_user();
		
		if(in_array('personnelinfo', $this->user->roles)){
			$this->admin		=	true;
		}else{
			$this->admin		= false;
		}

		$this->events			= new DisplayEvents();

		$this->lunchStartTime	= '12:00';
		$this->lunchEndTime		= '13:00';
		$this->dinerTime		= '18:00';
		$this->noPermissionText	= 'No permission to do that!';
	}
	
	/**
	 * Creates the table holding all schedules if it does not exist
	*/
	function createDbTable(){
		if ( !function_exists( 'maybe_create_table' ) ) { 
			require_once ABSPATH . '/wp-admin/install-helper.php'; 
		} 
		
		//only create db if it does not exist
		global $wpdb;
		$charsetCollate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->tableName} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			target mediumint(9) NOT NULL,
			published boolean NOT NULL,
			name longtext NOT NULL,
			info longtext NOT NULL,
			lunch boolean NOT NULL,
			orientation boolean NOT NULL,
			startdate varchar(80) NOT NULL,
			enddate varchar(80) NOT NULL,
			starttime varchar(80) NOT NULL,
			endtime varchar(80) NOT NULL,
			PRIMARY KEY  (id)
		) $charsetCollate;";

		maybe_create_table($this->tableName, $sql );
	}

	/**
	 * Get all schedules from the db
	*/
	function getSchedules(){
		global $wpdb;

		$query				= "SELECT * FROM {$this->tableName} WHERE 1";
		
		$this->schedules	= $wpdb->get_results($query);
	}

	/**
	 * Get a specific schedule from the db
	 * 
	 * @param	int	$id		the schedule ID
	 * 
	 * @return 	object		the schedule
	*/
	function findScheduleById($id){
		foreach($this->schedules as $schedule){
			if($schedule->id == $id){
				return $schedule;
			}
		}
	}

	/**
	 * Get all existing schedules
	 * 
	 * @return 	string		the schedules html
	*/
	function showschedules(){
		$schedules	= '';
		$form	= $this->addScheduleForm();

		foreach($this->schedules as $schedule){
			$schedules	.= $this->showSchedule($schedule);
		}

		$html	= "<div class='schedules_wrapper' style='z-index: 99;position: relative;'>";
			$html	.= $this->addModals();
			$html	.= $schedules;
			$html	.= $form;
		$html	.= "</div>";
			
		return $html;
	}

	function showSchedule($schedule){
		ob_start();

		if(
			(
				$schedule->target == $this->user->ID 					||		// Target is me
				$schedule->target == SIM\hasPartner($this->user->ID)			// Target is the partner
			) 															&& 
			!$this->admin 												&& 		// We are not admin
			!$schedule->published												// Schedule is not yet published
		){
			return '';
		}
		
		$onlyMeals 	= true;
		
		// Show also the orientation schedule if:
		if(
			$this->admin											||		// We are an admin
			!empty($this->getPersonalOrientationEvents($schedule)) 	||		// We are in the schedule
			$schedule->target == $this->user->ID							// The schedule is meant for us
		){
			$onlyMeals = false;
		}

		//if looking for a family
		if(strpos($schedule->name,'family') !== false){
			$args = array(
				'meta_query' => array(
					array(
						'key'		=> 'last_name',
						'value'		=> str_replace(' family', '', $schedule->name),
						'compare'	=> 'LIKE'
					)
				)
			);
		//Full name
		}else{
			$args = array(
				'search'			=> $schedule->name,
				'search_columns' 	=> ['display_name'],
			);
		}
		
		?>
		<div class='schedules_div table-wrapper'>
			<div class="modal publish_schedule hidden">
				<div class="modal-content">
					<span id="modal_close" class="close">&times;</span>
					<form action="" method="post" id="publish_schedule_form">
						<input type='hidden' name='schedule_id'>
						<p>
							Please select the user or family this schedule should be published for.<br>
							The schedule will show up on the dashboard of these persons after publish.
						</p>
						<?php
						echo SIM\userSelect('', true, true, '', 'schedule_target', $args);
						echo SIM\addSaveButton('publish_schedule', 'Publish this schedule'); 
						?>
					</form>
				</div>
			</div>
			<h3 class="table_title">
				Schedule for <?php echo $schedule->name;?>
			</h3>
			<h3 class="table_title sub-title"><?php echo $schedule->info;?></h3>
			<p>Click on an available date to indicate you want to host.<br>Click on any date you are subscribed for to unsubscribe</p>

			<table class="sim-table schedule" data-id="<?php echo $schedule->id; ?>" data-target="<?php echo $schedule->name; ?>" data-action='update_schedule' data-lunch='<?php echo $schedule->lunch ? 'true' : 'false';?>'>
				<thead>
					<tr>
						<th class='sticky'>Dates</th>
						<?php
						$date		= $schedule->startdate;
						while(true){
							$dateStr		= date('d F Y', strtotime($date));
							$dateTime		= strtotime($date);					
							$dayName		= date('l', $dateTime);
							$formatedDate	= date('d-m-Y', $dateTime);
							echo "<th data-date='$dateStr' data-isodate='$date'>$dayName<br>$formatedDate</th>";

							if($date == $schedule->enddate){
								break;
							}

							$date	= date('Y-m-d', strtotime('+1 day', $dateTime) );
						}
						?>
					</tr>
				</thead>
				<tbody class="table-body">
					<?php
					echo $this->writeRows($schedule, $onlyMeals);
					?>
				</tbody>
			</table>

			<?php
			if($this->admin){
				?>
				<div class='schedule_actions'>
					<button type='button' class='button schedule_action edit_schedule' data-schedule_id='<?php echo $schedule->id;?>'>Edit</button>
					<button type='button' class='button schedule_action remove_schedule' data-schedule_id='<?php echo $schedule->id;?>'>Remove</button>
					<?php
					//schedule is not yet set.
					if(!$schedule->published && $schedule->target != 0){
						echo "<button type='button' class='button schedule_action publish' data-target='{$schedule->target}' data-schedule_id='{$schedule->id}'>Publish</button>";
					}
					?>
				</div>
				<?php
			}
			?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get a single event on a specific date and time
	 * 
	 * @param	object	$schedule	the Schedule
	 * @param 	string	$startDate	The Date the event starts
	 * @param	string	#startTime	The time the event starts
	 * 
	 * @return 	object|false		The event or false if no event
	*/
	function getScheduleEvent($schedule, $startDate, $startTime){
		//get event which starts on this date and time
		$events = $this->getScheduleEvents($schedule->id, $startDate, $startTime);

		$event	= false;
		foreach($events as $ev){
			if($ev->onlyfor == $this->user->ID){
				$event	= $ev;
				//use this event
				break;
			}elseif($ev->onlyfor == $schedule->target){
				$event	= $ev;
				//do not break to keep look if there is an events for us.
			}
		}

		return $event;
	}

	/**
	 * Get multiple events on a specific date and time
	 * 
	 * @param	int		$scheduleId		the Schedule id
	 * @param 	string	$startDate		The Date the event starts
	 * @param	string	#startTime		The time the event starts
	 * 
	 * @return 	object|false			The event or false if no event
	*/
	function getScheduleEvents($scheduleId, $startDate, $startTime){
		global $wpdb;

		$query	=  "SELECT * FROM {$this->events->tableName} WHERE `schedule_id` = '{$scheduleId}' AND startdate = '$startDate' AND starttime='$startTime'";
		return $wpdb->get_results($query);
	}

	/**
	 * Get all personal events belonging to a schedule
	 * 
	 * @param	object	$schedule		the Schedule
	 * 
	 * @return 	array					Array of objects
	*/
	function getPersonalOrientationEvents($schedule){
		global $wpdb;

		$query	= "SELECT * FROM {$this->events->tableName} WHERE `schedule_id` = '{$schedule->id}' AND onlyfor={$this->user->ID} AND starttime != '$this->dinerTime'";
		if($schedule->lunch){
			$query	.= " AND starttime != '$this->lunchStartTime'";
		}
		return $wpdb->get_results($query);
	}

	/**
	 * Creates a meal cell html
	 * 
	 * @param	object	$schedule		the Schedule
	 * @param	string	$date			date string
	 * @param	string	$startTime		time string
	 * 
	 * @return 	array					Cell html
	*/
	function writeMealCell($schedule, $date, $startTime){
		//get event which starts on this date and time
		$event	= $this->getScheduleEvent($schedule, $date, $startTime);
		$class	= 'meal';

		if($startTime == $this->lunchStartTime){
			$rowSpan						= "rowspan='4'";
			$this->nextStartTimes[$date]	= $this->lunchEndTime;
		}else{
			$rowSpan = '';
		}
		
		if($event != null){
			$hostId		= $event->organizer_id;
			if(is_numeric($hostId)){
				$hostData	= "data-host=$hostId";
			}else{
				$hostData	= "";
			}
			$title			= get_the_title($event->post_id);
			$url			= get_permalink($event->post_id);
			$cellText		= "<a href='$url'>$title</a>";
			$date			= $event->startdate;
			$startTime		= $event->starttime;

			$class 			.= ' selected';
			$partnerId 		= SIM\hasPartner($this->user->ID);
			//Host is current user or the spouse
			if($hostId == $this->user->ID || $hostId == $partnerId){	
				$class 			.= ' own';
							
				$menu		= get_post_meta($event->post_id, 'recipe_keyword', true);
				if(empty($menu)){
					$menu	= 'Enter recipe keyword';
				}
			
				$cellText .= "<span class='keyword'>$menu</span>";
			//current user is the target or is admin
			}elseif(!$this->admin && $schedule->target != $this->user->ID && $schedule->target != $partnerId){
				$cellText = 'Taken';
			}
		}else{
			$cellText	 = 'Available';
		}

		if($this->admin){
			$class .= ' admin';
		}
		return "<td class='$class' $rowSpan $hostData>$cellText</td>";
	}
	
	/**
	 * Creates a orientation cell html
	 * 
	 * @param	object	$schedule		the Schedule
	 * @param	string	$date			date string
	 * @param	string	$startTime		time string
	 * 
	 * @return 	array					Cell html
	*/
	function writeOrientationCell($schedule, $date, $startTime){
		//get event which starts on this date and startTime
		$event		= $this->getScheduleEvent($schedule, $date, $startTime);
		$rowSpan	= '';
		$class		= 'orientation'; 

		if($event == null){
			$cellText = 'Available';
		}else{
			$hostId			= $event->organizer_id;
			$dataset	= "data-time='{$event->starttime}' data-endtime='{$event->endtime}'";
			if(is_numeric($hostId)){
				$dataset	.= " data-host='".get_userdata($hostId)->display_name."' data-host_id='$hostId'";
			}
			$title		= get_the_title($event->post_id);
			$dataset	.= " data-subject='".explode(' with ', $title)[0]."'";
			$url		= get_permalink($event->post_id);
			$date		= $event->startdate;
			$endTime	= $event->endtime;
			$class 		.= ' selected';
			$cellText	= "<span class='subject' data-userid='$hostId'><a href='$url'>$title</a></span><br>";
		
			if(!is_numeric($hostId)){
				$cellText .= "<span class='person timeslot'>Add person</span>";
			}
		
			if(empty($event->location)){
				$cellText .= "<span class='location timeslot'>Add location</span>";
			}else{
				$cellText .= "<span class='timeslot'>At <span class='location'>{$event->location}</span></span>";
				$dataset	.= " data-location='{$event->location}'";
			}

			////check how many rows this event should span
			$toTime		= new \DateTime($event->endtime);
			$fromTime	= new \DateTime($event->starttime);
			$interval	= $toTime->diff($fromTime);
			$value		= ($interval->h*60+$interval->i)/15;
			$rowSpan	= "rowspan='$value'";

			$this->nextStartTimes[$date] = $endTime;
		}

		//Make the cell editable if:
		if(
			$this->admin							||		// we are admin
			$this->user->ID == $event->organizer_id || 		// We are the organizer
			$cellText == 'Available'						// This cell  is available	
		){
			$class .= ' admin';
		}
		
		return "<td class='$class' $rowSpan $dataset>$cellText</td>";
	}

	/**
	 * Write all rws of a schedule table
	 * 
	 * @param	object	$schedule		the Schedule
	 * @param	bool	$onlyMeals		Whether to only write the meal rows. Default false
	 * 
	 * @return 	array					Rows html
	*/
	function writeRows($schedule, $onlyMeals = false){
		$html 					= '';
		
		$this->nextStartTimes	= [];

		//loop over the rows
		$startTime	= $schedule->starttime;

		//loop until we are at the endtime
		while(true){
			//If we do not have an orientation schedule, go strait to the dinner row
			if(
				$startTime >= $this->lunchEndTime	&& 
				$startTime < $this->dinerTime		&& 
				!$schedule->orientation
			){
				$startTime = $this->dinerTime;
			}
			
			$date				= $schedule->startdate;
			$mealScheduleRow	= true;
			$endTime			= date("H:i", strtotime('+15 minutes', strtotime($startTime)));
			$extra				= '';
			
			if(
				$startTime == $this->lunchStartTime	&&		// Starttime of the lunch
				$schedule->lunch							// And the schedule includes a lunch
			){			
				$extra				= 'rowspan="4"';		// Span 4 rows
				$description		= 'Lunch';
			}elseif(
				$startTime > $this->lunchStartTime	&&		// Time is past the start lunch time
				$startTime < $this->lunchEndTime	&& 		// but before the end
				$schedule->lunch							// And the schedule includes a lunch
			){
				$description		= 'Lunch';
				$extra				= "class='hidden'";		// These rows should be hidden as the first spans 4 rows
			}elseif($startTime == $this->dinerTime){
				$description		= 'Dinner';
			}else{
				$mealScheduleRow	= false;	
				$description		= $startTime;
			}

			//loop over the dates to write a cell per date in this timerow
			$cells	= '';
			while(true){
				if($this->nextStartTimes[$date] > $startTime){
					$cells	.= "<td class='hidden'>Available</td>";
				}else{
					//mealschedule
					if($mealScheduleRow){
						$cells .= $this->writeMealCell($schedule, $date, $startTime);
					//Orientation schedule
					}else{
						$cells .= $this->writeOrientationCell($schedule, $date, $startTime);	
					}
				}

				if($date == $schedule->enddate){
					break;
				}

				$date	= date('Y-m-d', strtotime('+1 day', strtotime($date)));
			}
			
			//Show the row if we can see all rows or the row is a mealschedule row
			if(!$onlyMeals || $mealScheduleRow){
				$html  .= "<tr class='table-row' data-starttime='$startTime' data-endtime='$endTime'>";
					$html 	.= "<td $extra class='sticky'><b>$description</b></td>";
					$html	.= $cells;
				$html .= "</tr>";
			}

			if($startTime == $schedule->endtime){
				break;
			}
			$startTime		= $endTime;
		}//end row
		
		return $html;
	}
		
	/**
	 * Create all modals
	 * 
	 * @return 	string		the modal html
	*/
	function addModals(){
		ob_start();
		?>
		<!-- Add host modal for admins -->
		<div name='add_host' class="modal hidden">
			<div class="modal-content">
				<span class="close">&times;</span>
				<form action="" method="post">
					<p>Please select the user or family who is hosting</p>
					<input type='hidden' name='schedule_id'>
					<input type='hidden' name='date'>
					<input type='hidden' name='starttime'>
					<input type='hidden' name='host_id'>
					<?php
					echo SIM\userSelect('', true, true, '', 'host', [], '', [], 'list');
					echo SIM\addSaveButton('add_host','Add host','update_schedule'); 
					?>
				</form>
			</div>
		</div>
		
		<!-- Add recipe modal -->
		<div name='recipe_keyword_modal' class="modal hidden">
			<div class="modal-content">
				<span class="close">&times;</span>
				<form action="" method="post">
					<input type='hidden' name='schedule_id'>
					<input type='hidden' name='date'>
					<input type='hidden' name='starttime'>
					
					<p>Enter one or two keywords for the meal you are planning to serve<br>
					For instance 'pasta', 'rice', 'Nigerian', 'salad'</p>
					<input type='text' name='recipe_keyword'>
					
					<?php
					echo SIM\addSaveButton('add_recipe_keyword','Add recipe keywords','update_schedule'); 
					?>
				</form>
			</div>
		</div>
		
		<!-- Add session modal -->
		<div name='add_session' class="modal hidden">
			<div class="modal-content">
				<span class="close">&times;</span>
				<form action="" method="post">
					<input type='hidden' name='schedule_id'>
					<input type='hidden' name='olddate'>
					<input type='hidden' name='host_id'>
					<input type='hidden' name='oldtime'>
					
					<h3>Add an orientation session</h3>

					<label>
						<h4>Date:</h4>
						<input type='date' name='date' required>
					</label>

					<label>
						<h4>Select a start time:</h4>
						<input type="time" name="starttime" class="time" step="900" min="08:00" max="18:00" required>
					</label>
					
					<label>
						<h4>Select an end time:</h4>
						<input type="time" name="endtime" class="time" step="900" min="08:00" max="18:00" required>
					</label>
					
					<label>
						<h4>What is the subject</h4>
						<input type="text" name="subject" class="wide" required>
					</label>
					
					<label>
						<h4>Location</h4>
						<input type="text"  name="location" class="wide">
					</label>
					
					<?php
					//select for person in charge if an admin
					if($this->admin){
						?>
						<label for="host"><h4>Who is in charge</h4></label>
						<?php
						echo SIM\userSelect('', true, false, 'wide', 'host', [], '', [], 'list', 'admin_host');
					}
					
					echo SIM\addSaveButton('add_timeslot','Add time slot','update_event add_schedule_row');
					?>
				</form>
			</div>
		</div>

		<?php
		if($this->admin){
			?>
			<!-- Edit schedule modal -->
			<div id='edit_schedule_modal' class="modal hidden">
				<div class="modal-content">
					<span class="close">&times;</span>
					<?php
						echo $this->addScheduleForm(true);
					?>
				</div>
			</div>
			
		<?php
		}
		return ob_get_clean();
	}

	/**	
	 * Create the form to add a schedule
	 * 
	 * @return 	string		the form html
	*/
	function addScheduleForm($update=false){
		ob_start();
		if(!$this->admin){
			return '';
		}

		?>			
		<h3 style='text-align:center;'>Add a schedule</h3>
		<form id='add-schedule-form'>
			<input type="hidden" name="target_id">
			<input type="hidden" name="update" value="<?php echo $update;?>">
			
			<label>
				<h4>Name of the person the schedule is for</h4>
				<input type='text' name='target_name' list="website_users" required>
			</label>
			
			<datalist id="website_users">
				<?php
				foreach(SIM\getUserAccounts(true) as $user){
					echo "<option value='{$user->display_name}' data-value='{$user->ID}'></option>";
				}
				?>
			</datalist>

			<label>
				<h4>Extra info or subtitle for this schedule</h4>
				<input type='text' name='schedule_info'>
			</label>
			
			<label>
				<h4>Date the schedule should start</h4>
				<input type='date' name='startdate' required>
			</label>
			
			<label>
				<h4>Date the schedule should end</h4>
				<input type='date' name='enddate' required>
			</label>
			
			<br>
			<label class='option-label'>
				<input type='checkbox' name='skiplunch' style="display: inline;width: auto;">
				Do not include a lunch in the schedule
			</label>
			<br>
			<label class='option-label'>
				<input type='checkbox' name='skiporientation' style="display: inline;width: auto;">
				Do not include an orientation schedule
			</label><br>
			
			<?php
			$action = 'Add';
			if($update){
				$action = 'Update';
			}
			echo SIM\addSaveButton('add_schedule', "$action schedule");
		echo '</form>';
		return ob_get_clean();
	}
}
