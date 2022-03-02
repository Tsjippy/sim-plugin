window['add_new_relation_to_select'] = function(result, responsdata){
	if (result.status >= 200 && result.status < 400) {
		var modal		= document.getElementById('add_account_modal');
		var family_form	= document.querySelector('#sim_form_user_family');

		var first_name	= modal.querySelector('[name="first_name"]').value;
		var last_name	= modal.querySelector('[name="last_name"]').value;
		var userid		= responsdata.id;

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
			select._niceselect.update();
		});

		hide_modals();
	}
};

document.addEventListener("DOMContentLoaded",function() {
	document.querySelectorAll('[name="add_user_account_button"]').forEach(el=>el.addEventListener('click',function(event){
		show_modal('add_account');
	}));
});