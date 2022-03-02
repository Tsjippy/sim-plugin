document.addEventListener("DOMContentLoaded", function() {
    console.log("Dynamic travel forms js loaded");

    tidy_multi_inputs();
    form = document.getElementById('sim_form_travel');
        //show first tab
    currentTab = 0; // Current tab is set to be the first tab (0)
    showTab(currentTab,form); // Display the current tab
    
});

window.addEventListener("click", travel_listener);
window.addEventListener("input", travel_listener);

travel_prev_el = '';
function travel_listener(event) {
    var el			= event.target;
    form			= el.closest('form');
    var el_name		= el.name;
    if(el_name == '' || el_name == undefined){
        //el is a nice select
        if(el.closest('.nice-select-dropdown') != null && el.closest('.inputwrapper') != null){
            //find the select element connected to the nice-select 
            el.closest('.inputwrapper').querySelectorAll('select').forEach(select=>{
                if(el.dataset.value == select.value){
                    el	= select;
                    el_name = select.name;
                }
            });
        }else{
            return;
        }
    }

    //prevent duplicate event handling
    if(el == travel_prev_el){
        return;
    }
    travel_prev_el = el;
    //clear event prevenion after 100 ms
    setTimeout(function(){ travel_prev_el = ''; }, 100);

    if(el_name == 'nextBtn'){
        nextPrev(1);
    }else if(el_name == 'prevBtn'){
        nextPrev(-1);
    }

    process_travel_fields(el);
}

