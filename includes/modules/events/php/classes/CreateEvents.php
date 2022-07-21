<?php
namespace SIM\EVENTS;
use SIM;
use WP_Error;

class CreateEvents extends Events{
	/**
	 * Retrieves the weeknumber from a certain date
	 * @param  	int  $date		a date in epoch
	 * 
	 * @return	int				the weeknumber
	*/
	function yearWeek($date){
		return intval(date('W', $date));
	}

	/**
	 * Retrieves the weeknumber from a certain date
	 * @param  	int  $date		a date in epoch
	 * 
	 * @return	int				the weeknumber
	*/
	protected function monthWeek($date){
		$firstDayOfMonth	= strtotime(date('Y-m-01', $date));
		return $this->yearWeek($firstDayOfMonth);
	}

	/**
	 * Creates events in the db
	*/
	protected function createEvents(){
		global $wpdb;

		$baseStartDateStr	= $this->eventData['startdate'];
		$baseStartDate		= strtotime($baseStartDateStr);
		$baseEndDate		= $this->eventData['enddate'];
		$dayDiff 			= ((new \DateTime($baseStartDateStr))->diff((new \DateTime($baseEndDate))))->d;

		$startDates			= [$baseStartDateStr];
		if(!empty($this->eventData['repeated'])){
			$this->createRepeatedEvents($baseStartDate);
		}
		
		foreach($startDates as $startDate){
			$enddate	= date('Y-m-d', strtotime("+{$dayDiff} day", strtotime($startDate)));
			$this->maybeCreateRow($startDate);

			$args	= $this->eventData;
			unset($args['startdate']);
			unset($args['repeated']);
			unset($args['repeat']);
			unset($args['allday']);
			$args['enddate']		= $enddate;

			//Update the database
			$wpdb->update($this->tableName, 
				$args, 
				array(
					'post_id'		=> $this->postId,
					'startdate'		=> $startDate
				),
			);
			
			if($wpdb->last_error !== ''){
				return new WP_Error('event', $wpdb->print_error());
			}
		}
	}

	function calculateStartDate($repeatParam, $baseStartDate, $index){
		$months			= (array)$repeatParam['months'];
		$weekDays		= (array)$repeatParam['weekDays'];
		$weeks			= (array)$repeatParam['weeks'];

		switch ($repeatParam['type']){
			case 'daily':
				$startDate		= strtotime("+{$index} day", $baseStartDate);
				if(!in_array(date('w', $startDate), $weekDays)){
					return false;
				}
				break;
			case 'weekly':
				$startDate		= strtotime("+{$index} week", $baseStartDate);
				$monthWeek		= $this->monthWeek($startDate);

				$lastWeek		= date('m', $startDate) == date('m', strtotime('+1 week', $startDate));
				if($lastWeek && in_array('last', $weeks)){
					$monthWeek	= 'last';
				}
				if(!in_array($monthWeek, $weeks)){
					return false;
				}
				break;
			case 'monthly':
				$startDate		= strtotime("+{$index} month", $baseStartDate);
				$month			= date('m', $startDate);
				if(!empty($months) && !in_array($month, $months)){
					return false;
				}

				if(!empty($weeks) && !empty($weekDays)){
					$startDate	= strtotime("{$weeks[0]} {$weekDays[0]} of this month", $startDate);
				}

				break;
			case 'yearly':
				$startDate	= strtotime("+{$index} year", $baseStartDate);

				if(!empty($weeks) && !empty($weekDays)){
					$startDate	= strtotime("{$weeks[0]} {$weekDays[0]} of this month", $startDate);
				}
				break;
			case 'custom_days':
				break;
			default:
				$startDate	= strtotime("+{$index} year", $baseStartDate);
				break;
		}

		return $startDate;
	}

	function createRepeatedEvents($baseStartDate){
		//first remove any existing events for this post
		$this->removeDbRows();

		//then create the new ones
		$repeatParam	= $this->eventData['repeat'];
		$interval		= max(1, (int)$repeatParam['interval']);
		$amount			= $repeatParam['amount'];
		if(!is_numeric($amount)){
			$amount = 200;
		}
		$repeatStop	= $repeatParam['stop'];

		if($repeatStop == 'date'){
			$repEnddate	= $repeatParam['enddate'];
		}else{
			$repEnddate	= strtotime("+90 year", $baseStartDate);
		}
		$excludeDates	= (array)$repeatParam['excludedates'];	
		$includeDates	= (array)$repeatParam['includedates'];	
					
		$startDate		= $baseStartDate;
		$i				= 1;
		while($startDate < $repEnddate && $amount > 0){
			$startDate	= $this->calculateStartDate($repeatParam, $baseStartDate, $i);
			if(!$startDate || in_array($startDate, $excludeDates)){
				$i				= $i+$interval;
				continue;
			}

			if($repeatParam['type'] == 'custom_days'){
				$startDateStr	= $includeDates[$i];
			}else{
				$startDateStr	= date('Y-m-d', $startDate);
			}
			
			if(
				!in_array($startDateStr, $includeDates)		&&		//we should not exclude this date
				$startDate < $repEnddate					&& 		//falls within the limits
				(!is_numeric($amount) || $amount > 0)				//We have not exeededthe amount
			){
				$startDates[]	= $startDateStr;
			}

			$i				= $i+$interval;
			$amount			= $amount-1;
		}	
	}

