<?php
namespace SIM\EVENTS;
use SIM;
use WP_Error;

class DisplayEvents extends Events{
	/**
	 * Get all events from the db, filtered by the optional parameters
	 * @param	string			$startDate		The minimum start date
	 * @param	string			$endDate		The maximum end date
	 * @param	int				$amount			The amount of events to return
	 * @param	string			$extraQuery		Any extra sql query
	 * @param	int				$offset			The offset to apply to the results
	 * @param	array			$cats			The categories the events should have
	*/
	public function retrieveEvents($startDate = '', $endDate = '', $amount = '', $extraQuery = '', $offset='', $cats=[]){
		global $wpdb;

		//select all events attached to a post with publish status
		$query	 = "SELECT * FROM {$wpdb->prefix}posts ";
		$query	.= "INNER JOIN `{$this->tableName}` ON {$wpdb->prefix}posts.ID={$this->tableName}.post_id";
		
		if(!empty($cats)){
			$query	.= " LEFT JOIN `{$wpdb->prefix}term_relationships` ON {$wpdb->prefix}posts.ID={$wpdb->prefix}term_relationships.object_id";
		}
		
		$query	 .= " WHERE  {$wpdb->prefix}posts.post_status='publish'";
		
		//start date is greater than or the requested date falls in between a multiday event
		if(!empty($startDate)){
			$query	.= " AND(`{$this->tableName}`.`startdate` >= '$startDate' OR '$startDate' BETWEEN `{$this->tableName}`.startdate and `{$this->tableName}`.enddate)";
		}
		
		//any event who starts before the enddate
		if(!empty($endDate)){
			$query		.= " AND `{$this->tableName}`.`startdate` <= '$endDate'";
		}

		//get events with a specific category
		if(!empty($cats)){
			$cats		= implode(', ', $cats);
			$query		.= " AND (`{$wpdb->prefix}term_relationships`.`term_taxonomy_id` IN ($cats) OR `{$wpdb->prefix}term_relationships`.`term_taxonomy_id` IS NULL)";
		}
		
		//extra query
		if(!empty($extraQuery)){
			$query	.= " AND $extraQuery";
		}
		
		//exclude private events which are not ours
		$userId	 = get_current_user_id();
		$query	.= " AND (`{$this->tableName}`.`onlyfor` IS NULL OR `{$this->tableName}`.`onlyfor`='$userId')";

		//sort on startdate
		$query	.= " ORDER BY `{$this->tableName}`.`startdate`, `{$this->tableName}`.`starttime` ASC";

		//LIMIT must be the very last
		if(is_numeric($amount)){
			$query	.= "  LIMIT $amount";
		}
		if(is_numeric($offset)){
			$query	.= "  OFFSET $offset";
		}

		$this->events 	=  $wpdb->get_results($query);
	}

	/**
	 * Get all all events of the coming X months with a maximum of X
	 * 
	 * @param	int		$max			The maximum total of items
	 * @param	int		$months			The amount of months we should get events for
	 * @param	array	$include		The categories to include
	 * 
	 * @return	string					The html containg an event list
	*/
	public function upcomingEvents($max, $months, $include){
		global $wpdb;

		$this->retrieveEvents(date("Y-m-d"), date('Y-m-d', strtotime("+$months month")), $max, "", '', $include);

		//do not list celebrations
		foreach($this->events as $key=>$event){
			// do not keep events who already happened
			if($event->startdate == date("Y-m-d") && $event->endtime < date('H:i', current_time('U'))){
				unset($this->events[$key]);
			}
		}

		ob_start();
		?>
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
						if(is_numeric($userId) && function_exists('SIM\USERPAGE\getUserPageLink')){
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
				?>
				</div>
				<?php
			}
			?>
			<a class='calendar button' href="<?php echo SITEURL;?>/events" class="button sim">
				Calendar
			</a>
		</div>
		<?php
		return ob_get_clean();
	}

