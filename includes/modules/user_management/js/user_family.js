async function submitAddAccountForm(event){
	var target		= event.target;

	var response	= await FormSubmit.submitForm(target, 'user_management/add_useraccount');

	if(response){
		var form		= target.closest('form');
		var familyForm	= document.querySelector('#sim_form_user_family');

		var firstName	= form.querySelector('[name="first_name"]').value;
		var lastName	= form.querySelector('[name="last_name"]').value;
		var userId		= response.user_id;

		//check if we should add a new child field
		var emptyFound	= false;
		familyForm.querySelectorAll('select').forEach(select=>{if(select.value==''){emptyFound=true}});

		if(!emptyFound){
			familyForm.querySelector('.add.button').click();
		}

		var opt = document.createElement('option');
		opt.value = userId;
		opt.innerHTML = firstName+' '+lastName;

		familyForm.querySelectorAll('select').forEach(select=>{
			select.appendChild(opt);

			// Make the new name selected if the there is no selection currently
			if(select.selectedIndex == 0){
				select.querySelector(`[value="${userId}"]`).defaultSelected	= true;
			}

			// Update the nice select
			select._niceselect.update();
		});

		main.displayMessage(response.message, 'success');
	}

	main.hideModals();
};

document.addEventListener("DOMContentLoaded",function() {
	document.querySelectorAll('[name="add_user_account_button"]').forEach(el=>el.addEventListener('click',function(event){
		main.showModal('add_account');
	}));

	document.querySelectorAll('[name="adduseraccount"]').forEach(el=>el.addEventListener('click', ev=>{
		submitAddAccountForm(ev);
	}));
});