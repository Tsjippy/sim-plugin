<?php
namespace SIM\BOOKINGS;
use SIM;

class Bookings{
    function __construct(){
        global $wpdb;
		$this->tableName		= $wpdb->prefix.'sim_bookings';

        $this->forms            = new SIM\FORMS\DisplayFormResults();

        wp_enqueue_style( 'sim_bookings_style');
    }

    /**
	 * Creates the table holding all bookings if it does not exist
	*/
	function createBookingsTable(){
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
            user_details varchar(80) NOT NULL,
			PRIMARY KEY  (id)
		) $charsetCollate;";

		maybe_create_table($this->tableName, $sql );
	}

    function getNavigator($date){
        $minusMonth		= strtotime('-1 month', $date);
		$minusMonthStr	= date('m', $minusMonth);
		$minusYearStr	= date('Y', $minusMonth);
		$plusMonth		= strtotime('+2 month', $date);
		$plusMonthStr	= date('m', $plusMonth);
		$plusYearStr	= date('Y', $plusMonth);

        $hidden         = '';
        if($minusMonth < $date){
            $hidden = 'hidden';
        }
        ob_start();
        ?>
        <div class="navigator">
            <div class="prev <?php echo $hidden;?>">
                <a href="#" class="prevnext" data-month="<?php echo $minusMonthStr;?>" data-year="<?php echo $minusYearStr;?>">
                    <span><</span> <?php echo date('F', $minusMonth);?>
                </a>
            </div>
            <div class="next">
                <a href="#" class="prevnext" data-month="<?php echo $plusMonthStr;?>" data-year="<?php echo $plusYearStr;?>">
                    <?php echo date('F', $plusMonth);?> <span>></span>
                </a>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }

    /**
     * 
     * Displays a date selector modal
     */
    function dateSelectorModal($subject){
        if(defined('REST_REQUEST')){
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
		$monthStr		= date('m', $date);
		$yearStr		= date('Y', $date);

        ob_start();
        $cleanSubject    = trim(str_replace(' ', '_', $subject));

		?>
        <div name='<?php echo $cleanSubject;?>-modal' class="booking modal hidden">
			<div class="modal-content">
				<span class="close">&times;</span>
                <div class="bookings-wrap" data-date="<?php echo "$yearStr-$monthStr";?>" data-subject="<?php echo $cleanSubject;?>">
                    <h4 style='text-align:center;'><?php echo $subject;?> Calendar</h4>
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

                        <div class="instructions-wrapper">
                            <div>
                                <div class="sewcpu6 dir dir-ltr" style="--spacingBottom:0;">
                                    <div class="s1bh1tge dir dir-ltr">
                                        <div class="_uxnsba" data-testid="availability-calendar-date-range">Please select your arrival and departure date</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="booking overview">
                        <?php
                        echo $this->getNavigator($date);
                        ?>
                        <div class="calendar table">
                            <?php
                            echo $this->monthCalendar($subject, $date);
                            echo $this->monthCalendar($subject, strtotime('+1 month', $date));
                            ?>
                        </div>
                        <div class="actions">
                            <button class="button action reset disabled" type='button'>Reset</button>
                            <button class="button action confirm disabled" type='button'>Confirm</button>
                        </div>
                    </div>
                    <div class="booking details-wrapper">
                        <?php
                        $this->detailHtml();
                        ?>
                    </div>
                </div>
            </div>
		</div>
        <?php

        return ob_get_clean();
    }

    /**
	 * Get the month calendar
	 * 
	 * @param	string		$subject		The subject name
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
		$this->retrieveBookings($month, $year, $subject);

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
                        if(date('md', $workingDate) < date('md', $curDate)){
                            $class	.= 'unavailable';
                        }elseif(!in_array($workingDateStr, $this->unavailable)){
                            $class	.= 'available';
                        }else{
                            $class	.= 'selected';
                        }
                        
                        $calendarRows .=  "<dt class='calendar day $class' data-date='".date('d-m-Y', $workingDate)."' data-isodate='".date('Y-m-d', $workingDate)."'>";
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
			if($workingMonth > date('m', $date)){
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
    function detailHtml(){
        $baseUrl	= plugins_url('../pictures', __DIR__);

        foreach($this->bookings as $booking){
            // Retrieve booking details
            $this->forms->getSubmissionData(null, $booking->submission_id);
            $this->forms->submissionData;

            $booker     = maybe_unserialize($booking->user_details);

            // Booker is an user
            if(is_numeric($booker)){
                $user   = get_userdata($booker);
                $url	= SIM\maybeGetUserPageUrl($booker);
                $email	= $user->user_email;
                $tel	= get_user_meta($booker, 'phonenumbers', true);
    
                $name	= "<a href='$url'>{$user->display_name}</a><br>";
                $email	= "<a href='mailto:$email'>$email</a>";
                
                $phone  = '';
                if(is_array($tel)){
                    foreach($tel as $p){
                        $phone	.="<a href='https://signal.me/#p/$p'>$p</a><br>";
                    }
                }
            }else{
                $name  = '';
                if(!empty($booker['name'])){
                    $name  = $booker['name'];
                }

                $email  = '';
                if(!empty($booker['email'])){
                    $email  = $booker['email'];
                }

                $phone  = '';
                if(!empty($booker['phone'])){
                    $phone  = $booker['phone'];
                }
            }

            $adults = '';
            $kids   = '';
            if(is_numeric($this->forms->submissionData->adultcount)){
                $adults = $this->forms->submissionData->adultcount;
            }
            if(is_numeric($this->forms->submissionData->childcount)){
                $kids   = $this->forms->submissionData->childcount;
            }

            ?>
            <div class='booking-details-wrapper hidden' data-id='<?php echo $booking->id;?>'>
                <h6 class='booking-title'>
                    Booking details
                </h6>

                <article class='booking'>
                    <h4 class='booking-title'><?php echo $name;?></h4>
                    <div class='booking-header'>
                        <div class='booking-date'>
                            <img src='<?php echo $baseUrl;?>/date.png' loading='lazy' alt='date' class='booking-icon'>
                            <?php echo date('d-m-Y', strtotime($booking->startdate)).' - '.date('d-m-Y',strtotime($booking->enddate));?>
                        </div>
                    </div>
                    <div class='booking-detail'>
                        <div class='email'>
                            <img src='<?php echo $baseUrl;?>/email.png' loading='lazy' alt='email' class='booking-icon'>
                            <?php echo $email;?>
                        </div>
                        
                        <div class='phone'>
                            
                            <img src='<?php echo $baseUrl;?>/phone.png' alt='phone' loading='lazy' class='booking-icon'>
                            <div>
                                <?php echo $phone;?>
                            </div>
                        </div>

                        <div class='adults'>
                            <img src='<?php echo $baseUrl;?>/adults.png' alt='adults' loading='lazy' class='booking-icon'>
                            <?php echo $adults;?>
                        </div>

                        <div class='kids'>
                            <img src='<?php echo $baseUrl;?>/kids.png' alt='kids' loading='lazy' class='booking-icon'>
                            <?php echo $kids;?>
                        </div>
                    </div>
                </article>
            </div>
            <?php
        }
    }

    /**
     * Insert a new booking
     */
    function insertBooking($startdate, $enddate, $subject, $submissionId, $userDetails){
        global $wpdb;

        $wpdb->insert(
            $this->tableName, 
            array(
                'startdate'			=> $startdate,
                'enddate'			=> $enddate,
                'subject'			=> $subject,
                'submission_id'	    => $submissionId,
                'user_details'		=> $userDetails
            )
        );
    }

    /**
     * Retrieve the bookings for a certain month
     * 
     * @param   int     $month          The month to retrieve bookings for
     * @param   int     $year           The year to retrieve bookings for
     * @param   string  $subject    The subject to retrieve bookings for
     * 
     */
    function retrieveBookings($month, $year, $subject){
        global $wpdb;

        $subject    = trim(str_replace(' ', '_', $subject));

		//select all bookings of this month
        $startDate  = "$year-$month-01";
        $endDate    = date("Y-m-t", strtotime($startDate));
		$query	    = "SELECT * FROM $this->tableName WHERE (`startdate` >= '$startDate' OR '$startDate' BETWEEN startdate and enddate) AND `startdate` <= '$endDate' AND subject = '$subject' ";

        //sort on startdate
		$query	.= " ORDER BY `startdate`, `starttime` ASC";

		$this->bookings 	=  $wpdb->get_results($query);

        $this->unavailable  = [];

        foreach($this->bookings as $booking){

            $current    = strtotime($booking->startdate);
            $last       = strtotime($booking->enddate);

            while( $current <= $last ) {
                $this->unavailable[]    = date('Y-m-d', $current);
                $current                = strtotime('+1 day', $current);
            }
        }
    }
}