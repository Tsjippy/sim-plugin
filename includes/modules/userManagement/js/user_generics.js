//Show the position field when a ministry is checked
function changeVisibility(target) {
	target.closest('li').querySelectorAll('.ministryposition').forEach(label=>{
		label.classList.toggle('hidden');
		label.querySelectorAll('input').forEach(el=>el.value = '');
	});
}

async function addNewMinistry(target){
	var response = await FormSubmit.submitForm(target, 'user_management/add_ministry');
	
	if(response){
		var ministryName 		= target.closest('form').querySelector('[name="location_name"]').value;
		ministryName			= ministryName.charAt(0).toUpperCase() + ministryName.slice(1);

		var html = `
		<li style="list-style-type: none"> 
			<label>
				<input type="checkbox" class="ministry_option_checkbox" name="ministries[]" value="${response.postId}" checked>
				<span class="optionlabel">${ministryName}</span>
			</label>
			<label class="ministryposition" style="display:block;">
				<h4 class="labeltext">Position at ${ministryName}:</h4>
				<input type="text" id="justadded" name="jobs[${response.postId}]">
			</label>
		</li>`;
		
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
document.addEventListener('click', function(event) {
	var target = event.target;
	//show add ministry modal
	if(target.id == 'add-ministry-button'){
		//uncheck other and hide
		target.closest('li').querySelector('.ministry_option_checkbox').checked = false;
		target.closest('.ministryposition').classList.add('hidden');

		//Show the modal
		Main.showModal('add_ministry');
	}
	
	if(target.matches('.ministry_option_checkbox')){
		changeVisibility(target);
	}

	if(target.name == 'add_ministry'){
		addNewMinistry(target);
	}
});

function onBlur(ev){
	document.querySelectorAll(`.ministryposition input[type="text"][name="${ev.target.name}"]`).forEach(input => {
		// set value
		input.value = ev.target.value;

		// make visible
		input.closest('.ministryposition').classList.remove('hidden');

		// check the ministry
		input.closest('li').querySelector('.ministry_option_checkbox').checked	= true;
	});
}

document.addEventListener("DOMContentLoaded", function() {
	// Add the value to all inputs with the same name
	document.querySelectorAll('.ministryposition input[type="text"]').forEach(el => {
		el.addEventListener('blur', onBlur);
	});
});