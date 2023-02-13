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
	protected $mobile;
	
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

		$this->mobile			= wp_is_mobile();
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
		<div class='schedules_div table-wrapper' data-id="<?php echo $schedule->id; ?>" data-target="<?php echo $schedule->name; ?>">
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
			if($this->mobile){
				$this->showMobileSchedule($schedule, $onlyMeals);
			}else{
				?>
				<p>Click on an available date to indicate you want to host.<br>Click on any date you are subscribed for to unsubscribe</p>

				<table class="sim-table schedule" data-id="<?php echo $schedule->id; ?>" data-action='update_schedule' data-lunch='<?php echo $schedule->lunch ? 'true' : 'false';?>'>
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
		<div class="add-host-mobile-wrapper modal hidden">
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
					<div class='lunch select-wrapper hidden'>
						<label>
							Select the date you want to host <?php echo $schedule->name;?> for lunch
						</label>
						<br>

						<?php
						foreach($availableLunches as $availableLunch){
							?>
							<label class='date'>
								<input type='checkbox' name='date[]' value='<?php echo $availableLunch;?>'>
								<?php echo date('l j F', strtotime($availableLunch));?>
							</label>
							<br>
							<?php
						}
						?>
					</div>
					<div class='diner select-wrapper <?php if($schedule->lunch){echo 'hidden';}?>'>
						<label>
							Select the date you want to host <?php echo $schedule->name;?> for diner
						</label>
						<br>
						<?php
						foreach($availableDiners as $availableDiner){
							?>
							<label class='date'>
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
			echo "<strong>$dayName $formatedDate</strong><br>";
			echo $html.'<br>';

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
			<br>
			<br>
			<button class='button' name='add-host'>Add a meal host</button>
			<?php
		}
		?>
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
	public function writeMealCell($schedule, $date, $startTime){
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
			$title			= get_the_title($event->post_id);
			$url			= get_permalink($event->post_id);
			$cellText		= "<a href='$url'>$title</a>";
			$hostId			= $event->organizer_id;
			$date			= $event->startdate;
			$startTime		= $event->starttime;
			$hostData		= "";
			if(is_numeric($hostId)){
				$hostData	.= " data-host=$hostId data-starttime='$startTime'";
			}

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
				//$cellText = 'Taken';
			}
		} else {
			if($this->mobile){
				$dateStr	= date('d-m-Y', strtotime($date));
				$hostId		= get_current_user_id();

				$cellText	= "<span class='add-me-as-host' data-date='$dateStr' data-starttime='$startTime' data-host_id='$hostId' data-isodate='$date'>";
					$cellText .= 'Available   ';
					$cellText .= '<svg fill="#000000" height="20px" width="20px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve"> <g> <g><path d="M256,0C114.611,0,0,114.611,0,256s114.611,256,256,256s256-114.611,256-256S397.389,0,256,0z M256,486.4 C128.759,486.4,25.6,383.249,25.6,256S128.759,25.6,256,25.6S486.4,128.759,486.4,256S383.241,486.4,256,486.4z"/> </g> </g> <g> <g> <path d="M384,243.2H268.8V128c0-7.066-5.734-12.8-12.8-12.8c-7.066,0-12.8,5.734-12.8,12.8v115.2H128 c-7.066,0-12.8,5.734-12.8,12.8c0,7.066,5.734,12.8,12.8,12.8h115.2V384c0,7.066,5.734,12.8,12.8,12.8 c7.066,0,12.8-5.734,12.8-12.8V268.8H384c7.066,0,12.8-5.734,12.8-12.8C396.8,248.934,391.066,243.2,384,243.2z"/> </g> </g> </svg>';
				$cellText .= "</span>";
			}else{
				$cellText	 = 'Available';
			}
		}

		if ($this->admin) {
			$class .= ' admin';
		}

		$label	= date('d-m-Y', strtotime($date));

		if($this->mobile){
			return [
				'text'	=> $cellText,
				'data'	=> $hostData,
				'event'	=> $event
			];
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
	public function writeOrientationCell( $schedule, $date, $startTime) {
		//get event which starts on this date and startTime
		$event		= $this->getScheduleEvent($schedule, $date, $startTime);
		$rowSpan	= '';
		$class		= 'orientation';

		if (!$event){
			$cellText = 'Available';
		}else {
			$hostId			= $event->organizer_id;
			$dataset	= "data-starttime='{$event->starttime}' data-endtime='{$event->endtime}' data-event_id='$event->id'";
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

			if(!$this->mobile){
				//check how many rows this event should span
				$toTime		= new \DateTime($event->endtime);
				$fromTime	= new \DateTime($event->starttime);
				$interval	= $toTime->diff($fromTime);
				$value		= ($interval->h*60+$interval->i)/15;
				$rowSpan	= "rowspan='$value'";
			}

			$this->nextStartTimes[$date] = $endTime;
		}

		// for Mobile view
		if($this->mobile){
			return [
				'text'		=> $cellText,
				'data'		=> $dataset,
				'event'		=> $event
			];
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
		$html 	= "<div class='day-wrapper-mobile'>";

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
						$data			= $this->writeMealCell($schedule, $date, $startTime, true);
					//Orientation schedule
					}elseif(!$onlyMeals){
						$data			= $this->writeOrientationCell($schedule, $date, $startTime, true);
						if($data['event']){
							$description	.=	' - '.$data['event']->endtime;
						}
					}
					$content	= $data['text'];
					
					if(
						$content != 'Available'		||	// There is something scheduled
						(
							$mealScheduleRow		&&	// Or it is a mealschedule row
							(
								$startTime == $this->lunchStartTime	||	// and this is the lunch
								$startTime == $this->dinerTime			// or diner time
							)
						)
					){
						$admin	= '';
						if($this->admin){
							$admin = 'admin';
						}

						$html 	.= "<div class='session-wrapper-mobile $admin' style='display:flex;'>";
							$html 	.= "<div style='padding-right:10px;'>";
								$html 	.= "<strong>$description</strong>:<br>";
								$html 	.=	$content.'<br>';
							$html 	.= "</div>";

							if(
								$this->admin	||	// We can change any event
								(
									isset($data['event']->organizer_id)	&&						// an organizer id is set
									$data['event']->organizer_id	== get_current_user_id()	// we are the organizer
								)
							){
								$icon	= '';
								$class	= '';
								if($this->admin){
									$class	= 'admin ';
								}
								if($mealScheduleRow){
									if(strpos($content, 'Available') === false){
										$icon	= '<svg xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 24 24" width="24px" height="24px">    <path d="M 10 2 L 9 3 L 4 3 L 4 5 L 20 5 L 20 3 L 15 3 L 14 2 L 10 2 z M 5 7 L 5 22 L 19 22 L 19 7 L 5 7 z M 8 9 L 10 9 L 10 20 L 8 20 L 8 9 z M 14 9 L 16 9 L 16 20 L 14 20 L 14 9 z"/></svg>';
										$class	.= 'remove-host-mobile';
									}
								}else{
									$icon	= '<svg xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 30 30" width="30px" height="30px">    <path d="M 22.828125 3 C 22.316375 3 21.804562 3.1954375 21.414062 3.5859375 L 19 6 L 24 11 L 26.414062 8.5859375 C 27.195062 7.8049375 27.195062 6.5388125 26.414062 5.7578125 L 24.242188 3.5859375 C 23.851688 3.1954375 23.339875 3 22.828125 3 z M 17 8 L 5.2597656 19.740234 C 5.2597656 19.740234 6.1775313 19.658 6.5195312 20 C 6.8615312 20.342 6.58 22.58 7 23 C 7.42 23.42 9.6438906 23.124359 9.9628906 23.443359 C 10.281891 23.762359 10.259766 24.740234 10.259766 24.740234 L 22 13 L 17 8 z M 4 23 L 3.0566406 25.671875 A 1 1 0 0 0 3 26 A 1 1 0 0 0 4 27 A 1 1 0 0 0 4.328125 26.943359 A 1 1 0 0 0 4.3378906 26.939453 L 4.3632812 26.931641 A 1 1 0 0 0 4.3691406 26.927734 L 7 26 L 5.5 24.5 L 4 23 z"/></svg>';;
									$class	.= 'edit-session-mobile orientation';
								}
								
								if(!empty($icon)){
									$dateStr	= date('d-m-Y', strtotime($date));
									$html 	.= "<div class='$class' {$data['data']} data-date='$dateStr' data-isodate='$date' style='margin-left: auto;'>";
										$html 	.= $icon;
									$html 	.= "</div>";
								}
							}
						$html 	.= "</div>";
					}
				}

				if($startTime == $schedule->endtime){
					break;
				}
				$startTime		= $endTime;
			}//end true
		
		$html 	.= "</div>";
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
					<input type='hidden' name='event-id'>
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