	public function upcomingEventsArray(){
		global $wpdb;

		$this->retrieveEvents(date("Y-m-d"), date('Y-m-d', strtotime('+3 month')), 10, "{$wpdb->prefix}posts.ID NOT IN ( SELECT `post_id` FROM `{$wpdb->prefix}postmeta` WHERE `meta_key`='celebrationdate')");

		//do not list celebrations
		foreach($this->events as $key=>$event){
			// do not keep events who already happened
			if($event->startdate == date("Y-m-d") && $event->endtime < date('H:i', current_time('U'))){
				unset($this->events[$key]);
			}
		}

		if(empty($this->events)){
			return false;
		}

		$events	= [];
		foreach($this->events as $event){
			$startDate		= strtotime($event->startdate);
			$eventDayNr		= date('d', $startDate);
			$eventDay		= date('l', $startDate);
			$eventMonth		= date('M', $startDate);
			$eventTitle		= get_the_title($event->post_id);
			$endDateStr		= date('d M', strtotime(($event->enddate)));

			$userId = get_post_meta($event->post_id,'user',true);
			if(is_numeric($userId) && function_exists('SIM\USERPAGE\getUserPageLink')){
				//Get the user page of this user
				$eventUrl	= SIM\USERPAGE\getUserPageLink($userId);
			}else{
				$eventUrl	= get_permalink($event->post_id);
			}

			$e	= [
				'day'			=> $eventDayNr,
				'month'			=> $eventMonth,
				'url'			=> $eventUrl,
				'title'			=> $eventTitle
			];

			if($event->startdate == $event->enddate){
				$e['time']	= "$eventDay {$event->starttime}";
			}else{
				$e['time']	= "Until $endDateStr {$event->endtime}";
			}

			$events[]	= $e;
		}
		return $events;
	}