	/**
	 * Deletes old celebration events
	 * @param  	int  	$postId		WP_Post if
	 * @param	string	$date		date string 
	 * @param	int		$userId		WP_User id
	 * @param	string	$type		the anniverasry type
	 * @param	string	$title		the event title
	*/
	protected function deleteOldCelEvent($postId, $date, $userId, $type, $title){
		if(!is_numeric($postId)){
			return false;
		}
		
		$existingEvent	= $this->retrieveSingleEvent($postId);
		if(
			date('-m-d', strtotime($existingEvent->startdate)) == date('-m-d', strtotime($date)) &&
			$title == $existingEvent->post_title
		){
			return false; //nothing changed
		}else{
			wp_delete_post($postId, true);
			delete_user_meta($userId, $type.'_event_id');

			$this->removeDbRows();
		}
	}

	/**
	 * Deletes old celebration events
	 * @param	string		$type		the anniverasry type
	 * @param	int|object	$userId		WP_User id or WP_User object
	 * @param	string		$metaKey	the meta key key
	 * @param	string		$metaValue	the meta value, should be a date string
	*/
	public function createCelebrationEvent($type, $user, $metaKey='', $metaValue=''){
		if(is_numeric($user)){
			$user = get_userdata($user);
		}
		
		if(empty($metaKey)){
			$metaKey = $type;
		}

		if($type != 'birthday' && SIM\isChild($user->ID)){
			//do not create annversaries for children
			return;
		}

		$oldMetaValue	= SIM\getMetaArrayValue($user->ID, $metaKey);
		if(!is_array($metaValue) && is_array($oldMetaValue) && count($oldMetaValue) == 1){
			$oldMetaValue	= array_values($oldMetaValue)[0];
		}

		if(empty($metaValue) && empty($oldMetaValue) || $metaValue == $oldMetaValue){
			// nothing to work with or no update
			return;
		}elseif(empty($metaValue)){
			$metaValue = $oldMetaValue;
		}

		$eventIdMetaKey	= $type.'_event_id';
		$title			= ucfirst($type).' '.$user->display_name;
		$this->partnerId		= SIM\hasPartner($user->ID);
		if($this->partnerId){
			$partnerMeta	= SIM\getMetaArrayValue($this->partnerId, $metaKey);

			//only treat as a couples event if they both have the same value
			if($partnerMeta == $metaValue){
				$partnerName	= get_userdata($this->partnerId)->first_name;
				$title			= ucfirst($type)." {$user->first_name} & $partnerName {$user->last_name}";
			}else{
				$partnerId	= false;
			}
		}
		
		//get old post
		$this->postId	= get_user_meta($user->ID, $eventIdMetaKey, true);
		$this->deleteOldCelEvent($this->postId, $metaValue, $user->ID, $type, $title);

		if($partnerId){
			$this->deleteOldCelEvent(get_user_meta($this->partnerId, $eventIdMetaKey, true), $metaValue, $this->partnerId, $type, $title);
		}

		// Create the post
		$this->createCelebrationPost($user, $metaValue, $title, $type, $eventIdMetaKey);

		

		$this->createEvents();
	}

