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
    if($element != null && !empty($element->booking_details)){
        $bookingDetails = maybe_unserialize($element->booking_details);
    }else{
        return;
    }

    if(!isset($bookingDetails['subjects'])){
        $bookingDetails['subjects'] = ['No Subjects defined yet'];
    }

    if(!is_array($bookingDetails['subjects'])){
        $bookingDetails['subjects'] = explode("\n", $bookingDetails['subjects']);
    }
    ?>
    <div class='elementoption booking_selector hidden'>
        <label>
            Specify the subjects to show a calendar for
            <div class="clone_divs_wrapper">
                <?php
                foreach($bookingDetails['subjects'] as $index=>$subject){
                    if(!is_array($subject)){
                        $subject    = [
                            'name'   => $subject,
                            'amount' => 1
                        ];
                    }
                    ?>
                    <div class="clone_div" data-divid="<?php echo $index;?>" style='display: flex;'>
                        <label name="Subject" class=" formfield formfieldlabel" style='width: auto;margin-right: 20px;'>
                            <h4 class="labeltext">Subject <?php echo $index+1;?></h4>
                            <input type="text" name="formfield[booking_details][subjects][<?php echo $index;?>][name]" id="subjects" class=" formfield formfieldinput" value="<?php echo $subject['name'];?>" placeholder="Enter subject name" style='width: unset;'>
                        </label>

                        <label name="Subject" class=" formfield formfieldlabel">
                            <h4 class="labeltext">Room numbering type <?php echo $index+1;?></h4>
                            <input type='radio' class='booking-subject-selector' name='formfield[booking_details][subjects][<?php echo $index;?>][nrtype]' value='none' <?php if($subject['nrtype'] == ''){echo 'checked';}?> onchange='this.closest(`.clone_div`).querySelector(`label.amount`).classList.add(`hidden`);this.closest(`.clone_div`).querySelector(`.rooms`).classList.add(`hidden`)'>
                            No seperate rooms
                            <input type='radio' class='booking-subject-selector' name='formfield[booking_details][subjects][<?php echo $index;?>][nrtype]' value='numbers' <?php if($subject['nrtype'] == 'numbers'){echo 'checked';}?> onchange='this.closest(`.clone_div`).querySelector(`label.amount`).classList.remove(`hidden`);this.closest(`.clone_div`).querySelector(`.rooms`).classList.add(`hidden`)'>
                            Numbers
                            <input type='radio' class='booking-subject-selector' name='formfield[booking_details][subjects][<?php echo $index;?>][nrtype]' value='letters' <?php if($subject['nrtype'] == 'letters'){echo 'checked';}?> onchange='this.closest(`.clone_div`).querySelector(`label.amount`).classList.remove(`hidden`);this.closest(`.clone_div`).querySelector(`.rooms`).classList.add(`hidden`)'>
                            Letters
                            <input type='radio' class='booking-subject-selector' name='formfield[booking_details][subjects][<?php echo $index;?>][nrtype]' value='custom' <?php if($subject['nrtype'] == 'custom'){echo 'checked';}?> onchange='this.closest(`.clone_div`).querySelector(`label.amount`).classList.add(`hidden`);this.closest(`.clone_div`).querySelector(`.rooms`).classList.remove(`hidden`)'>
                            Custom
                        </label>

                        <label class="amount formfield formfieldlabel <?php if($subject['nrtype'] == 'custom' || empty($subject['nrtype'])){echo 'hidden';}?>">
                            <h4 class="labeltext">Room amount <?php echo $index+1;?></h4>
                            <input type="number" name="formfield[booking_details][subjects][<?php echo $index;?>][amount]" id="subjects" class=" formfield formfieldinput" value="<?php echo $subject['amount'];?>" placeholder="Enter subject amount" style='width: unset;'>
                        </label>                            

                        <div class="rooms clone_divs_wrapper <?php if($subject['nrtype'] != 'custom'){echo 'hidden';}?>" style='display: inline-block;background: lightgrey;padding-bottom: 10px;padding-left: 10px;margin-right:10px'>
                            <?php
                            if(empty($subject['rooms'])){
                                $subject['rooms']   = ['1'];
                            }

                            foreach($subject['rooms'] as $i=>$room){
                                ?>
                                <div class="clone_div" data-divid="<?php echo $i;?>" style='display: flex;'>
                                    <label name="roomname" class=" formfield formfieldlabel">
                                        <h4 class="labeltext">Room name <?php echo $i+1;?></h4>
                                        <input type="text" name="formfield[booking_details][subjects][<?php echo $index;?>][rooms][<?php echo $i;?>]" id="rooms" class=" formfield formfieldinput" value="<?php echo $room;?>" placeholder="Enter room name" style='width: unset;'>
                                    </label>
                                    
                                    <div class="buttonwrapper" style="width:100%; display: flex;">
                                        <button type="button" class="add button" style="flex: 1;">+</button>
                                        <?php
                                        if(count($subject['rooms'])> 1){
                                            ?>
                                            <button type="button" class="remove button" style="flex: 1;">-</button>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                            
                        <div class="buttonwrapper" style="display: flex;">
                            <button type="button" class="add button" style="flex: 1;">+</button>
                            <?php
                            if(count($bookingDetails['subjects'])> 1){
                                ?>
                                <button type="button" class="remove button" style="flex: 1;">-</button>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
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
add_filter('sim-forms-elements', function($elements, $displayFormResults, $force){
    if(!$force && !in_array(get_class($displayFormResults), ["SIM\FORMS\DisplayFormResults", "SIM\FORMS\SubmitForm", "SIM\FORMS\EditFormResults"])){
        return $elements;
    }

    // do not add to the formbuilder screen
    if(str_contains($_SERVER['QUERY_STRING'], 'formbuilder=true')){
        return $elements;
    }

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

        $room               = clone $element;
        $room->type         = 'checkbox';
        $room->name         = 'booking-room';
        $room->nicename     = 'booking-room';
        $room->id           = -104;
        
        $elements[]         = $startdate;
        $elements[]         = $enddate;
        $elements[]         = $room;
    }
    return $elements;
}, 10, 3);

// Display the date selector in the form
add_filter('sim-forms-element-html', function($html, $element, $displayForm){
    if($element->type == 'booking_selector'){
        $bookingDetails = maybe_unserialize($element->booking_details);

        if(!isset($bookingDetails['subjects'])){
            return 'Please add one or more subjects';
        }else{
            $subjects       = $bookingDetails['subjects'];
        }

        $html   = '';
        $hidden     = 'hidden';
        $buttonText = 'Change';
        $required   = '';
        if($element->required){
            $required   = 'required';
        }

        if(empty($subjects)){
            $hidden     = "";
            $buttonText = 'Select dates';
        }elseif(count($subjects) < 6){
            foreach($subjects as $subject){
                $cleanSubject    = trim($subject['name']);
                $checked    = '';
                if(isset($displayForm->submission->formresults['accomodation']) && $displayForm->submission->formresults['accomodation'] == $cleanSubject){
                    $checked    = 'checked';
                }
                $html   .= "<label style='margin-right:5px;'>";
                    $html   .= "<input type='radio' class='booking-subject-selector' name='$element->name' value='$cleanSubject' $checked>";
                    $html   .= "$cleanSubject";
                $html   .= "</label>";
            }
        }else{
            $html   .= "<select class='booking-subject-selector' name='$element->name' $required>";
                foreach($subjects as $subject){
                    $cleanSubject    = trim($subject['name']);
                    $html   .= "<option value='$cleanSubject'>$cleanSubject</option>";
                }
            $html   .= "</select>";
        }

        ob_start();

        ?>
        <div style='display:flex;align-items: center;'>
            <div class="clone_divs_wrapper selected-booking-dates <?php echo $hidden;?>">
                <div class="clone_div" data-divid="0">
                    <div class="buttonwrapper">
                        <div class='hidden'>
                            <h4>Room</h4>
                            <input type='text' name='booking-room[0]' disabled <?php echo $required;?>>
                        </div>
                        <div>
                            <h4>Arrival Date</h4>
                            <input type='date' name='booking-startdate[0]' disabled <?php echo $required;?>>
                        </div>
                        <div>
                            <h4>Departure Date</h4>
                            <input type='date' name='booking-enddate[0]' disabled <?php echo $required;?>>
                        </div>
                    </div>
                </div>
            </div>
            <button class='button change-booking-date hidden' type='button' style='margin-left: 20px;'><?php echo $buttonText;?></button>
        </div>
        <?php
        $html   .= ob_get_clean();

        wp_enqueue_script('sim-bookings');

        $booking   = new Bookings($displayForm);

        // Find the accomodation names
        foreach($subjects as $subject){
            $html   .= $booking->dateSelectorModal($subject);
        }
    }

    if($element->name == 'booking-room'){
        $bookings   = new Bookings($displayForm);

        $bookingDetails = maybe_unserialize($element->booking_details);

        if(!isset($bookingDetails['subjects'])){
            return 'Please add one or more subjects';
        }else{
            $subjects       = $bookingDetails['subjects'];
        }

        foreach($subjects as $subject){
            if($subject['name'] == $displayForm->submission->formresults['accomodation']){
                break;
            }
        }
        $html   .= $bookings->roomSelector($subject, false);
    }

    return $html;

}, 10, 3);

// Form settings
add_action('sim-forms-form-settings-form', function($formBuilderForm){
    if(empty($formBuilderForm->getElementByType('booking_selector'))){
        return;
    }
    global $wp_roles;
    
    //Get all available roles
    $userRoles = $wp_roles->role_names;
    
    //Sort the roles
    asort($userRoles);

    $state    = '';
    if(isset($formBuilderForm->formData->settings['default-booking-state'])){
        $state  = $formBuilderForm->formData->settings['default-booking-state'];
    }
    ?>
    <h4>Default status for new bookings</h4>
    <label>
        <input type='radio' name='settings[default-booking-state]' value='pending' <?php if($state == 'pending'){echo 'checked';}?>>
        Pending
    </label>
    <label>
        <input type='radio' name='settings[default-booking-state]' value='confimed' <?php if($state == 'confimed'){echo 'checked';}?>>
        Confimed
    </label>
    <br>
    <script>
        document.querySelectorAll('[name="settings[default-booking-state]"]').forEach(el=>{
            el.addEventListener('change', (ev) => {
                let div = document.getElementById('confirmed-roles-wrapper');
                if(ev.target.value == 'pending' && ev.target.checked){
                    div.classList.remove('hidden');
                }else{
                    div.classList.add('hidden');
                }
            });
        });
    </script>
    <div id='confirmed-roles-wrapper' class='<?php if($state != 'pending'){echo 'hidden';}?>'>
        <h4>Select roles for which bookings are confirmed by default</h4>
        <div class="role_info">
            <?php
            foreach($userRoles as $key=>$roleName){
                if(!empty($formBuilderForm->formData->settings['confirmed-booking-roles'][$key])){
                    $checked = 'checked';
                }else{
                    $checked = '';
                }
                echo "<label class='option-label'>";
                    echo "<input type='checkbox' class='formbuilder formfieldsetting' name='settings[confirmed-booking-roles][$key]' value='$roleName' $checked>";
                    echo $roleName;
                echo"</label><br>";
            }
            ?>
        </div>
    </div>
    <br>
    <?php
});

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
        !isset($displayFormResults->tableSettings['booking-display'])   ||                                                      // no option choosen
        (
            isset($displayFormResults->tableSettings['booking-display']) &&                                                     // option chosen
            $displayFormResults->tableSettings['booking-display'] != 'calendar'                                                 // but choose table view
        ) ||
        !array_intersect($displayFormResults->userRoles, array_keys($displayFormResults->formSettings['full_right_roles']))
    ){
        return $shouldShow;
    }
    
    wp_enqueue_script('sim-bookings');

    $booking    = new Bookings($displayFormResults);

    $indexes    = $displayFormResults->getElementByType('booking_selector');

    foreach($indexes as $index){
        $element    = $displayFormResults->formElements[$index];

        $bookingDetails = maybe_unserialize($element->booking_details);

        if(!isset($bookingDetails['subjects'])){
            return 'Please add one or more booking subjects';
        }else{
            $subjects       = $bookingDetails['subjects'];
        }
    }

    $pendingBookings    = $booking->retrievePendingBookings();
    $html   = '<div class="tables-wrapper">';
        if(!empty($pendingBookings)){
            $submissions    = [];
            foreach($displayFormResults->submissions as $submission){
                $submissions[$submission->id] = $submission->formresults;
            }

            $html   .= "<div class='pending-bookings-wrapper'>";
                $html   .= "<table class='sim-table' data-formid='{$booking->forms->formData->id}'>";
                    $html   .= "<thead>";
                        $html   .= "<tr>";
                            foreach($displayFormResults->columnSettings as $setting){
                                if($setting['show'] == 'hide'){
                                    break;
                                }
                                $html   .= "<th>{$setting['nice_name']}</th>";
                            }
                            $html   .= "<th>Actions</th>";
                        $html   .= "</tr>";
                    $html   .= "</thead>";

                    $html   .= "<tbody>";
                        foreach($pendingBookings as $pendingBooking){
                            $data   = $submissions[$pendingBooking->submission_id];
                            $html   .= "<tr data-id='$pendingBooking->submission_id' >";
                                foreach($displayFormResults->columnSettings as $setting){
                                    if($setting['show'] == 'hide'){
                                        break;
                                    }
                                    $cellContent    = $displayFormResults->transformInputData($data[$setting['name']], $setting['name'], $data);
                                    $html   .= "<td class='edit_forms_table' data-id='{$setting['name']}' data-oldvalue='".json_encode($data[$setting['name']])."'>$cellContent</td>";
                                }
                                $html   .= "<td>";
                                    $html   .= "<button class='button approve' type='button' data-id='$pendingBooking->id'>Approve</button>";
                                    $html   .= "<button class='button delete' type='button' data-id='$pendingBooking->id'>Delete</button><br>";
                                $html   .= "</td>";
                            $html   .= "</tr>";
                        }
                    $html   .= "</tbody>";
                $html   .= "</table>";
            $html   .= "</div>";
        }

        $calendars  = '';
        $checkboxes = '<h4>Please select the accomodation you want to see the calendar for</h4>';
        // Find the accomodation names
        foreach($subjects as $subject){
            $booking->bookings  = [];   // reset the bookings so they do not include the previous location

            $cleanSubject   = trim($subject['name']);
            $checkboxes .= "<label>";
                $checkboxes .= "<input type='checkbox' class='admin-booking-subject-selector' value='$cleanSubject'>";
                $checkboxes .= $cleanSubject;
            $checkboxes .= "</label>";

            $booking->forms->submission = null;
            $calendars  .= $booking->modalContent($subject, time(), true, true, true);
        }
        $html   .= '<div class="form-data-table">';
            $html   .= $checkboxes;
            $html   .= $calendars;
        $html   .= "</div>";
    $html   .= '</div>';

    return $html;
    
    
}, 10, 2);