	/**
	 * Get the date string of an event
	 * 
	 * @param	object		$event		The event to get the date of
	 * 
	 * @return	string					The date of the event. Startdate and end date in case of an multiday event
	*/
	public function getDate($event){
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
	public function getTime($event){
		if($event->startdate == $event->enddate){
			if($event->allday || ($event->starttime == $this->dayStartTime && $event->endtime == $this->dayEndTime)){
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
	public function getAuthorDetail($event){
		$userId		= $event->organizer_id;
		$user		= get_userdata($userId);

		if(empty($userId)){
			return $event->organizer;
		}else{
			$url	= SIM\maybeGetUserPageUrl($userId);
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
	public function getLocationDetail($event){
		$postId		= $event->location_id;
		$location	= get_post_meta($postId,'location',true);

		if(!is_numeric($postId)){
			return $event->location;
		}else{
			$url	= get_permalink($postId);

			$html	= "<href='$url'>{$event->location}</a><br>";
			if(!empty($location['address'])){
				$html	.="<br><a onclick='Main.getRoute(this,{$location['latitude']},{$location['longitude']})'>{$location['address']}</a>";
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
	public function getRepeatDetail($meta){
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
	public function eventExportHtml($event){
		$eventMeta		= (array)get_post_meta($event->post_id, 'eventdetails', true);

		//set the timezone
		date_default_timezone_set(wp_timezone_string());

		$title			= urlencode($event->post_title);
		$description	= urlencode("<a href='".get_permalink($event->post_id)."'>Read more on simnigeria.org</a>");
		$location		= urlencode($event->location);
		$startDate		= date('Ymd',strtotime($event->startdate));
		$endDate		= date('Ymd',strtotime($event->enddate));

		if($event->allday){
			$enddt		= date('Ymd',strtotime('+1 day', $event->enddate));
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
					if($index>0){
						$gmail .= ',';
					}
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
						default:
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
	public function monthCalendar($cat=''){
		if(defined('REST_REQUEST')){
			$month		= $_POST['month'];
			$year		= $_POST['year'];
			$dateStr	= "$year-$month-01";
		}else{
			//events
			$day	= date('d');
			$month	= $_GET['month'];
			$year	= $_GET['yr'];
			if(!is_numeric($month) || strlen($month)!=2){
				$month	= date('m');
			}
			if(!is_numeric($year) || strlen($year)!=4){
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

		$baseUrl	= plugins_url('../pictures', __DIR__);

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
					$workingDateStr == date('Y-m-d') ||			// date is today
					(
						$monthStr != date('m') &&						// We are requesting another mont than this month
						date('j', $workingDate) == 1 &&				// This is the first day of the month
						date('m', $workingDate) == $monthStr			// We are in the requested month
					)
				){
					$class = 'selected';
					$hidden = '';
				}else{
					$class = '';
					$hidden = 'hidden';
				}

				if($month != date('m',$date)){
					$class.= ' nullday';
				}

				if(!empty($this->events)){
					$class	.= ' has-event';
				}
				
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
				if(date('w', $workingDate) == 0){
					break;
				}
			}
			$calendarRows .= '</dl>';

			//stop if next month
			if($month != date('m', $date)){
				break;
			}
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
	public function weekCalendar($cat=''){
		if(defined('REST_REQUEST')){
			$weekNr		= $_POST['wknr'];
			$year		= $_POST['year'];
		}else{
			$weekNr		= $_GET['week'];
			$year		= $_GET['yr'];
			if(!is_numeric($weekNr) || strlen($weekNr)>2){
				$weekNr	= date('W');
			}
			if(!is_numeric($year) || strlen($year)!=4){
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
		$baseUrl		= plugins_url('pictures', __DIR__);

		//loop over all days of a week
		while(true){
			$workingDateStr		= date('Y-m-d', $workingDate);
			$weekDay			= date('w', $workingDate);
			$year				= date('Y', $workingDate);
			$prevWeekNr			= date("W", strtotime("-1 week", $workingDate));
			$nextWeekNr			= date("W", strtotime("+1 week", $workingDate));
			
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
						$endTime	= $this->dayEndTime;
					}elseif($event->enddate == $workingDateStr){
						$startTime	= $this->dayStartTime;
					}else{
						$startTime	= $this->dayStartTime;
						$endTime	= $this->dayEndTime;
					}
				}

				//index is amount of hours times 2
				$index			= date('H', strtotime($startTime))*2;
				//plus one if starting at :30
				if(date('i', strtotime($startTime)) != '00'){
					$index++;
				}
			}else{
				$index	= -1;
			}

			// loop over all timeslots
			for ($x = 0; $x < 48; $x++) {
				//there is an events starting now
				if($x === $index){	
					if($startTime == $this->dayStartTime && $endTime == $this->dayEndTime && $event->startdate == $event->enddate){
						while($event->starttime == $this->dayStartTime){
							if(!empty($calendarRows['allday'][$weekDay])){
								$calendarRows['allday'][$weekDay] .= "<br>";
							}

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
						if(date('i', strtotime($startTime)) != '00'){
							$index++;
						}
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
				if($event->startdate != $event->enddate && $event->startdate != $workingDateStr && date('w', $workingDate)>0){
					continue;
				}

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
			if(date('w', $workingDate) == 0){
				break;
			}
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
								echo "<td class='calendar-hour$class' data-date='".date('Ymd', $workingDate)."' data-starttime='$this->dayStartTime'>{$calendarRows['allday'][$y]}</td>";
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
	public function listCalendar($cat=''){
		$offset='';
		if(defined('REST_REQUEST')){
			$offset	= $_POST['offset'];
			$month	= $_POST['month'];
			$year	= $_POST['year'];
		}else{
			$month	= $_GET['month'];
			$year	= $_GET['yr'];
		}

		if(!is_numeric($month) || strlen($month)!=2){
			$month	= date('m');
		}
		if(!is_numeric($year) || strlen($year)!=4){
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

	/**
	 * Sends a message to anyone who has an event which is about to start
	 * @param  	int 	$eventId		The id of the event
	 * 
	 * @return	bool					true if succesfull false if no event found
	
	 */
	public function sendEventReminder($eventId){
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
}

