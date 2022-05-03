//Show the position field when a ministry is checked
function change_visibility(target) {
	target.closest('span').querySelector('.ministryposition').classList.toggle('hidden');
}

async function addNewMinistry(target){
	var response = await formsubmit.submitForm(target, 'user_management/add_ministry');
	
	if(response){
		var ministry_name 		= target.closest('form').querySelector('[name="location_name"]').value;
		ministry_name			= ministry_name.charAt(0).toUpperCase() + ministry_name.slice(1);
		//Replace any spaces with underscore
		ministry_key			= ministry_name.replace(/ /g,'_');

		var html = `
		<span>
			<label>
				<input type="checkbox" class="ministry_option_checkbox" name="ministries[]" value="${ministry_key}" checked>
				<span class="optionlabel">${ministry_name}</span>
			</label>
			<label class="ministryposition" style="display:block;">
				<h4 class="labeltext">Position at ${ministry_name}:</h4>
				<input type="text" id="justadded" name="user_ministries[${ministry_key}]">
			</label>
		</span>`;
		
		document.querySelector("#ministries_list").insertAdjacentHTML('beforeEnd',html);
		
		//hide the SWAL window
		setTimeout(function(){document.querySelectorAll('.swal2-container').forEach(el=>el.remove());}, 1500);

		//focus on the newly added input
		document.getElementById('justadded').focus();
		document.getElementById('justadded').select();

		main.displayMessage(`Succesfully added ministry ${ministry_name}.`)
	}
	
	main.hideModals();
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
		main.showModal('add_ministry');
	}
	
	if(target.classList.contains('ministry_option_checkbox')){
		change_visibility(target);
	}

	if(target.name == 'add_ministry'){
		addNewMinistry(target);
	}
});