// Create a booking
add_filter('sim_after_saving_formdata', function($message, $formBuilder){
    if(isset($formBuilder->submission->formresults['booking-startdate'])){
        // find the subject
        $indexes        = $formBuilder->getElementByType('booking_selector');

        $bookings       = new Bookings($formBuilder);

        foreach($indexes as $index){
            $elementName    = $formBuilder->formElements[$index]->name;
            $startDate      = $formBuilder->submission->formresults['booking-startdate'];
            $endDate        = $formBuilder->submission->formresults['booking-enddate'];
            $subject        = $formBuilder->submission->formresults[$elementName];
            $submissionId   = $formBuilder->submission->formresults['id'];

            if(!empty($formBuilder->submission->formresults['booking-room'])){
                foreach($formBuilder->submission->formresults['booking-room'] as $index=>$room){
                    $result         = $bookings->insertBooking($startDate[$index], $endDate[$index], "$subject;$room", $submissionId);
                }
            }else{
                $result         = $bookings->insertBooking($startDate[0], $endDate[0], $subject, $submissionId);
            }

            if(is_wp_error($result)){
                return $result;
            }
        }
    }

    return $message;
}, 10, 2);

// Update an existing booking
add_filter('sim-forms-submission-updated', function($message, $formTable, $elementName, $oldValue, $newValue){
    global $wpdb;

    $bookings           =  new Bookings($formTable);
    $currentBookings   = $bookings->getBookingsBySubmission($formTable->submission->id);

    if(!$currentBookings || !isset($currentBookings[0])){
        return $message;
    }

    $booking    = $currentBookings[0];

    // Get the element name
    $query      = "SELECT name FROM `{$wpdb->prefix}sim_form_elements` WHERE `form_id`=(select form_id from {$bookings->forms->submissionTableName} WHERE id=$booking->submission_id) AND `type`='booking_selector'";
    $subject    = $wpdb->get_var($query);

    $elementName  = str_replace('booking-', '', $elementName);
    // location and date & time are editable
    if(!in_array($elementName, ['startdate', 'enddate', 'startime', 'endtime', $subject, 'room'])){
        return $message;
    }

    // change the $elementName to subject as that is the name of the column in the db
    if($subject == $elementName){
        $elementName  = 'subject';
    }

    // multiple rooms and bookings
    if($elementName == 'room'){
        $newValue   = explode(';', $newValue);
        $baseSubject= explode(';', $booking->subject)[0];

        //$oldRooms   = explode(';', $oldValue);

        $deleted    = array_diff($oldValue, $newValue);
        $added      = array_diff($newValue, $oldValue);

        // remove any removed bookings
        if(!empty($deleted)){
            // find all bookings with the same submission_id
            $currentBookings    = $bookings->getBookingsBySubmission($formTable->submission->id);

            foreach($currentBookings as $booking){
                if(in_array(explode(';', $booking->subject)[1], $deleted)){
                    $result = $bookings->removeBooking($booking);
                }
            }
        }

        // add new ones
        foreach($added as $room){
            $result = $bookings->insertBooking($booking->startdate, $booking->enddate, $baseSubject.';'.$room, $formTable->submission->id);
        }

        $formTable->submission->formresults['booking-room'] = array_values($newValue);
    }else{
        foreach($currentBookings as $booking){
            $result = $bookings->updateBooking($booking, [$elementName => $newValue]);
        }
    }

    if(is_wp_error($result)){
        return $result;
    }

    return $message;
}, 10, 5);

