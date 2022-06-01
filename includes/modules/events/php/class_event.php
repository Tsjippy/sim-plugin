<?php
namespace SIM\EVENTS;
use SIM;
use WP_Error;

use function SIM\getModuleOption;

class Events{
	function __construct(){
		global $wpdb;
		
		$this->tableName		= $wpdb->prefix.'sim_events';
	}
	
	/**
	 * Creates the table holding all events if it does not exist
	*/
	function createEventsTable(){
		if ( !function_exists( 'maybe_create_table' ) ) { 
			require_once ABSPATH . '/wp-admin/install-helper.php'; 
		}

		global $wpdb;
		
		$charsetCollate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->tableName}(
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			post_id mediumint(9) NOT NULL,
			startdate varchar(80) NOT NULL,
			enddate varchar(80) NOT NULL,
			starttime varchar(80) NOT NULL,
			endtime varchar(80) NOT NULL,
			location varchar(80),
			organizer varchar(80),
			location_id mediumint(9),
			organizer_id mediumint(9),
			onlyfor mediumint(9),
			PRIMARY KEY  (id)
		) $charsetCollate;";

		maybe_create_table($this->tableName, $sql );
	}

	/**
	 * Sends a message to anyone who has an event which is about to start
	 * @param  	int 	$eventId		The id of the event
	 * 
	 * @return	bool					true if succesfull false if no event found
	*/
	function sendEventReminder($eventId){
		global $wpdb;
		$query		= "SELECT * FROM $this->tableName WHERE id=$eventId";
		$results	= $wpdb->get_results($query);
		
		if(empty($results)){
			return false;
		}

		$event	= $results[0];

		if(is_numeric($event->onlyfor)){
			SIM\trySendSignal(get_the_title($event->post_title)." is about to start\nIt starts at $event->starttime", $event->onlyfor);
		}

		return true;
	}

	/**
	 * Stores all event details in the db, removes any existing events, and creates new ones.
	 * @param  	int|WP_post  $post		The id of a post or the post itself
	*/
	function storeEventMeta($post){
		if(is_numeric($post))	$post	= get_post($post);
		$this->postId					= $post->ID;
	
		$event							= $_POST['event'];	
		$event['allday']				= sanitize_text_field($event['allday']);
		$event['startdate']				= sanitize_text_field($event['startdate']);
		$event['starttime']				= sanitize_text_field($event['starttime']);
		$event['enddate']				= sanitize_text_field($event['enddate']);
		$event['endtime']				= sanitize_text_field($event['endtime']);

		if(empty($event['startdate']) or empty($event['enddate']))	return;

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
	function maybeCreateRow($startdate){
		global $wpdb;
		
		//check if form row already exists
		if($wpdb->get_var("SELECT * FROM {$this->tableName} WHERE `post_id` = '{$this->postId}' AND startdate = '$startdate'") != true){
			$result = $wpdb->insert(
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
	function removeDbRows($postId = null){
		global $wpdb; 
 
		if(!is_numeric($postId)) $postId = $this->postId;
		return $wpdb->delete(
			$this->tableName,
			['post_id' => $postId],
			['%d']
		);
	}

	/**
	 * Retrieves the weeknumber from a certain date
	 * @param  	int  $date		a date in epoch
	 * 
	 * @return	int				the weeknumber
	*/
	function yearWeek($date){
		$weakYear	= intval(date('W', $date));
		return $weakYear;
	}

	/**
	 * Retrieves the weeknumber from a certain date
	 * @param  	int  $date		a date in epoch
	 * 
	 * @return	int				the weeknumber
	*/
	function monthWeek($date){
		$firstDayOfMonth	= strtotime(date('Y-m-01', $date));
		return $this->yearWeek($firstDayOfMonth);
	}
	
	/**
	 * Creates events in the db
	*/
	function createEvents(){
		global $wpdb;

		$baseStartDateStr	= $this->eventData['startdate'];
		$baseStartDate		= strtotime($baseStartDateStr);
		$baseEndDate		= $this->eventData['enddate'];
		$dayDiff 			= ((new \DateTime($baseStartDateStr))->diff((new \DateTime($baseEndDate))))->d;

		$startDates			= [$baseStartDateStr];
		if(!empty($this->eventData['repeated'])){
			//first remove any existing events for this post
			$this->removeDbRows();

			//then create the new ones
			$repeatParam	= $this->eventData['repeat'];
			$interval		= max(1,(int)$repeatParam['interval']);
			$months			= (array)$repeatParam['months'];
			$weekDays		= (array)$repeatParam['weekDays'];
			$weeks			= (array)$repeatParam['weeks'];
			$amount			= $repeatParam['amount'];
			if(!is_numeric($amount)) $amount = 200;
			$repeatStop	= $repeatParam['stop'];

			switch($repeatStop){
				case 'never':
					$repEnddate	= strtotime("+90 year",$baseStartDate);
					break;
				case 'date':
					$repEnddate	= $repeatParam['enddate'];
					break;
				case 'after':
					$repEnddate	= strtotime("+90 year",$baseStartDate);
					break;
				default:
					$repEnddate	= strtotime("+90 year",$baseStartDate);
					break;
			}
			$excludeDates	= (array)$repeatParam['excludedates'];	
			$includeDates	= (array)$repeatParam['includedates'];	
						
			$startDate		= $baseStartDate;
			$i				= 1;
			while($startDate < $repEnddate and $amount > 0){
				switch ($repeatParam['type']){
					case 'daily':
						$startDate		= strtotime("+{$i} day", $baseStartDate);
						$startDateStr	= date('Y-m-d', $startDate);
						if(!in_array(date('w', $startDate), $weekDays)){
							continue 2;
						}
						break;
					case 'weekly':
						$startDate		= strtotime("+{$i} week", $baseStartDate);
						$startDateStr	= date('Y-m-d', $startDate);
						$monthWeek		= $this->monthWeek($startDate);

						$lastWeek		= date('m',$startDate) == date('m', strtotime('+1 week', $startDate));
						if($lastWeek and in_array('last', $weeks)){
							$monthWeek	= 'last';
						}
						if(!in_array($monthWeek, $weeks)){
							continue 2;
						}
						break;
					case 'monthly':
						$startDate		= strtotime("+{$i} month", $baseStartDate);
						$month			= date('m',$startDate);
						if(!empty($months) and !in_array($month, $months)){
							continue 2;
						}

						if(!empty($weeks) and !empty($weekDays)){
							$startDate	= strtotime("{$weeks[0]} {$weekDays[0]} of this month", $startDate);
						}
						
						$startDateStr	= date('Y-m-d', $startDate);

						break;
					case 'yearly':
						$startDate	= strtotime("+{$i} year", $baseStartDate);

						if(!empty($weeks) and !empty($weekDays)){
							$startDate	= strtotime("{$weeks[0]} {$weekDays[0]} of this month", $startDate);
						}
						$startDateStr	= date('Y-m-d', $startDate);
						break;
					case 'custom_days':
						$startDateStr	= $includeDates[$i];
						break;
				}
				
				if(
					!in_array($startDateStr, $includeDates)		and		//we should not exclude this date
					$startDate < $repEnddate					and 	//falls within the limits
					(!is_numeric($amount) or $amount > 0)				//We have not exeededthe amount
				){
					$startDates[]	= $startDateStr;
				}
				$i				= $i+$interval;
				$amount			= $amount-1;
			}
		}
		
		foreach($startDates as $startDate){
			$enddate	= date('Y-m-d',strtotime("+{$dayDiff} day",strtotime($startDate)));
			$this->maybeCreateRow($startDate);

			$args	= $this->eventData;
			unset($args['startdate']);
			unset($args['repeated']);
			unset($args['repeat']);
			unset($args['allday']);
			$args['enddate']		= $enddate;

			//Update the database
			$result = $wpdb->update($this->tableName, 
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

	/**
	 * Deletes old celebration events
	 * @param  	int  	$postId		WP_Post if
	 * @param	string	$date		date string 
	 * @param	int		$userId		WP_User id
	 * @param	string	$type		the anniverasry type
	 * @param	string	$title		the event title
	*/
	function deleteOldCelEvent($postId, $date, $userId, $type, $title){
		if(is_numeric($postId)){
			$existingEvent	= $this->retrieveSingleEvent($postId);
			if(
				date('-m-d', strtotime($existingEvent->startdate)) == date('-m-d', strtotime($date)) and
				$title == $existingEvent->post_title
			){
				return false; //nothing changed
			}else{
				wp_delete_post($postId, true);
				delete_user_meta($userId, $type.'_event_id');
	
				$this->removeDbRows();
			}
		}
	}

	/**
	 * Deletes old celebration events
	 * @param	string		$type		the anniverasry type
	 * @param	int|object	$userId		WP_User id or WP_User object
	 * @param	string		$metaKey	the meta key key
	 * @param	string		$metaValue	the meta value, should be a date string
	*/
	function createCelebrationEvent($type, $user, $metaKey='', $metaValue=''){
		if(is_numeric($user)) $user = get_userdata($user);
		
		if(empty($metaKey)) $metaKey = $type;

		$oldMetaValue	= SIM\getMetaArrayValue($user->ID, $metaKey);

		if(empty($metaValue)){
			$metaValue = $oldMetaValue;
		}elseif($metaValue == $oldMetaValue){
			//nothing changed return
			return;
		}

		$title		= ucfirst($type).' '.$user->display_name;
		$partnerId	= SIM\hasPartner($user->ID);
		if($partnerId){
			$partnerMeta	= SIM\getMetaArrayValue($partnerId, $metaKey);

			//only treat as a couples event if they both have the same value
			if($partnerMeta == $metaValue){
				$partnerName	= get_userdata($partnerId)->first_name;
				$title			= ucfirst($type)." {$user->first_name} & $partnerName {$user->last_name}";
			}else{
				$partnerId	= false;
			}
		}

		if($type == 'birthday'){
			$catName = 'birthday';
		}else{
			$catName = 'anniversary';
			if(SIM\isChild($user->ID)) return;//do not create annversaries for children
		}
		$termId = get_term_by('slug', $catName,'events')->term_id;
		if(empty($termId)) $termId = wp_insert_term(ucfirst($catName),'events')['term_id'];
		
		//get old post
		$this->postId	= get_user_meta($user->ID,$type.'_event_id',true);
		$this->deleteOldCelEvent($this->postId, $metaValue, $user->ID, $type, $title);

		if($partnerId){
			$this->deleteOldCelEvent(get_user_meta($partnerId, $type.'_event_id',true), $metaValue, $user->ID, $type, $title);
		}

		if(!empty($metaValue)){
			//Get the upcoming celebration date
			$startdate								= date(date('Y').'-m-d',strtotime($metaValue));

			$this->eventData['startdate']			= $startdate;
			$this->eventData['enddate']			= $startdate;
			$this->eventData['location']			= '';
			$this->eventData['organizer']			= $user->display_name;
			$this->eventData['organizer_id']		= $user->ID;
			$this->eventData['starttime']			= '00:00';
			$this->eventData['endtime']			= '23:59';
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
			update_metadata( 'post', $this->postId,'eventdetails', $this->eventData);
			update_metadata( 'post', $this->postId,'celebrationdate', $metaValue);

			wp_set_object_terms($this->postId, $termId, 'events');

			$pictureIds	= SIM\getModuleOption('events', 'picture_ids');
			if($type == 'birthday'){
				$pictureId = $pictureIds['birthday_image'];
			}else{
				$pictureId = $pictureIds['anniversary_image'];
			}
			set_post_thumbnail( $this->postId, $pictureId);

			update_user_meta($user->ID, $type.'_event_id', $this->postId);

			if($partnerId) update_user_meta($partnerId, $type.'_event_id', $this->postId);
			$this->createEvents();
		}
	}

	/**
	 * Gets an event from the db
	 * @param	int			$postId		WP_Post id
	 * 
	 * @return	object|false			The event or false if no event found
	*/
	function retrieveSingleEvent($postId){
		global $wpdb;
		$query		= "SELECT * FROM {$wpdb->prefix}posts INNER JOIN `{$this->tableName}` ON {$wpdb->prefix}posts.ID={$this->tableName}.post_id WHERE post_id=$postId ORDER BY ABS( DATEDIFF( startdate, CURDATE() ) ) LIMIT 1";
		$results	= $wpdb->get_results($query);
		
		if(empty($results)){
			return false;
		}
		return $results[0];
	}

	/**
	 * Get all events from the db, filtered by the optional parameters
	 * @param	string			$startDate		The minimum start date
	 * @param	string			$endDate		The maximum end date
	 * @param	int				$amount			The amount of events to return
	 * @param	string			$extraQuery		Any extra sql query
	 * @param	int				$offset			The offset to apply to the results
	 * @param	int				$cat			The category the events should have
	*/
	function retrieveEvents($startDate = '', $endDate = '', $amount = '', $extraQuery = '', $offset='', $cat=''){
		global $wpdb;

		//select all events attached to a post with publish status
		$query	 = "SELECT * FROM {$wpdb->prefix}posts ";
		$query	.= "INNER JOIN `{$this->tableName}` ON {$wpdb->prefix}posts.ID={$this->tableName}.post_id";
		
		if(is_numeric($cat)){
			$query	.= " INNER JOIN `{$wpdb->prefix}term_relationships` ON {$wpdb->prefix}posts.ID={$wpdb->prefix}term_relationships.object_id";
		}
		
		$query	 .= " WHERE  {$wpdb->prefix}posts.post_status='publish'";
		
		//start date is greater than or the requested date falls in between a multiday event
		if(!empty($startDate)) $query	.= " AND(`{$this->tableName}`.`startdate` >= '$startDate' OR '$startDate' BETWEEN `{$this->tableName}`.startdate and `{$this->tableName}`.enddate)";
		
		//any event who starts before the enddate
		if(!empty($endDate)) $query		.= " AND `{$this->tableName}`.`startdate` <= '$endDate'";

		//get events with a specific category
		if(is_numeric($cat)) $query		.= " AND `{$wpdb->prefix}term_relationships`.`term_taxonomy_id` = '$cat'";
		
		//extra query
		if(!empty($extraQuery)) $query	.= " AND $extraQuery";
		
		//exclude private events which are not ours
		$userId	 = get_current_user_id();
		$query	.= " AND (`{$this->tableName}`.`onlyfor` IS NULL OR `{$this->tableName}`.`onlyfor`='$userId')";

		//sort on startdate
		$query	.= " ORDER BY `{$this->tableName}`.`startdate`, `{$this->tableName}`.`starttime` ASC";

		//LIMIT must be the very last
		if(is_numeric($amount)) $query	.= "  LIMIT $amount";
		if(is_numeric($offset)) $query	.= "  OFFSET $offset";

		$this->events 	=  $wpdb->get_results($query);
	}

	/**
	 * Get all all events of the coming 3 months with a maximum of ten
	 * 
	 * @return	string					The html containg an event list
	*/
	function upcomingEvents(){
		global $wpdb;

		$this->retrieveEvents($startDate = date("Y-m-d"), $endDate = date('Y-m-d', strtotime('+3 month')), $amount = 10, $extraQuery = "endtime > '".date('H:i', current_time('U'))."' AND {$wpdb->prefix}posts.ID NOT IN ( SELECT `post_id` FROM `{$wpdb->prefix}postmeta` WHERE `meta_key`='celebrationdate')");

		//do not list celebrations
		foreach($this->events as $key=>$event){
			if(!empty(get_post_meta($event->post_id, 'celebrationdate', true))) unset($this->events[$key]);
		}

		ob_start();
		?>
		<aside class='event'>
			<h4 class="title">Upcoming events</h4>
			<div class="upcomingevents_wrapper">
				<?php
				if(empty($this->events)){
				?>
				<div class="no-events">
					No events found!    
				</div>
				<?php
				}else{
				?>
				<div class="eventlist">
					<?php
					foreach($this->events as $event){
						$startDate		= strtotime($event->startdate);
						$eventDayNr		= date('d', $startDate);
						$eventDay		= date('l', $startDate);
						$eventMonth		= date('M', $startDate);
						$eventTitle		= get_the_title($event->post_id);
						$endDateStr		= date('d M', strtotime(($event->enddate)));

						$userId = get_post_meta($event->post_id,'user',true);
						if(is_numeric($userId) and function_exists('SIM\USERPAGE\getUserPageLink')){
							//Get the user page of this user
							$eventUrl	= SIM\USERPAGE\getUserPageLink($userId);
						}else{
							$eventUrl	= get_permalink($event->post_id);
						}
					?>
					<article class="event-article">
						<div class="event-wrapper">
							<div class="event-date">
								<?php echo "<span>$eventDayNr</span> $eventMonth";?>
							</div>
							<h4 class="event-title">
								<a href="<?php echo $eventUrl; ?>">
									<?php echo $eventTitle;?>
								</a>
							</h4>
							<div class="event-detail">
								<?php
								if($event->startdate == $event->enddate){
									echo "$eventDay {$event->starttime}";
								}else{
									echo "Until $endDateStr {$event->endtime}";
								}
								?>
							</div>
						</div>
					</article>
					<?php
					}
				}
				?>
				</div>
				<a class='calendar button' href="<?php echo SITEURL;?>/events" class="button sim">
					Calendar
				</a>
			</div>
		</aside>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get the date string of an event
	 * 
	 * @param	object		$event		The event to get the date of
	 * 
	 * @return	string					The date of the event. Startdate and end date in case of an multiday event
	*/
	function getDate($event){
		if($event->startdate != $event->enddate){
			$date		= date('d-m-Y', strtotime($event->startdate)).' - '.date('d-m-Y', strtotime($event->enddate));
		}else{
			$date		= date('d-m-Y', strtotime($event->startdate));
		}

		return $date;
	}

	/**
	 * Get the time string of an event
	 * 
	 * @param	object		$event		The event to get the time of
	 * 
	 * @return	string					The time of the event. Start time and end time in case of an multiday event
	*/
	function getTime($event){
		if($event->startdate == $event->enddate){
			if($event->allday or ($event->starttime == '00:00' and $event->endtime == '23:59')){
				$time			= 'ALL DAY';
			}else{
				$time			= $event->starttime.' - '.$event->endtime;
			}
		}else{
			$time			= date('d-m-Y',strtotime($event->startdate)).' - '.$event->starttime.' - '.date('d-m-Y',strtotime($event->enddate)).' - '.$event->endtime;
		}

		return $time;
	}

	/**
	 * Get the author details of an event
	 * 
	 * @param	object		$event		The event to get the author of
	 * 
	 * @return	string					Html with the author name linking to the user page of the author. E-mail and phonenumbers
	*/
	function getAuthorDetail($event){
		$userId		= $event->organizer_id;
		$user		= get_userdata($userId);

		if(empty($userId)){
			return $event->organizer;
		}else{
			$url	= SIM\getUserPageUrl($userId);
			$email	= $user->user_email;
			$phone	= get_user_meta($userId,'phonenumbers',true);

			$html	= "<a href='$url'>{$user->display_name}</a><br>";
			$html	.="<br><a href='mailto:$email'>$email</a>";
			if(is_array($phone)){
				foreach($phone as $p){
					$html	.="<br>$p";
				}
			}
			return $html;
		}
	}

	/**
	 * Get the location details of an event
	 * 
	 * @param	object		$event		The event to get the location of
	 * 
	 * @return	string					The location name or location url linking to the location page
	*/
	function getLocationDetail($event){
		$postId		= $event->location_id;
		$location	= get_post_meta($postId,'location',true);

		if(!is_numeric($postId)){
			return $event->location;
		}else{
			$url	= get_permalink($postId);

			$html	= "<href='$url'>{$event->location}</a><br>";
			if(!empty($location['address'])){
				$html	.="<br><a onclick='main.getRoute(this,{$location['latitude']},{$location['longitude']})'>{$location['address']}</a>";
			}
			return $html;
		}
	}

	/**
	 * Get the repitition details of an event
	 * 
	 * @param	object		$meta		The event meta data
	 * 
	 * @return	string					String describing the repetition
	*/
	function getRepeatDetail($meta){
		$html = 'Repeats '.$meta['repeat']['type'];
		if(!empty($meta['repeat']['enddate'])){
			$html	.= " until ".date('j F Y',strtotime($meta['repeat']['enddate']));
		}
		if(!empty($meta['repeat']['amount'])){
			$repeatAmount = $meta['repeat']['amount'];
			if($repeatAmount != 90){
				$html	.= " for $repeatAmount times";
			}
		}

		return $html;
	}

	/**
	 * Get the export buttons for an event
	 * 
	 * @param	object		$meta		The event meta data
	 * 
	 * @return	string					Html containing buttons to export the event
	*/
	function eventExportHtml($event){
		$eventMeta		= (array)get_post_meta($event->post_id, 'eventdetails', true);
		//set the timezone
		date_default_timezone_set(wp_timezone_string());

		$title			= urlencode($event->post_title);
		$description	= urlencode("<a href='".get_permalink($event->post_id)."'>Read more on simnigeria.org</a>");
		$location		= urlencode($event->location);
		$startDate		= date('Ymd',strtotime($event->startdate));
		$endDate		= date('Ymd',strtotime($event->enddate));

		if($event->allday){
			$enddt		= date('Ymd',strtotime('+1 day',$event->enddate));
		}else{
			$startdt	= $startDate."T".gmdate('His',strtotime($event->starttime)).'Z';
			$enddt		= $endDate."T".gmdate('His',strtotime($event->endtime)).'Z';
		}

		$gmail			= "https://calendar.google.com/calendar/render?action=TEMPLATE&text=$title&dates={$startdt}/{$enddt}&details={$description}&location={$location}&ctz=Africa/Lagos&sprop=website:simnigeria.org&sprop=name:SIM%20Nigeria";
 		if(!empty($eventMeta['repeated'])){
			$gmail		.= "&recur=RRULE:FREQ=".strtoupper($eventMeta['repeat']['type']).";INTERVAL=".$eventMeta['repeat']['interval'].';';
			$weeks 		= $eventMeta['repeat']['weeks'];
			$weekDays	= $eventMeta['repeat']['weekDays'];
			if(is_array($weeks)){
				$gmail	.= 'BYDAY=';
				foreach($weeks as $index=>$week){
					if($index>0) $gmail .= ',';
					switch($week){
						case 'First':
							$gmail	.= '1';
							break;
						case 'Second':
							$gmail	.= '2';
							break;
						case 'Third':
							$gmail	.= '3';
							break;
						case 'Fourth':
							$gmail	.= '4';
							break;
						case 'Last':
							$gmail	.= '-1';
							break;
					}
					$gmail	.= substr($weekDays[0], 0, 2);
				}

				$gmail	.= ';';
			}
		} 

		$startTime		= urlencode($event->starttime.':00');
		$endTime		= urlencode($event->endtime.':00');

		$sim			= "https://outlook.office.com/calendar/0/deeplink/compose/?path=/calendar/action/compose/&body={$description}&startdt={$event->startdate}T{$startTime}&enddt={$event->enddate}T{$endTime}&location={$location}&rru=addevent&subject=$title";
		if($event->allday){
			$sim .= '&amp;allday=true';
		}

		$html = "<div class='event-export'>";
			$html .= "<a class='button agenda-export' href='$gmail' target='_blank'>Add to Google Calendar</a>";
			$html .= "<br>";
			$html .= "<a class='button agenda-export' href='$sim' target='_blank'>Add to your SIM agenda</a>";
		$html	.= '</div>';

		return $html;								
	}

	/**
	 * Get the month calendar
	 * 
	 * @param	int		$cat		The category of events to display
	 * 
	 * @return	string				Html of the calendar
	*/
	function monthCalendar($cat=''){
		if(defined('REST_REQUEST')){
			$month		= $_POST['month'];
			$year		= $_POST['year'];
			$dateStr	= "$year-$month-01";
		}else{
			//events
			$day	= date('d');
			$month	= $_GET['month'];
			$year	= $_GET['yr'];
			if(!is_numeric($month) or strlen($month)!=2){
				$month	= date('m');
			}
			if(!is_numeric($year) or strlen($year)!=4){
				$year	= date('Y');
			}
			$dateStr	= "$year-$month-$day";
		}
		ob_start();
		
		$date			= strtotime($dateStr);
		$monthStr		= date('m', $date);
		$yearStr		= date('Y', $date);
		$minusMonth		= strtotime('-1 month', $date);
		$minusMonthStr	= date('m', $minusMonth);
		$minusYearStr	= date('Y', $minusMonth);
		$plusMonth		= strtotime('+1 month', $date);
		$plusMonthStr	= date('m', $plusMonth);
		$plusYearStr	= date('Y', $plusMonth);

		$weekDay		= date("w", strtotime(date('Y-m-01', $date)));
		$workingDate	= strtotime("-$weekDay day", strtotime(date('Y-m-01', $date)));

		$calendarRows	= '';
		$detailHtml		= '';

		$baseUrl	= plugins_url('pictures', __DIR__);

		//loop over all weeks of a month
		while(true){
			$calendarRows .= "<dl class='calendar-row'>";

			//loop over all days of a week
			while(true){
				$monthName			= date('F', $workingDate);
				$workingDateStr		= date('Y-m-d', $workingDate);
				$month				= date('m', $workingDate);
				$day				= date('j', $workingDate);
				
				//get the events for this day
				$this->retrieveEvents($workingDateStr, $workingDateStr, '', '', '', $cat);

				if(
					$workingDateStr == date('Y-m-d') or			// date is today
					(
						$monthStr != date('m') and						// We are requesting another mont than this month
						date('j', $workingDate) == 1 and				// This is the first day of the month
						date('m', $workingDate) == $monthStr			// We are in the requested month
					)
				){
					$class = 'selected';
					$hidden = '';
				}else{
					$class = '';
					$hidden = 'hidden';
				}

				if($month != date('m',$date)) $class.= ' nullday';
				if(!empty($this->events)) $class	.= ' has-event';
				
				$calendarRows .=  "<dt class='calendar-day $class' data-date='".date('Ymd', $workingDate)."'>";
					$calendarRows	.= $day;
				$calendarRows	.= "</dt>";

				$detailHtml .= "<div class='event-details-wrapper $hidden' data-date='".date('Ymd', $workingDate)."'>";
					$detailHtml .= "<h6 class='event-title'>";
						$detailHtml .= "Events for <span class='day'> ".date('j', $workingDate)."st</span>$monthName";
					$detailHtml .= "</h6>";
						if(empty($this->events)){
							$detailHtml .= "<article class='event-article'>";
								$detailHtml .= "<h4 class='event-title'><a>No Events</a></h4>";
							$detailHtml .= "</article>";
						}else{
							foreach($this->events as $event){
								$meta		= get_post_meta($event->ID,'eventdetails',true);
								$detailHtml .= "<article class='event-article'>";
									$detailHtml .= "<div class='event-header'>";
										if(has_post_thumbnail($event->post_id)){
											$detailHtml .= "<div class='event-image'>";
												$detailHtml .= get_the_post_thumbnail($event->post_id);
											$detailHtml .= '</div>';
										}
										$detailHtml .= "<div class='event-time'>";
											$detailHtml .= "<img src='{$baseUrl}/time_red.png' alt='time' class='event_icon'>";
											$detailHtml .=  $this->getTime($event);
										$detailHtml .= "</div>";
									$detailHtml .= "</div>";
									$detailHtml .= "<h4 class='event-title'>";
										$url	= get_permalink($event->ID);
										$detailHtml .= "<a href='$url'>";
											$detailHtml .= $event->post_title;
										$detailHtml .= "</a>";
									$detailHtml .= "</h4>";
									$detailHtml .= "<div class='event-detail'>";
									if(!empty($event->location)){
										$detailHtml .= "<div class='location'>";
											$detailHtml .= "<img src='{$baseUrl}/location_red.png' alt='time' class='event_icon'>";
											$detailHtml .= $this->getLocationDetail($event);
										$detailHtml .= "</div>";
									}	
									if(!empty($event->organizer)){
										$detailHtml .= "<div class='organizer'>";
											$detailHtml .= "<img src='{$baseUrl}/organizer.png' alt='time' class='event_icon'>";
											$detailHtml .= $this->getAuthorDetail($event);
										$detailHtml .= "</div>";
									}
									if(!empty($meta['repeat']['type'])){
										$detailHtml .= "<div class='repeat'>";
											$detailHtml .= "<img src='{$baseUrl}/repeat_small.png' alt='repeat' class='event_icon'>";
											$detailHtml .= $this->getRepeatDetail($meta);
										$detailHtml .= "</div>";
									}
										$detailHtml .= $this->eventExportHtml($event);
								$detailHtml .= "</article>";
							}
						} 
				$detailHtml .=  "</div>"; 
				
				//calculate the next week
				$workingDate	= strtotime('+1 day', $workingDate);
				//if the next day is the first day of a new week
				if(date('w', $workingDate) == 0) break;
			}
			$calendarRows .= '</dl>';

			//stop if next month
			if($month != date('m',$date)) break;
		}

		?>
		<div class="events-wrap" data-date="<?php echo "$yearStr-$monthStr";?>">
			<div class="event overview">
				<div class="navigator">
					<div class="prev">
						<a href="#" class="prevnext" data-month="<?php echo $minusMonthStr;?>" data-year="<?php echo $minusYearStr;?>">
							<span><</span> <?php echo date('F', $minusMonth);?>
						</a>
					</div>
					<div class="current">
						<?php echo date('F Y', $date);?>
					</div>
					<div class="next">
						<a href="#" class="prevnext" data-month="<?php echo $plusMonthStr;?>" data-year="<?php echo $plusYearStr;?>">
							<?php echo date('F', $plusMonth);?> <span>></span>
						</a>
					</div>
				</div>
				<div class="calendar-table">
					<div class="month-container">
						<dl>
							<?php
							$workingDate	= strtotime("-$weekDay day", strtotime(date('Y-m-01', $date)));
							for ($y = 0; $y <= 6; $y++) {
								$name	= date('D', $workingDate);
								echo "<dt class='calendar-day-head'>$name</dt>";
								$workingDate	= strtotime("+1 days",$workingDate);
							}
							?>
						</dl>
						<?php		
						echo $calendarRows;
						?>
					</div>
				</div>
			</div>
			<div class="event details-wrapper">
				<?php
				echo $detailHtml;
				?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get the week calendar
	 * 
	 * @param	int		$cat		The category of events to display
	 * 
	 * @return	string				Html of the calendar
	*/
	function weekCalendar($cat=''){
		if(defined('REST_REQUEST')){
			$weekNr		= $_POST['wknr'];
			$year		= $_POST['year'];
		}else{
			$weekNr		= $_GET['week'];
			$year		= $_GET['yr'];
			if(!is_numeric($weekNr) or strlen($weekNr)>2){
				$weekNr	= date('W');
			}
			if(!is_numeric($year) or strlen($year)!=4){
				$year	= date('Y');
			}
		}
		$dto 		= new \DateTime();
		$dto->setISODate($year, $weekNr);
		$dateStr 	= $dto->format('Y-m-d');

		ob_start();
		
		$date			= strtotime($dateStr);
		$weekNr			= date('W',$date);
		$dateTime		= new \DateTime();
		$workingDate	= $dateTime->setISODate(date('Y',$date), $weekNr, "0")->getTimestamp();
		$calendarRows	= [];
		$detailHtml		= '';

		$baseUrl	= plugins_url('pictures', __DIR__);

		//loop over all days of a week
		while(true){
			$workingDateStr		= date('Y-m-d',$workingDate);
			$weekDay			= date('w',$workingDate);
			$year				= date('Y',$workingDate);
			$prevWeekNr			= date("W",strtotime("-1 week",$workingDate));
			$nextWeekNr			= date("W",strtotime("+1 week",$workingDate));
			
			//get the events for this day
			$this->retrieveEvents($workingDateStr, $workingDateStr, '', '', '', $cat);

			$events 		= $this->events;
			if(!empty($events )){
				$event			= $events[0];
				$startTime		= $event->starttime;
				$endTime		= $event->endtime;

				//multi day event
				if($event->startdate != $event->enddate){
					if($event->startdate == $workingDateStr){
						$endTime	= '23:59';
					}elseif($event->enddate == $workingDateStr){
						$startTime	= '00:00';
					}else{
						$startTime	= '00:00';
						$endTime	= '23:59';
					}
				}

				//index is amount of hours times 2
				$index			= date('H', strtotime($startTime))*2;
				//plus one if starting at :30
				if(date('i', strtotime($startTime)) != '00') $index++;
			}else{
				$index	= -1;
			}

			// loop over all timeslots
			for ($x = 0; $x < 48; $x++) {
				//there is an events starting now
				if($x === $index){	
					if($startTime=='00:00' and $endTime=='23:59' and $event->startdate == $event->enddate){
						while($event->starttime == '00:00'){
							if(!empty($calendarRows['allday'][$weekDay]))	$calendarRows['allday'][$weekDay] .="<br>";
							$calendarRows['allday'][$weekDay]	.= $event->post_title;
							unset($events[0]);
							$events			= array_values($events);
							$event			= $events[0];
						}
						$calendarRows[$x][$weekDay] = "<td class='calendar-hour'></td>";
					}else{
						$duration	= strtotime($endTime)-strtotime($startTime);
						$halfHours	= round($duration/60/30);
						$endIndex	= (int)round($duration/60/30)+$index;

						//add the event
						$calendarRows[$index][$weekDay] .=  "<td rowspan='$halfHours' class='calendar-hour has-event' data-date='".date('Ymd',strtotime($event->startdate))."' data-starttime='{$event->starttime}'>";
							$calendarRows[$index][$weekDay]	.= $event->post_title;
						$calendarRows[$index][$weekDay]	.= "</td>";
						
						//add hidden cells as many as needed
						for ($y = $index+1; $y < $endIndex; $y++) {
							$calendarRows[$y][$weekDay]	= "<td class='hidden'></td>";
						}
						$x = $endIndex-1;

						unset($events[0]);
					}

					if(!empty($events)){
						$events			= array_values($events);
						$event			= $events[0];
						$startTime		= $event->starttime;
						$endTime		= $event->endtime;
						//index is amount of hours times 2
						$index			= date('H',strtotime($startTime))*2;
						//plus one if starting at :30
						if(date('i', strtotime($startTime)) != '00') $index++;
					}
				//write an empty cell
				}else{
					$calendarRows[$x][$weekDay] = "<td class='calendar-hour' data-date='".date('Ymd', $workingDate)."'></td>";
				}
			}

			foreach($this->events as $event){
				$meta	= get_post_meta($event->ID, 'eventdetails', true);
				$url	= get_permalink($event->ID);

				//do not re-add event details for a multiday event in the same week
				if($event->startdate != $event->enddate and $event->startdate != $workingDateStr and date('w', $workingDate)>0) continue;

				$detailHtml .= "<div class='event-details-wrapper hidden' data-date='".date('Ymd', strtotime($event->startdate))."' data-starttime='{$event->starttime}'>";
					$detailHtml .= "<article class='event-article'>";
						if(has_post_thumbnail($event->post_id)){
							$detailHtml .= "<div class='event-image'>";
								$detailHtml .= get_the_post_thumbnail($event->post_id);
							$detailHtml .= '</div>';
						}
						$detailHtml .= "<div class='event-time'>";
							$detailHtml .= "<img src='{$baseUrl}/time_red.png' alt='time' class='event_icon'>";
							$detailHtml .=  $this->getDate($event).'   '.$this->getTime($event);
						$detailHtml .= "</div>";

						$detailHtml .= "<h4 class='event-title'>";
							$detailHtml .= "<a href='$url'>";
								$detailHtml .= $event->post_title;
							$detailHtml .= "</a>";
						$detailHtml .= "</h4>";
						$detailHtml .= "<div class='event-detail'>";
						if(!empty($event->location)){
							$detailHtml .= "<div class='location'>";
								$detailHtml .= "<img src='{$baseUrl}/location_red.png' alt='time' class='event_icon'>";
								$detailHtml .= $this->getLocationDetail($event);
							$detailHtml .= "</div>";
						}	
						if(!empty($event->organizer)){
							$detailHtml .= "<div class='organizer'>";
								$detailHtml .= "<img src='{$baseUrl}/organizer.png' alt='time' class='event_icon'>";
								$detailHtml .= $this->getAuthorDetail($event);
							$detailHtml .= "</div>";
						}

						if(!empty($meta['repeat']['type'])){
							$detailHtml .= "<div class='repeat'>";
								$detailHtml .= "<img src='{$baseUrl}/repeat_small.png' alt='repeat' class='event_icon'>";
								$detailHtml .= $this->getRepeatDetail($meta);
							$detailHtml .= "</div>";
						}
							$detailHtml .= $this->eventExportHtml($event);
						$detailHtml .= "</div>";
					$detailHtml .= "</article>";
				$detailHtml .= "</div>";
			}
			
			//calculate the next week
			$workingDate	= strtotime('+1 day', $workingDate);
			//if the next day is the first day of a new week
			if(date('w', $workingDate) == 0) break;
		}

		?>
		<div class="events-wrap" data-weeknr="<?php echo $weekNr;?>" data-year="<?php echo $year;?>">
			<div class="event overview">
				<div class="navigator">
					<div class="prev">
						<a href="#" class="prevnext" data-weeknr="<?php echo $prevWeekNr;?>" data-year="<?php echo $year;?>">
							<span><</span> <?php echo $prevWeekNr;?>
						</a>
					</div>
					<div class="current">
						Week <?php echo $weekNr;?>
					</div>
					<div class="next">
						<a href="#" class="prevnext" data-weeknr="<?php echo $nextWeekNr;?>" data-year="<?php echo $year;?>">
							<?php echo $nextWeekNr;?> <span>></span>
						</a>
					</div>
				</div>
				<div class="calendar-table">
					<table class="week-container">
						<thead>
							<th> </th>
							<?php
							$workingDate	= $dateTime->setISODate(date('Y', $date), $weekNr, "0")->getTimestamp();
							for ($y = 0; $y <= 6; $y++) {
								$name	= date('D', $workingDate);
								$nr		= date('d', $workingDate);
								echo "<th>$name<br>$nr</th>";
								$workingDate	= strtotime("+1 days", $workingDate);
							}
							?>
						</thead>

						<tbody>
						<?php
						if(!empty($calendarRows['allday'])){
							echo "<tr class='calendar-row'>";
								echo "<td class=''><b>All day</b></td>";
							//loop over the dayweeks
							$workingDate	= $dateTime->setISODate(date('Y', $date), $weekNr, "0")->getTimestamp();
							for ($y = 0; $y <= 6; $y++) {
								$content	= $calendarRows['allday'][$y];
								if(empty($content)){
									$class='';
								}else{
									$class=' has-event';
								}
								echo "<td class='calendar-hour$class' data-date='".date('Ymd', $workingDate)."' data-starttime='00:00'>{$calendarRows['allday'][$y]}</td>";
								$workingDate	= strtotime("+1 days", $workingDate);
							}
							echo '</tr>';
						}

						unset($calendarRows['allday']);
						//one row per half an hour
						foreach($calendarRows as $i=>$row){
							echo "<tr class='calendar-row'>";
							$time	= $i/2;
							if($i % 2 == 0){
								echo "<td class='calendar-hour-head' rowspan='2'><b>$time:00</b></td>";
							}else{
								echo "<td class='hidden'></td>";
							}
							//loop over the dayweeks
							foreach($row as $cell){
								echo $cell;
							}
							echo "</tr>";
						}
						?>
						</tbody>
					</table>
				</div>
			</div>
			<div class="event details-wrapper">
				<div class="event-details-wrapper" data-date="empty">
					<article class="event-article">
						<h4 class="event-title"><a>No Event selected</a></h4>
					</article>
				</div>
				<?php
				echo $detailHtml;
				?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get the list calendar
	 * 
	 * @param	int		$cat		The category of events to display
	 * 
	 * @return	string				Html of the calendar
	*/
	function listCalendar($cat=''){
		$offset='';
		if(defined('REST_REQUEST')){
			$offset	= $_POST['offset'];
			$month	= $_POST['month'];
			$year	= $_POST['year'];
		}else{
			$month	= $_GET['month'];
			$year	= $_GET['yr'];
		}

		if(!is_numeric($month) or strlen($month)!=2){
			$month	= date('m');
		}
		if(!is_numeric($year) or strlen($year)!=4){
			$year	= date('Y');
		}
		
		$day	= date('d');
		$dateStr	= "$year-$month-$day";

		$this->retrieveEvents($dateStr, '', 10, '', $offset, $cat);
		$html ='';

		$baseUrl	= plugins_url('pictures', __DIR__);

		foreach($this->events as $event){
			$meta		= get_post_meta($event->ID,'eventdetails',true);
			$url		= get_permalink($event->ID);

			$html .= "<article class='event-article'>";
				$html .= "<div class='event-wrapper'>";
					$html .= get_the_post_thumbnail($event->post_id,'medium');
					$html .= "<h3 class='event-title'>";
						$html .= "<a href='$url'>";
							$html .= $event->post_title;
						$html .= "</a>";
					$html .= "</h3>";
					$html .= "<div class='event-detail'>";
						$html .= "<div class='date'>";
							$html .="<img src='{$baseUrl}/date.png' alt='' class='event_icon'>";
							$html .= $this->getDate($event);
						$html .= "</div>";
						$html .= "<div class='time'>";
							$html .="<img src='{$baseUrl}/time_red.png' alt='' class='event_icon'>";
							$html .= $this->getTime($event);
						$html .= "</div>";
					if(!empty($event->location)){
						$html .= "<div class='location'>";
							$html .= "<img src='{$baseUrl}/location_red.png' alt='time' class='event_icon'>";
							$html .= $this->getLocationDetail($event);
						$html .= "</div>";
					}
					if(!empty($event->organizer)){
						$html .= "<div class='organizer'>";
							$html .= "<img src='{$baseUrl}/organizer.png' alt='time' class='event_icon'>";
							$html .= $this->getAuthorDetail($event);
						$html .= "</div>";
					}
					if(!empty($meta['repeat']['type'])){
						$html .= "<div class='repeat'>";
							$html .= "<img src='{$baseUrl}/repeat_small.png' alt='repeat' class='event_icon'>";
							$html .= $this->getRepeatDetail($meta);
						$html .= "</div>";
					}

						$html .= $this->eventExportHtml($event);

					$html .= "</div>";
					$html .= "<div class='readmore'>";
						$html .= "<a class='button' href='{$url}'>Read more</a>";
					$html .= "</div>";
				$html .= "</div>";
			$html .= "</article>";
		}

		return $html;
	}
}