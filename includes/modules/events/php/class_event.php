<?php
namespace SIM\EVENTS;
use SIM;
use WP_Error;

class Events{
	function __construct(){
		global $wpdb;
		
		$this->table_name		= $wpdb->prefix.'sim_events';
	}
		
	function create_events_table(){
		if ( !function_exists( 'maybe_create_table' ) ) { 
			require_once ABSPATH . '/wp-admin/install-helper.php'; 
		}

		global $wpdb;
		
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->table_name}(
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
		) $charset_collate;";

		maybe_create_table($this->table_name, $sql );
	}

	function send_event_reminder($event_id){
		global $wpdb;
		$query		= "SELECT * FROM $this->table_name WHERE id=$event_id";
		$results	= $wpdb->get_results($query);
		
		if(empty($results)){
			return false;
		}

		$event	= $results[0];

		if(is_numeric($event->onlyfor)){
			SIM\try_send_signal(get_the_title($event->post_title)." is about to start\nIt starts at $event->starttime", $event->onlyfor);
		}
	}

	function store_event_meta($post, $update){
		$this->post_id					= $post->ID;
	
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
		if(get_post_meta($this->post_id,'eventdetails',true) != $event){
			// First delete any existing events
			$this->remove_db_rows($this->post_id);

			// Delete any existing events as well
			wp_clear_scheduled_hook([$this, 'send_event_reminder'], $this->post_id);

			//store meta in db
			update_metadata( 'post', $this->post_id, 'eventdetails', $event);
		
			//create events
			$this->event_data		= $event;
			$this->create_events();	
		}
	}

	function maybe_create_row($startdate){
		global $wpdb;
		
		//check if form row already exists
		if($wpdb->get_var("SELECT * FROM {$this->table_name} WHERE `post_id` = '{$this->post_id}' AND startdate = '$startdate'") != true){
			$result = $wpdb->insert(
				$this->table_name, 
				array(
					'post_id'			=> $this->post_id,
					'startdate'			=> $startdate
				)
			);
		}
	}

	function remove_db_rows($post_id = null){
		global $wpdb; 
 
		if(!is_numeric($post_id)) $post_id = $this->post_id;
		return $wpdb->delete(
			$this->table_name,
			['post_id' => $post_id],
			['%d']
		);
	}

	function yearWeek($date){
		$weakYear	= intval(date('W',$date));
		return $weakYear;
	}

	function monthWeek($date){
		$first_day_of_month	= strtotime(date('Y-m-01',$date));
		return $this->yearWeek($first_day_of_month);
	}
	
	function create_events(){
		global $wpdb;

		$this->create_events_table();

		$base_start_date_str= $this->event_data['startdate'];
		$base_start_date	= strtotime($base_start_date_str);
		$base_end_date		= $this->event_data['enddate'];
		$day_diff 			= ((new \DateTime($base_start_date_str))->diff((new \DateTime($base_end_date))))->d;

		$startdates			= [$base_start_date_str];
		if(!empty($this->event_data['repeated'])){
			//first remove any existing events for this post
			$this->remove_db_rows();

			//then create the new ones
			$repeat_param	= $this->event_data['repeat'];
			$interval		= max(1,(int)$repeat_param['interval']);
			$months			= (array)$repeat_param['months'];
			$weekdays		= (array)$repeat_param['weekdays'];
			$weeks			= (array)$repeat_param['weeks'];
			$amount			= $repeat_param['amount'];
			if(!is_numeric($amount)) $amount = 200;
			$repeat_stop	= $repeat_param['stop'];
			switch($repeat_stop){
				case 'never':
					$rep_enddate	= strtotime("+90 year",$base_start_date);
					break;
				case 'date':
					$rep_enddate	= $repeat_param['enddate'];
					break;
				case 'after':
					$rep_enddate	= strtotime("+90 year",$base_start_date);
					break;
				default:
					$rep_enddate	= strtotime("+90 year",$base_start_date);
					break;
			}
			$excludedates	= (array)$repeat_param['excludedates'];	
			$includedates	= (array)$repeat_param['includedates'];	
						
			$startdate		= $base_start_date;
			$i				= 1;
			while($startdate < $rep_enddate and $amount > 0){
				switch ($repeat_param['type']){
					case 'daily':
						$startdate		= strtotime("+{$i} day",$base_start_date);
						$startdate_str	= date('Y-m-d',$startdate);
						if(!in_array(date('w', $startdate),$weekdays)){
							continue 2;
						}
						break;
					case 'weekly':
						$startdate		= strtotime("+{$i} week",$base_start_date);
						$startdate_str	= date('Y-m-d',$startdate);
						$monthWeek		= $this->monthWeek($startdate);

						$lastweek		= date('m',$startdate) == date('m', strtotime('+1 week', $startdate));
						if($lastweek and in_array('last',$weeks)){
							$monthWeek	= 'last';
						}
						if(!in_array($monthWeek,$weeks)){
							continue 2;
						}
						break;
					case 'monthly':
						$startdate		= strtotime("+{$i} month",$base_start_date);
						$month		= date('m',$startdate);
						if(!empty($months) and !in_array($month,$months)){
							continue 2;
						}

						if(!empty($weeks) and !empty($weekdays)){
							$startdate	= strtotime("{$weeks[0]} {$weekdays[0]} of this month",$startdate);
						}
						
						$startdate_str	= date('Y-m-d',$startdate);

						break;
					case 'yearly':
						$startdate	= strtotime("+{$i} year",$base_start_date);

						if(!empty($weeks) and !empty($weekdays)){
							$startdate	= strtotime("{$weeks[0]} {$weekdays[0]} of this month",$startdate);
						}
						$startdate_str	= date('Y-m-d',$startdate);
						break;
					case 'custom_days':
						$startdate_str	= $includedates[$i];
						break;
				}
				
				if(
					!in_array($startdate_str,$excludedates)		and		//we should not exclude this date
					$startdate < $rep_enddate					and 	//falls within the limits
					(!is_numeric($amount) or $amount > 0)				//We have not exeededthe amount
				){
					$startdates[]	= $startdate_str;
				}
				$i				= $i+$interval;
				$amount			= $amount-1;
			}
		}
		
		foreach($startdates as $startdate){
			$enddate	= date('Y-m-d',strtotime("+{$day_diff} day",strtotime($startdate)));
			$this->maybe_create_row($startdate);

			$args	= $this->event_data;
			unset($args['startdate']);
			unset($args['repeated']);
			unset($args['repeat']);
			unset($args['allday']);
			$args['enddate']		= $enddate;

			//Update the database
			$result = $wpdb->update($this->table_name, 
				$args, 
				array(
					'post_id'		=> $this->post_id,
					'startdate'		=> $startdate
				),
			);
			
			if($wpdb->last_error !== ''){
				return new WP_Error('event', $wpdb->print_error());
			}
		}
	}

	function delete_old_cel_event($post_id, $metavalue, $userId, $type, $title){
		if(is_numeric($post_id)){
			$existing_event	= $this->retrieve_single_event($post_id);
			if(
				date('-m-d',strtotime($existing_event->startdate)) == date('-m-d',strtotime($metavalue)) and
				$title == $existing_event->post_title
			){
				return false;//nothing changed
			}else{
				wp_delete_post($post_id,true);
				delete_user_meta($userId,$type.'_event_id');
	
				$this->remove_db_rows();
			}
		}
	}

	function create_celebration_event($type, $user, $metakey='', $metavalue=''){
		if(is_numeric($user)) $user = get_userdata($user);
		
		if(empty($metakey)) $metakey = $type;

		$metakeys	= explode('[', str_replace(']', '', $metakey));

		$old_metavalue = get_user_meta($user->ID, $metakeys[0], true);

		$i		= 1;
		while ($i != count($metakeys)){
			$old_metavalue	= $old_metavalue[$metakeys[$i]];
			$i++;
		}

		if(empty($metavalue)){
			$metavalue = $old_metavalue;
		}elseif($metavalue == $old_metavalue){
			//nothing changed return
			return;
		}

		$title		= ucfirst($type).' '.$user->display_name;
		$partner_id	= SIM\hasPartner($user->ID);
		if($partner_id){
			$partner_meta	= get_user_meta($partner_id, $metakeys[0], true);

			$i		= 1;
			while ($i != count($metakeys)){
				$partner_meta	= $partner_meta[$metakeys[$i]];
				$i++;
			}

			//only treat as a couples event if they both have the same value
			if($partner_meta == $metavalue){
				$partner_name	= get_userdata($partner_id)->first_name;
				$title	= ucfirst($type)." {$user->first_name} & $partner_name {$user->last_name}";
			}else{
				$partner_id	= false;
			}
		}

		if($type == 'birthday'){
			$cat_name = 'birthday';
		}else{
			$cat_name = 'anniversary';
			if(SIM\isChild($user->ID)) return;//do not create annversaries for children
		}
		$termid = get_term_by('slug', $cat_name,'events')->term_id;
		if(empty($termid)) $termid = wp_insert_term(ucfirst($cat_name),'events')['term_id'];
		
		//get old post
		$this->post_id	= get_user_meta($user->ID,$type.'_event_id',true);
		$this->delete_old_cel_event($this->post_id, $metavalue, $user->ID, $type, $title);

		if($partner_id){
			$this->delete_old_cel_event(get_user_meta($partner_id, $type.'_event_id',true), $metavalue, $user->ID, $type, $title);
		}

		if(!empty($metavalue)){
			//Get the upcoming celebration date
			$startdate								= date(date('Y').'-m-d',strtotime($metavalue));

			$this->event_data['startdate']			= $startdate;
			$this->event_data['enddate']			= $startdate;
			$this->event_data['location']			= '';
			$this->event_data['organizer']			= $user->display_name;
			$this->event_data['organizer_id']		= $user->ID;
			$this->event_data['starttime']			= '00:00';
			$this->event_data['endtime']			= '23:59';
			$this->event_data['allday']				= true;
			$this->event_data['repeated']			= 'Yes';
			$this->event_data['repeat']['interval']	= 1;
			$this->event_data['repeat']['amount']	= 90;
			$this->event_data['repeat']['stop']		= 'never';
			$this->event_data['repeat']['type']		= 'yearly';

			$post = array(
				'post_type'		=> 'event',
				'post_title'    => $title,
				'post_content'  => $title,
				'post_status'   => 'publish',
				'post_author'   => $user->ID
			);

			$this->post_id 	= wp_insert_post( $post,true,false);
			update_metadata( 'post', $this->post_id,'eventdetails',$this->event_data);
			update_metadata( 'post', $this->post_id,'celebrationdate',$metavalue);

			wp_set_object_terms($this->post_id,$termid,'events');

			if($type == 'birthday'){
				$mod_name = 'birthdaydefaultimage';
			}else{
				$mod_name = 'anniversarydefaultimage';
			}
			set_post_thumbnail( $this->post_id, get_theme_mod($mod_name,''));

			update_user_meta($user->ID, $type.'_event_id',$this->post_id);

			if($partner_id) update_user_meta($partner_id,$type.'_event_id',$this->post_id);
			$this->create_events();
		}
	}

	function retrieve_single_event($post_id){
		global $wpdb;
		$query		= "SELECT * FROM {$wpdb->prefix}posts INNER JOIN `{$this->table_name}` ON {$wpdb->prefix}posts.ID={$this->table_name}.post_id WHERE post_id=$post_id ORDER BY ABS( DATEDIFF( startdate, CURDATE() ) ) LIMIT 1";
		$results	= $wpdb->get_results($query);
		
		if(empty($results)){
			return false;
		}
		return $results[0];
	}

	function retrieve_events($startdate = '', $enddate = '', $amount = '', $extra_query = '', $offset='', $cat=''){
		global $wpdb;

		//select all events attached to a post with publish status
		$query	 = "SELECT * FROM {$wpdb->prefix}posts ";
		$query	.= "INNER JOIN `{$this->table_name}` ON {$wpdb->prefix}posts.ID={$this->table_name}.post_id";
		
		if(is_numeric($cat)){
			$query	.= " INNER JOIN `{$wpdb->prefix}term_relationships` ON {$wpdb->prefix}posts.ID={$wpdb->prefix}term_relationships.object_id";
		}
		
		$query	 .= " WHERE  {$wpdb->prefix}posts.post_status='publish'";
		
		//start date is greater than or the requested date falls in between a multiday event
		if(!empty($startdate)) $query	.= " AND(`{$this->table_name}`.`startdate` >= '$startdate' OR '$startdate' BETWEEN `{$this->table_name}`.startdate and `{$this->table_name}`.enddate)";
		
		//any event who starts before the enddate
		if(!empty($enddate)) $query		.= " AND `{$this->table_name}`.`startdate` <= '$enddate'";

		//get events with a specific category
		if(is_numeric($cat)) $query		.= " AND `{$wpdb->prefix}term_relationships`.`term_taxonomy_id` = '$cat'";
		
		//extra query
		if(!empty($extra_query)) $query	.= " AND $extra_query";
		
		//exclude private events which are not ours
		$userId	= get_current_user_id();
		$query	.= " AND (`{$this->table_name}`.`onlyfor` IS NULL OR `{$this->table_name}`.`onlyfor`='$userId')";

		//sort on startdate
		$query	.= " ORDER BY `{$this->table_name}`.`startdate`, `{$this->table_name}`.`starttime` ASC";

		//LIMIT must be the very last
		if(is_numeric($amount)) $query	.= "  LIMIT $amount";
		if(is_numeric($offset)) $query	.= "  OFFSET $offset";

		$this->events 	=  $wpdb->get_results($query);
	}

	function upcomingevents(){
		global $wpdb;

		$this->retrieve_events($startdate = date("Y-m-d"), $enddate = date('Y-m-d', strtotime('+3 month')), $amount = 10, $extra_query = "endtime > '".date('H:i', current_time('U'))."' AND {$wpdb->prefix}posts.ID NOT IN ( SELECT `post_id` FROM `{$wpdb->prefix}postmeta` WHERE `meta_key`='celebrationdate')");

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
						$startdate		= strtotime($event->startdate);
						$event_day		= date('d',$startdate);
						$eventDay		= date('l',$startdate);
						$event_month	= date('M',$startdate);
						$event_title	= get_the_title($event->post_id);
						$enddate_str	= date('d M', strtotime(($event->enddate)));

						$userId = get_post_meta($event->post_id,'user',true);
						if(is_numeric($userId) and function_exists('SIM\USERPAGE\get_user_page_link')){
							//Get the user page of this user
							$event_url	= SIM\USERPAGE\get_user_page_link($userId);
						}else{
							$event_url	= get_permalink($event->post_id);
						}
					?>
					<article class="event-article">
						<div class="event-wrapper">
							<div class="event-date">
								<?php echo "<span>$event_day</span> $event_month";?>
							</div>
							<h4 class="event-title">
								<a href="<?php echo $event_url; ?>">
									<?php echo $event_title;?>
								</a>
							</h4>
							<div class="event-detail">
								<?php
								if($event->startdate == $event->enddate){
									echo "$eventDay {$event->starttime}";
								}else{
									echo "Until $enddate_str {$event->endtime}";
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

	function get_date($event){
		if($event->startdate != $event->enddate){
			$date		= date('d-m-Y',strtotime($event->startdate)).' - '.date('d-m-Y',strtotime($event->enddate));
		}else{
			$date		= date('d-m-Y',strtotime($event->startdate));
		}

		return $date;
	}

	function get_time($event){
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

	function get_author_detail($event){
		$userId	= $event->organizer_id;
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

	function get_location_detail($event){
		$post_id	= $event->location_id;
		$location	= get_post_meta($post_id,'location',true);

		if(!is_numeric($post_id)){
			return $event->location;
		}else{
			$url	= get_permalink($post_id);
			$html	= "<href='$url'>{$event->location}</a><br>";
			if(!empty($location['address'])){
				$html	.="<br><a onclick='main.getRoute(this,{$location['latitude']},{$location['longitude']})'>{$location['address']}</a>";
			}
			return $html;
		}
	}

	function get_repeat_detail($meta){
		$html = 'Repeats '.$meta['repeat']['type'];
		if(!empty($meta['repeat']['enddate'])){
			$html	.= " until ".date('j F Y',strtotime($meta['repeat']['enddate']));
		}
		if(!empty($meta['repeat']['amount'])){
			$repeat_amount = $meta['repeat']['amount'];
			if($repeat_amount != 90){
				$html	.= " for $repeat_amount times";
			}
		}

		return $html;
	}

	function event_export_html($event){
		$event_meta		= (array)get_post_meta($event->post_id,'eventdetails',true);
		//set the timezone
		date_default_timezone_set(wp_timezone_string());

		$title			= urlencode($event->post_title);
		$description	= urlencode("<a href='".get_permalink($event->post_id)."'>Read more on simnigeria.org</a>");
		$location		= urlencode($event->location);
		$startdate		= date('Ymd',strtotime($event->startdate));
		$enddate		= date('Ymd',strtotime($event->enddate));

		if($event->allday){
			$enddt		= date('Ymd',strtotime('+1 day',$event->enddate));
		}else{
			$startdt	= $startdate."T".gmdate('His',strtotime($event->starttime)).'Z';
			$enddt		= $enddate."T".gmdate('His',strtotime($event->endtime)).'Z';
		}

		$gmail			= "https://calendar.google.com/calendar/render?action=TEMPLATE&text=$title&dates={$startdt}/{$enddt}&details={$description}&location={$location}&ctz=Africa/Lagos&sprop=website:simnigeria.org&sprop=name:SIM%20Nigeria";
 		if(!empty($event_meta['repeated'])){
			$gmail		.= "&recur=RRULE:FREQ=".strtoupper($event_meta['repeat']['type']).";INTERVAL=".$event_meta['repeat']['interval'].';';
			$weeks 		= $event_meta['repeat']['weeks'];
			$weekdays	= $event_meta['repeat']['weekdays'];
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
					$gmail	.= substr($weekdays[0], 0, 2);
				}

				$gmail	.= ';';
			}
		} 

		$starttime		= urlencode($event->starttime.':00');
		$endtime		= urlencode($event->endtime.':00');

		$sim			= "https://outlook.office.com/calendar/0/deeplink/compose/?path=/calendar/action/compose/&body={$description}&startdt={$event->startdate}T{$starttime}&enddt={$event->enddate}T{$endtime}&location={$location}&rru=addevent&subject=$title";
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

	function month_calendar($cat=''){
		if(defined('REST_REQUEST')){
			$month		= $_POST['month'];
			$year		= $_POST['year'];
			$date_str	= "$year-$month-01";
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
			$date_str	= "$year-$month-$day";
		}
		ob_start();
		
		$date			= strtotime($date_str);
		$month_str		= date('m',$date);
		$year_str		= date('Y',$date);
		$minusmonth		= strtotime('-1 month',$date);
		$minusmonth_str	= date('m',$minusmonth);
		$minusyear_str	= date('Y',$minusmonth);
		$plusmonth		= strtotime('+1 month',$date);
		$plusmonth_str	= date('m',$plusmonth);
		$plusyear_str	= date('Y',$plusmonth);

		$weekday		= date("w", strtotime(date('Y-m-01',$date)));
		$working_date	= strtotime("-$weekday day",strtotime(date('Y-m-01',$date)));

		$calendar_rows	= '';
		$detail_html	= '';

		$baseUrl	= plugins_url('pictures', __DIR__);

		//loop over all weeks of a month
		while(true){
			$calendar_rows .= "<dl class='calendar-row'>";

			//loop over all days of a week
			while(true){
				$monthname			= date('F',$working_date);
				$working_date_str	= date('Y-m-d',$working_date);
				$month				= date('m',$working_date);
				$day				= date('j',$working_date);
				
				//get the events for this day
				$this->retrieve_events($working_date_str, $working_date_str, '', '', '', $cat);

				if(
					$working_date_str== date('Y-m-d') or			// date is today
					(
						$month_str != date('m') and						// We are requesting another mont than this month
						date('j',$working_date) == 1 and				// This is the first day of the month
						date('m',$working_date) == $month_str			// We are in the requested month
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
				
				$calendar_rows .=  "<dt class='calendar-day $class' data-date='".date('Ymd',$working_date)."'>";
					$calendar_rows	.= $day;
				$calendar_rows	.= "</dt>";

				$detail_html .= "<div class='event-details-wrapper $hidden' data-date='".date('Ymd',$working_date)."'>";
					$detail_html .= "<h6 class='event-title'>";
						$detail_html .= "Events for <span class='day'> ".date('j',$working_date)."st</span>$monthname";
					$detail_html .= "</h6>";
						if(empty($this->events)){
							$detail_html .= "<article class='event-article'>";
								$detail_html .= "<h4 class='event-title'><a>No Events</a></h4>";
							$detail_html .= "</article>";
						}else{
							foreach($this->events as $event){
								$meta		= get_post_meta($event->ID,'eventdetails',true);
								$detail_html .= "<article class='event-article'>";
									$detail_html .= "<div class='event-header'>";
										if(has_post_thumbnail($event->post_id)){
											$detail_html .= "<div class='event-image'>";
												$detail_html .= get_the_post_thumbnail($event->post_id);
											$detail_html .= '</div>';
										}
										$detail_html .= "<div class='event-time'>";
											$detail_html .= "<img src='{$baseUrl}/time_red.png' alt='time' class='event_icon'>";
											$detail_html .=  $this->get_time($event);
										$detail_html .= "</div>";
									$detail_html .= "</div>";
									$detail_html .= "<h4 class='event-title'>";
										$url	= get_permalink($event->ID);
										$detail_html .= "<a href='$url'>";
											$detail_html .= $event->post_title;
										$detail_html .= "</a>";
									$detail_html .= "</h4>";
									$detail_html .= "<div class='event-detail'>";
									if(!empty($event->location)){
										$detail_html .= "<div class='location'>";
											$detail_html .= "<img src='{$baseUrl}/location_red.png' alt='time' class='event_icon'>";
											$detail_html .= $this->get_location_detail($event);
										$detail_html .= "</div>";
									}	
									if(!empty($event->organizer)){
										$detail_html .= "<div class='organizer'>";
											$detail_html .= "<img src='{$baseUrl}/organizer.png' alt='time' class='event_icon'>";
											$detail_html .= $this->get_author_detail($event);
										$detail_html .= "</div>";
									}
									if(!empty($meta['repeat']['type'])){
										$detail_html .= "<div class='repeat'>";
											$detail_html .= "<img src='{$baseUrl}/repeat_small.png' alt='repeat' class='event_icon'>";
											$detail_html .= $this->get_repeat_detail($meta);
										$detail_html .= "</div>";
									}
										$detail_html .= $this->event_export_html($event);
								$detail_html .= "</article>";
							}
						} 
				$detail_html .=  "</div>"; 
				
				//calculate the next week
				$working_date	= strtotime('+1 day', $working_date);
				//if the next day is the first day of a new week
				if(date('w',$working_date) == 0) break;
			}
			$calendar_rows .= '</dl>';

			//stop if next month
			if($month != date('m',$date)) break;
		}

		?>
		<div class="events-wrap" data-date="<?php echo "$year_str-$month_str";?>">
			<div class="event overview">
				<div class="navigator">
					<div class="prev">
						<a href="#" class="prevnext" data-month="<?php echo $minusmonth_str;?>" data-year="<?php echo $minusyear_str;?>">
							<span><</span> <?php echo date('F', $minusmonth);?>
						</a>
					</div>
					<div class="current">
						<?php echo date('F Y',$date);?>
					</div>
					<div class="next">
						<a href="#" class="prevnext" data-month="<?php echo $plusmonth_str;?>" data-year="<?php echo $plusyear_str;?>">
							<?php echo date('F', $plusmonth);?> <span>></span>
						</a>
					</div>
				</div>
				<div class="calendar-table">
					<div class="month-container">
						<dl>
							<?php
							$working_date	= strtotime("-$weekday day",strtotime(date('Y-m-01',$date)));
							for ($y = 0; $y <= 6; $y++) {
								$name	= date('D',$working_date);
								echo "<dt class='calendar-day-head'>$name</dt>";
								$working_date	= strtotime("+1 days",$working_date);
							}
							?>
						</dl>
						<?php		
						echo $calendar_rows;
						?>
					</div>
				</div>
			</div>
			<div class="event details-wrapper">
				<?php
				echo $detail_html;
				?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	function week_calendar($cat=''){
		if(defined('REST_REQUEST')){
			$week_nr	= $_POST['wknr'];
			$year		= $_POST['year'];
		}else{
			$week_nr	= $_GET['week'];
			$year		= $_GET['yr'];
			if(!is_numeric($week_nr) or strlen($week_nr)>2){
				$week_nr	= date('W');
			}
			if(!is_numeric($year) or strlen($year)!=4){
				$year	= date('Y');
			}
		}
		$dto = new \DateTime();
		$dto->setISODate($year, $week_nr);
		$date_str = $dto->format('Y-m-d');

		ob_start();
		
		$date			= strtotime($date_str);
		$week_nr		= date('W',$date);
		$datetime		= new \DateTime();
		$working_date	= $datetime->setISODate(date('Y',$date), $week_nr, "0")->getTimestamp();
		$calendar_rows	= [];
		$detail_html	= '';

		$baseUrl	= plugins_url('pictures', __DIR__);

		//loop over all days of a week
		while(true){
			$working_date_str	= date('Y-m-d',$working_date);
			$weekday			= date('w',$working_date);
			$year				= date('Y',$working_date);
			$prev_week_nr		= date("W",strtotime("-1 week",$working_date));
			$next_week_nr		= date("W",strtotime("+1 week",$working_date));
			
			//get the events for this day
			$this->retrieve_events($working_date_str, $working_date_str, '', '', '', $cat);

			$events 		= $this->events;
			if(!empty($events )){
				$event			= $events[0];
				$starttime		= $event->starttime;
				$endtime		= $event->endtime;

				//multi day event
				if($event->startdate != $event->enddate){
					if($event->startdate == $working_date_str){
						$endtime	= '23:59';
					}elseif($event->enddate == $working_date_str){
						$starttime	= '00:00';
					}else{
						$starttime	= '00:00';
						$endtime	= '23:59';
					}
				}

				//index is amount of hours times 2
				$index			= date('H',strtotime($starttime))*2;
				//plus one if starting at :30
				if(date('i',strtotime($starttime)) != '00') $index++;
			}else{
				$index	= -1;
			}

			// loop over all timeslots
			for ($x = 0; $x < 48; $x++) {
				//there is an events starting now
				if($x === $index){	
					if($starttime=='00:00' and $endtime=='23:59' and $event->startdate == $event->enddate){
						while($event->starttime == '00:00'){
							if(!empty($calendar_rows['allday'][$weekday]))	$calendar_rows['allday'][$weekday] .="<br>";
							$calendar_rows['allday'][$weekday]	.= $event->post_title;
							unset($events[0]);
							$events			= array_values($events);
							$event			= $events[0];
						}
						$calendar_rows[$x][$weekday] = "<td class='calendar-hour'></td>";
					}else{
						$duration	= strtotime($endtime)-strtotime($starttime);
						$half_hours	= round($duration/60/30);
						$endindex	= (int)round($duration/60/30)+$index;

						//add the event
						$calendar_rows[$index][$weekday] .=  "<td rowspan='$half_hours' class='calendar-hour has-event' data-date='".date('Ymd',strtotime($event->startdate))."' data-starttime='{$event->starttime}'>";
							$calendar_rows[$index][$weekday]	.= $event->post_title;
						$calendar_rows[$index][$weekday]	.= "</td>";
						
						//add hidden cells as many as needed
						for ($y = $index+1; $y < $endindex; $y++) {
							$calendar_rows[$y][$weekday]	= "<td class='hidden'></td>";
						}
						$x = $endindex-1;

						unset($events[0]);
					}

					if(!empty($events)){
						$events			= array_values($events);
						$event			= $events[0];
						$starttime		= $event->starttime;
						$endtime		= $event->endtime;
						//index is amount of hours times 2
						$index			= date('H',strtotime($starttime))*2;
						//plus one if starting at :30
						if(date('i',strtotime($starttime)) != '00') $index++;
					}
				//write an empty cell
				}else{
					$calendar_rows[$x][$weekday] = "<td class='calendar-hour' data-date='".date('Ymd',$working_date)."'></td>";
				}
			}

			foreach($this->events as $event){
				$meta		= get_post_meta($event->ID,'eventdetails',true);
				//do not re-add event details for a multiday event in the same week
				if($event->startdate != $event->enddate and $event->startdate != $working_date_str and date('w',$working_date)>0) continue;

				$detail_html .= "<div class='event-details-wrapper hidden' data-date='".date('Ymd',strtotime($event->startdate))."' data-starttime='{$event->starttime}'>";
					$detail_html .= "<article class='event-article'>";
						if(has_post_thumbnail($event->post_id)){
							$detail_html .= "<div class='event-image'>";
								$detail_html .= get_the_post_thumbnail($event->post_id);
							$detail_html .= '</div>';
						}
						$detail_html .= "<div class='event-time'>";
							$detail_html .= "<img src='{$baseUrl}/time_red.png' alt='time' class='event_icon'>";
							$detail_html .=  $this->get_date($event).'   '.$this->get_time($event);
						$detail_html .= "</div>";

						$detail_html .= "<h4 class='event-title'>";
							$url	= get_permalink($event->ID);
							$detail_html .= "<a href='$url'>";
								$detail_html .= $event->post_title;
							$detail_html .= "</a>";
						$detail_html .= "</h4>";
						$detail_html .= "<div class='event-detail'>";
						if(!empty($event->location)){
							$detail_html .= "<div class='location'>";
								$detail_html .= "<img src='{$baseUrl}/location_red.png' alt='time' class='event_icon'>";
								$detail_html .= $this->get_location_detail($event);
							$detail_html .= "</div>";
						}	
						if(!empty($event->organizer)){
							$detail_html .= "<div class='organizer'>";
								$detail_html .= "<img src='{$baseUrl}/organizer.png' alt='time' class='event_icon'>";
								$detail_html .= $this->get_author_detail($event);
							$detail_html .= "</div>";
						}

						if(!empty($meta['repeat']['type'])){
							$detail_html .= "<div class='repeat'>";
								$detail_html .= "<img src='{$baseUrl}/repeat_small.png' alt='repeat' class='event_icon'>";
								$detail_html .= $this->get_repeat_detail($meta);
							$detail_html .= "</div>";
						}

							
							$detail_html .= $this->event_export_html($event);
						$detail_html .= "</div>";
					$detail_html .= "</article>";
				$detail_html .= "</div>";
			}
			
			//calculate the next week
			$working_date	= strtotime('+1 day', $working_date);
			//if the next day is the first day of a new week
			if(date('w',$working_date) == 0) break;
		}

		?>
		<div class="events-wrap" data-weeknr="<?php echo $week_nr;?>" data-year="<?php echo $year;?>">
			<div class="event overview">
				<div class="navigator">
					<div class="prev">
						<a href="#" class="prevnext" data-weeknr="<?php echo $prev_week_nr;?>" data-year="<?php echo $year;?>">
							<span><</span> <?php echo $prev_week_nr;?>
						</a>
					</div>
					<div class="current">
						Week <?php echo $week_nr;?>
					</div>
					<div class="next">
						<a href="#" class="prevnext" data-weeknr="<?php echo $next_week_nr;?>" data-year="<?php echo $year;?>">
							<?php echo $next_week_nr;?> <span>></span>
						</a>
					</div>
				</div>
				<div class="calendar-table">
					<table class="week-container">
						<thead>
							<th> </th>
							<?php
							$working_date	= $datetime->setISODate(date('Y',$date), $week_nr, "0")->getTimestamp();
							for ($y = 0; $y <= 6; $y++) {
								$name	= date('D',$working_date);
								$nr		= date('d',$working_date);
								echo "<th>$name<br>$nr</th>";
								$working_date	= strtotime("+1 days",$working_date);
							}
							?>
						</thead>

						<tbody>
						<?php
						if(!empty($calendar_rows['allday'])){
							echo "<tr class='calendar-row'>";
								echo "<td class=''><b>All day</b></td>";
							//loop over the dayweeks
							$working_date	= $datetime->setISODate(date('Y',$date), $week_nr, "0")->getTimestamp();
							for ($y = 0; $y <= 6; $y++) {
								$content	= $calendar_rows['allday'][$y];
								if(empty($content)){
									$class='';
								}else{
									$class=' has-event';
								}
								echo "<td class='calendar-hour$class' data-date='".date('Ymd',$working_date)."' data-starttime='00:00'>{$calendar_rows['allday'][$y]}</td>";
								$working_date	= strtotime("+1 days",$working_date);
							}
							echo '</tr>';
						}

						unset($calendar_rows['allday']);
						//one row per half an hour
						foreach($calendar_rows as $i=>$row){
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
				echo $detail_html;
				?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	function list_calendar($cat=''){
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
		$date_str	= "$year-$month-$day";

		$this->retrieve_events($date_str, '', 10, '', $offset, $cat);
		$html ='';

		$baseUrl	= plugins_url('pictures', __DIR__);

		foreach($this->events as $event){
			$meta		= get_post_meta($event->ID,'eventdetails',true);
			$html .= "<article class='event-article'>";
				$html .= "<div class='event-wrapper'>";
					$html .= get_the_post_thumbnail($event->post_id,'medium');
					$html .= "<h3 class='event-title'>";
						$url	= get_permalink($event->ID);
						$html .= "<a href='$url'>";
							$html .= $event->post_title;
						$html .= "</a>";
					$html .= "</h3>";
					$html .= "<div class='event-detail'>";
						$html .= "<div class='date'>";
							$html .="<img src='{$baseUrl}/date.png' alt='' class='event_icon'>";
							$html .= $this->get_date($event);
						$html .= "</div>";
						$html .= "<div class='time'>";
							$html .="<img src='{$baseUrl}/time_red.png' alt='' class='event_icon'>";
							$html .= $this->get_time($event);
						$html .= "</div>";
					if(!empty($event->location)){
						$html .= "<div class='location'>";
							$html .= "<img src='{$baseUrl}/location_red.png' alt='time' class='event_icon'>";
							$html .= $this->get_location_detail($event);
						$html .= "</div>";
					}
					if(!empty($event->organizer)){
						$html .= "<div class='organizer'>";
							$html .= "<img src='{$baseUrl}/organizer.png' alt='time' class='event_icon'>";
							$html .= $this->get_author_detail($event);
						$html .= "</div>";
					}
					if(!empty($meta['repeat']['type'])){
						$html .= "<div class='repeat'>";
							$html .= "<img src='{$baseUrl}/repeat_small.png' alt='repeat' class='event_icon'>";
							$html .= $this->get_repeat_detail($meta);
						$html .= "</div>";
					}

						$html .= $this->event_export_html($event);

					$html .= "</div>";
					$html .= "<div class='readmore'>";
						$html .= "<a class='button' href='{$event->guid}'>Read more</a>";
					$html .= "</div>";
				$html .= "</div>";
			$html .= "</article>";
		}

		return $html;
	}
}