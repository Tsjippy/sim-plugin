var user_generics = new function(){
	document.addEventListener('DOMContentLoaded', function() {
		console.log('Dynamic user_generics forms js loaded');
		FormFunctions.tidyMultiInputs();
		form = document.querySelector('[data-formid="10"]');
		form.querySelectorAll('select, input, textarea').forEach(
			el=>user_generics.processFields(el)
		);
	});
	var prevEl = '';

	var listener = function(event) {
		var el			= event.target;
		form			= el.closest('form');
		var elName		= el.name;

		if(elName == '' || elName == undefined){
			//el is a nice select
			if(el.closest('.nice-select-dropdown') != null && el.closest('.inputwrapper') != null){
				//find the select element connected to the nice-select
				el.closest('.inputwrapper').querySelectorAll('select').forEach(select=>{
					if(el.dataset.value == select.value){
						el	= select;
						elName = select.name;
					}
				});
			}else{
				return;
			}
		}

		//prevent duplicate event handling
		if(el == prevEl){
			return;
		}
		prevEl = el;

		//clear event prevenion after 100 ms
		setTimeout(function(){ prevEl = ''; }, 100);

		if(elName == 'nextBtn'){
			FormFunctions.nextPrev(1);
		}else if(elName == 'prevBtn'){
			FormFunctions.nextPrev(-1);
		}

		user_generics.processFields(el);
	};

	window.addEventListener('click', listener);
	window.addEventListener('input', listener);

	this.processFields    = function(el){
		var elName = el.name;
		if(elName == 'nickname'){
			var nickname = FormFunctions.getFieldValue('nickname', form);
			FormFunctions.changeFieldValue('first_name_label', nickname, user_generics.processFields);
			FormFunctions.changeFieldValue('display_name', nickname, user_generics.processFields);
		}

		if(elName == 'advanced_options_button'){
			form.querySelectorAll('[name="privacy_preferences_label"], [name="privacy_explain"], [name="privacy_preference[]"], [name="nickname_label"], [name="nickname"]').forEach(el=>{
				try{
					//Make sure we only do each wrapper once by adding a temp class
					if(!el.closest('.inputwrapper').matches('.action-processed')){
						el.closest('.inputwrapper').classList.add('action-processed');
						el.closest('.inputwrapper').classList.toggle('hidden');
					}
				}catch(e){
					el.classList.toggle('hidden');
				}
			});
			document.querySelectorAll('.action-processed').forEach(el=>{el.classList.remove('action-processed')});
		}

		if(elName == 'age_preference[]'){

			if(el.checked){
				FormFunctions.changeFieldValue('privacy_preference[]', "hide_age", user_generics.processFields);
			}
		}

	};
};

//Show the position field when a ministry is checked
function change_visibility(target) {
	target.closest('span').querySelector('.ministryposition').classList.toggle('hidden');
}

async function addNewMinistry(target){
	var response = await FormSubmit.submitForm(target, 'user_management/add_ministry');
	
	if(response){
		var ministryName 		= target.closest('form').querySelector('[name="location_name"]').value;
		ministryName			= ministryName.charAt(0).toUpperCase() + ministryName.slice(1);
		//Replace any spaces with underscore
		var ministryKey			= ministryName.replace(/ /g,'_');

		var html = `
		<span>
			<label>
				<input type="checkbox" class="ministry_option_checkbox" name="ministries[]" value="${ministryKey}" checked>
				<span class="optionlabel">${ministryName}</span>
			</label>
			<label class="ministryposition" style="display:block;">
				<h4 class="labeltext">Position at ${ministryName}:</h4>
				<input type="text" id="justadded" name="user_ministries[${ministryKey}]">
			</label>
		</span>`;
		
		document.querySelector("#ministries_list").insertAdjacentHTML('beforeEnd', html);
		
		//hide the SWAL window
		setTimeout(function(){document.querySelectorAll('.swal2-container').forEach(el=>el.remove());}, 1500);

		//focus on the newly added input
		document.getElementById('justadded').focus();
		document.getElementById('justadded').select();

		Main.displayMessage(`Succesfully added ministry ${ministryName}.`)
	}
	
	Main.hideModals();
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
		Main.showModal('add_ministry');
	}
	
	if(target.classList.contains('ministry_option_checkbox')){
		change_visibility(target);
	}

	if(target.name == 'add_ministry'){
		addNewMinistry(target);
	}
});