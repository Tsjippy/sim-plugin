

document.addEventListener("DOMContentLoaded", function() {
	console.log("Dynamic user_generics forms js loaded");
	
	tidy_multi_inputs();
	
	//show first tab
	currentTab = 0; // Current tab is set to be the first tab (0)
	form = document.getElementById('simnigeria_form_user_generics');
	showTab(currentTab,form); // Display the current tab
	form.querySelectorAll('select, input, textarea').forEach(el=>process_user_generics_fields(el));
});

window.addEventListener("click", user_generics_listener);
window.addEventListener("input", user_generics_listener);

user_generics_prev_el = '';
function user_generics_listener(event) {
	var el			= event.target;
	form			= el.closest('form');
	var name		= el.name;
	if(name == '' || name == undefined){
		if(el.closest('.nice-select-dropdown') != null){
			el.closest('.inputwrapper').querySelectorAll('select').forEach(select=>{
				if(el.dataset.value == select.value){
					el	= select;
					name = select.name;
				}
			});
		}else{
			return;
		}
	}
	
	//prevent duplicate event handling
	if(el == user_generics_prev_el){
		return;
	}
	user_generics_prev_el = el;
	//clear event prevenion after 200 ms
	setTimeout(function(){ user_generics_prev_el = ''; }, 200);
	
	if(name == 'nextBtn'){
		nextPrev(1);
	}else if(name == 'prevBtn'){
		nextPrev(-1);
	}

	process_user_generics_fields(el);
}

function process_user_generics_fields(el){
	var name = el.name;

	if(name == 'nickname'){
			var replacement_value = get_field_value('nickname')
			change_field_value('first_name_label', replacement_value);
	}
	if(name == 'advanced_options_button'){

			form.querySelector('[name="privacy_preferences_label"]').closest('.inputwrapper').classList.toggle('hidden');
			form.querySelector('[name="privacy_explain"]').closest('.inputwrapper').classList.toggle('hidden');
			form.querySelector('[name="privacy_preference[]"]').closest('.inputwrapper').classList.toggle('hidden');
			form.querySelector('[name="nickname"]').closest('.inputwrapper').classList.toggle('hidden');
	}
	if(name == 'nickname'){
			var replacement_value = get_field_value('nickname')
			change_field_value('display_name', replacement_value);
	}
}