<?php
namespace SIM\BOOKINGS;
use SIM;

// Add a new type to the element choice dropdown
add_filter('sim-special-form-elements', function($options){
    $options['booking_selector']    = 'Booking selector';

    return $options;
});

// add element options
add_action('sim-after-formbuilder-element-options', function($element){
    $bookingDetails = [];
    if($element != null && isset($element->booking_details)){
        $bookingDetails = maybe_unserialize($element->booking_details);
    }
    ?>
    <div class='elementoption booking_selector hidden'>
        <label>
            Specify the subjects to show a calendar for
            <textarea class="formbuilder" name="formfield[booking_details][subjects]"><?php 
                if(isset($bookingDetails['subjects'])){echo trim($bookingDetails['subjects']);}
            ?></textarea>
        </label>
        <br>
        <label>
            <input type='checkbox' name='formfield[booking_details][oneday]' value='yes' <?php if(isset($bookingDetails['oneday']) && $bookingDetails['oneday'] == 'yes'){echo 'checked';}?>>
            Allow one day events
        </label>
        <br>
    </div>
    <?php
});

// add extra elements for displaying in results table
add_filter('sim-forms-elements', function($elements, $displayFormResults){
    // Check if it has an booking selector
    $hasBookingSelector = false;

    foreach($elements as $element){
        if($element->type == 'booking_selector'){
            $hasBookingSelector = true;
            break;
        }
    }

    if($hasBookingSelector){
        // Add the startdate and enddate
        $startdate          = clone $element;
        $startdate->type    = 'date';
        $startdate->name    = 'booking-startdate';
        $startdate->nicename= 'booking-startdate';
        $startdate->id      = -102;
        $enddate            = clone $element;
        $enddate->type      = 'date';
        $enddate->name      = 'booking-enddate';
        $enddate->nicename  = 'booking-enddate';
        $enddate->id        = -103;
        
        $elements[]         = $startdate;
        $elements[]         = $enddate;
    }
    return $elements;
}, 10, 2);

// Display the date selector in the form
add_filter('sim-forms-element-html', function($html, $element, $formBuilderForm){
    if($element->type == 'booking_selector'){
        $bookingDetails = maybe_unserialize($element->booking_details);

        if(!isset($bookingDetails['subjects'])){
            return 'Please add one or more subjects';
        }else{
            $subjects       = explode("\n", $bookingDetails['subjects']);
        }

        $html   = '';

         $hidden     = 'hidden';
        $buttonText = 'Change';
        $required   = '';
        if($element->required){
            $required   = 'required';
        }

        if(count($subjects) == 1){
            $hidden     = "";
            $buttonText = 'Select dates';
        }elseif(strlen($bookingDetails['subjects']) < 60){
            foreach($subjects as $subject){
                $cleanSubject    = trim(str_replace(' ', '_', $subject));
                $html   .= "<label>";
                    $html   .= "<input type='radio' class='booking-subject-selector' name='$element->name' value='$cleanSubject'>";
                    $html   .= "$subject";
                $html   .= "</label>";
            }
        }else{
            $html   .= "<select class='booking-subject-selector' name='$element->name' $required>";
                foreach($subjects as $subject){
                    $cleanSubject    = trim(str_replace(' ', '_', $subject));
                    $html   .= "<option value='$cleanSubject'>$subject</option>";
                }
            $html   .= "</select>";
        }

        $html   .= "<div class='selected-booking-dates $hidden'>";
            $html   .= "<div>";
                $html   .= "<h4>Arrival Date</h4>";
                $html   .= "<input type='date' name='booking-startdate' disabled $required>";
            $html   .= "</div>";
            $html   .= "<div>";
                $html   .= "<h4>Departure Date</h4>";
                $html   .= "<input type='date' name='booking-enddate' disabled $required>";
            $html   .= "</div>";
            $html   .= "<button class='button change-booking-date' type='button'>$buttonText</button>";
        $html   .= "</div>"; 

        wp_enqueue_script('sim-bookings');

        $booking   = new Bookings();

        // Find the accomodation names
        foreach($subjects as $subject){
            $html   .= $booking->dateSelectorModal($subject);
        }
    }  
    return $html;
}, 10, 3); 

// the choice for table view or calendar view
add_action('sim-formstable-after-table-settings', function($displayFormResults){
    // Check if it has an booking selector
    if(empty($displayFormResults->getElementByType('booking_selector'))){
        return;
    }

    $setting    = '';
    if(isset($displayFormResults->tableSettings['booking-display'])){
        $setting    = $displayFormResults->tableSettings['booking-display'];
    }

    ?>
    <div class="table_rights_wrapper">
        <label>
            Select if you want to see the bookings as table or as calendar
        </label>
        <br>
        <label>
            <input type='radio' name='table_settings[booking-display]' value='table' <?php if($setting == 'table'){echo 'checked';}?>>
            Table
        </label>
        <label>
            <input type='radio' name='table_settings[booking-display]' value='calendar'<?php if($setting == 'calendar'){echo 'checked';}?>>
            Calendar
        </label>
    </div>
    <?php
});

