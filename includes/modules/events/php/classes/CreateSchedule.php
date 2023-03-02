<?php
namespace SIM\EVENTS;
use SIM;
use WP_Error;

class CreateSchedule extends Schedules{
	public $date;
	public $startTime;
	public $endTime;
	public $location;
	public $hostId;
	public $scheduleId;
	public $name;
	public $title;

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

		return $eventId;
	}

	/**
	 * Add new events to the db when a new activity is scheduled
	 *
	 * @param	string	$title			the title of the event
	 * @param	bool	$addHostPartner	Whether to add an event for the host partner as well. Default true
	 * @param	bool	$addPartner		Whether to add an event for the schedule target partner as well. Default true
	*/
	protected function addScheduleEvents($addHostPartner=true, $addPartner=true){
		$event							= [];
		$event['startdate']				= $this->date;
		$event['starttime']				= $this->startTime;
		$event['enddate']				= $this->date;
		$event['endtime']				= $this->endTime;
		$event['location']				= $this->location;
		$event['organizer_id']			= $this->hostId;
		
		$hostPartner					= false;
		if(is_numeric($this->hostId)){
			if($addHostPartner){
				$event['organizer']				= SIM\getFamilyName($this->hostId, $hostPartner);
			}else{
				$event['organizer']				= get_userdata($this->hostId)->display_name;
			}
		}elseif(!empty($_POST['host'])){
			$event['organizer']					= $_POST['host'];
		}

		if($addPartner){
			$partnerId	= SIM\hasPartner($this->currentSchedule->target);
		}else{
			$partnerId	= false;
		}

		//clean title
		$title	= str_replace([" with", $event['organizer'], "Hosting {$this->name} for "], '', $this->title);

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
				'onlyfor'	=> [$this->currentSchedule->target]
			]
		];

		if($partnerId){
			$eventArray[0]['onlyfor'][]	= $partnerId;
		}

		if(is_numeric($this->hostId)){
			$eventArray[] =
			[
				'title'		=>"Hosting {$this->name} for $title",
				'onlyfor'	=>[$this->hostId, $hostPartner]
			];
		}

		$eventIds	= [];
		$postIds	= [];
		foreach($eventArray as $a){
			$post = array(
				'post_type'		=> 'event',
				'post_title'    => $a['title'],
				'post_content'  => $a['title'],
				'post_status'   => "publish",
				'post_author'   => $this->hostId
			);

			$postId 	= wp_insert_post( $post, true, false);
			$postIds[]	= $postId;
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
					$eventId 		= $this->addEventToDb($event);

					if(is_numeric($eventId)){
						$eventIds[]	= $eventId;
					}
				}
			}
		}

		// store the event and post ids in db
		global $wpdb;

		$wpdb->insert(
			$this->sessionTableName,
			[
				'schedule_id'	=> $this->scheduleId,
				'post_ids'		=> serialize($postIds),
				'event_ids'		=> serialize($eventIds),
				'meal'			=> $title == 'lunch' || $title == 'dinner'
			]
		);

		// add to schedule
		if(!isset($this->currentSchedule->sessions[$this->date])){
			$this->currentSchedule->sessions[$this->date]	= [];
		}
		$this->currentSchedule->sessions[$this->date][$this->startTime]	= $this->getSessionEvent($wpdb->insert_id);
	}

	/**
	 * Updates events in the db
	*/
	protected function updateScheduleEvents($addHostPartner=true, $addPartner=true){
		global $wpdb;

		$updated	= false;

		$hostPartner					= false;
		if(is_numeric($this->hostId)){
			if($addHostPartner){
				$organizer				= SIM\getFamilyName($this->hostId, $hostPartner);
			}else{
				$organizer				= get_userdata($this->hostId)->display_name;
			}
		}elseif(!empty($_POST['host'])){
			$organizer				= $_POST['host'];
		}

		$args	= [];
		foreach($this->currentSession->events as $event){
			if($event->startdate != $this->date){
				$args['startdate']	= $this->date;
				$args['enddate']		= $this->date;
				$updated			= true;
			}

			if($event->starttime != $this->startTime){
				$args['starttime']	= $this->startTime;
				$updated			= true;
			}

			if($event->endtime != $this->endTime){
				$args['endtime']	= $this->endTime;
				$updated			= true;
			}

			if($event->location != $this->location){
				$args['location']	= $this->location;
				$updated			= true;
			}

			if($event->organizer != $organizer){
				$args['organizer']	= $organizer;
				$updated			= true;
			}

			if($event->organizer_id != $this->hostId){
				$args['organizer_id']	= $this->hostId;
				$updated				= true;
			}

			if(!$updated){
				return new WP_Error('schedules', 'Nothing to update');
			}

			//Update the database
			$wpdb->update(
				$this->events->tableName,
				$args,
				array(
					'id'		=> $event->id
				),
			);
		}

		// update the schedule
		if(!isset($this->currentSchedule->sessions[$this->date])){
			$this->currentSchedule->sessions[$this->date]	= [];
		}
		$this->currentSchedule->sessions[$this->date][$this->startTime]	= $this->getSessionEvent($this->currentSession->id);

		/* 		$event							= [];
		$event['startdate']				= $this->date;
		$event['starttime']				= $this->startTime;
		$event['enddate']				= $this->date;
		$event['endtime']				= $this->endTime;
		$event['location']				= $this->location;
		$event['organizer_id']			= $this->hostId;
		//$event['schedule_id']			= $this->scheduleId;
		
		

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
		} */
		
		
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
			'diner'			=> !isset($_POST['skipdiner']),
			'orientation'	=> $orientation,
			'startdate'		=> $startDateStr,
			'enddate'		=> $endDateStr,
			'starttime'		=> $startTime,
			'endtime'		=> $this->dinerTime,
			'timeslot_size'	=> $_POST['timeslotsize'],
			'hidenames'		=> isset($_POST['hidenames']),
			'admin_roles'	=> maybe_serialize($_POST['admin-roles']),
			'view_roles'	=> maybe_serialize($_POST['view-roles'])
		);

		if($update){
			$wpdb->update(
				$this->tableName,
				$arg,
				array( 'id' => $_POST['schedule_id'] )
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
	public function addHost($date){
		$message			= '';
		$this->scheduleId	= $_POST['schedule_id'];
		$this->startTime	= $_POST['starttime'];
		$schedule			= $this->getScheduleById($this->scheduleId);

		// check if available
		$session	= $this->getScheduleSession($date, $this->startTime);
		if($session && $session->id != $_POST['session-id']){
			return new \WP_Error('schedules', 'This is already booked, sorry');
		}

		if(is_numeric($_POST['host_id'])){
			$this->hostId	= $_POST['host_id'];
			$host			= get_userdata($this->hostId);
			$partnerId		= SIM\hasPartner($this->hostId);

			if(
				!$this->admin						&&			// We are not admin
				$this->hostId != $this->user->ID	&&			// We are not the host
				$this->hostId != $partnerId						// Our partner is not the host
			){
				return new WP_Error('No permission', $this->noPermissionText);
			}
			
			if($partnerId && !isset($_POST['subject'])){
				$hostName		= SIM\getFamilyName($host);
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

		$this->name			= $schedule->name;
		$this->date			= $date;
		$dateStr			= date('d F Y',strtotime($this->date));
		$isMeal				= false;
		if($this->startTime == $this->lunchStartTime && $schedule->lunch){
			$this->endTime		= $this->lunchEndTime;
			$this->title		= 'lunch';
			$this->location		= "House of $hostName";
			$isMeal				= true;
		}elseif($this->startTime == $this->dinerTime){
			$this->endTime		= '19:30';
			$this->title		= 'dinner';
			$this->location		= "House of $hostName";
			$isMeal				= true;
		}else{
			$this->title		= sanitize_text_field($_POST['subject']);
			$this->location		= sanitize_text_field($_POST['location']);
			$this->endTime		= $_POST['endtime'];
		}

		if(!empty($_POST['session-id'])){
			$message	= "Succesfully updated this entry";
		}elseif($this->admin){
			$message	= "Succesfully added $hostName as a host for {$this->name} on $dateStr";
		}else{
			$message	= "Succesfully added you as a host for {$this->name} on $dateStr";
		}

		if($session){
			$result	= $this->updateScheduleEvents($isMeal);
		}else{
			$result	= $this->addScheduleEvents($isMeal);
		}

		if(is_wp_error($result)){
			return $result;
		}

		if($this->mobile){
			$html	= $this->getMobileDay($this->date);
		}elseif($isMeal){
			$html	= $this->writeMealCell($this->date, $this->startTime);
		}else{
			$html	= $this->writeOrientationCell($this->date, $this->startTime);
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
	public function removeHost($sessionId){
		global $wpdb;

		$this->currentSession	= $this->getSessionEvent($sessionId);

		$this->getScheduleById($this->currentSession->schedule_id);

		$date				= $this->currentSession->events[0]->startdate;
		$startTime			= $this->currentSession->events[0]->starttime;

		$hostId				= $this->currentSession->events[0]->organizer_id;

		$partnerId			= SIM\hasPartner($this->user->ID);
		if(
			!$this->admin 				&&
			$hostId != $this->user->ID 	&&
			$hostId != $partnerId
		){
			return new \WP_Error('Permission error', $this->noPermissionText);
		}
		
		foreach($this->currentSession->post_ids as $id){
			//delete event_post
			wp_delete_post($id);

			//delete events
			$this->events->removeDbRows($id);
		}

		// Delete the session
		$wpdb->delete(
			$this->sessionTableName,
			['id' => $sessionId],
			['%d'],
		);

		$dateStr		= date(get_option( 'date_format' ), strtotime($date));

		if($this->admin){
			$hostName	= $this->currentSession->events[0]->organizer;
			$message	= "Succesfully removed $hostName as a host on $dateStr";
		}else{
			$message	= "Succesfully removed you as a host on $dateStr";
		}

		if($this->mobile){
			$html	= $this->getMobileDay($date);
		}elseif($this->currentSession->meal){
			$html	= $this->writeMealCell($date, $startTime);
		}else{
			$html	= $this->writeOrientationCell($date, $startTime);
		}
		
		return [
			'message'	=> $message,
			'html'		=> $html
		];
	}

	/**
	 * Adds a keyword to a meal session
	 *
	 * @return string	success message
	*/
	public function addMenu(){
		$scheduleId		= $_POST['schedule_id'];
		$schedule		= $this->getScheduleById($scheduleId);

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