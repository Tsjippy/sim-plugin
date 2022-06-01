<?php
namespace SIM\EVENTS;
use SIM;

add_action( 'init', function() {
    add_rewrite_endpoint( 'public_calendar', EP_ROOT);
});

add_action( 'template_redirect', function() {
    global $wp_query;
 
    // if this is not a request for json or a singular object then bail
    if ( ! isset( $wp_query->query_vars['public_calendar'] ))     return;
 
    // include custom template
    calendarStream();
} );

//outlook.com: https://outlook.office.com/calendar/addcalendar
//google: https://calendar.google.com/calendar/u/1/r/settings/addbyurl

/**
 * Creates an iCal feed
*/
function calendarStream(){
	$userId		= $_GET['id'];
	if(!is_numeric($userId))	$userId	= -1;

	//see https://gist.github.com/jakebellacera/635416
	$icalFormat = 'Ymd\THis\Z';

	$events		= get_posts( 
		array(
			'post_type'      => 'event', 
			'posts_per_page' => -1
		) 
	);
	
	$icalStart		 = "BEGIN:VCALENDAR\r\n";
	$icalStart		.= "VERSION:2.0\r\n";
	$icalStart		.= "METHOD:PUBLISH\r\n";
	$icalStart		.= "PRODID:-//sim//website//EN\r\n";
	$icalStart		.= "X-WR-CALNAME: SIM events\r\n";
	$icalEvents	 = '';
	
	foreach($events as $event){
		$onlyFor		= get_post_meta($event->ID, 'onlyfor', true);

		//do not show events which are not meant for us
		if(!empty($onlyFor) and !in_array($userId, $onlyFor)) continue;

		//skip events without meta data
		$meta			= (array)get_post_meta($event->ID,'eventdetails',true);
		if(empty($meta)) continue;

		$icalEvent	="BEGIN:VEVENT\r\n";

		//between times
		if(empty($meta['allday'])){
			$start			= date($icalFormat,strtotime($meta['startdate'].' '.$meta['starttime']));
			$end			= date($icalFormat,strtotime($meta['enddate'].' '.$meta['endtime']));
			$start			="DTSTART:$start\r\n";
			$end			="DTEND:$end\r\n";
		//all day
		}else{
			$start			= strtotime($meta['startdate']);
			$startDate		= date('Ymd',$start);

			if($meta['startdate'] == $meta['enddate']){
				$endDate		= date('Ymd',strtotime('+1 day',$start));
			}else{
				$endDate		= date('Ymd',strtotime($meta['enddate']));
			}
			$start				="DTSTART;VALUE=DATE:$startDate\r\n";
			$end				="DTEND;VALUE=DATE:$endDate\r\n";
		}
		$icalEvent	.= $start.$end;
		
		$creationdate	 = date($icalFormat,strtotime($event->post_date_gmt));
		$icalEvent	.="DTSTAMP:$creationdate\r\n";
		$icalEvent	.="SUMMARY:{$event->post_title}\r\n";

		$url			 = get_permalink($event->ID);
		//maxline length is 75
		$icalEvent	.= trim(chunk_split("DESCRIPTION:".$event->post_title." read more on $url",74,"\r\n "))."\r\n";

		$uid			 = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
		$icalEvent		.="UID:$uid\r\n";

		$moddate		 = date($icalFormat,strtotime($event->post_modified_gmt));
		$icalEvent		.="LAST-MODIFIED:$moddate\r\n";
		$icalEvent		.="LOCATION:{$meta['location']}\r\n";

		$icalEvent	.= trim(chunk_split("URL:$url",74,"\r\n "))."\r\n";

		if(is_array($meta['repeat'])){
			$freq			 = strtoupper($meta['repeat']['type']);

			if($freq == 'CUSTOM_DAYS'){
				if(is_array($meta['repeat']['includedates'])){
					//copy the event for each includes date
					$extraIcalEvents	= '';
					foreach($meta['repeat']['includedates'] as $key=>$date){
						$extraIcalEvents	.= "END:VEVENT\r\n";
						$newStart			 = date($icalFormat, strtotime("$date ".$meta['starttime']));
						$newEnd			 	 = date($icalFormat, strtotime("$date ".$meta['endtime']));
						$newStart			 = "DTSTART:$newStart\r\n";
						$newEnd			 	 = "DTEND:$newEnd\r\n";

						$newUid			 = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));

						$extraIcalEvents 	.= str_replace([$start, $end, $uid], [$newStart, $newEnd, $newUid] ,$icalEvent);
					}

					$icalEvent .= $extraIcalEvents;
				}
			}else{
				$intval		= $meta['repeat']['interval'];

				$icalEvent	.= "RRULE:FREQ=$freq;INTERVAL=$intval;";

				if($freq == 'YEARLY' or $meta['repeat']['datetype'] == 'samedate'){
					$month	= date('m', strtotime($meta['startdate']));
					$day	= date('d', strtotime($meta['startdate']));
					if($freq == 'YEARLY'){
						$icalEvent	.="BYMONTH=$month;";
					}
					$icalEvent	.="BYMONTHDAY=$day;";
				}elseif($freq == 'MONTHLY' or $freq == 'WEEKLY'){
					$weeks			 = $meta['repeat']['weeks'];
					$weekDays		 = $meta['repeat']['weekdays'];
					if(is_array($weeks)){
						$icalEvent	.="BYDAY=";

						foreach($weeks as $index=>$week){
							if($index>0) $icalEvent	.=",";

							if($freq == 'MONTHLY'){
								//number of the week in the month
								$icalEvent	.= SIM\numberToWords($week);
							}

							//add the first two letters of the weekday of the startdate as capitals (FR)
							if(empty($weekDays[$index])){
								$icalEvent	.= strtoupper(substr(date('D',strtotime($meta['startdate'])), 0, 2));
							//add the first two letters as capitals (FR)
							}else{
								$icalEvent	.= strtoupper(substr($weekDays[$index], 0, 2));
							}
						}

						$icalEvent	.=";";
					}
				}

				if(!empty($meta['repeat']['enddate'])){
					$endDate		 = date($icalFormat,strtotime($meta['repeat']['enddate']));
					$icalEvent		.="UNTIL=$endDate;";
				}elseif(is_numeric($meta['repeat']['amount'])){
					$icalEvent		.="COUNT={$meta['repeat']['amount']};";
				}

				$icalEvent	.="\r\n";

				if(!empty($meta['repeat']['excludedates'])){
					$excludeDates		= $meta['repeat']['excludedates'];
					SIM\cleanUpNestedArray($excludeDates);
					if(!empty($excludeDates)){
						$icalEvent	.="EXDATE:";
						
						foreach($excludeDates as $i=>$exdate){
							if($i>0)$icalEvent	.= ',';
							$icalEvent	.= date($icalFormat, strtotime($exdate));
						}
						$icalEvent	.= "\r\n";
					}
				}
			}
		}

		$icalEvent	.="END:VEVENT\r\n";

		$icalEvents .= $icalEvent;
	}
	
	// close calendar
	$icalEnd = "END:VCALENDAR\r\n";

	// Set the headers
	header('Content-type: text/calendar; charset=utf-8');
	header('Content-Disposition: attachment; filename="sim_events.ics"');
	
	for ($i = 0; $i < ob_get_level(); $i++) {
		ob_get_clean();
	}
	ob_start();
	echo $icalStart.$icalEvents.$icalEnd;
	ob_end_flush();
	exit;
	die();
}