// add a min and a max to booking dates on edit
add_filter('sim-forms-element-html', function($html, $element, $displayFormResults){
    global $wpdb;

    if($element->name == 'booking-enddate'){
        // Get the subject
        $subject    = $displayFormResults->submission->formresults[$displayFormResults->formElements[$displayFormResults->getElementByType('booking_selector')[0]]->name];
        
        // get the first event after this one
        $query  = "SELECT startdate FROM {$wpdb->prefix}sim_bookings WHERE subject = '$subject' AND startdate > '{$displayFormResults->submission->formresults[$element->name]}' ORDER BY startdate LIMIT 1";
        $max    = $wpdb->get_var($query);

        if(!empty($max)){
            $max    = "max='$max'";
        }

        return str_replace('>', "min='{$displayFormResults->submission->formresults['booking-startdate']}' $max>", $html);
    }elseif($element->name == 'booking-startdate'){
        // Get the subject
        $subject    = $displayFormResults->submission->formresults[$displayFormResults->formElements[$displayFormResults->getElementByType('booking_selector')[0]]->name];

        // get the first event before this one
        $query  = "SELECT enddate FROM {$wpdb->prefix}sim_bookings WHERE subject = '$subject' AND enddate <= '{$displayFormResults->submission->formresults[$element->name]}' ORDER BY enddate LIMIT 1";
        $min    = $wpdb->get_var($query);

        if(!empty($min)){
            $min    = "min='$min'";
        }

        return str_replace('>', "$min max='{$displayFormResults->submission->formresults['booking-enddate']}'>", $html);
    }
    return $html;
}, 10, 3);

