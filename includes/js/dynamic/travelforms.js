

document.addEventListener("DOMContentLoaded", function() {
	console.log("Dynamic travel forms js loaded");
	
	tidy_multi_inputs();
	
	//show first tab
	currentTab = 0; // Current tab is set to be the first tab (0)
	form = document.getElementById('simnigeria_form_travel');
	showTab(currentTab,form); // Display the current tab
});

window.addEventListener("click", travel_listener);
window.addEventListener("input", travel_listener);

travel_prev_el = '';
function travel_listener(event) {
	var el			= event.target;
	form			= el.closest('form');
	var name		= el.name;
	if(name == '' || name == undefined){
		return;
	}
	
	//prevent duplicate event handling
	if(el == travel_prev_el){
		return;
	}
	travel_prev_el = el;
	//clear event prevenion after 200 ms
	setTimeout(function(){ travel_prev_el = ''; }, 200);
	
	if(name == 'nextBtn'){
		nextPrev(1);
	}else if(name == 'prevBtn'){
		nextPrev(-1);
	}

	process_travel_fields(el);
}

function process_travel_fields(el){
	var name = el.name;

	if(name == 'name'){
			var replacement_value = get_field_value('name')
			change_field_value('userid', replacement_value);
	}
	if(name == 'userid'){
			var replacement_value = get_field_value('userid')
			change_field_value('email', replacement_value);
	}
	if(name == 'userid'){
			var replacement_value = get_field_value('userid')
			change_field_value('phonenumber', replacement_value);
	}
	if(name == 'i_travel_with_others_button'){

			form.querySelector('[name="specify_the_people_you_are_traveling_with_label"]').closest('.inputwrapper').classList.toggle('hidden');
			form.querySelector('[name="passenger_info"]').closest('.inputwrapper').classList.toggle('hidden');
			form.querySelector('[name^="passengers"]').closest('.inputwrapper').classList.toggle('hidden');
			form.querySelector('[name="childrenpasssengers"]').closest('.inputwrapper').classList.toggle('hidden');
	}
	if(name == 'roundtrip[]' || name == 'traveltype[]'){

		var value_1 = get_field_value('roundtrip[]',true,'no');

		var value_3 = get_field_value('traveltype[]',true,'international');

		if(value_1.toLowerCase() == 'no' && value_3.toLowerCase() == 'international'){

			form.querySelector('[name="fromorto[]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'traveltype[]' || name == 'roundtrip[]'){

		var value_1 = get_field_value('traveltype[]',true,'international');

		var value_3 = get_field_value('roundtrip[]',true,'yes');

		if(value_1.toLowerCase() != 'international' || value_3.toLowerCase() == 'yes'){

			form.querySelector('[name="fromorto[]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'traveltype[]' || name == 'roundtrip[]'){

		var value_1 = get_field_value('traveltype[]',true,'in_country');

		var value_3 = get_field_value('roundtrip[]',true,'yes');

		if(value_1.toLowerCase() == 'in_country' || value_3.toLowerCase() == 'yes'){
			var replacement_value = '';
			change_field_property('fromorto[]', 'checked', replacement_value);
		}
	}
	if(name == 'fromorto[]'){

		var value_1 = get_field_value('fromorto[]',true,'to_nigeria');

		if(value_1.toLowerCase() == 'to_nigeria'){

			form.querySelector('[name="departure_travel_details_formstep"]').classList.add('hidden');
		}
	}
	if(name == 'fromorto[]'){

		var value_1 = get_field_value('fromorto[]',true,'to_nigeria');

		if(value_1.toLowerCase() != 'to_nigeria'){

			form.querySelector('[name="departure_travel_details_formstep"]').classList.remove('hidden');
		}
	}
	if(name == 'fromorto[]'){

		var value_1 = get_field_value('fromorto[]',true,'to_nigeria');

		if(value_1.toLowerCase() == 'to_nigeria'){
			var replacement_value = '';
			change_field_value('departure1address', replacement_value);
		}
	}
	if(name == 'stopover[]'){

		if(el.checked == true){

			form.querySelector('[name="stopover1address"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="when_do_you_leave_from_you_stopover_location_label"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="stopover1date"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'stopover[]'){

		if(el.checked == false){

			form.querySelector('[name="stopover1address"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="when_do_you_leave_from_you_stopover_location_label"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="stopover1date"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'stopover[]'){

		if(el.checked == false){
			var replacement_value = '';
			change_field_value('stopover1address', replacement_value);
		}
	}
	if(name == 'departuredate1'){
			var replacement_value = get_field_value('departuredate1')
			change_field_property('stopover1date', 'min', replacement_value);
	}
	if(name == 'stopover[]'){

		if(el.checked == false){
			var replacement_value = '';
			change_field_value('stopover1date', replacement_value);
		}
	}
	if(name == 'traveltype[]'){

		var value_1 = get_field_value('traveltype[]',true,'international');

		if(value_1.toLowerCase() == 'international'){

			form.querySelector('[name="departure_airport[]"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="when_does_your_flight_leave?_label"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="flight1date"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="flight_departure_time"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="what_is_your_flight_number_label"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="flightnr1"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'traveltype[]'){

		var value_1 = get_field_value('traveltype[]',true,'international');

		if(value_1.toLowerCase() != 'international'){

			form.querySelector('[name="departure_airport[]"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="when_does_your_flight_leave?_label"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="flight1date"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="flight_departure_time"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="what_is_your_flight_number_label"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="flightnr1"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'departure_airport[]'){

		var value_1 = get_field_value('departure_airport[]',true,'other');

		if(value_1.toLowerCase() == 'other'){

			form.querySelector('[name="custom_airport"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'departure_airport[]'){

		var value_1 = get_field_value('departure_airport[]',true,'other');

		if(value_1.toLowerCase() != 'other'){

			form.querySelector('[name="custom_airport"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'departure_airport[]'){

		var value_3 = get_field_value('departure_airport[]',true,'other');

		if(value_3.toLowerCase() != 'other'){
			var replacement_value = get_field_value('departure_airport[]')
			change_field_value('custom_airport', replacement_value);
		}
	}
	if(name == 'departure_airport[]'){

		var value_1 = get_field_value('departure_airport[]',true,'other');

		if(value_1.toLowerCase() == 'other'){
			var replacement_value = '';
			change_field_value('custom_airport', replacement_value);
		}
	}
	if(name == 'stopover1date'){
			var replacement_value = get_field_value('stopover1date')
			change_field_property('flight1date', 'min', replacement_value);
	}
	if(name == 'departuredate1'){

		var value_3 = get_field_value('stopover1date',true,'');

		if(value_3.toLowerCase() == ''){
			var replacement_value = get_field_value('departuredate1')
			change_field_property('flight1date', 'min', replacement_value);
		}
	}
	if(name == 'simtransport[]'){

		var value_1 = get_field_value('simtransport[]',true,'yes');

		if(value_1.toLowerCase() == 'yes'){

			form.querySelector('[name="number_of_bags_2"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="number_of_bags_returning_label"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="number_of_bags_returning"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'simtransport[]'){

		var value_1 = get_field_value('simtransport[]',true,'yes');

		if(value_1.toLowerCase() != 'yes'){

			form.querySelector('[name="number_of_bags_2"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="number_of_bags_returning_label"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="number_of_bags_returning"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'departuredate1' || name == 'flight1date' || name == 'stopover1date' || name == 'flight1date' || name == 'simtransport[]' || name == 'traveltype[]'){

		var value_1 = get_field_value('departuredate1',true,value_2);

		var value_2 = get_field_value('flight1date',true,value_2);

		var value_3 = get_field_value('stopover1date',true,value_4);

		var value_4 = get_field_value('flight1date',true,value_4);

		var value_5 = get_field_value('simtransport[]',true,'no');

		var value_7 = get_field_value('traveltype[]',true,'international');

		if(value_1.toLowerCase() != value_2 && value_3.toLowerCase() != value_4 && value_5.toLowerCase() == 'no' && value_7.toLowerCase() == 'international'){

			form.querySelector('[name="beforeairportaddress"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'stopover1date' || name == 'flight1date' || name == 'stopover[]'){

		var value_1 = get_field_value('stopover1date',true,value_2);

		var value_2 = get_field_value('flight1date',true,value_2);

		if(value_1.toLowerCase() == value_2 && form.querySelector('[name="stopover[]"]').checked == true){

			form.querySelector('[name="beforeairportaddress"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'flight1date'){

		var value_1 = get_field_value('departuredate1',true,value_2);

		var value_2 = get_field_value('flight1date',true,value_2);

		if(value_1.toLowerCase() == value_2){

			form.querySelector('[name="beforeairportaddress"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'traveltype[]'){

		var value_1 = get_field_value('traveltype[]',true,'in_country');

		if(value_1.toLowerCase() == 'in_country'){

			form.querySelector('[name="destination"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="when_do_you_leave_from_here?_label"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="returnstartdate"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'traveltype[]'){

		var value_1 = get_field_value('traveltype[]',true,'in_country');

		if(value_1.toLowerCase() != 'in_country'){

			form.querySelector('[name="destination"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="when_do_you_leave_from_here?_label"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="returnstartdate"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'custom_airport'){

		var value_3 = get_field_value('custom_airport',true,'');

		if(value_3.toLowerCase() != ''){
			var replacement_value = 'Abroad';
			change_field_value('destination', replacement_value);
		}
	}
	if(name == 'custom_airport'){

		var value_3 = get_field_value('custom_airport',true,'');

		if(value_3.toLowerCase() == ''){
			var replacement_value = '';
			change_field_value('destination', replacement_value);
		}
	}
	if(name == 'roundtrip[]' || name == 'fromorto[]'){

		var value_1 = get_field_value('roundtrip[]',true,'no');

		var value_3 = get_field_value('fromorto[]',true,'to_nigeria');

		if(value_1.toLowerCase() == 'no' && value_3.toLowerCase() != 'to_nigeria'){

			form.querySelector('[name="return_trip_details_formstep"]').classList.add('hidden');
		}
	}
	if(name == 'fromorto[]' || name == 'roundtrip[]'){

		var value_1 = get_field_value('fromorto[]',true,'to_nigeria');

		var value_3 = get_field_value('roundtrip[]',true,'yes');

		if(value_1.toLowerCase() == 'to_nigeria' || value_3.toLowerCase() == 'yes'){

			form.querySelector('[name="return_trip_details_formstep"]').classList.remove('hidden');
		}
	}
	if(name == 'roundtrip[]'){

		var value_1 = get_field_value('roundtrip[]',true,'no');

		var value_3 = get_field_value('fromorto[]',true,'to_nigeria');

		if(value_1.toLowerCase() == 'no' && value_3.toLowerCase() != 'to_nigeria'){
			var replacement_value = '';
			change_field_value('returnstartdate', replacement_value);
		}
	}
	if(name == 'flight1date'){
			var replacement_value = get_field_value('flight1date')
			change_field_property('returnstartdate', 'min', replacement_value);
	}
	if(name == 'stopover1date'){

		var value_3 = get_field_value('flight1date',true,'');

		if(value_3.toLowerCase() == ''){
			var replacement_value = get_field_value('stopover1date')
			change_field_property('returnstartdate', 'min', replacement_value);
		}
	}
	if(name == 'departuredate1'){

		var value_3 = get_field_value('stopover1date',true,'');

		var value_5 = get_field_value('flight1date',true,'');

		if(value_3.toLowerCase() == '' && value_5.toLowerCase() == ''){
			var replacement_value = get_field_value('departuredate1')
			change_field_property('returnstartdate', 'min', replacement_value);
		}
	}
	if(name == 'traveltype[]'){

		var value_1 = get_field_value('traveltype[]',true,'international');

		if(value_1.toLowerCase() == 'international'){

			form.querySelector('[name="return_airport[]"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="when_does_your_flight_arrive?_label"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="flight2date"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="arrival_time"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="flightnumber_label"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="flightnr2"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="when_do_you_leave_for_your_next_destination?_label"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="departfrominboundairport"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'traveltype[]'){

		var value_1 = get_field_value('traveltype[]',true,'international');

		if(value_1.toLowerCase() != 'international'){

			form.querySelector('[name="return_airport[]"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="when_does_your_flight_arrive?_label"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="flight2date"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="arrival_time"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="flightnumber_label"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="flightnr2"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="when_do_you_leave_for_your_next_destination?_label"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="departfrominboundairport"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'departure_airport[]'){

		var value_3 = get_field_value('roundtrip[]',true,'yes');

		if(value_3.toLowerCase() == 'yes'){
			var replacement_value = get_field_value('departure_airport[]')
			change_field_value('return_airport[]', replacement_value);
		}
	}
	if(name == 'return_airport[]'){

		var value_1 = get_field_value('return_airport[]',true,'other');

		if(value_1.toLowerCase() == 'other'){

			form.querySelector('[name="custom_return_airport"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'return_airport[]'){

		var value_1 = get_field_value('return_airport[]',true,'other');

		if(value_1.toLowerCase() != 'other'){

			form.querySelector('[name="custom_return_airport"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'custom_airport'){

		var value_3 = get_field_value('roundtrip[]',true,'yes');

		if(value_3.toLowerCase() == 'yes'){
			var replacement_value = get_field_value('custom_airport')
			change_field_value('custom_return_airport', replacement_value);
		}
	}
	if(name == 'departure_airport[]'){

		var value_3 = get_field_value('departure_airport[]',true,'other');

		var value_5 = get_field_value('roundtrip[]',true,'yes');

		if(value_3.toLowerCase() != 'other' && value_5.toLowerCase() == 'yes'){
			var replacement_value = get_field_value('departure_airport[]')
			change_field_value('custom_return_airport', replacement_value);
		}
	}
	if(name == 'departure_airport[]'){

		var value_1 = get_field_value('departure_airport[]',true,'other');

		if(value_1.toLowerCase() == 'other'){
			var replacement_value = '';
			change_field_property('custom_return_airport', '', replacement_value);
		}
	}
	if(name == 'return_airport[]'){

		var value_1 = get_field_value('fromorto[]',true,'to_nigeria');

		var value_5 = get_field_value('return_airport[]',true,'other');

		if(value_1.toLowerCase() == 'to_nigeria' && value_5.toLowerCase() != 'other'){
			var replacement_value = get_field_value('return_airport[]')
			change_field_value('custom_return_airport', replacement_value);
		}
	}
	if(name == 'returnstartdate'){
			var replacement_value = get_field_value('returnstartdate')
			change_field_property('flight2date', 'min', replacement_value);
	}
	if(name == 'flight2date'){
			var replacement_value = get_field_value('flight2date')
			change_field_property('departfrominboundairport', 'min', replacement_value);
	}
	if(name == 'departfrominboundairport'){

		var value_3 = get_field_value('flight2date',true,value_4);

		var value_4 = get_field_value('departfrominboundairport',true,value_4);

		var value_5 = get_field_value('simtransport[]',true,'no');

		if(value_3.toLowerCase() != value_4 && value_5.toLowerCase() == 'no'){

		}
	}
	if(name == 'departfrominboundairport'){

		var value_3 = get_field_value('flight2date',true,value_4);

		var value_4 = get_field_value('departfrominboundairport',true,value_4);

		if(value_3.toLowerCase() == value_4){

		}
	}
	if(name == 'stopover[]' || name == 'roundtrip[]'){

		var value_3 = get_field_value('roundtrip[]',true,'yes');

		if(form.querySelector('[name="stopover[]"]').checked == true && value_3.toLowerCase() == 'yes'){
			var replacement_value = 'true';
			change_field_property('return_stopover[]', 'checked', replacement_value);
		}
	}
	if(name == 'stopover[]'){

		if(el.checked == false){
			var replacement_value = '';
			change_field_property('return_stopover[]', 'checked', replacement_value);
		}
	}
	if(name == 'return_stopover[]'){

		if(el.checked == true){

			form.querySelector('[name="stopover2address"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="when_do_you_leave?_label"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="stopover2date"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'return_stopover[]'){

		if(el.checked == false){

			form.querySelector('[name="stopover2address"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="when_do_you_leave?_label"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="stopover2date"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'return_stopover[]'){

		if(el.checked == false){
			var replacement_value = '';
			change_field_value('stopover2address', replacement_value);
		}
	}
	if(name == 'stopover1address'){

		var value_3 = get_field_value('roundtrip[]',true,'yes');

		if(value_3.toLowerCase() == 'yes'){
			var replacement_value = get_field_value('stopover1address')
			change_field_value('stopover2address', replacement_value);
		}
	}
	if(name == 'departfrominboundairport'){
			var replacement_value = get_field_value('departfrominboundairport')
			change_field_property('stopover2date', 'min', replacement_value);
	}
	if(name == 'returnstartdate'){

		var value_3 = get_field_value('departfrominboundairport',true,'');

		if(value_3.toLowerCase() == ''){
			var replacement_value = get_field_value('returnstartdate')
			change_field_property('stopover2date', 'min', replacement_value);
		}
	}
	if(name == 'departure1address' || name == 'roundtrip[]'){

		var value_1 = get_field_value('roundtrip[]',true,'yes');

		var value_5 = get_field_value('roundtrip[]',true,'yes');

		if(value_1.toLowerCase() == 'yes' || value_5.toLowerCase() == 'yes'){
			var replacement_value = get_field_value('departure1address')
			change_field_value('final_destination', replacement_value);
		}
	}
	if(name == 'roundtrip[]'){

		var value_1 = get_field_value('roundtrip[]',true,'no');

		if(value_1.toLowerCase() == 'no'){
			var replacement_value = '';
			change_field_value('final_destination', replacement_value);
		}
	}
	if(name == 'travel[6][date]'){

		var value_3 = get_field_value('travel[1][date]',true,'');
		var calculated_value_2 = (Date.parse(value_5) - Date.parse(value_6))/ (1000 * 60 * 60 * 24);

		var value_5 = get_field_value('travel[6][date]',true,2);

		var value_6 = get_field_value('travel[1][date]',true,2);

		if(value_3.toLowerCase() != '' && calculated_value_2 > 2){

			form.querySelector('[name="colleaguesnotified[]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'travel[5][date]'){

		var value_3 = get_field_value('travel[1][date]',true,'');

		var value_5 = get_field_value('travel[6][date]',true,'');
		var calculated_value_3 = (Date.parse(value_7) - Date.parse(value_8))/ (1000 * 60 * 60 * 24);

		var value_7 = get_field_value('travel[5][date]',true,2);

		var value_8 = get_field_value('travel[1][date]',true,2);

		if(value_3.toLowerCase() != '' && value_5.toLowerCase() == '' && calculated_value_3 > 2){

			form.querySelector('[name="colleaguesnotified[]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'travel[4][date]'){

		var value_3 = get_field_value('travel[5][date]',true,'');

		var value_5 = get_field_value('travel[6][date]',true,'');
		var calculated_value_3 = (Date.parse(value_7) - Date.parse(value_8))/ (1000 * 60 * 60 * 24);

		var value_7 = get_field_value('travel[4][date]',true,2);

		var value_8 = get_field_value('travel[1][date]',true,2);

		if(value_3.toLowerCase() == '' && value_5.toLowerCase() == '' && calculated_value_3 > 2){

			form.querySelector('[name="colleaguesnotified[]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'fromorto[]'){

		var value_1 = get_field_value('fromorto[]',true,'to_nigeria');

		if(value_1.toLowerCase() == 'to_nigeria'){

			form.querySelector('[name="leg_1_label"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="travel[1][from]"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="travel[1][date]"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="travel[1][to]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'fromorto[]'){

		var value_1 = get_field_value('fromorto[]',true,'to_nigeria');

		if(value_1.toLowerCase() != 'to_nigeria'){

			form.querySelector('[name="leg_1_label"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="travel[1][from]"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="travel[1][date]"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="travel[1][to]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'departure1address'){
			var replacement_value = get_field_value('departure1address')
			change_field_value('travel[1][from]', replacement_value);
	}
	if(name == 'travel[1][date]' || name == 'travel[1][to]'){

		var value_1 = get_field_value('travel[1][date]',true,'');

		var value_3 = get_field_value('travel[1][to]',true,'');

		if(value_1.toLowerCase() != '' && value_3.toLowerCase() != ''){

			form.querySelector('[name="travel[1][from]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'travel[1][to]' || name == 'travel[1][date]'){

		var value_1 = get_field_value('travel[1][to]',true,'');

		var value_3 = get_field_value('travel[1][date]',true,'');

		if(value_1.toLowerCase() == '' && value_3.toLowerCase() == ''){

			form.querySelector('[name="travel[1][from]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'departuredate1'){
			var replacement_value = get_field_value('departuredate1')
			change_field_value('travel[1][date]', replacement_value);
	}
	if(name == 'stopover1address'){

		var value_3 = get_field_value('stopover1address',true,'');

		if(value_3.toLowerCase() != ''){
			var replacement_value = get_field_value('stopover1address')
			change_field_value('travel[1][to]', replacement_value);
		}
	}
	if(name == 'travel[3][from]'){

		var value_3 = get_field_value('travel[3][from]',true,'');

		var value_5 = get_field_value('stopover1address',true,'');

		if(value_3.toLowerCase() != '' && value_5.toLowerCase() == ''){
			var replacement_value = get_field_value('travel[3][from]')
			change_field_value('travel[1][to]', replacement_value);
		}
	}
	if(name == 'destination'){

		var value_3 = get_field_value('destination',true,'');

		var value_5 = get_field_value('stopover1address',true,'');

		var value_7 = get_field_value('travel[3][from]',true,'');

		if(value_3.toLowerCase() != '' && value_5.toLowerCase() == '' && value_7.toLowerCase() == ''){
			var replacement_value = get_field_value('destination')
			change_field_value('travel[1][to]', replacement_value);
		}
	}
	if(name == 'stopover1address'){

		var value_3 = get_field_value('stopover1address',true,'');

		var value_5 = get_field_value('travel[3][from]',true,'');

		if(value_3.toLowerCase() == '' && value_5.toLowerCase() == ''){
			var replacement_value = get_field_value('destination')
			change_field_value('travel[1][to]', replacement_value);
		}
	}
	if(name == 'stopover1address'){

		var value_3 = get_field_value('stopover1address',true,'');

		var value_5 = get_field_value('travel[3][from]',true,'');

		if(value_3.toLowerCase() == '' && value_5.toLowerCase() != ''){
			var replacement_value = get_field_value('travel[3][from]')
			change_field_value('travel[1][to]', replacement_value);
		}
	}
	if(name == 'stopover[]'){

		if(el.checked == true){

			form.querySelector('[name="leg_2_label"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="travel[2][from]"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="travel[2][date]"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="travel[2][to]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'stopover[]'){

		if(el.checked == false){

			form.querySelector('[name="leg_2_label"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="travel[2][from]"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="travel[2][date]"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="travel[2][to]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'stopover1address'){
			var replacement_value = get_field_value('stopover1address')
			change_field_value('travel[2][from]', replacement_value);
	}
	if(name == 'travel[2][date]' || name == 'travel[2][to]'){

		var value_1 = get_field_value('travel[2][date]',true,'');

		var value_3 = get_field_value('travel[2][to]',true,'');

		if(value_1.toLowerCase() != '' || value_3.toLowerCase() != ''){

			form.querySelector('[name="travel[2][from]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'travel[2][to]' || name == 'travel[2][date]'){

		var value_1 = get_field_value('travel[2][to]',true,'');

		var value_3 = get_field_value('travel[2][date]',true,'');

		if(value_1.toLowerCase() == '' && value_3.toLowerCase() == ''){

			form.querySelector('[name="travel[2][from]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'stopover1date'){
			var replacement_value = get_field_value('stopover1date')
			change_field_value('travel[2][date]', replacement_value);
	}
	if(name == 'destination'){

		var value_3 = get_field_value('destination',true,'');

		var value_7 = get_field_value('traveltype[]',true,'in_country');

		if(value_3.toLowerCase() != '' && form.querySelector('[name="stopover[]"]').checked == true && value_7.toLowerCase() == 'in_country'){
			var replacement_value = get_field_value('destination')
			change_field_value('travel[2][to]', replacement_value);
		}
	}
	if(name == 'stopover1address'){

		var value_3 = get_field_value('destination',true,'');

		var value_5 = get_field_value('traveltype[]',true,'in_country');

		if(value_3.toLowerCase() != '' && value_5.toLowerCase() == 'in_country'){
			var replacement_value = get_field_value('destination')
			change_field_value('travel[2][to]', replacement_value);
		}
	}
	if(name == 'stopover[]' || name == 'traveltype[]'){

		var value_3 = get_field_value('traveltype[]',true,'in_country');

		if(form.querySelector('[name="stopover[]"]').checked == false && value_3.toLowerCase() == 'in_country'){
			var replacement_value = '';
			change_field_value('travel[2][to]', replacement_value);
		}
	}
	if(name == 'custom_airport'){

		if(form.querySelector('[name="stopover[]"]').checked == true){
			var replacement_value = get_field_value('custom_airport')
			change_field_value('travel[2][to]', replacement_value);
		}
	}
	if(name == 'stopover[]' || name == 'custom_airport'){

		var value_3 = get_field_value('custom_airport',true,'');

		if(form.querySelector('[name="stopover[]"]').checked == true && value_3.toLowerCase() != ''){
			var replacement_value = get_field_value('custom_airport')
			change_field_value('travel[2][to]', replacement_value);
		}
	}
	if(name == 'travel[3][from]' || name == 'travel[3][date]' || name == 'travel[3][to]'){

		var value_1 = get_field_value('travel[3][from]',true,'');

		var value_3 = get_field_value('travel[3][date]',true,'');

		var value_5 = get_field_value('travel[3][to]',true,'');

		if(value_1.toLowerCase() != '' || value_3.toLowerCase() != '' || value_5.toLowerCase() != ''){

			form.querySelector('[name="leg_3_label"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="travel[3][from]"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="travel[3][date]"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="travel[3][to]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'travel[3][to]' || name == 'travel[3][from]' || name == 'travel[3][date]'){

		var value_1 = get_field_value('travel[3][to]',true,'');

		var value_3 = get_field_value('travel[3][from]',true,'');

		var value_5 = get_field_value('travel[3][date]',true,'');

		if(value_1.toLowerCase() == '' && value_3.toLowerCase() == '' && value_5.toLowerCase() == ''){

			form.querySelector('[name="leg_3_label"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="travel[3][from]"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="travel[3][date]"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="travel[3][to]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'custom_airport'){
			var replacement_value = get_field_value('custom_airport')
			change_field_value('travel[3][from]', replacement_value);
	}
	if(name == 'flight1date'){
			var replacement_value = get_field_value('flight1date')
			change_field_value('travel[3][date]', replacement_value);
	}
	if(name == 'destination'){

		var value_3 = get_field_value('traveltype[]',true,'international');

		if(value_3.toLowerCase() == 'international'){
			var replacement_value = get_field_value('destination')
			change_field_value('travel[3][to]', replacement_value);
		}
	}
	if(name == 'travel[3][flightnr]'){

		var value_1 = get_field_value('travel[3][flightnr]',true,'');

		if(value_1.toLowerCase() != ''){

		}
	}
	if(name == 'travel[3][flightnr]'){

		var value_1 = get_field_value('travel[3][flightnr]',true,'');

		if(value_1.toLowerCase() == ''){

		}
	}
	if(name == 'flightnr1'){
			var replacement_value = get_field_value('flightnr1')
			change_field_value('travel[3][flightnr]', replacement_value);
	}
	if(name == 'travel[4][from]' || name == 'travel[4][date]' || name == 'travel[4][to]'){

		var value_1 = get_field_value('travel[4][from]',true,'');

		var value_3 = get_field_value('travel[4][date]',true,'');

		var value_5 = get_field_value('travel[4][to]',true,'');

		if(value_1.toLowerCase() != '' || value_3.toLowerCase() != '' || value_5.toLowerCase() != ''){

			form.querySelector('[name="leg_4_label"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="travel[4][from]"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="travel[4][date]"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="travel[4][to]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'travel[4][from]' || name == 'travel[4][date]' || name == 'travel[4][to]'){

		var value_1 = get_field_value('travel[4][from]',true,'');

		var value_3 = get_field_value('travel[4][date]',true,'');

		var value_5 = get_field_value('travel[4][to]',true,'');

		if(value_1.toLowerCase() == '' && value_3.toLowerCase() == '' && value_5.toLowerCase() == ''){

			form.querySelector('[name="leg_4_label"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="travel[4][from]"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="travel[4][date]"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="travel[4][to]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'destination'){

		var value_3 = get_field_value('roundtrip[]',true,'yes');

		if(value_3.toLowerCase() == 'yes'){
			var replacement_value = get_field_value('destination')
			change_field_value('travel[4][from]', replacement_value);
		}
	}
	if(name == 'roundtrip[]'){

		var value_1 = get_field_value('roundtrip[]',true,'yes');

		if(value_1.toLowerCase() == 'yes'){
			var replacement_value = '';
			change_field_value('travel[4][from]', replacement_value);
		}
	}
	if(name == 'fromorto[]'){

		var value_1 = get_field_value('fromorto[]',true,'to_nigeria');

		if(value_1.toLowerCase() == 'to_nigeria'){
			var replacement_value = 'Abroad';
			change_field_value('travel[4][from]', replacement_value);
		}
	}
	if(name == 'fromorto[]'){

		var value_1 = get_field_value('fromorto[]',true,'to_nigeria');

		if(value_1.toLowerCase() != 'to_nigeria'){
			var replacement_value = '';
			change_field_value('travel[4][from]', replacement_value);
		}
	}
	if(name == 'travel[4][date]' || name == 'travel[4][to]'){

		var value_1 = get_field_value('travel[4][date]',true,'');

		var value_3 = get_field_value('travel[4][to]',true,'');

		if(value_1.toLowerCase() != '' || value_3.toLowerCase() != ''){

			form.querySelector('[name="travel[4][from]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'travel[4][date]' || name == 'travel[4][to]'){

		var value_1 = get_field_value('travel[4][date]',true,'');

		var value_3 = get_field_value('travel[4][to]',true,'');

		if(value_1.toLowerCase() == '' && value_3.toLowerCase() == ''){

			form.querySelector('[name="travel[4][from]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'flight2date'){
			var replacement_value = get_field_value('flight2date')
			change_field_value('travel[4][date]', replacement_value);
	}
	if(name == 'returnstartdate'){
			var replacement_value = get_field_value('returnstartdate')
			change_field_value('travel[4][date]', replacement_value);
	}
	if(name == 'stopover2address'){

		var value_3 = get_field_value('stopover2address',true,'');

		var value_5 = get_field_value('traveltype[]',true,'international');

		if(value_3.toLowerCase() != '' && value_5.toLowerCase() != 'international'){
			var replacement_value = get_field_value('stopover2address')
			change_field_value('travel[4][to]', replacement_value);
		}
	}
	if(name == 'final_destination'){

		var value_1 = get_field_value('traveltype[]',true,'international');

		if(value_1.toLowerCase() != 'international' && form.querySelector('[name="return_stopover[]"]').checked == false){
			var replacement_value = get_field_value('final_destination')
			change_field_value('travel[4][to]', replacement_value);
		}
	}
	if(name == 'final_destination'){

		var value_3 = get_field_value('traveltype[]',true,'international');

		if(value_3.toLowerCase() != 'international' && form.querySelector('[name="return_stopover[]"]').checked == false){
			var replacement_value = get_field_value('final_destination')
			change_field_value('travel[4][to]', replacement_value);
		}
	}
	if(name == 'return_stopover[]' || name == 'traveltype[]'){

		var value_3 = get_field_value('traveltype[]',true,'in_country');

		if(form.querySelector('[name="return_stopover[]"]').checked == false && value_3.toLowerCase() == 'in_country'){
			var replacement_value = get_field_value('final_destination')
			change_field_value('travel[4][to]', replacement_value);
		}
	}
	if(name == 'custom_return_airport'){
			var replacement_value = get_field_value('custom_return_airport')
			change_field_value('travel[4][to]', replacement_value);
	}
	if(name == 'fromorto[]'){

		var value_1 = get_field_value('fromorto[]',true,'leaving_nigeria');

		if(value_1.toLowerCase() == 'leaving_nigeria'){
			var replacement_value = '';
			change_field_value('travel[4][to]', replacement_value);
		}
	}
	if(name == 'travel[4][flightnr]'){

		var value_1 = get_field_value('travel[4][flightnr]',true,'');

		if(value_1.toLowerCase() != ''){

			form.querySelector('[name="travel[4][flightnr]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'travel[4][flightnr]'){

		var value_1 = get_field_value('travel[4][flightnr]',true,'');

		if(value_1.toLowerCase() == ''){

			form.querySelector('[name="travel[4][flightnr]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'flightnr2'){
			var replacement_value = get_field_value('flightnr2')
			change_field_value('travel[4][flightnr]', replacement_value);
	}
	if(name == 'travel[5][from]' || name == 'travel[5][date]' || name == 'travel[5][to]'){

		var value_1 = get_field_value('travel[5][from]',true,'');

		var value_3 = get_field_value('travel[5][date]',true,'');

		var value_5 = get_field_value('travel[5][to]',true,'');

		if(value_1.toLowerCase() != '' || value_3.toLowerCase() != '' || value_5.toLowerCase() != ''){

			form.querySelector('[name="leg_5_label"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="travel[5][from]"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="travel[5][date]"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="travel[5][to]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'travel[5][from]' || name == 'travel[5][date]' || name == 'travel[5][to]'){

		var value_1 = get_field_value('travel[5][from]',true,'');

		var value_3 = get_field_value('travel[5][date]',true,'');

		var value_5 = get_field_value('travel[5][to]',true,'');

		if(value_1.toLowerCase() == '' && value_3.toLowerCase() == '' && value_5.toLowerCase() == ''){

			form.querySelector('[name="leg_5_label"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="travel[5][from]"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="travel[5][date]"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="travel[5][to]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'custom_return_airport'){
			var replacement_value = get_field_value('custom_return_airport')
			change_field_value('travel[5][from]', replacement_value);
	}
	if(name == 'departfrominboundairport'){
			var replacement_value = get_field_value('departfrominboundairport')
			change_field_value('travel[5][date]', replacement_value);
	}
	if(name == 'stopover2address'){

		var value_3 = get_field_value('stopover2address',true,'');

		var value_5 = get_field_value('traveltype[]',true,'international');

		if(value_3.toLowerCase() != '' && value_5.toLowerCase() == 'international'){
			var replacement_value = get_field_value('stopover2address')
			change_field_value('travel[5][to]', replacement_value);
		}
	}
	if(name == 'returnstartdate'){

		var value_5 = get_field_value('traveltype[]',true,'in_country');

		if(form.querySelector('[name="return_stopover[]"]').checked == false && value_5.toLowerCase() != 'in_country'){
			var replacement_value = get_field_value('final_destination')
			change_field_value('travel[5][to]', replacement_value);
		}
	}
	if(name == 'return_stopover[]' || name == 'traveltype[]'){

		var value_3 = get_field_value('traveltype[]',true,'international');

		if(form.querySelector('[name="return_stopover[]"]').checked == false && value_3.toLowerCase() == 'international'){
			var replacement_value = get_field_value('final_destination')
			change_field_value('travel[5][to]', replacement_value);
		}
	}
	if(name == 'final_destination'){

		var value_3 = get_field_value('traveltype[]',true,'in_country');

		if(value_3.toLowerCase() != 'in_country' && form.querySelector('[name="return_stopover[]"]').checked == false){
			var replacement_value = get_field_value('final_destination')
			change_field_value('travel[5][to]', replacement_value);
		}
	}
	if(name == 'return_stopover[]'){

		if(el.checked == true){

			form.querySelector('[name="leg_6_label"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="travel[6][from]"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="travel[6][date]"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="travel[6][to]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'return_stopover[]'){

		if(el.checked == false){

			form.querySelector('[name="leg_6_label"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="travel[6][from]"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="travel[6][date]"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="travel[6][to]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'stopover2address'){
			var replacement_value = get_field_value('stopover2address')
			change_field_value('travel[6][from]', replacement_value);
	}
	if(name == 'stopover2date'){
			var replacement_value = get_field_value('stopover2date')
			change_field_value('travel[6][date]', replacement_value);
	}
	if(name == 'departure1address'){

		if(form.querySelector('[name="return_stopover[]"]').checked == true){
			var replacement_value = get_field_value('departure1address')
			change_field_value('travel[6][to]', replacement_value);
		}
	}
	if(name == 'return_stopover[]'){

		if(el.checked == false){
			var replacement_value = '';
			change_field_value('travel[6][to]', replacement_value);
		}
	}
	if(name == 'return_stopover[]' || name == 'departure1address'){

		var value_3 = get_field_value('departure1address',true,'');

		if(form.querySelector('[name="return_stopover[]"]').checked == true && value_3.toLowerCase() != ''){
			var replacement_value = get_field_value('departure1address')
			change_field_value('travel[6][to]', replacement_value);
		}
	}
	if(name == 'final_destination'){

		if(form.querySelector('[name="return_stopover[]"]').checked == true){
			var replacement_value = get_field_value('final_destination')
			change_field_value('travel[6][to]', replacement_value);
		}
	}
}