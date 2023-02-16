<?php
namespace SIM\EVENTS;
use SIM;
use WP_Error;

class Schedules{
	public $tableName;
	public $sessionTableName;
	public $user;
	public $admin;
	public $events;
	public $lunchStartTime;
	public $lunchEndTime;
	public $dinerTime;
	public $noPermissionText;
	public $tdLabels;
	public $schedules;
	public $currentSchedule;
	public $nextStartTimes;
	public $onlyMeals;
	protected $mobile;
	protected $currentSession;
	
	public function __construct(){
		global $wpdb;

		$this->tableName		= $wpdb->prefix . 'sim_schedules';
		$this->sessionTableName	= $wpdb->prefix . 'sim_schedule_sessions';

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

		$sql = "CREATE TABLE {$this->sessionTableName} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			schedule_id mediumint(9) NOT NULL,
			date varchar(80) NOT NULL,
			starttime varchar(80) NOT NULL,
			post_ids longtext NOT NULL,
			event_ids longtext NOT NULL,
			meal boolean NOT NULL,
			PRIMARY KEY  (id)
		) $charsetCollate;";

		maybe_create_table($this->sessionTableName, $sql );
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
				$this->currentSchedule	= $schedule;

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
		wp_enqueue_style('sim_schedules_css');
		wp_enqueue_script('sim_schedules_script');

		$schedules	= '';
		$form		= $this->addScheduleForm();

		foreach($this->schedules as $this->currentSchedule){
			$schedules	.= $this->showSchedule();
		}

		$html	= "<div class='schedules_wrapper' style='position: relative;'>";
			$html	.= $this->addModals();
			$html	.= $schedules;
			$html	.= $form;
		$html	.= "</div>";
			
