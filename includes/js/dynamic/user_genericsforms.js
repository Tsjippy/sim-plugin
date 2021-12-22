//Show the position field when a ministry is checked
function change_visibility(target) {
	target.closest('span').querySelector('.ministryposition').classList.toggle('hidden');
}

window['add_new_ministry_data'] = function(result){
	if (result.status >= 200 && result.status < 400) {
		var add_ministry_form = document.getElementById('add_ministry_form');
		
		if(add_ministry_form != null){
			var ministry_name 		= add_ministry_form.querySelector('[name="location_name"]').value;
			ministry_name			= ministry_name.charAt(0).toUpperCase() + ministry_name.slice(1);
			//Replace any spaces with underscore
			ministry_key			= ministry_name.replace(/ /g,'_');

			var html ='<span>\
				<label>\
					<input type="checkbox" class="ministry_option_checkbox" name="ministries[]" value="'+ministry_key+'" checked>\
					<span class="optionlabel">'+ministry_name+'</span>\
				</label>\
				<label class="ministryposition" style="display:block;">\
					<h4 class="labeltext">Position at '+ministry_name+':</h4>\
					<input type="text" id="justadded" name="user_ministries['+ministry_key+']">\
				</label>\
			</span>';
			
			document.querySelector("#ministries_list").insertAdjacentHTML('beforeEnd',html);
			
			//hide the SWAL window
			setTimeout(function(){document.querySelectorAll('.swal2-container').forEach(el=>el.remove());}, 1500);

			//focus on the newly added input
			document.getElementById('justadded').focus();
			document.getElementById('justadded').select();
		}
	}
	
	hide_modals();
	
	return;
}

//listen to all clicks
document.addEventListener('click',function(event) {
	var target = event.target;
	//show add ministry modal
	if(target.id == 'add-ministry-button'){
		//uncheck other and hide
		target.closest('span').querySelector('.ministry_option_checkbox').checked = false;
		target.closest('.ministryposition').classList.add('hidden');

		//Show the modal
		show_modal('add_ministry');
	}
	
	if(target.classList.contains('ministry_option_checkbox')){
		change_visibility(target);
	}
});

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
		return;
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