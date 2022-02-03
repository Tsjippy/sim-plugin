

document.addEventListener("DOMContentLoaded", function() {
	console.log("Dynamic user_medical forms js loaded");
	
	tidy_multi_inputs();
	
	//show first tab
	currentTab = 0; // Current tab is set to be the first tab (0)
	form = document.getElementById('simnigeria_form_user_medical');
	showTab(currentTab,form); // Display the current tab
	form.querySelectorAll('select, input, textarea').forEach(el=>process_user_medical_fields(el));
});

window.addEventListener("click", user_medical_listener);
window.addEventListener("input", user_medical_listener);

user_medical_prev_el = '';
function user_medical_listener(event) {
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
	if(el == user_medical_prev_el){
		return;
	}
	user_medical_prev_el = el;
	//clear event prevenion after 200 ms
	setTimeout(function(){ user_medical_prev_el = ''; }, 200);
	
	if(name == 'nextBtn'){
		nextPrev(1);
	}else if(name == 'prevBtn'){
		nextPrev(-1);
	}

	process_user_medical_fields(el);
}

function process_user_medical_fields(el){
	var name = el.name;

	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'hepatitis_a');

		if(value_1.toLowerCase() == 'hepatitis_a'){

			form.querySelector('[name="medical[hepatitis_a_expiry_date]"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="medical[never_hepatitis_a][]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'hepatitis_a');

		if(value_1.toLowerCase() != 'hepatitis_a'){

			form.querySelector('[name="medical[hepatitis_a_expiry_date]"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="medical[never_hepatitis_a][]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'hepatitis_b');

		if(value_1.toLowerCase() == 'hepatitis_b'){

			form.querySelector('[name="medical[never_hepatitis_b][]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'hepatitis_b');

		if(value_1.toLowerCase() != 'hepatitis_b'){

			form.querySelector('[name="medical[never_hepatitis_b][]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'rabies');

		if(value_1.toLowerCase() == 'rabies'){

			form.querySelector('[name="medical[never_rabies][]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'rabies');

		if(value_1.toLowerCase() != 'rabies'){

			form.querySelector('[name="medical[never_rabies][]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'meningitis');

		if(value_1.toLowerCase() == 'meningitis'){

			form.querySelector('[name="medical[never_meningitis][]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'meningitis');

		if(value_1.toLowerCase() != 'meningitis'){

			form.querySelector('[name="medical[never_meningitis][]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'yellow_fever');

		if(value_1.toLowerCase() == 'yellow_fever'){

			form.querySelector('[name="medical[never_yellow_fewer][]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'yellow_fever');

		if(value_1.toLowerCase() != 'yellow_fever'){

			form.querySelector('[name="medical[never_yellow_fewer][]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'poliomyelitis');

		if(value_1.toLowerCase() == 'poliomyelitis'){

			form.querySelector('[name="medical[poliomyelitis_expiry_date]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'poliomyelitis');

		if(value_1.toLowerCase() != 'poliomyelitis'){

			form.querySelector('[name="medical[poliomyelitis_expiry_date]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'diphtheria_tetanus_pertussis_diteper');

		if(value_1.toLowerCase() == 'diphtheria_tetanus_pertussis_diteper'){

			form.querySelector('[name="medical[diteper_expiry_date]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'diphtheria_tetanus_pertussis_diteper');

		if(value_1.toLowerCase() != 'diphtheria_tetanus_pertussis_diteper'){

			form.querySelector('[name="medical[diteper_expiry_date]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'measles_mumps_rubella_mmr');

		if(value_1.toLowerCase() == 'measles_mumps_rubella_mmr'){

			form.querySelector('[name="medical[never_mmr][]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'measles_mumps_rubella_mmr');

		if(value_1.toLowerCase() != 'measles_mumps_rubella_mmr'){

			form.querySelector('[name="medical[never_mmr][]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'chickenpox');

		if(value_1.toLowerCase() == 'chickenpox'){

			form.querySelector('[name="medical[never_chickenpox][]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'chickenpox');

		if(value_1.toLowerCase() != 'chickenpox'){

			form.querySelector('[name="medical[never_chickenpox][]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'typhoid');

		if(value_1.toLowerCase() == 'typhoid'){

			form.querySelector('[name="medical[typhoid_expiry_date]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'typhoid');

		if(value_1.toLowerCase() != 'typhoid'){

			form.querySelector('[name="medical[typhoid_expiry_date]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'cholera');

		if(value_1.toLowerCase() == 'cholera'){

			form.querySelector('[name="medical[never_cholera][]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'cholera');

		if(value_1.toLowerCase() != 'cholera'){

			form.querySelector('[name="medical[never_cholera][]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'covid');

		if(value_1.toLowerCase() == 'covid'){

			form.querySelector('[name="medical[covid_vaccintype][]"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="medical[covid_date_1]"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="medical[covid_date_2]"]').closest('.inputwrapper').classList.remove('hidden');
			form.querySelector('[name="medical[covid_2_not_needed][]"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'covid');

		if(value_1.toLowerCase() != 'covid'){

			form.querySelector('[name="medical[covid_vaccintype][]"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="medical[covid_date_1]"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="medical[covid_date_2]"]').closest('.inputwrapper').classList.add('hidden');
			form.querySelector('[name="medical[covid_2_not_needed][]"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'other');

		if(value_1.toLowerCase() == 'other'){

			form.querySelector('[name="othervaccinationstart"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'medical[vaccinations][]'){

		var value_1 = get_field_value('medical[vaccinations][]',true,'other');

		if(value_1.toLowerCase() != 'other'){

			form.querySelector('[name="othervaccinationstart"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
}