<?php
namespace SIM\BOOKINGS;
use SIM;
use SIM\EVENTS;
use SIM\FORMS;

class Bookings{
    public $tableName;
    public $bookings;
    public $forms;
    public $unavailable;
    public $showArchived;
    public $tableEditPermissions;
    public $user;
    public $userRoles;

    public function __construct($displayFormResults=''){
        global $wpdb;
		$this->tableName		= $wpdb->prefix.'sim_bookings';
        $this->bookings         = [];

        if(getType($displayFormResults) == 'object'){
            $this->forms        = $displayFormResults;
        }else{
            $this->forms        = new SIM\FORMS\DisplayFormResults();
        }

        wp_enqueue_style( 'sim_bookings_style');
    }

    /**
	 * Creates the table holding all bookings if it does not exist
	 */
	public function createBookingsTable(){
		if ( !function_exists( 'maybe_create_table' ) ) {
			require_once ABSPATH . '/wp-admin/install-helper.php';
		}

		global $wpdb;
		
		$charsetCollate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->tableName}(
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			startdate varchar(80) NOT NULL,
			enddate varchar(80) NOT NULL,
			starttime varchar(80) NOT NULL,
			endtime varchar(80) NOT NULL,
			subject varchar(80) NOT NULL,
            submission_id mediumint(9) NOT NULL,
            event_id mediumint(9),
            pending boolean DEFAULT true,
			PRIMARY KEY  (id)
		) $charsetCollate;";