	function createCelebrationPost($user, $metaValue, $title, $type, $eventIdMetaKey){
		//Get the upcoming celebration date
		$startdate								= date(date('Y').'-m-d', strtotime($metaValue));

		$this->eventData['startdate']			= $startdate;
		$this->eventData['enddate']				= $startdate;
		$this->eventData['location']			= '';
		$this->eventData['organizer']			= $user->display_name;
		$this->eventData['organizer_id']		= $user->ID;
		$this->eventData['starttime']			= '00:00';
		$this->eventData['endtime']				= '23:59';
		$this->eventData['allday']				= true;
		$this->eventData['repeated']			= 'Yes';
		$this->eventData['repeat']['interval']	= 1;
		$this->eventData['repeat']['amount']	= 90;
		$this->eventData['repeat']['stop']		= 'never';
		$this->eventData['repeat']['type']		= 'yearly';

		$post = array(
			'post_type'		=> 'event',
			'post_title'    => $title,
			'post_content'  => $title,
			'post_status'   => 'publish',
			'post_author'   => $user->ID
		);

		$this->postId 	= wp_insert_post( $post, true, false);
		update_metadata( 'post', $this->postId, 'eventdetails', $this->eventData);
		update_metadata( 'post', $this->postId, 'celebrationdate', $metaValue);

		// Set the categories
		if($type == 'birthday'){
			$catName = 'birthday';
		}else{
			$catName = 'anniversary';
		}
		$termId = get_term_by('slug', $catName,'events')->term_id;
		if(empty($termId)){
			$termId = wp_insert_term(ucfirst($catName), 'events')['term_id'];
		}

		wp_set_object_terms($this->postId, $termId, 'events');

		// Set the featured image
		$pictureIds	= SIM\getModuleOption(MODULE_SLUG, 'picture_ids');
		if($type == 'birthday'){
			$pictureId = $pictureIds['birthday_image'];
		}else{
			$pictureId = $pictureIds['anniversary_image'];
		}
		set_post_thumbnail( $this->postId, $pictureId);

		//Store the post id for the user
		update_user_meta($user->ID, $eventIdMetaKey, $this->postId);

		if($this->partnerId){
			update_user_meta($this->partnerId, $eventIdMetaKey, $this->postId);
		}
	}

	/**
	 * Stores all event details in the db, removes any existing events, and creates new ones.
	 * @param  	int|WP_post  $post		The id of a post or the post itself
	*/

	public function storeEventMeta($post){
		if(is_numeric($post)){
			$post	= get_post($post);
		}

		$this->postId					= $post->ID;

		//store categories
		$cats = [];
		if(is_array($_POST['events_ids'])){
			foreach($_POST['events_ids'] as $catId) {
				if(is_numeric($catId)){
					$cats[] = $catId;
				}
			}
			
			//Store types
			$cats = array_map( 'intval', $cats );
			
			wp_set_post_terms($post->ID, $cats, 'events');
		}
	
		$event							= $_POST['event'];	
		$event['allday']				= sanitize_text_field($event['allday']);
		$event['startdate']				= sanitize_text_field($event['startdate']);
		$event['starttime']				= sanitize_text_field($event['starttime']);
		$event['enddate']				= sanitize_text_field($event['enddate']);
		$event['endtime']				= sanitize_text_field($event['endtime']);

		if(empty($event['startdate']) || empty($event['enddate'])){
			return;
		}

		$event['repeat']['type']		= sanitize_text_field($event['repeat']['type']);
		$event['repeat']['interval']	= sanitize_text_field($event['repeat']['interval']);
		$event['repeat']['stop']		= sanitize_text_field($event['repeat']['stop']);
		$event['repeat']['enddate']		= sanitize_text_field($event['repeat']['enddate']);
		$event['repeat']['amount']		= sanitize_text_field($event['repeat']['amount']);
		$event['location']				= sanitize_text_field($event['location']);
		$event['location_id']			= sanitize_text_field($event['location_id']);
		$event['organizer']				= sanitize_text_field($event['organizer']);
		$event['organizer_id']			= sanitize_text_field($event['organizer_id']);
	
		//check if anything has changed
		if(get_post_meta($this->postId, 'eventdetails', true) != $event){
			// First delete any existing events
			$this->removeDbRows($this->postId);

			// Delete any existing events as well
			wp_clear_scheduled_hook([$this, 'sendEventReminder'], $this->postId);

			//store meta in db
			update_metadata( 'post', $this->postId, 'eventdetails', $event);
		
			//create events
			$this->eventData		= $event;
			$this->createEvents();	
		}
	}

	/**
	 * Creatres a new event in db if it does not exist already
	 * @param  	string  $startdate		The startdate of the event
	*/
	protected function maybeCreateRow($startdate){
		global $wpdb;
		
		//check if form row already exists
		if(!$wpdb->get_var("SELECT * FROM {$this->tableName} WHERE `post_id` = '{$this->postId}' AND startdate = '$startdate'")){
			$wpdb->insert(
				$this->tableName, 
				array(
					'post_id'			=> $this->postId,
					'startdate'			=> $startdate
				)
			);
		}
	}

	/**
	 * Removes all events connected to an certain event post
	 * @param  	int  $postId		Optional post id
	*/
	public function removeDbRows($postId = null){
		global $wpdb; 
 
		if(!is_numeric($postId)){
			$postId = $this->postId;
		}

		return $wpdb->delete(
			$this->tableName,
			['post_id' => $postId],
			['%d']
		);
	}
}