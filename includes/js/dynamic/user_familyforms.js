window['add_new_relation_to_select'] = function(result, responsdata){
	if (result.status >= 200 && result.status < 400) {
		var modal		= document.getElementById('add_account_modal');
		var family_form	= document.querySelector('#simnigeria_form_user_family');

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

document.addEventListener("DOMContentLoaded", function() {
	console.log("Dynamic user_family forms js loaded");
	
	tidy_multi_inputs();
	
	//show first tab
	currentTab = 0; // Current tab is set to be the first tab (0)
	form = document.getElementById('simnigeria_form_user_family');
	showTab(currentTab,form); // Display the current tab
	form.querySelectorAll('select, input, textarea').forEach(el=>process_user_family_fields(el));
});

window.addEventListener("click", user_family_listener);
window.addEventListener("input", user_family_listener);

user_family_prev_el = '';
function user_family_listener(event) {
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
	if(el == user_family_prev_el){
		return;
	}
	user_family_prev_el = el;
	//clear event prevenion after 200 ms
	setTimeout(function(){ user_family_prev_el = ''; }, 200);
	
	if(name == 'nextBtn'){
		nextPrev(1);
	}else if(name == 'prevBtn'){
		nextPrev(-1);
	}

	process_user_family_fields(el);
}

function process_user_family_fields(el){
	var name = el.name;

	if(name == 'family[partner]'){

		var value_1 = get_field_value('family[partner]',true,'');

		if(value_1.toLowerCase() != ''){

			form.querySelector('[name="weddingdate"]').closest('.inputwrapper').classList.remove('hidden');
		}
	}
	if(name == 'family[partner]'){

		var value_1 = get_field_value('family[partner]',true,'');

		if(value_1.toLowerCase() == ''){

			form.querySelector('[name="weddingdate"]').closest('.inputwrapper').classList.add('hidden');
		}
	}
}