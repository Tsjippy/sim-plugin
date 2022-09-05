//Show the position field when a ministry is checked
function change_visibility(target) {
	target.closest('span').querySelector('.ministryposition').classList.toggle('hidden');
}

async function addNewMinistry(target){
	var response = await FormSubmit.submitForm(target, 'user_management/add_ministry');
	
	if(response){
		var ministryName 		= target.closest('form').querySelector('[name="location_name"]').value;
		ministryName			= ministryName.charAt(0).toUpperCase() + ministryName.slice(1);

		var html = `
		<span>
			<label>
				<input type="checkbox" class="ministry_option_checkbox" name="ministries[]" value="${response.postId}" checked>
				<span class="optionlabel">${ministryName}</span>
			</label>
			<label class="ministryposition" style="display:block;">
				<h4 class="labeltext">Position at ${ministryName}:</h4>
				<input type="text" id="justadded" name="jobs[${postId}]">
			</label>
		</span>`;
		
		document.querySelector("#ministries_list").insertAdjacentHTML('beforeEnd', html);
		
		//hide the SWAL window
		setTimeout(function(){document.querySelectorAll('.swal2-container').forEach(el=>el.remove());}, 1500);

		//focus on the newly added input
		document.getElementById('justadded').focus();
		document.getElementById('justadded').select();

		Main.displayMessage(response.html)
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