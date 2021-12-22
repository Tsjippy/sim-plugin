<?php
namespace SIM;

class Events{
	function __construct(){
		global $wpdb;
		
		$this->table_name	= $wpdb->prefix.'simnigeria_events';

		add_action('wp_ajax_add_event_type',array($this,'add_event_cat'));

		add_action('wp_ajax_getmonthhtml',array($this,'month_calendar'));

		add_action('wp_ajax_getweekhtml',array($this,'week_calendar'));

		add_action('wp_ajax_getlisthtml',array($this,'list_calendar'));

		add_action('frontend_post_save_meta', array($this,'store_event_meta'), 10, 2);

		add_shortcode("upcomingevents", array($this,'upcomingevents'));

		add_action( 'before_delete_post', array($this,'remove_db_rows'));
	}

	function add_event_cat(){
		verify_nonce('add_event_type_nonce');
		
		$name		= sanitize_text_field($_POST['event_type_name']);
		$parent		= $_POST['event_type_parent'];
		
		$args 		= ['slug' => strtolower($name)];
		if(is_numeric($parent)) $args['parent'] = $parent;
		
		$result = wp_insert_term( ucfirst($name), 'eventtype',$args);
		
		if(is_wp_error($result)){
			wp_die($result->get_error_message(),500);
		}else{
			wp_die(json_encode(
				[
					'id'		=> $result['term_id'],
					'name'		=> $name,
					'type'		=> 'event',
					'message'	=> "Added $name succesfully as event category",
					'callback'	=> 'cat_type_added'
				]
			));
		}
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

	function store_event_meta($post_id, $post_type){
		$this->post_id					= $post_id;
	
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
		if(get_post_meta($post_id,'eventdetails',true) != $event){
			//store meta in db
			update_post_meta($post_id,'eventdetails',$event);
		
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
	
	function create_events($onlyfor=null){
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
							continue;
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
							continue;
						}
						break;
					case 'monthly':
						$startdate		= strtotime("+{$i} month",$base_start_date);
						$month		= date('m',$startdate);
						if(!empty($months) and !in_array($month,$months)){
							continue;
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
			$args['onlyfor']		= $onlyfor;

			//Update the database
			$result = $wpdb->update($this->table_name, 
				$args, 
				array(
					'post_id'		=> $this->post_id,
					'startdate'		=> $startdate
				),
			);
			
			if($wpdb->last_error !== ''){
				wp_die($wpdb->print_error(),500);
			}
		}
	}

	function create_celebration_event($type, $user, $metakey='', $metavalue=''){
		if(is_numeric($user)) $user = get_userdata($user);
		
		if(empty($metakey)) $metakey = $type;

		if(empty($metavalue))	$metavalue = get_user_meta($user->ID,$metakey,true);

		$title	= ucfirst($type).' '.$user->display_name;
		$partner_id	= has_partner($user->ID);
		if($partner_id){
			$partner_meta	= get_user_meta($partner_id,$metakey,true);

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
			if(is_child($user->ID)) return;//do not create annversaries for children
		}
		$termid = get_term_by('slug', $cat_name,'eventtype')->term_id;
		if(empty($termid)) $termid = wp_insert_term(ucfirst($cat_name),'eventtype')['term_id'];
		
		//get old post
		$this->post_id	= get_user_meta($user->ID,$type.'_event_id',true);

		if(is_numeric($this->post_id)){
			$existing_event	= $this->retrieve_single_event($this->post_id)->startdate;
			if(date('-m-d',strtotime($existing_event)) == date('-m-d',strtotime($metavalue))){
				return;//nothing changed
			}else{
				wp_delete_post($this->post_id,true);
				delete_user_meta($user->ID,$type.'_event_id');
	
				$this->remove_db_rows();
			}
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
				'post_content'  => '',
				'post_status'   => 'publish',
				'post_author'   => $user->ID
			);

			$this->post_id 	= wp_insert_post( $post,true,false);
			update_post_meta($this->post_id,'eventdetails',$this->event_data);
			update_post_meta($this->post_id,'celebrationdate',$metavalue);

			wp_set_object_terms($this->post_id,$termid,'eventtype');

			if($type == 'birthday'){
				$mod_name = 'birthdaydefaultimage';
			}else{
				$mod_name = 'anniversarydefaultimage';
			}
			set_post_thumbnail( $this->post_id, get_theme_mod($mod_name,''));

			update_user_meta($user->ID,$type.'_event_id',$this->post_id);

			if($partner_id) update_user_meta($partner_id,$type.'_event_id',$this->post_id);
			$this->create_events();
		}
	}

	function retrieve_single_event($post_id){
		global $wpdb;
		$query	= "SELECT * FROM {$wpdb->prefix}posts INNER JOIN `{$this->table_name}` ON {$wpdb->prefix}posts.ID={$this->table_name}.post_id WHERE  {$wpdb->prefix}posts.post_status='publish' AND post_id=$post_id ORDER BY ABS( DATEDIFF( startdate, CURDATE() ) ) LIMIT 1";
		return $wpdb->get_results($query)[0];
	}

	function retrieve_events($startdate = '', $enddate = '', $amount = '', $extra_query = '', $offset=''){
		global $wpdb;

		//select all events attached to a post with publish status
		$query	= "SELECT * FROM {$wpdb->prefix}posts INNER JOIN `{$this->table_name}` ON {$wpdb->prefix}posts.ID={$this->table_name}.post_id WHERE  {$wpdb->prefix}posts.post_status='publish'";
		
		//start date is greater than or the requested date falls in between a multiday event
		if(!empty($startdate)) $query	.= " AND(`{$this->table_name}`.`startdate` >= '$startdate' OR '$startdate' BETWEEN `{$this->table_name}`.startdate and `{$this->table_name}`.enddate)";
		
		//any event who starts before the enddate
		if(!empty($enddate)) $query		.= " AND `{$this->table_name}`.`startdate` <= '$enddate'";
		
		//extra query
		if(!empty($extra_query)) $query	.= " AND $extra_query";
		
		//exclude private events which are not ours
		$user_id	= get_current_user_id();
		$query	.= " AND (`{$this->table_name}`.`onlyfor` IS NULL OR `{$this->table_name}`.`onlyfor`='$user_id')";

		//sort on startdate
		$query	.= " ORDER BY `{$this->table_name}`.`startdate`, `{$this->table_name}`.`starttime` ASC";

		//LIMIT must be the very last
		if(is_numeric($amount)) $query	.= "  LIMIT $amount";
		if(is_numeric($offset)) $query	.= "  OFFSET $offset";

		$this->events 	=  $wpdb->get_results($query);
	}

	// Frontpage eventlist
	function upcomingevents($atts){
		global $LoggedInHomePage;

		if(!is_page($LoggedInHomePage)) return;
		$this->retrieve_events($startdate = date("Y-m-d"), $enddate = date('Y-m-d', strtotime('+3 month')), $amount = 10);

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
						//do not list celebrations
						if(!empty(get_post_meta($event->post_id,'celebrationdate',true))) continue;

						$startdate		= strtotime($event->startdate);
						$event_day		= date('d',$startdate);
						$eventDay		= date('l',$startdate);
						$event_month	= date('M',$startdate);
						$event_title	= get_the_title($event->post_id);
						$enddate_str	= date('d M', strtotime(($event->enddate)));

						$user_id = get_post_meta($event->post_id,'user',true);
						if(is_numeric($user_id)){
							//Get the missionary page of this user
							$missionary_page_id = get_user_meta($user_id,'missionary_page_id',true);
							if(is_numeric($missionary_page_id)){
								$event_url		= get_permalink($missionary_page_id);
							}
						}else{
							$event_url		= get_permalink($event->post_id);
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
				<a class='calendar button' href="<?php echo get_site_url();?>/events" class="button sim">
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
		$user_id	= $event->organizer_id;
		$user		= get_userdata($user_id);

		if(empty($user_id)){
			return $event->organizer;
		}else{
			$url	= get_missionary_page_url($user_id);
			$email	= $user->user_email;
			$phone	= get_user_meta($user_id,'phonenumbers',true);
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
				$html	.="<br><a onclick='getRoute(this,{$location['latitude']},{$location['longitude']})'>{$location['address']}</a>";
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
			$html	.= " for {$meta['repeat']['amount']} times";
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

	//full calendar
	function month_calendar(){
		global $picturesurl;

		if(wp_doing_ajax()){
			$month	= $_POST['month'];
			$year		= $_POST['year'];
			if(!is_numeric($month)) 	wp_die('Invalid month given',500); 
			if(!is_numeric($year)) 		wp_die('Invalid year given',500);
			$date_str	= "$year-$month-01";
		}else{
			wp_enqueue_script('simnigeria_event_script');
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
				$this->retrieve_events($working_date_str,$working_date_str);

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
											$detail_html .= "<img src='$picturesurl/time_red.png' alt='time' class='event_icon'>";
											$detail_html .=  $this->get_time($event);
										$detail_html .= "</div>";
									$detail_html .= "</div>";
									$detail_html .= "<h4 class='event-title'>";
										$detail_html .= "<a href='{$event->guid}'>";
											$detail_html .= $event->post_title;
										$detail_html .= "</a>";
									$detail_html .= "</h4>";
									$detail_html .= "<div class='event-detail'>";
									if(!empty($event->location)){
										$detail_html .= "<div class='location'>";
											$detail_html .= "<img src='$picturesurl/location_red.png' alt='time' class='event_icon'>";
											$detail_html .= $this->get_location_detail($event);
										$detail_html .= "</div>";
									}	
									if(!empty($event->organizer)){
										$detail_html .= "<div class='organizer'>";
											$detail_html .= "<img src='$picturesurl/organizer.png' alt='time' class='event_icon'>";
											$detail_html .= $this->get_author_detail($event);
										$detail_html .= "</div>";
									}
									if(!empty($meta['repeat']['type'])){
										$detail_html .= "<div class='repeat'>";
											$detail_html .= "<img src='$picturesurl/repeat_small.png' alt='repeat' class='event_icon'>";
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
						<dl class="calendar-table-head">
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

		if(wp_doing_ajax()){
			wp_die(
				json_encode([
					'html'		=> ob_get_clean(),
					'callback'	=> 'add_month',
				])
			);
		}else{
			return ob_get_clean();
		}
	}

	function week_calendar(){
		global $picturesurl;

		if(wp_doing_ajax()){
			$week_nr	= $_POST['wknr'];
			$year		= $_POST['year'];
			if(!is_numeric($week_nr)) 	wp_die('Invalid week given',500); 
			if(!is_numeric($year)) 		wp_die('Invalid year given',500); 
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

		//loop over all days of a week
		while(true){
			$working_date_str	= date('Y-m-d',$working_date);
			$day				= date('j',$working_date);
			$weekday			= date('w',$working_date);
			$year				= date('Y',$working_date);
			$prev_week_nr		= date("W",strtotime("-1 week",$working_date));
			$next_week_nr		= date("W",strtotime("+1 week",$working_date));
			
			//get the events for this day
			$this->retrieve_events($working_date_str,$working_date_str);

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
							$detail_html .= "<img src='$picturesurl/time_red.png' alt='time' class='event_icon'>";
							$detail_html .=  $this->get_date($event).'   '.$this->get_time($event);
						$detail_html .= "</div>";

						$detail_html .= "<h4 class='event-title'>";
							$detail_html .= "<a href='{$event->guid}'>";
								$detail_html .= $event->post_title;
							$detail_html .= "</a>";
						$detail_html .= "</h4>";
						$detail_html .= "<div class='event-detail'>";
						if(!empty($event->location)){
							$detail_html .= "<div class='location'>";
								$detail_html .= "<img src='$picturesurl/location_red.png' alt='time' class='event_icon'>";
								$detail_html .= $this->get_location_detail($event);
							$detail_html .= "</div>";
						}	
						if(!empty($event->organizer)){
							$detail_html .= "<div class='organizer'>";
								$detail_html .= "<img src='$picturesurl/organizer.png' alt='time' class='event_icon'>";
								$detail_html .= $this->get_author_detail($event);
							$detail_html .= "</div>";
						}

						if(!empty($meta['repeat']['type'])){
							$detail_html .= "<div class='repeat'>";
								$detail_html .= "<img src='$picturesurl/repeat_small.png' alt='repeat' class='event_icon'>";
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
						<thead class="calendar-table-head">
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

		if(wp_doing_ajax()){
			wp_die(
				json_encode([
					'html'		=> ob_get_clean(),
					'callback'	=> 'add_week',
				])
			);
		}else{
			return ob_get_clean();
		}
	}

	function list_calendar(){
		global $picturesurl;

		$offset='';
		if(wp_doing_ajax()){
			if(is_numeric($_POST['offset'])){
				$offset	= $_POST['offset'];
			}else{
				wp_die('Invalid date given',500);
			}

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

		$this->retrieve_events($date_str,'',10,'',$offset);
		$html ='';

		foreach($this->events as $event){
			$meta		= get_post_meta($event->ID,'eventdetails',true);
			$html .= "<article class='event-article'>";
				$html .= "<div class='event-wrapper'>";
					$html .= get_the_post_thumbnail($event->post_id,'medium');
					$html .= "<h3 class='event-title'>";
						$html .= "<a href='{$event->guid}'>";
							$html .= $event->post_title;
						$html .= "</a>";
					$html .= "</h3>";
					$html .= "<div class='event-detail'>";
						$html .= "<div class='date'>";
							$html .="<img src='$picturesurl/date.png' alt='' class='event_icon'>";
							$html .= $this->get_date($event);
						$html .= "</div>";
						$html .= "<div class='time'>";
							$html .="<img src='$picturesurl/time_red.png' alt='' class='event_icon'>";
							$html .= $this->get_time($event);
						$html .= "</div>";
					if(!empty($event->location)){
						$html .= "<div class='location'>";
							$html .= "<img src='$picturesurl/location_red.png' alt='time' class='event_icon'>";
							$html .= $this->get_location_detail($event);
						$html .= "</div>";
					}
					if(!empty($event->organizer)){
						$html .= "<div class='organizer'>";
							$html .= "<img src='$picturesurl/organizer.png' alt='time' class='event_icon'>";
							$html .= $this->get_author_detail($event);
						$html .= "</div>";
					}
					if(!empty($meta['repeat']['type'])){
						$html .= "<div class='repeat'>";
							$html .= "<img src='$picturesurl/repeat_small.png' alt='repeat' class='event_icon'>";
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

		if(wp_doing_ajax()){
			wp_die(
				json_encode([
					'html'		=> $html,
					'callback'	=> 'expand_list',
				])
			);
		}else{
			return $html;
		}
	}
}

add_action('init', function(){
	$GLOBALS['Events']	= new Events();

	register_post_type_and_tax('event', 'events');
	add_action('frontend_post_modal', 'SIM\add_event_modal');
	add_action('frontend_post_before_content', 'SIM\event_specific_fields',10,2);
	add_action('frontend_post_content_title', 'SIM\event_title');
	
	add_filter(
			'widget_categories_args',
			function ( $cat_args, $instance  ) {
				//if we are on a eventtype page, change to display the event types
				if(is_tax('eventtype') or is_page('event') or get_post_type()=='event'){
					$cat_args['taxonomy'] 		= 'eventtype';
					$cat_args['hierarchical']	= true;
					$cat_args['hide_empty'] 	= false;
				}
				
				return $cat_args;
			},
			10, 
			2 
		);
}, 999);

function add_event_modal(){
	global $FrontEndContent;
	$FrontEndContent->add_modal('event');
}

function event_title($post_type){
	$class = 'event';
	if($post_type != 'event')	$class .= ' hidden';
	
	echo "<label class='$class' name='event_content_label'>";
		echo '<h4>Describe the event</h4>';
	echo "</label>";
}

function event_specific_fields($post_type,$post_id){
	global $FrontEndContent;
	
	$categories	= get_categories( array(
		'orderby' => 'name',
		'order'   => 'ASC',
		'taxonomy'=> 'eventtype',
		'hide_empty' => false,
	) );
	
	$FrontEndContent->show_categories('event',$categories);
	
	$eventdetails	= (array)get_post_meta($post_id,'eventdetails',true);
	$repeat_param	= $eventdetails['repeat'];
	
	?>
	<br>
	<div class="event <?php if($post_type != 'event') echo 'hidden'; ?>">
		<label>
			<input type='checkbox' name='event[allday]' value='allday' <?php if(!empty($eventdetails['allday'])) echo 'checked'?> ;>
			All day event
		</label>
	
		<label name="startdate_label">
			<h4>Startdate</h4>
			<input type='date'						name='event[startdate]' value='<?php echo $eventdetails['startdate']; ?>' required>
			<input type='time' class='eventtime<?php if(!empty($eventdetails['allday'])) echo " hidden";?>'	name='event[starttime]' value='<?php echo $eventdetails['starttime']; ?>' required>
		</label>
		
		<label name="enddate_label" <?php if(!empty($eventdetails['allday'])) echo "class='hidden'";?>>
			<h4>Enddate</h4>
			<input type='date'						name='event[enddate]' value='<?php echo $eventdetails['enddate']; ?>' required>
			<input type='time' class='eventtime'	name='event[endtime]' value='<?php echo $eventdetails['endtime']; ?>' required>
		</label>

		<label name="location">
			<h4>Location</h4>
			<input type='hidden' class='datalistvalue'	name='event[location_id]' 	value='<?php echo $eventdetails['location_id']; ?>'>
			<input type='text'							name='event[location]' 		value='<?php echo $eventdetails['location']; ?>' list="locations">
			<datalist id="locations">
				<?php
				$locations = get_posts( 
					array(
						'post_type'      => 'location', 
						'posts_per_page' => -1,
						'orderby'    	=> 'title',
						'order' 	=> 'ASC'
					) 
				);
				foreach($locations as $location){
					echo "<option data-value='{$location->ID}' value='{$location->post_title}'></option>";
				}
				?>
			</datalist>
		</label>

		<label name="organizer">
			<h4>Organizer</h4>
			<input type='hidden' class='datalistvalue'	name='event[organizer_id]'	value='<?php echo $eventdetails['organizer_id']; ?>'>
			<input type='text'							name='event[organizer]'		value='<?php echo $eventdetails['organizer']; ?>' list="users">
			<datalist id="users">
				<?php
				foreach(get_missionary_accounts(false,true,true) as $user){
					echo "<option data-value='{$user->ID}' value='{$user->display_name}'></option>";
				}
				?>
			</datalist>
		</label>
		
		<label class='block'>
			<button class='button' type='button' name='enable_event_repeat'>
				Repeat this event
			</button>
			<input type='hidden' name='event[repeated]' value='<?php echo $eventdetails['repeated'];?>'>
		</label>
		
		<fieldset class='repeat_wrapper <?php if(empty($eventdetails['repeated'])) echo 'hidden';?>'>
			<legend>Repeat parameters</legend>
			<h4>Select repeat type:</h4>
			<select name="event[repeat][type]">
				<option value="">---</option>
				<option value="daily"		<?php if($repeat_param['type'] == 'daily') echo 'selected';?>>Daily</option>
				<option value="weekly"		<?php if($repeat_param['type'] == 'weekly') echo 'selected';?>>Weekly</option>
				<option value="monthly"		<?php if($repeat_param['type'] == 'monthly') echo 'selected';?>>Monthly</option>
				<option value="yearly"		<?php if($repeat_param['type'] == 'yearly') echo 'selected';?>>Yearly</option>
				<option value="custom_days"	<?php if($repeat_param['type'] == 'custom_days') echo 'selected';?>>Custom Days</option>
			</select>

			<div class='repeatdatetype <?php echo (($repeat_param['type'] == 'monthly' or $repeat_param['type'] == 'yearly') ? 'hide' : 'hidden');?>'>
				<h4>Select repeat pattern:</h4>
				<label class='optionlabel'>
					<input type='radio' name='event[repeat][datetype]' value='samedate' <?php if($repeat_param['datetype'] == 'samedate') echo 'checked';?>>
					<?php
					$monthday	= explode('-',$eventdetails['startdate'])[2];
					if($repeat_param['type'] == 'monthly'){
						$monthtext	= "a month";
					}elseif($repeat_param['type'] == 'yearly'){
						$monthnumber	= explode('-',$eventdetails['startdate'])[1];
						$monthname		= date("F", mktime(0, 0, 0, $monthnumber, 10));
						$monthtext		= "$monthname every year";
					}
					
					echo "On the <span class='monthday'>$monthday</span>th of <span class='monthoryear'>$monthtext</span>";
					?>
				</label>
				<label class='optionlabel'>
					<input type='radio' name='event[repeat][datetype]' value='patterned' <?php if($repeat_param['datetype'] == 'patterned') echo 'checked';?>>
					<?php
					echo "<span class='monthoryeartext'>";
					if($repeat_param['type'] == 'monthly'){
						echo "On a certain day and week of a month";
					}else{
						echo "On a certain day, week and month of a year";
					}
					echo "</span>";
					?>
				</label>
			</div>
						
			<label class='repeatinterval <?php echo ($repeat_param['datetype'] == 'samedate' ? 'hide' : 'hidden');?>'>
				<h4>Repeat every </h4>
				<?php
				if(empty($repeat_param['interval'])){
					$value	= 1;
				}else{
					$value	= $repeat_param['interval'];
				}
				?>
				<input type='number' name='event[repeat][interval]' value='<?php echo $value;?>'>
				<span id='repeattype'></span>
			</label>

			<div class="selector_wrapper months <?php echo ($repeat_param['datetype'] == 'patterned' ? 'hide' : 'hidden');?>">
				<?php
				if($repeat_param['type'] == 'monthly'){
					$type		= 'checkbox';
					$hidden1	= 'hide';
					$hidden2	= 'hidden';
				}else{
					$type		= 'radio';
					$hidden1	= 'hidden';
					$hidden2	= 'hide';
				}
				?>
				<h4 class='checkbox <?php echo $hidden1;?>'>Select month(s) this event should be repeated on</h4>
				<h4 class='radio <?php echo $hidden2;?>'>Select the month this event should be repeated on</h4>
				<label class='selectall_wrapper optionlabel <?php echo $hidden1;?>'><input type='<?php echo $type;?>' class='selectall' name='allmonths' value='all'>Select all months<br></label>
				<?php
				for ($m=1;$m<13;$m++){
					$monthname = date("F", mktime(0, 0, 0, $m, 10));
					if(is_array($eventdetails['repeat']['months']) and in_array($m,$eventdetails['repeat']['months'])){
						$checked = 'checked';
					}else{
						$checked = '';
					}
					echo  "<label class='optionlabel month'><input type='$type' name='event[repeat][months][]' value='$m' $checked>$monthname</label>";
					if($m  % 3 == 0) echo  '<br>';
				}
				?>
			</div>
			
			<div class="selector_wrapper weeks <?php echo ($repeat_param['datetype'] == 'patterned' ? 'hide' : 'hidden');?>">
				<?php
				if($repeat_param['type'] == 'weekly'){
					$type		= 'checkbox';
					$hidden1	= 'hide';
					$hidden2	= 'hidden';
				}else{
					$type		= 'radio';
					$hidden1	= 'hidden';
					$hidden2	= 'hide';
				}

				$weeknames = ['First','Second','Third','Fourth','Fifth','Last'];
				?>
				<h4 class='checkbox <?php echo $hidden1;?>'>Select weeks(s) of the month this event should be repeated on</h4>
				<h4 class='radio <?php echo $hidden2;?>'>Select the week of the month this event should be repeated on</h4>
				<label class='selectall_wrapper optionlabel <?php echo $hidden1;?>'><input type='checkbox' class='selectall' name='allweekss' value='all'>Select all weeks<br></label>
				<?php
				foreach($weeknames as $index=>$weekname){
					if(is_array($eventdetails['repeat']['weeks']) and in_array($weekname,$eventdetails['repeat']['weeks'])){
						$checked = 'checked';
					}else{
						$checked = '';
					}
					echo "<label class='optionlabel'><input type='$type' name='event[repeat][weeks][]' value='$weekname' $checked>$weekname</label>";
				}
				?>
			</div>
			
			<div class="selector_wrapper day_selector <?php echo ($repeat_param['datetype'] == 'patterned' ? 'hide' : 'hidden');?>">
				<?php
				if($repeat_param['type'] == 'weekly'){
					$type		= 'checkbox';
					$hidden1	= 'hide';
					$hidden2	= 'hidden';
				}else{
					$type		= 'radio';
					$hidden1	= 'hidden';
					$hidden2	= 'hide';
				}
				?>
				<h4 class='checkbox <?php echo $hidden1;?>'>Select day(s) this event should be repeated on</h4>
				<h4 class='radio <?php echo $hidden2;?>'>Select the day this event should be repeated on</h4>
				<label class='selectall_wrapper optionlabel <?php echo $hidden1;?>'><input type='checkbox' class='selectall' name='alldays' value='all'>Select all days<br></label>
				<?php 
				foreach(['Sunday','Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $index=>$dayname){
					if(is_array($eventdetails['repeat']['weekdays']) and in_array($dayname,$eventdetails['repeat']['weekdays'])){
						$checked	= 'checked';
					}else{
						$checked	= '';
					}
					echo"<label class='optionlabel'><input type='$type' name='event[repeat][weekdays][]' value='$dayname'$checked>$dayname</label>";
				}
				?>
			</div>
			
			<div class="custom_dates_selector <?php echo ($repeat_param['type'] == 'custom_days' ? 'hide' : 'hidden');?>">
				<h4> Specify repeat days</h4>
				<div class="clone_divs_wrapper">
					<?php
					$includedates	= $eventdetails['repeat']['includedates'];
					if(!is_array($includedates)){
						$includedates	= [''];
					}
					
					foreach($includedates as $index=>$includedate){
						?>
						<div id="includedate_div_<?php echo $index;?>" class="clone_div" data-divid="<?php echo $index;?>">
							<label>Include date <?php echo $index+1;?></label>
							<div class='buttonwrapper'>
								<input type="date" name="event[repeat][includedates][<?php echo $index;?>]" style="flex: 9;" value="<?php echo $includedate;?>">
								<button type="button" class="add button" style="flex: 1;">+</button>
							</div>
						</div>
						<?php
					}
					?>
				</div>
			</div>
			
			<h4>Stop repetition</h4>
			<label>
				<input type='radio' name='event[repeat][stop]' value='never' <?php if(empty($eventdetails['repeat']['stop']) or $eventdetails['repeat']['stop'] == 'never') echo 'checked';?>>
				Never
			</label><br>
			<div class='repeat_type_option'>
				<label>
					<input type='radio' name='event[repeat][stop]' value='date' <?php if($eventdetails['repeat']['stop'] == 'date') echo 'checked';?>>
					On this date:
				</label><br>
				
				<label class='repeat_type_option_specifics hidden'>
					<input type='date' name='event[repeat][enddate]' value='<?php echo $eventdetails['repeat']['enddate'];?>'>
				</label>
			</div>

			<div class='repeat_type_option'>
				<label>
					<input type='radio' name='event[repeat][stop]' value='after' <?php if($eventdetails['repeat']['stop'] == 'after') echo 'checked';?>>
					After this amount of repeats:<br>
				</label>
				
				<label class='repeat_type_option_specifics hidden'>
					<input type='number' name='event[repeat][amount]' value='<?php echo $eventdetails['repeat']['amount'];?>'>
				</label>
			</div>
			
			<div>
				<h4>Exclude dates from this pattern</h4>
				<div class="clone_divs_wrapper">
				<?php
				$excludedates	= (array)$eventdetails['repeat']['excludedates'];
				if(empty($excludedates)){
					$excludedates	= [''];
				}
				
				foreach($excludedates as $index=>$excludedate){
					?>
					<div id="excludedate_div_<?php echo $index;?>" class="clone_div" data-divid="<?php echo $index;?>">
						<label>Exclude date <?php echo $index+1;?></label>
						<div class='buttonwrapper'>
							<input type="date" name="event[repeat][excludedates][<?php echo $index;?>]" style="flex: 9;" value="<?php echo $excludedate;?>">
							<button type="button" class="add button" style="flex: 1;">+</button>
						</div>
					</div>
					<?php
				}
				?>
				</div>
			</div>
		</fieldset>
	</div>
	<?php
}


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
	global $num_word_list;

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
	$ical_start		.= "PRODID:-//simnigeria//website//EN\r\n";
	$ical_start		.= "X-WR-CALNAME: SIM Nigeria events\r\n";
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
								$ical_event	.= array_search(strtolower($week), $num_word_list);
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
					remove_from_nested_array($excludedates);
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
	header('Content-Disposition: attachment; filename="simnigeria_events.ics"');
	
	for ($i = 0; $i < ob_get_level(); $i++) {
		ob_get_clean();
	}
	ob_start();
	echo $ical_start.$ical_events.$ical_end;
	ob_end_flush();
	exit;
	die();
}