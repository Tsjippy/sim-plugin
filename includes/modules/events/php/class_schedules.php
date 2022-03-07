<?php
namespace SIM\EVENTS;
use SIM;

class Schedule{
	function __construct(){
		global $wpdb;

		$this->table_name		= $wpdb->prefix . 'sim_schedules';

		$this->create_db_table();

		$this->get_schedules();
		
		$this->user 			= wp_get_current_user();
		
		if(in_array('personnelinfo',$this->user->roles)){
			$this->admin	=	true;
		}else{
			$this->admin	= false;
		}
		
		add_action ( 'wp_ajax_add_schedule',array($this,'add_schedule'));

		add_action ( 'wp_ajax_remove_schedule',array($this,'remove_schedule'));
		
		add_action ( 'wp_ajax_add_menu',array($this,'add_menu'));
		
		add_action( 'wp_ajax_publish_schedule',array($this,'publish_schedule'));

		add_action( 'wp_ajax_add_host',array($this,'add_host'));

		add_action( 'wp_ajax_remove_host',array($this,'remove_host'));
	}
	
	function create_db_table(){
		global $Events;

		if ( !function_exists( 'maybe_create_table' ) ) { 
			require_once ABSPATH . '/wp-admin/install-helper.php'; 
		} 
		
		//only create db if it does not exist
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->table_name} (
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
		) $charset_collate;";