function process_travel_fields(el){
    var el_name = el.name;
	if(el_name == 'name'){
		var name = get_field_value('name');
		change_field_value('userid', name);
	}

	if(el_name == 'userid'){
		var userid = get_field_value('userid');
		change_field_value('email', userid);
		change_field_value('phonenumber', userid);
	}

	if(el_name == 'i_travel_with_others_button'){
			form.querySelectorAll('[name="specify_the_people_you_are_traveling_with_label"], [name="passenger_info"], [name^="passengers"], [name="childrenpasssengers"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.toggle('hidden');
				}catch(e){
					el.classList.toggle('hidden');
				}
			});
	}

	if(el_name == 'traveltype[]'){
		var value_1 = get_field_value('traveltype[]',true,'international');

		if(value_1 == 'international'){
			form.querySelectorAll('[name="from_or_to_nigeria?_label"], [name="select_your_in_country_airport_label"], [name="departure_airport[]"], [name="when_does_your_flight_leave?_label"], [name="flight1date"], [name="flight_departure_time"], [name="what_is_your_flight_number_label"], [name="flightnr1"], [name="your_in_country_airport_label"], [name="return_airport[]"], [name="when_does_your_flight_arrive?_label"], [name="flight2date"], [name="arrival_time"], [name="flightnumber_label"], [name="flightnr2"], [name="when_do_you_leave_for_your_next_destination?_label"], [name="departfrominboundairport"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.remove('hidden');
				}catch(e){
					el.classList.remove('hidden');
				}
			});
		}

		if(value_1 != 'international'){
			form.querySelectorAll('[name="select_your_in_country_airport_label"], [name="departure_airport[]"], [name="when_does_your_flight_leave?_label"], [name="flight1date"], [name="flight_departure_time"], [name="what_is_your_flight_number_label"], [name="flightnr1"], [name="your_in_country_airport_label"], [name="return_airport[]"], [name="when_does_your_flight_arrive?_label"], [name="flight2date"], [name="arrival_time"], [name="flightnumber_label"], [name="flightnr2"], [name="when_do_you_leave_for_your_next_destination?_label"], [name="departfrominboundairport"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.add('hidden');
				}catch(e){
					el.classList.add('hidden');
				}
			});
		}
		var value_1 = get_field_value('traveltype[]',true,'in_country');

		if(value_1 == 'in_country'){
			form.querySelectorAll('[name="specify_your_destination_address_label"], [name="destination"], [name="when_do_you_leave_from_here?_label"], [name="returnstartdate"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.remove('hidden');
				}catch(e){
					el.classList.remove('hidden');
				}
			});
		}

		if(value_1 != 'in_country'){
			form.querySelectorAll('[name="specify_your_destination_address_label"], [name="destination"], [name="when_do_you_leave_from_here?_label"], [name="returnstartdate"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.add('hidden');
				}catch(e){
					el.classList.add('hidden');
				}
			});
		}
	}

	if(el_name == 'traveltype[]' || el_name == 'roundtrip[]'){
		var value_1 = get_field_value('traveltype[]',true,'international');
		var value_3 = get_field_value('roundtrip[]',true,'yes');

		if(value_1 != 'international' || value_3 == 'yes'){
			form.querySelector('[name="from_or_to_nigeria?_label"]').closest('.inputwrapper').classList.add('hidden');
		}
		var value_1 = get_field_value('traveltype[]',true,'in_country');

		if(value_1 == 'in_country' || value_3 == 'yes'){
			change_field_property('fromorto[]', 'checked', "");
		}
	}

	if(el_name == 'fromorto[]'){
		var value_1 = get_field_value('fromorto[]',true,'to_nigeria');

		if(value_1 == 'to_nigeria'){
			form.querySelectorAll('[name="leg_1_label"], [name="travel[1][from]"], [name="travel[1][date]"], [name="travel[1][to]"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.add('hidden');
				}catch(e){
					el.classList.add('hidden');
				}
			});
			form.querySelector('[name="departure_travel_details_formstep"]').classList.add('hidden');
			change_field_value('departure1address', "");
			change_field_value('travel[4][from]', "Abroad");
		}

		if(value_1 != 'to_nigeria'){
			form.querySelectorAll('[name="leg_1_label"], [name="travel[1][from]"], [name="travel[1][date]"], [name="travel[1][to]"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.remove('hidden');
				}catch(e){
					el.classList.remove('hidden');
				}
			});
			form.querySelector('[name="departure_travel_details_formstep"]').classList.remove('hidden');
		}
		var value_1 = get_field_value('fromorto[]',true,'leaving_nigeria');

		if(value_1 == 'leaving_nigeria'){
			change_field_value('travel[4][from]', "");
			change_field_value('travel[4][to]', "");
		}
	}

	if(el_name == 'stopover[]'){

		if(el.checked == true){
			form.querySelectorAll('[name="specify_your_stopover_address_label"], [name="stopover1address"], [name="when_do_you_leave_from_you_stopover_location_label"], [name="stopover1date"], [name="leg_2_label"], [name="travel[2][from]"], [name="travel[2][date]"], [name="travel[2][to]"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.remove('hidden');
				}catch(e){
					el.classList.remove('hidden');
				}
			});
		}

		if(el.checked == false){
			form.querySelectorAll('[name="specify_your_stopover_address_label"], [name="stopover1address"], [name="when_do_you_leave_from_you_stopover_location_label"], [name="stopover1date"], [name="leg_2_label"], [name="travel[2][from]"], [name="travel[2][date]"], [name="travel[2][to]"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.add('hidden');
				}catch(e){
					el.classList.add('hidden');
				}
			});
			change_field_value('stopover1address', "");
			change_field_value('stopover1date', "");
			change_field_property('return_stopover[][]', 'checked', "");
		}
	}

	if(el_name == 'departuredate1'){
		var departuredate1 = get_field_value('departuredate1');
		change_field_property('stopover1date', 'min', departuredate1);
		change_field_value('travel[1][date]', departuredate1);
		var value_3 = get_field_value('stopover1date',true,'');

		if(value_3 == ''){
			change_field_property('flight1date', 'min', departuredate1);
		}
		var value_5 = get_field_value('flight1date',true,'');

		if(value_3 == '' && value_5 == ''){
			change_field_property('returnstartdate', 'min', departuredate1);
		}
	}

	if(el_name == 'departure_airport[]'){
		var departure_airport = get_field_value('departure_airport[]');
		var value_1 = get_field_value('departure_airport[]',true,'other');

		if(value_1 == 'other'){
			form.querySelectorAll('[name="specify_your_airport_name_label"], [name="custom_airport"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.remove('hidden');
				}catch(e){
					el.classList.remove('hidden');
				}
			});
			change_field_value('custom_airport', "");
			change_field_property('custom_return_airport', '', "");
		}

		if(value_1 != 'other'){
			form.querySelectorAll('[name="specify_your_airport_name_label"], [name="custom_airport"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.add('hidden');
				}catch(e){
					el.classList.add('hidden');
				}
			});
		}
		var value_3 = get_field_value('departure_airport[]',true,'other');

		if(value_3 != 'other'){
			change_field_value('custom_airport', departure_airport);
		}
		var value_3 = get_field_value('roundtrip[]',true,'yes');

		if(value_3 == 'yes'){
			change_field_value('return_airport[]', departure_airport);
		}
		var value_3 = get_field_value('departure_airport[]',true,'other');
		var value_5 = get_field_value('roundtrip[]',true,'yes');

		if(value_3 != 'other' && value_5 == 'yes'){
			change_field_value('custom_return_airport', departure_airport);
		}
	}

	if(el_name == 'stopover1date'){
		var stopover1date = get_field_value('stopover1date');
		change_field_property('flight1date', 'min', stopover1date);
		change_field_value('travel[2][date]', stopover1date);
		var value_3 = get_field_value('flight1date',true,'');

		if(value_3 == ''){
			change_field_property('returnstartdate', 'min', stopover1date);
		}
	}

	if(el_name == 'simtransport[]'){
		var value_1 = get_field_value('simtransport[]',true,'yes');

		if(value_1 == 'yes'){
			form.querySelectorAll('[name="number_of_bags_departing_label"], [name="number_of_bags_2"], [name="number_of_bags_label"], [name="number_of_bags_returning"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.remove('hidden');
				}catch(e){
					el.classList.remove('hidden');
				}
			});
		}

		if(value_1 != 'yes'){
			form.querySelectorAll('[name="number_of_bags_departing_label"], [name="number_of_bags_2"], [name="number_of_bags_label"], [name="number_of_bags_returning"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.add('hidden');
				}catch(e){
					el.classList.add('hidden');
				}
			});
		}
	}

	if(el_name == 'departuredate1' || el_name == 'flight1date' || el_name == 'stopover1date' || el_name == 'flight1date' || el_name == 'simtransport[]' || el_name == 'traveltype[]'){
		var value_1 = get_field_value('departuredate1',true,value_2);
		var value_2 = get_field_value('flight1date',true,value_2);
		var value_3 = get_field_value('stopover1date',true,value_4);
		var value_4 = get_field_value('flight1date',true,value_4);
		var value_5 = get_field_value('simtransport[]',true,'no');
		var value_7 = get_field_value('traveltype[]',true,'international');

		if(value_1 != value_2 && value_3 != value_4 && value_5 == 'no' && value_7 == 'international'){
			form.querySelectorAll('[name="where_are_you_staying_before_your_flight_leaves_label"], [name="beforeairportaddress"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.remove('hidden');
				}catch(e){
					el.classList.remove('hidden');
				}
			});
		}
	}

	if(el_name == 'stopover1date' || el_name == 'flight1date' || el_name == 'stopover[]'){
		var value_1 = get_field_value('stopover1date',true,value_2);
		var value_2 = get_field_value('flight1date',true,value_2);

		if(value_1 == value_2 && form.querySelector('[name="stopover[]"]').checked == true){
			form.querySelectorAll('[name="where_are_you_staying_before_your_flight_leaves_label"], [name="beforeairportaddress"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.add('hidden');
				}catch(e){
					el.classList.add('hidden');
				}
			});
		}
	}

	if(el_name == 'flight1date'){
		var flight1date = get_field_value('flight1date');
		change_field_property('returnstartdate', 'min', flight1date);
		change_field_value('travel[3][date]', flight1date);
		var value_1 = get_field_value('departuredate1',true,value_2);
		var value_2 = get_field_value('flight1date',true,value_2);

		if(value_1 == value_2){
			form.querySelectorAll('[name="where_are_you_staying_before_your_flight_leaves_label"], [name="beforeairportaddress"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.add('hidden');
				}catch(e){
					el.classList.add('hidden');
				}
			});
		}
	}

	if(el_name == 'custom_airport'){
		var custom_airport = get_field_value('custom_airport');
		change_field_value('travel[3][from]', custom_airport);
		var value_3 = get_field_value('custom_airport',true,'');

		if(value_3 != ''){
			change_field_value('destination', "Abroad");
		}

		if(value_3 == ''){
			change_field_value('destination', "");
		}
		var value_3 = get_field_value('roundtrip[]',true,'yes');

		if(value_3 == 'yes'){
			change_field_value('custom_return_airport', custom_airport);
		}

		if(form.querySelector('[name="stopover[]"]').checked == true){
			change_field_value('travel[2][to]', custom_airport);
		}
	}

	if(el_name == 'roundtrip[]' || el_name == 'fromorto[]'){
		var value_1 = get_field_value('roundtrip[]',true,'no');
		var value_3 = get_field_value('fromorto[]',true,'to_nigeria');

		if(value_1 == 'no' && value_3 != 'to_nigeria'){
			form.querySelector('[name="return_trip_details_formstep"]').classList.add('hidden');
		}
	}

	if(el_name == 'fromorto[]' || el_name == 'roundtrip[]'){
		var value_1 = get_field_value('fromorto[]',true,'to_nigeria');
		var value_3 = get_field_value('roundtrip[]',true,'yes');

		if(value_1 == 'to_nigeria' || value_3 == 'yes'){
			form.querySelector('[name="return_trip_details_formstep"]').classList.remove('hidden');
		}
	}

	if(el_name == 'roundtrip[]'){
		var value_1 = get_field_value('roundtrip[]',true,'no');
		var value_3 = get_field_value('fromorto[]',true,'to_nigeria');

		if(value_1 == 'no' && value_3 != 'to_nigeria'){
			change_field_value('returnstartdate', "");
		}

		if(value_1 == 'no'){
			change_field_value('final_destination', "");
		}
		var value_1 = get_field_value('roundtrip[]',true,'yes');

		if(value_1 == 'yes'){
			change_field_value('travel[4][from]', "");
		}
	}

	if(el_name == 'return_airport[]'){
		var return_airport = get_field_value('return_airport[]');
		var value_1 = get_field_value('return_airport[]',true,'other');

		if(value_1 == 'other'){
			form.querySelectorAll('[name="specify_your_airport_name_label"], [name="custom_return_airport"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.remove('hidden');
				}catch(e){
					el.classList.remove('hidden');
				}
			});
		}

		if(value_1 != 'other'){
			form.querySelectorAll('[name="specify_your_airport_name_label"], [name="custom_return_airport"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.add('hidden');
				}catch(e){
					el.classList.add('hidden');
				}
			});
		}
		var value_1 = get_field_value('fromorto[]',true,'to_nigeria');
		var value_5 = get_field_value('return_airport[]',true,'other');

		if(value_1 == 'to_nigeria' && value_5 != 'other'){
			change_field_value('custom_return_airport', return_airport);
		}
	}

	if(el_name == 'returnstartdate'){
		var returnstartdate = get_field_value('returnstartdate');
		var final_destination = get_field_value('final_destination');
		change_field_property('flight2date', 'min', returnstartdate);
		change_field_value('travel[4][date]', returnstartdate);
		var value_3 = get_field_value('departfrominboundairport',true,'');

		if(value_3 == ''){
			change_field_property('stopover2date', 'min', returnstartdate);
		}
		var value_5 = get_field_value('traveltype[]',true,'in_country');

		if(form.querySelector('[name="return_stopover[]"]').checked == false && value_5 != 'in_country'){
			change_field_value('travel[5][to]', final_destination);
		}
	}

	if(el_name == 'flight2date'){
		var flight2date = get_field_value('flight2date');
		change_field_property('departfrominboundairport', 'min', flight2date);
		change_field_value('travel[4][date]', flight2date);
	}

	if(el_name == 'departfrominboundairport'){
		var departfrominboundairport = get_field_value('departfrominboundairport');
		change_field_property('stopover2date', 'min', departfrominboundairport);
		change_field_value('travel[5][date]', departfrominboundairport);
		var value_3 = get_field_value('flight2date',true,value_4);
		var value_4 = get_field_value('departfrominboundairport',true,value_4);
		var value_5 = get_field_value('simtransport[]',true,'no');

		if(value_3 != value_4 && value_5 == 'no'){
			form.querySelector('[name="where_do_you_stay_before_you_continue_your_journey_label"]').closest('.inputwrapper').classList.remove('hidden');
		}

		if(value_3 == value_4){
			form.querySelector('[name="where_do_you_stay_before_you_continue_your_journey_label"]').closest('.inputwrapper').classList.add('hidden');
		}
	}

	if(el_name == 'stopover[]' || el_name == 'roundtrip[]'){
		var value_3 = get_field_value('roundtrip[]',true,'yes');

		if(form.querySelector('[name="stopover[]"]').checked == true && value_3 == 'yes'){
			change_field_property('return_stopover[][]', 'checked', "true");
		}
	}

	if(el_name == 'return_stopover[]'){

		if(el.checked == true){
			form.querySelectorAll('[name="stopover_address_label"], [name="stopover2address"], [name="when_do_you_leave?_label"], [name="stopover2date"], [name="leg_6_label"], [name="travel[6][from]"], [name="travel[6][date]"], [name="travel[6][to]"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.remove('hidden');
				}catch(e){
					el.classList.remove('hidden');
				}
			});
		}

		if(el.checked == false){
			form.querySelectorAll('[name="stopover_address_label"], [name="stopover2address"], [name="when_do_you_leave?_label"], [name="stopover2date"], [name="leg_6_label"], [name="travel[6][from]"], [name="travel[6][date]"], [name="travel[6][to]"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.add('hidden');
				}catch(e){
					el.classList.add('hidden');
				}
			});
			change_field_value('stopover2address', "");
			change_field_value('travel[6][to]', "");
		}
	}

	if(el_name == 'stopover1address'){
		var stopover1address = get_field_value('stopover1address');
		var destination = get_field_value('destination');
		var travel_3_from = get_field_value('travel[3][from]');
		change_field_value('travel[2][from]', stopover1address);
		var value_3 = get_field_value('roundtrip[]',true,'yes');

		if(value_3 == 'yes'){
			change_field_value('stopover2address', stopover1address);
		}
		var value_3 = get_field_value('stopover1address',true,'');

		if(value_3 != ''){
			change_field_value('travel[1][to]', stopover1address);
		}
		var value_5 = get_field_value('travel[3][from]',true,'');

		if(value_3 == '' && value_5 == ''){
			change_field_value('travel[1][to]', destination);
		}

		if(value_3 == '' && value_5 != ''){
			change_field_value('travel[1][to]', travel_3_from);
		}
		var value_3 = get_field_value('destination',true,'');
		var value_5 = get_field_value('traveltype[]',true,'in_country');

		if(value_3 != '' && value_5 == 'in_country'){
			change_field_value('travel[2][to]', destination);
		}
	}

	if(el_name == 'departure1address' || el_name == 'roundtrip[]'){
		var departure1address = get_field_value('departure1address');
		var value_1 = get_field_value('roundtrip[]',true,'yes');
		var value_5 = get_field_value('roundtrip[]',true,'yes');

		if(value_1 == 'yes' || value_5 == 'yes'){
			change_field_value('final_destination', departure1address);
		}
	}

	if(el_name == 'travel[6][date]'){
		var value_3 = get_field_value('travel[1][date]',true,'');
		var calculated_value_2 = (Date.parse(value_5) - Date.parse(value_6))/ (1000 * 60 * 60 * 24);
		var value_5 = get_field_value('travel[6][date]',true,2);
		var value_6 = get_field_value('travel[1][date]',true,2);

		if(value_3 != '' && calculated_value_2 > 2){
			form.querySelectorAll('[name="did_you_notify_your_colleagues_about_your_absence_label"], [name="colleaguesnotified[]"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.remove('hidden');
				}catch(e){
					el.classList.remove('hidden');
				}
			});
		}
	}

	if(el_name == 'travel[5][date]'){
		var value_3 = get_field_value('travel[1][date]',true,'');
		var value_5 = get_field_value('travel[6][date]',true,'');
		var calculated_value_3 = (Date.parse(value_7) - Date.parse(value_8))/ (1000 * 60 * 60 * 24);
		var value_7 = get_field_value('travel[5][date]',true,2);
		var value_8 = get_field_value('travel[1][date]',true,2);

		if(value_3 != '' && value_5 == '' && calculated_value_3 > 2){
			form.querySelectorAll('[name="did_you_notify_your_colleagues_about_your_absence_label"], [name="colleaguesnotified[]"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.remove('hidden');
				}catch(e){
					el.classList.remove('hidden');
				}
			});
		}
	}

	if(el_name == 'travel[4][date]'){
		var value_3 = get_field_value('travel[5][date]',true,'');
		var value_5 = get_field_value('travel[6][date]',true,'');
		var calculated_value_3 = (Date.parse(value_7) - Date.parse(value_8))/ (1000 * 60 * 60 * 24);
		var value_7 = get_field_value('travel[4][date]',true,2);
		var value_8 = get_field_value('travel[1][date]',true,2);

		if(value_3 == '' && value_5 == '' && calculated_value_3 > 2){
			form.querySelectorAll('[name="did_you_notify_your_colleagues_about_your_absence_label"], [name="colleaguesnotified[]"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.remove('hidden');
				}catch(e){
					el.classList.remove('hidden');
				}
			});
		}
	}

	if(el_name == 'departure1address'){
		var departure1address = get_field_value('departure1address');
		change_field_value('travel[1][from]', departure1address);

		if(form.querySelector('[name="return_stopover[]"]').checked == true){
			change_field_value('travel[6][to]', departure1address);
		}
	}

	if(el_name == 'travel[1][date]' || el_name == 'travel[1][to]'){
		var value_1 = get_field_value('travel[1][date]',true,'');
		var value_3 = get_field_value('travel[1][to]',true,'');

		if(value_1 != '' && value_3 != ''){
			form.querySelector('[name="travel[1][from]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}

	if(el_name == 'travel[1][to]' || el_name == 'travel[1][date]'){
		var value_1 = get_field_value('travel[1][to]',true,'');
		var value_3 = get_field_value('travel[1][date]',true,'');

		if(value_1 == '' && value_3 == ''){
			form.querySelector('[name="travel[1][from]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}

	if(el_name == 'travel[3][from]'){
		var travel_3_from = get_field_value('travel[3][from]');
		var value_3 = get_field_value('travel[3][from]',true,'');
		var value_5 = get_field_value('stopover1address',true,'');

		if(value_3 != '' && value_5 == ''){
			change_field_value('travel[1][to]', travel_3_from);
		}
	}

	if(el_name == 'destination'){
		var destination = get_field_value('destination');
		var value_3 = get_field_value('destination',true,'');
		var value_5 = get_field_value('stopover1address',true,'');
		var value_7 = get_field_value('travel[3][from]',true,'');

		if(value_3 != '' && value_5 == '' && value_7 == ''){
			change_field_value('travel[1][to]', destination);
		}
		var value_7 = get_field_value('traveltype[]',true,'in_country');

		if(value_3 != '' && form.querySelector('[name="stopover[]"]').checked == true && value_7 == 'in_country'){
			change_field_value('travel[2][to]', destination);
		}
		var value_3 = get_field_value('traveltype[]',true,'international');

		if(value_3 == 'international'){
			change_field_value('travel[3][to]', destination);
		}
		var value_3 = get_field_value('roundtrip[]',true,'yes');

		if(value_3 == 'yes'){
			change_field_value('travel[4][from]', destination);
		}
	}

	if(el_name == 'travel[2][date]' || el_name == 'travel[2][to]'){
		var value_1 = get_field_value('travel[2][date]',true,'');
		var value_3 = get_field_value('travel[2][to]',true,'');

		if(value_1 != '' || value_3 != ''){
			form.querySelector('[name="travel[2][from]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}

	if(el_name == 'travel[2][to]' || el_name == 'travel[2][date]'){
		var value_1 = get_field_value('travel[2][to]',true,'');
		var value_3 = get_field_value('travel[2][date]',true,'');

		if(value_1 == '' && value_3 == ''){
			form.querySelector('[name="travel[2][from]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}

	if(el_name == 'stopover[]' || el_name == 'traveltype[]'){
		var value_3 = get_field_value('traveltype[]',true,'in_country');

		if(form.querySelector('[name="stopover[]"]').checked == false && value_3 == 'in_country'){
			change_field_value('travel[2][to]', "");
		}
	}

	if(el_name == 'stopover[]' || el_name == 'custom_airport'){
		var custom_airport = get_field_value('custom_airport');
		var value_3 = get_field_value('custom_airport',true,'');

		if(form.querySelector('[name="stopover[]"]').checked == true && value_3 != ''){
			change_field_value('travel[2][to]', custom_airport);
		}
	}

	if(el_name == 'travel[3][from]' || el_name == 'travel[3][date]' || el_name == 'travel[3][to]'){
		var value_1 = get_field_value('travel[3][from]',true,'');
		var value_3 = get_field_value('travel[3][date]',true,'');
		var value_5 = get_field_value('travel[3][to]',true,'');

		if(value_1 != '' || value_3 != '' || value_5 != ''){
			form.querySelectorAll('[name="leg_3_label"], [name="travel[3][from]"], [name="travel[3][date]"], [name="travel[3][to]"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.remove('hidden');
				}catch(e){
					el.classList.remove('hidden');
				}
			});
		}
	}

	if(el_name == 'travel[3][to]' || el_name == 'travel[3][from]' || el_name == 'travel[3][date]'){
		var value_1 = get_field_value('travel[3][to]',true,'');
		var value_3 = get_field_value('travel[3][from]',true,'');
		var value_5 = get_field_value('travel[3][date]',true,'');

		if(value_1 == '' && value_3 == '' && value_5 == ''){
			form.querySelectorAll('[name="leg_3_label"], [name="travel[3][from]"], [name="travel[3][date]"], [name="travel[3][to]"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.add('hidden');
				}catch(e){
					el.classList.add('hidden');
				}
			});
		}
	}

	if(el_name == 'travel[3][flightnr]'){
		var value_1 = get_field_value('travel[3][flightnr]',true,'');

		if(value_1 != ''){
			form.querySelector('[name="flightnumber:_label"]').closest('.inputwrapper').classList.remove('hidden');
		}

		if(value_1 == ''){
			form.querySelector('[name="flightnumber:_label"]').closest('.inputwrapper').classList.add('hidden');
		}
	}

	if(el_name == 'flightnr1'){
		var flightnr1 = get_field_value('flightnr1');
		change_field_value('travel[3][flightnr]', flightnr1);
	}

	if(el_name == 'travel[4][from]' || el_name == 'travel[4][date]' || el_name == 'travel[4][to]'){
		var value_1 = get_field_value('travel[4][from]',true,'');
		var value_3 = get_field_value('travel[4][date]',true,'');
		var value_5 = get_field_value('travel[4][to]',true,'');

		if(value_1 != '' || value_3 != '' || value_5 != ''){
			form.querySelectorAll('[name="leg_4_label"], [name="travel[4][from]"], [name="travel[4][date]"], [name="travel[4][to]"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.remove('hidden');
				}catch(e){
					el.classList.remove('hidden');
				}
			});
		}

		if(value_1 == '' && value_3 == '' && value_5 == ''){
			form.querySelectorAll('[name="leg_4_label"], [name="travel[4][from]"], [name="travel[4][date]"], [name="travel[4][to]"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.add('hidden');
				}catch(e){
					el.classList.add('hidden');
				}
			});
		}
	}

	if(el_name == 'travel[4][date]' || el_name == 'travel[4][to]'){
		var value_1 = get_field_value('travel[4][date]',true,'');
		var value_3 = get_field_value('travel[4][to]',true,'');

		if(value_1 != '' || value_3 != ''){
			form.querySelector('[name="travel[4][from]"]').closest('.inputwrapper').classList.remove('hidden');
		}

		if(value_1 == '' && value_3 == ''){
			form.querySelector('[name="travel[4][from]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}

	if(el_name == 'stopover2address'){
		var stopover2address = get_field_value('stopover2address');
		change_field_value('travel[6][from]', stopover2address);
		var value_3 = get_field_value('stopover2address',true,'');
		var value_5 = get_field_value('traveltype[]',true,'international');

		if(value_3 != '' && value_5 != 'international'){
			change_field_value('travel[4][to]', stopover2address);
		}

		if(value_3 != '' && value_5 == 'international'){
			change_field_value('travel[5][to]', stopover2address);
		}
	}

	if(el_name == 'final_destination'){
		var final_destination = get_field_value('final_destination');
		var value_1 = get_field_value('traveltype[]',true,'international');

		if(value_1 != 'international' && form.querySelector('[name="return_stopover[]"]').checked == false){
			change_field_value('travel[4][to]', final_destination);
		}
		var value_3 = get_field_value('traveltype[]',true,'international');

		if(value_3 != 'international' && form.querySelector('[name="return_stopover[]"]').checked == false){
			change_field_value('travel[4][to]', final_destination);
		}
		var value_3 = get_field_value('traveltype[]',true,'in_country');

		if(value_3 != 'in_country' && form.querySelector('[name="return_stopover[]"]').checked == false){
			change_field_value('travel[5][to]', final_destination);
		}

		if(form.querySelector('[name="return_stopover[]"]').checked == true){
			change_field_value('travel[6][to]', final_destination);
		}
	}

	if(el_name == 'return_stopover[]' || el_name == 'traveltype[]'){
		var final_destination = get_field_value('final_destination');
		var value_3 = get_field_value('traveltype[]',true,'in_country');

		if(form.querySelector('[name="return_stopover[]"]').checked == false && value_3 == 'in_country'){
			change_field_value('travel[4][to]', final_destination);
		}
		var value_3 = get_field_value('traveltype[]',true,'international');

		if(form.querySelector('[name="return_stopover[]"]').checked == false && value_3 == 'international'){
			change_field_value('travel[5][to]', final_destination);
		}
	}

	if(el_name == 'custom_return_airport'){
		var custom_return_airport = get_field_value('custom_return_airport');
		change_field_value('travel[4][to]', custom_return_airport);
		change_field_value('travel[5][from]', custom_return_airport);
	}

	if(el_name == 'travel[4][flightnr]'){
		var value_1 = get_field_value('travel[4][flightnr]',true,'');

		if(value_1 != ''){
			form.querySelectorAll('[name="return_flight_number_label"], [name="travel[4][flightnr]"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.remove('hidden');
				}catch(e){
					el.classList.remove('hidden');
				}
			});
		}

		if(value_1 == ''){
			form.querySelectorAll('[name="return_flight_number_label"], [name="travel[4][flightnr]"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.add('hidden');
				}catch(e){
					el.classList.add('hidden');
				}
			});
		}
	}

	if(el_name == 'flightnr2'){
		var flightnr2 = get_field_value('flightnr2');
		change_field_value('travel[4][flightnr]', flightnr2);
	}

	if(el_name == 'travel[5][from]' || el_name == 'travel[5][date]' || el_name == 'travel[5][to]'){
		var value_1 = get_field_value('travel[5][from]',true,'');
		var value_3 = get_field_value('travel[5][date]',true,'');
		var value_5 = get_field_value('travel[5][to]',true,'');

		if(value_1 != '' || value_3 != '' || value_5 != ''){
			form.querySelectorAll('[name="leg_5_label"], [name="travel[5][from]"], [name="travel[5][date]"], [name="travel[5][to]"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.remove('hidden');
				}catch(e){
					el.classList.remove('hidden');
				}
			});
		}

		if(value_1 == '' && value_3 == '' && value_5 == ''){
			form.querySelectorAll('[name="leg_5_label"], [name="travel[5][from]"], [name="travel[5][date]"], [name="travel[5][to]"]').forEach(el=>{
				try{
					el.closest('.inputwrapper').classList.add('hidden');
				}catch(e){
					el.classList.add('hidden');
				}
			});
		}
	}

	if(el_name == 'stopover2date'){
		var stopover2date = get_field_value('stopover2date');
		change_field_value('travel[6][date]', stopover2date);
	}

	if(el_name == 'return_stopover[]' || el_name == 'departure1address'){
		var departure1address = get_field_value('departure1address');
		var value_3 = get_field_value('departure1address',true,'');

		if(form.querySelector('[name="return_stopover[]"]').checked == true && value_3 != ''){
			change_field_value('travel[6][to]', departure1address);
		}
	}

}