// Display calendar instead of a table
add_filter('sim-formstable-should-show', function($shouldShow, $displayFormResults){
    // display the calendar instead of the table
    if(
        !isset($displayFormResults->tableSettings['booking-display'])   ||
        (
            isset($displayFormResults->tableSettings['booking-display']) && 
            $displayFormResults->tableSettings['booking-display'] != 'calendar'
        ) ||
        !array_intersect($displayFormResults->userRoles, array_keys($displayFormResults->formSettings['full_right_roles']))
    ){
        return $shouldShow;
    }
    
    wp_enqueue_script('sim-bookings');

    $booking   = new Bookings($displayFormResults);

    $indexes        = $displayFormResults->getElementByType('booking_selector');

    foreach($indexes as $index){
        $element    = $displayFormResults->formElements[$index];

        $bookingDetails = maybe_unserialize($element->booking_details);

        if(!isset($bookingDetails['subjects'])){
            return 'Please add one or more booking subjects';
        }else{
            $subjects       = explode("\n", $bookingDetails['subjects']);
        }
    }

    $html       = '<div class="tables-wrapper">';
        $html       .= '<div class="form-data-table" data-formid="20" data-shortcodeid="4">';
            $checkboxes = '<h4>Please select the accomodation you want to see the calendar for</h4>';
            // Find the accomodation names
            foreach($subjects as $subject){
                $cleanSubject   = trim(str_replace(' ', '_', $subject));
                $checkboxes .= "<label>";
                    $checkboxes .= "<input type='checkbox' class='admin-booking-subject-selector' value='$cleanSubject'>";
                    $checkboxes .= $subject;
                $checkboxes .= "</label>";
                $html       .= $booking->modalContent($subject, time(), true, true);
            }
        $html   .= '</div>';
    $html   .= '</div>';

    return $checkboxes.$html;
    
    
}, 10, 2);

// Create a booking
add_action('sim_after_saving_formdata', function($formBuilder){
    if(isset($formBuilder->formResults['booking-startdate'])){
        // find the subject
        $indexes        = $formBuilder->getElementByType('booking_selector');

        $bookings       = new Bookings();

        foreach($indexes as $index){
            $elementName    = $formBuilder->formElements[$index]->name;
            $result = $bookings->insertBooking($formBuilder->formResults['booking-startdate'], $formBuilder->formResults['booking-enddate'], $formBuilder->formResults[$elementName], $formBuilder->formResults['id']);

            if(is_wp_error($result)){
                return $result;
            }
        }
    }
}, 10, 3);


// Update an existing booking
add_action('sim-forms-submission-updated', function($formTable, $fieldName, $newValue){
    global $wpdb;

    $bookings   =  new Bookings();
    $booking    = $bookings->getBookingBySubmission($formTable->formResults['id']);

    // Get the subject element name
    $query      = "SELECT name FROM `wp_sim_form_elements` WHERE `form_id`=(select form_id from {$bookings->forms->submissionTableName} WHERE id=$booking->submission_id) AND `type`='booking_selector'";
    $subject    = $wpdb->get_var($query);

    $fieldName  = str_replace('booking-', '', $fieldName);
    if(!in_array($fieldName, ['startdate', 'enddate', 'startime', 'endtime', $subject])){
        return;
    }

    // update the subject column
    if($subject == $fieldName){
        $fieldName  = 'subject';
    }

    $bookings->updateBooking($booking->id, [$fieldName => $newValue]);
}, 10, 3);

// add a min and a max to booking dates on edit
add_filter('sim-forms-element-html', function($html, $element, $displayFormResults){
    global $wpdb;

    if($element->name == 'booking-enddate'){
        // Get the subject
        $subject    = $displayFormResults->formResults[$displayFormResults->formElements[$displayFormResults->getElementByType('booking_selector')[0]]->name];
        
        // get the first event after this one
        $query  = "SELECT startdate FROM {$wpdb->prefix}sim_bookings WHERE subject = '$subject' AND startdate > '{$displayFormResults->formResults[$element->name]}' ORDER BY startdate LIMIT 1";
        $max    = $wpdb->get_var($query);

        if(!empty($max)){
            $max    = "max='$max'";
        }

        return str_replace('>', "min='{$displayFormResults->formResults['booking-startdate']}' $max>", $html);
    }elseif($element->name == 'booking-startdate'){
        // Get the subject
        $subject    = $displayFormResults->formResults[$displayFormResults->formElements[$displayFormResults->getElementByType('booking_selector')[0]]->name];

        // get the first event before this one
        $query  = "SELECT enddate FROM {$wpdb->prefix}sim_bookings WHERE subject = '$subject' AND enddate <= '{$displayFormResults->formResults[$element->name]}' ORDER BY enddate LIMIT 1";
        $min    = $wpdb->get_var($query);

        if(!empty($min)){
            $min    = "min='$min'";
        }

        return str_replace('>', "$min max='{$displayFormResults->formResults['booking-enddate']}'>", $html);
    }
    return $html;
}, 10, 3);