		maybe_create_table($this->table_name, $sql );
	}

	function get_schedules(){
		global $wpdb;

		$query				= "SELECT * FROM {$this->table_name} WHERE 1";
		
		$this->schedules	= $wpdb->get_results($query);
	}

	function find_schedule_by_id($id){
		foreach($this->schedules as $schedule){
			if($schedule->id == $id)
				return $schedule;
		}
	}
	
	function add_modals(){
		global $LoaderImageURL;
		ob_start();
		?>
		<!-- Add host modal for admins -->
		<div name='add_host' class="modal hidden">
			<div class="modal-content">
				<span class="close">&times;</span>
				<form action="" method="post">
					<p>Please select the user or family who is hosting</p>
					<input type='hidden' name='action' value='add_host'>
					<input type="hidden" name="update_schedule_nonce" value="<?php echo wp_create_nonce("update_schedule_nonce"); ?>">
					<input type='hidden' name='schedule_id'>
					<input type='hidden' name='date'>
					<input type='hidden' name='starttime'>
					<?php
					echo SIM\user_select($text='',$only_adults=true,$families=true,$class='',$id='host');
					echo SIM\add_save_button('add_host','Add host','update_schedule'); 
					?>
				</form>
			</div>
		</div>
		
		<!-- Add recipe modal -->
		<div name='add_recipe_keyword' class="modal hidden">
			<div class="modal-content">
				<span class="close">&times;</span>
				<form action="" method="post">
					<input type="hidden" name="update_schedule_nonce" value="<?php echo wp_create_nonce("update_schedule_nonce"); ?>">
					<input type='hidden' name='action' value='add_menu'>
					<input type='hidden' name='schedule_id'>
					<input type='hidden' name='date'>
					<input type='hidden' name='starttime'>
					
					<p>Enter one or two keywords for the meal you are planning to serve<br>
					For instance 'pasta', 'rice', 'Nigerian', 'salad'</p>
					<input type='text' name='recipe_keyword'>
					
					<?php
					echo SIM\add_save_button('add_recipe_keyword','Add recipe keywords','update_schedule'); 
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
					<input type='hidden' name='action' value='add_host'>
					<input type="hidden" name="update_schedule_nonce" value="<?php echo wp_create_nonce("update_schedule_nonce"); ?>">
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
						echo SIM\user_select($text='',$only_adults=true,$families=false,$class='',$id='host');
					}
					
					echo SIM\add_save_button('add_timeslot','Add time slot','update_event add_schedule_row');
					?>
				</form>
			</div>
		</div>
		
		<?php
		return ob_get_clean();
	}

	function add_schedule_form(){
		ob_start();
		if($this->admin == true){
			?>			
			<h3 style='text-align:center;'>Add a schedule</h3>
			<form>
				<input type="hidden" name="add_schedule_nonce" value="<?php echo wp_create_nonce("add_schedule_nonce"); ?>">
				<input type="hidden" name="action" value="add_schedule">
				<input type="hidden" name="target_id">
				
				<label>
					<h4>Name of the person the schedule is for</h4>
					<input type='text' name='target_name' list="website_users" required>
				</label>
				
				<datalist id="website_users">
					<?php
					foreach(SIM\get_user_accounts(true) as $user){
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
				echo SIM\add_save_button('add_schedule', 'Add schedule');
			echo '</form>';
		}
		return ob_get_clean();
	}
	
	function showschedules(){	
		ob_start();
		?>
		<div class='schedules_wrapper' style='z-index: 99;position: relative;'>
			<input	type="hidden" name="remove_schedule_nonce" value="<?php echo wp_create_nonce("remove_schedule_nonce"); ?>">
			<input type="hidden" name="update_schedule_nonce" value="<?php echo wp_create_nonce("update_schedule_nonce"); ?>">
		<?php
		echo $this->add_modals();
		
		//indicator if othing to show
		$nothing	= true;

		foreach($this->schedules as $key=>$schedule){
			//do not show schedule if not published and it is for us
			if(($schedule->target == $this->user->ID or $schedule->target == SIM\has_partner($this->user->ID)) and !$this->admin and !$schedule->published) continue;
			
			$nothing	= false;
			$only_meals = true;
			
			//Show also the orientation schedule if:
			if(
				//We are an admin
				$this->admin	==	true									or
				//We are in the schedule
				!empty($this->get_personal_orientation_events($schedule)) 	or
				//The schedule is meant for us
				$schedule->target == $this->user->ID
			){
				$only_meals = false;
			}
			
			?>
			<div class='schedules_div form_table_wrapper'>
				<div class="modal publish_schedule hidden">
					<!-- Modal content -->
					<div class="modal-content">
						<span id="modal_close" class="close">&times;</span>
						<form action="" method="post" id="publish_schedule_form">
							<input type='hidden' name='schedule_id'>
							<input type='hidden' name='action' value='publish_schedule'>
							<input type="hidden" name="publish_schedule_nonce" value="<?php echo wp_create_nonce("publish_schedule_nonce"); ?>">
						
							<p>
								Please select the user or family this schedule should be published for.<br>
								The schedule will show up on the dashboard of these persons after publish.
							</p>
							<?php
							//if looking for a family
							if(strpos($schedule->name,'family') !== false){
								$args=array(
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
								$args=array(
									'search' => $schedule->name,
									'search_columns' => ['display_name'],
								);
							}
							
							echo SIM\user_select($text='',$only_adults=true,$families=true,$class='',$id='select_schedule_target',$args);
							echo SIM\add_save_button('publish_schedule','Publish this schedule'); 
							?>
						</form>
					</div>
				</div>
				<h3 class="table_title">
					Schedule for <?php echo $schedule->name;?><br>
					<?php echo $schedule->info;?>
				</h3>
				<p>Click on an available date to indicate you want to host.<br>Click on any date you are subscribed for to unsubscribe</p>
				<table class="table schedule" data-id="<?php echo $schedule->id; ?>" data-target="<?php echo $schedule->name; ?>" data-action='update_schedule'>
					<thead class="table-head">
						<tr>
							<th>Dates</th>
							<?php
							$date		= $schedule->startdate;
							while(true){	
								$date_str	= date('d F Y',strtotime($date));	
								$datetime	= strtotime($date);					
								$dayname	= date('l', $datetime);
								$formated_date	= date('d-m-Y', $datetime);
								echo "<th data-date='$date_str' data-isodate='$date'>$dayname<br>$formated_date</th>";

								if($date == $schedule->enddate) break;

								$date	= date('Y-m-d',strtotime('+1 day',$datetime));
							}
							?>
						</tr>
					</thead>
					<tbody class="table-body">
						<?php
						echo $this->write_rows($schedule,$only_meals);
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
					if(!$schedule->published){
						echo "<button type='button' class='button schedule_action publish' data-target='{$schedule->target}' data-schedule_id='{$schedule->id}'>Publish</button>";
					}
					echo '</div>';
				}
				?>
			</div>
			<?php
		}
		$form	= $this->add_schedule_form();
		if($nothing and empty($form)){
			return "There are currently no signup requests.";
		}else{
			echo $form;
		}
		echo '</div>';
			
		return ob_get_clean();
	}

	//single event
	function get_schedule_event($schedule, $startdate, $starttime){
		//get event which starts on this date and time
		$events = $this->get_schedule_events($schedule->id, $startdate, $starttime);
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

	//multiple events
	function get_schedule_events($schedule_id, $startdate, $starttime){
		global $wpdb;
		global $Events;

		$query	=  "SELECT * FROM {$Events->table_name} WHERE `schedule_id` = '{$schedule_id}' AND startdate = '$startdate' AND starttime='$starttime'";
		return $wpdb->get_results($query);
	}

	//personal events
	function get_personal_orientation_events($schedule){
		global $wpdb;
		global $Events;

		$query	= "SELECT * FROM {$Events->table_name} WHERE `schedule_id` = '{$schedule->id}' AND onlyfor={$this->user->ID} AND starttime != '18:00'";
		if($schedule->lunch){
			$query	.= " AND starttime != '12:00'";
		}
		return $wpdb->get_results();
	}

	function write_meal_cell($schedule, $date, $starttime){
		//get event which starts on this date and time
		$event	= $this->get_schedule_event($schedule, $date, $starttime);
		$class	= 'meal';

		if($starttime == '12:00'){
			$rowspan						= "rowspan='4'";
			$this->nextstarttimes[$date]	= '13:00';
		}else{
			$rowspan = '';
		}
		
		if($event != null){
			$host_id		= $event->organizer_id;
			if(is_numeric($host_id)){
				$hostdata	= "data-host=$host_id";
			}else{
				$hostdata	= "";
			}
			$title			= get_the_title($event->post_id);
			$url			= get_permalink($event->post_id);
			$celltext		= "<a href='$url'>$title</a>";
			$date		= $event->startdate;
			$starttime	= $event->starttime;

			$class .= ' selected';
			$partner_id = SIM\has_partner($this->user->ID);
			//Host is current user or the spouse
			if($host_id == $this->user->ID or $host_id == $partner_id){				
				$menu		= get_post_meta($event->post_id,'recipe_keyword',true);
				if(empty($menu)){
					$menu	= 'Enter recipe keyword';
				}
			
				$celltext .= "<span class='keyword'>$menu</span>";
			//current user is the target or is admin
			}elseif(!$this->admin and $schedule->target != $this->user->ID and $schedule->target != $partner_id){
				$celltext = 'Taken';
			}
		}else{
			$celltext	 = 'Available';
		}

		if($this->admin) $class .= ' admin';
		return "<td class='$class' $rowspan $hostdata>$celltext</td>";
	}
	
	function write_orientation_cell($schedule, $date, $starttime){
		//get event which starts on this date and time
		$event	= $this->get_schedule_event($schedule, $date, $starttime);

		$rowspan	= '';
		$class		= 'orientation'; 

		if($event == null){
			$celltext = 'Available';
		}else{
			$host_id	= $event->organizer_id;
			if(is_numeric($host_id)){
				$hostdata	= "data-host=$host_id";
			}else{
				$hostdata	= "";
			}
			$title		= get_the_title($event->post_id);
			$url		= get_permalink($event->post_id);
			$date		= $event->startdate;
			$starttime	= $event->starttime;
			$endtime	= $event->endtime;
			$class 		.= ' selected';
			$celltext	= "<span class='subject' data-userid='$host_id'><a href='$url'>$title</a></span><br>";
		
			if(!is_numeric($host_id)){
				$celltext .= "<span class='person timeslot'>Add person</span>";
			}
		
			if(empty($event->location)){
				$celltext .= "<span class='location timeslot'>Add location</span>";
			}else{
				$celltext .= "<span class='timeslot'>At <span class='location'>{$event->location}</span></span>";
			}

			////check how many rows this event should span
			$to_time	= new \DateTime($event->endtime);
			$from_time	= new \DateTime($event->starttime);
			$interval	= $to_time->diff($from_time);
			$value		= ($interval->h*60+$interval->i)/15;
			$rowspan	= "rowspan='$value'";

			$this->nextstarttimes[$date] = $endtime;
		}

		//Make the cell editable if:
		if(
			//we are admin
			$this->admin == true or
			//We are the organizer
			$this->user->ID == $event->organizer_id or 
			//This cell  is available
			$celltext == 'Available'			
		){
			if($this->admin) $class .= ' admin';
		}
		
		return "<td class='$class' $rowspan $hostdata>$celltext</td>";
	}

	function write_rows($schedule, $only_meals = false){
		$html = '';
		
		$this->nextstarttimes	= [];

		//loop over the rows
		$starttime	= $schedule->starttime;
		//loop until we are at the endtime
		while(true){
			//If we do not have an orientation schedule, go strait to the dinner row
			if($starttime >= '13:00' and $starttime < '18:00' and !$schedule->orientation)	$starttime = '18:00';
			
			$date				= $schedule->startdate;
			$mealschedulerow	= false;
			$endtime			= date("H:i", strtotime('+15 minutes', strtotime($starttime)));
			
			if($starttime >= '12:00' and $starttime < '13:00' and $schedule->lunch){
				$mealschedulerow	= true;
				if($starttime >= '12:00'){
					$rowspan			= 'rowspan="4"';
					$description		= 'Lunch';
				}
			}elseif($starttime == '18:00'){
				$mealschedulerow	= true;
				$rowspan			= '';
				$description		= 'Diner';
			}else{
				$rowspan			= '';
				$description		= $starttime;
			}
			
			//Show the row if we can see all rows or the row is a mealschedule row
			if(!$only_meals or $mealschedulerow){
				$html  .= "<tr class='table-row' data-starttime='$starttime' data-endtime='$endtime'>";
					if($starttime > '12:00' and $starttime < '13:00' and $schedule->lunch){
						$html .= "<td class='hidden'><b>$description</b></td>";
					}else{
						$html .= "<td $rowspan><b>$description</b></td>";
					}
				
					//loop over the dates to write a cell per date in this timerow
					while(true){
						if($this->nextstarttimes[$date] > $starttime){
							$html .= "<td class='hidden'>Available</td>";
						}else{
							//mealschedule
							if($mealschedulerow){
								$html .= $this->write_meal_cell($schedule, $date, $starttime);
							//Orientation schedule
							}else{
								$html .= $this->write_orientation_cell($schedule, $date, $starttime);	
							}
						}

						if($date == $schedule->enddate) break;

						$date	= date('Y-m-d',strtotime('+1 day',strtotime($date)));
					}
				$html .= "</tr>";
			}

			if($starttime == $schedule->endtime) break;
			$starttime		= $endtime;
		}//end row
		
		return $html;
	}

	function add_event_to_db($event){
		global $wpdb;
		global $Events;

		$result = $wpdb->insert(
			$Events->table_name, 
			$event
		);
		
		if($wpdb->last_error !== ''){
			wp_die($wpdb->print_error(),500);
		}
	}

	function add_schedule_events($title, $schedule, $add_host_partner=true, $add_partner=true){
		$event							= [];	
		$event['startdate']				= $this->date;
		$event['starttime']				= $this->starttime;
		$event['enddate']				= $this->date;
		$event['endtime']				= $this->endtime;	
		$event['location']				= $this->location;
		$event['organizer_id']			= $this->host_id;
		$event['schedule_id']			= $this->schedule_id;

		if($add_host_partner){
			$host_partner	= SIM\has_partner($this->host_id);
			$event['organizer']				= get_userdata($this->host_id)->last_name.' family';
		}else{
			$host_partner	= false;
			$event['organizer']				= get_userdata($this->host_id)->display_name;
		}

		if($add_partner){
			$partner_id	= SIM\has_partner($schedule->target);
		}else{
			$partner_id	= false;
		}

		//clean title
		$title	= str_replace(" with {$event['organizer']}",'',$title);
		$title	= str_replace("Hosting {$this->name} for ",'',$title);

		//New events
		$array		= [
			[
				'title'		=>ucfirst($title)." with {$event['organizer']}",
				'onlyfor'	=>[$schedule->target, $partner_id]
			],
			[
				'title'		=>"Hosting {$this->name} for $title",
				'onlyfor'	=>[$this->host_id, $host_partner]
			]
		];
		foreach($array as $a){
			$post = array(
				'post_type'		=> 'event',
				'post_title'    => $a['title'],
				'post_content'  => $a['title'],
				'post_status'   => "publish",
				'post_author'   => $this->host_id
			);
			$post_id 	= wp_insert_post( $post,true,false);
			update_post_meta($post_id,'eventdetails',$event);
			update_post_meta($post_id,'onlyfor',$a['onlyfor']);

			foreach($a['onlyfor'] as $user_id){
				if(is_numeric($user_id)){
					$event['onlyfor']	= $user_id;
					$event['post_id']	= $post_id;
					$this->add_event_to_db($event);
				}
			}
		}
	}
	
	function add_schedule(){
		SIM\verify_nonce('add_schedule_nonce');
		
		if(empty($_POST['target_name']) or empty($_POST['startdate']) or empty($_POST['enddate'])){
			wp_die("Please submit a name and two dates",500);
		}else{
			global $wpdb;
		
			$name		= sanitize_text_field($_POST['target_name']);
			//check if schedule already exists
			if($wpdb->get_var("SELECT * FROM {$this->table_name} WHERE `name` = '$name'") != null){
				wp_die("A schedule for $name already exists!",500);
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

			$startdate_str	= $_POST['startdate'];
			$startdate		= strtotime($startdate_str);
			$enddate_str	= $_POST['enddate'];
			$enddate		= strtotime($enddate_str);

			if($orientation){
				$starttime	= '08:00';
			}elseif($lunch){
				$starttime	= '12:00';
			}else{
				$starttime	= '18:00';
			}						

			if($startdate > $enddate) wp_die("Ending date cannot be before starting date",500);

			$wpdb->insert(
				$this->table_name, 
				array(
					'target'		=> $_POST['target_id'],
					'name'			=> $name,
					'info'			=> $info,
					'lunch'			=> $lunch,
					'orientation'	=> $orientation,
					'startdate'		=> $startdate_str,
					'enddate'		=> $enddate_str,
					'starttime'		=> $starttime,
					'endtime'		=> '18:00',
				)
			);
			
			
			$this->get_schedules();
			$schedules_html	= $this->showschedules();

			wp_die(json_encode(
				[
					'message'		=> "Succesfully added a schedule for $name",
					'callback'		=> 'add_schedule',
					'html'			=> $schedules_html
				]
			));
			wp_die();
		}
	}
	
	function remove_schedule(){
		global $Events;
		global $wpdb;

		SIM\verify_nonce('remove_schedule_nonce');

		$schedule_id	= $_POST['schedule_id'];
		if(!is_numeric($schedule_id)) wp_die('No valid schedule id given',500);

		$schedule	= $this->schedules[$schedule_id];

		//Remove the schedule from the user meta
		if(is_numeric($schedule->target)){
			SIM\update_family_meta($schedule->target,'schedule','delete');
		}
		
		//Delete all the posts of this schedule
		$events = $wpdb->get_results("SELECT * FROM {$Events->table_name} WHERE schedule_id='$schedule_id'");
		foreach($events as $event){
			wp_delete_post($event->post_id,true);
		}
		
		//Delete all events of this schedule
		$wpdb->delete(
			$Events->table_name,      
			['schedule_id' => $schedule_id],           
			['%d'],
		);
		
		//Delete the schedule
		$wpdb->delete(
			$this->table_name,
			['id' => $schedule_id],
			['%d'],
		);
			
		//Remove the schedule from the schedules array
		wp_die('Succesfully removed the schedule');
	}

	function add_host(){
		SIM\verify_nonce('update_schedule_nonce');

		$this->schedule_id	= $_POST['schedule_id'];
		if(!is_numeric($this->schedule_id)) wp_die('No valid schedule id given',500);
		$schedule		= $this->find_schedule_by_id($this->schedule_id);

		$this->host_id	= $_POST['host'];
		if(!is_numeric($this->host_id)) wp_die('No valid host given',500);

		$partner_id		= SIM\has_partner($this->host_id);
		if($this->admin != true and $this->host_id != $this->user->ID and $this->host_id != $partner_id) wp_die('No permission to do that!',500);
		
		if($partner_id){
			$host_name		= get_userdata($this->host_id)->last_name.' family';
		}else{
			$host_name		= get_userdata($this->host_id)->display_name;
		}

		$this->name			= $schedule->name;

		$this->date			= $_POST['date'];
		$date_str			= date('d F Y',strtotime($this->date));
		$this->starttime	= $_POST['starttime'];
		if($this->starttime == '12:00' and $schedule->lunch){
			$this->endtime		= '13:00';
			$title				= 'lunch';
			$this->location		= "House of $host_name";
		}elseif($this->starttime == '18:00'){
			$this->endtime		= '19:30'; 
			$title				= 'diner';
			$this->location		= "House of $host_name";
		}else{
			$title				= sanitize_text_field($_POST['subject']);
			$this->location		= sanitize_text_field($_POST['location']);
			$this->endtime		= $_POST['endtime'];
		}

		if($this->admin == true){
			$message	= "Succesfully added $host_name as a host for {$this->name} on $date_str";
		}else{
			$message	= "Succesfully added you as a host for {$this->name} on $date_str";
		}

		if($title == 'lunch' or $title == 'diner'){
			$this->add_schedule_events($title, $schedule);
			$cell_html	= $this->write_meal_cell($schedule, $this->date, $this->starttime);
		}else{
			$this->add_schedule_events($title, $schedule, false);
			$cell_html	= $this->write_orientation_cell($schedule, $this->date, $this->starttime);
		}
		
		wp_die(json_encode(
			[
				'message'		=> $message,
				'callback'		=> 'schedule_host_added',
				'cell_html'		=> $cell_html,
				'schedule_id'	=> $this->schedule_id,
				'starttime'		=> $this->starttime,
				'endtime'		=> $this->endtime,
				'date'			=> $this->date,
			]
		));
	}

	function remove_host(){
		global $Events;

		SIM\verify_nonce('update_schedule_nonce');

		$this->schedule_id	= $_POST['schedule_id'];
		if(!is_numeric($this->schedule_id)) wp_die('No valid schedule id given',500);
		$schedule		= $this->find_schedule_by_id($this->schedule_id);

		$this->host_id	= $_POST['host'];
		if(!is_numeric($this->host_id)) wp_die('No valid host given',500);

		$partner_id		= SIM\has_partner($this->host_id);
		if($this->admin != true and $this->host_id != $this->user->ID and $this->host_id != $partner_id) wp_die('No permission to do that!',500);
		if($partner_id){
			$host_name		= get_userdata($this->host_id)->last_name.' family';
		}else{
			$host_name		= get_userdata($this->host_id)->display_name;
		}

		$this->date			= $_POST['date'];
		$date_str			= date('d F Y',strtotime($this->date));
		$this->starttime	= $_POST['starttime'];

		$events	= $this->get_schedule_events($schedule->id, $this->date, $this->starttime);
		
		foreach($events as $event){
			//delete event_post
			wp_delete_post($event->post_id);

			//delete events
			$Events->remove_db_rows($event->post_id);
		}

		if($this->admin == true){
			$message	= "Succesfully removed $host_name as a host for {$schedule->name} on $date_str";
		}else{
			$message	= "Succesfully removed you as a host for {$this->name} on $date_str";
		}

		wp_die(json_encode(
			[
				'message'		=> $message,
				'callback'		=> 'schedule_host_removed',
				'schedule_id'	=> $this->schedule_id,
				'starttime'		=> $this->starttime,
				'date'			=> $this->date,
			]
		));
	}

	function add_menu(){
		$schedule_id	= $_POST['schedule_id'];
		if(!is_numeric($schedule_id)) wp_die('No valid schedule id given',500);
		$schedule		= $this->find_schedule_by_id($schedule_id);

		$date	= sanitize_text_field($_POST['date']);
		if(empty($date)) wp_die('No valid date given',500);

		$starttime	= sanitize_text_field($_POST['starttime']);
		if(empty($starttime)) wp_die('No valid start time given',500);

		$menu	= sanitize_text_field($_POST['recipe_keyword']);
		if(empty($menu)) wp_die('No valid menu given',500);

		$events	= $this->get_schedule_events($schedule->id, $date, $starttime);
		foreach($events as $event){
			update_post_meta($event->post_id, 'recipe_keyword', $menu);
		}

		wp_die(json_encode(
			[
				'message'		=> 'succesfully added the keywords',
				'callback'		=> 'schedule_add_menu',
				'menu'			=> $menu,
				'schedule_id'	=> $schedule_id,
				'starttime'		=> $starttime,
				'date'			=> $date,
			]
		));
	}
	
	function publish_schedule(){
		global $wpdb;
		
		SIM\verify_nonce('publish_schedule_nonce');
		
		$schedule_id	= $_POST['schedule_id'];
		if(!is_numeric($schedule_id) or !is_numeric($_POST['select_schedule_target'])){
			wp_die("Please submit a name and a schedule id",500);
		}

		$schedule	= $this->find_schedule_by_id($schedule_id);
		SIM\update_family_meta($_POST['select_schedule_target'],'schedule',$_POST['schedule_index']);

		$schedule->target		= $_POST['select_schedule_target'];

		$wpdb->update(
			$this->table_name, 
			array(
				'published'	=> true
			),
			array(
				'id'		=> $schedule->id
			)
		);

		wp_die(json_encode(
			[
				'message'	=> 'Succesfully published the schedule',
				'callback'	=> 'hide_modals'
			]
		));
	}
}

add_action('init', function(){
	if(wp_doing_ajax()) new Schedule();
});