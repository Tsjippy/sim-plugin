<?php
namespace SIM\EVENTS;
use SIM;
use WP_Error;

class Schedule{
	function __construct(){
		global $wpdb;

		$this->tableName		= $wpdb->prefix . 'sim_schedules';

		$this->createDbTable();

		$this->getSchedules();
		
		$this->user 			= wp_get_current_user();
		
		if(in_array('personnelinfo', $this->user->roles)){
			$this->admin	=	true;
		}else{
			$this->admin	= false;
		}

		$this->events		= new Events();
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
	 * Get a specific schedule from the db\
	 * 
	 * @param	int	$id		the schedule ID
	 * 
	 * @return 	object		the schedule
	*/
	function findScheduleById($id){
		foreach($this->schedules as $schedule){
			if($schedule->id == $id)
				return $schedule;
		}
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
					<?php
					echo SIM\userSelect('', $only_adults=true, $families=true, $class='', $id='host', $args=[], $userId='', $exclude_ids=[], $type='list');
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
					
					<h3>Add an orientation session</h3>

					<label>
						<h4>Date:</h4>
						<input type='date' name='date' required>
					</label>

					<label>
						<h4>Select a start time:</h4>
						<input type="time" name="starttime" step="900" min="08:00" max="18:00" required>
					</label>
					
					<label>
						<h4>Select an end time:</h4>
						<input type="time" name="endtime"  step="900" min="08:00" max="18:00" required>
					</label>
					
					<label>
						<h4>What is the subject</h4>
						<input type="text" name="subject" required>
					</label>
					
					<label>
						<h4>Location</h4>
						<input type="text"  name="location">
					</label>
					
					<?php
					//select for person in charge if an admin
					if($this->admin	==	true){
						?>
						<label for="host"><h4>Who is in charge</h4></label>
						<?php
						echo SIM\userSelect('', $only_adults=true, $families=false, $class='', $id='host', $args=[], $userId='', $exclude_ids=[], $type='list');
					}
					
					echo SIM\addSaveButton('add_timeslot','Add time slot','update_event add_schedule_row');
					?>
				</form>
			</div>
		</div>
		
		<?php
		return ob_get_clean();
	}

	/**	
	 * Create the form to add a schedule
	 * 
	 * @return 	string		the form html
	*/
	function addScheduleForm(){
		ob_start();
		if($this->admin == true){
			?>			
			<h3 style='text-align:center;'>Add a schedule</h3>
			<form id='add-schedule-form'>
				<input type="hidden" name="target_id">
				
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
				echo SIM\addSaveButton('add_schedule', 'Add schedule');
			echo '</form>';
		}
		return ob_get_clean();
	}
	
	/**
	 * Get all existing schedules
	 * 
	 * @return 	string		the schedules html
	*/
	function showschedules(){	
		ob_start();
		?>
		<div class='schedules_wrapper' style='z-index: 99;position: relative;'>
		<?php
		echo $this->addModals();
		
		//indicator if othing to show
		$nothing	= true;

		foreach($this->schedules as $key=>$schedule){
			//do not show schedule if not published and it is for us
			if(($schedule->target == $this->user->ID or $schedule->target == SIM\hasPartner($this->user->ID)) and !$this->admin and !$schedule->published) continue;
			
			$nothing	= false;
			$onlyMeals = true;
			
			//Show also the orientation schedule if:
			if(
				//We are an admin
				$this->admin	==	true									or
				//We are in the schedule
				!empty($this->getPersonalOrientationEvents($schedule)) 	or
				//The schedule is meant for us
				$schedule->target == $this->user->ID
			){
				$onlyMeals = false;
			}
			
			?>
			<div class='schedules_div form-table-wrapper'>
				<div class="modal publish_schedule hidden">
					<!-- Modal content -->
					<div class="modal-content">
						<span id="modal_close" class="close">&times;</span>
						<form action="" method="post" id="publish_schedule_form">
							<input type='hidden' name='schedule_id'>
						
							<p>
								Please select the user or family this schedule should be published for.<br>
								The schedule will show up on the dashboard of these persons after publish.
							</p>
							<?php
							//if looking for a family
							if(strpos($schedule->name,'family') !== false){
								$args = array(
									'meta_query' => array(
										array(
											'key'		=> 'last_name',
											'value'		=> str_replace(' family','',$schedule->name),
											'compare'	=> 'LIKE'
										)
									)
								);
							//Full name
							}else{
								$args = array(
									'search' => $schedule->name,
									'search_columns' => ['display_name'],
								);
							}
							
							echo SIM\userSelect($text='',$only_adults=true,$families=true,$class='',$id='schedule_target',$args);
							echo SIM\addSaveButton('publish_schedule','Publish this schedule'); 
							?>
						</form>
					</div>
				</div>
				<h3 class="table_title">
					Schedule for <?php echo $schedule->name;?><br>
					<?php echo $schedule->info;?>
				</h3>
				<p>Click on an available date to indicate you want to host.<br>Click on any date you are subscribed for to unsubscribe</p>
				<table class="sim-table schedule" data-id="<?php echo $schedule->id; ?>" data-target="<?php echo $schedule->name; ?>" data-action='update_schedule'>
					<thead>
						<tr>
							<th>Dates</th>
							<?php
							$date		= $schedule->startdate;
							while(true){	
								$dateStr		= date('d F Y', strtotime($date));	
								$dateTime		= strtotime($date);					
								$dayName		= date('l', $dateTime);
								$formatedDate	= date('d-m-Y', $dateTime);
								echo "<th data-date='$dateStr' data-isodate='$date'>$dayName<br>$formatedDate</th>";

								if($date == $schedule->enddate) break;

								$date	= date('Y-m-d',strtotime('+1 day', $dateTime));
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
				if($this->admin == true){
					?>
					<div class='schedule_actions'>
						<button type='button' class='button schedule_action remove_schedule' data-schedule_id='<?php echo $schedule->id;?>'>Remove</button>
					<?php
					//schedule is not yet set.
					if(!$schedule->published and $schedule->target != 0){
						echo "<button type='button' class='button schedule_action publish' data-target='{$schedule->target}' data-schedule_id='{$schedule->id}'>Publish</button>";
					}
					echo '</div>';
				}
				?>
			</div>
			<?php
		}
		$form	= $this->addScheduleForm();
		if($nothing and empty($form)){
			return "There are currently no signup requests.";
		}else{
			echo $form;
		}
		echo '</div>';
			
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

		$query	= "SELECT * FROM {$this->events->tableName} WHERE `schedule_id` = '{$schedule->id}' AND onlyfor={$this->user->ID} AND starttime != '18:00'";
		if($schedule->lunch){
			$query	.= " AND starttime != '12:00'";
		}
		return $wpdb->get_results();
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

		if($startTime == '12:00'){
			$rowSpan						= "rowspan='4'";
			$this->nextStartTimes[$date]	= '13:00';
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
			if($hostId == $this->user->ID or $hostId == $partnerId){				
				$menu		= get_post_meta($event->post_id,'recipe_keyword',true);
				if(empty($menu)){
					$menu	= 'Enter recipe keyword';
				}
			
				$cellText .= "<span class='keyword'>$menu</span>";
			//current user is the target or is admin
			}elseif(!$this->admin and $schedule->target != $this->user->ID and $schedule->target != $partnerId){
				$cellText = 'Taken';
			}
		}else{
			$cellText	 = 'Available';
		}

		if($this->admin) $class .= ' admin';
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
			if(is_numeric($hostId)){
				$hostData	= "data-host=$hostId";
			}else{
				$hostData	= "";
			}
			$title		= get_the_title($event->post_id);
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
			//we are admin
			$this->admin == true or
			//We are the organizer
			$this->user->ID == $event->organizer_id or 
			//This cell  is available
			$cellText == 'Available'			
		){
			if($this->admin) $class .= ' admin';
		}
		
		return "<td class='$class' $rowSpan $hostData>$cellText</td>";
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
			if($startTime >= '13:00' and $startTime < '18:00' and !$schedule->orientation)	$startTime = '18:00';
			
			$date				= $schedule->startdate;
			$mealScheduleRow	= false;
			$endTime			= date("H:i", strtotime('+15 minutes', strtotime($startTime)));
			
			if($startTime >= '12:00' and $startTime < '13:00' and $schedule->lunch){
				$mealScheduleRow	= true;
				if($startTime >= '12:00'){
					$rowSpan			= 'rowspan="4"';
					$description		= 'Lunch';
				}
			}elseif($startTime == '18:00'){
				$mealScheduleRow	= true;
				$rowSpan			= '';
				$description		= 'Dinner';
			}else{
				$rowspan			= '';
				$description		= $startTime;
			}
			
			//Show the row if we can see all rows or the row is a mealschedule row
			if(!$onlyMeals or $mealScheduleRow){
				$html  .= "<tr class='table-row' data-starttime='$startTime' data-endtime='$endTime'>";
					if($startTime > '12:00' and $startTime < '13:00' and $schedule->lunch){
						$html .= "<td class='hidden'><b>$description</b></td>";
					}else{
						$html .= "<td $rowSpan><b>$description</b></td>";
					}
				
					//loop over the dates to write a cell per date in this timerow
					while(true){
						if($this->nextStartTimes[$date] > $startTime){
							$html .= "<td class='hidden'>Available</td>";
						}else{
							//mealschedule
							if($mealScheduleRow){
								$html .= $this->writeMealCell($schedule, $date, $startTime);
							//Orientation schedule
							}else{
								$html .= $this->writeOrientationCell($schedule, $date, $startTime);	
							}
						}

						if($date == $schedule->enddate) break;

						$date	= date('Y-m-d',strtotime('+1 day',strtotime($date)));
					}
				$html .= "</tr>";
			}

			if($startTime == $schedule->endtime) break;
			$startTime		= $endTime;
		}//end row
		
		return $html;
	}