		maybe_create_table($this->tableName, $sql );
	}

    public function getNavigator($date, $plus=2){
        if($plus == 2){
            $min    = 1;
        }else{
            $min    = 2;
        }
        $minusMonth		= strtotime("first day of $min months ago", $date);
		$minusMonthStr	= date('m', $minusMonth);
		$minusYearStr	= date('Y', $minusMonth);

        $firstMonth     = strtotime("first day of next month", $minusMonth);

		$plusMonth		= strtotime("first day of $plus months", $date);
		$plusMonthStr	= date('m', $plusMonth);
		$plusYearStr	= date('Y', $plusMonth);

        $hidden         = '';
        if(date('ym', $minusMonth) < date('ym')){
            $hidden = 'hidden';
        }
        ob_start();
        ?>
        <div class="navigator" data-month='<?php echo date('m', $firstMonth);?>' data-year='<?php echo date('Y', $firstMonth);?>'>
            <div class="prev <?php echo $hidden;?>">
                <a class="prevnext" data-month="<?php echo $minusMonthStr;?>" data-year="<?php echo $minusYearStr;?>">
                    <span><</span> <?php echo date('F', $minusMonth);?>
                </a>
            </div>
            <div class="next">
                <a class="prevnext" data-month="<?php echo $plusMonthStr;?>" data-year="<?php echo $plusYearStr;?>">
                    <?php echo date('F', $plusMonth);?> <span>></span>
                </a>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }

    /**
     * Displays the booking calendars
     * @param   string      $subject    The subject of the calendar
     * @param   int         $date       The date to retrieve the calendar for
     * @param   boolean     $isAdmin    Wheter to show for admin puposes
     * @param   boolean     $hidden     Wheter to hide the calendar by default
     *
     * @return  string                  The html
     */
    public function modalContent($subject, $date, $isAdmin = false, $hidden = false){
		$monthStr		= date('m', $date);
		$yearStr		= date('Y', $date);
        $cleanSubject   = trim(str_replace(' ', '_', $subject));

        ob_start();

        ?>
        <div class="bookings-wrap <?php if($hidden){echo 'hidden';}?>" data-date="<?php echo "$yearStr-$monthStr";?>" data-subject="<?php echo $cleanSubject;?>" data-shortcodeid="<?php echo $this->forms->shortcodeId;?>">
            <div class="booking overview">
                <div class='header mobile-sticky'>
                    <h4 style='text-align:center;'><?php echo $subject;?> Calendar</h4>
                
                    <?php
                    if(!$isAdmin){
                        echo $this->showSelectedModalDates();
                    }
                    ?>
                    <div class="navigators">
                        <?php
                        echo $this->getNavigator($date);
                        ?>
                    </div>
                </div>
                <div class="calendar table">
                    <?php
                    echo $this->monthCalendar($subject, $date);
                    echo $this->monthCalendar($subject, strtotime('first day of next month', $date));
                    ?>
                </div>
                <?php
                if(!$isAdmin){
                    ?>
                    <div class="actions mobile-sticky bottom">
                        <button class="button action reset disabled" type='button'>Reset</button>
                        <button class="button action confirm disabled" type='button'>Confirm</button>
                    </div>
                    <?php
                }
                ?>
            </div>
            <?php
            if($isAdmin){
                ?>
                <div class="booking details-wrapper">
                    <?php
                    echo $this->detailHtml();
                    ?>
                </div>
                <?php
            }
            ?>
        </div>
        
        <?php
        return ob_get_clean();
    }

    /**
     * Displays the selected dates
     */
    protected function showSelectedModalDates(){
        ob_start();

        ?>
        <div class="booking-date-wrapper">
            <div class="booking-dates-input-wrapper">
                <div class="_h0i9fjw">
                    <div class="booking-date-label-wrapper">
                        <label class="booking-date-label" for="booking-startdate">
                            <div class="booking-date-label-text">Arrival</div>
                            <div dir="ltr">
                                <div class="booking-date-label-input-wrapper">
                                    <input class="booking-date-label-input booking-startdate" placeholder="Select a date" type="text" value="" disabled>
                                </div>
                            </div>
                        </label>
                    </div>
                    <div></div>
                    <div class="booking-date-label-wrapper disabled enddate">
                        <label class="booking-date-label" for="booking-enddate">
                            <div class="booking-date-label-text">Departure</div>
                            <div dir="ltr">
                                <div class="booking-date-label-input-wrapper">
                                    <input class="booking-date-label-input booking-enddate" placeholder="Select a date" type="text" value="" disabled>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="instructions-wrapper mobile-hidden">
                <div>
                    <div class="sewcpu6 dir dir-ltr" style="--spacingBottom:0;">
                        <div class="s1bh1tge dir dir-ltr">
                            <div class="_uxnsba" data-testid="availability-calendar-date-range">Please select your arrival and departure date</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php

        return ob_get_clean();
    }

    /**
     *
     * Displays a date selector modal
     */
    public function dateSelectorModal($subject){
        if(defined('REST_REQUEST') && isset($_POST['month']) && isset($_POST['year'])){
			$month		= $_POST['month'];
			$year		= $_POST['year'];
			$dateStr	= "$year-$month-01";
		}else{
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

        $date			= strtotime($dateStr);

        ob_start();
        $cleanSubject    = trim(str_replace(' ', '_', $subject));

		?>
        <div name='<?php echo $cleanSubject;?>-modal' class="booking modal hidden">
			<div class="modal-content">
				<span class="close mobile-sticky">&times;</span>
                <?php echo $this->modalContent($subject, $date);?>
            </div>
		</div>
        <?php

        return ob_get_clean();
    }

    /**
	 * Get the month calendar
	 *
	 * @param	string		$subject		The subject name
     * @param   int         $date           The time
	 *
	 * @return	string				        Html of the calendar
	 */
	public function monthCalendar($subject, $date){
		
		ob_start();
		$curDate        = time();
        $month          = date('m', $date);
        $year           = date('Y', $date);
		$weekDay		= date("w", strtotime(date('Y-m-01', $date)));
		$workingDate	= strtotime("-$weekDay day", strtotime(date('Y-m-01', $date)));
		$calendarRows	= '';

        //get the bookings for this month
		$this->retrieveMonthBookings($month, $year, $subject);

		//loop over all weeks of a month
		while(true){
            $hidden         = '';
            if($month != date('m', $date)){
                $hidden = 'hidden';
            }

			$calendarRows .= "<dl class='calendar row $hidden' data-month='$month'>";
                //loop over all days of a week
                while(true){
                    $workingDateStr		= date('Y-m-d', $workingDate);
                    $workingMonth	    = date('m', $workingDate);
                    $workingDay			= date('j', $workingDate);

                    $class              = '';

                    if($workingMonth != $month){
                        $calendarRows .=  "<dt class='empty'></dt>";
                    }else{
                        $data   = '';
                        // date is in the past
                        if(date('Ymd', $workingDate) < date('Ymd', $curDate)){
                            $class	.= 'unavailable';
                        // not booked
                        }elseif(!isset($this->unavailable[$workingDateStr])){
                            $class	.= 'available';
                        // booked
                        }else{
                            // First and last day of a reservation are both booked and available
                            /* if(
                                !isset($this->unavailable[date('Y-m-d', strtotime('-1 day', $workingDate))])    ||
                                !isset($this->unavailable[date('Y-m-d', strtotime('+1 day', $workingDate))])
                            ){
                                $class	.= 'available ';
                            } */
                            $class	.= 'booked';
                            $data   .= "data-bookingid='{$this->unavailable[$workingDateStr]}'";
                        }
                        
                        $calendarRows .=  "<dt class='calendar day $class' data-date='".date(DATEFORMAT, $workingDate)."' data-isodate='".date('Y-m-d', $workingDate)."' $data>";
                            $calendarRows	.= "<span class='day-nr'>$workingDay</span>";
                        $calendarRows	.= "</dt>";
                    }
                    
                    //calculate the next week
                    $workingDate	= strtotime('+1 day', $workingDate);
                    //if the next day is the first day of a new week
                    if(date('w', $workingDate) == 0){
                        break;
                    }
                }
			$calendarRows .= '</dl>';

			// Break if next month
			if(date('Ym', $workingDate) > date('Ym', $date)){
				break;
			}
		}

        ?>
        <div class="month-container" data-month='<?php echo date('m', $date);?>' data-year='<?php echo date('Y', $date);?>'>
            <div class="current">
                <?php echo date('F Y', $date);?>
            </div>
            <dl>
                <?php
                $workingDate	= strtotime("-$weekDay day", strtotime(date('Y-m-01', $date)));
                for ($y = 0; $y <= 6; $y++) {
                    $name	= date('D', $workingDate);
                    echo "<dt class='calendar day head'>$name</dt>";
                    $workingDate	= strtotime("+1 days", $workingDate);
                }
                ?>
            </dl>
            <?php
            echo $calendarRows;
            ?>
        </div>

        <?php

		return ob_get_clean();
	}

    /**
     * Build the detail html for the current month
     */
    public function detailHtml(){
        $baseUrl	= plugins_url('../pictures', __DIR__);

        if($this->forms->columnSettings == null){
            $this->forms->loadShortcodeData();
        }

        ob_start();

        foreach($this->bookings as $booking){
            // Retrieve booking details
            $this->forms->parseSubmissions(null, $booking->submission_id);
            $bookingData    = $this->forms->submission->formresults;

            ?>
            <div class='booking-detail-wrapper hidden' data-bookingid='<?php echo $booking->id;?>'>
                <h6 class='booking-title'>
                    Booking details
                </h6>

                <article class='booking'>
                    <h4 class='booking-title'><?php echo $bookingData['name'];?></h4>
                    <div class='booking-detail'>
                        <table data-formid='<?php echo $this->forms->submission->formresults['formid'];?>' style='width: unset;'>
                            <thead></thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <img src='<?php echo $baseUrl;?>/date.png' loading='lazy' alt='date' class='booking-icon'>
                                    </td>
                                    <td class='booking-data-wrapper edit_forms_table'>
                                        <table data-formid='<?php echo $this->forms->submission->formresults['formid'];?>' style='margin-bottom: 0px; width:unset;'>
                                            <tr data-id='<?php echo $this->forms->submission->formresults['id'];?>'>
                                                <td data-name='booking-startdate' data-id='<?php echo $this->forms->getElementByName('booking-startdate')->id;?>' data-oldvalue='<?php echo json_encode($booking->startdate);?>' class='edit_forms_table'>
                                                    <?php echo date('d-M-Y', strtotime($booking->startdate));?>
                                                </td>
                                            </tr>
                                            <tr data-id='<?php echo $this->forms->submission->formresults['id'];?>'>
                                                <td data-name='booking-enddate' data-id='<?php echo  $this->forms->getElementByName('booking-enddate')->id;?>' data-oldvalue='<?php echo json_encode($booking->enddate);?>' class='edit_forms_table'>
                                                    <?php echo date('d-M-Y', strtotime($booking->enddate));?>
                                                </td>
                                            </tr>
                                        </table>
                                        
                                    </td>
                                </tr>
                                <?php
                                    foreach($this->forms->columnSettings as $key=>$setting){
                                        if($setting['show'] == 'hide' || !is_numeric($key) || in_array($setting['name'], ['formid', 'formurl', '_wpnonce', 'id', 'submissiontime', 'edittime', 'booking-startdate', 'booking-enddate', 'name'])){
                                            continue;
                                        }

                                        $index  = $setting['name'];
                                        $data   = $bookingData[$index];
                                        $transformedData   = $this->forms->transformInputData($data, $index, $bookingData);
                                        if(empty($transformedData)){
                                            $transformedData    = 'X';
                                        }
                                        echo "<tr class='$index' data-id='{$this->forms->submission->formresults['id']}'>";
                                            if(file_exists(SIM\urlToPath("$baseUrl/$index.png"))){
                                                echo "<td><img src='$baseUrl/$index.png' loading='lazy' alt='{$setting['nice_name']}' class='booking-icon'></td>";
                                            }else{
                                                echo "<td>{$setting['nice_name']}:</td>";
                                            }
                                            echo "<td class='booking-data-wrapper edit_forms_table' data-id='$index' data-oldvalue='".json_encode($data)."'>";
                                                echo $transformedData;
                                            echo "</td>";
                                        echo "</tr>";
                                    }

                                    //if there are actions
                                    if(!empty($this->forms->formData->settings['actions'])){
                                        //loop over all the actions
                                        $buttonsHtml	= [];
                                        $buttons		= '';
                                        foreach($this->forms->formData->settings['actions'] as $action){
                                            if($action == 'archive' && $this->showArchived == 'true' && $this->forms->submissions->archived){
                                                $action = 'unarchive';
                                            }
                                            $buttonsHtml[$action]	= "<button class='$action button forms_table_action' name='{$action}_action' value='$action'>".ucfirst($action)."</button>";
                                        }
                                        $buttonsHtml = apply_filters('sim_form_actions_html', $buttonsHtml, $bookingData, $index, $this, $this->forms->submission);
                                        
                                        //we have te html now, check for which one we have permission
                                        foreach($buttonsHtml as $action=>$button){
                                            if(
                                                $this->tableEditPermissions || 																			//if we are allowed to do all actions
                                                $bookingData['userid'] == $this->user->ID || 															//or this is our own entry
                                                array_intersect($this->userRoles, (array)$this->forms->columnSettings[$action]['edit_right_roles'])		//or we have permission for this specific button
                                            ){
                                                $buttons .= $button;
                                            }
                                        }
                                        if(!empty($buttons)){
                                            echo "<tr class='actions' data-id='{$this->forms->submission->formresults['id']}'>";
                                                echo "<td  colspan='2'>$buttons</td>";
                                            echo "</tr>";
                                        }
                                    }
                                    ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>
            <?php
        }

        return ob_get_clean();
    }

    /**
     * Check if a booking overlaps another booking
     */
    public function checkOverlap($startDate, $endDate, $subject, $id=-1){
        global $wpdb;

        // start end enddate may overlap so only check for dates in between
        //$queryStartDate  = date('Y-m-d', strtotime('+1 day', strtotime($startDate)));
        //$queryEndDate    = date('Y-m-d', strtotime('-1 day', strtotime($endDate)));

        // First check if a booking on these dates doesn't exist
        $query	    = "SELECT * FROM $this->tableName WHERE pending=0 AND subject = '$subject' AND ('$startDate' BETWEEN startdate and enddate OR '$endDate' BETWEEN startdate and enddate)";

        if($id != -1){
            $query  .= " AND NOT id=$id";
        }
        
        //sort on startdate
		$query	.= " ORDER BY `startdate`, `starttime` ASC";

		return !empty($wpdb->get_results($query));
    }

    /**
     * Insert a new booking
     *
     * @param   string      $startdate      The startdate string
     * @param   string      $enddate        The enddate string
     * @param   string      $subject        The subject the booking is for
     * @param   int         $submissionId   The form submission id
     */
    public function insertBooking($startDate, $endDate, $subject, $submissionId){
        global $wpdb;

		if($this->checkOverlap($startDate, $endDate, $subject)){
            return new \WP_Error('booking', 'This booking overlaps with an existing one, try again');
        }

        $userId     = $this->forms->submission->formresults['user_id'];

        // create a personal event
        if(!empty($userId)){
            $post = array(
                'post_type'		=> 'event',
                'post_title'    => "Booking for $subject",
                'post_content'  => "Booking for $subject",
                'post_status'   => 'publish',
                'post_author'   => $userId
            );

            $eventId 	= wp_insert_post( $post, true, false);

            $event							= [];
            $event['startdate']				= $startDate;
            $event['starttime']				= '14:00';
            $event['enddate']				= $endDate;
            $event['endtime']				= '12:00';
            $event['location']				= $subject;
            $event['organizer_id']			= $userId;
            $event['onlyfor']               = $userId;
            update_post_meta($eventId, 'eventdetails', json_encode($event));
            update_post_meta($eventId, 'onlyfor', $userId);
        }

        // insert the booking
        $pending    = false;

        $user       = false;
        if(is_numeric($userId)){
            $user       = get_userdata($userId);
        }

        $submittingUser       = get_userdata($this->forms->submission->userid);

        if(
            isset($this->forms->formData->settings['default-booking-state'])            &&
            $this->forms->formData->settings['default-booking-state']   == 'pending'    &&
            (
                !$user  ||          // No user found
                !array_intersect($user->roles, array_keys($this->forms->formData->settings['confirmed-booking-roles'])) // or not allowed
            )   &&
            (
                !$submittingUser  ||          // No user found
                !array_intersect($submittingUser->roles, array_keys($this->forms->formData->settings['confirmed-booking-roles'])) // or not allowed
            )
        ){
            $pending    = true;
        }

        // Insert booking in db
        $wpdb->insert(
            $this->tableName,
            array(
                'startdate'			=> $startDate,
                'enddate'			=> $endDate,
                'subject'			=> $subject,
                'submission_id'	    => $submissionId,
                'event_id'          => $eventId,
                'pending'           => $pending
            )
        );
    }

    /**
     * Update an existing booking
     *
     * @param   int     $bookingId  The booking id
     * @param   array   $values     The values to update
     */
    public function updateBooking($booking, $values){
        global $wpdb;

        // Get the booking
        if(is_numeric($booking)){
            $booking        = $this->getBookingById($booking);
        }

        // only keep valid values
        $values         = array_filter( $values, function($val){return in_array($val, ['startdate', 'enddate', 'starttime', 'endtime', 'subject', 'pending']);}, ARRAY_FILTER_USE_KEY);

        $startdate      = $booking->startdate;
        if(isset($values['startdate'])){
            $startdate  = $values['startdate'];
        }
        $enddate      = $booking->enddate;
        if(isset($values['enddate'])){
            $enddate  = $values['enddate'];
        }
        $subject      = $booking->subject;
        if(isset($values['subject'])){
            $subject  = $values['subject'];
        }
        
        if($this->checkOverlap($startdate, $enddate, $subject, $booking->id)){
            return new \WP_Error('booking', 'This booking overlaps with an existing one, try again');
        }

        // update booking
        $wpdb->update(
            $this->tableName,
            $values,
            array(
                'id'		=> $booking->id
            ),
        );

        // update event
        $event                          = json_decode(get_post_meta($booking->event_id, 'eventdetails', true), true);
        update_post_meta($booking->event_id, 'eventdetails', json_encode(array_merge($event, $values)));

        $monthsHtml     = [];
        $months         = [];
        $years          = [];
        $details        = '';

        // Get all the months
        $start    = (new \DateTime($booking->startdate))->modify('first day of this month');
        $end      = (new \DateTime($booking->enddate))->modify('first day of next month');
        $interval = \DateInterval::createFromDateString('1 month');
        $period   = new \DatePeriod($start, $interval, $end);

        foreach ($period as $dt) {
            $monthsHtml[]   = $this->monthCalendar($booking->subject, $dt->format("U"));
            $months[]       = $dt->format("m");
            $years[]        = $dt->format("Y");

            $details        .= $this->detailHtml();
        }

        return [
            'months'        => $months,
            'years'         => $years,
            'subject'       => $booking->subject,
            'html'          => $monthsHtml,
            'details'       => $details
        ];
    }

    /**
     * Update an existing booking
     *
     * @param   int|object     $booking  The booking or booking id
     */
    public function removeBooking($booking){
        global $wpdb;

        // Get the booking
        if(is_numeric($booking)){
            $booking        = $this->getBookingById($booking);
        }

        // Remove the event
        $events = new SIM\EVENTS\CreateEvents();
        $events->removeDbRows($booking->event_id, true);

        // Remove the booking
        $wpdb->delete(
			$this->tableName,
			['id' => $booking->id],
			['%d'],
		);
    }

    /**
     * Retrieve the bookings for a certain month
     *
     * @param   int     $month          The month to retrieve bookings for
     * @param   int     $year           The year to retrieve bookings for
     * @param   string  $subject        The subject to retrieve bookings for
     *
     */
    protected function retrieveMonthBookings($month, $year, $subject){
        global $wpdb;

        $subject    = trim(str_replace(' ', '_', $subject));

		//select all bookings of this month
        $startDate  = "$year-$month-01";
        $endDate    = date("Y-m-t", strtotime($startDate));
		$query	    = "SELECT * FROM $this->tableName WHERE (`startdate` >= '$startDate' OR '$startDate' BETWEEN startdate and enddate) AND `startdate` <= '$endDate' AND subject = '$subject' AND pending=0";

        //sort on startdate
		$query	.= " ORDER BY `startdate`, `starttime` ASC";

        $result             = $wpdb->get_results($query);
		$this->bookings 	=  array_merge($this->bookings, $result);

        $this->unavailable  = [];

        foreach($result as $booking){

            $current    = strtotime($booking->startdate);
            $last       = strtotime($booking->enddate);

            while( $current <= $last ) {
                $this->unavailable[date('Y-m-d', $current)] = $booking->id;
                $current                = strtotime('+1 day', $current);
            }
        }
    }

    /**
     * Retrieve all the pending bookings
     *
     */
    public function retrievePendingBookings(){
        global $wpdb;

        $query	    = "SELECT * FROM $this->tableName WHERE pending=1 ";

        //sort on startdate
		$query	.= " ORDER BY `startdate`, `starttime` ASC";

		return $wpdb->get_results($query);
    }

    /** Get a booking by submission id */
    protected function getBookingById($id){
        global $wpdb;

		$query	    = "SELECT * FROM $this->tableName WHERE id=$id ";

		return  $wpdb->get_results($query)[0];
    }

    /**
     * Get a booking by submission id
     *
     * @param int   $id     The submission id
     *
     * @return  object|false    The booking or false if no booking found
     * */
    public function getBookingBySubmission($id){
        global $wpdb;

		$query	    = "SELECT * FROM $this->tableName WHERE submission_id=$id";

        $results    = $wpdb->get_results($query);
        if(!empty($results)){
            return $results[0];
        }
		
        return false;
    }
}