		return $html;
	}

	public function showSchedule() {
		ob_start();

		if (
			$this->currentSchedule->target											&&		// There is a target set
			(
				$this->currentSchedule->target == $this->user->ID 					||		// Target is me
				$this->currentSchedule->target == SIM\hasPartner($this->user->ID)			// Target is the partner
			) 																		&&
			!$this->admin 															&& 		// We are not admin
			!$this->currentSchedule->published												// Schedule is not yet published
		){
			return '';
		}
		
		$this->onlyMeals 	= true;
		
		// Show also the orientation schedule if:
		if (
			$this->admin											||		// We are an admin
			!empty($this->getPersonalOrientationEvents($this->currentSchedule)) 	||		// We are in the schedule
			$this->currentSchedule->target == $this->user->ID							// The schedule is meant for us
		){
			$this->onlyMeals = false;
		}

		// Load all schedule events
		$this->getScheduleSessions();

		// Prepare the user select
		if (strpos($this->currentSchedule->name, 'family') !== false ){
			$args = array(
				'meta_query' => array(
					array(
						'key'		=> 'last_name',
						'value'		=> str_replace(' family', '', $this->currentSchedule->name),
						'compare'	=> 'LIKE'
					)
				)
			);
		//Full name
		}else{
			$args = array(
				'search'			=> $this->currentSchedule->name,
				'search_columns' 	=> ['display_name'],
			);
		}

		// Do not show dates in the past
		$this->currentSchedule->startdate	= max([date('Y-m-d'), $this->currentSchedule->startdate]);
		
		?>
		<div class='schedules_div table-wrapper' data-id="<?php echo $this->currentSchedule->id; ?>" data-target="<?php echo $this->currentSchedule->name; ?>">
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
			if(!is_numeric($this->currentSchedule->target) || !get_userdata($this->currentSchedule->target)){
				?>
				<div class='warning'>
					This schedule has no website user connected to it.<br>
					Please <button type="button" class="button small schedule_action edit_schedule" data-schedule_id="<?php echo $this->currentSchedule->id;?>">Edit</button> the schedule to add one.
				</div>
				<?php
			}
			?>
			<h3 class="table_title">
				Schedule for <?php echo $this->currentSchedule->name;?>
			</h3>
			<h3 class="table_title sub-title">
				<?php echo $this->currentSchedule->info;?>
			</h3>

			<?php
			// Only render when not on a mobile device
			if($this->mobile){
				$this->showMobileSchedule();
			}else{
				?>
				<p>Click on an available date to indicate you want to host.<br>Click on any date you are subscribed for to unsubscribe</p>

				<table class="sim-table schedule" data-id="<?php echo $this->currentSchedule->id; ?>" data-action='update_schedule' data-lunch='<?php echo $this->currentSchedule->lunch ? 'true' : 'false';?>'>
					<thead>
						<tr>
							<th class='sticky'>Dates</th>
							<?php
							$date		= $this->currentSchedule->startdate;
							while(true){
								$dateStr		= date('d F Y', strtotime($date));
								$dateTime		= strtotime($date);
								$dayName		= date('l', $dateTime);
								$formatedDate	= date(DATEFORMAT, $dateTime);
								echo "<th data-date='$dateStr' data-isodate='$date'>$dayName<br>$formatedDate</th>";

								if ($date == $this->currentSchedule->enddate) {
									break;
								}

								$date	= date('Y-m-d', strtotime('+1 day', $dateTime) );
							}
							?>
						</tr>
					</thead>
					<tbody class="table-body">
						<?php
						echo $this->writeRows($this->currentSchedule, $this->onlyMeals);
						?>
					</tbody>
				</table>

				<?php
				if($this->admin){
					?>
					<div class='schedule_actions'>
						<button type='button' class='button schedule_action edit_schedule' data-schedule_id='<?php echo $this->currentSchedule->id;?>'>Edit</button>
						<button type='button' class='button schedule_action remove_schedule' data-schedule_id='<?php echo $this->currentSchedule->id;?>'>Remove</button>
						<?php
						//schedule is not yet set.
						if(!$this->currentSchedule->published && $this->currentSchedule->target != 0){
							echo "<button type='button' class='button schedule_action publish' data-target='{$this->currentSchedule->target}' data-schedule_id='{$this->currentSchedule->id}'>Publish</button>";
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

	public function showMobileSchedule() {
		?>
		<div class="add-host-mobile-wrapper modal hidden">
			<div class="modal-content">
				<span class="close">&times;</span>
				<form action="" method="post">
					<input type='hidden' name='schedule_id' value='<?php echo $this->currentSchedule->id;?>'>

					<?php
					if($this->currentSchedule->lunch){
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
					$date				= $this->currentSchedule->startdate;
					$availableLunches	= [];
					$availableDiners	= [];

					while(true){
						$dateTime		= strtotime($date);

						if($this->currentSchedule->lunch){
							$event		= $this->getScheduleSession($date, '12:00');
							if(!$event){
								$availableLunches[]	= $date;
							}
						}

						$event			= $this->getScheduleSession($date, '18:00');
						if(!$event){
							$availableDiners[]	= $date;
						}

						if ($date == $this->currentSchedule->enddate) {
							break;
						}
						$date			= date('Y-m-d', strtotime('+1 day', $dateTime) );
					}

					$nameString	= $this->currentSchedule->name;
					if(strpos($nameString, 'Family') !== false){
						$nameString	= "the $nameString";
					}
					?>
					<div class='lunch select-wrapper hidden'>
						<label>
							Select the date(s) to host <?php echo $nameString;?> for lunch
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
					<div class='diner select-wrapper <?php if($this->currentSchedule->lunch){echo 'hidden';}?>'>
						<label>
							Select the date(s) to host <?php echo $nameString;?> for diner
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

		$date		= $this->currentSchedule->startdate;
		while(true){
			$html			= $this->getMobileDay($date);
			echo $html.'<br>';

			if ($date == $this->currentSchedule->enddate) {
				break;
			}

			$date	= date('Y-m-d', strtotime('+1 day', $dateTime) );
		}

		if($this->onlyMeals){
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

	protected function parsePostsAndEvents(&$result){
		global $wpdb;

		$result->post_ids	= unserialize($result->post_ids);
		$result->event_ids	= unserialize($result->event_ids);

		$ids				= implode(',', $result->post_ids);
		$result->posts		=  $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE ID IN ($ids)");

		$ids				= implode(',', $result->event_ids);
		$result->events		= $wpdb->get_results("SELECT * FROM {$this->events->tableName} WHERE id IN ($ids)");
	}

	/**
	 * Get all sessions of a schedule
	 *
	 * @return 	object|false			The event or false if no event
	*/
	public function getScheduleSessions(){
		global $wpdb;

		if(isset($this->currentSchedule->sessions)){
			return;
		}

		$query	=  "SELECT * FROM $this->sessionTableName WHERE `schedule_id`={$this->currentSchedule->id}";

		$results	= $wpdb->get_results($query);

		$this->currentSchedule->sessions	= [];

		foreach($results as $index=>&$result){
			if($this->onlyMeals && !$result->meal){
				unset($results[$index]);
			}else{
				$this->parsePostsAndEvents($result);

				$date				= $result->events[0]->startdate;
				$starttime			= $result->events[0]->starttime;

				if(!isset($this->currentSchedule->sessions[$date])){
					$this->currentSchedule->sessions[$date]	= [];
				}
				$this->currentSchedule->sessions[$date][$starttime]	= $result;
			}
		}

		return $results;
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
	public function getScheduleSession($startDate, $startTime){
		//get event which starts on this date and time
		$this->getScheduleSessions();

		if(
			!isset($this->currentSchedule->sessions[$startDate])	||
			!isset($this->currentSchedule->sessions[$startDate][$startTime])
		){
			return false;
		}

		$this->currentSession	= $this->currentSchedule->sessions[$startDate][$startTime];

		return $this->currentSchedule->sessions[$startDate][$startTime];
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
	public function getScheduleEvents($scheduleId='', $startDate, $startTime){
		global $wpdb;

		if(empty($scheduleId)){
			$scheduleId	= $this->currentSchedule->id;
		}

		$query	=  "SELECT * FROM {$this->events->tableName} WHERE `schedule_id` = '{$scheduleId}' AND startdate = '$startDate' AND starttime='$startTime'";
		return	$wpdb->get_results($query);
	}

	/**
	 * Get a specific schedule session
	 *
	 * @param	int		$sessionId		the session id
	 *
	 * @return 	object|false			The event or false if no event
	*/
	public function getSessionEvent($sessionId){
		global $wpdb;

		$query	=  "SELECT * FROM `wp_sim_schedule_sessions` WHERE wp_sim_schedule_sessions.id=$sessionId";

		$results	= $wpdb->get_results($query);

		if(empty($results)){
			return false;
		}

		$session			= $results[0];
		$this->parsePostsAndEvents($session);

		return $session;
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
	public function writeMealCell($date, $startTime){
		//get event which starts on this date and time
		$class	= 'meal';

		if($startTime == $this->lunchStartTime){
			$rowSpan						= "rowspan='4'";
			$this->nextStartTimes[$date]	= $this->lunchEndTime;
		}else{
			$rowSpan = '';
		}

		if(isset($this->currentSchedule->sessions[$date][$startTime])){
			$data			= $this->currentSchedule->sessions[$date][$startTime];
			$title			= $data->posts[0]->post_title;
			$url			= get_permalink($data->posts[0]->ID);
			$cellText		= "<a href='$url'>$title</a>";
			$hostId			= $data->events[0]->organizer_id;
			$date			= $data->events[0]->startdate;
			$startTime		= $data->events[0]->starttime;
			$hostData		= "";
			if(is_numeric($hostId)){
				$hostData	.= " data-host=$hostId data-session_id='$data->id'";
			}

			$class 			.= ' selected';
			$partnerId 		= SIM\hasPartner($this->user->ID);
			//Host is current user or the spouse
			if ($hostId == $this->user->ID || $hostId == $partnerId){
				$class 			.= ' own';
							
				$menu		= get_post_meta($data->posts[0]->ID, 'recipe_keyword', true);
				if(empty($menu)){
					$menu	= 'Enter recipe keyword';
				}
			
				$cellText .= "<br><span class='keyword'>$menu</span>";
			//current user is the target or is admin
			} elseif ( !$this->admin && $this->currentSchedule->target != $this->user->ID && $this->currentSchedule->target != $partnerId){
				//$cellText = 'Taken';
			}
		} else {
			if($this->mobile){
				$dateStr	= date(DATEFORMAT, strtotime($date));
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

		$label	= date(DATEFORMAT, strtotime($date));

		if($this->mobile){
			return [
				'text'	=> $cellText,
				'data'	=> $hostData,
				'event'	=> $data->events[0]
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
	public function writeOrientationCell( $date, $startTime) {
		$rowSpan	= '';
		$class		= 'orientation';

		if(isset($this->currentSchedule->sessions[$date][$startTime])){
			$data		= $this->currentSchedule->sessions[$date][$startTime];
			$event		= $data->events[0];

			$hostId			= $event->organizer_id;
			$dataset	= "data-starttime='{$event->starttime}' data-endtime='{$event->endtime}' data-session_id='$data->id'";
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
		}else{
			$cellText = 'Available';
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

		$label	= date(DATEFORMAT, strtotime($date));
		
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
	public function writeRows(){
		$html 					= '';
		
		$this->nextStartTimes	= [];

		//loop over the rows
		$startTime	= $this->currentSchedule->starttime;

		//loop until we are at the endtime
		while(true){
			//If we do not have an orientation schedule, go strait to the dinner row
			if(
				$startTime >= $this->lunchEndTime	&&
				$startTime < $this->dinerTime		&&
				!$this->currentSchedule->orientation
			){
				$startTime = $this->dinerTime;
			}
			
			$date				= $this->currentSchedule->startdate;
			$mealScheduleRow	= true;
			$endTime			= date(TIMEFORMAT, strtotime('+15 minutes', strtotime($startTime)));
			$extra				= '';
			
			if(
				$startTime == $this->lunchStartTime	&&		// Starttime of the lunch
				$this->currentSchedule->lunch							// And the schedule includes a lunch
			){
				$extra				= 'rowspan="4"';		// Span 4 rows
				$description		= 'Lunch';
			}elseif(
				$startTime > $this->lunchStartTime	&&		// Time is past the start lunch time
				$startTime < $this->lunchEndTime	&& 		// but before the end
				$this->currentSchedule->lunch							// And the schedule includes a lunch
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
						$cells .= $this->writeMealCell($date, $startTime);
					//Orientation schedule
					}else{
						$cells .= $this->writeOrientationCell($date, $startTime);
					}
				}

				if($date == $this->currentSchedule->enddate){
					break;
				}

				$date	= date('Y-m-d', strtotime('+1 day', strtotime($date)));
			}
			
			//Show the row if we can see all rows or the row is a mealschedule row
			if (!$this->onlyMeals || $mealScheduleRow ) {
				$html  .= "<tr class='table-row' data-starttime='$startTime' data-endtime='$endTime'>";
					$html 	.= "<td $extra class='sticky' label=''><strong>$description</strong></td>";
					$html	.= $cells;
				$html .= "</tr>";
			}

			if($startTime == $this->currentSchedule->endtime){
				break;
			}
			$startTime		= $endTime;
		}//end row
		
		return $html;
	}

	/**
	 * Write all rws of a schedule table
	 *
	 * @param	string	$date		the date
	 *
	 * @return 	array					Rows html
	*/
	public function getMobileDay($date){
		$dateTime		= strtotime($date);
		$dayName		= date('l', $dateTime);
		$formatedDate	= date(DATEFORMAT, $dateTime);

		$html 	= "<div class='day-wrapper-mobile'>";
			$html 	.= "<strong>$dayName $formatedDate</strong><br>";

			//loop over the rows
			$startTime	= $this->currentSchedule->starttime;

			//loop until we are at the endtime
			while(true){
				//If we do not have an orientation schedule, go strait to the dinner row
				if(
					$startTime >= $this->lunchEndTime	&&
					$startTime < $this->dinerTime		&&
					!$this->currentSchedule->orientation
				){
					$startTime = $this->dinerTime;
				}
				
				$mealScheduleRow	= true;
				$endTime			= date(TIMEFORMAT, strtotime('+15 minutes', strtotime($startTime)));
				
				if(
					$startTime >= $this->lunchStartTime	&&		// Time is past the start lunch time
					$startTime < $this->lunchEndTime	&& 		// but before the end
					$this->currentSchedule->lunch				// And the schedule includes a lunch
				){
					if($startTime != $this->lunchStartTime){
						$startTime		= $endTime;
						continue;
					}
					$description		= 'Lunch';
				}elseif($startTime == $this->dinerTime){
					$description		= 'Dinner';
				}else{
					$mealScheduleRow	= false;
					$description		= $startTime;
				}

				//Show the row if we can see all rows or the row is a mealschedule row
				if (!$this->onlyMeals || $mealScheduleRow ) {
					//mealschedule
					if($mealScheduleRow){
						$data			= $this->writeMealCell($date, $startTime, true);
					//Orientation schedule
					}elseif(!$this->onlyMeals){
						$data			= $this->writeOrientationCell($date, $startTime, true);
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
									$dateStr	= date(DATEFORMAT, strtotime($date));
									$html 	.= "<div class='$class' {$data['data']} data-date='$dateStr' data-isodate='$date' style='margin-left: auto;'>";
										$html 	.= $icon;
									$html 	.= "</div>";
								}
							}
						$html 	.= "</div>";
					}
				}

				if($startTime == $this->currentSchedule->endtime){
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
					$id	= 'add_host';
					if($this->mobile){
						$id	= 'add_host_mobile';
					}
					echo SIM\addSaveButton($id,'Add host','update_schedule');
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
					<input type='hidden' name='session-id'>
					<input type='hidden' name='host_id'>
					
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
