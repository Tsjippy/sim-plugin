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
    calendar_stream();
} );

//outlook.com: https://outlook.office.com/calendar/addcalendar
//google: https://calendar.google.com/calendar/u/1/r/settings/addbyurl
function calendar_stream(){
	//see https://gist.github.com/jakebellacera/635416
	$ICAL_FORMAT = 'Ymd\THis\Z';

	$events		= get_posts( 
		array(
			'post_type'      => 'event', 
			'posts_per_page' => -1
		) 
	);
	
	$ical_start		 = "BEGIN:VCALENDAR\r\n";
	$ical_start		.= "VERSION:2.0\r\n";
	$ical_start		.= "METHOD:PUBLISH\r\n";
	$ical_start		.= "PRODID:-//sim//website//EN\r\n";
	$ical_start		.= "X-WR-CALNAME: SIM events\r\n";
	$ical_events	 = '';
	
	foreach($events as $event){
		$onlyfor		= get_post_meta($event->ID,'onlyfor',true);
		$user_id		= $_GET['id'];
		if(!is_numeric($user_id))	$user_id	= 0;

		//do not show events which are not meant for us
		if(!empty($onlyfor) and !in_array($user_id, $onlyfor)) continue;

		//skip events without meta data
		$meta			= (array)get_post_meta($event->ID,'eventdetails',true);
		if(empty($meta)) continue;

		$ical_event	="BEGIN:VEVENT\r\n";

		//between times
		if(empty($meta['allday'])){
			$start			= date($ICAL_FORMAT,strtotime($meta['startdate'].' '.$meta['starttime']));
			$end			= date($ICAL_FORMAT,strtotime($meta['enddate'].' '.$meta['endtime']));
			$start			="DTSTART:$start\r\n";
			$end			="DTEND:$end\r\n";
		//all day
		}else{
			$start			= strtotime($meta['startdate']);
			$startdate		= date('Ymd',$start);

			if($meta['startdate'] == $meta['enddate']){
				$enddate		= date('Ymd',strtotime('+1 day',$start));
			}else{
				$enddate		= date('Ymd',strtotime($meta['enddate']));
			}
			$start				="DTSTART;VALUE=DATE:$startdate\r\n";
			$end				="DTEND;VALUE=DATE:$enddate\r\n";
		}
		$ical_event	.= $start.$end;
		
		$creationdate	 = date($ICAL_FORMAT,strtotime($event->post_date_gmt));
		$ical_event	.="DTSTAMP:$creationdate\r\n";
		$ical_event	.="SUMMARY:{$event->post_title}\r\n";

		$url			 = get_permalink($event->ID);
		//maxline length is 75
		$ical_event	.= trim(chunk_split("DESCRIPTION:".$event->post_title." read more on $url",74,"\r\n "))."\r\n";

		$uid			 = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
		$ical_event		.="UID:$uid\r\n";

		$moddate		 = date($ICAL_FORMAT,strtotime($event->post_modified_gmt));
		$ical_event		.="LAST-MODIFIED:$moddate\r\n";
		$ical_event		.="LOCATION:{$meta['location']}\r\n";

		$ical_event	.= trim(chunk_split("URL:$url",74,"\r\n "))."\r\n";

		if(is_array($meta['repeat'])){
			$freq			 = strtoupper($meta['repeat']['type']);

			if($freq == 'CUSTOM_DAYS'){
				if(is_array($meta['repeat']['includedates'])){
					//copy the event for each includes date
					$extra_ical_events	= '';
					foreach($meta['repeat']['includedates'] as $key=>$date){
						$extra_ical_events	.="END:VEVENT\r\n";
						$new_start			 = date($ICAL_FORMAT,strtotime("$date ".$meta['starttime']));
						$new_end			 = date($ICAL_FORMAT,strtotime("$date ".$meta['endtime']));
						$new_start			 ="DTSTART:$new_start\r\n";
						$new_end			 ="DTEND:$new_end\r\n";

						$new_uid			 = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));

						$extra_ical_events 		.= str_replace([$start,$end,$uid], [$new_start,$new_end,$new_uid] ,$ical_event);
					}

					$ical_event .= $extra_ical_events;
				}
			}else{
				$intval			 = $meta['repeat']['interval'];

				$ical_event	.="RRULE:FREQ=$freq;INTERVAL=$intval;";

				if($freq == 'YEARLY' or $meta['repeat']['datetype'] == 'samedate'){
					$month	= date('m',strtotime($meta['startdate']));
					$day	= date('d',strtotime($meta['startdate']));
					if($freq == 'YEARLY'){
						$ical_event	.="BYMONTH=$month;";
					}
					$ical_event	.="BYMONTHDAY=$day;";
				}elseif($freq == 'MONTHLY' or $freq == 'WEEKLY'){
					$weeks			 = $meta['repeat']['weeks'];
					$weekdays		 = $meta['repeat']['weekdays'];
					if(is_array($weeks)){
						$ical_event	.="BYDAY=";

						foreach($weeks as $index=>$week){
							if($index>0) $ical_event	.=",";

							if($freq == 'MONTHLY'){
								//number of the week in the month
								$ical_event	.= SIM\number_to_words($week);
							}

							//add the first two letters of the weekday of the startdate as capitals (FR)
							if(empty($weekdays[$index])){
								$ical_event.= strtoupper(substr(date('D',strtotime($meta['startdate'])),0,2));
							//add the first two letters as capitals (FR)
							}else{
								$ical_event.= strtoupper(substr($weekdays[$index],0,2));
							}
						}

						$ical_event	.=";";
					}
				}

				if(!empty($meta['repeat']['enddate'])){
					$enddate		 = date($ICAL_FORMAT,strtotime($meta['repeat']['enddate']));
					$ical_event	.="UNTIL=$enddate;";
				}elseif(is_numeric($meta['repeat']['amount'])){
					$ical_event	.="COUNT={$meta['repeat']['amount']};";
				}

				$ical_event	.="\r\n";

				if(!empty($meta['repeat']['excludedates'])){
					$excludedates		= $meta['repeat']['excludedates'];
					SIM\clean_up_nested_array($excludedates);
					if(!empty($excludedates)){
						$ical_event	.="EXDATE:";
						
						foreach($excludedates as $i=>$exdate){
							if($i>0)$ical_event	.= ',';
							$ical_event	.= date($ICAL_FORMAT,strtotime($exdate));
						}
						$ical_event	.= "\r\n";
					}
				}
			}
		}

		$ical_event	.="END:VEVENT\r\n";

		$ical_events .= $ical_event;
	}
	
	// close calendar
	$ical_end = "END:VCALENDAR\r\n";
	
	// Set the headers
	header('Content-type: text/calendar; charset=utf-8');
	header('Content-Disposition: attachment; filename="sim_events.ics"');
	
	for ($i = 0; $i < ob_get_level(); $i++) {
		ob_get_clean();
	}
	ob_start();
	echo $ical_start.$ical_events.$ical_end;
	ob_end_flush();
	exit;
	die();
}