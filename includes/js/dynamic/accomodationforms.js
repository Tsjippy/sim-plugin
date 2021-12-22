

document.addEventListener("DOMContentLoaded", function() {
	console.log("Dynamic accomodation forms js loaded");
	
	tidy_multi_inputs();
	
	//show first tab
	currentTab = 0; // Current tab is set to be the first tab (0)
	form = document.getElementById('simnigeria_form_accomodation');
	showTab(currentTab,form); // Display the current tab
});

window.addEventListener("click", accomodation_listener);
window.addEventListener("input", accomodation_listener);

accomodation_prev_el = '';
function accomodation_listener(event) {
	var el			= event.target;
	form			= el.closest('form');
	var name		= el.name;
	if(name == '' || name == undefined){
		return;
	}
	
	//prevent duplicate event handling
	if(el == accomodation_prev_el){
		return;
	}
	accomodation_prev_el = el;
	//clear event prevenion after 200 ms
	setTimeout(function(){ accomodation_prev_el = ''; }, 200);
	
	if(name == 'nextBtn'){
		nextPrev(1);
	}else if(name == 'prevBtn'){
		nextPrev(-1);
	}

	process_accomodation_fields(el);
}

function process_accomodation_fields(el){
	var name = el.name;

	if(name == 'childcount'){

		var value_1 = get_field_value('childcount',true,0);

		if(value_1.toLowerCase() > 0){

			form.querySelector('[name="childrenage"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'childcount'){

		var value_1 = get_field_value('childcount',true,0);

		if(value_1.toLowerCase() == 0){

			form.querySelector('[name="childrenage"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
}