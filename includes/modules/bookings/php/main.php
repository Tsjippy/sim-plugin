<?php
namespace SIM\BOOKINGS;
use SIM;

/**
 * Displays a calendar showing available dates
 */
function dateSelector(){

}

// Add a new type to the element choice dropdown
add_filter('sim-special-form-elements', function($options){
    $options['booking_selector']    = 'Booking selector';

    return $options;
});

// Display the date selector
add_filter('sim-forms-element-html', function($html, $element, $forms){

    if($element->type == 'booking_selector'){
        $html   = "<div class='selected-booking-dates hidden'>";
            $html   .= "<div>";
                $html   .= "<h4>Arrival Date</h4>";
                $html   .= "<input type='date' name='booking[startdate]' value='' disabled>";
            $html   .= "</div>";
            $html   .= "<div>";
                $html   .= "<h4>Departure Date</h4>";
                $html   .= "<input type='date' name='booking[enddate]' value='' disabled>";
            $html   .= "</div>";
            $html   .= "<button class='button change-booking-date' type='button'>Change</button>";
        $html   .= "</div>"; 

        wp_enqueue_script('sim-bookings');

        $booking   = new Bookings();

        // Find the accomodation names
        foreach($forms->formElements as $el){
            if($el->name == 'accomodation'){
                foreach(explode("\n", $el->valuelist) as $accomodation){
                    if($accomodation == 'No preference'){
                        continue;
                    }
                    $html   .= $booking->dateSelectorModal($accomodation);
                }
            }
        }
        
    }
    return $html;
}, 10, 3);

// Create a booking
add_action('sim_after_saving_formdata', function($formBuilder){
    if(isset($formBuilder->formResults['booking'])){
        $bookings   = new Bookings();

        $bookings->insertBooking($formBuilder->formResults['booking']['startdate'], $formBuilder->formResults['booking']['enddate'], $formBuilder->formResults['accomodation'], $formBuilder->formResults['id'], $formBuilder->userId);
    }
}, 10, 3);