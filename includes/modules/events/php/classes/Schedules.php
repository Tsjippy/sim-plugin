<?php
namespace SIM\EVENTS;
use SIM;
use WP_Error;

class Schedules{
	public $tableName;
	public $user;
	public $admin;
	public $events;
	public $lunchStartTime;
	public $lunchEndTime;
	public $dinerTime;
	public $noPermissionText;
	public $tdLabels;
	public $schedules;
	public $nextStartTimes;
	
	public function __construct(){
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
		$this->tdLabels			= [];
	}
	
	/**
	 * Creates the table holding all schedules if it does not exist
	*/
	public function createDbTable(){
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
	public function getSchedules(){
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
	public function findScheduleById($id){
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
	public function showschedules(){
		$schedules	= '';
		$form	= $this->addScheduleForm();

		foreach($this->schedules as $schedule){
			$schedules	.= $this->showSchedule($schedule);
		}

		$html	= "<div class='schedules_wrapper' style='position: relative;'>";
			$html	.= $this->addModals();
			$html	.= $schedules;
			$html	.= $form;
		$html	.= "</div>";
			
		return $html;
	}

	public function showSchedule($schedule) {
		$mobile	= wp_is_mobile();

		ob_start();

		if (
			$schedule->target											&&		// There is a target set
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
		if (
			$this->admin											||		// We are an admin
			!empty($this->getPersonalOrientationEvents($schedule)) 	||		// We are in the schedule
			$schedule->target == $this->user->ID							// The schedule is meant for us
		){
			$onlyMeals = false;
		}

		//if looking for a family
		if (strpos($schedule->name, 'family') !== false ){
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

		// Do not show dates in the past
		$schedule->startdate	= max([date('Y-m-d'), $schedule->startdate]);
		
		?>
		<div class='schedules_div table-wrapper' data-id="<?php echo $schedule->id; ?>">
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
			<?php
			if(!is_numeric($schedule->target) || !get_userdata($schedule->target)){
				?>
				<div class='warning'>
					This schedule has no website user connected to it.<br>
					Please <button type="button" class="button small schedule_action edit_schedule" data-schedule_id="<?php echo $schedule->id;?>">Edit</button> the schedule to add one.
				</div>
				<?php
			}
			?>
			<h3 class="table_title">
				Schedule for <?php echo $schedule->name;?>
			</h3>
			<h3 class="table_title sub-title">
				<?php echo $schedule->info;?>
			</h3>

			<?php
			// Only render when not on a mobile device
			if($mobile){
				$this->showMobileSchedule($schedule, $onlyMeals);
			}else{
				?>
				<p>Click on an available date to indicate you want to host.<br>Click on any date you are subscribed for to unsubscribe</p>

				<table class="sim-table schedule" data-id="<?php echo $schedule->id; ?>" data-target_id="<?php echo $schedule->target; ?>" data-target="<?php echo $schedule->name; ?>" data-action='update_schedule' data-lunch='<?php echo $schedule->lunch ? 'true' : 'false';?>'>
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

								if ($date == $schedule->enddate) {
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
			}
			?>
		</div>
		<?php

		return ob_get_clean();
	}

	public function showMobileSchedule($schedule, $onlyMeals) {
		?>
		<div name='add_host_mobile' class="modal hidden">
			<div class="modal-content">
				<span class="close">&times;</span>
				<form action="" method="post">
					<input type='hidden' name='schedule_id' value='<?php echo $schedule->id;?>'>

					<?php
					if($schedule->lunch){
						?>
						<label>
							For what are you hosting?
						</label>
						<br>
						<label >
							<input type='radio' id='lunch' name='starttime' value='12:00'>
							Lunch
						</label>
						<label>
							<input type='radio' id='diner' name='starttime' value='18:00'>
							Diner
						</label>
						<br>
						<br>
						<?php
					}else{
						?>
						<input type='hidden' name='starttime' value='18:00'>
						<?php
					}
					$date				= $schedule->startdate;
					$availableLunches	= [];
					$availableDiners	= [];

					while(true){
						$dateTime		= strtotime($date);

						if($schedule->lunch){
							$event			= $this->getScheduleEvent($schedule, $date, '12:00');
							if(!$event){
								$availableLunches[]	= $date;
							}
						}
						$event			= $this->getScheduleEvent($schedule, $date, '18:00');
						if(!$event){
							$availableDiners[]	= $date;
						}

						if ($date == $schedule->enddate) {
							break;
						}
						$date			= date('Y-m-d', strtotime('+1 day', $dateTime) );
					}

					?>
					<div class='lunch-select-wrapper hidden'>
						<label>
							Select the date you want to host <?php echo $schedule->name;?> for lunch
						</label>
						<br>

						<?php
						foreach($availableLunches as $availableLunch){
							?>
							<label>
								<input type='checkbox' name='date[]' value='<?php echo $availableLunch;?>'>
								<?php echo date('l j F', strtotime($availableLunch));?>
							</label>
							<br>
							<?php
						}
						?>
					</div>
					<div class='diner-select-wrapper hidden'>
						<label>
							Select the date you want to host <?php echo $schedule->name;?> for diner
						</label>
						<br>
						<?php
						foreach($availableDiners as $availableDiner){
							?>
							<label>
								<input type='checkbox' name='date[]' value='<?php echo $availableDiner;?>'>
								<?php echo date('l j F', strtotime($availableDiner));?>
							</label>
							<br>
							<?php
						}
						?>
					</div>
					<br>
					<br>

						<?php
					if($this->admin){
						?>
						<p>Please select the user or family who is hosting</p>
						<?php
						echo SIM\userSelect('', true, true, '', 'host_id', [], '', [], 'list');
					}else{
						echo "<input type='hidden' name='host_id' value='{$this->user->ID}'>";
					}
					echo SIM\addSaveButton('add_host_mobile','Save','update_schedule');
					?>
				</form>
			</div>
		</div>
			
				
				<?php

			$date		= $schedule->startdate;
			while(true){
				$dateTime		= strtotime($date);
				$dayName		= date('l', $dateTime);
				$formatedDate	= date('d-m-Y', $dateTime);
				$html			= $this->getMobileDay($schedule, $onlyMeals, $date);
				if(!empty($html)){
					echo "<strong>$dayName $formatedDate</strong><br>";
					echo $html.'<br>';
				}

				if ($date == $schedule->enddate) {
					break;
				}

				$date	= date('Y-m-d', strtotime('+1 day', $dateTime) );
			}

			if($onlyMeals){
				?>
				<br>
				<button class='button' name='add-host'>Add me as a host</button>
				<?php
			}else{
				?>
				<br>
				<button class='button' name='add-session'>Add a session</button>
				<?php
			}
			?>
		</div>
		<?php
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
	public function getScheduleEvent($schedule, $startDate, $startTime){
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
	public function getScheduleEvents($scheduleId, $startDate, $startTime){
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
	public function getPersonalOrientationEvents($schedule){
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
	public function writeMealCell($schedule, $date, $startTime, $onlyText=false){
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
			if ($hostId == $this->user->ID || $hostId == $partnerId){
				$class 			.= ' own';
							
				$menu		= get_post_meta($event->post_id, 'recipe_keyword', true);
				if(empty($menu)){
					$menu	= 'Enter recipe keyword';
				}
			
				$cellText .= "<span class='keyword'>$menu</span>";
			//current user is the target or is admin
			} elseif ( !$this->admin && $schedule->target != $this->user->ID && $schedule->target != $partnerId){
				$cellText = 'Taken';
			}
		} else {
			$cellText	 = 'Available';
		}

		if ($this->admin) {
			$class .= ' admin';
		}

		$label	= date('d-m-Y', strtotime($date));

		if($onlyText){
			return $cellText;
		}
		return "<td class='$class' $rowSpan $hostData label='$label'>$cellText</td>";
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
	public function writeOrientationCell( $schedule, $date, $startTime, $onlyText=false) {
		//get event which starts on this date and startTime
		$event		= $this->getScheduleEvent($schedule, $date, $startTime);
		$rowSpan	= '';
		$class		= 'orientation';

		if (!$event){
			$cellText = 'Available';
		}else {
			$hostId			= $event->organizer_id;
			$dataset	= "data-time='{$event->starttime}' data-endtime='{$event->endtime}'";
			if (is_numeric($hostId)) {
				$dataset	.= " data-host='".get_userdata($hostId)->display_name."' data-host_id='$hostId'";
			}
			$title		= get_the_title($event->post_id);
			$dataset	.= " data-subject='".explode(' with ', $title)[0]."'";
			$url		= get_permalink($event->post_id);
			$date		= $event->startdate;
			$endTime	= $event->endtime;
			$class 		.= ' selected';
			$cellText	= "<span class='subject' data-userid='$hostId'><a href='$url'>$title</a></span><br>";
		
			if (!is_numeric($hostId)) {
				$cellText .= "<span class='person timeslot'>Add person</span>";
			}
		
			if (empty($event->location)) {
				$cellText .= "<span class='location timeslot'>Add location</span>";
			} else {
				$cellText .= "<span class='timeslot'>At <span class='location'>{$event->location}</span></span>";
				$dataset	.= " data-location='{$event->location}'";
			}

			//check how many rows this event should span
			$toTime		= new \DateTime($event->endtime);
			$fromTime	= new \DateTime($event->starttime);
			$interval	= $toTime->diff($fromTime);
			$value		= ($interval->h*60+$interval->i)/15;
			$rowSpan	= "rowspan='$value'";

			$this->nextStartTimes[$date] = $endTime;
		}

		if($onlyText){
			return $cellText;
		}

		//Make the cell editable if:
		if(
			$this->admin							||		// we are admin
			$this->user->ID == $event->organizer_id || 		// We are the organizer
			$cellText == 'Available'						// This cell  is available
		){
			$class .= ' admin';
		}

		$label	= date('d-m-Y', strtotime($date));
		
		return "<td class='$class' $rowSpan $dataset label='$label'>$cellText</td>";
	}

	/**
	 * Write all rws of a schedule table
	 *
	 * @param	object	$schedule		the Schedule
	 * @param	bool	$onlyMeals		Whether to only write the meal rows. Default false
	 *
	 * @return 	array					Rows html
	*/
	public function writeRows($schedule, $onlyMeals = false){
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
			if (!$onlyMeals || $mealScheduleRow ) {
				$html  .= "<tr class='table-row' data-starttime='$startTime' data-endtime='$endTime'>";
					$label	= date('d-m-Y', strtotime($date));
					$html 	.= "<td $extra class='sticky' label=''><strong>$description</strong></td>";
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
	 * Write all rws of a schedule table
	 *
	 * @param	object	$schedule		the Schedule
	 * @param	bool	$onlyMeals		Whether to only write the meal rows. Default false
	 *
	 * @return 	array					Rows html
	*/
	public function getMobileDay($schedule, $onlyMeals, $date){
		$html 					= '';

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
			
			$mealScheduleRow	= true;
			$endTime			= date("H:i", strtotime('+15 minutes', strtotime($startTime)));
			
			if(
				$startTime >= $this->lunchStartTime	&&		// Time is past the start lunch time
				$startTime < $this->lunchEndTime	&& 		// but before the end
				$schedule->lunch							// And the schedule includes a lunch
			){
				$description		= 'Lunch';
			}elseif($startTime == $this->dinerTime){
				$description		= 'Dinner';
			}else{
				$mealScheduleRow	= false;
				$description		= $startTime;
			}

			//Show the row if we can see all rows or the row is a mealschedule row
			if (!$onlyMeals || $mealScheduleRow ) {
				//mealschedule
				if($mealScheduleRow){
					$content	= $this->writeMealCell($schedule, $date, $startTime, true);
				//Orientation schedule
				}elseif(!$onlyMeals){
					$content	= $this->writeOrientationCell($schedule, $date, $startTime, true);
				}

				if($content != 'Available'){
					$html 	.= "<strong>$description</strong>:<br>";
					$html 	.=	$content.'<br>';
				}
			}

			if($startTime == $schedule->endtime){
				break;
			}
			$startTime		= $endTime;
		}//end true
		
		return $html;
	}
		
	/**
	 * Create all modals
	 *
	 * @return 	string		the modal html
	*/
	public function addModals(){
		ob_start();
		?>
		<!-- Add host modal for admins -->
		<div name='add_host' class="modal hidden">
			<div class="modal-content">
				<span class="close">&times;</span>
				<form action="" method="post">
					<input type='hidden' name='schedule_id'>
					<input type='hidden' name='host_id'>
					<input type='hidden' name='oldtime'>
					<input type='hidden' name='starttime'>
					<input type='hidden' name='date'>
						<?php
					if($this->admin){
						?>
						<p>Please select the user or family who is hosting</p>
						<?php
						echo SIM\userSelect('', true, true, '', 'host', [], '', [], 'list');
					}else{
						echo "<input type='hidden' name='host' value='{$this->user->ID}'>";
					}
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
					<input type='text' class='wide' name='recipe_keyword'>
					
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
						<input type='date' name='date' class='wide' required>
					</label>

					<label>
						<h4>Select a start time:</h4>
						<input type="time" name="starttime" class="time wide" step="900" min="08:00" max="18:00" required>
					</label>
					
					<label>
						<h4>Select an end time:</h4>
						<input type="time" name="endtime" class="time wide" step="900" min="08:00" max="18:00" required>
					</label>
					
					<label>
						<h4>Subject</h4>
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

					?>
					<h4>Warnings</h4>
					<label>
						<input type="checkbox" name="reminders[]" value="15" checked>
						Send a remider 15 minutes before the start
					</label>
					<br>
					<label>
						<input type="checkbox" name="reminders[]" value="1440">
						Send a remider 1 day before the start
					</label>
					<br>
					<?php
					
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
	public function addScheduleForm($update=false){
		ob_start();
		if(!$this->admin){
			return '';
		}

		?>
		<h3 style='text-align:center;'>Add a schedule</h3>
		<form class='add-schedule-form'>
			<input type="hidden" name="schedule_id">
			<input type="hidden" name="target_id">
			<input type="hidden" name="update" value="<?php echo $update;?>">
			
			<label>
				<h4>Name of the person the schedule is for</h4>
				<input type='text' name='target_name' class='wide' list="website_users" required>
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
				<input type='text' class='wide' name='schedule_info'>
			</label>
			
			<label>
				<h4>Date the schedule should start</h4>
				<input type='date' class='wide' name='startdate' required>
			</label>
			
			<label>
				<h4>Date the schedule should end</h4>
				<input type='date' class='wide' name='enddate' required>
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