add_action('sim-forms-entry-archived', __NAMESPACE__.'\removeBooking', 10, 2);
add_action('sim-forms-entry-removed', __NAMESPACE__.'\removeBooking', 10, 2);
function removeBooking($instance, $submissionId){
    // remove the booking
    $bookings   = new Bookings();

    $bookings    = $bookings->getBookingsBySubmission($submissionId);

    if(!$bookings){
        return;
    }

    foreach($bookings as $booking){
        $bookings->removeBooking($booking);
    }
}

add_filter('sim_form_actions_html', function($buttonsHtml, $bookingData, $index, $instance, $submission){
    if(get_class($instance) != 'SIM\BOOKINGS\Bookings' || !isset($buttonsHtml['archive'])){
        return $buttonsHtml;
    }

    $buttonsHtml['archive'] = str_replace('>Archive', 'style="width: max-content;">Cancel booking', $buttonsHtml['archive']);

    return $buttonsHtml;
}, 10, 5);

add_filter('sim_transform_formtable_data', function($output, $elementName){
    if(str_contains($output, ';')){
        // include room if needed
        $rooms  = explode(';', $output);
        $output = implode('&', $rooms);
    }
    return $output;
}, 10, 2);

// Filter e-mail transforms
add_filter('sim-forms-transform-array', function($string, $replaceValue, $forms, $match){
    if(count(array_unique($replaceValue)) == 1){
        $string = array_unique($replaceValue)[0];
    }else{

    }
    return $string;
}, 10, 4);