	/**
	 * Add a new event to the db
	 * 
	 * @param	object	$event			the event
	 * 
	 * @return 	array					Rows html
	*/
	function addEventToDb($event){
		global $wpdb;

		$result = $wpdb->insert(
			$this->events->tableName, 
			$event
		);
		
		if($wpdb->last_error !== ''){
			return new WP_Error('schedules', $wpdb->print_error());
		}

		$eventId   = $wpdb->insert_id;

		//Create event warning
		$start	= new \DateTime($event['startdate'].' '.$event['starttime'], new \DateTimeZone(wp_timezone_string()));

		//Warn 15 minutes in advance
		$start	= $start->getTimestamp() - 15 * MINUTE_IN_SECONDS;
		
		wp_schedule_single_event($start, 'send_event_reminder_action', [$eventId]);
	}

	/**
	 * Add new events to the db when a new activity is schedules
	 * 
	 * @param	string	$title			the title of the event
	 * @param	object	$schedule		the schedule of the event
	 * @param	bool	$addHostPartner	Whether to add an event for the host partner as well. Default true
	 * @param	bool	$addPartner		Whether to add an event for the schedule target partner as well. Default true
	*/
	function addScheduleEvents($title, $schedule, $addHostPartner=true, $addPartner=true){
		$event							= [];	
		$event['startdate']				= $this->date;
		$event['starttime']				= $this->starttime;
		$event['enddate']				= $this->date;
		$event['endtime']				= $this->endtime;	
		$event['location']				= $this->location;
		$event['organizer_id']			= $this->hostId;
		$event['schedule_id']			= $this->schedule_id;
		
		$hostPartner					= false;
		if(is_numeric($this->hostId)){
			if($addHostPartner and SIM\hasPartner($this->hostId)){
				$hostPartner	= true;
				$event['organizer']				= get_userdata($this->hostId)->last_name.' family';
			}else{
				$event['organizer']				= get_userdata($this->hostId)->display_name;
			}
		}elseif(!empty($_POST['host'])){
			$event['organizer']					= $_POST['host'];
		}

		if($addPartner){
			$partnerId	= SIM\hasPartner($schedule->target);
		}else{
			$partnerId	= false;
		}

		//clean title
		$title	= str_replace(" with {$event['organizer']}", '', $title);
		$title	= str_replace("Hosting {$this->name} for ", '', $title);

		//New events
		$eventArray		= [
			[
				'title'		=>ucfirst($title)." with {$event['organizer']}",
				'onlyfor'	=>[$schedule->target, $partnerId]
			]
		];

		if(is_numeric($this->hostId)){
			$eventArray[] =
			[
				'title'		=>"Hosting {$this->name} for $title",
				'onlyfor'	=>[$this->hostId, $hostPartner]
			];
		}
		foreach($eventArray as $a){
			$post = array(
				'post_type'		=> 'event',
				'post_title'    => $a['title'],
				'post_content'  => $a['title'],
				'post_status'   => "publish",
				'post_author'   => $this->hostId
			);
			$postId 	= wp_insert_post( $post,true,false);
			update_post_meta($postId,'eventdetails',$event);
			update_post_meta($postId,'onlyfor',$a['onlyfor']);

			foreach($a['onlyfor'] as $userId){
				if(is_numeric($userId)){
					$event['onlyfor']	= $userId;
					$event['post_id']	= $postId;
					$this->addEventToDb($event);
				}
			}
		}
	}
	
