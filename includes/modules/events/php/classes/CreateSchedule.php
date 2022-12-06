<?php
namespace SIM\EVENTS;
use SIM;
use WP_Error;

class CreateSchedule extends Schedules{
	/**
	 * Add a new event to the db
	 *
	 * @param	object	$event			the event
	 *
	 * @return 	array					Rows html
	*/
	protected function addEventToDb($event){
		global $wpdb;

		$wpdb->insert(
			$this->events->tableName,
			$event
		);
		
		if($wpdb->last_error !== ''){
			return new WP_Error('schedules', $wpdb->print_error());
		}

		$eventId   = $wpdb->insert_id;

		//Create event warning
		if(isset($_POST['reminders'])){
			foreach($_POST['reminders'] as $minutes){
				$start	= new \DateTime($event['startdate'].' '.$event['starttime'], new \DateTimeZone(wp_timezone_string()));

				//Warn minutes in advance
				$start	= $start->getTimestamp() - $minutes * MINUTE_IN_SECONDS;
				
				wp_schedule_single_event($start, 'send_event_reminder_action', [$eventId]);
			}
		}
	}

	/**
	 * Add new events to the db when a new activity is scheduled
	 *
	 * @param	string	$title			the title of the event
	 * @param	object	$schedule		the schedule of the event
	 * @param	bool	$addHostPartner	Whether to add an event for the host partner as well. Default true
	 * @param	bool	$addPartner		Whether to add an event for the schedule target partner as well. Default true
	*/
	protected function addScheduleEvents($title, $schedule, $addHostPartner=true, $addPartner=true){
		$event							= [];
		$event['startdate']				= $this->date;
		$event['starttime']				= $this->starttime;
		$event['enddate']				= $this->date;
		$event['endtime']				= $this->endtime;
		$event['location']				= $this->location;
		$event['organizer_id']			= $this->hostId;
		$event['schedule_id']			= $this->scheduleId;
		
		$hostPartner					= false;
		if(is_numeric($this->hostId)){
			if($addHostPartner && SIM\hasPartner($this->hostId)){
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
		$title	= str_replace([" with", $event['organizer'], "Hosting {$this->name} for "], '', $title);

		if(!empty($event['organizer'])){
			$ownTitle	= ucfirst($title)." with {$event['organizer']}";
		}else{
			$ownTitle	= ucfirst($title);
		}

		if(strpos(strtolower($ownTitle), 'at home') !== false){
			if(!empty($event['organizer'])){
				$ownTitle	= ucfirst($title).' '.$event['organizer'];
			}

			$event['location']	= 'Home';
			unset($event['organizer']);
		}
		
		//New events
		$eventArray		= [
			[
				'title'		=> $ownTitle,
				'onlyfor'	=> [$schedule->target, $partnerId]
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

			$postId 	= wp_insert_post( $post, true, false);
			
			update_post_meta($postId, 'eventdetails', json_encode($event));
			update_post_meta($postId, 'onlyfor', $a['onlyfor']);
			update_post_meta($postId, 'reminders', $_POST['reminders']);

			// setting the eventdetails meta value also creates the event. Remove it
			$events = new CreateEvents();
			$events->postId	= $postId;
			$events->removeDbRows();

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
	public function addSchedule($update=false){
		global $wpdb;
	
		$name		= sanitize_text_field($_POST['target_name']);
		//check if schedule already exists
		if(!$update && $wpdb->get_var("SELECT * FROM {$this->tableName} WHERE `name` = '$name'") != null){
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
			$startTime	= $this->lunchStartTime;
		}else{
			$startTime	= $this->dinerTime;
		}

		if($startDate > $endDate){
			return new WP_Error('schedule', "Ending date cannot be before starting date");
		}

		$arg	= array(
			'target'		=> $_POST['target_id'],
			'name'			=> $name,
			'info'			=> $info,
			'lunch'			=> $lunch,
			'orientation'	=> $orientation,
			'startdate'		=> $startDateStr,
			'enddate'		=> $endDateStr,
			'starttime'		=> $startTime,
			'endtime'		=> $this->dinerTime,
		);

		if($update){
			$wpdb->update(
				$this->tableName,
				$arg,
				array( 'ID' => $_POST['target_id'] )
			);
			$action	= 'updated';
		}else{
			$wpdb->insert(
				$this->tableName,
				$arg
			);
			$action	= 'added';
		}
		
		$this->getSchedules();
		return [
			'message'		=> "Succesfully $action a schedule for $name",
			'html'			=> $this->showschedules()
		];
	}

	/**
	 * Publishes a new schedule
	 *
	 * @return string	success message
	*/
	public function publishSchedule(){
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
	public function removeSchedule($scheduleId){
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
	public function addHost(){
		$message			= '';
		$this->scheduleId	= $_POST['schedule_id'];
		$schedule			= $this->findScheduleById($this->scheduleId);

		if(is_numeric($_POST['host_id'])){
			$this->hostId	= $_POST['host_id'];
			$host			= get_userdata($this->hostId);
			$partnerId		= SIM\hasPartner($this->hostId);

			if(
				!$this->admin						&&
				$this->hostId != $this->user->ID	&&
				$this->hostId != $partnerId
			){
				return new WP_Error('No permission', $this->noPermissionText);
			}
			
			if($partnerId && !isset($_POST['subject'])){
				$hostName		= $host->last_name.' family';
			}else{
				$hostName		= $host->display_name;
			}
		}else{
			$this->hostId	= '';
			$hostName		= $_POST['host'];
			if(!$this->admin){
				return new WP_Error('No permission', $this->noPermissionText);
			}
		}

		// remove any existing events
		if(isset($_POST['oldtime'])){
			$this->date			= $_POST['olddate'];
			$this->starttime	= $_POST['oldtime'];
			$this->removeHost();

			$message	= "Succesfully updated this entry";
		}

		$this->name			= $schedule->name;
		$this->date			= $_POST['date'];
		$dateStr			= date('d F Y',strtotime($this->date));
		$this->starttime	= $_POST['starttime'];
		if($this->starttime == $this->lunchStartTime && $schedule->lunch){
			$this->endtime		= $this->lunchEndTime;
			$title				= 'lunch';
			$this->location		= "House of $hostName";
		}elseif($this->starttime == $this->dinerTime){
			$this->endtime		= '19:30';
			$title				= 'dinner';
			$this->location		= "House of $hostName";
		}else{
			$title				= sanitize_text_field($_POST['subject']);
			$this->location		= sanitize_text_field($_POST['location']);
			$this->endtime		= $_POST['endtime'];
		}

		if(empty($message)){
			if($this->admin){
				$message	= "Succesfully added $hostName as a host for {$this->name} on $dateStr";
			}else{
				$message	= "Succesfully added you as a host for {$this->name} on $dateStr";
			}
		}

		if($title == 'lunch' || $title == 'dinner'){
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
	public function removeHost(){
		$this->scheduleId	= $_POST['schedule_id'];
		$schedule			= $this->findScheduleById($this->scheduleId);

		$this->hostId		= $_POST['host_id'];

		$partnerId			= SIM\hasPartner($this->hostId);
		if(
			!$this->admin 						&&
			$this->hostId != $this->user->ID 	&&
			$this->hostId != $partnerId
		){
			return new \WP_Error('Permission error', $this->noPermissionText);
		}

		if($partnerId){
			$hostName		= get_userdata($this->hostId)->last_name.' family';
		}else{
			$hostName		= get_userdata($this->hostId)->display_name;
		}
		
		$dateStr			= date('d F Y', strtotime($this->date));

		$events				= $this->getScheduleEvents($schedule->id, $this->date, $this->starttime);
		
		foreach($events as $event){
			//delete event_post
			wp_delete_post($event->post_id);

			//delete events
			$this->events->removeDbRows($event->post_id);
		}

		if($this->admin){
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
	public function addMenu(){
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