// add the booking details to the drop down for use in e-mails
add_action('sim-add-email-placeholder-option', function(){
    echo "<option>%booking-startdate%</option>";
    echo "<option>%booking-enddate%</option>";
    echo "<option>%booking-room%</option>";
    echo "<option>%booking detalis%</option>";
});

add_filter('sim-forms-transform-empty', function($replaceValue, $instance, $match){

    if($match == "booking detalis"){
        
        if(!empty($instance->submission->formresults['booking-startdate'])){
            $startDates     = array_unique($instance->submission->formresults['booking-startdate']);
            $endDates       = array_unique($instance->submission->formresults['booking-enddate']);
        
            // NO ROOMS
            if(empty($instance->submission->formresults['booking-room'])){
                
                $startDate      = date(get_option('date_format'), strtotime((string)$startDates[0]));
                $endDate        = date(get_option('date_format'), strtotime((string)$endDates[0]));
                $replaceValue   = "from $startDate till $endDate";
            }else{
                if(count($startDates) == 1 && count($endDates) == 1){
                    $rooms          = implode('&', $instance->submission->formresults['booking-room']);
                    $startDate      = date(get_option('date_format'), strtotime((string)$startDates[0]));
                    $endDate        = date(get_option('date_format'), strtotime((string)$endDates[0]));
                    $replaceValue   = "room $rooms from $startDate till $endDate";
                }else{
                    $replaceValue   = "room:<br>";
                    foreach($instance->submission->formresults['booking-room'] as $index=>$room){
                        $startDate      = date(get_option('date_format'), strtotime((string)$startDates[$index]));
                        $endDate        = date(get_option('date_format'), strtotime((string)$endDates[$index]));

                        $replaceValue   .= "$room from $startDate till $endDate<br>";
                    }
                }
            }
        }
        
    }
    return $replaceValue;
}, 10, 3);