	/**
	 * Add a new schedule
	 * 
	 * @return array	text, new schedules list in html
	*/
	function addSchedule(){
		global $wpdb;
	
		$name		= sanitize_text_field($_POST['target_name']);
		//check if schedule already exists
		if($wpdb->get_var("SELECT * FROM {$this->tableName} WHERE `name` = '$name'") != null){
			return new WP_Error('schedule', "A schedule for $name already exists!");
		}

		$info		= sanitize_text_field($_POST['schedule_info']);

		if(empty($_POST['skiplunch'])){
			$lunch	= true;
		}else{
			$lunch	= false;
		}

		if(empty($_POST['skiporientation'])){
			$orientation	= true;
		}else{
			$orientation	= false;
		}

		$startDateStr	= $_POST['startdate'];
		$startDate		= strtotime($startDateStr);
		$endDateStr		= $_POST['enddate'];
		$endDate		= strtotime($endDateStr);

		if($orientation){
			$startTime	= '08:00';
		}elseif($lunch){
			$startTime	= '12:00';
		}else{
			$startTime	= '18:00';
		}						

		if($startDate > $endDate) return new WP_Error('schedule', "Ending date cannot be before starting date");

		$wpdb->insert(
			$this->tableName, 
			array(
				'target'		=> $_POST['target_id'],
				'name'			=> $name,
				'info'			=> $info,
				'lunch'			=> $lunch,
				'orientation'	=> $orientation,
				'startdate'		=> $startDateStr,
				'enddate'		=> $endDateStr,
				'starttime'		=> $startTime,
				'endtime'		=> '18:00',
			)
		);
		
		$this->getSchedules();
		return [
			'message'		=> "Succesfully added a schedule for $name",
			'html'			=> $this->showschedules()
		];
	}

