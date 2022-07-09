var user_visa = new function(){
	document.addEventListener('DOMContentLoaded', function() {
		console.log('Dynamic user_visa forms js loaded');
		FormFunctions.tidyMultiInputs();
		form = document.querySelector('[data-formid="13"]');
		form.querySelectorAll('select, input, textarea').forEach(
			el=>user_visa.processFields(el)
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

		user_visa.processFields(el);
	};

	window.addEventListener('click', listener);
	window.addEventListener('input', listener);

	this.processFields    = function(el){
		var elName = el.name;
		if(elName == 'visa_info[permit_type][]' || elName == 'visa_info[permit_type][]'){
			var value_1 = FormFunctions.getFieldValue('visa_info[permit_type][]', form, true, 'greencard', true);
			var value_3 = FormFunctions.getFieldValue('visa_info[permit_type][]', form, true, 'accompanying', true);

			if(value_1 == 'greencard' || value_3 == 'accompanying'){
			form.querySelectorAll('[name="greencard_expiry_date_label"], [name="visa_info[passport_name]"], [name="visa_info[nin]"], [name="upload_your_passport_and_greencard_copy_in_one_file_label"], [name="visa_info[passport_and_greencard_files]_files[]"]').forEach(el=>{
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

			if(value_1 != 'greencard' && value_3 != 'accompanying'){
			form.querySelectorAll('[name="greencard_expiry_date_label"], [name="visa_info[passport_name]"], [name="visa_info[nin]"], [name="upload_your_passport_and_greencard_copy_in_one_file_label"], [name="visa_info[passport_and_greencard_files]_files[]"]').forEach(el=>{
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

		if(elName == 'visa_info[permit_type][]'){
			var value_1 = FormFunctions.getFieldValue('visa_info[permit_type][]', form, true, 'greencard', true);

			if(value_1 == 'greencard'){
			form.querySelectorAll('[name="quota_position_label"], [name="visa_info[quota_position]"], [name="positions"], [name="upload_any_qualifications_you_have_label"], [name^="visa_info[qualifications]_files[]"]').forEach(el=>{
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

			if(value_1 != 'greencard'){
			form.querySelectorAll('[name="quota_position_label"], [name="visa_info[quota_position]"], [name="positions"], [name="upload_any_qualifications_you_have_label"], [name^="visa_info[qualifications]_files[]"]').forEach(el=>{
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