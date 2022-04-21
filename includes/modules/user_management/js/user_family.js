async function submitAddAccountForm(event){
	var target		= event.target;

	var response	= await submitForm(target, 'user_management/add_useraccount');

	if(response){
		var form		= target.closest('form');
		var family_form	= document.querySelector('#sim_form_user_family');

		var first_name	= form.querySelector('[name="first_name"]').value;
		var last_name	= form.querySelector('[name="last_name"]').value;
		var userid		= response.user_id;

		//check if we should add a new child field
		var empty_found	= false;
		family_form.querySelectorAll('select').forEach(select=>{if(select.value==''){empty_found=true}});

		if(!empty_found){
			family_form.querySelector('.add.button').click();
		}

		var opt = document.createElement('option');
		opt.value = userid;
		opt.innerHTML = first_name+' '+last_name;

		family_form.querySelectorAll('select').forEach(select=>{
			select.appendChild(opt);

			// Make the new name selected if the there is no selection currently
			if(select.selectedIndex == 0){
				select.querySelector(`[value="${userid}"]`).defaultSelected	= true;
			}

			// Update the nice select
			select._niceselect.update();
		});

		display_message(response.message, 'success');
	}

	hide_modals();
};

document.addEventListener("DOMContentLoaded",function() {
	document.querySelectorAll('[name="add_user_account_button"]').forEach(el=>el.addEventListener('click',function(event){
		show_modal('add_account');
	}));

	document.querySelectorAll('[name="adduseraccount"]').forEach(el=>el.addEventListener('click', ev=>{
		submitAddAccountForm(ev);
	}));
});