	/**
	 * Publishes a new schedule
	 * 
	 * @return string	success message
	*/
	function publishSchedule(){
		global $wpdb;
		
		$scheduleId	= $_POST['schedule_id'];

		SIM\updateFamilyMeta($_POST['schedule_target'], 'schedule', $scheduleId);

		$wpdb->update(
			$this->tableName, 
			array(
				'published'	=> true
			),
			array(
				'id'		=> $scheduleId
			)
		);

		return 'Succesfully published the schedule';
	}
	
	/**
	 * Removes a given schedule
	 * 
	 * @param int	$scheduleId	THe id of the schedule to remove	
	 * 
	 * @return string	success message
	*/
	function removeSchedule($scheduleId){
		global $wpdb;

		if(!is_numeric($scheduleId)){
			return new WP_Error('schedules', 'Schedule id should be numeric');
		}

		$schedule	= $this->schedules[$scheduleId];

		//Remove the schedule from the user meta
		if(is_numeric($schedule->target)){
			SIM\updateFamilyMeta($schedule->target,'schedule','delete');
		}
		
		//Delete all the posts of this schedule
		$events = $wpdb->get_results("SELECT * FROM {$this->events->tableName} WHERE schedule_id='$scheduleId'");
		foreach($events as $event){
			wp_delete_post($event->post_id,true);
		}
		
		//Delete all events of this schedule
		$wpdb->delete(
			$this->events->tableName,      
			['schedule_id' => $scheduleId],           
			['%d'],
		);
		
		//Delete the schedule
		$wpdb->delete(
			$this->tableName,
			['id' => $scheduleId],
			['%d'],
		);
			
		//Remove the schedule from the schedules array
		return 'Succesfully removed the schedule';
	}

