var user_family = new function(){
	document.addEventListener('DOMContentLoaded', function() {
		console.log('Dynamic user_family forms js loaded');
		FormFunctions.tidyMultiInputs();
		form = document.querySelector('[data-formid="12"]');
		form.querySelectorAll('select, input, textarea').forEach(
			el=>user_family.processFields(el)
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

		user_family.processFields(el);
	};

	window.addEventListener('click', listener);
	window.addEventListener('input', listener);

	this.processFields    = function(el){
		var elName = el.name;
		if(elName == 'family[partner]'){
			var value_1 = FormFunctions.getFieldValue('family[partner]', form, true, '', true);

			if(value_1 != ''){
			form.querySelectorAll('[name="married_since_label"], [name="family[weddingdate]"], [name="family[picture]_files[]"], [name^="family[children]"]').forEach(el=>{
				try{
					//Make sure we only do each wrapper once by adding a temp class
					if(!el.closest('.inputwrapper').matches('.action-processed')){
						el.closest('.inputwrapper').classList.add('action-processed');
						el.closest('.inputwrapper').classList.remove('hidden');
					}
				}catch(e){
					el.classList.remove('hidden');
				}
			});
			document.querySelectorAll('.action-processed').forEach(el=>{el.classList.remove('action-processed')});
			}

			if(value_1 == ''){
			form.querySelectorAll('[name="married_since_label"], [name="family[weddingdate]"], [name="family[picture]_files[]"], [name^="family[children]"]').forEach(el=>{
				try{
					//Make sure we only do each wrapper once by adding a temp class
					if(!el.closest('.inputwrapper').matches('.action-processed')){
						el.closest('.inputwrapper').classList.add('action-processed');
						el.closest('.inputwrapper').classList.add('hidden');
					}
				}catch(e){
					el.classList.add('hidden');
				}
			});
			document.querySelectorAll('.action-processed').forEach(el=>{el.classList.remove('action-processed')});
			}
		}

	};
};

async function submitAddAccountForm(event){
	var target		= event.target;

	var response	= await FormSubmit.submitForm(target, 'user_management/add_useraccount');

	if(response){
		var form		= target.closest('form');

		var firstName	= form.querySelector('[name="first_name"]').value;
		var lastName	= form.querySelector('[name="last_name"]').value;
		var userId		= response.user_id;

		//check if we should add a new child field
		var emptyFound	= false;
		document.querySelectorAll('select[name^="family"]').forEach(select=>{if(select.value==''){emptyFound=true}});

		if(!emptyFound){
			document.querySelector('select[name^="family"]').closest('form').querySelector('.add.button').click();
		}

		var opt 		= document.createElement('option');
		opt.value 		= userId;
		opt.innerHTML 	= firstName+' '+lastName;

		document.querySelectorAll('select[name^="family"]').forEach(select=>{
			select.appendChild(opt);

			// Make the new name selected if the there is no selection currently
			if(select.selectedIndex == 0){
				select.querySelector(`[value="${userId}"]`).defaultSelected	= true;
			}

			// Update the nice select
			select._niceselect.update();
		});

		Main.displayMessage(response.message, 'success');
	}

	Main.hideModals();
}

document.addEventListener("DOMContentLoaded",function() {
	document.querySelectorAll('[name="add_user_account_button"]').forEach(el=>el.addEventListener('click',function(){
		Main.showModal('add_account');
	}));

	document.querySelectorAll('[name="adduseraccount"]').forEach(el=>el.addEventListener('click', ev=>{
		
		ev.preventDefault();
		ev.stopPropagation();
		submitAddAccountForm(ev);
	}));
});