	/**
	 * Add a new host for a session
	 * 
	 * @return string	success message and new cell html
	*/
	function addHost(){
		$this->schedule_id	= $_POST['schedule_id'];
		$schedule			= $this->findScheduleById($this->schedule_id);

		if(is_numeric($_POST['host'])){
			$this->hostId	= $_POST['host'];
			$host			= get_userdata($this->hostId);

			$partnerId		= SIM\hasPartner($this->hostId);

			if($this->admin != true and $this->hostId != $this->user->ID and $this->hostId != $partnerId) return new WP_Error('No permission', 'No permission to do that!');
			
			if($partnerId){
				$hostName		= $host->last_name.' family';
			}else{
				$hostName		= $host->display_name;
			}
		}else{
			$this->hostId	= '';
			$hostName		= $_POST['host'];
			if($this->admin != true) return new WP_Error('No permission', 'No permission to do that!');
		}

		$this->name			= $schedule->name;

		$this->date			= $_POST['date'];
		$dateStr			= date('d F Y',strtotime($this->date));
		$this->starttime	= $_POST['starttime'];
		if($this->starttime == '12:00' and $schedule->lunch){
			$this->endtime		= '13:00';
			$title				= 'lunch';
			$this->location		= "House of $hostName";
		}elseif($this->starttime == '18:00'){
			$this->endtime		= '19:30'; 
			$title				= 'dinner';
			$this->location		= "House of $hostName";
		}else{
			$title				= sanitize_text_field($_POST['subject']);
			$this->location		= sanitize_text_field($_POST['location']);
			$this->endtime		= $_POST['endtime'];
		}

		if($this->admin == true){
			$message	= "Succesfully added $hostName as a host for {$this->name} on $dateStr";
		}else{
			$message	= "Succesfully added you as a host for {$this->name} on $dateStr";
		}

		if($title == 'lunch' or $title == 'dinner'){
			$this->addScheduleEvents($title, $schedule);
			$html	= $this->writeMealCell($schedule, $this->date, $this->starttime);
		}else{
			$this->addScheduleEvents($title, $schedule, false);
			$html	= $this->writeOrientationCell($schedule, $this->date, $this->starttime);
		}
		
		return [
			'message'	=> $message,
			'html'		=> $html
		];
	}

	/**
	 * Removes a host for a session
	 * 
	 * @return string	success message
	*/
	function removeHost(){
		$this->schedule_id	= $_POST['schedule_id'];
		$schedule		= $this->findScheduleById($this->schedule_id);

		$this->hostId	= $_POST['host'];

		$partnerId		= SIM\hasPartner($this->hostId);
		if($this->admin != true and $this->hostId != $this->user->ID and $this->hostId != $partnerId) return new \WP_Error('Permission error', 'No permission to do that!');
		if($partnerId){
			$hostName		= get_userdata($this->hostId)->last_name.' family';
		}else{
			$hostName		= get_userdata($this->hostId)->display_name;
		}

		$this->date			= $_POST['date'];
		$dateStr			= date('d F Y',strtotime($this->date));
		$this->starttime	= $_POST['starttime'];

		$events	= $this->getScheduleEvents($schedule->id, $this->date, $this->starttime);
		
		foreach($events as $event){
			//delete event_post
			wp_delete_post($event->post_id);

			//delete events
			$this->events->removeDbRows($event->post_id);
		}

		if($this->admin == true){
			$message	= "Succesfully removed $hostName as a host for {$schedule->name} on $dateStr";
		}else{
			$message	= "Succesfully removed you as a host for {$this->name} on $dateStr";
		}

		return $message;
	}

	/**
	 * Adds a keyword to a meal session
	 * 
	 * @return string	success message
	*/
	function addMenu(){
		$scheduleId		= $_POST['schedule_id'];
		$schedule		= $this->findScheduleById($scheduleId);

		$date			= sanitize_text_field($_POST['date']);

		$startTime		= sanitize_text_field($_POST['starttime']);

		$menu			= sanitize_text_field($_POST['recipe_keyword']);

		$events			= $this->getScheduleEvents($schedule->id, $date, $startTime);
		foreach($events as $event){
			update_post_meta($event->post_id, 'recipe_keyword', $menu);
		}

		if(count(explode(' ', $menu)) == 1){
			return "Succesfully added the keyword $menu";
		}
		return "Succesfully added the keywords $menu";
	}
}